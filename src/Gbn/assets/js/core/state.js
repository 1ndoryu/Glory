;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var elementsIndex = new WeakMap();
    var usedIds = new Set();

    // Ensure Store is available (it should be loaded before state.js in dependency order)
    var store = Gbn.core && Gbn.core.store ? Gbn.core.store : null;

    function ensureId(el) {
        var existing = el.getAttribute('data-gbn-id');
        if (existing) {
            usedIds.add(existing);
            return existing;
        }
        var generated = utils.generateId(el);
        while (usedIds.has(generated)) {
            generated = generated + '-' + Math.floor(Math.random() * 10).toString(36);
        }
        usedIds.add(generated);
        el.setAttribute('data-gbn-id', generated);
        return generated;
    }

    function captureInlineStyles(el) {
        var inline = el.getAttribute('style');
        return utils.parseStyleString(inline || '');
    }

    function parseJsonAttr(el, attr, fallback) {
        if (!el || !attr) return fallback || null;
        var raw = el.getAttribute(attr);
        if (!raw) return fallback || null;
        try { return JSON.parse(raw); } catch (_) { return fallback || null; }
    }

    function registerBlock(role, el, meta) {
        if (!el) return null;
        
        // Check if already indexed
        var existing = elementsIndex.get(el);
        if (existing) {
            // Update meta if needed?
            return existing;
        }

        var id = ensureId(el);
        var initialConfig = parseJsonAttr(el, 'data-gbn-config', {}) || {};
        var initialSchema = parseJsonAttr(el, 'data-gbn-schema', []);
        
        var block = {
            id: id,
            role: role,
            element: el,
            meta: utils.assign({}, meta || {}),
            config: utils.assign({}, initialConfig),
            schema: Array.isArray(initialSchema) ? initialSchema : [],
            styles: {
                inline: captureInlineStyles(el),
                current: {}
            }
        };

        // Dispatch to Store
        if (store) {
            store.dispatch({
                type: store.Actions.ADD_BLOCK,
                payload: block
            });
        }

        elementsIndex.set(el, block);
        return block;
    }

    function updateConfig(id, nextConfig) {
        // Dispatch update to Store
        if (store) {
            store.dispatch({
                type: store.Actions.UPDATE_BLOCK,
                id: id,
                payload: nextConfig,
                breakpoint: 'desktop' // Force base update since nextConfig is already fully processed
            });
            
            // Return updated block from store
            var state = store.getState();
            var block = state.blocks[id];
            
            // Sync DOM attribute for persistence (legacy behavior, but good for backup)
            if (block && block.element) {
                 block.element.setAttribute('data-gbn-config', JSON.stringify(block.config));
            }
            return block;
        }
        return null;
    }

    function all() {
        if (store) {
            var state = store.getState();
            return Object.values(state.blocks);
        }
        return [];
    }

    function getByElement(el) {
        var block = elementsIndex.get(el);
        // Ensure we return the latest state from store if possible
        if (block && store) {
            var state = store.getState();
            if (state.blocks[block.id]) {
                return state.blocks[block.id];
            }
        }
        return block || null;
    }

    function get(id) {
        if (store) {
            var state = store.getState();
            return state.blocks[id] || null;
        }
        return null;
    }

    function deleteBlock(id) {
        if (store) {
            var state = store.getState();
            var block = state.blocks[id];
            if (block && block.element) {
                elementsIndex.delete(block.element);
                if (block.element.parentNode) {
                    block.element.parentNode.removeChild(block.element);
                }
            }
            store.dispatch({
                type: store.Actions.DELETE_BLOCK,
                id: id
            });
            usedIds.delete(id);
            return true;
        }
        return false;
    }
    
    function clear() {
        // Not implemented in store yet, but usually for full reset
        elementsIndex = new WeakMap();
        usedIds.clear();
    }

    Gbn.state = {
        register: registerBlock,
        updateConfig: updateConfig,
        deleteBlock: deleteBlock,
        all: all,
        getByElement: getByElement,
        get: get,
        clear: clear,
        parseJsonAttr: parseJsonAttr,
    };
})(window);

