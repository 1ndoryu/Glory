(function(){
    // URLs para estilos de highlight
    var lightHref = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github.min.css';
    var darkHref  = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github-dark.min.css';
    var themeLink = document.createElement('link');
    themeLink.rel = 'stylesheet';
    themeLink.id = 'hljs-theme';

    function isDarkMode() {
        var docEl = document.documentElement;
        var bodyEl = document.body;
        var dataTheme = docEl ? docEl.getAttribute('data-theme') : null;
        if (dataTheme === 'dark') return true;
        if (dataTheme === 'light') return false;
        if (docEl && docEl.classList.contains('dark')) return true;
        if (bodyEl && (bodyEl.classList.contains('theme-dark') || bodyEl.classList.contains('dark'))) return true;
        if (docEl && (docEl.classList.contains('theme-light') || docEl.classList.contains('light'))) return false;
        if (docEl && docEl.classList.contains('light')) return false;
        if (bodyEl && bodyEl.classList.contains('light')) return false;
        try {
            var bgVar = getComputedStyle(docEl).getPropertyValue('--bg').trim();
            if (bgVar) {
                var rgb = (function(c){
                    if (!c) return null;
                    if (c.startsWith('#')) {
                        var hex = c.replace('#','');
                        if (hex.length === 3) hex = hex.split('').map(function(h){return h+h;}).join('');
                        var r = parseInt(hex.substring(0,2),16), g = parseInt(hex.substring(2,4),16), b = parseInt(hex.substring(4,6),16);
                        return [r,g,b];
                    }
                    var m = c.match(/rgb[a]?\(([^)]+)\)/i);
                    if (m) {
                        return m[1].split(',').slice(0,3).map(function(n){return parseInt(n.trim(),10);});
                    }
                    return null;
                })(bgVar);
                if (rgb) {
                    var r = rgb[0]/255, g = rgb[1]/255, b = rgb[2]/255;
                    var lum = 0.2126*r + 0.7152*g + 0.0722*b;
                    return lum < 0.5;
                }
            }
        } catch(e) {}
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function applyTheme() {
        var href = isDarkMode() ? darkHref : lightHref;
        if (themeLink.href !== href) themeLink.href = href;
        if (!themeLink.parentNode) document.head.appendChild(themeLink);
    }

    function highlightAll() {
        if (window.hljs && typeof window.hljs.highlightAll === 'function') {
            try { window.hljs.highlightAll(); } catch(e) {}
        }
    }

    var observer = new MutationObserver(function(){ setTimeout(applyTheme,0); });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'class'] });
    if (document.body) observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

    if (window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        if (mq.addEventListener) mq.addEventListener('change', function(){ applyTheme(); highlightAll(); });
        else if (mq.addListener) mq.addListener(function(){ applyTheme(); highlightAll(); });
    }

    ['gloryThemeChange','themechange','colorSchemeChange','ThemeToggleChanged'].forEach(function(evt){
        document.addEventListener(evt, function(){ applyTheme(); highlightAll(); });
        window.addEventListener(evt, function(){ applyTheme(); highlightAll(); });
    });

    document.addEventListener('DOMContentLoaded', function(){
        applyTheme();
        highlightAll();
    });
})();
