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
     * @param {string} role - Rol del bloque (principal, secundario, etc.)
     * @param {string} path - Ruta de la propiedad
     * @returns {*} Valor del tema o undefined
     */
    function getThemeDefault(role, path) {
        if (!role) return undefined;
        
        var themeSettings = (typeof gloryGbnCfg !== 'undefined' && gloryGbnCfg.themeSettings) 
            ? gloryGbnCfg.themeSettings 
            : (Gbn.config && Gbn.config.themeSettings ? Gbn.config.themeSettings : null);
        
        if (!themeSettings || !themeSettings.components || !themeSettings.components[role]) {
            return undefined;
        }
        
        return getDeepValue(themeSettings.components[role], path);
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
     * Evalúa si un campo debe mostrarse basado en su condición
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

        var current = getConfigValue(block, key);

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
        appendFieldDescription: appendFieldDescription,
        parseSpacingValue: parseSpacingValue,
        shouldShowField: shouldShowField,
        ICONS: ICONS
    };

})(window);

