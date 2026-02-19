/**
 * Glory CLI — Schema Generator
 *
 * Lee los archivos *Schema.php de App/Config/Schema/ y genera:
 * - PHP: {Tabla}Cols.php (constantes de columna)
 * - PHP: {Tabla}DTO.php (clase tipada con desdeRow)
 * - TS: schema.ts (interfaces TypeScript)
 *
 * Uso: npx glory schema:generate
 */

import { readFileSync, writeFileSync, mkdirSync, readdirSync, existsSync } from 'node:fs';
import { resolve, basename } from 'node:path';
import { getProjectRoot, toPascalCase, log } from './utils.mjs';
import { generarRepositorios } from './repositoryGenerate.mjs';

/*
 * Extraer entradas de columna de un bloque PHP respetando arrays anidados.
 * Solo extrae entradas al nivel 0 de profundidad (columnas, no propiedades internas).
 * Evita que 'check' => [...] dentro de una columna se confunda con otra columna.
 */
function extraerEntradasColumna(bloque) {
    const entradas = [];
    let pos = 0;

    while (pos < bloque.length) {
        /* Buscar patrón 'nombre' => [ solo al nivel 0 */
        const resto = bloque.substring(pos);
        const m = resto.match(/^[\s,]*'([a-z_]+)'\s*=>\s*\[/);
        if (!m) {
            /* Avanzar un carácter si no hay match al inicio */
            pos++;
            continue;
        }

        const nombre = m[1];
        pos += m[0].length;

        /* Avanzar respetando niveles de [] para capturar el contenido completo */
        let nivel = 1;
        const inicio = pos;
        while (pos < bloque.length && nivel > 0) {
            if (bloque[pos] === '[') nivel++;
            else if (bloque[pos] === ']') nivel--;
            if (nivel > 0) pos++;
        }

        const props = bloque.substring(inicio, pos);
        entradas.push({ nombre, props });

        /* Saltar el ] de cierre */
        pos++;
    }

    return entradas;
}

/* Parsear un archivo *Schema.php y extraer tabla + columnas */
function parsearSchema(contenido, nombreArchivo) {
    /* Extraer nombre de tabla */
    const tablaMatch = contenido.match(/return\s+['"]([a-z_]+)['"]/);
    if (!tablaMatch) {
        log(`No se pudo encontrar nombre de tabla en ${nombreArchivo}`, 'warn');
        return null;
    }
    const tabla = tablaMatch[1];

    /* Extraer bloque de columnas */
    const columnasMatch = contenido.match(/public\s+function\s+columnas\s*\(\s*\)\s*:\s*array\s*\{[\s\S]*?return\s*\[([\s\S]*?)\];\s*\}/);
    if (!columnasMatch) {
        log(`No se pudo parsear columnas en ${nombreArchivo}`, 'warn');
        return null;
    }

    const bloqueColumnas = columnasMatch[1];
    const columnas = [];

    /*
     * Parsear cada columna con soporte para arrays anidados (ej: 'check' => [...]).
     * El regex simple [^\]]* falla con arrays anidados, así que extraemos
     * cada bloque respetando el nivel de profundidad de corchetes.
     */
    const entradas = extraerEntradasColumna(bloqueColumnas);
    for (const { nombre, props } of entradas) {

        const col = { nombre };

        /* Tipo */
        const tipoM = props.match(/'tipo'\s*=>\s*'([a-z]+)'/);
        col.tipo = tipoM ? tipoM[1] : 'string';

        /* PK */
        col.pk = /['"]pk['"]\s*=>\s*true/.test(props);

        /* Nullable */
        col.nullable = /['"]nullable['"]\s*=>\s*true/.test(props);

        /* Default */
        const defM = props.match(/'default'\s*=>\s*(?:'([^']*)'|(\d+(?:\.\d+)?)|(\w+))/);
        if (defM) {
            col.default = defM[1] ?? defM[2] ?? defM[3];
        }

        /* Unique */
        col.unico = /['"]unico['"]\s*=>\s*true/.test(props);

        /* Max */
        const maxM = props.match(/'max'\s*=>\s*(\d+)/);
        col.max = maxM ? parseInt(maxM[1]) : null;

        /* Check */
        const checkM = props.match(/'check'\s*=>\s*\[([^\]]+)\]/);
        if (checkM) {
            col.check = checkM[1].match(/'([^']+)'/g)?.map(s => s.replace(/'/g, '')) || [];
        }

        /* Ref */
        const refM = props.match(/'ref'\s*=>\s*'([^']+)'/);
        col.ref = refM ? refM[1] : null;

        columnas.push(col);
    }

    if (columnas.length === 0) {
        log(`No se encontraron columnas en ${nombreArchivo}`, 'warn');
        return null;
    }

    /* Extraer nombre de clase para derivar nombre de DTO */
    const claseM = contenido.match(/class\s+(\w+)Schema/);
    const nombreClase = claseM ? claseM[1] : toPascalCase(tabla);

    return { tabla, columnas, nombreClase };
}

/* Mapeo tipo schema → tipo PHP */
function tipoPHP(tipo) {
    const mapa = { int: 'int', float: 'float', decimal: 'float', string: 'string', text: 'string', bool: 'bool', datetime: 'string', json: 'array', array: 'array', vector: 'string' };
    return mapa[tipo] || 'mixed';
}

/* Mapeo tipo schema → tipo TS */
function tipoTS(tipo, col) {
    /* Si tiene check con valores string, generar union literal */
    if (col.check && (tipo === 'string')) {
        return col.check.map(v => `'${v}'`).join(' | ');
    }
    const mapa = { int: 'number', float: 'number', decimal: 'number', string: 'string', text: 'string', bool: 'boolean', datetime: 'string', json: 'Record<string, unknown>', array: 'string[]', vector: 'number[]' };
    return mapa[tipo] || 'unknown';
}

/* Convertir snake_case a UPPER_SNAKE_CASE */
function toUpperSnake(str) {
    return str.toUpperCase();
}

/* Convertir snake_case a camelCase */
function snakeToCamel(str) {
    return str.replace(/_([a-z])/g, (_, c) => c.toUpperCase());
}

/*
 * Generar archivo {Tabla}Enums.php con constantes de valores permitidos (check).
 * Solo se genera si alguna columna tiene restricción check.
 */
function generarEnums(schema) {
    /* Filtrar columnas que tienen check */
    const colsConCheck = schema.columnas.filter(c => c.check && c.check.length > 0);
    if (colsConCheck.length === 0) return null;

    const bloques = colsConCheck.map(col => {
        const prefijo = toUpperSnake(col.nombre);
        const constantes = col.check.map(valor => {
            /* Convertir el valor a UPPER_SNAKE para la constante: en_supervision → EN_SUPERVISION */
            const sufijo = valor.toUpperCase().replace(/[^A-Z0-9]+/g, '_').replace(/^_|_$/g, '');
            return `    const ${prefijo}_${sufijo} = '${valor}';`;
        }).join('\n');
        return `    /* Valores para columna "${col.nombre}" */\n${constantes}`;
    }).join('\n\n');

    return `<?php

/* ARCHIVO AUTO-GENERADO por Glory Schema Generator — NO EDITAR */
/* Fuente: App/Config/Schema/${schema.nombreClase}Schema.php */

namespace App\\Config\\Schema\\_generated;

final class ${schema.nombreClase}Enums
{
${bloques}
}
`;
}

/* Generar archivo {Tabla}Cols.php */
function generarCols(schema) {
    const constantes = schema.columnas.map(col => {
        return `    const ${toUpperSnake(col.nombre)} = '${col.nombre}';`;
    }).join('\n');

    const listaTodas = schema.columnas.map(c => `'${c.nombre}'`).join(', ');

    return `<?php

/* ARCHIVO AUTO-GENERADO por Glory Schema Generator — NO EDITAR */
/* Fuente: App/Config/Schema/${schema.nombreClase}Schema.php */

namespace App\\Config\\Schema\\_generated;

final class ${schema.nombreClase}Cols
{
    const TABLA = '${schema.tabla}';

${constantes}

    /* Lista completa de columnas para validación */
    const TODAS = [${listaTodas}];
}
`;
}

/* Generar archivo {Tabla}DTO.php */
function generarDTO(schema) {
    const propiedades = schema.columnas.map(col => {
        const tipo = tipoPHP(col.tipo);
        const nullable = col.nullable ? '?' : '';
        return `        public readonly ${nullable}${tipo} $${snakeToCamel(col.nombre)}`;
    }).join(',\n');

    /* Generar body de desdeRow */
    const asignaciones = schema.columnas.map(col => {
        const camel = snakeToCamel(col.nombre);
        const tipo = tipoPHP(col.tipo);
        let cast;
        switch (tipo) {
            case 'int': cast = `(int) `; break;
            case 'float': cast = `(float) `; break;
            case 'bool': cast = `(bool) `; break;
            case 'array':
                if (col.tipo === 'json') {
                    cast = '';
                    /* Especial: decodificar JSON string */
                    if (col.nullable) {
                        return `            ${camel}: isset($row['${col.nombre}']) ? (is_string($row['${col.nombre}']) ? json_decode($row['${col.nombre}'], true) : $row['${col.nombre}']) : null`;
                    }
                    return `            ${camel}: isset($row['${col.nombre}']) ? (is_string($row['${col.nombre}']) ? json_decode($row['${col.nombre}'], true) ?? [] : $row['${col.nombre}']) : []`;
                }
                cast = '(array) ';
                break;
            default: cast = ''; break;
        }

        if (col.nullable) {
            return `            ${camel}: isset($row['${col.nombre}']) ? ${cast}$row['${col.nombre}'] : null`;
        }

        const defaultVal = col.default !== undefined ? getDefaultPHP(col) : `throw new \\Glory\\Exception\\SchemaException("Columna '${col.nombre}' ausente en ${schema.tabla}", '${schema.tabla}', '${col.nombre}')`;

        return `            ${camel}: ${cast}($row['${col.nombre}'] ?? ${defaultVal})`;
    }).join(',\n');

    /* Mapeo camelCase → snake_case para aArrayDB() */
    const snakeMap = schema.columnas.map(col => {
        const camel = snakeToCamel(col.nombre);
        return `\n            '${col.nombre}' => $this->${camel}`;
    }).join(',');

    return `<?php

/* ARCHIVO AUTO-GENERADO por Glory Schema Generator — NO EDITAR */
/* Fuente: App/Config/Schema/${schema.nombreClase}Schema.php */

namespace App\\Config\\Schema\\_generated;

final class ${schema.nombreClase}DTO
{
    public function __construct(
${propiedades}
    ) {}

    /**
     * Construir desde array de base de datos.
     * Valida presencia de columnas requeridas.
     */
    public static function desdeRow(array $row): self
    {
        return new self(
${asignaciones}
        );
    }

    /**
     * Convertir a array asociativo camelCase (para serialización JSON).
     */
    public function aArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Convertir a array con claves snake_case (para queries SQL).
     */
    public function aArrayDB(): array
    {
        return [${snakeMap}];
    }
}
`;
}

/* Obtener valor default para PHP */
function getDefaultPHP(col) {
    if (col.default === undefined || col.default === null) return 'null';
    if (col.default === 'NOW()') return "date('Y-m-d H:i:s')";
    if (col.default === 'true' || col.default === true) return 'true';
    if (col.default === 'false' || col.default === false) return 'false';
    if (typeof col.default === 'number' || /^\d+(\.\d+)?$/.test(col.default)) return String(col.default);
    return `'${col.default}'`;
}

/* Generar schema.ts con todas las interfaces */
function generarTS(schemas) {
    const interfaces = schemas.map(schema => {
        /* Nombre de interface: I + PascalCase de tabla */
        const nombre = `I${schema.nombreClase}`;

        const propiedades = schema.columnas.map(col => {
            const tsType = tipoTS(col.tipo, col);
            const nullable = col.nullable ? ' | null' : '';
            const camel = snakeToCamel(col.nombre);
            return `  ${camel}: ${tsType}${nullable}`;
        }).join('\n');

        return `export interface ${nombre} {\n${propiedades}\n}`;
    }).join('\n\n');

    /* Generar constantes de columna para TS tambien */
    const colConsts = schemas.map(schema => {
        const constName = `${schema.nombreClase}Cols`;
        const entries = schema.columnas.map(col => {
            return `  ${toUpperSnake(col.nombre)}: '${col.nombre}'`;
        }).join(',\n');
        return `export const ${constName} = {\n  TABLA: '${schema.tabla}',\n${entries}\n} as const`;
    }).join('\n\n');

    /* Generar constantes de enums (check values) para TS */
    const enumConsts = schemas
        .map(schema => {
            const colsConCheck = schema.columnas.filter(c => c.check && c.check.length > 0);
            if (colsConCheck.length === 0) return null;
            const constName = `${schema.nombreClase}Enums`;
            const entries = colsConCheck.flatMap(col => {
                const prefijo = toUpperSnake(col.nombre);
                return col.check.map(valor => {
                    const sufijo = valor.toUpperCase().replace(/[^A-Z0-9]+/g, '_').replace(/^_|_$/g, '');
                    return `  ${prefijo}_${sufijo}: '${valor}'`;
                });
            }).join(',\n');
            return `export const ${constName} = {\n${entries}\n} as const`;
        })
        .filter(Boolean)
        .join('\n\n');

    return `/* ARCHIVO AUTO-GENERADO por Glory Schema Generator — NO EDITAR */
/* Regenerar con: npx glory schema:generate */

${interfaces}

/* Constantes de columna (mirror de PHP) */
${colConsts}

/* Constantes de valores enum/check (mirror de PHP) */
${enumConsts}
`;
}

/* Punto de entrada */
export function schemaGenerate() {
    const root = getProjectRoot();
    const schemaDir = resolve(root, 'App/Config/Schema');
    const generatedDir = resolve(schemaDir, '_generated');
    const tsDir = resolve(root, 'App/React/types/_generated');

    if (!existsSync(schemaDir)) {
        log('No existe App/Config/Schema/. Crea schemas primero con "npx glory create table".', 'error');
        return false;
    }

    mkdirSync(generatedDir, { recursive: true });
    mkdirSync(tsDir, { recursive: true });

    /* Leer todos los *Schema.php */
    const archivos = readdirSync(schemaDir).filter(f => f.endsWith('Schema.php'));

    if (archivos.length === 0) {
        log('No se encontraron archivos *Schema.php en App/Config/Schema/', 'warn');
        return false;
    }

    const schemas = [];
    let errores = 0;

    for (const archivo of archivos) {
        const ruta = resolve(schemaDir, archivo);
        const contenido = readFileSync(ruta, 'utf-8');
        const schema = parsearSchema(contenido, archivo);

        if (schema) {
            schemas.push(schema);

            /* Generar {Tabla}Cols.php */
            const colsPath = resolve(generatedDir, `${schema.nombreClase}Cols.php`);
            writeFileSync(colsPath, generarCols(schema), 'utf-8');
            log(`  Cols: _generated/${schema.nombreClase}Cols.php`, 'success');

            /* Generar {Tabla}DTO.php */
            const dtoPath = resolve(generatedDir, `${schema.nombreClase}DTO.php`);
            writeFileSync(dtoPath, generarDTO(schema), 'utf-8');
            log(`  DTO:  _generated/${schema.nombreClase}DTO.php`, 'success');

            /* Generar {Tabla}Enums.php — solo si hay columnas con check */
            const enumsContent = generarEnums(schema);
            if (enumsContent) {
                const enumsPath = resolve(generatedDir, `${schema.nombreClase}Enums.php`);
                writeFileSync(enumsPath, enumsContent, 'utf-8');
                log(`  Enum: _generated/${schema.nombreClase}Enums.php`, 'success');
            }
        } else {
            errores++;
        }
    }

    /* Generar schema.ts unificado */
    if (schemas.length > 0) {
        const tsPath = resolve(tsDir, 'schema.ts');
        writeFileSync(tsPath, generarTS(schemas), 'utf-8');
        log(`  TS:   App/React/types/_generated/schema.ts`, 'success');
    }

    /* Generar Repositories — preserva sección CUSTOM de repos existentes */
    if (schemas.length > 0) {
        const repoDir = resolve(root, 'App/Database/Repositories');
        mkdirSync(repoDir, { recursive: true });
        const reposGenerados = generarRepositorios(schemas, repoDir);
        log(`\n  Repositories: ${reposGenerados} generados`, 'success');
    }

    log(`\nSchema generation completada: ${schemas.length} tablas, ${errores} errores.`, schemas.length > 0 ? 'success' : 'error');
    return errores === 0;
}
