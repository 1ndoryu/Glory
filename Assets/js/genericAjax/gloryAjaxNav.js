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
        ignoreUrlPatterns: [ // Array of regex patterns to skip AJAX for
            '/wp-admin',
            '/wp-login.php',
            '\\.(pdf|zip|rar|jpg|jpeg|png|gif|webp|mp3|mp4|xml|txt|docx|xlsx)$' // Common file extensions
        ],
        ignoreUrlParams: ['s', 'nocache'], // Query params that prevent caching
        noAjaxClass: 'noAjax' // Class name to manually disable AJAX on links/containers
    };

    // Merge defaults with user-provided config (if available)
    const config = { ...defaults, ...(window.dataGlobal || {}) };

    // Exit immediately if disabled via config
    if (!config.enabled) {
        // console.log('Glory AJAX Nav is disabled via configuration.');
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
            detail: { contentElement: contentElement }
        });
        document.dispatchEvent(event);
        // console.log('Event gloryRecarga dispatched.');
    }

    /**
     * Checks if a URL should be handled by standard browser navigation.
     * @param {string | undefined} url - The URL to check.
     * @param {HTMLAnchorElement} linkElement - The clicked link element.
     * @returns {boolean} - True to skip AJAX, false to use AJAX.
     */
    function skipAjax(url, linkElement) {
        if (!url) return true;
        const currentOrigin = window.location.origin;
        const urlObject = new URL(url, currentOrigin); // Handles relative URLs correctly

        // 1. Basic checks: non-http(s), different origin, target=_blank, download attr
        if (!urlObject.protocol.startsWith('http') ||
            urlObject.origin !== currentOrigin ||
            linkElement.getAttribute('target') === '_blank' ||
            linkElement.hasAttribute('download')) {
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
            // console.error("Error parsing URL for caching decision:", url, e);
            return false; // Don't cache if URL is invalid
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
            // console.log(`Loading from cache: ${url}`);
            contentElement.innerHTML = pageCache[url];
            if (pushState) {
                history.pushState({ url: url }, '', url);
            }
            // Scroll container
            if (mainScrollElement) {
                requestAnimationFrame(() => { mainScrollElement.scrollTop = 0; });
            } else {
                window.scrollTo({ top: 0, behavior: 'auto' });
            }
            triggerPageReady(); // Re-init scripts for cached content
            return;
        }

        // Show loading indicators
        if (loadingBar) {
            loadingBar.style.transition = 'width 0.3s ease, opacity 0.3s ease'; // Ensure transition is set
            loadingBar.style.width = '0%'; // Reset width before showing
            loadingBar.style.opacity = '1';
            requestAnimationFrame(() => { // Allow repaint before starting animation
                loadingBar.style.width = '70%';
            });
        }
        contentElement.style.transition = 'opacity 0.3s ease';
        contentElement.style.opacity = '0.5';

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("text/html")) {
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
                    // console.error(`AJAX Nav Error: Selector "${config.contentSelector}" not found in fetched HTML from ${url}. Loading full page.`);
                    window.location.href = url; // Fallback to full page load
                    return;
                }

                // Replace content & title
                contentElement.innerHTML = newContent.innerHTML;
                if (newTitle) document.title = newTitle.textContent;

                // Cache if applicable
                if (shouldCache(url)) {
                    pageCache[url] = newContent.innerHTML;
                    // console.log(`Cached: ${url}`);
                }

                // Update History
                if (pushState) {
                    history.pushState({ url: url }, '', url);
                }

                // Scroll container (if found)
                if (mainScrollElement) {
                    requestAnimationFrame(() => { mainScrollElement.scrollTop = 0; });
                } else {
                    window.scrollTo({ top: 0, behavior: 'auto' }); // Fallback to window scroll
                }

                // Hide loading indicators
                contentElement.style.opacity = '1';
                if (loadingBar) {
                    loadingBar.style.width = '100%';
                    setTimeout(() => {
                        loadingBar.style.opacity = '0';
                        // Reset width after fade out for next use
                        setTimeout(() => { if (loadingBar) loadingBar.style.width = '0%'; }, 300); // Wait for opacity transition
                    }, 150); // Delay before fade out
                }

                // Trigger re-initialization for dynamically loaded content
                triggerPageReady();

            })
            .catch(error => {
                console.error('AJAX Load Error:', error);
                if (loadingBar) { // Hide loading bar on error
                    loadingBar.style.opacity = '0';
                    setTimeout(() => { if (loadingBar) loadingBar.style.width = '0%'; }, 300);
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

        // Use skipAjax for detailed checks (URL pattern, class, origin etc.)
        if (skipAjax(linkElement.href, linkElement)) {
            // console.log(`AJAX skipped for: ${linkElement.href}`);
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
        const targetUrl = (e.state && e.state.url) ? e.state.url : location.href;

        // Create a dummy link to check if this URL should be handled by AJAX
        // This prevents issues if the user navigates back to a non-AJAX page
        const pseudoLink = document.createElement('a');
        pseudoLink.href = targetUrl;

        if (!skipAjax(targetUrl, pseudoLink)) {
            // console.log(`Popstate triggered: ${targetUrl}`);
            load(targetUrl, false); // Load content, false = don't push state again
        } else {
            // If popstate leads to a URL that should be skipped (e.g., external),
            // force a full page load to ensure correct behavior.
            // console.log(`Popstate requires full load for: ${targetUrl}`);
            window.location.reload(); // Or window.location.href = targetUrl;
        }
    }

    // --- Initialization ---
    // Evitar doble inicialización
    if (window.gloryAjaxNavInitialized) {
        // console.log('Glory AJAX Nav already initialized.');
        return;
    }
    window.gloryAjaxNavInitialized = true;

    document.addEventListener('DOMContentLoaded', () => {
        const contentElement = document.querySelector(config.contentSelector);
        if (!contentElement) {
             console.warn(`AJAX Nav disabled: Content element "${config.contentSelector}" not found.`);
             return; // Don't initialize if the main container isn't there
        }

        // Cache initial page state and content if caching is enabled
        if (config.cacheEnabled) {
            const initialUrl = window.location.href;
            if (shouldCache(initialUrl) && contentElement.innerHTML) {
                pageCache[initialUrl] = contentElement.innerHTML;
                // Use replaceState for the initial load so it doesn't create a redundant history entry
                history.replaceState({ url: initialUrl }, '', initialUrl);
            }
        }

        // Attach event listeners
        document.body.addEventListener('click', handleClick);
        window.addEventListener('popstate', handlePopState);

        // Trigger initializers for the first page load
        // Use requestAnimationFrame to ensure layout is stable before firing
        requestAnimationFrame(triggerPageReady);

        console.log('Glory AJAX Navigation Initialized with config:', config);
    });

})();