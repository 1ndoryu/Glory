import { execSync } from 'node:child_process';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { log, getProjectRoot } from './utils.mjs';

/*
 * Verifica que un comando este disponible en el sistema.
 */
function verificarComando(cmd, descripcion) {
    try {
        const version = execSync(`${cmd} --version`, { stdio: 'pipe', encoding: 'utf-8' })
            .trim()
            .split('\n')[0];
        log(`${descripcion}: ${version}`, 'success');
        return true;
    } catch {
        log(`${descripcion} no encontrado. Instala ${cmd} antes de continuar.`, 'error');
        return false;
    }
}

/*
 * Valida que todos los prerequisitos esten instalados.
 */
export function validarPrerequisitos() {
    log('Verificando prerequisitos...', 'info');
    const resultados = [
        verificarComando('node', 'Node.js'),
        verificarComando('npm', 'npm'),
        verificarComando('php', 'PHP'),
        verificarComando('composer', 'Composer'),
    ];
    return resultados.every(Boolean);
}

/*
 * Ejecuta un comando y muestra output resumido.
 */
function ejecutar(cmd, cwd, descripcion) {
    log(`${descripcion}...`, 'info');
    try {
        execSync(cmd, { cwd, stdio: 'inherit' });
        return true;
    } catch (error) {
        log(`Error durante: ${descripcion}`, 'error');
        return false;
    }
}

/*
 * Configura feature flags en control.php segun las opciones del usuario.
 */
function configurarFlags(root, opciones) {
    const archivoControl = resolve(root, 'App/Config/control.php');
    if (!existsSync(archivoControl)) {
        log('control.php no encontrado, omitiendo configuracion de flags', 'warn');
        return;
    }

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

/*
 * Muestra el checklist final de post-instalacion.
 */
function mostrarChecklist(root, nombre) {
    console.log('');
    log('=== Proyecto listo ===', 'success');
    console.log('');
    console.log('  Siguientes pasos:');
    console.log(`  1. cd ${nombre || '.'}`);
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
}

/*
 * Comando setup: inicializa un proyecto ya clonado.
 * Instala dependencias, configura flags, valida tipos.
 */
export function setup(opciones = {}) {
    const root = getProjectRoot();

    /* Verificar prerequisitos */
    if (!validarPrerequisitos()) {
        log('Faltan prerequisitos. Instala las herramientas faltantes y reintenta.', 'error');
        return false;
    }

    /* Instalar dependencias PHP */
    if (existsSync(resolve(root, 'composer.json'))) {
        ejecutar('composer install --no-interaction', root, 'Instalando dependencias PHP');
    }

    /* Instalar dependencias npm */
    ejecutar('npm run install:all', root, 'Instalando dependencias npm');

    /* Configurar feature flags */
    configurarFlags(root, opciones);

    /* Validar tipos (no bloquea si falla) */
    log('Validando tipos TypeScript...', 'info');
    try {
        execSync('npm run type-check', { cwd: root, stdio: 'pipe' });
        log('type-check pasado correctamente', 'success');
    } catch {
        log('type-check tiene advertencias (revisar con npm run type-check)', 'warn');
    }

    mostrarChecklist(root, null);
    return true;
}
