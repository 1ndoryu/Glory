;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;
    var STYLE_ATTR = 'data-gbn-style';
    
    // Propiedades CSS que GBN puede controlar - se limpian al aplicar nuevos estilos
    var GBN_CONTROLLED_PROPERTIES = [
        'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'background', 'background-color',
        'gap', 'row-gap', 'column-gap',
        'display', 'flex-direction', 'flex-wrap', 'justify-content', 'align-items',
        'grid-template-columns', 'grid-template-rows',
        'height', 'max-width', 'width', 'flex-basis', 'flex-shrink', 'flex-grow',
        'text-align', 'color', 'font-size', 'font-family', 'line-height', 
        'letter-spacing', 'text-transform'
    ];

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
        if (!block || !block.element) {
            return;
        }
        styles = styles || {};
        
        // Leer estilos actuales
        var currentInline = utils.parseStyleString(block.element.getAttribute('style') || '');
        
        // Limpiar propiedades controladas por GBN que no están en los nuevos estilos
        // Esto permite que los valores borrados vuelvan al default del tema
        var cleanedInline = {};
        Object.keys(currentInline).forEach(function(prop) {
            var propLower = prop.toLowerCase();
            // Si la propiedad está controlada por GBN y no viene en los nuevos estilos, no la incluimos
            if (GBN_CONTROLLED_PROPERTIES.indexOf(propLower) !== -1) {
                // Solo mantener si viene en los nuevos estilos
                if (styles[prop] !== undefined || styles[propLower] !== undefined) {
                    cleanedInline[prop] = currentInline[prop];
                }
                // Si no viene en styles, se limpia (no se incluye)
            } else {
                // Propiedades no controladas por GBN se mantienen
                cleanedInline[prop] = currentInline[prop];
            }
        });
        
        // Mezclar con los nuevos estilos
        var merged = utils.assign({}, cleanedInline, styles);
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

