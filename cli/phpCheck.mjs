#!/usr/bin/env node

/**
 * phpCheck — Verifica errores de sintaxis en TODO el PHP del proyecto.
 *
 * Ejecuta `php -l` recursivamente sobre App/, Glory/src/, Config/ y archivos
 * PHP raiz. Retorna true si todos los archivos estan limpios.
 *
 * Uso:
 *   npx glory php:check              Verificar todo
 *   npx glory php:check --verbose    Mostrar cada archivo verificado
 */

import { execSync } from 'node:child_process';
import { readdirSync, statSync } from 'node:fs';
import { join, relative, extname } from 'node:path';
import { log } from './utils.mjs';

/* Directorios PHP del proyecto a escanear */
const DIRECTORIOS = ['App', 'Glory/src', 'Glory/Config'];

/* Archivos PHP raiz */
const ARCHIVOS_RAIZ = [
    'functions.php',
    'header.php',
    'footer.php',
    'index.php',
    'TemplateReact.php',
    'style.css', /* No es PHP pero a veces WP lo parsea */
];

/**
 * Recolecta recursivamente todos los archivos .php de un directorio.
 * @param {string} dir Ruta absoluta del directorio
 * @param {string[]} resultado Array acumulador
 * @returns {string[]}
 */
function recolectarPhp(dir, resultado = []) {
    let entradas;
    try {
        entradas = readdirSync(dir);
    } catch {
        return resultado;
    }

    for (const entrada of entradas) {
        const ruta = join(dir, entrada);
        let stat;
        try {
            stat = statSync(ruta);
        } catch {
            continue;
        }

        if (stat.isDirectory()) {
            /* Saltar vendor, node_modules, _generated (solo tiene constantes auto-generadas) */
            if (entrada === 'vendor' || entrada === 'node_modules' || entrada === 'logs') continue;
            recolectarPhp(ruta, resultado);
        } else if (extname(entrada) === '.php') {
            resultado.push(ruta);
        }
    }

    return resultado;
}

/**
 * Ejecuta php -l sobre un archivo y retorna null si ok, o el mensaje de error.
 * @param {string} rutaAbsoluta
 * @returns {string|null}
 */
function verificarArchivo(rutaAbsoluta) {
    try {
        execSync(`php -l "${rutaAbsoluta}"`, {
            stdio: 'pipe',
            encoding: 'utf-8',
            timeout: 10000,
        });
        return null;
    } catch (err) {
        const salida = (err.stdout || '') + (err.stderr || '');
        return salida.trim() || 'Error desconocido de sintaxis';
    }
}

/**
 * Punto de entrada principal.
 * @param {boolean} verbose Mostrar cada archivo procesado
 * @returns {boolean} true si todo esta limpio
 */
export function phpCheck(verbose = false) {
    const raiz = process.cwd();

    log('Recolectando archivos PHP...', 'info');

    /* Recolectar de directorios */
    const archivos = [];
    for (const dir of DIRECTORIOS) {
        const rutaDir = join(raiz, dir);
        recolectarPhp(rutaDir, archivos);
    }

    /* Agregar archivos raiz que existan */
    for (const archivo of ARCHIVOS_RAIZ) {
        const ruta = join(raiz, archivo);
        try {
            if (statSync(ruta).isFile() && extname(archivo) === '.php') {
                archivos.push(ruta);
            }
        } catch {
            /* Archivo no existe, ignorar */
        }
    }

    log(`Verificando ${archivos.length} archivos PHP...`, 'info');

    const errores = [];
    let verificados = 0;

    for (const archivo of archivos) {
        const rutaRelativa = relative(raiz, archivo);

        if (verbose) {
            process.stdout.write(`  ${rutaRelativa} ... `);
        }

        const error = verificarArchivo(archivo);
        verificados++;

        if (error) {
            errores.push({ archivo: rutaRelativa, error });
            if (verbose) {
                console.log('ERROR');
            }
        } else if (verbose) {
            console.log('OK');
        }
    }

    /* Resumen */
    console.log('');
    if (errores.length === 0) {
        log(`${verificados} archivos verificados — sin errores de sintaxis`, 'success');
        return true;
    }

    log(`${errores.length} errores encontrados en ${verificados} archivos:`, 'error');
    console.log('');

    for (const { archivo, error } of errores) {
        console.log(`  ${archivo}`);
        /* Extraer solo la linea relevante del error */
        const lineas = error.split('\n').filter(l => l.includes('Parse error') || l.includes('Fatal error'));
        for (const linea of lineas) {
            console.log(`    ${linea.trim()}`);
        }
        if (lineas.length === 0) {
            console.log(`    ${error.split('\n')[0]}`);
        }
        console.log('');
    }

    return false;
}
