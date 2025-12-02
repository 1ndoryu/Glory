;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Obtiene un valor anidado de un objeto usando notación de punto
     * @param {Object} obj - Objeto fuente
     * @param {string} path - Ruta en notación punto (ej: 'padding.superior')
     * @returns {*} Valor encontrado o undefined
     */
    function getDeepValue(obj, path) {
        if (!obj || !path) return undefined;
        var value = obj;
        var segments = path.split('.');
        for (var i = 0; i < segments.length; i += 1) {
            if (value === null || value === undefined) return undefined;
            value = value[segments[i]];
        }
        return value;
    }

    /**
     * Obtiene el valor por defecto del tema para un rol y propiedad específicos
     * Jerarquía: themeSettings guardados > valores CSS actuales (via cssSync)
     * @param {string} role - Rol del bloque (principal, secundario, etc.)
     * @param {string} path - Ruta de la propiedad (ej: 'padding.superior')
     * @returns {*} Valor del tema o undefined
     */
    function getThemeDefault(role, path) {
        if (!role) return undefined;
        
        // 1. Intentar desde themeSettings guardados
        var themeSettings = (typeof gloryGbnCfg !== 'undefined' && gloryGbnCfg.themeSettings) 
            ? gloryGbnCfg.themeSettings 
            : (Gbn.config && Gbn.config.themeSettings ? Gbn.config.themeSettings : null);
        
        if (themeSettings && themeSettings.components && themeSettings.components[role]) {
            var themeVal = getDeepValue(themeSettings.components[role], path);
            if (themeVal !== undefined && themeVal !== null && themeVal !== '') {
                return themeVal;
            }
        }
        
        // 2. Fallback: leer valores actuales desde el CSS via cssSync
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
     * Obtiene el valor de configuración de un bloque, con fallback a defaults del tema
     * @param {Object} block - Bloque con config y role
     * @param {string} path - Ruta de la propiedad
     * @returns {*} Valor encontrado o undefined
     */
    function getConfigValue(block, path) {
        if (!block || !path) return undefined;
        
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
     * Agrega descripción/hint a un campo
     * @param {HTMLElement} container - Contenedor del campo
     * @param {Object} field - Definición del campo
     */
    function appendFieldDescription(container, field) {
        if (!field || !field.descripcion) return;
        var hint = document.createElement('p');
        hint.className = 'gbn-field-hint';
        hint.textContent = field.descripcion;
        container.appendChild(hint);
    }

    /**
     * Parsea un valor de spacing en valor numérico y unidad
     * @param {*} raw - Valor crudo (ej: '20px', 20, '1.5rem')
     * @param {string} fallbackUnit - Unidad por defecto
     * @returns {{valor: string, unidad: string}}
     */
    function parseSpacingValue(raw, fallbackUnit) {
        if (raw === null || raw === undefined || raw === '') {
            return { valor: '', unidad: fallbackUnit || 'px' };
        }
        if (typeof raw === 'number') {
            return { valor: String(raw), unidad: fallbackUnit || 'px' };
        }
        var match = /^(-?\d+(?:\.\d+)?)([a-z%]*)$/i.exec(String(raw).trim());
        if (!match) {
            return { valor: String(raw), unidad: fallbackUnit || 'px' };
        }
        return { valor: match[1], unidad: match[2] || fallbackUnit || 'px' };
    }

    /**
     * Íconos SVG para campos de spacing
     */
    var ICONS = {
        superior: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke-opacity="0.3"></rect><path d="M4 6h16"></path></svg>',
        derecha: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke-opacity="0.3"></rect><path d="M18 4v16"></path></svg>',
        inferior: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke-opacity="0.3"></rect><path d="M4 18h16"></path></svg>',
        izquierda: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke-opacity="0.3"></rect><path d="M6 4v16"></path></svg>'
    };

    /**
     * Mapeo de propiedades de config a propiedades CSS
     */
    var CONFIG_TO_CSS_MAP = {
        'padding.superior': 'paddingTop',
        'padding.derecha': 'paddingRight',
        'padding.inferior': 'paddingBottom',
        'padding.izquierda': 'paddingLeft',
        'fondo': 'backgroundColor',
        'background': 'backgroundColor',
        'gap': 'gap',
        'layout': 'display',
        'flexDirection': 'flexDirection',
        'flexWrap': 'flexWrap',
        'flexJustify': 'justifyContent',
        'flexAlign': 'alignItems',
        'height': 'height',
        'alineacion': 'textAlign',
        'maxAncho': 'maxWidth',
        'color': 'color',
        'size': 'fontSize'
    };

    /**
     * Obtiene el valor computado de una propiedad CSS de un elemento
     * @param {HTMLElement} element - Elemento DOM
     * @param {string} cssProperty - Propiedad CSS (camelCase, ej: 'paddingTop')
     * @returns {string|undefined} Valor computado o undefined
     */
    function getComputedValue(element, cssProperty) {
        if (!element || !cssProperty) return undefined;
        try {
            var computed = window.getComputedStyle(element);
            var value = computed[cssProperty];
            // Retornar undefined si es vacío o no existe
            if (value === '' || value === undefined || value === null) {
                return undefined;
            }
            return value;
        } catch (e) {
            return undefined;
        }
    }

    /**
     * Obtiene el valor computado para una ruta de configuración
     * Usa el mapeo CONFIG_TO_CSS_MAP para traducir la ruta a propiedad CSS
     * @param {HTMLElement} element - Elemento DOM
     * @param {string} configPath - Ruta de configuración (ej: 'padding.superior')
     * @returns {string|undefined} Valor computado o undefined
     */
    function getComputedValueForPath(element, configPath) {
        if (!element || !configPath) return undefined;
        var cssProperty = CONFIG_TO_CSS_MAP[configPath];
        if (!cssProperty) return undefined;
        return getComputedValue(element, cssProperty);
    }

    /**
     * Determina el valor efectivo para un campo del panel
     * Prioridad: 1) block.config, 2) computedStyle, 3) themeDefault
     * Retorna { value, source, placeholder }
     * @param {Object} block - Bloque con config, role y element
     * @param {string} path - Ruta de la propiedad
     * @returns {{value: *, source: string, placeholder: *}}
     */
    function getEffectiveValue(block, path) {
        var result = { value: undefined, source: 'none', placeholder: undefined };
        
        // 1. Valor guardado en config del bloque (máxima prioridad)
        var savedValue = getDeepValue(block.config, path);
        if (savedValue !== undefined && savedValue !== null && savedValue !== '') {
            result.value = savedValue;
            result.source = 'config';
        }
        
        // 2. Si no hay valor guardado, leer del computedStyle
        if (result.source === 'none' && block.element) {
            var computedValue = getComputedValueForPath(block.element, path);
            if (computedValue !== undefined && computedValue !== null && computedValue !== '') {
                // Obtener theme default para comparar
                var themeDefault = getThemeDefault(block.role, path);
                var parsedComputed = parseSpacingValue(computedValue);
                var parsedTheme = parseSpacingValue(themeDefault);
                
                // Si el valor computado es diferente al default del tema, 
                // significa que hay estilos inline o de clase que debemos mostrar
                if (parsedComputed.valor !== parsedTheme.valor) {
                    result.value = computedValue;
                    result.source = 'computed';
                }
            }
        }
        
        // 3. Placeholder: siempre es el valor del tema (si existe)
        var themePlaceholder = getThemeDefault(block.role, path);
        if (themePlaceholder !== undefined && themePlaceholder !== null) {
            result.placeholder = themePlaceholder;
        }
        
        return result;
    }

    /**
     * Evalúa si un campo debe mostrarse basado en su condición
     * Usa getEffectiveValue para incluir valores computados del DOM
     * Para Theme Settings con campos de componentes (ej: 'components.principal.layout'),
     * la condición se evalúa relativa al componente
     * @param {Object} block - Bloque actual
     * @param {Object} field - Definición del campo
     * @returns {boolean}
     */
    function shouldShowField(block, field) {
        if (!field || !field.condicion || !Array.isArray(field.condicion)) {
            return true;
        }
        
        var cond = field.condicion;
        var key, operator, value;

        if (cond.length === 2) {
            key = cond[0];
            operator = '==';
            value = cond[1];
        } else if (cond.length === 3) {
            key = cond[0];
            operator = cond[1];
            value = cond[2];
        } else {
            return true;
        }

        var current;
        
        // Para Theme Settings con campos de componentes
        // Si field.id es 'components.{role}.{prop}', buscar condición en 'components.{role}.{key}'
        if (field.id && field.id.indexOf('components.') === 0) {
            var parts = field.id.split('.');
            if (parts.length >= 3) {
                var componentPath = parts.slice(0, 2).join('.') + '.' + key;
                current = getDeepValue(block.config, componentPath);
            }
        }
        
        // Si no encontramos valor con la lógica de componentes, usar getEffectiveValue normal
        if (current === undefined || current === null) {
            var effective = getEffectiveValue(block, key);
            current = effective.value;
            
            // Para 'layout', mapear 'flex' desde computedStyle 'display: flex'
            if (key === 'layout' && effective.source === 'computed') {
                if (current === 'flex') current = 'flex';
                else if (current === 'grid') current = 'grid';
                else if (current === 'block' || current === 'block flow') current = 'block';
            }
        }

        switch (operator) {
            case '==': return current === value;
            case '!=': return current !== value;
            case 'in': return Array.isArray(value) && value.indexOf(current) !== -1;
            case '!in': return Array.isArray(value) && value.indexOf(current) === -1;
            default: return true;
        }
    }

    // Exportar utilidades
    Gbn.ui.fieldUtils = {
        getDeepValue: getDeepValue,
        getThemeDefault: getThemeDefault,
        getConfigValue: getConfigValue,
        getComputedValue: getComputedValue,
        getComputedValueForPath: getComputedValueForPath,
        getEffectiveValue: getEffectiveValue,
        appendFieldDescription: appendFieldDescription,
        parseSpacingValue: parseSpacingValue,
        shouldShowField: shouldShowField,
        CONFIG_TO_CSS_MAP: CONFIG_TO_CSS_MAP,
        ICONS: ICONS
    };

})(window);

