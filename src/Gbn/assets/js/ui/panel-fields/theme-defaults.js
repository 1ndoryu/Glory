;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Módulo de defaults del tema.
     * 
     * Gestiona la obtención de valores por defecto desde Theme Settings,
     * con soporte para jerarquía de fuentes y responsive.
     * 
     * Jerarquía de prioridad:
     * 1. Gbn.config.themeSettings (estado local, puede tener cambios no guardados)
     * 2. gloryGbnCfg.themeSettings (valores del servidor)
     * 3. cssSync.readDefaults() (valores computados del CSS)
     * 
     * @module theme-defaults
     */

    /**
     * Obtiene el valor por defecto del tema para un rol y propiedad específicos.
     * 
     * @param {string} role - Rol del bloque (principal, secundario, etc.)
     * @param {string} path - Ruta de la propiedad (ej: 'padding.superior')
     * @returns {*} Valor del tema o undefined
     * 
     * @example
     * getThemeDefault('principal', 'padding.superior')
     * // Returns: '20px' (si está definido en theme settings)
     */
    function getThemeDefault(role, path) {
        if (!role) return undefined;

        var getDeepValue = Gbn.ui.fieldUtils.getDeepValue;
        if (!getDeepValue) {
            console.warn('[GBN] theme-defaults: getDeepValue no disponible');
            return undefined;
        }

        // Delegar a responsive.js si está disponible
        if (Gbn.responsive && Gbn.responsive.getThemeResponsiveValue && Gbn.responsive.getCurrentBreakpoint) {
            var bp = Gbn.responsive.getCurrentBreakpoint();
            return Gbn.responsive.getThemeResponsiveValue(role, path, bp);
        }
        
        // 1. PRIMERO: Intentar desde estado local (Gbn.config.themeSettings)
        // Este tiene prioridad porque puede contener cambios no guardados
        if (Gbn.config && Gbn.config.themeSettings) {
            var localSettings = Gbn.config.themeSettings;
            if (localSettings.components && localSettings.components[role]) {
                var localVal = getDeepValue(localSettings.components[role], path);
                if (localVal !== undefined && localVal !== null && localVal !== '') {
                    return localVal;
                }
            }
        }
        
        // 2. SEGUNDO: Intentar desde gloryGbnCfg (valores del servidor)
        if (typeof gloryGbnCfg !== 'undefined' && gloryGbnCfg.themeSettings) {
            var serverSettings = gloryGbnCfg.themeSettings;
            if (serverSettings.components && serverSettings.components[role]) {
                var serverVal = getDeepValue(serverSettings.components[role], path);
                if (serverVal !== undefined && serverVal !== null && serverVal !== '') {
                    return serverVal;
                }
            }
        }
        
        // 3. Fallback: leer valores actuales desde el CSS via cssSync
        if (Gbn.cssSync && Gbn.cssSync.readDefaults) {
            var cssDefaults = Gbn.cssSync.readDefaults();
            if (cssDefaults && cssDefaults.components && cssDefaults.components[role]) {
                var cssVal = getDeepValue(cssDefaults.components[role], path);
                if (cssVal !== undefined && cssVal !== null && cssVal !== '') {
                    return cssVal;
                }
            }
        }
        
        return undefined;
    }

    /**
     * Verifica si existe un valor de tema para un rol y propiedad.
     * 
     * @param {string} role - Rol del bloque
     * @param {string} path - Ruta de la propiedad
     * @returns {boolean} true si existe un valor definido
     */
    function hasThemeDefault(role, path) {
        var value = getThemeDefault(role, path);
        return value !== undefined && value !== null && value !== '';
    }

    // Exportar funciones
    Gbn.ui.fieldUtils.getThemeDefault = getThemeDefault;
    Gbn.ui.fieldUtils.hasThemeDefault = hasThemeDefault;

})(window);
