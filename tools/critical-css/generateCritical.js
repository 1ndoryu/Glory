#!/usr/bin/env node
/*
Genera CSS crítico real para una URL usando Penthouse + Puppeteer.
Imprime el CSS crítico minificado por stdout. Errores por stderr y exit code != 0.
*/

const fs = require('fs');
const path = require('path');
const penthouse = require('penthouse');
const csso = require('csso');

function parseArgs(argv) {
  const args = { url: null, cssDir: null, width: 1300, height: 900, timeout: 120000, renderWait: 800, skipLoadAfter: 25000 };
  for (let i = 2; i < argv.length; i++) {
    const a = argv[i];
    if (a === '--url') { args.url = argv[++i]; continue; }
    if (a === '--cssDir') { args.cssDir = argv[++i]; continue; }
    if (a === '--width') { args.width = parseInt(argv[++i], 10) || args.width; continue; }
    if (a === '--height') { args.height = parseInt(argv[++i], 10) || args.height; continue; }
    if (a === '--timeout') { args.timeout = parseInt(argv[++i], 10) || args.timeout; continue; }
    if (a === '--renderWait') { args.renderWait = parseInt(argv[++i], 10) || args.renderWait; continue; }
    if (a === '--skipLoadAfter') { args.skipLoadAfter = parseInt(argv[++i], 10) || args.skipLoadAfter; continue; }
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
    ['footer.css', 50],
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

(async () => {
  const { url, cssDir, width, height, timeout, renderWait, skipLoadAfter } = parseArgs(process.argv);
  if (!url) {
    console.error('Missing --url');
    process.exit(2);
  }
  // Asegurar que la URL tenga el flag noAjax=1 para congelar navegación/JS dinámico
  function ensureNoAjaxParam(u){
    try {
      const uo = new URL(u);
      if (!uo.searchParams.has('noAjax')) {
        uo.searchParams.set('noAjax','1');
      }
      return uo.toString();
    } catch(_e){
      // Fallback para URLs relativas
      if (u.indexOf('?') === -1) return u + '?noAjax=1';
      if (/([?&])noAjax=/.test(u)) return u;
      return u + '&noAjax=1';
    }
  }
  const targetUrl = ensureNoAjaxParam(url);
  const realCssDir = cssDir
    ? path.resolve(process.cwd(), cssDir)
    : path.resolve(__dirname, '../../..', 'App/Assets/css');
  if (!fs.existsSync(realCssDir)) {
    console.error('CSS directory not found:', realCssDir);
    process.exit(3);
  }
  const cssString = collectCssString(realCssDir);
  if (!cssString.trim()) {
    console.error('No CSS found in', realCssDir);
    process.exit(4);
  }

  try {
    const critical = await penthouse({
      url: targetUrl,
      cssString,
      width,
      height,
      timeout,
      forceInclude: [],
      keepLargerMediaQueries: true,
      renderWaitTime: renderWait,
      blockJSRequests: true,
      pageLoadSkipTimeout: skipLoadAfter,
    });
    const min = csso.minify(critical || '').css || '';
    process.stdout.write(min);
    process.exit(0);
  } catch (err) {
    console.error('Penthouse error:', err && err.message ? err.message : err);
    process.exit(1);
  }
})();


