;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;

    function buildIndex(blocks) {
        var byId = {};
        var byEl = new WeakMap();
        (blocks || []).forEach(function (b) { byId[b.id] = b; byEl.set(b.element, b); });
        return { byId: byId, byEl: byEl };
    }

    function findParentBlock(block, index) {
        var el = block && block.element ? block.element.parentElement : null;
        while (el) {
            var parent = index.byEl.get(el);
            if (parent) { return parent; }
            el = el.parentElement;
        }
        return null;
    }

    function computeTree(blocks) {
        var list = (blocks || []).slice();
        var index = buildIndex(list);
        var rootChildren = [];
        var childrenMap = {};

        list.forEach(function (b) { childrenMap[b.id] = []; });

        list.forEach(function (b) {
            var parent = findParentBlock(b, index);
            if (parent) { childrenMap[parent.id].push(b.id); }
            else { rootChildren.push(b.id); }
        });

        function sortByDom(ids) {
            return ids.sort(function (idA, idB) {
                var elA = index.byId[idA].element;
                var elB = index.byId[idB].element;
                if (!elA || !elB) return 0;
                return (elA.compareDocumentPosition(elB) & 4) ? -1 : 1;
            });
        }

        function assignOrder(ids) { 
            return sortByDom(ids).map(function (id, i) { return { id: id, order: i }; }); 
        }

        return {
            root: assignOrder(rootChildren),
            childrenMap: Object.keys(childrenMap).reduce(function (acc, k) {
                acc[k] = assignOrder(childrenMap[k]);
                return acc;
            }, {}),
        };
    }

    /**
     * Limpia los clones de PostRender antes de persistir.
     * PostRender debe ser 100% dinámico: solo guardamos el template (PostItem original),
     * no los posts clonados que se generan en el preview.
     * 
     * @param {HTMLElement} container Contenedor raíz del contenido
     * @returns {void}
     */
    function cleanPostRenderClones(container) {
        if (!container) return;
        
        // Buscar todos los contenedores PostRender
        var postRenders = container.querySelectorAll('[gloryPostRender], [glorypostrender]');
        
        postRenders.forEach(function(pr) {
            // Eliminar todos los clones generados por el preview
            var clones = pr.querySelectorAll('[data-gbn-pr-clone]');
            clones.forEach(function(clone) {
                clone.parentNode.removeChild(clone);
            });
            
            // Restaurar y limpiar template original
            var template = pr.querySelector('[data-gbn-is-template]');
            if (template) {
                template.style.display = '';
                template.removeAttribute('data-gbn-is-template');
                template.removeAttribute('data-gbn-original-structure');
            }
            
            // RESTAURAR CONTENIDO ORIGINAL DE LOS CAMPOS DE PREVIEW
            // Antes de guardar, volvemos a poner los placeholders originales
            var previewFields = pr.querySelectorAll('[data-gbn-preview-field]');
            previewFields.forEach(function(field) {
                var originalContent = field.getAttribute('data-gbn-original-content');
                if (originalContent !== null) {
                    field.innerHTML = originalContent;
                }
                // Limpiar atributos de preview
                field.removeAttribute('data-gbn-original-content');
                field.removeAttribute('data-gbn-preview-field');
            });
            
            // Limpiar atributos de preview del template
            var postItems = pr.querySelectorAll('[gloryPostItem], [glorypostitem]');
            postItems.forEach(function(item) {
                item.removeAttribute('data-post-id');
                item.removeAttribute('data-categories');
                item.removeAttribute('data-gbn-preview-populated');
                item.removeAttribute('data-gbn-preview-post-id');
                item.removeAttribute('data-gbn-is-template');
                item.removeAttribute('data-gbn-original-structure');
            });
            
            // Limpiar mensajes de preview
            var messages = pr.querySelectorAll('.gbn-pr-preview-message');
            messages.forEach(function(msg) {
                msg.parentNode.removeChild(msg);
            });
            
            // Limpiar indicador de posts encontrados
            var indicators = pr.querySelectorAll('.gbn-pr-posts-indicator');
            indicators.forEach(function(ind) {
                ind.parentNode.removeChild(ind);
            });
            
            // Limpiar banner informativo de modo plantilla (solo visible en editor)
            var infobanners = pr.querySelectorAll('.gbn-pr-template-info');
            infobanners.forEach(function(banner) {
                banner.parentNode.removeChild(banner);
            });
            
            // Limpiar badges de plantilla de los PostItems
            var badges = pr.querySelectorAll('.gbn-template-badge');
            badges.forEach(function(badge) {
                badge.parentNode.removeChild(badge);
            });
            
            // Limpiar atributos que indican procesamiento previo
            // Esto fuerza a PostRenderProcessor a procesar de nuevo en el frontend
            pr.classList.remove('gbn-post-render');
            pr.removeAttribute('data-post-type');
            pr.removeAttribute('data-posts-per-page');
            pr.removeAttribute('data-pattern');
            pr.removeAttribute('data-hover-effect');
        });
    }

    function collectBlocksPayload() {
        var blocks = state.all();
        var tree = computeTree(blocks);
        var orderById = {};
        tree.root.forEach(function (x) { orderById[x.id] = x.order; });
        Object.keys(tree.childrenMap).forEach(function (pid) {
            (tree.childrenMap[pid] || []).forEach(function (x) { orderById[x.id] = x.order; });
        });

        return blocks.map(function (b) {
            var styles = (b.styles && b.styles.current) ? utils.assign({}, b.styles.current) : {};
            return {
                id: b.id,
                role: b.role || 'block',
                order: orderById[b.id] || 0,
                config: utils.assign({}, b.config || {}),
                styles: styles,
                children: (tree.childrenMap[b.id] || []).map(function (x) { return x.id; }),
                domPath: utils.computeDomPath(b.element), // Debugging path mismatch
            };
        });
    }

    async function savePageConfig() {
        var ajaxFn = global.gloryAjax || global.enviarAjax;
        var cfg = utils.getConfig();
        if (typeof ajaxFn !== 'function' || !cfg || !cfg.pageId) {
            utils.warn('Persistencia: gloryAjax o configuración no disponibles');
            return { success: false, message: 'Persistencia no disponible' };
        }
        // Generar CSS responsive
        var blocksMap = {};
        state.all().forEach(function(b) { blocksMap[b.id] = b; });
        var responsiveCss = (Gbn.services && Gbn.services.styleGenerator) 
            ? Gbn.services.styleGenerator.generateCss(blocksMap) 
            : '';

        // Capturar HTML del contenido para persistencia estructural
        // IMPORTANTE: Clonamos para limpiar sin afectar el DOM visible
        var rootHtml = '';
        var rootEl = document.querySelector('[data-gbn-root]') || document.querySelector('main');
        if (rootEl) {
            // Clonar el contenedor para no modificar el DOM visible
            var clonedRoot = rootEl.cloneNode(true);
            
            // Limpiar clones de PostRender del clon
            // Esto asegura que PostRender sea 100% dinámico (solo guardamos el template)
            cleanPostRenderClones(clonedRoot);
            
            rootHtml = clonedRoot.innerHTML;
        }

        var payload = {
            nonce: cfg.nonce,
            pageId: cfg.pageId,
            blocks: JSON.stringify(collectBlocksPayload()),
            responsiveCss: responsiveCss,
            htmlContent: rootHtml // Nuevo campo
        };
        return ajaxFn('gbn_save_config', payload);
    }

    async function restorePage() {
        var ajaxFn = global.gloryAjax || global.enviarAjax;
        var cfg = utils.getConfig();
        if (typeof ajaxFn !== 'function' || !cfg || !cfg.pageId) {
            utils.warn('Persistencia: gloryAjax o configuración no disponibles');
            return { success: false, message: 'Persistencia no disponible' };
        }
        return ajaxFn('gbn_restore_page', { nonce: cfg.nonce, pageId: cfg.pageId });
    }

    async function getPageSettings() {
        var ajaxFn = global.gloryAjax || global.enviarAjax;
        var cfg = utils.getConfig();
        if (typeof ajaxFn !== 'function' || !cfg || !cfg.pageId) {
            return { success: false, message: 'Configuración no disponible' };
        }
        return ajaxFn('gbn_get_page_settings', { nonce: cfg.nonce, pageId: cfg.pageId });
    }

    async function savePageSettings(settings) {
        var ajaxFn = global.gloryAjax || global.enviarAjax;
        var cfg = utils.getConfig();
        if (typeof ajaxFn !== 'function' || !cfg || !cfg.pageId) {
            return { success: false, message: 'Configuración no disponible' };
        }
        return ajaxFn('gbn_save_page_settings', { nonce: cfg.nonce, pageId: cfg.pageId, settings: JSON.stringify(settings) });
    }

    async function getThemeSettings() {
        var ajaxFn = global.gloryAjax || global.enviarAjax;
        var cfg = utils.getConfig();
        if (typeof ajaxFn !== 'function') {
            return { success: false, message: 'Configuración no disponible' };
        }
        return ajaxFn('gbn_get_theme_settings', { nonce: cfg.nonce });
    }

    async function saveThemeSettings(settings) {
        var ajaxFn = global.gloryAjax || global.enviarAjax;
        var cfg = utils.getConfig();
        if (typeof ajaxFn !== 'function') {
            return { success: false, message: 'Configuración no disponible' };
        }
        return ajaxFn('gbn_save_theme_settings', { nonce: cfg.nonce, settings: JSON.stringify(settings) });
    }

    Gbn.persistence = { 
        savePageConfig: savePageConfig, 
        restorePage: restorePage,
        getPageSettings: getPageSettings,
        savePageSettings: savePageSettings,
        getThemeSettings: getThemeSettings,
        saveThemeSettings: saveThemeSettings
    };
})(window);


