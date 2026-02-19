/**
 * Glory CLI — Repository Generator
 *
 * Lee los schemas parseados y genera clases Repository por tabla.
 * Cada repositorio extiende BaseRepository y ofrece CRUD tipado
 * usando Cols + Enums del Schema System.
 *
 * Si el archivo ya existe, preserva la sección CUSTOM (métodos manuales).
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

    const seccion = despuesMarca.substring(0, ultimoClose).trim();
    return seccion || null;
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
            "SELECT * FROM {$tabla} WHERE {$colEstado} = :estado ORDER BY id DESC LIMIT :limit OFFSET :offset",
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
            "SELECT * FROM {$tabla} WHERE {$col} = :${paramDesc}Id ORDER BY id DESC LIMIT :limit OFFSET :offset",
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
            "SELECT * FROM {$tabla} ORDER BY created_at DESC LIMIT :limit",
            ['limit' => $limit]
        );
    }`);
    }

    return metodos.join('\n');
}

/*
 * Generar el contenido PHP completo de un Repository.
 */
function generarRepository(schema, seccionCustom) {
    const nombre = schema.nombreClase;
    const colsClass = `${nombre}Cols`;
    const enumsClass = `${nombre}Enums`;
    const dtoClass = `${nombre}DTO`;
    const conEnums = tieneEnums(schema);

    /* Imports */
    let imports = `use App\\Config\\Schema\\_generated\\${colsClass};`;
    if (conEnums) {
        imports += `\nuse App\\Config\\Schema\\_generated\\${enumsClass};`;
    }
    imports += `\nuse App\\Config\\Schema\\_generated\\${dtoClass};`;

    /* Métodos específicos basados en columnas */
    const metodosEspecificos = generarMetodosEspecificos(schema);

    /* Sección custom */
    const bloqueCustom = seccionCustom
        ? `\n${seccionCustom}\n`
        : `\n    /* Agregar metodos custom aqui (queries complejas, JOINs, CTEs, etc.) */\n`;

    return `<?php

/**
 * ${nombre}Repository — Acceso a datos para tabla '${schema.tabla}'.
 *
 * SECCION AUTO-GENERADA: Los metodos base se regeneran con schema:generate.
 * SECCION CUSTOM: Todo debajo de la marca CUSTOM se preserva al regenerar.
 *
 * @package App
 */

namespace Glory\\App\\Database\\Repositories;

${imports}

class ${nombre}Repository extends BaseRepository
{
    protected static function tabla(): string
    {
        return ${colsClass}::TABLA;
    }

    protected static function colId(): string
    {
        return ${colsClass}::ID;
    }
${metodosEspecificos}

    ${MARCA_CUSTOM}
${bloqueCustom}}
`;
}

/*
 * Generar repositorios para todos los schemas proporcionados.
 * Preserva secciones CUSTOM de repos existentes.
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

        const contenido = generarRepository(schema, seccionCustom);
        writeFileSync(repoPath, contenido, 'utf-8');

        const label = seccionCustom ? '(custom preservado)' : '(nuevo)';
        log(`  Repo: Repositories/${schema.nombreClase}Repository.php ${label}`, 'success');
        generados++;
    }

    return generados;
}
