(function () {
    // URLs para estilos de highlight
    var lightHref = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github.min.css';
    var darkHref = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github-dark.min.css';
    var themeLink = document.createElement('link');
    themeLink.rel = 'stylesheet';
    themeLink.id = 'hljs-theme';

    // Cache para evitar llamadas repetidas a getComputedStyle (causa reflow)
    var cachedDarkMode = null;
    var cacheTimestamp = 0;
    var CACHE_DURATION = 100; // ms - tiempo de cache para evitar reflows repetidos

    function isDarkMode() {
        var now = Date.now();
        // Usar cache si es reciente (evita reflows en llamadas consecutivas)
        if (cachedDarkMode !== null && now - cacheTimestamp < CACHE_DURATION) {
            return cachedDarkMode;
        }

        var docEl = document.documentElement;
        var bodyEl = document.body;

        // Verificaciones rapidas primero (sin reflow)
        var dataTheme = docEl ? docEl.getAttribute('data-theme') : null;
        if (dataTheme === 'dark') {
            cachedDarkMode = true;
            cacheTimestamp = now;
            return true;
        }
        if (dataTheme === 'light') {
            cachedDarkMode = false;
            cacheTimestamp = now;
            return false;
        }
        if (docEl && docEl.classList.contains('dark')) {
            cachedDarkMode = true;
            cacheTimestamp = now;
            return true;
        }
        if (bodyEl && (bodyEl.classList.contains('theme-dark') || bodyEl.classList.contains('dark'))) {
            cachedDarkMode = true;
            cacheTimestamp = now;
            return true;
        }
        if (docEl && (docEl.classList.contains('theme-light') || docEl.classList.contains('light'))) {
            cachedDarkMode = false;
            cacheTimestamp = now;
            return false;
        }
        if (bodyEl && bodyEl.classList.contains('light')) {
            cachedDarkMode = false;
            cacheTimestamp = now;
            return false;
        }

        // Solo si no hay indicadores claros, usar getComputedStyle (causa reflow)
        try {
            var bgVar = getComputedStyle(docEl).getPropertyValue('--bg').trim();
            if (bgVar) {
                var rgb = (function (c) {
                    if (!c) return null;
                    if (c.startsWith('#')) {
                        var hex = c.replace('#', '');
                        if (hex.length === 3)
                            hex = hex
                                .split('')
                                .map(function (h) {
                                    return h + h;
                                })
                                .join('');
                        var r = parseInt(hex.substring(0, 2), 16),
                            g = parseInt(hex.substring(2, 4), 16),
                            b = parseInt(hex.substring(4, 6), 16);
                        return [r, g, b];
                    }
                    var m = c.match(/rgb[a]?\(([^)]+)\)/i);
                    if (m) {
                        return m[1]
                            .split(',')
                            .slice(0, 3)
                            .map(function (n) {
                                return parseInt(n.trim(), 10);
                            });
                    }
                    return null;
                })(bgVar);
                if (rgb) {
                    var r = rgb[0] / 255,
                        g = rgb[1] / 255,
                        b = rgb[2] / 255;
                    var lum = 0.2126 * r + 0.7152 * g + 0.0722 * b;
                    cachedDarkMode = lum < 0.5;
                    cacheTimestamp = now;
                    return cachedDarkMode;
                }
            }
        } catch (e) {}

        cachedDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        cacheTimestamp = now;
        return cachedDarkMode;
    }

    // Invalidar cache cuando cambia el tema
    function invalidateCache() {
        cachedDarkMode = null;
    }

    function applyTheme() {
        var href = isDarkMode() ? darkHref : lightHref;
        if (themeLink.href !== href) themeLink.href = href;
        if (!themeLink.parentNode) document.head.appendChild(themeLink);
    }

    function highlightAll() {
        if (window.hljs && typeof window.hljs.highlightAll === 'function') {
            try {
                window.hljs.highlightAll();
            } catch (e) {}
        }
    }

    var observer = new MutationObserver(function () {
        invalidateCache();
        setTimeout(applyTheme, 0);
    });
    observer.observe(document.documentElement, {attributes: true, attributeFilter: ['data-theme', 'class']});
    if (document.body) observer.observe(document.body, {attributes: true, attributeFilter: ['class']});

    if (window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        if (mq.addEventListener)
            mq.addEventListener('change', function () {
                invalidateCache();
                applyTheme();
                highlightAll();
            });
        else if (mq.addListener)
            mq.addListener(function () {
                invalidateCache();
                applyTheme();
                highlightAll();
            });
    }

    ['gloryThemeChange', 'themechange', 'colorSchemeChange', 'ThemeToggleChanged'].forEach(function (evt) {
        document.addEventListener(evt, function () {
            invalidateCache();
            applyTheme();
            highlightAll();
        });
        window.addEventListener(evt, function () {
            invalidateCache();
            applyTheme();
            highlightAll();
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        applyTheme();
        highlightAll();
    });
})();
