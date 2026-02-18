/**
 * Glory CLI — Create Table (scaffolding)
 *
 * Genera un archivo Schema para una tabla nueva + migración SQL base.
 *
 * Uso: npx glory create table <nombre>
 *      npx glory create table usuarios_ext
 */

import { writeFileSync, mkdirSync, existsSync, readdirSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { getProjectRoot, toPascalCase, fileExists, log } from './utils.mjs';

/* Generar contenido del Schema PHP */
function generarPlantillaSchema(nombreTabla, nombreClase) {
    return `<?php

namespace App\\Config\\Schema;

use Glory\\Contracts\\TableSchema;

class ${nombreClase}Schema extends TableSchema
{
    public function tabla(): string
    {
        return '${nombreTabla}';
    }

    public function columnas(): array
    {
        return [
            'id'         => ['tipo' => 'int', 'pk' => true],
            'created_at' => ['tipo' => 'datetime', 'default' => 'NOW()'],
            'updated_at' => ['tipo' => 'datetime', 'default' => 'NOW()'],
            /* TO-DO: Definir columnas de la tabla ${nombreTabla} */
        ];
    }
}
`;
}

/* Generar migración SQL */
function generarMigracion(nombreTabla, version) {
    return `-- Migración: Crear tabla ${nombreTabla}
-- Ejecutar: psql -U postgres -d kamples -f ${version}_crear_${nombreTabla}.sql

CREATE TABLE IF NOT EXISTS ${nombreTabla} (
    id SERIAL PRIMARY KEY,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
    -- TO-DO: Agregar columnas
);

CREATE INDEX IF NOT EXISTS idx_${nombreTabla}_created ON ${nombreTabla}(created_at);
`;
}

/* Determinar la siguiente versión de migración */
function siguienteVersion(migrationsDir) {
    if (!existsSync(migrationsDir)) return 'v001';

    const archivos = readdirSync(migrationsDir).filter(f => f.endsWith('.sql'));
    let maxVersion = 0;

    for (const archivo of archivos) {
        const match = archivo.match(/^v(\d+)/);
        if (match) {
            maxVersion = Math.max(maxVersion, parseInt(match[1]));
        }
    }

    return `v${String(maxVersion + 1).padStart(3, '0')}`;
}

export function createTable(nombre) {
    const root = getProjectRoot();

    /* Normalizar nombre: snake_case */
    const nombreTabla = nombre.toLowerCase().replace(/-/g, '_');
    const nombreClase = toPascalCase(nombre.replace(/_/g, '-'));

    /* Rutas */
    const schemaDir = resolve(root, 'App/Config/Schema');
    const schemaPath = resolve(schemaDir, `${nombreClase}Schema.php`);
    const migrationsDir = resolve(root, 'App/Kamples/Database/migrations');

    /* Verificar que no exista */
    if (fileExists(schemaPath)) {
        log(`El schema ${nombreClase}Schema.php ya existe`, 'error');
        return false;
    }

    /* Crear directorios */
    mkdirSync(schemaDir, { recursive: true });
    mkdirSync(migrationsDir, { recursive: true });

    /* Generar Schema */
    writeFileSync(schemaPath, generarPlantillaSchema(nombreTabla, nombreClase), 'utf-8');
    log(`Schema creado: App/Config/Schema/${nombreClase}Schema.php`, 'success');

    /* Generar migración */
    const version = siguienteVersion(migrationsDir);
    const migrationName = `${version}_crear_${nombreTabla}.sql`;
    const migrationPath = resolve(migrationsDir, migrationName);
    writeFileSync(migrationPath, generarMigracion(nombreTabla, version), 'utf-8');
    log(`Migración creada: App/Kamples/Database/migrations/${migrationName}`, 'success');

    log(`\nSiguientes pasos:`, 'info');
    log(`  1. Edita ${nombreClase}Schema.php y define las columnas`, 'info');
    log(`  2. Edita la migración SQL con las columnas reales`, 'info');
    log(`  3. Ejecuta: npx glory schema:generate`, 'info');

    return true;
}
