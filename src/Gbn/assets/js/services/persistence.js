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

        // Capturar HTML del contenido para persistencia estructural (Nuevos bloques)
        var rootHtml = '';
        var rootEl = document.querySelector('main') || document.querySelector('[data-gbn-root]');
        if (rootEl) {
            // Clonar para limpiar clases de UI si es necesario, aunque el backend debería limpiar
            // Por ahora enviamos raw, DomProcessor limpiará
            rootHtml = rootEl.innerHTML;
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


