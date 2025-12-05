;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.services = Gbn.services || {};

    /**
     * Generador de estilos CSS responsive y estados (hover/focus)
     * 
     * ACTUALIZADO (Fase 10): Ahora incluye soporte para pseudo-clases CSS
     * Los estados se almacenan en config._states y se generan como reglas separadas
     */
    var StyleGenerator = {
        
        /**
         * Estados CSS soportados para generación
         */
        SUPPORTED_STATES: ['hover', 'focus', 'active', 'visited', 'focus-visible', 'focus-within'],
        
        /**
         * Genera el CSS completo para todos los bloques
         * Incluye: responsive (tablet/mobile) + estados (hover/focus)
         * @param {Object} blocksMap Mapa de bloques por ID
         * @returns {string} CSS generado
         */
        generateCss: function(blocksMap) {
            if (!blocksMap) return '';
            
            var css = '';
            var tabletRules = [];
            var mobileRules = [];
            var stateRules = []; // Nuevo: reglas de estados
            
            Object.keys(blocksMap).forEach(function(id) {
                var block = blocksMap[id];
                if (!block || !block.config) return;
                
                // Increase specificity to override template styles (e.g. .btnPrimary:hover)
                var selector = 'body [data-gbn-id="' + id + '"]';
                
                // === RESPONSIVE (existente) ===
                if (block.config._responsive) {
                    var responsive = block.config._responsive;
                    
                    // Tablet
                    if (responsive.tablet) {
                        var tabletStyles = StyleGenerator.extractStyles(responsive.tablet, block.role);
                        if (Object.keys(tabletStyles).length > 0) {
                            tabletRules.push(StyleGenerator.createRule(selector, tabletStyles));
                        }
                    }
                    
                    // Mobile
                    if (responsive.mobile) {
                        var mobileStyles = StyleGenerator.extractStyles(responsive.mobile, block.role);
                        if (Object.keys(mobileStyles).length > 0) {
                            mobileRules.push(StyleGenerator.createRule(selector, mobileStyles));
                        }
                    }
                }
                
                // === ESTADOS (Fase 10) ===
                if (block.config._states) {
                    var statesCss = StyleGenerator.generateBlockStates(id, block.config._states);
                    if (statesCss) {
                        stateRules.push(statesCss);
                    }
                }
            });
            
            // Estados primero (menor especificidad base)
            if (stateRules.length > 0) {
                css += '/* Estados (hover/focus/active) */\n';
                css += stateRules.join('\n') + '\n';
            }
            
            if (tabletRules.length > 0) {
                css += '@media (max-width: 1024px) {\n' + tabletRules.join('\n') + '\n}\n';
            }
            
            if (mobileRules.length > 0) {
                css += '@media (max-width: 768px) {\n' + mobileRules.join('\n') + '\n}\n';
            }
            
            return css;
        },
        
        /**
         * Genera CSS para los estados de un bloque específico
         * @param {string} blockId - ID del bloque
         * @param {Object} states - Objeto con estados {hover: {...}, focus: {...}}
         * @returns {string} CSS generado
         */
        generateBlockStates: function(blockId, states) {
            if (!blockId || !states || typeof states !== 'object') return '';
            
            var css = '';
            var selector = 'body [data-gbn-id="' + blockId + '"]';
            var self = this;
            
            self.SUPPORTED_STATES.forEach(function(state) {
                if (!states[state] || Object.keys(states[state]).length === 0) return;
                
                var stateStyles = self.extractStyles(states[state]);
                if (Object.keys(stateStyles).length > 0) {
                    css += self.createRule(selector + ':' + state, stateStyles) + '\n';
                }
            });
            
            return css;
        },
        
        /**
         * Crea una regla CSS
         */
        createRule: function(selector, styles) {
            var rule = '  ' + selector + ' { ';
            Object.keys(styles).forEach(function(prop) {
                var val = styles[prop];
                // Ensure overrides (responsive/states) always beat inline base styles
                if (String(val).indexOf('!important') === -1) {
                    val += ' !important';
                }
                rule += prop + ': ' + val + '; ';
            });
            rule += '}';
            return rule;
        },
        
        /**
         * Convierte camelCase a kebab-case
         * @param {string} str - String en camelCase
         * @returns {string} String en kebab-case
         */
        toKebabCase: function(str) {
            return str.replace(/([A-Z])/g, function(g) {
                return '-' + g[0].toLowerCase();
            });
        },
        
        /**
         * Extrae estilos CSS de un objeto de configuración (similar a styleResolvers pero genérico)
         * 
         * NOTA (Fase 10): Esta función ahora soporta tanto la estructura anidada (padding.superior)
         * como propiedades CSS planas en camelCase (backgroundColor) que vienen de _states.
         */
        extractStyles: function(config, role) {
            var styles = {};
            var self = this;
            
            // === PROPIEDADES CSS PLANAS (camelCase) ===
            // Los estados (_states.hover, _states.focus) guardan propiedades CSS directamente
            // en camelCase. Las convertimos a kebab-case para el CSS final.
            // 
            // [BUG FIX] Agregadas propiedades de spacing (padding*, margin*) que se guardan
            // en camelCase cuando el usuario edita spacing en estados hover/focus.
            // Sin estas propiedades, el CSS generado no incluía padding-top, margin-left, etc.
            // causando que los estilos no persistieran después de guardar.
            var cssDirectProps = [
                'backgroundColor', 'color', 'borderColor', 'borderWidth', 'borderStyle', 
                'borderRadius', 'transform', 'transition', 'opacity', 'boxShadow',
                'textDecoration', 'cursor', 'fontWeight', 'fontSize', 'fontFamily',
                'lineHeight', 'letterSpacing', 'textTransform', 'textShadow',
                // Spacing (padding/margin) - necesarios para estados hover/focus
                'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
                'marginTop', 'marginRight', 'marginBottom', 'marginLeft'
            ];
            
            cssDirectProps.forEach(function(prop) {
                if (config[prop] !== undefined && config[prop] !== null && config[prop] !== '') {
                    var kebabProp = self.toKebabCase(prop);
                    styles[kebabProp] = config[prop];
                }
            });
            
            // === ESTRUCTURA ANIDADA (configuración de bloque normal) ===
            // Padding
            if (config.padding) {
                if (typeof config.padding === 'object') {
                    if (config.padding.superior) styles['padding-top'] = config.padding.superior;
                    if (config.padding.derecha) styles['padding-right'] = config.padding.derecha;
                    if (config.padding.inferior) styles['padding-bottom'] = config.padding.inferior;
                    if (config.padding.izquierda) styles['padding-left'] = config.padding.izquierda;
                } else {
                    styles['padding'] = config.padding;
                }
            }
            
            // Margin
            if (config.margin) {
                if (typeof config.margin === 'object') {
                    if (config.margin.superior) styles['margin-top'] = config.margin.superior;
                    if (config.margin.derecha) styles['margin-right'] = config.margin.derecha;
                    if (config.margin.inferior) styles['margin-bottom'] = config.margin.inferior;
                    if (config.margin.izquierda) styles['margin-left'] = config.margin.izquierda;
                } else {
                    styles['margin'] = config.margin;
                }
            }
            
            // Dimensions
            if (config.width) styles['width'] = config.width;
            if (config.height) styles['height'] = config.height;
            if (config.maxAncho) styles['max-width'] = config.maxAncho;
            
            // Colors (fallback para estructura legacy)
            if (config.background && !styles['background-color']) styles['background-color'] = config.background;
            if (config.color && !styles['color']) styles['color'] = config.color;
            
            // Typography (si config.typography existe como objeto)
            function processTypo(typoConfig, prefix) {
                if (!typoConfig) return;
                if (typoConfig.size && !styles['font-size']) styles[prefix + 'font-size'] = typoConfig.size;
                if (typoConfig.lineHeight && !styles['line-height']) styles[prefix + 'line-height'] = typoConfig.lineHeight;
                if (typoConfig.letterSpacing && !styles['letter-spacing']) styles[prefix + 'letter-spacing'] = typoConfig.letterSpacing;
                if (typoConfig.transform && !styles['text-transform']) styles[prefix + 'text-transform'] = typoConfig.transform;
                if (typoConfig.font && typoConfig.font !== 'Default' && !styles['font-family']) styles[prefix + 'font-family'] = typoConfig.font;
                if (typoConfig.weight && !styles['font-weight']) styles[prefix + 'font-weight'] = typoConfig.weight;
            }
            
            if (config.typography) processTypo(config.typography, '');
            
            // Layout (Flex/Grid)
            if (config.display) styles['display'] = config.display;
            if (config.flexDirection) styles['flex-direction'] = config.flexDirection;
            if (config.flexWrap) styles['flex-wrap'] = config.flexWrap;
            if (config.justifyContent) styles['justify-content'] = config.justifyContent;
            if (config.alignItems) styles['align-items'] = config.alignItems;
            if (config.gap) styles['gap'] = config.gap;
            
            return styles;
        }
    };

    Gbn.services.styleGenerator = StyleGenerator;

})(window);
