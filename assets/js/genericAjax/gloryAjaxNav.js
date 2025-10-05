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
        
        // Agnostic hook: Custom function to determine if AJAX should be skipped
        shouldSkipAjax: null, // function(url: string, linkElement: HTMLAnchorElement): boolean
        
        // Agnostic hook: Custom function to check if initialization should abort
        shouldAbortInit: null // function(): boolean
    };

    // Merge defaults with user-provided config (prefer a specific object to avoid collisions)
    const runtimeConfig = (window.gloryNavConfig || window.dataGlobal || {});
    const config = {...defaults, ...runtimeConfig};

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
    function executeInlineScriptsFromElement(containerEl) {
        if (!containerEl) return;
        const scripts = containerEl.querySelectorAll('script');
        scripts.forEach((oldScript) => {
            const newScript = document.createElement('script');
            // Copiar todos los atributos tal cual
            for (let i = 0; i < oldScript.attributes.length; i++) {
                const attr = oldScript.attributes[i];
                newScript.setAttribute(attr.name, attr.value);
            }
            // Si es inline, copiar su contenido
            if (!oldScript.src) {
                newScript.textContent = oldScript.textContent || '';
            }
            // Insertar en el documento para ejecutar
            (document.body || document.documentElement).appendChild(newScript);
            // Opcional: remover después para no ensuciar el DOM
            // setTimeout(() => newScript.parentNode && newScript.parentNode.removeChild(newScript), 0);
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
        if (config.ignoreUrlPatterns.some(pattern => new RegExp(pattern, 'i').test(pathAndQuery))) {
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
            contentElement.innerHTML = pageCache[url].content || pageCache[url];
            if (pushState) {
                history.pushState({url: url}, '', url);
            }
            // Restablecer posición de scroll
            resetScrollPosition();
            
            // Ejecutar scripts de configuración del head si están cacheados
            if (pageCache[url].headScripts) {
                gloryLog('Executing cached head scripts...');
                pageCache[url].headScripts.forEach((scriptCode) => {
                    try {
                        const ns = document.createElement('script');
                        ns.textContent = scriptCode;
                        ns.setAttribute('data-glory-cached', 'head-config');
                        (document.head || document.body).appendChild(ns);
                    } catch(_e) {}
                });
            }
            
            // Ejecutar scripts embebidos (los <script> del contenido cacheado)
            const cachedContent = pageCache[url].content || pageCache[url];
            executeInlineScriptsFromHTML(cachedContent);
            
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

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('text/html')) {
                    throw new TypeError(`Expected HTML but received ${contentType}. Aborting AJAX.`);
                }
                return response.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.querySelector(config.contentSelector);
                const newTitle = doc.querySelector('title');

                if (!newContent) {
                    console.error(`AJAX Nav Error: Selector "${config.contentSelector}" not found in fetched HTML from ${url}. Loading full page.`);
                    window.location.href = url; // Fallback to full page load
                    return;
                }

                // Replace content & title
                contentElement.innerHTML = newContent.innerHTML;
                if (newTitle) document.title = newTitle.textContent;

                // Extraer scripts del head ANTES de procesarlos (para cache y ejecución)
                const headScripts = extractAndExecuteHeadScripts(doc, false);
                
                // Cache if applicable - guardar tanto contenido como scripts del head
                if (shouldCache(url)) {
                    pageCache[url] = {
                        content: newContent.innerHTML,
                        headScripts: headScripts
                    };
                    gloryLog(`Cached: ${url} (with ${headScripts.length} head scripts)`);
                }

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
                        try {
                            const ns = document.createElement('script');
                            ns.textContent = scriptCode;
                            ns.setAttribute('data-glory-executed', 'head-config');
                            (document.head || document.body).appendChild(ns);
                        } catch(_e) {
                            //gloryLog('Error executing cached head script:', _e);
                        }
                    });
                    //gloryLog(`Executed ${headScripts.length} head scripts from extraction`);
                }

                // PASO 2: Cargar assets externos adicionales (scripts/estilos) y esperar a que terminen
                //gloryLog('Step 2: Loading external assets...');
                loadExternalAssetsFromDoc(doc).then(() => {
                    //gloryLog('Step 2: External assets loaded');
                    
                    // PASO 3: Ejecutar scripts embebidos presentes en el nuevo contenido
                    //gloryLog('Step 3: Executing inline content scripts...');
                    executeInlineScriptsFromElement(newContent);
                    
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

    /**
     * Handles click events on potential AJAX links.
     * @param {Event} e - The click event.
     */
    function handleClick(e) {
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

        // Trigger initializers for the first page load
        // Use requestAnimationFrame to ensure layout is stable before firing
        requestAnimationFrame(triggerPageReady);

        gloryLog('Glory AJAX Navigation Initialized with config:', config);
    });
})();
