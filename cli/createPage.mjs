import { readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { getProjectRoot, toPascalCase, fileExists, log } from './utils.mjs';
import { createIsland } from './createIsland.mjs';

/*
 * Genera la linea PHP de registro para pages.php
 */
function generarRegistroPhp(slug, nombre) {
    const pascal = toPascalCase(nombre);
    return `PageManager::reactPage('${slug}', '${pascal}Island', [\n    'titulo' => '${pascal}'\n]);`;
}

/*
 * Registra la pagina en App/Config/pages.php
 */
function registrarEnPages(slug, nombre, root) {
    const archivoPages = resolve(root, 'App/Config/pages.php');

    if (!fileExists(archivoPages)) {
        log('App/Config/pages.php no encontrado, omitiendo registro PHP', 'warn');
        return false;
    }

    let contenido = readFileSync(archivoPages, 'utf-8');
    const pascal = toPascalCase(nombre);
    const nombreIsla = `${pascal}Island`;

    /* Verificar que no exista */
    if (contenido.includes(`'${slug}'`)) {
        log(`La pagina '${slug}' ya esta registrada en pages.php`, 'warn');
        return false;
    }

    /* Buscar seccion de paginas React e insertar antes de la seccion de templates PHP */
    const marcador = 'PAGINAS CON TEMPLATES PHP';
    const posicion = contenido.indexOf(marcador);

    const registro = `\n// ${nombreIsla}\n${generarRegistroPhp(slug, nombre)}\n`;

    if (posicion !== -1) {
        /* Buscar el inicio del comentario de bloque antes del marcador */
        const bloqueInicio = contenido.lastIndexOf('/*', posicion);
        if (bloqueInicio !== -1) {
            contenido =
                contenido.slice(0, bloqueInicio) + registro + '\n' + contenido.slice(bloqueInicio);
        } else {
            contenido += registro;
        }
    } else {
        /* Si no hay seccion de templates PHP, agregar al final */
        contenido += registro;
    }

    writeFileSync(archivoPages, contenido, 'utf-8');
    return true;
}

/*
 * Comando principal: crea isla + registra pagina PHP
 */
export function createPage(nombre) {
    const root = getProjectRoot();
    const slug = nombre.toLowerCase().replace(/[^a-z0-9-]/g, '-');

    /* Crear la isla primero */
    const islaCreada = createIsland(nombre);

    /* Registrar en pages.php */
    if (registrarEnPages(slug, nombre, root)) {
        log(`Pagina registrada en App/Config/pages.php con slug '${slug}'`, 'success');
    }

    if (islaCreada) {
        log(`Pagina '${slug}' lista. Recuerda ejecutar 'npm run build' para ver los cambios`, 'success');
    }

    return true;
}
