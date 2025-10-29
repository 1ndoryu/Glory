;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var registry = new Map();
    var elementsIndex = new WeakMap();
    var usedIds = new Set();

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

    function registerBlock(role, el, meta) {
        if (!el) {
            return null;
        }
        var existing = elementsIndex.get(el);
        if (existing) {
            if (meta) {
                existing.meta = utils.assign({}, existing.meta, meta);
            }
            return existing;
        }

        var id = ensureId(el);
        var block = {
            id: id,
            role: role,
            element: el,
            meta: utils.assign({}, meta || {}),
            config: {},
            styles: {
                inline: captureInlineStyles(el),
                current: {}
            }
        };

        registry.set(id, block);
        elementsIndex.set(el, block);
        return block;
    }

    function updateConfig(id, nextConfig) {
        var block = registry.get(id);
        if (!block) {
            return null;
        }
        block.config = utils.assign({}, block.config, nextConfig || {});
        block.element.setAttribute('data-gbn-config', JSON.stringify(block.config));
        return block;
    }

    function all() {
        return Array.from(registry.values());
    }

    function getByElement(el) {
        return elementsIndex.get(el) || null;
    }

    function get(id) {
        return registry.get(id) || null;
    }

    function clear() {
        registry.clear();
        elementsIndex = new WeakMap();
        usedIds.clear();
    }

    Gbn.state = {
        register: registerBlock,
        updateConfig: updateConfig,
        all: all,
        getByElement: getByElement,
        get: get,
        clear: clear,
    };
})(window);

