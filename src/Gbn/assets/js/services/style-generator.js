;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.services = Gbn.services || {};

    /**
     * Generador de estilos CSS responsive
     */
    var StyleGenerator = {
        
        /**
         * Genera el CSS completo para todos los bloques
         * @param {Object} blocksMap Mapa de bloques por ID
         * @returns {string} CSS generado
         */
        generateCss: function(blocksMap) {
            if (!blocksMap) return '';
            
            var css = '';
            var tabletRules = [];
            var mobileRules = [];
            
            Object.keys(blocksMap).forEach(function(id) {
                var block = blocksMap[id];
                if (!block || !block.config || !block.config._responsive) return;
                
                var responsive = block.config._responsive;
                var selector = '[data-gbn-id="' + id + '"]';
                
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
            });
            
            if (tabletRules.length > 0) {
                css += '@media (max-width: 1024px) {\n' + tabletRules.join('\n') + '\n}\n';
            }
            
            if (mobileRules.length > 0) {
                css += '@media (max-width: 768px) {\n' + mobileRules.join('\n') + '\n}\n';
            }
            
            return css;
        },
        
        /**
         * Crea una regla CSS
         */
        createRule: function(selector, styles) {
            var rule = '  ' + selector + ' { ';
            Object.keys(styles).forEach(function(prop) {
                rule += prop + ': ' + styles[prop] + '; ';
            });
            rule += '}';
            return rule;
        },
        
        /**
         * Extrae estilos CSS de un objeto de configuración (similar a styleResolvers pero genérico)
         */
        extractStyles: function(config, role) {
            var styles = {};
            
            // Reutilizamos la lógica de panel-render.js si es posible, 
            // pero aquí necesitamos algo más simple y directo para propiedades comunes.
            // Idealmente deberíamos refactorizar styleResolvers para que sea usable aquí.
            // Por ahora, implementamos lo básico: padding, margin, width, height, typography, colors.
            
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
            
            // Colors
            if (config.background) styles['background-color'] = config.background;
            if (config.color) styles['color'] = config.color;
            
            // Typography (si config.typography existe como objeto o propiedades sueltas)
            // En GBN parece que typography es un campo compuesto pero guarda en propiedades sueltas o anidadas?
            // En typography.js: baseId + '.size', baseId + '.font', etc.
            // Si el config viene plano o anidado depende de cómo se guarde.
            // Asumimos estructura anidada si viene de _responsive.
            
            // Helper para procesar typography si está anidado
            function processTypo(typoConfig, prefix) {
                if (!typoConfig) return;
                if (typoConfig.size) styles[prefix + 'font-size'] = typoConfig.size;
                if (typoConfig.lineHeight) styles[prefix + 'line-height'] = typoConfig.lineHeight;
                if (typoConfig.letterSpacing) styles[prefix + 'letter-spacing'] = typoConfig.letterSpacing;
                if (typoConfig.transform) styles[prefix + 'text-transform'] = typoConfig.transform;
                if (typoConfig.font && typoConfig.font !== 'Default') styles[prefix + 'font-family'] = typoConfig.font;
            }
            
            // Si hay un objeto 'typography'
            if (config.typography) processTypo(config.typography, '');
            
            // Si hay propiedades sueltas (legacy o estructura plana)
            if (config.fontSize) styles['font-size'] = config.fontSize;
            // ...
            
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
