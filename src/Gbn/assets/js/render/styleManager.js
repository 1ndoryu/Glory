;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;
    var STYLE_ATTR = 'data-gbn-style';

    function ensureStyleElement(id) {
        var selector = 'style[' + STYLE_ATTR + '="' + id + '"]';
        var existing = document.querySelector(selector);
        if (existing) {
            return existing;
        }
        var style = document.createElement('style');
        style.setAttribute(STYLE_ATTR, id);
        style.type = 'text/css';
        (document.head || document.body || document.documentElement).appendChild(style);
        return style;
    }

    function applyInlineStyles(block, styles) {
        if (!block || !block.element || !styles) {
            return;
        }
        var currentInline = utils.parseStyleString(block.element.getAttribute('style') || '');
        var merged = utils.assign({}, currentInline, styles);
        var styleString = utils.stringifyStyles(merged);
        if (styleString) {
            block.element.setAttribute('style', styleString);
        } else {
            block.element.removeAttribute('style');
        }
        block.styles.current = utils.assign({}, styles);
    }

    function writeRule(block, styles) {
        if (!block || !styles) {
            return;
        }
        var cssBody = utils.stringifyStyles(styles);
        if (!cssBody) {
            return;
        }
        var selector = '[data-gbn-id="' + block.id + '"]';
        var rule = selector + ' {' + cssBody + ';}';
        var styleEl = ensureStyleElement(block.id);
        styleEl.textContent = rule;
        block.styles.current = utils.assign({}, styles);
    }

    function ensureBaseline(block) {
        if (!block || !block.styles) {
            return;
        }
        // Solo escribir reglas CSS para estilos que no estén en inline
        // Los estilos inline ya están aplicados en el elemento
        var inlineKeys = Object.keys(block.styles.inline || {});
        var nonInlineStyles = {};
        if (block.styles.current) {
            Object.keys(block.styles.current).forEach(function(key) {
                if (inlineKeys.indexOf(key) === -1) {
                    nonInlineStyles[key] = block.styles.current[key];
                }
            });
        }
        if (Object.keys(nonInlineStyles).length) {
            writeRule(block, nonInlineStyles);
        }
    }

    function update(blockId, styles) {
        var block = typeof blockId === 'string' ? state.get(blockId) : blockId;
        if (!block) {
            return;
        }
        // Aplicar estilos directamente al elemento para máxima prioridad
        applyInlineStyles(block, styles);
    }

    Gbn.styleManager = {
        ensureBaseline: ensureBaseline,
        update: update,
    };
})(window);

