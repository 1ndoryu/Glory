import { writeFileSync, readFileSync, mkdirSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { getProjectRoot, toPascalCase, toCamelCase, fileExists, log } from './utils.mjs';

/*
 * Genera el contenido del componente isla (.tsx)
 */
function generarPlantillaIsla(nombre) {
    const pascal = toPascalCase(nombre);
    const camel = toCamelCase(nombre);

    return `/**
 * Componente: ${pascal}Island
 * TO-DO: describir el proposito de esta isla
 */

import '../styles/${camel}.css';

interface ${pascal}IslandProps {
    titulo?: string;
}

export function ${pascal}Island({ titulo = '${pascal}' }: ${pascal}IslandProps): JSX.Element {
    return (
        <div id="seccion${pascal}" className="contenedor${pascal}">
            <h1>{titulo}</h1>
        </div>
    );
}

export default ${pascal}Island;
`;
}

/*
 * Genera el contenido del archivo de estilos (.css)
 */
function generarPlantillaCss(nombre) {
    const pascal = toPascalCase(nombre);

    return `/* Estilos para la isla ${pascal} */

.contenedor${pascal} {
    padding: var(--espaciado-medio, 2rem);
}
`;
}

/*
 * Registra la isla en appIslands.tsx (import + entry en el mapa)
 */
function registrarEnAppIslands(nombre, root) {
    const pascal = toPascalCase(nombre);
    const archivoApp = resolve(root, 'App/React/appIslands.tsx');

    if (!fileExists(archivoApp)) {
        log('appIslands.tsx no encontrado, omitiendo registro automatico', 'warn');
        return false;
    }

    let contenido = readFileSync(archivoApp, 'utf-8');
    const nombreIsla = `${pascal}Island`;
    const importPath = `./islands/${nombreIsla}`;

    /* Verificar si ya esta importado */
    if (contenido.includes(importPath)) {
        log(`${nombreIsla} ya esta registrada en appIslands.tsx`, 'warn');
        return false;
    }

    /* Agregar import despues del ultimo import existente */
    const lineas = contenido.split('\n');
    let ultimoImportIndex = -1;

    for (let i = 0; i < lineas.length; i++) {
        if (lineas[i].startsWith('import ')) {
            ultimoImportIndex = i;
        }
    }

    if (ultimoImportIndex === -1) {
        log('No se encontraron imports en appIslands.tsx', 'error');
        return false;
    }

    const lineaImport = `import {${nombreIsla}} from '${importPath}';`;
    lineas.splice(ultimoImportIndex + 1, 0, lineaImport);

    /* Agregar entrada al mapa appIslands */
    contenido = lineas.join('\n');
    const patron = /(\bappIslands\b[^{]*\{)/;
    const match = contenido.match(patron);

    if (!match) {
        log('No se encontro el objeto appIslands en appIslands.tsx', 'error');
        return false;
    }

    /* Buscar la ultima entrada antes del cierre del objeto */
    const entradaRegistro = `    ${nombreIsla}: ${nombreIsla} as React.ComponentType<Record<string, unknown>>,`;
    const cierreObjeto = contenido.lastIndexOf('};');

    if (cierreObjeto === -1) {
        log('No se encontro el cierre del objeto appIslands', 'error');
        return false;
    }

    /* Insertar antes del cierre y asegurar coma en entrada anterior */
    const antesDelCierre = contenido.slice(0, cierreObjeto).trimEnd();
    const ultimaLinea = antesDelCierre.split('\n').pop() || '';

    /* Si la linea anterior no termina en coma ni es apertura del objeto, agregar coma */
    let prefijo = antesDelCierre;
    if (ultimaLinea.trim() && !ultimaLinea.trimEnd().endsWith(',') && !ultimaLinea.trimEnd().endsWith('{')) {
        prefijo = antesDelCierre + ',';
    }

    contenido = prefijo + '\n' + entradaRegistro + '\n' + contenido.slice(cierreObjeto);

    writeFileSync(archivoApp, contenido, 'utf-8');
    return true;
}

/*
 * Comando principal: crea isla + css + registro
 */
export function createIsland(nombre) {
    const root = getProjectRoot();
    const pascal = toPascalCase(nombre);
    const camel = toCamelCase(nombre);
    const nombreIsla = `${pascal}Island`;

    const rutaIsla = resolve(root, `App/React/islands/${nombreIsla}.tsx`);
    const rutaCss = resolve(root, `App/React/styles/${camel}.css`);

    /* Verificar que no exista */
    if (fileExists(rutaIsla)) {
        log(`La isla ${nombreIsla} ya existe en ${rutaIsla}`, 'error');
        return false;
    }

    /* Crear directorios si no existen */
    mkdirSync(dirname(rutaIsla), { recursive: true });
    mkdirSync(dirname(rutaCss), { recursive: true });

    /* Escribir archivos */
    writeFileSync(rutaIsla, generarPlantillaIsla(nombre), 'utf-8');
    log(`Isla creada: App/React/islands/${nombreIsla}.tsx`, 'success');

    if (!fileExists(rutaCss)) {
        writeFileSync(rutaCss, generarPlantillaCss(nombre), 'utf-8');
        log(`Estilos creados: App/React/styles/${camel}.css`, 'success');
    }

    /* Registrar en appIslands.tsx */
    if (registrarEnAppIslands(nombre, root)) {
        log(`Registrada en App/React/appIslands.tsx`, 'success');
    }

    log(`Isla ${nombreIsla} lista para usar`, 'success');
    return true;
}
