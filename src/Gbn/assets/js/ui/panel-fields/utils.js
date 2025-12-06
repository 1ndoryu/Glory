;(function(global) {
    'use strict';

    /**
     * Panel Fields Utils - Orquestador Principal
     * 
     * Este archivo ha sido refactorizado en módulos más pequeños (Diciembre 2025).
     * Ahora actúa como punto de entrada que verifica y re-exporta las funciones
     * de los módulos individuales.
     * 
     * Módulos extraídos:
     * - deep-access.js      → getDeepValue, setDeepValue, deleteDeepValue, hasDeepValue
     * - theme-defaults.js   → getThemeDefault, hasThemeDefault
     * - css-map.js          → CONFIG_TO_CSS_MAP, UNITLESS_PROPERTIES, isBrowserDefault
     * - computed-styles.js  → getComputedValue, getComputedValueForPath
     * - config-values.js    → getConfigValue, getResponsiveConfigValue, getValueSource
     * - effective-value.js  → getEffectiveValue
     * - condition-handler.js → shouldShowField, shouldShowFieldMultiple
     * - state-utils.js      → SUPPORTED_STATES, getStateConfig, hasStateStyles
     * - helpers.js          → ICONS, parseSpacingValue, obtenerSchemaDelRole
     * 
     * IMPORTANTE: Los módulos deben cargarse en orden de dependencias en GbnManager.php
     * 
     * @module utils (orquestador)
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Verifica que todos los módulos necesarios estén cargados.
     * Útil para debugging cuando hay problemas de dependencias.
     */
    function verifyModulesLoaded() {
        var required = [
            'getDeepValue',
            'getThemeDefault', 
            'CONFIG_TO_CSS_MAP',
            'getComputedValue',
            'getConfigValue',
            'getEffectiveValue',
            'shouldShowField',
            'SUPPORTED_STATES',
            'parseSpacingValue'
        ];
        
        var missing = [];
        required.forEach(function(fn) {
            if (!Gbn.ui.fieldUtils[fn]) {
                missing.push(fn);
            }
        });
        
        if (missing.length > 0) {
            console.warn('[GBN] fieldUtils: Módulos faltantes:', missing.join(', '));
            return false;
        }
        
        return true;
    }

    // Ejecutar verificación después de que todos los scripts se carguen
    if (document.readyState === 'complete') {
        setTimeout(verifyModulesLoaded, 100);
    } else {
        global.addEventListener('load', function() {
            setTimeout(verifyModulesLoaded, 100);
        });
    }

    // Exponer función de verificación
    Gbn.ui.fieldUtils.verifyModulesLoaded = verifyModulesLoaded;

    /**
     * Legacy: Mantener compatibilidad con código que importa desde utils.js
     * Los módulos individuales ya exportan a Gbn.ui.fieldUtils, 
     * este archivo solo verifica y documenta.
     */

})(window);
