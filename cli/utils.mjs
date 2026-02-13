import { existsSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));

/*
 * Raiz del proyecto (Glory/cli/ -> ../../)
 */
export function getProjectRoot() {
    return resolve(__dirname, '..', '..');
}

/*
 * "mi-isla" -> "MiIsla"
 */
export function toPascalCase(str) {
    return str
        .split(/[-_\s]+/)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join('');
}

/*
 * "mi-isla" -> "miIsla"
 */
export function toCamelCase(str) {
    const pascal = toPascalCase(str);
    return pascal.charAt(0).toLowerCase() + pascal.slice(1);
}

export function fileExists(filePath) {
    return existsSync(filePath);
}

/*
 * Valida que el nombre sea alfanumerico con guiones.
 */
export function validateName(name) {
    if (!name || name.length < 2) {
        return 'El nombre debe tener al menos 2 caracteres';
    }
    if (!/^[a-zA-Z][a-zA-Z0-9-]*$/.test(name)) {
        return 'El nombre debe empezar con letra y contener solo letras, numeros y guiones';
    }
    return null;
}

const colores = {
    reset: '\x1b[0m',
    cian: '\x1b[36m',
    verde: '\x1b[32m',
    rojo: '\x1b[31m',
    amarillo: '\x1b[33m',
    gris: '\x1b[90m',
};

export function log(mensaje, tipo = 'info') {
    const prefijos = {
        info: `${colores.cian}[Glory]${colores.reset}`,
        success: `${colores.verde}[Glory]${colores.reset}`,
        error: `${colores.rojo}[Glory]${colores.reset}`,
        warn: `${colores.amarillo}[Glory]${colores.reset}`,
    };
    console.log(`${prefijos[tipo] || prefijos.info} ${mensaje}`);
}
