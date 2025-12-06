;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Módulo de valor efectivo.
     * 
     * Determina el valor final a mostrar en el panel considerando:
     * 1. Configuración del bloque (guardada)
     * 2. Estilos computados (de clases CSS)
     * 3. Defaults del tema
     * 4. Estados (hover, focus, etc.)
     * 
     * Retorna un objeto con { value, source, placeholder } para que
     * el campo pueda mostrar indicadores visuales del origen del valor.
     * 
     * @module effective-value
     */

    /**
     * Determina el valor efectivo para un campo del panel.
     * 
     * Prioridad:
     * 1) block.config (valor guardado)
     * 2) computedStyle (de clases CSS)
     * 3) themeDefault (placeholder)
     * 
     * Para estados (hover, focus):
     * 1) config._states[state] (guardado)
     * 2) stateStyles desde CSS (computado)
     * 3) valor normal como placeholder
     * 
     * @param {Object} block - Bloque con config, role y element
     * @param {string} path - Ruta de la propiedad
     * @returns {{value: *, source: string, placeholder: *}}
     * 
     * @example
     * getEffectiveValue(myBlock, 'padding.superior')
     * // Returns: { value: '20px', source: 'config', placeholder: '10px' }
     */
    function getEffectiveValue(block, path) {
        var result = { value: undefined, source: 'none', placeholder: undefined };
        
        var getDeepValue = Gbn.ui.fieldUtils.getDeepValue;
        var getThemeDefault = Gbn.ui.fieldUtils.getThemeDefault;
        var getComputedValueForPath = Gbn.ui.fieldUtils.getComputedValueForPath;
        var getStateConfig = Gbn.ui.fieldUtils.getStateConfig;
        var parseSpacingValue = Gbn.ui.fieldUtils.parseSpacingValue;
        var isBrowserDefault = Gbn.ui.fieldUtils.isBrowserDefault;
        var CONFIG_TO_CSS_MAP = Gbn.ui.fieldUtils.CONFIG_TO_CSS_MAP;
        
        // Determinar el estado actual de edición (Normal, Hover, Focus)
        var currentState = 'normal';
        if (Gbn.ui.panelRender && Gbn.ui.panelRender.getCurrentState) {
            currentState = Gbn.ui.panelRender.getCurrentState();
        }

        // --- LÓGICA PARA ESTADOS (Hover, Focus, etc.) ---
        if (currentState !== 'normal') {
            // 1. Configuración guardada para el estado
            var stateConfigVal = getStateConfig(block, currentState, path);
            if (stateConfigVal !== undefined && stateConfigVal !== null && stateConfigVal !== '') {
                result.value = stateConfigVal;
                result.source = 'state-config';
                return result;
            }

            // 2. Estilos computados del estado (desde CSS)
            if (Gbn.services.stateStyles && Gbn.services.stateStyles.getStateStyles) {
                var cssProp = CONFIG_TO_CSS_MAP[path];
                if (cssProp) {
                    var stateStyles = Gbn.services.stateStyles.getStateStyles(block.element, currentState);
                    var computedStateVal = stateStyles[cssProp];
                    
                    if (computedStateVal !== undefined && computedStateVal !== null && computedStateVal !== '') {
                        result.value = computedStateVal;
                        result.source = 'state-computed';
                        return result;
                    }
                }
            }

            // 3. Fallback: Mostrar el valor "Normal" como placeholder
            var baseConfig = getDeepValue(block.config, path);
            if (baseConfig !== undefined) {
                result.placeholder = baseConfig;
            } else {
                var baseComputed = getComputedValueForPath(block.element, path);
                if (baseComputed !== undefined) {
                    result.placeholder = baseComputed;
                }
            }
            
            return result;
        }

        // --- LÓGICA NORMAL (Desktop/Responsive) ---
        var savedValue;
        
        // Usar getResponsiveValue para aprovechar la lógica de theme settings
        if (Gbn.responsive && Gbn.responsive.getResponsiveValue) {
            var bp = Gbn.responsive.getCurrentBreakpoint();
            savedValue = Gbn.responsive.getResponsiveValue(block, path, bp);
        } else {
            savedValue = getDeepValue(block.config, path);
        }
        
        if (savedValue !== undefined && savedValue !== null && savedValue !== '') {
            result.value = savedValue;
            result.source = 'config';
        }
        
        // [FIX BUG-017] Si no hay valor en config, buscar en defaults del schema PHP
        // Los defaults se definen en getDefaults() del componente y se exponen en gloryGbnCfg
        // Esto es CRÍTICO para campos no-CSS como logoMode, fieldType, etc.
        if (result.source === 'none' && block.role) {
            var cfg = global.gloryGbnCfg;
            if (cfg && cfg.roleSchemas && cfg.roleSchemas[block.role]) {
                var roleData = cfg.roleSchemas[block.role];
                var schemaDefault = getDeepValue(roleData.config, path);
                
                if (schemaDefault !== undefined && schemaDefault !== null && schemaDefault !== '') {
                    result.value = schemaDefault;
                    result.source = 'schema-default';
                }
            }
        }
        
        // 2. Si no hay valor guardado, leer del computedStyle
        if (result.source === 'none' && block.element) {
            
            // [FIX] Inferencia de hasBorder desde estilos computados
            if (path === 'hasBorder') {
                var bWidth = getComputedValueForPath(block.element, 'borderWidth');
                var bStyle = getComputedValueForPath(block.element, 'borderStyle');
                
                if (bWidth && bWidth !== '0px' && bWidth !== '0' && bStyle && bStyle !== 'none') {
                    result.value = true;
                    result.source = 'computed';
                    return result;
                }
                return result; 
            }

            var computedValue = getComputedValueForPath(block.element, path);
            if (computedValue !== undefined && computedValue !== null && computedValue !== '') {
                var themeDefault = getThemeDefault(block.role, path);
                
                // Verificar si es un valor por defecto del navegador
                var isBrowserDefaultVal = isBrowserDefault ? isBrowserDefault(path, computedValue) : false;
                
                // Si NO es un default del navegador, verificar si es diferente al tema
                if (!isBrowserDefaultVal) {
                    if (path === 'backgroundImage') {
                        if (computedValue !== 'none' && computedValue !== themeDefault) {
                            result.value = computedValue;
                            result.source = 'computed';
                        }
                    } else {
                        var parsedComputed = parseSpacingValue(computedValue);
                        var parsedTheme = parseSpacingValue(themeDefault);
                        
                        if (parsedComputed.valor !== parsedTheme.valor) {
                            result.value = computedValue;
                            result.source = 'computed';
                        }
                    }
                }
            }
        }
        
        // 3. Placeholder: valor del tema o valor computado como fallback
        // [BUG-003 FIX] Si no hay valor en themeSettings, usar el valor computado
        // Esto permite que variables CSS en theme-styles.css se muestren como placeholder
        var themePlaceholder = getThemeDefault(block.role, path);
        if (themePlaceholder !== undefined && themePlaceholder !== null) {
            result.placeholder = themePlaceholder;
        } else if (block.element && result.source === 'none') {
            // Fallback: usar valor computado como placeholder si no hay config ni tema
            var computedPlaceholder = getComputedValueForPath(block.element, path);
            if (computedPlaceholder !== undefined && computedPlaceholder !== null && computedPlaceholder !== '') {
                // Solo usar si no es un browser default
                var isDefault = isBrowserDefault ? isBrowserDefault(path, computedPlaceholder) : false;
                if (!isDefault) {
                    result.placeholder = computedPlaceholder;
                }
            }
        }
        
        return result;
    }

    // Exportar función
    Gbn.ui.fieldUtils.getEffectiveValue = getEffectiveValue;

})(window);
