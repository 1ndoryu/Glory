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
        if (block.styles.inline && Object.keys(block.styles.inline).length) {
            writeRule(block, block.styles.inline);
        }
    }

    function update(blockId, styles) {
        var block = typeof blockId === 'string' ? state.get(blockId) : blockId;
        if (!block) {
            return;
        }
        writeRule(block, styles);
    }

    Gbn.styleManager = {
        ensureBaseline: ensureBaseline,
        update: update,
    };
})(window);

