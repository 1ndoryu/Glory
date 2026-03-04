/**
 * Glory CLI — Repository Generator
 *
 * Lee los schemas parseados y genera clases Repository por tabla.
 * Cada repositorio extiende BaseRepository y ofrece CRUD tipado
 * usando Cols + Enums del Schema System.
 *
 * Si el archivo ya existe, preserva la sección CUSTOM (métodos manuales)
 * y los use statements extra añadidos manualmente.
 *
 * Uso: se invoca desde schemaGenerate como paso adicional
 */

import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import { toPascalCase, log } from './utils.mjs';

/* Marca que delimita la sección auto-generada de la sección custom */
const MARCA_CUSTOM = '/* === METODOS CUSTOM (seguro para editar debajo de esta linea) === */';

/*
 * Extraer sección custom de un archivo Repository existente.
 * Retorna el bloque de código debajo de la marca CUSTOM, o null si no existe.
 * Preserva indentación original — NO hace trim() agresivo.
 */
function extraerSeccionCustom(rutaArchivo) {
    if (!existsSync(rutaArchivo)) return null;

    const contenido = readFileSync(rutaArchivo, 'utf-8');
    const idx = contenido.indexOf(MARCA_CUSTOM);
    if (idx === -1) return null;

    /* Capturar todo después de la marca hasta el cierre de clase */
    const despuesMarca = contenido.substring(idx + MARCA_CUSTOM.length);

    /* Buscar el último } que cierra la clase */
    const ultimoClose = despuesMarca.lastIndexOf('}');
    if (ultimoClose === -1) return null;

    /* Preservar indentación: solo trim de newlines al inicio/final, no espacios */
    const seccion = despuesMarca.substring(0, ultimoClose).replace(/^\n+/, '').replace(/\n+$/, '');
    return seccion || null;
}

/*
 * Extraer use statements custom de un archivo Repository existente.
 * Retorna array de líneas 'use ...' que NO son del propio schema (Cols/Enums/DTO).
 * Esto permite preservar imports manuales para JOINs con otras tablas.
 */
function extraerUsesCustom(rutaArchivo, nombreClase) {
    if (!existsSync(rutaArchivo)) return [];

    const contenido = readFileSync(rutaArchivo, 'utf-8');
    const lines = contenido.split('\n');

    /* Imports que el generador crea automáticamente — NO preservar */
    const prefijosAuto = [
        `use App\\Config\\Schema\\_generated\\${nombreClase}Cols;`,
        `use App\\Config\\Schema\\_generated\\${nombreClase}Enums;`,
        `use App\\Config\\Schema\\_generated\\${nombreClase}DTO;`,
    ];

    const usesCustom = [];
    for (const line of lines) {
        const trimmed = line.trim();
        if (!trimmed.startsWith('use ')) continue;
        /* Ignorar los imports auto-generados del propio schema */
        if (prefijosAuto.some(p => trimmed === p)) continue;
        /* Capturar cualquier otro use */
        usesCustom.push(trimmed);
    }

    return usesCustom;
}

/*
 * Extraer colId() personalizado de un archivo Repository existente.
 * Retorna la línea completa del return o null si usa el default (::ID).
 * Necesario para tablas con PK compuesta donde colId no es ::ID.
 */
function extraerColIdCustom(rutaArchivo) {
    if (!existsSync(rutaArchivo)) return null;

    const contenido = readFileSync(rutaArchivo, 'utf-8');

    /* Buscar el bloque colId() completo */
    const regex = /protected static function colId\(\): string\s*\{([^}]+)\}/;
    const match = contenido.match(regex);
    if (!match) return null;

    const body = match[1].trim();
    /* Si retorna ::ID, es el default genérico — no necesitamos preservar */
    if (body.match(/return\s+\w+Cols::ID;/)) return null;

    /* Retornar el bloque colId completo con comentarios */
    const startIdx = contenido.indexOf('protected static function colId()');
    if (startIdx === -1) return null;

    /* Buscar el comentario previo si existe */
    const antes = contenido.substring(0, startIdx);
    const lineas = antes.split('\n');
    let comentarioPrevio = '';
    for (let i = lineas.length - 1; i >= 0; i--) {
        const l = lineas[i].trim();
        if (l === '') continue;
        if (l.startsWith('/*') || l.startsWith('*') || l.startsWith('//')) {
            comentarioPrevio = lineas.slice(i).join('\n').trimEnd() + '\n';
            break;
        }
        break;
    }

    return { comentario: comentarioPrevio.trim(), body };
}

/*
 * Extraer métodos/constantes custom que estén ENTRE la clase y la marca CUSTOM.
 * Esto preserva código como constantes o métodos colocados en la zona auto-generada
 * por error. Se moverán a la sección custom para no perderse en futuros regenerados.
 */
function extraerCodigoEntreAutoYCustom(rutaArchivo, schema) {
    if (!existsSync(rutaArchivo)) return null;

    const contenido = readFileSync(rutaArchivo, 'utf-8');
    const marcaIdx = contenido.indexOf(MARCA_CUSTOM);
    if (marcaIdx === -1) return null;

    /* Encontrar el final del último método auto-generado */
    /* Los métodos auto-generados terminan con } seguido de línea vacía antes de la marca */
    const antesMarca = contenido.substring(0, marcaIdx);

    /* Nombres de métodos que el generador crea */
    const nombresAuto = ['buscarActivos', 'buscarPorUsuario', 'buscarPorCreador', 'buscarPorAutor', 'buscarRecientes', 'tabla()', 'colId()'];

    /* Buscar la última aparición de cualquier método auto-generado */
    let ultimoFinAuto = -1;
    for (const nombre of nombresAuto) {
        const idx = antesMarca.lastIndexOf(nombre);
        if (idx > ultimoFinAuto) {
            /* Buscar el cierre de ese método */
            const despues = antesMarca.substring(idx);
            const closeIdx = despues.indexOf('\n    }');
            if (closeIdx !== -1) {
                ultimoFinAuto = Math.max(ultimoFinAuto, idx + closeIdx + 6);
            }
        }
    }

    if (ultimoFinAuto === -1) return null;

    /* Capturar código entre el fin del último método auto y la marca */
    const entreMedio = antesMarca.substring(ultimoFinAuto, marcaIdx).trim();
    if (!entreMedio || entreMedio.length < 10) return null;

    return entreMedio;
}

/* Convertir snake_case a UPPER_SNAKE */
function toUpperSnake(str) {
    return str.toUpperCase();
}

/*
 * Determinar qué columnas tienen check/enum para generar imports de Enums.
 */
function tieneEnums(schema) {
    return schema.columnas.some(c => c.check && c.check.length > 0);
}

/*
 * Generar métodos adicionales basados en columnas del schema.
 * Por ejemplo, si tiene columna 'estado' con check, genera buscarActivos().
 */
function generarMetodosEspecificos(schema) {
    const metodos = [];
    const nombre = schema.nombreClase;
    const colsClass = `${nombre}Cols`;
    const enumsClass = `${nombre}Enums`;

    /* Si tiene columna 'estado' con check que incluye 'activo', generar buscarActivos */
    const colEstado = schema.columnas.find(c => c.nombre === 'estado' && c.check?.includes('activo'));
    if (colEstado) {
        metodos.push(`
    /*
     * Buscar registros con estado activo, paginados.
     */
    public static function buscarActivos(int $limit = 20, int $offset = 0): array
    {
        $tabla = ${colsClass}::TABLA;
        $colEstado = ${colsClass}::ESTADO;

        return static::consultar(
            "SELECT * FROM {$tabla} WHERE {$colEstado} = :estado ORDER BY " . ${colsClass}::ID . " DESC LIMIT :limit OFFSET :offset",
            [
                'estado' => ${enumsClass}::ESTADO_ACTIVO,
                'limit' => $limit,
                'offset' => $offset,
            ]
        );
    }`);
    }

    /* Si tiene columna 'usuario_id' o 'creador_id', generar buscarPorUsuario */
    const colUsuario = schema.columnas.find(c => ['usuario_id', 'creador_id', 'autor_id'].includes(c.nombre));
    if (colUsuario) {
        const colConst = toUpperSnake(colUsuario.nombre);
        const paramDesc = colUsuario.nombre === 'creador_id' ? 'creador' : 'usuario';

        metodos.push(`
    /*
     * Buscar registros del ${paramDesc} dado.
     */
    public static function buscarPor${toPascalCase(paramDesc)}(int $${paramDesc}Id, int $limit = 20, int $offset = 0): array
    {
        $tabla = ${colsClass}::TABLA;
        $col = ${colsClass}::${colConst};

        return static::consultar(
            "SELECT * FROM {$tabla} WHERE {$col} = :${paramDesc}Id ORDER BY " . ${colsClass}::ID . " DESC LIMIT :limit OFFSET :offset",
            ['${paramDesc}Id' => $${paramDesc}Id, 'limit' => $limit, 'offset' => $offset]
        );
    }`);
    }

    /* Si tiene columna 'created_at', generar buscarRecientes */
    const colCreated = schema.columnas.find(c => c.nombre === 'created_at');
    if (colCreated) {
        metodos.push(`
    /*
     * Buscar registros mas recientes.
     */
    public static function buscarRecientes(int $limit = 20): array
    {
        $tabla = ${colsClass}::TABLA;

        return static::consultar(
            "SELECT * FROM {$tabla} ORDER BY " . ${colsClass}::CREATED_AT . " DESC LIMIT :limit",
            ['limit' => $limit]
        );
    }`);
    }

    return metodos.join('\n');
}

/*
 * Generar el contenido PHP completo de un Repository.
 * Preserva: use statements custom, colId() custom, sección custom.
 */
function generarRepository(schema, seccionCustom, usesCustom, colIdCustom, codigoHuerfano) {
    const nombre = schema.nombreClase;
    const colsClass = `${nombre}Cols`;
    const enumsClass = `${nombre}Enums`;
    const dtoClass = `${nombre}DTO`;
    const conEnums = tieneEnums(schema);

    /* Imports auto-generados */
    let imports = `use App\\Config\\Schema\\_generated\\${colsClass};`;
    if (conEnums) {
        imports += `\nuse App\\Config\\Schema\\_generated\\${enumsClass};`;
    }
    imports += `\nuse App\\Config\\Schema\\_generated\\${dtoClass};`;

    /* Agregar use statements custom preservados */
    if (usesCustom.length > 0) {
        imports += '\n' + usesCustom.join('\n');
    }

    /* Métodos específicos basados en columnas */
    const metodosEspecificos = generarMetodosEspecificos(schema);

    /* colId() — usar custom si existe, si no default ::ID */
    let bloqueColId;
    if (colIdCustom) {
        /* Preservar comentario y body original */
        const comentario = colIdCustom.comentario ? `\n    ${colIdCustom.comentario}` : '';
        bloqueColId = `${comentario}
    protected static function colId(): string
    {
        ${colIdCustom.body}
    }`;
    } else {
        bloqueColId = `
    protected static function colId(): string
    {
        return ${colsClass}::ID;
    }`;
    }

    /* Sección custom — preservar indentación original */
    let bloqueCustom;
    if (codigoHuerfano && seccionCustom) {
        /* Mover código huérfano (estaba arriba de CUSTOM) al inicio de la sección custom */
        bloqueCustom = `\n    ${codigoHuerfano}\n\n    ${seccionCustom}\n`;
    } else if (seccionCustom) {
        bloqueCustom = `\n    ${seccionCustom}\n`;
    } else {
        bloqueCustom = `\n    /* Agregar metodos custom aqui (queries complejas, JOINs, CTEs, etc.) */\n`;
    }

    return `<?php

/**
 * ${nombre}Repository — Acceso a datos para tabla '${schema.tabla}'.
 *
 * SECCION AUTO-GENERADA: Los metodos base se regeneran con schema:generate.
 * SECCION CUSTOM: Todo debajo de la marca CUSTOM se preserva al regenerar.
 *
 * @package Kamples
 */

namespace App\\Kamples\\Database\\Repositories;

${imports}

class ${nombre}Repository extends BaseRepository
{
    protected static function tabla(): string
    {
        return ${colsClass}::TABLA;
    }
${bloqueColId}
${metodosEspecificos}

    ${MARCA_CUSTOM}
${bloqueCustom}}
`;
}

/*
 * Generar repositorios para todos los schemas proporcionados.
 * Preserva: secciones CUSTOM, use statements custom, colId() custom,
 * y código huérfano entre zona auto y marca CUSTOM.
 *
 * @param {Array} schemas - Schemas parseados por schemaGenerate
 * @param {string} repoDir - Directorio destino de los repos
 * @returns {number} Cantidad de repos generados
 */
export function generarRepositorios(schemas, repoDir) {
    let generados = 0;

    for (const schema of schemas) {
        const repoPath = resolve(repoDir, `${schema.nombreClase}Repository.php`);

        /* Preservar sección custom si existe */
        const seccionCustom = extraerSeccionCustom(repoPath);

        /* Preservar use statements custom (JOINs con otras tablas, helpers, etc.) */
        const usesCustom = extraerUsesCustom(repoPath, schema.nombreClase);

        /* Preservar colId() custom para tablas con PK compuesta */
        const colIdCustom = extraerColIdCustom(repoPath);

        /* Detectar código huérfano entre zona auto y marca CUSTOM */
        const codigoHuerfano = extraerCodigoEntreAutoYCustom(repoPath, schema);

        if (codigoHuerfano) {
            log(`  AVISO: ${schema.nombreClase}Repository tiene código entre zona auto y CUSTOM — será movido a sección custom`, 'warn');
        }

        const contenido = generarRepository(schema, seccionCustom, usesCustom, colIdCustom, codigoHuerfano);
        writeFileSync(repoPath, contenido, 'utf-8');

        const labels = [];
        if (seccionCustom) labels.push('custom');
        if (usesCustom.length) labels.push(`${usesCustom.length} uses`);
        if (colIdCustom) labels.push('colId custom');
        const label = labels.length ? `(preservado: ${labels.join(', ')})` : '(nuevo)';
        log(`  Repo: Repositories/${schema.nombreClase}Repository.php ${label}`, 'success');
        generados++;
    }

    return generados;
}
