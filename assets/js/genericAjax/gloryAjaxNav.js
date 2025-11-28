// Glory/js/ajax-page.js
(function () {
    'use strict';

    // Default configuration
    const defaults = {
        enabled: true, // Global switch for the AJAX functionality
        contentSelector: '#content', // Main content area to replace
        mainScrollSelector: '#main', // Element to scroll to top (fallback to window)
        loadingBarSelector: '#loadingBar', // Loading indicator element (optional)
        cacheEnabled: true, // Enable/disable caching mechanism
        prefetchOnHover: true, // Prefetch en hover/mousedown
        prefetchDelayMs: 50, // Pequeño retraso para evitar spam
        prefetchInViewport: false, // Quicklink-like: prefetch enlaces visibles
        prefetchMaxEntries: 24, // Límite de URLs prefetch cacheadas por sesión
        requestTimeoutMs: 10000, // Timeout para fetch de navegación
        optimizeImages: true, // Forzar loading=lazy en imágenes nuevas
        allowAsyncExternalScripts: true, // Permitir paralelizar scripts marcados async
        ignoreUrlPatterns: [
            // Array of regex patterns to skip AJAX for
            '/wp-admin',
            '/wp-login.php',
            '\\.(pdf|zip|rar|jpg|jpeg|png|gif|webp|mp3|mp4|xml|txt|docx|xlsx)$' // Common file extensions
        ],
        ignoreUrlParams: ['s', 'nocache'], // Query params that prevent caching
        noAjaxClass: 'noAjax', // Class name to manually disable AJAX on links/containers
        
        // Agnostic configuration: Keywords to identify critical inline scripts to extract/execute
        criticalScriptKeywords: [], // Array of strings to search for in inline scripts (e.g., ['appConfig', 'siteVars'])
        
        // Agnostic SEO sync (optional): If enabled, sync selected head tags from fetched doc
        syncHeadSeo: false,
        headSeoConfig: {
            canonicalSelector: 'link[rel="canonical"]',
            metaSelectors: ['meta[name="description"]'],
            jsonLdSelectors: [] // e.g. ['script[type="application/ld+json"][data-glory-seo="1"]']
        },

        // Inline script execution controls (agnóstico)
        skipInlineScriptTypes: ['application/ld+json'],
        skipInlineScriptSelectors: [], // e.g. ['script[data-skip-exec="1"]']
        
        // Agnostic hook: Custom function to determine if AJAX should be skipped
        shouldSkipAjax: null, // function(url: string, linkElement: HTMLAnchorElement): boolean
        
        // Agnostic hook: Custom function to check if initialization should abort
        shouldAbortInit: null // function(): boolean
    };

    // Merge defaults with user-provided config (prefer a specific object to avoid collisions)
    const runtimeConfig = (window.gloryNavConfig || window.dataGlobal || {});
    const config = {...defaults, ...runtimeConfig};
    const compiledIgnorePatterns = Array.isArray(config.ignoreUrlPatterns)
        ? config.ignoreUrlPatterns.map((p) => {
            try { return new RegExp(p, 'i'); } catch(_e) { return null; }
        }).filter(Boolean)
        : [];

    // Helper de logging: activo solo si `window.gloryDebug` es truthy
    const gloryLog = (...args) => { if (typeof window !== 'undefined' && window.gloryDebug) console.log(...args); };

    /**
     * isFusionBuilderActive ahora se define globalmente en utils/fusionBuilderDetect.js
     * y está disponible como window.isFusionBuilderActive()
     */
    // (Función local eliminada: usar window.isFusionBuilderActive())

    // Si detectamos que el editor de Fusion Builder está activo, abortamos inmediatamente.
    if (window.isFusionBuilderActive && window.isFusionBuilderActive()) {
        gloryLog('Glory AJAX Nav desactivado por Fusion Builder (detección temprana)');
        return;
    }

    // Exit immediately if disabled via config
    if (!config.enabled) {
        gloryLog('Glory AJAX Nav is disabled via configuration.');
        return;
    }

    const pageCache = {};
    const prefetchEnCurso = new Set();
    const headScriptsEjecutados = new Set();
    let currentFetchController = null;

    function hashCodigo(cadena) {
        try {
            let h = 5381; let i = cadena.length;
            while (i) { h = (h * 33) ^ cadena.charCodeAt(--i); }
            return (h >>> 0).toString(36);
        } catch(_e) { return String(cadena && cadena.length || 0); }
    }

    /**
     * Dispatches an event after content is loaded for other scripts to listen to.
     */
    function triggerPageReady() {
        const contentElement = document.querySelector(config.contentSelector);
        if (!contentElement) return; // Should not happen if config is valid

        const event = new CustomEvent('gloryRecarga', {
            bubbles: true,
            cancelable: true,
            detail: {contentElement: contentElement}
        });
        document.dispatchEvent(event);
        gloryLog('Event gloryRecarga dispatched.');
    }

    /**
     * Ejecuta los <script> embebidos dentro de un contenedor HTML ya parseado.
     * Necesario porque innerHTML no ejecuta scripts por defecto.
     * @param {Element} containerEl
     */
    function shouldSkipInlineScript(node) {
        try {
            if (!node) return true;
            const type = (node.getAttribute('type') || '').toLowerCase();
            if (type && Array.isArray(config.skipInlineScriptTypes) && config.skipInlineScriptTypes.includes(type)) {
                return true;
            }
            if (Array.isArray(config.skipInlineScriptSelectors) && config.skipInlineScriptSelectors.length) {
                for (let i = 0; i < config.skipInlineScriptSelectors.length; i++) {
                    const sel = config.skipInlineScriptSelectors[i];
                    if (sel && node.matches && node.matches(sel)) return true;
                }
            }
            return false;
        } catch(_e) {
            return false;
        }
    }

    function executeInlineScriptsFromElement(containerEl) {
        if (!containerEl) return;
        const scripts = Array.prototype.slice.call(containerEl.querySelectorAll('script'));
        scripts.forEach((oldScript) => {
            // Saltar scripts según tipo/selector (p.ej. application/ld+json)
            if (shouldSkipInlineScript(oldScript)) return;

            const newScript = document.createElement('script');
            for (let i = 0; i < oldScript.attributes.length; i++) {
                const attr = oldScript.attributes[i];
                newScript.setAttribute(attr.name, attr.value);
            }
            if (!oldScript.src) {
                newScript.textContent = oldScript.textContent || '';
            }
            // Si el contenedor está conectado al DOM, reemplazar in-place; si no, anexar al body
            if (containerEl.isConnected && oldScript.parentNode) {
                oldScript.parentNode.replaceChild(newScript, oldScript);
            } else {
                (document.body || document.documentElement).appendChild(newScript);
            }
        });
    }

    /**
     * Ejecuta scripts a partir de un string HTML (usado para contenido cacheado).
     * @param {string} htmlString
     */
    function executeInlineScriptsFromHTML(htmlString) {
        if (!htmlString) return;
        const tmp = document.createElement('div');
        tmp.innerHTML = htmlString;
        executeInlineScriptsFromElement(tmp);
    }

    /**
     * Extrae y opcionalmente ejecuta inline scripts críticos del documento
     * Busca scripts que contengan palabras clave configuradas en criticalScriptKeywords.
     * @param {Document} doc - Documento parseado
     * @param {boolean} executeNow - Si true, ejecuta los scripts inmediatamente
     * @returns {Array} - Array de códigos de scripts encontrados
     */
    function extractAndExecuteHeadScripts(doc, executeNow = false) {
        const scriptCodes = [];
        
        // Si no hay keywords configuradas, no extraer nada
        if (!config.criticalScriptKeywords || !config.criticalScriptKeywords.length) {
            return scriptCodes;
        }
        
        try {
            // Buscar en head Y body (algunos CMS ponen scripts inline en cualquier lugar)
            const containers = [
                doc.head || doc.getElementsByTagName('head')[0],
                doc.body || doc.getElementsByTagName('body')[0]
            ];
            
            containers.forEach(container => {
                if (!container) return;
                
                // Buscar scripts inline (sin src)
                const scripts = container.querySelectorAll('script:not([src])');
                
                scripts.forEach((oldScript) => {
                    const code = oldScript.textContent || '';
                    if (!code || code.length < 10) return; // Ignorar scripts vacíos o muy cortos
                    
                    // Detectar scripts críticos basándose en keywords configuradas
                    const isCriticalScript = config.criticalScriptKeywords.some(keyword => 
                        code.indexOf(keyword) !== -1
                    );
                    
                    if (isCriticalScript) {
                        // Evitar duplicados
                        if (!scriptCodes.includes(code)) {
                            scriptCodes.push(code);
                            gloryLog(`Found critical script in ${container.tagName}: ${code.substring(0, 80).replace(/\n/g, ' ')}...`);
                            
                            if (executeNow) {
                                const ns = document.createElement('script');
                                ns.textContent = code;
                                ns.setAttribute('data-glory-injected', 'config');
                                ns.setAttribute('data-glory-source', container.tagName.toLowerCase());
                                (document.head || document.body || document.documentElement).appendChild(ns);
                            }
                        }
                    }
                });
            });
            
            if (scriptCodes.length > 0) {
                if (executeNow) {
                    gloryLog(`✓ Executed ${scriptCodes.length} critical config scripts`);
                } else {
                    gloryLog(`✓ Extracted ${scriptCodes.length} critical config scripts for caching`);
                }
            } else {
                gloryLog(`⚠ No critical config scripts found in document`);
            }
        } catch(_e) {
            gloryLog('Error extracting config scripts:', _e);
        }
        
        return scriptCodes;
    }

    /**
     * Normaliza URL absoluta para comparación.
     */
    function toAbsoluteUrl(url) {
        try { return new URL(url, window.location.origin).href; } catch(_e){ return url; }
    }

    /**
     * Comprueba si un <script src> ya está presente en el documento.
     */
    function isScriptLoaded(src) {
        const abs = toAbsoluteUrl(src);
        const nodes = document.querySelectorAll('script[src]');
        for (let i=0;i<nodes.length;i++) {
            if (toAbsoluteUrl(nodes[i].getAttribute('src')) === abs) return true;
        }
        return false;
    }

    /**
     * Comprueba si un <link rel="stylesheet"> ya está presente en el documento.
     */
    function isStylesheetLoaded(href) {
        const abs = toAbsoluteUrl(href);
        const links = document.querySelectorAll('link[rel="stylesheet"][href]');
        for (let i=0;i<links.length;i++) {
            if (toAbsoluteUrl(links[i].getAttribute('href')) === abs) return true;
        }
        return false;
    }

    /**
     * Carga scripts/estilos externos del documento respuesta que aún no existan.
     * Devuelve una promesa que resuelve cuando los scripts añadidos terminan de cargar.
     */
    function loadExternalAssetsFromDoc(doc) {
        return new Promise((resolve) => {
            try {
                const head = document.head || document.getElementsByTagName('head')[0];
                const body = document.body || document.documentElement;

                // Estilos primero (no bloquean)
                const newLinks = [];
                doc.querySelectorAll('link[rel="stylesheet"][href]').forEach((lnk) => {
                    const href = lnk.getAttribute('href');
                    if (!href || isStylesheetLoaded(href)) return;
                    const nl = document.createElement('link');
                    nl.rel = 'stylesheet';
                    nl.href = toAbsoluteUrl(href);
                    head.appendChild(nl);
                    newLinks.push(nl);
                });

                // Scripts externos, preservar orden de aparición
                const scripts = Array.prototype.slice.call(doc.querySelectorAll('script[src]'));
                const toLoad = scripts.filter(s => s.getAttribute('src') && !isScriptLoaded(s.getAttribute('src')));
                if (!toLoad.length) {
                    resolve();
                    return;
                }
                // Carga secuencial para mantener orden y dependencias
                const loadSequential = (index) => {
                    if (index >= toLoad.length) { resolve(); return; }
                    const oldScript = toLoad[index];
                    const ns = document.createElement('script');
                    for (let i=0;i<oldScript.attributes.length;i++) {
                        const attr = oldScript.attributes[i];
                        if (attr.name === 'src') continue;
                        ns.setAttribute(attr.name, attr.value);
                    }
                    ns.src = toAbsoluteUrl(oldScript.getAttribute('src'));
                    ns.onload = () => loadSequential(index + 1);
                    ns.onerror = () => loadSequential(index + 1);
                    (body || document.documentElement).appendChild(ns);
                };
                loadSequential(0);
            } catch(_e) {
                resolve();
            }
        });
    }

    // Nota: La re-inicialización de librerías específicas (Avada u otras)
    // debe realizarse fuera de este archivo para mantenerlo agnóstico.
    // Escuchar 'gloryRecarga' en scripts puente dedicados.

    /**
     * Checks if a URL should be handled by standard browser navigation.
     * @param {string | undefined} url - The URL to check.
     * @param {HTMLAnchorElement} linkElement - The clicked link element.
     * @returns {boolean} - True to skip AJAX, false to use AJAX.
     */
    function skipAjax(url, linkElement) {
        // Agnostic hook: Allow external logic to override skip decision
        if (typeof config.shouldSkipAjax === 'function') {
            const customDecision = config.shouldSkipAjax(url, linkElement);
            if (customDecision === true || customDecision === false) {
                return customDecision;
            }
            // If hook returns undefined/null, continue with default logic
        }
        
        if (!url) return true;
        const currentOrigin = window.location.origin;
        const urlObject = new URL(url, currentOrigin); // Handles relative URLs correctly

        // 1. Basic checks: non-http(s), different origin, target=_blank, download attr
        if (!urlObject.protocol.startsWith('http') || urlObject.origin !== currentOrigin || linkElement.getAttribute('target') === '_blank' || linkElement.hasAttribute('download')) {
            return true;
        }

        // 2. Check against configured path patterns
        const pathAndQuery = urlObject.pathname + urlObject.search;
        if (compiledIgnorePatterns.some(re => re.test(pathAndQuery))) {
            return true;
        }

        // 3. Check for no-ajax class on link or ancestors
        if (linkElement.classList.contains(config.noAjaxClass) || linkElement.closest('.' + config.noAjaxClass)) {
            return true;
        }

        // 4. Check for specific modifier keys
        // Allow middle-click, ctrl/cmd-click, shift-click to open in new tab/window
        // Check event object in handleClick instead of link properties here.

        return false; // Use AJAX
    }

    /**
     * Decides if content for a URL should be cached based on config.
     * @param {string} url - URL to check.
     * @returns {boolean} - True to cache.
     */
    function shouldCache(url) {
        if (!config.cacheEnabled) return false;

        try {
            const urlObject = new URL(url, window.location.origin);
            const searchParams = urlObject.searchParams;
            // Don't cache if any configured param exists in the URL
            return !config.ignoreUrlParams.some(param => searchParams.has(param));
        } catch (e) {
            console.error("Error parsing URL for caching decision:", url, e);
            return false; // Don't cache if URL is invalid
        }
    }

    // Nueva función: asegura que la página o contenedor principal vuelva al principio
    function resetScrollPosition() {
        const mainScrollElement = config.mainScrollSelector ? document.querySelector(config.mainScrollSelector) : null;
        if (mainScrollElement) {
            mainScrollElement.scrollTop = 0;
        }
        // Respaldo para otros escenarios / navegadores
        window.scrollTo({ top: 0, behavior: 'auto' });
        document.documentElement.scrollTop = 0; // Safari/IE
        document.body.scrollTop = 0;
    }

    /**
     * Sincroniza etiquetas SEO en <head> de forma agnóstica según selectores configurados.
     * No asume estructura específica del proyecto.
     * @param {Document} doc - Documento parseado de la respuesta
     */
    function syncHeadSeo(doc) {
        try {
            if (!config.syncHeadSeo || !doc) return;
            const head = document.head || document.getElementsByTagName('head')[0];
            const cfg = config.headSeoConfig || {};

            // Canonical
            if (cfg.canonicalSelector) {
                const newCanonical = doc.querySelector(cfg.canonicalSelector);
                if (newCanonical) {
                    let currentCanonical = head.querySelector('link[rel="canonical"]');
                    if (!currentCanonical) {
                        currentCanonical = document.createElement('link');
                        currentCanonical.setAttribute('rel', 'canonical');
                        head.appendChild(currentCanonical);
                    }
                    const href = newCanonical.getAttribute('href') || '';
                    if (href) currentCanonical.setAttribute('href', href);
                }
            }

            // Meta tags (e.g., description)
            if (Array.isArray(cfg.metaSelectors)) {
                cfg.metaSelectors.forEach((sel) => {
                    if (!sel) return;
                    const newMeta = doc.querySelector(sel);
                    if (!newMeta) return;
                    // Intentar localizar meta equivalente por name o el selector exacto
                    const name = newMeta.getAttribute('name');
                    let currentMeta = null;
                    if (name) {
                        currentMeta = head.querySelector(`meta[name="${name}"]`);
                    }
                    if (!currentMeta) {
                        currentMeta = head.querySelector(sel);
                    }
                    if (!currentMeta) {
                        currentMeta = document.createElement('meta');
                        if (name) currentMeta.setAttribute('name', name);
                        head.appendChild(currentMeta);
                    }
                    // Copiar atributo content si existe
                    const content = newMeta.getAttribute('content');
                    if (content !== null) currentMeta.setAttribute('content', content);
                });
            }

            // JSON-LD (opcional, controlado por selectores)
            if (Array.isArray(cfg.jsonLdSelectors)) {
                cfg.jsonLdSelectors.forEach((sel) => {
                    if (!sel) return;
                    const newJson = doc.querySelector(sel);
                    if (!newJson) return;
                    // Eliminar existentes que coincidan con el selector para evitar duplicados
                    head.querySelectorAll(sel).forEach((n) => n.parentNode && n.parentNode.removeChild(n));
                    const clone = document.createElement('script');
                    clone.type = 'application/ld+json';
                    // Copiar atributos no críticos si hubiera
                    for (let i = 0; i < newJson.attributes.length; i++) {
                        const attr = newJson.attributes[i];
                        if (attr.name === 'type') continue;
                        clone.setAttribute(attr.name, attr.value);
                    }
                    clone.textContent = newJson.textContent || '';
                    head.appendChild(clone);
                });
            }
        } catch(_e) {
            // Silencioso por ser agnóstico
        }
    }

    /**
     * Loads page content via fetch, parses, replaces content, and triggers re-initialization.
     * @param {string} url - The URL to load.
     * @param {boolean} pushState - Whether to push the URL to browser history.
     */
    function load(url, pushState = true) {
        // Create a dummy link to reuse skipAjax logic easily
        // Note: We don't need full skipAjax here as handleClick already does it.
        // This is more of a sanity check or for direct calls to load().

        const contentElement = document.querySelector(config.contentSelector);
        const loadingBar = config.loadingBarSelector ? document.querySelector(config.loadingBarSelector) : null;
        const mainScrollElement = config.mainScrollSelector ? document.querySelector(config.mainScrollSelector) : null;

        if (!contentElement) {
            console.error(`AJAX Nav Error: Content element "${config.contentSelector}" not found.`);
            window.location.href = url; // Fallback
            return;
        }

        // Use cache if available and caching is enabled/allowed for this URL
        if (pageCache[url] && shouldCache(url)) {
            gloryLog(`Loading from cache: ${url}`);
            if (pageCache[url].nodeChildren && pageCache[url].nodeChildren.length) {
                // Reutilizar nodos previos (imágenes ya cargadas no se vuelven a pedir)
                try {
                    contentElement.replaceChildren.apply(contentElement, pageCache[url].nodeChildren);
                } catch(_e) {
                    // Fallback si apply falla en algunos navegadores antiguos
                    contentElement.replaceChildren(...pageCache[url].nodeChildren);
                }
            } else {
                contentElement.innerHTML = pageCache[url].content || pageCache[url];
            }
            if (pushState) {
                history.pushState({url: url}, '', url);
            }
            // Restablecer posición de scroll
            resetScrollPosition();
            
            // Ejecutar scripts de configuración del head si están cacheados
            if (pageCache[url].headScripts) {
                gloryLog('Executing cached head scripts...');
                pageCache[url].headScripts.forEach((scriptCode) => {
                    const k = hashCodigo(scriptCode);
                    if (headScriptsEjecutados.has(k)) return;
                    try {
                        const ns = document.createElement('script');
                        ns.textContent = scriptCode;
                        ns.setAttribute('data-glory-cached', 'head-config');
                        (document.head || document.body).appendChild(ns);
                        headScriptsEjecutados.add(k);
                    } catch(_e) {}
                });
            }
            
            // Ejecutar scripts embebidos (los <script> del contenido cacheado)
            // Solo ejecutar scripts embebidos si no se reutilizaron nodos (evitar dobles ejecuciones)
            if (!(pageCache[url].nodeChildren && pageCache[url].nodeChildren.length)) {
                const cachedContent = pageCache[url].content || pageCache[url];
                executeInlineScriptsFromHTML(cachedContent);
            }
            
            // Pequeño delay antes de disparar el evento
            setTimeout(() => {
                gloryLog('Triggering gloryRecarga from cache...');
                // Disparar evento para que otros scripts (agnóstico)
                triggerPageReady();
            }, 50);
            return;
        }

        // Show loading indicators
        if (loadingBar) {
            loadingBar.style.transition = 'width 0.3s ease, opacity 0.3s ease'; // Ensure transition is set
            loadingBar.style.width = '0%'; // Reset width before showing
            loadingBar.style.opacity = '1';
            requestAnimationFrame(() => {
                // Allow repaint before starting animation
                loadingBar.style.width = '70%';
            });
        }
        contentElement.style.transition = 'opacity 0.3s ease';
        contentElement.style.opacity = '0.5';

        // Cancelar navegación previa en curso, si la hay
        try { if (currentFetchController) { currentFetchController.abort(); } } catch(_e) {}
        currentFetchController = (typeof AbortController !== 'undefined') ? new AbortController() : null;
        const signal = currentFetchController ? currentFetchController.signal : undefined;
        let timeoutId = null;
        if (signal && typeof config.requestTimeoutMs === 'number' && config.requestTimeoutMs > 0) {
            timeoutId = setTimeout(() => { try { currentFetchController.abort(); } catch(_e) {} }, config.requestTimeoutMs);
        }

        fetch(url, { signal })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('text/html')) {
                    throw new TypeError(`Expected HTML but received ${contentType}. Aborting AJAX.`);
                }
                return response.text();
            })
            .then(html => {
                if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.querySelector(config.contentSelector);
                const newTitle = doc.querySelector('title');

                if (!newContent) {
                    console.error(`AJAX Nav Error: Selector "${config.contentSelector}" not found in fetched HTML from ${url}. Loading full page.`);
                    window.location.href = url; // Fallback to full page load
                    return;
                }

                // Capturar nodos actuales antes de reemplazar para guardarlos en caché del URL de origen
                const fromUrl = window.location.href;
                const previousNodes = Array.prototype.slice.call(contentElement.childNodes);

                // Replace content & title usando adopción de nodos (evita reparsear strings)
                const frag = document.createDocumentFragment();
                while (newContent.firstChild) { frag.appendChild(newContent.firstChild); }
                contentElement.replaceChildren(frag);
                if (newTitle) document.title = newTitle.textContent;

                // Opcional: sincronizar <head> SEO de la respuesta (agnóstico por config)
                syncHeadSeo(doc);

                // Extraer scripts del head ANTES de procesarlos (para cache y ejecución)
                const headScripts = extractAndExecuteHeadScripts(doc, false);
                
                // Cache if applicable - guardar tanto contenido como scripts del head
                if (shouldCache(url)) {
                    pageCache[url] = {
                        content: contentElement.innerHTML,
                        headScripts: headScripts,
                        // Guardamos referencia de nodos actuales (ya insertados) para reuso posterior
                        nodeChildren: Array.prototype.slice.call(contentElement.childNodes)
                    };
                    gloryLog(`Cached: ${url} (with ${headScripts.length} head scripts)`);
                }

                // Guardar nodos de la página anterior para reuso si se vuelve a ella
                try {
                    if (shouldCache(fromUrl)) {
                        pageCache[fromUrl] = pageCache[fromUrl] || {};
                        pageCache[fromUrl].nodeChildren = previousNodes;
                        // Asegurar tener también la versión string como respaldo
                        if (!pageCache[fromUrl].content) {
                            pageCache[fromUrl].content = previousNodes.map(n => n.outerHTML || (n.nodeType === 3 ? n.textContent : '')).join('');
                        }
                    }
                } catch(_e) {}

                // Update History
                if (pushState) {
                    history.pushState({url: url}, '', url);
                }

                // Restablecer posición de scroll tras insertar el nuevo contenido
                resetScrollPosition();

                // Hide loading indicators
                contentElement.style.opacity = '1';
                if (loadingBar) {
                    loadingBar.style.width = '100%';
                    setTimeout(() => {
                        loadingBar.style.opacity = '0';
                        // Reset width after fade out for next use
                        setTimeout(() => {
                            if (loadingBar) loadingBar.style.width = '0%';
                        }, 300); // Wait for opacity transition
                    }, 150); // Delay before fade out
                }

                // PASO 1: Ejecutar scripts inline de configuración del <head>
                // (p.ej. formCreatorConfig, fusionAppConfig) ANTES de cargar scripts externos
                //gloryLog('Step 1: Executing inline head config scripts...');
                if (headScripts && headScripts.length > 0) {
                    headScripts.forEach((scriptCode) => {
                        const k = hashCodigo(scriptCode);
                        if (headScriptsEjecutados.has(k)) return;
                        try {
                            const ns = document.createElement('script');
                            ns.textContent = scriptCode;
                            ns.setAttribute('data-glory-executed', 'head-config');
                            (document.head || document.body).appendChild(ns);
                            headScriptsEjecutados.add(k);
                        } catch(_e) {}
                    });
                }

                // PASO 2: Cargar assets externos adicionales (scripts/estilos) y esperar a que terminen
                loadExternalAssetsFromDoc(doc).then(() => {
                    //gloryLog('Step 2: External assets loaded');
                    
                    // PASO 3: Ejecutar scripts embebidos presentes en el nuevo contenido
                    executeInlineScriptsFromElement(contentElement);

                    // Optimizar imágenes: forzar lazy si procede
                    try {
                        if (config.optimizeImages) {
                            contentElement.querySelectorAll('img:not([loading]), img[loading="auto"]').forEach((img) => {
                                if (!img.hasAttribute('data-eager')) { img.setAttribute('loading', 'lazy'); }
                            });
                        }
                    } catch(_e) {}
                    
                    // PASO 4: Pequeño delay para asegurar que todo esté inicializado
                    // antes de disparar el evento gloryRecarga
                    setTimeout(() => {
                        //gloryLog('Step 4: Triggering gloryRecarga event...');
                        // Trigger re-initialization for dynamically loaded content (agnóstico)
                        triggerPageReady();
                    }, 50);
                });
            })
            .catch(error => {
                if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
                // Si la petición fue abortada por nueva navegación, no hacer fallback completo
                if (error && (error.name === 'AbortError' || /abort/i.test(String(error)))) {
                    gloryLog('AJAX Load aborted');
                    return;
                }
                console.error('AJAX Load Error:', error);
                if (loadingBar) {
                    // Hide loading bar on error
                    loadingBar.style.opacity = '0';
                    setTimeout(() => {
                        if (loadingBar) loadingBar.style.width = '0%';
                    }, 300);
                }
                contentElement.style.opacity = '1'; // Restore content visibility
                window.location.href = url; // Fallback to normal navigation on any error
            });
    }

    // Prefetch controlado (agnóstico)
    function prefetch(url) {
        try {
            if (!url || !config.prefetchOnHover) return;
            if (pageCache[url] && shouldCache(url)) return;
            if (prefetchEnCurso.has(url)) return;

            // Respetar reglas de skipAjax
            const pl = document.createElement('a'); pl.href = url;
            if (skipAjax(url, pl)) return;

            prefetchEnCurso.add(url);
            setTimeout(() => {
                fetch(url)
                    .then(r => {
                        if (!r.ok) throw new Error('prefetch ' + r.status);
                        const ct = r.headers.get('content-type');
                        if (!ct || !ct.includes('text/html')) throw new Error('nohtml');
                        return r.text();
                    })
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const nc = doc.querySelector(config.contentSelector);
                        if (!nc) return;
                        const hs = extractAndExecuteHeadScripts(doc, false);
                        if (shouldCache(url)) {
                            pageCache[url] = { content: nc.innerHTML, headScripts: hs };
                            gloryLog('Prefetched and cached:', url);
                        }
                    })
                    .catch(() => {})
                    .finally(() => { prefetchEnCurso.delete(url); });
            }, Math.max(0, Number(config.prefetchDelayMs) || 0));
        } catch(_e) {}
    }

    /**
     * Handles click events on potential AJAX links.
     * @param {Event} e - The click event.
     */
    function handleClick(e) {
        if (e.defaultPrevented) return;

        // Ignore clicks if modifier keys are pressed (for opening in new tab/window)
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }

        // Find the closest ancestor anchor tag
        const linkElement = e.target.closest('a');

        // Basic checks first
        if (!linkElement || !linkElement.href) {
            return;
        }

        // --- Manejo de anclas internas (hash links) con scroll suave ---
        // Detecta enlaces cuyo href contiene únicamente un hash o bien un hash en la misma ruta actual.
        // En esos casos se evita la navegación (y el sistema AJAX) y simplemente se hace scroll
        // hacia el elemento destino si existe.
        if (linkElement.hash && (linkElement.getAttribute('href').startsWith('#') || linkElement.pathname === window.location.pathname)) {
            e.preventDefault();
            e.stopImmediatePropagation();

            const targetId = linkElement.hash.substring(1);
            if (targetId) {
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                    // Actualizamos la URL con el hash sin crear una nueva entrada de historial.
                    history.replaceState(history.state, '', linkElement.hash);
                } else {
                    console.warn(`Destino de ancla no encontrado: #${targetId}`);
                }
            }
            return; // Terminar aquí, no se procesa AJAX.
        }

        // Use skipAjax for detailed checks (URL pattern, class, origin etc.)
        if (skipAjax(linkElement.href, linkElement)) {
            gloryLog(`AJAX skipped for: ${linkElement.href}`);
            return; // Let the browser handle it
        }

        // If we reach here, handle with AJAX
        e.preventDefault();
        e.stopImmediatePropagation(); // Optional: Prevent other handlers

        // Evitar recarga si es la misma URL exacta sin hash
        try {
            const dest = new URL(linkElement.href, window.location.origin).href;
            const cur = new URL(window.location.href, window.location.origin).href;
            if (dest === cur) { return; }
        } catch(_e) {}

        const delay = linkElement.dataset.ajaxDelay ? parseInt(linkElement.dataset.ajaxDelay, 10) : 0;

        setTimeout(() => {
            load(linkElement.href, true);
        }, delay);
    }

    /**
     * Handles browser back/forward navigation.
     * @param {PopStateEvent} e - The popstate event.
     */
    function handlePopState(e) {
        // Check if the state object has our expected URL (it should if pushed by us)
        // Or just use location.href as the target
        const targetUrl = e.state && e.state.url ? e.state.url : location.href;

        // Create a dummy link to check if this URL should be handled by AJAX
        // This prevents issues if the user navigates back to a non-AJAX page
        const pseudoLink = document.createElement('a');
        pseudoLink.href = targetUrl;

        if (!skipAjax(targetUrl, pseudoLink)) {
            gloryLog(`Popstate triggered: ${targetUrl}`);
            load(targetUrl, false); // Load content, false = don't push state again
        } else {
            // If popstate leads to a URL that should be skipped (e.g., external),
            // force a full page load to ensure correct behavior.
            gloryLog(`Popstate requires full load for: ${targetUrl}`);
            window.location.reload(); // Or window.location.href = targetUrl;
        }
    }

    // --- Initialization ---
    // Evitar doble inicialización
    if (window.gloryAjaxNavInitialized) {
        gloryLog('Glory AJAX Nav already initialized.');
        return;
    }
    window.gloryAjaxNavInitialized = true;

    document.addEventListener('DOMContentLoaded', () => {
        // Double-check abort condition after DOM is ready (in case conditions change)
        if (typeof config.shouldAbortInit === 'function' && config.shouldAbortInit()) {
            gloryLog('Glory AJAX Nav aborted in DOMContentLoaded by shouldAbortInit hook');
            return;
        }

        const contentElement = document.querySelector(config.contentSelector);
        if (!contentElement) {
            console.warn(`AJAX Nav disabled: Content element "${config.contentSelector}" not found.`);
            return; // Don't initialize if the main container isn't there
        }

        // Cache initial page state and content if caching is enabled
        if (config.cacheEnabled) {
            const initialUrl = window.location.href;
            if (shouldCache(initialUrl) && contentElement.innerHTML) {
                // Extraer scripts del head de la página inicial (sin ejecutar, ya están en el documento)
                const initialHeadScripts = extractAndExecuteHeadScripts(document, false);
                pageCache[initialUrl] = {
                    content: contentElement.innerHTML,
                    headScripts: initialHeadScripts
                };
                gloryLog(`Initial page cached: ${initialUrl} (with ${initialHeadScripts.length} head scripts)`);
                // Use replaceState for the initial load so it doesn't create a redundant history entry
                history.replaceState({url: initialUrl}, '', initialUrl);
            }
        }

        // Attach event listeners
        document.body.addEventListener('click', handleClick);
        window.addEventListener('popstate', handlePopState);

        // Prefetch en hover/mousedown/focus
        if (config.prefetchOnHover) {
            const onHover = (ev) => {
                const a = ev.target && ev.target.closest && ev.target.closest('a');
                if (!a || !a.href) return;
                prefetch(a.href);
            };
            document.body.addEventListener('mouseover', onHover, { passive: true });
            document.body.addEventListener('mousedown', onHover, { passive: true });
            document.body.addEventListener('focusin', onHover, { passive: true });
        }

        // Prefetch por visibilidad (Quicklink-like)
        if (config.prefetchInViewport && 'IntersectionObserver' in window) {
            try {
                const seen = new WeakSet();
                const obs = new IntersectionObserver((entries) => {
                    for (let i = 0; i < entries.length; i++) {
                        const it = entries[i];
                        if (!it.isIntersecting) continue;
                        const a = it.target;
                        if (seen.has(a)) continue;
                        seen.add(a);
                        if (!a.href) continue;
                        prefetch(a.href);
                    }
                }, { rootMargin: '200px' });

                const limit = Math.max(1, Number(config.prefetchMaxEntries) || 24);
                let count = 0;
                document.querySelectorAll('a[href]')
                    .forEach((a) => {
                        if (count >= limit) return;
                        try {
                            const u = new URL(a.href, window.location.origin);
                            if (u.origin !== window.location.origin) return;
                            obs.observe(a);
                            count++;
                        } catch(_e) {}
                    });
            } catch(_e) {}
        }

        // Trigger initializers for the first page load
        // Use requestAnimationFrame to ensure layout is stable before firing
        requestAnimationFrame(triggerPageReady);

        gloryLog('Glory AJAX Navigation Initialized with config:', config);
    });

    // Extiende carga de assets externos: permitir async paralelo si el script remoto lo marca
    const _origLoadExternalAssetsFromDoc = loadExternalAssetsFromDoc;
    function loadExternalAssetsFromDoc(doc) {
        return new Promise((resolveOuter) => {
            try {
                const head = document.head || document.getElementsByTagName('head')[0];
                const body = document.body || document.documentElement;

                // Estilos
                doc.querySelectorAll('link[rel="stylesheet"][href]').forEach((lnk) => {
                    const href = lnk.getAttribute('href');
                    if (!href || isStylesheetLoaded(href)) return;
                    const nl = document.createElement('link');
                    nl.rel = 'stylesheet';
                    nl.href = toAbsoluteUrl(href);
                    head.appendChild(nl);
                });

                // Scripts externos
                const scripts = Array.prototype.slice.call(doc.querySelectorAll('script[src]'));
                const toLoad = scripts.filter(s => s.getAttribute('src') && !isScriptLoaded(s.getAttribute('src')));
                if (!toLoad.length) { resolveOuter(); return; }

                if (!config.allowAsyncExternalScripts) {
                    // Mantener comportamiento original secuencial
                    const seq = (i) => { if (i >= toLoad.length) return resolveOuter();
                        const oldScript = toLoad[i];
                        const ns = document.createElement('script');
                        for (let j=0;j<oldScript.attributes.length;j++) {
                            const attr = oldScript.attributes[j];
                            if (attr.name === 'src') continue;
                            ns.setAttribute(attr.name, attr.value);
                        }
                        ns.src = toAbsoluteUrl(oldScript.getAttribute('src'));
                        ns.onload = () => seq(i+1);
                        ns.onerror = () => seq(i+1);
                        (body || document.documentElement).appendChild(ns);
                    };
                    return seq(0);
                }

                const asyncAllowed = [];
                const sequential = [];
                toLoad.forEach((oldScript) => {
                    if (oldScript.hasAttribute('data-glory-async') || oldScript.getAttribute('async') === '' ) {
                        asyncAllowed.push(oldScript);
                    } else {
                        sequential.push(oldScript);
                    }
                });

                let remaining = asyncAllowed.length;
                const done = () => {
                    const seq = (i) => { if (i >= sequential.length) return resolveOuter();
                        const oldScript = sequential[i];
                        const ns = document.createElement('script');
                        for (let j=0;j<oldScript.attributes.length;j++) {
                            const attr = oldScript.attributes[j];
                            if (attr.name === 'src') continue;
                            ns.setAttribute(attr.name, attr.value);
                        }
                        ns.src = toAbsoluteUrl(oldScript.getAttribute('src'));
                        ns.onload = () => seq(i+1);
                        ns.onerror = () => seq(i+1);
                        (body || document.documentElement).appendChild(ns);
                    };
                    seq(0);
                };

                if (!asyncAllowed.length) { return done(); }

                asyncAllowed.forEach((oldScript) => {
                    const ns = document.createElement('script');
                    for (let j=0;j<oldScript.attributes.length;j++) {
                        const attr = oldScript.attributes[j];
                        if (attr.name === 'src') continue;
                        ns.setAttribute(attr.name, attr.value);
                    }
                    ns.async = true;
                    ns.src = toAbsoluteUrl(oldScript.getAttribute('src'));
                    ns.onload = ns.onerror = () => { remaining--; if (remaining === 0) done(); };
                    (body || document.documentElement).appendChild(ns);
                });
            } catch(_e) { resolveOuter(); }
        });
    }
})();
