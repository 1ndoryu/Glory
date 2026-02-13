#!/usr/bin/env node

/**
 * Glory CLI - Scaffolding de componentes React
 *
 * Uso:
 *   npx glory create island <Nombre>
 *   npx glory create page <nombre>
 *   npx glory create component <Nombre>
 *   npx glory create hook <nombre>
 */

import { argv, exit } from 'node:process';
import { validateName, log } from './utils.mjs';
import { createIsland } from './createIsland.mjs';
import { createPage } from './createPage.mjs';
import { createComponent } from './createComponent.mjs';
import { createHook } from './createHook.mjs';

const args = argv.slice(2);

function mostrarAyuda() {
    console.log(`
  Glory CLI - Scaffolding de componentes React

  Uso:
    npx glory create <tipo> <nombre>

  Tipos disponibles:
    island <Nombre>      Crea isla (.tsx + .css + registro en appIslands.tsx)
    page <nombre>        Crea isla + registro en pages.php
    component <Nombre>   Crea componente en App/React/components/
    hook <nombre>        Crea hook en App/React/hooks/

  Ejemplos:
    npx glory create island MiSeccion
    npx glory create page contacto
    npx glory create component BotonPrimario
    npx glory create hook useProductos

  Notas:
    - Los nombres se convierten automaticamente a PascalCase
    - Las islas reciben el sufijo "Island" automaticamente
    - Los hooks reciben el prefijo "use" si no lo tienen
`);
}

/* Validar argumentos minimos */
if (args.length < 1 || args[0] === 'help' || args[0] === '--help' || args[0] === '-h') {
    mostrarAyuda();
    exit(0);
}

const comando = args[0];

if (comando !== 'create') {
    log(`Comando desconocido: "${comando}". Usa "npx glory create <tipo> <nombre>"`, 'error');
    exit(1);
}

const tipo = args[1];
const nombre = args[2];

if (!tipo) {
    log('Falta el tipo. Usa: island, page, component, hook', 'error');
    exit(1);
}

if (!nombre) {
    log(`Falta el nombre. Usa: npx glory create ${tipo} <Nombre>`, 'error');
    exit(1);
}

/* Validar nombre */
const errorNombre = validateName(nombre);
if (errorNombre) {
    log(errorNombre, 'error');
    exit(1);
}

/* Despachar al creador correspondiente */
const creadores = {
    island: createIsland,
    page: createPage,
    component: createComponent,
    hook: createHook,
};

const creador = creadores[tipo];

if (!creador) {
    log(`Tipo desconocido: "${tipo}". Tipos validos: island, page, component, hook`, 'error');
    exit(1);
}

try {
    creador(nombre);
} catch (error) {
    log(`Error: ${error.message}`, 'error');
    exit(1);
}
