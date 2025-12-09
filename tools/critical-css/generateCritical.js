#!/usr/bin/env node
/*
Genera CSS critico completo para una URL usando Penthouse + Puppeteer.
Incluye automaticamente: variables CSS (:root), fuentes, y selectores base criticos.
Imprime el CSS critico minificado por stdout. Errores por stderr y exit code != 0.
*/

const fs = require('fs');
const path = require('path');
const penthouse = require('penthouse');
const csso = require('csso');

function parseArgs(argv) {
    const args = {url: null, cssDir: null, width: 1300, height: 900, timeout: 120000, renderWait: 800, skipLoadAfter: 25000};
    for (let i = 2; i < argv.length; i++) {
        const a = argv[i];
        if (a === '--url') {
            args.url = argv[++i];
            continue;
        }
        if (a === '--cssDir') {
            args.cssDir = argv[++i];
            continue;
        }
        if (a === '--width') {
            args.width = parseInt(argv[++i], 10) || args.width;
            continue;
        }
        if (a === '--height') {
            args.height = parseInt(argv[++i], 10) || args.height;
            continue;
        }
        if (a === '--timeout') {
            args.timeout = parseInt(argv[++i], 10) || args.timeout;
            continue;
        }
        if (a === '--renderWait') {
            args.renderWait = parseInt(argv[++i], 10) || args.renderWait;
            continue;
        }
        if (a === '--skipLoadAfter') {
            args.skipLoadAfter = parseInt(argv[++i], 10) || args.skipLoadAfter;
            continue;
        }
    }
    return args;
}

function collectCssString(cssDir) {
    const exclude = new Set(['task.css', 'admin-elementor.css']);
    const order = new Map([
        ['init.css', 10],
        ['Pages.css', 20],
        ['home.css', 30],
        ['header.css', 40],
        ['footer.css', 50]
    ]);
    let files = fs.readdirSync(cssDir).filter(f => f.endsWith('.css') && !exclude.has(f));
    files.sort((a, b) => (order.get(a) || 999) - (order.get(b) || 999) || a.localeCompare(b));
    let css = '';
    for (const f of files) {
        const p = path.join(cssDir, f);
        const content = fs.readFileSync(p, 'utf8');
        css += `\n/* ${f} */\n` + content;
    }
    return css;
}

/**
 * Extrae CSS esencial que SIEMPRE debe incluirse en el CSS critico:
 * - @import (fuentes externas como Google Fonts)
 * - @font-face (fuentes locales)
 * - :root (variables CSS)
 * - html[data-theme] (temas claro/oscuro)
 * - Estilos base de html, body, main
 */
function extractEssentialCss(cssString) {
    const essentialParts = [];

    // 1. Extraer @import (fuentes Google, etc.)
    const importRegex = /@import\s+url\([^)]+\)[^;]*;/gi;
    const imports = cssString.match(importRegex) || [];
    essentialParts.push(...imports);

    // 2. Extraer @font-face completos
    const fontFaceRegex = /@font-face\s*\{[^}]+\}/gi;
    const fontFaces = cssString.match(fontFaceRegex) || [];
    essentialParts.push(...fontFaces);

    // 3. Extraer :root con todas las variables CSS
    // Regex mejorado para capturar :root { ... } con contenido anidado
    const rootRegex = /:root\s*\{([^{}]*(?:\{[^{}]*\}[^{}]*)*)\}/gi;
    let rootMatch;
    while ((rootMatch = rootRegex.exec(cssString)) !== null) {
        essentialParts.push(`:root{${rootMatch[1]}}`);
    }

    // 4. Extraer html[data-theme] (modo oscuro/claro)
    const themeRegex = /:where\(\s*html\[data-theme[^\]]*\]\s*\)\s*\{([^{}]*(?:\{[^{}]*\}[^{}]*)*)\}/gi;
    let themeMatch;
    while ((themeMatch = themeRegex.exec(cssString)) !== null) {
        essentialParts.push(`:where(html[data-theme='dark']){${themeMatch[1]}}`);
    }

    // 5. Extraer estilos base de html y body (simplificado)
    const htmlBodyRegex = /:where\(\s*(html|body)\s*\)\s*\{([^{}]+)\}/gi;
    let hbMatch;
    while ((hbMatch = htmlBodyRegex.exec(cssString)) !== null) {
        essentialParts.push(`:where(${hbMatch[1]}){${hbMatch[2]}}`);
    }

    return essentialParts.join('\n');
}

(async () => {
    const {url, cssDir, width, height, timeout, renderWait, skipLoadAfter} = parseArgs(process.argv);
    if (!url) {
        console.error('Missing --url');
        process.exit(2);
    }

    // Asegurar que la URL tenga el flag noAjax=1 para congelar navegacion/JS dinamico
    function ensureNoAjaxParam(u) {
        try {
            const uo = new URL(u);
            if (!uo.searchParams.has('noAjax')) {
                uo.searchParams.set('noAjax', '1');
            }
            return uo.toString();
        } catch (_e) {
            if (u.indexOf('?') === -1) return u + '?noAjax=1';
            if (/([?&])noAjax=/.test(u)) return u;
            return u + '&noAjax=1';
        }
    }

    const targetUrl = ensureNoAjaxParam(url);
    const realCssDir = cssDir ? path.resolve(process.cwd(), cssDir) : path.resolve(__dirname, '../../..', 'App/Assets/css');

    if (!fs.existsSync(realCssDir)) {
        console.error('CSS directory not found:', realCssDir);
        process.exit(3);
    }

    const cssString = collectCssString(realCssDir);
    if (!cssString.trim()) {
        console.error('No CSS found in', realCssDir);
        process.exit(4);
    }

    // Extraer CSS esencial (variables, fuentes, base) ANTES de Penthouse
    const essentialCss = extractEssentialCss(cssString);

    // Selectores criticos que Penthouse debe forzar a incluir
    // Estos son elementos que siempre estan visibles above-the-fold
    const forceIncludeSelectors = [
        // Estructura base
        /^html$/,
        /^body$/,
        /^main$/,
        /^\*$/,
        // Header y navegacion (siempre visible)
        /^\.header/,
        /^header/,
        /^\.nav/,
        /^nav/,
        /^\.menu/,
        /^\.logo/,
        // Contenedores principales
        /^\.landing-container/,
        /^\.hero/,
        /^\.site-/,
        // Variables y temas
        /^:root/,
        /^\[data-theme/,
        // Loader (si existe)
        /^\.cosmoPageLoader/,
        /^#cosmoPageLoader/,
        // Tipografia base
        /^h1/,
        /^h2/,
        /^p$/,
        /^a$/,
        // Utilidades criticas
        /^\.container/,
        /^\.wrapper/
    ];

    try {
        const critical = await penthouse({
            url: targetUrl,
            cssString,
            width,
            height,
            timeout,
            forceInclude: forceIncludeSelectors,
            keepLargerMediaQueries: true,
            renderWaitTime: renderWait,
            blockJSRequests: true,
            pageLoadSkipTimeout: skipLoadAfter
        });

        // Combinar: CSS esencial primero, luego lo que Penthouse detecto
        const combined = essentialCss + '\n' + (critical || '');

        // Minificar el resultado final
        const min = csso.minify(combined).css || '';
        process.stdout.write(min);
        process.exit(0);
    } catch (err) {
        console.error('Penthouse error:', err && err.message ? err.message : err);
        process.exit(1);
    }
})();
