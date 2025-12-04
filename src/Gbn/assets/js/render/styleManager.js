;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;
    var STYLE_ATTR = 'data-gbn-style';
    
    // Propiedades CSS que GBN puede controlar - se limpian al aplicar nuevos estilos
    var GBN_CONTROLLED_PROPERTIES = [
        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'background', 'background-color',
        'gap', 'row-gap', 'column-gap',
        'display', 'flex-direction', 'flex-wrap', 'justify-content', 'align-items',
        'grid-template-columns', 'grid-template-rows',
        'height', 'max-width', 'width', 'flex-basis', 'flex-shrink', 'flex-grow',
        'text-align', 'color', 'font-size', 'font-family', 'line-height', 
        'letter-spacing', 'text-transform', 'font', 'border', 'border-radius'
    ];

    /**
     * Estados CSS soportados (Fase 10)
     */
    var SUPPORTED_STATES = ['hover', 'focus', 'active', 'visited', 'focus-visible', 'focus-within'];

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

    function applyCustomCss(block, css) {
        if (!block) return;
        var styleId = block.id + '-custom';
        var styleEl = ensureStyleElement(styleId);
        
        if (!css) {
            styleEl.textContent = '';
            return;
        }

        // Replace & with block selector
        var selector = '[data-gbn-id="' + block.id + '"]';
        var processedCss = css.replace(/&/g, selector);
        
        // If no & found, wrap it (simple safety, though user should use &)
        if (processedCss.indexOf(selector) === -1) {
            processedCss = selector + ' { ' + processedCss + ' }';
        }

        styleEl.textContent = processedCss;
    }

    /**
     * Aplica estilos de un estado específico (hover, focus, etc.) - Fase 10
     * Genera una regla CSS con pseudo-clase
     * @param {Object} block - Bloque GBN
     * @param {string} stateName - Nombre del estado ('hover', 'focus', etc.)
     * @param {Object} styles - Estilos a aplicar en ese estado (propiedades en camelCase)
     */
    function applyStateCss(block, stateName, styles) {
        if (!block || !stateName) return;
        if (SUPPORTED_STATES.indexOf(stateName) === -1) return;
        
        var styleId = block.id + '-state-' + stateName;
        var styleEl = ensureStyleElement(styleId);
        
        if (!styles || Object.keys(styles).length === 0) {
            styleEl.textContent = '';
            return;
        }
        
        // [FIX Fase 10] Convertir propiedades de camelCase a kebab-case
        // porque _states guarda 'backgroundColor' pero CSS necesita 'background-color'
        var kebabStyles = {};
        Object.keys(styles).forEach(function(key) {
            var kebabKey = key.replace(/([A-Z])/g, function(g) {
                return '-' + g[0].toLowerCase();
            });
            kebabStyles[kebabKey] = styles[key];
        });
        
        var selector = '[data-gbn-id="' + block.id + '"]';
        var cssBody = utils.stringifyStyles(kebabStyles);
        // Include simulation class for editor preview
        var simulationSelector = selector + '.gbn-simulated-' + stateName;
        styleEl.textContent = selector + ':' + stateName + ', ' + simulationSelector + ' { ' + cssBody + ' }';
    }

    /**
     * Aplica todos los estilos de estados de un bloque
     * @param {Object} block - Bloque GBN con config._states
     */
    function applyAllStates(block) {
        if (!block || !block.config || !block.config._states) return;
        
        SUPPORTED_STATES.forEach(function(stateName) {
            var stateStyles = block.config._states[stateName];
            applyStateCss(block, stateName, stateStyles || {});
        });
    }

    /**
     * Limpia todos los estilos de estados de un bloque
     * @param {Object} block - Bloque GBN
     */
    function clearAllStates(block) {
        if (!block) return;
        
        SUPPORTED_STATES.forEach(function(stateName) {
            var styleId = block.id + '-state-' + stateName;
            var existing = document.querySelector('style[' + STYLE_ATTR + '="' + styleId + '"]');
            if (existing) {
                existing.textContent = '';
            }
        });
    }

    function update(blockId, styles) {
        var block = typeof blockId === 'string' ? state.get(blockId) : blockId;
        if (!block) {
            return;
        }
        
        // Extract Custom CSS
        var customCss = null;
        if (styles && styles.__custom_css !== undefined) {
            customCss = styles.__custom_css;
            delete styles.__custom_css;
        }
        
        // Apply Custom CSS
        applyCustomCss(block, customCss);

        // Aplicar estilos directamente al elemento para máxima prioridad
        applyInlineStyles(block, styles);
        
        // Aplicar estilos de estados si existen (Fase 10)
        if (block.config && block.config._states) {
            applyAllStates(block);
        }
    }

    Gbn.styleManager = {
        ensureBaseline: ensureBaseline,
        update: update,
        // Fase 10: Estados Hover/Focus
        applyStateCss: applyStateCss,
        applyAllStates: applyAllStates,
        clearAllStates: clearAllStates,
        SUPPORTED_STATES: SUPPORTED_STATES
    };
})(window);


