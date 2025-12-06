;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Módulo de valores de configuración.
     * 
     * Proporciona funciones para obtener valores de configuración de bloques,
     * con soporte para responsive y fallback a defaults del tema.
     * 
     * @module config-values
     */

    /**
     * Obtiene el valor de configuración de un bloque, con fallback a defaults del tema.
     * 
     * @param {Object} block - Bloque con config y role
     * @param {string} path - Ruta de la propiedad
     * @returns {*} Valor encontrado o undefined
     * 
     * @example
     * getConfigValue(myBlock, 'padding.superior')
     * // Returns: '20px' (de config o theme default)
     */
    function getConfigValue(block, path) {
        if (!block || !path) return undefined;
        
        var getDeepValue = Gbn.ui.fieldUtils.getDeepValue;
        var getThemeDefault = Gbn.ui.fieldUtils.getThemeDefault;
        
        // 0. Intentar obtener valor responsive si el sistema está activo
        if (Gbn.responsive && Gbn.responsive.getResponsiveValue && Gbn.responsive.getCurrentBreakpoint) {
            var bp = Gbn.responsive.getCurrentBreakpoint();
            var val = Gbn.responsive.getResponsiveValue(block, path, bp);
            return val;
        }
        
        // 1. Intentar desde config del bloque
        var value = getDeepValue(block.config, path);
        if (value !== undefined && value !== null && value !== '') {
            return value;
        }

        // 2. Intentar desde defaults del tema (excepto para theme/page)
        if (block.role && block.role !== 'theme' && block.role !== 'page') {
            var themeVal = getThemeDefault(block.role, path);
            if (themeVal !== undefined && themeVal !== null && themeVal !== '') {
                return themeVal;
            }
        }

        return undefined;
    }

    /**
     * Obtiene valor considerando breakpoint activo y herencia responsive.
     * 
     * @param {Object} block - Bloque GBN
     * @param {string} path - Ruta de la propiedad
     * @param {string} breakpoint - Breakpoint específico (opcional)
     * @returns {*} Valor para el breakpoint
     */
    function getResponsiveConfigValue(block, path, breakpoint) {
        if (!Gbn.responsive || !Gbn.responsive.getResponsiveValue) {
            // Fallback si responsive no está disponible
            return getConfigValue(block, path);
        }
        
        return Gbn.responsive.getResponsiveValue(block, path, breakpoint);
    }

    /**
     * Determina el origen del valor actual para mostrar indicador correcto.
     * 
     * Fuentes posibles (en orden de prioridad):
     * - 'override': Valor específico del breakpoint actual
     * - 'tablet': Heredado de tablet (solo para mobile)
     * - 'block': Definido en config base del bloque
     * - 'theme': Viene de Theme Settings
     * - 'computed': Valor de clases CSS o estilos inline
     * - 'css': Fallback, no hay valor explícito
     * 
     * @param {Object} block - Bloque GBN
     * @param {string} path - Ruta de la propiedad
     * @param {string} breakpoint - Breakpoint actual (opcional)
     * @returns {string} Fuente del valor
     */
    function getValueSource(block, path, breakpoint) {
        breakpoint = breakpoint || (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint()) || 'desktop';
        
        var getDeepValue = Gbn.ui.fieldUtils.getDeepValue;
        var getComputedValueForPath = Gbn.ui.fieldUtils.getComputedValueForPath;
        var isBrowserDefault = Gbn.ui.fieldUtils.isBrowserDefault;
        
        if (!getDeepValue) return 'css';
        
        // 1. Override específico del breakpoint
        if (breakpoint !== 'desktop' && block.config._responsive && block.config._responsive[breakpoint]) {
            var val = getDeepValue(block.config._responsive[breakpoint], path);
            if (val !== undefined) return 'override';
        }
        
        // 2. Heredado de tablet (solo para mobile)
        if (breakpoint === 'mobile' && block.config._responsive && block.config._responsive.tablet) {
            var tabletVal = getDeepValue(block.config._responsive.tablet, path);
            if (tabletVal !== undefined) return 'tablet';
        }
        
        // 3. Desktop (base del bloque)
        var desktopVal = getDeepValue(block.config, path);
        if (desktopVal !== undefined) return 'block';
        
        // 4-6. Theme settings
        var themeSettings = (Gbn.config && Gbn.config.themeSettings) || (gloryGbnCfg && gloryGbnCfg.themeSettings) || {};
        var roleConfig = themeSettings.components && themeSettings.components[block.role];
        
        if (roleConfig && roleConfig._responsive && roleConfig._responsive[breakpoint]) {
            var themeVal = getDeepValue(roleConfig._responsive[breakpoint], path);
            if (themeVal !== undefined) return 'theme';
        }
        
        if (breakpoint === 'mobile' && roleConfig && roleConfig._responsive && roleConfig._responsive.tablet) {
            var themeTablet = getDeepValue(roleConfig._responsive.tablet, path);
            if (themeTablet !== undefined) return 'theme';
        }
        
        if (roleConfig) {
            var themeDesktop = getDeepValue(roleConfig, path);
            if (themeDesktop !== undefined) return 'theme';
        }
        
        // 7. Valores computados del DOM
        if (block.element && getComputedValueForPath) {
            var computedValue = getComputedValueForPath(block.element, path);
            if (computedValue !== undefined && computedValue !== null && computedValue !== '') {
                // Verificar si es default del navegador
                if (isBrowserDefault && !isBrowserDefault(path, computedValue)) {
                    return 'computed';
                }
            }
        }
        
        // 8. CSS defaults
        return 'css';
    }

    // Exportar funciones
    Gbn.ui.fieldUtils.getConfigValue = getConfigValue;
    Gbn.ui.fieldUtils.getResponsiveConfigValue = getResponsiveConfigValue;
    Gbn.ui.fieldUtils.getValueSource = getValueSource;

})(window);
