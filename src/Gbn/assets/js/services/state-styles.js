;(function (global) {
    'use strict';

    /**
     * State Styles Service
     * 
     * PROPÓSITO:
     * Este servicio maneja la lectura, escritura y generación de estilos para
     * pseudo-clases CSS como :hover, :focus, :active.
     * 
     * PROBLEMA QUE RESUELVE (Fase 10):
     * - El panel no puede leer estilos :hover de clases CSS
     * - No hay forma de editar colores de hover desde el panel
     * - Los estilos hover de clases CSS no se detectan
     * 
     * ARQUITECTURA DE ESTADOS:
     * Los estados se almacenan en config._states:
     * {
     *   _states: {
     *     hover: { backgroundColor: '#e5e5e5', color: '#000' },
     *     focus: { borderColor: '#1d8ff1', boxShadow: '...' },
     *     active: { transform: 'scale(0.98)' }
     *   }
     * }
     * 
     * LIMITACIÓN CONOCIDA:
     * getComputedStyle() NO puede leer pseudo-clases directamente.
     * Debemos parsear las hojas de estilo cargadas en el documento.
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.services = Gbn.services || {};

    // Estados soportados
    var SUPPORTED_STATES = ['hover', 'focus', 'active', 'visited', 'focus-visible', 'focus-within'];

    // Cache de reglas parseadas (evita re-parsear en cada selección)
    var rulesCache = new WeakMap();
    var lastCacheTime = 0;
    var CACHE_TTL = 5000; // 5 segundos de TTL para el cache

    /**
     * Parsea las hojas de estilo del documento para extraer reglas con pseudo-clases
     * @returns {Object} Mapa de reglas por selector+estado
     */
    function parseStylesheets() {
        var now = Date.now();
        
        // Usar cache si es válido
        if (rulesCache._data && (now - lastCacheTime) < CACHE_TTL) {
            return rulesCache._data;
        }

        var rules = {};
        var sheets = document.styleSheets;

        for (var i = 0; i < sheets.length; i++) {
            try {
                var sheet = sheets[i];
                // Algunos stylesheets externos pueden lanzar SecurityError
                if (!sheet.cssRules) continue;

                for (var j = 0; j < sheet.cssRules.length; j++) {
                    var rule = sheet.cssRules[j];
                    parseRule(rule, rules);
                }
            } catch (e) {
                // Error de CORS, ignorar esta stylesheet
                console.warn('[GBN StateStyles] No se pudo acceder a stylesheet:', e.message);
            }
        }

        // Actualizar cache
        rulesCache._data = rules;
        lastCacheTime = now;

        return rules;
    }

    /**
     * Parsea una regla CSS individual, incluyendo reglas anidadas (@media, etc.)
     */
    function parseRule(rule, rulesMap) {
        // Regla normal (CSSStyleRule)
        if (rule.selectorText) {
            var selector = rule.selectorText;
            
            // Buscar pseudo-clases
            SUPPORTED_STATES.forEach(function(state) {
                var pseudoPattern = ':' + state;
                if (selector.indexOf(pseudoPattern) !== -1) {
                    // Extraer el selector base (sin la pseudo-clase)
                    var baseSelector = extractBaseSelector(selector, state);
                    if (baseSelector) {
                        var key = baseSelector + '::' + state;
                        if (!rulesMap[key]) {
                            rulesMap[key] = {};
                        }
                        // Mezclar estilos (último gana por cascada)
                        var styles = extractStyles(rule.style);
                        Object.assign(rulesMap[key], styles);
                    }
                }
            });
        }
        // Reglas @media u otras nested rules
        else if (rule.cssRules) {
            for (var i = 0; i < rule.cssRules.length; i++) {
                parseRule(rule.cssRules[i], rulesMap);
            }
        }
    }

    /**
     * Extrae el selector base de un selector con pseudo-clase
     * Ejemplo: ".btnPrimary:hover" -> ".btnPrimary"
     */
    function extractBaseSelector(fullSelector, state) {
        var pseudoPattern = ':' + state;
        var index = fullSelector.indexOf(pseudoPattern);
        if (index === -1) return null;
        
        // Tomar la parte antes de la pseudo-clase
        var basePart = fullSelector.substring(0, index);
        
        // Manejar selectores compuestos (ej: "div.btn:hover, a.link:hover")
        // Por ahora retornamos solo la parte antes del pseudo
        return basePart.trim();
    }

    /**
     * Extrae estilos de un CSSStyleDeclaration a un objeto plano
     */
    function extractStyles(styleDecl) {
        var styles = {};
        for (var i = 0; i < styleDecl.length; i++) {
            var prop = styleDecl[i];
            var value = styleDecl.getPropertyValue(prop);
            if (value) {
                // Convertir a camelCase para consistencia con el resto del código
                styles[toCamelCase(prop)] = value;
            }
        }
        return styles;
    }

    /**
     * Convierte property-name a propertyName
     */
    function toCamelCase(str) {
        return str.replace(/-([a-z])/g, function(g) { 
            return g[1].toUpperCase(); 
        });
    }

    /**
     * Convierte propertyName a property-name
     */
    function toKebabCase(str) {
        return str.replace(/([A-Z])/g, function(g) {
            return '-' + g[0].toLowerCase();
        });
    }

    /**
     * Obtiene los estilos de un estado específico para un elemento
     * @param {HTMLElement} element - Elemento DOM
     * @param {string} state - Estado ('hover', 'focus', etc.)
     * @returns {Object} Estilos encontrados o objeto vacío
     */
    function getStateStyles(element, state) {
        if (!element || !state) return {};
        if (SUPPORTED_STATES.indexOf(state) === -1) return {};

        var allRules = parseStylesheets();
        var matchedStyles = {};

        // Obtener todos los selectores que aplican al elemento
        var selectors = getMatchingSelectors(element);

        // Buscar reglas que coincidan
        selectors.forEach(function(selector) {
            var key = selector + '::' + state;
            if (allRules[key]) {
                Object.assign(matchedStyles, allRules[key]);
            }
        });

        return matchedStyles;
    }

    /**
     * Obtiene los selectores que podrían aplicar a un elemento
     * (IDs, clases, atributos)
     */
    function getMatchingSelectors(element) {
        var selectors = [];

        // ID
        if (element.id) {
            selectors.push('#' + element.id);
        }

        // Clases
        if (element.classList) {
            for (var i = 0; i < element.classList.length; i++) {
                selectors.push('.' + element.classList[i]);
            }
        }

        // Atributos de GBN
        ['gloryButton', 'gloryTexto', 'gloryDiv', 'gloryDivSecundario'].forEach(function(attr) {
            if (element.hasAttribute(attr)) {
                selectors.push('[' + attr + ']');
            }
        });

        // data-gbn-id
        var gbnId = element.getAttribute('data-gbn-id');
        if (gbnId) {
            selectors.push('[data-gbn-id="' + gbnId + '"]');
        }

        return selectors;
    }

    /**
     * Lee todos los estados de un elemento desde las hojas de estilo
     * @param {HTMLElement} element - Elemento DOM
     * @returns {Object} Mapa de estados con sus estilos
     */
    function getAllStatesFromCSS(element) {
        var states = {};
        SUPPORTED_STATES.forEach(function(state) {
            var styles = getStateStyles(element, state);
            if (Object.keys(styles).length > 0) {
                states[state] = styles;
            }
        });
        return states;
    }

    /**
     * Obtiene la configuración de estados de un bloque
     * Combina estilos de CSS con los guardados en config
     * @param {Object} block - Bloque GBN
     * @param {string} state - Estado específico o null para todos
     * @returns {Object} Configuración de estados
     */
    function getBlockStates(block, state) {
        if (!block) return {};

        // Estilos desde CSS (clases)
        var cssStates = block.element ? getAllStatesFromCSS(block.element) : {};
        
        // Estilos guardados en config
        var configStates = (block.config && block.config._states) || {};

        // Mezclar (config tiene prioridad sobre CSS)
        var merged = {};
        SUPPORTED_STATES.forEach(function(s) {
            if (cssStates[s] || configStates[s]) {
                merged[s] = Object.assign({}, cssStates[s] || {}, configStates[s] || {});
            }
        });

        if (state) {
            return merged[state] || {};
        }
        return merged;
    }

    /**
     * Guarda estilos de un estado específico en la configuración del bloque
     * @param {Object} block - Bloque GBN
     * @param {string} state - Estado ('hover', 'focus', etc.)
     * @param {Object} styles - Estilos a guardar
     */
    function setStateStyles(block, state, styles) {
        if (!block || !state || SUPPORTED_STATES.indexOf(state) === -1) return;

        // Inicializar _states si no existe
        if (!block.config._states) {
            block.config._states = {};
        }

        // Guardar o eliminar el estado
        if (styles && Object.keys(styles).length > 0) {
            block.config._states[state] = Object.assign({}, styles);
        } else {
            delete block.config._states[state];
        }

        // Disparar actualización del estado global si está disponible
        if (Gbn.state && Gbn.state.updateConfig) {
            Gbn.state.updateConfig(block.id, { _states: block.config._states });
        }
    }

    /**
     * Actualiza una propiedad específica dentro de un estado
     * @param {Object} block - Bloque GBN
     * @param {string} state - Estado ('hover', 'focus', etc.)
     * @param {string} property - Propiedad CSS (camelCase)
     * @param {*} value - Valor a establecer (null para eliminar)
     */
    function setStateProperty(block, state, property, value) {
        if (!block || !state || !property) return;
        if (SUPPORTED_STATES.indexOf(state) === -1) return;

        // Inicializar estructura si es necesario
        if (!block.config._states) {
            block.config._states = {};
        }
        if (!block.config._states[state]) {
            block.config._states[state] = {};
        }

        // Establecer o eliminar propiedad
        if (value !== null && value !== undefined && value !== '') {
            block.config._states[state][property] = value;
        } else {
            delete block.config._states[state][property];
            // Limpiar estado vacío
            if (Object.keys(block.config._states[state]).length === 0) {
                delete block.config._states[state];
            }
        }

        // Disparar actualización
        if (Gbn.state && Gbn.state.updateConfig) {
            Gbn.state.updateConfig(block.id, { _states: block.config._states });
        }
    }

    /**
     * Genera CSS para los estados de un bloque
     * @param {Object} block - Bloque GBN con config._states
     * @returns {string} CSS generado para pseudo-clases
     */
    function generateStateCSS(block) {
        if (!block || !block.config || !block.config._states) return '';

        var css = '';
        var selector = '[data-gbn-id="' + block.id + '"]';
        var states = block.config._states;

        Object.keys(states).forEach(function(state) {
            var styles = states[state];
            if (!styles || Object.keys(styles).length === 0) return;

            var declarations = [];
            Object.keys(styles).forEach(function(prop) {
                var kebabProp = toKebabCase(prop);
                declarations.push('  ' + kebabProp + ': ' + styles[prop] + ';');
            });

            if (declarations.length > 0) {
                css += selector + ':' + state + ' {\n';
                css += declarations.join('\n');
                css += '\n}\n';
            }
        });

        return css;
    }

    /**
     * Invalida el cache forzando un re-parseo en la próxima lectura
     */
    function invalidateCache() {
        rulesCache._data = null;
        lastCacheTime = 0;
    }

    // Exportar servicio
    Gbn.services.stateStyles = {
        // Constantes
        SUPPORTED_STATES: SUPPORTED_STATES,
        
        // Lectura
        getStateStyles: getStateStyles,
        getAllStatesFromCSS: getAllStatesFromCSS,
        getBlockStates: getBlockStates,
        
        // Escritura
        setStateStyles: setStateStyles,
        setStateProperty: setStateProperty,
        
        // Generación CSS
        generateStateCSS: generateStateCSS,
        
        // Utilidades
        parseStylesheets: parseStylesheets,
        invalidateCache: invalidateCache,
        toCamelCase: toCamelCase,
        toKebabCase: toKebabCase
    };

})(window);
