#!/usr/bin/env node

/**
 * Glory CLI - Scaffolding y gestion de proyectos React
 *
 * Uso:
 *   npx glory create island <Nombre>       Crear isla React
 *   npx glory create page <nombre>         Crear isla + registro PHP
 *   npx glory create component <Nombre>    Crear componente
 *   npx glory create hook <nombre>         Crear hook
 *   npx glory create table <nombre>        Crear tabla + schema + migracion
 *   npx glory schema:generate              Generar constantes + DTOs + TS desde schemas
 *   npx glory schema:validate              Validar accesos a columnas en codigo PHP
 *   npx glory new <nombre> [--flags]       Crear proyecto nuevo
 *   npx glory setup [--flags]              Inicializar proyecto existente
 */

import { argv, exit } from 'node:process';
import { validateName, log } from './utils.mjs';
import { createIsland } from './createIsland.mjs';
import { createPage } from './createPage.mjs';
import { createComponent } from './createComponent.mjs';
import { createHook } from './createHook.mjs';
import { newProject, parsearOpciones } from './installer.mjs';
import { setup } from './setup.mjs';
import { createTable } from './createTable.mjs';
import { schemaGenerate } from './schemaGenerate.mjs';
import { schemaValidate } from './schemaValidate.mjs';

const args = argv.slice(2);

function mostrarAyuda() {
    console.log(`
  Glory CLI - Framework React para WordPress

  Scaffolding:
    npx glory create island <Nombre>       Crea isla (.tsx + .css + registro)
    npx glory create page <nombre>         Crea isla + registro en pages.php
    npx glory create component <Nombre>    Crea componente en App/React/components/
    npx glory create hook <nombre>         Crea hook en App/React/hooks/
    npx glory create table <nombre>        Crea schema + migracion SQL

  Schema:
    npx glory schema:generate              Genera Cols + DTOs (PHP) + types (TS)
    npx glory schema:validate              Valida columnas hardcodeadas en PHP

  Proyecto:
    npx glory new <nombre> [opciones]      Crea un proyecto nuevo desde cero
    npx glory setup [opciones]             Inicializa un proyecto ya clonado

  Opciones de proyecto:
    --minimal        Solo React + TS + ESLint (sin extras)
    --tailwind       Activa Tailwind CSS
    --shadcn         Activa shadcn/ui (implica --tailwind)
    --with-stripe    Activa integracion Stripe

  Ejemplos:
    npx glory create island MiSeccion
    npx glory create page contacto
    npx glory new mi-proyecto --tailwind
    npx glory setup --shadcn
`);
}

/* Sin argumentos o help */
if (args.length < 1 || args[0] === 'help' || args[0] === '--help' || args[0] === '-h') {
    mostrarAyuda();
    exit(0);
}

const comando = args[0];

try {
    if (comando === 'create') {
        /* Scaffolding: glory create <tipo> <nombre> */
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

        const errorNombre = validateName(nombre);
        if (errorNombre) {
            log(errorNombre, 'error');
            exit(1);
        }

        const creadores = { island: createIsland, page: createPage, component: createComponent, hook: createHook, table: createTable };
        const creador = creadores[tipo];

        if (!creador) {
            log(`Tipo desconocido: "${tipo}". Tipos validos: island, page, component, hook, table`, 'error');
            exit(1);
        }

        creador(nombre);
    } else if (comando === 'new') {
        /* Instalador: glory new <nombre> [--flags] */
        const nombre = args[1];

        if (!nombre || nombre.startsWith('--')) {
            log('Falta el nombre del proyecto. Usa: npx glory new <nombre>', 'error');
            exit(1);
        }

        const opciones = parsearOpciones(args.slice(2));
        newProject(nombre, opciones);
    } else if (comando === 'setup') {
        /* Setup: glory setup [--flags] */
        const opciones = parsearOpciones(args.slice(1));
        setup(opciones);
    } else if (comando === 'schema:generate') {
        /* Generar constantes, DTOs y tipos TS desde schemas */
        log('Generando archivos desde schemas...', 'info');
        const ok = schemaGenerate();
        exit(ok ? 0 : 1);
    } else if (comando === 'schema:validate') {
        /* Validar accesos a columnas en codigo PHP */
        log('Validando accesos a columnas...', 'info');
        const ok = schemaValidate();
        exit(ok ? 0 : 1);
    } else {
        log(`Comando desconocido: "${comando}". Usa "npx glory --help" para ver opciones.`, 'error');
        exit(1);
    }
} catch (error) {
    log(`Error: ${error.message}`, 'error');
    exit(1);
}
