/**
 * Prerender Script - Genera HTML estatico para SSG
 *
 * Este script se ejecuta DESPUES de `vite build` para generar
 * archivos HTML estaticos de cada isla React.
 *
 * Estrategia:
 * - Usamos Vite SSR mode para compilar y ejecutar los componentes
 * - Esto permite importar archivos .tsx directamente
 *
 * Uso: npx vite-node scripts/prerender.ts
 */

import {renderToString} from 'react-dom/server';
import {createElement} from 'react';
import {existsSync, mkdirSync, writeFileSync, readdirSync} from 'fs';
import {resolve, join, dirname} from 'path';
import {fileURLToPath} from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

/*
 * Configuracion
 */
const CONFIG = {
    outputDir: resolve(__dirname, '../dist/ssg'),
    gloryIslandsDir: resolve(__dirname, '../src/islands'),
    appIslandsDir: resolve(__dirname, '../../../../App/React/islands')
};

/*
 * Props mock para el prerender
 * Estos valores se usan solo para generar el HTML estatico base.
 * PHP inyectara los valores reales en runtime.
 *
 * IMPORTANTE: Mantener estos props lo mas genericos posible
 * para que el HTML generado sea util para SEO sin datos especificos.
 */
const mockProps: Record<string, Record<string, unknown>> = {
    ExampleIsland: {
        title: 'React + WordPress',
        initialCount: 0,
        message: 'Cargando...'
    },
    HomeIsland: {
        siteName: 'Glory',
        stripeUrl: '#'
    }
};

/*
 * Islas que NO deben prerenderizarse
 * - SPA routers (contenido 100% dinamico)
 * - Islas que requieren datos de servidor obligatorios
 * - Páginas muy grandes de contenido estático (mejor renderizar en cliente)
 */
const skipIslands = new Set([
    'MainAppIsland', // SPA Router
    'BienvenidaIsland', // Requiere contexto de browser no estable en vite-node
    'DashboardIsland', // Requires extensive browser APIs (localStorage, window)
    'PoliticaPrivacidadIsland', // Contenido estático extenso (renderizar en cliente)
    'TerminosServicioIsland' // Contenido estático extenso (renderizar en cliente)
]);

function wait(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Descubre las islas disponibles para prerenderizar
 */
function discoverIslands(): Array<{name: string; path: string; source: string}> {
    const islands: Array<{name: string; path: string; source: string}> = [];

    // Buscar en Glory/assets/react/src/islands/
    if (existsSync(CONFIG.gloryIslandsDir)) {
        const files = readdirSync(CONFIG.gloryIslandsDir).filter(f => f.endsWith('.tsx'));
        for (const file of files) {
            const name = file.replace('.tsx', '');
            if (!skipIslands.has(name)) {
                islands.push({
                    name,
                    path: join(CONFIG.gloryIslandsDir, file),
                    source: 'glory'
                });
            }
        }
    }

    // Buscar en App/React/islands/ (si existe)
    if (existsSync(CONFIG.appIslandsDir)) {
        const files = readdirSync(CONFIG.appIslandsDir).filter(f => f.endsWith('.tsx'));
        for (const file of files) {
            const name = file.replace('.tsx', '');
            if (!skipIslands.has(name)) {
                islands.push({
                    name,
                    path: join(CONFIG.appIslandsDir, file),
                    source: 'app'
                });
            }
        }
    }

    return islands;
}

/**
 * Prerenderiza un componente a HTML estatico
 */
async function prerenderIsland(island: {name: string; path: string; source: string}): Promise<string | null> {
    try {
        // Importar el modulo dinamicamente
        const module = await import(island.path);

        // Buscar el componente exportado
        const Component = module[island.name] || module.default;

        if (!Component) {
            console.warn(`[SSG] No se encontro export "${island.name}" en ${island.path}`);
            return null;
        }

        // Obtener props mock (o objeto vacio)
        const props = mockProps[island.name] || {};

        // Renderizar a string HTML
        const html = renderToString(createElement(Component, props));

        return html;
    } catch (error) {
        const errorMessage = error instanceof Error ? error.message : String(error);

        if (errorMessage.includes('Request is outdated')) {
            try {
                await wait(250);
                const retryModule = await import(island.path);
                const RetryComponent = retryModule[island.name] || retryModule.default;

                if (!RetryComponent) {
                    console.warn(`[SSG] Reintento sin export valido para ${island.name}`);
                    return null;
                }

                const props = mockProps[island.name] || {};
                return renderToString(createElement(RetryComponent, props));
            } catch (retryError) {
                const retryMessage = retryError instanceof Error ? retryError.message : String(retryError);
                console.error(`[SSG] Reintento fallido en ${island.name}: ${retryMessage}`);
                return null;
            }
        }

        console.error(`[SSG] Error prerenderizando ${island.name}: ${errorMessage}`);
        return null;
    }
}

/**
 * Guarda el HTML en un archivo
 */
function saveHtml(name: string, html: string): void {
    const outputPath = join(CONFIG.outputDir, `${name}.html`);
    writeFileSync(outputPath, html, 'utf-8');
    console.log(`[SSG] Generado: ${name}.html (${html.length} bytes)`);
}

/**
 * Funcion principal
 */
async function main(): Promise<void> {
    console.log('\n========================================');
    console.log('  Glory SSG Pre-render');
    console.log('========================================\n');

    // Crear directorio de salida
    if (!existsSync(CONFIG.outputDir)) {
        mkdirSync(CONFIG.outputDir, {recursive: true});
        console.log(`[SSG] Creado: ${CONFIG.outputDir}\n`);
    }

    // Descubrir islas
    const islands = discoverIslands();
    console.log(`[SSG] Encontradas ${islands.length} islas\n`);

    if (islands.length === 0) {
        console.log('[SSG] No hay islas para prerenderizar.\n');
        return;
    }

    // Prerenderizar cada isla
    let successCount = 0;
    let skipCount = 0;

    for (const island of islands) {
        const html = await prerenderIsland(island);
        if (html && html.trim()) {
            saveHtml(island.name, html);
            successCount++;
        } else {
            skipCount++;
        }
    }

    console.log('\n----------------------------------------');
    console.log(`[SSG] Completado: ${successCount} generados, ${skipCount} omitidos`);
    console.log('----------------------------------------\n');
}

// Ejecutar
main()
    .then(() => {
        process.exit(0);
    })
    .catch(error => {
        console.error('[SSG] Error fatal:', error);
        process.exit(1);
    });
