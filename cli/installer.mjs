import { execSync, execFileSync } from 'node:child_process';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { log, validateName } from './utils.mjs';
import { validarPrerequisitos } from './setup.mjs';

const REPO_URL = 'https://github.com/1ndoryu/glorytemplate.git';
const BRANCH = 'glory-react';

/*
 * Verifica que git este disponible.
 */
function verificarGit() {
    try {
        execSync('git --version', { stdio: 'pipe' });
        return true;
    } catch {
        log('git no encontrado. Instala git antes de continuar.', 'error');
        return false;
    }
}

/*
 * Parsea los flags del instalador desde argv.
 */
export function parsearOpciones(args) {
    return {
        minimal: args.includes('--minimal'),
        tailwind: args.includes('--tailwind'),
        shadcn: args.includes('--shadcn'),
        stripe: args.includes('--with-stripe'),
    };
}

/*
 * Comando "glory new <nombre>": crea un nuevo proyecto Glory desde cero.
 *
 * Flujo:
 *   1. Valida prerequisitos (git, node, php, composer)
 *   2. Clona el repo en <nombre>/
 *   3. Inicializa submodulo Glory
 *   4. Ejecuta setup (instalar deps, configurar flags)
 *   5. Muestra checklist
 */
export function newProject(nombre, opciones = {}) {
    /* Validar nombre para prevenir command injection */
    if (!validateName(nombre)) {
        log(`Nombre de proyecto invalido: "${nombre}". Usa solo letras, numeros, guiones y guiones bajos.`, 'error');
        return false;
    }

    const destino = resolve(process.cwd(), nombre);

    log(`Creando proyecto "${nombre}"...`, 'info');

    /* Verificar que el directorio no exista */
    if (existsSync(destino)) {
        log(`El directorio "${nombre}" ya existe. Elige otro nombre.`, 'error');
        return false;
    }

    /* Verificar git */
    if (!verificarGit()) return false;

    /* Verificar otros prerequisitos */
    if (!validarPrerequisitos()) {
        log('Faltan prerequisitos. Instala las herramientas faltantes y reintenta.', 'error');
        return false;
    }

    /* Clonar repositorio — usar execFileSync para evitar inyeccion de shell */
    log(`Clonando ${REPO_URL} (rama ${BRANCH})...`, 'info');
    try {
        execFileSync('git', ['clone', '--branch', BRANCH, '--single-branch', REPO_URL, nombre], {
            cwd: process.cwd(),
            stdio: 'inherit',
        });
    } catch {
        log('Error clonando el repositorio', 'error');
        return false;
    }

    /* Inicializar submodulos (Glory es un submodulo) */
    log('Inicializando submodulos...', 'info');
    try {
        execSync('git submodule update --init --recursive', {
            cwd: destino,
            stdio: 'inherit',
        });
    } catch {
        log('Error inicializando submodulos (puede que Glory no sea submodulo en esta config)', 'warn');
    }

    /* Configurar shadcn implica tailwind */
    if (opciones.shadcn) {
        opciones.tailwind = true;
    }

    /* Instalar dependencias PHP */
    log('Instalando dependencias PHP...', 'info');
    try {
        execSync('composer install --no-interaction', { cwd: destino, stdio: 'inherit' });
    } catch {
        log('Error instalando dependencias PHP', 'warn');
    }

    /* Instalar dependencias npm */
    log('Instalando dependencias npm...', 'info');
    try {
        execSync('npm run install:all', { cwd: destino, stdio: 'inherit' });
    } catch {
        log('Error instalando dependencias npm (intenta manualmente con npm run install:all)', 'warn');
    }

    /* Configurar feature flags en control.php */
    if (!opciones.minimal) {
        const archivoControl = resolve(destino, 'App/Config/control.php');
        if (existsSync(archivoControl)) {
            let contenido = readFileSync(archivoControl, 'utf-8');

            if (opciones.tailwind || opciones.shadcn) {
                contenido = contenido.replace(
                    "GloryFeatures::disable('tailwind');",
                    "GloryFeatures::enable('tailwind');",
                );
                log('Tailwind CSS activado', 'success');
            }

            if (opciones.shadcn) {
                contenido = contenido.replace(
                    "GloryFeatures::disable('shadcnUI');",
                    "GloryFeatures::enable('shadcnUI');",
                );
                log('shadcn/ui activado', 'success');
            }

            writeFileSync(archivoControl, contenido, 'utf-8');
        }
    }

    /* Crear primer isla de ejemplo si no es minimal */
    if (!opciones.minimal) {
        log('Creando isla de ejemplo...', 'info');
        try {
            execSync('node Glory/cli/glory.mjs create island Inicio', {
                cwd: destino,
                stdio: 'inherit',
            });
        } catch {
            log('No se pudo crear isla de ejemplo', 'warn');
        }
    }

    /* Validar tipos */
    log('Validando tipos TypeScript...', 'info');
    try {
        execSync('npm run type-check', { cwd: destino, stdio: 'pipe' });
        log('type-check pasado correctamente', 'success');
    } catch {
        log('type-check tiene advertencias (revisar manualmente)', 'warn');
    }

    /* Checklist final */
    console.log('');
    log(`=== Proyecto "${nombre}" creado ===`, 'success');
    console.log('');
    console.log('  Siguientes pasos:');
    console.log(`  1. cd ${nombre}`);
    console.log('  2. npm run dev          (servidor de desarrollo)');
    console.log('  3. npx glory create island MiPrimeraIsla');
    console.log('  4. Abrir WordPress admin para ver los cambios');
    console.log('');
    console.log('  Comandos utiles:');
    console.log('  npm run build           Compilar para produccion');
    console.log('  npm run lint            Verificar errores de codigo');
    console.log('  npm run type-check      Verificar tipos TypeScript');
    console.log('  npx glory create --help Ver opciones de scaffolding');
    console.log('');

    return true;
}
