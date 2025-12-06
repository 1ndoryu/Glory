;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Módulo de estilos computados.
     * 
     * Proporciona funciones para leer valores CSS computados del DOM,
     * con manejo especial para evitar interferencias del inspector GBN.
     * 
     * IMPORTANTE: Este módulo remueve temporalmente clases y atributos
     * del inspector antes de leer getComputedStyle para evitar que
     * estilos hover/selección contaminen los valores.
     * 
     * @module computed-styles
     */

    // Clases del inspector que pueden interferir con getComputedStyle
    var INSPECTOR_CLASSES = [
        'gbn-show-controls',
        'gbn-block',
        'gbn-block-active',
        'gbn-node',
        'gbn-selected',
        'gbn-hovered'
    ];

    // Atributos del inspector que disparan estilos hover
    var INSPECTOR_ATTRIBUTES = [
        'data-gbnPrincipal',
        'data-gbnSecundario',
        'data-gbn-role'
    ];

    /**
     * Obtiene el valor computado de una propiedad CSS de un elemento.
     * 
     * Remueve temporalmente clases y atributos del inspector GBN
     * para obtener el valor "real" sin interferencias de estilos hover.
     * 
     * @param {HTMLElement} element - Elemento DOM
     * @param {string} cssProperty - Propiedad CSS (camelCase, ej: 'paddingTop')
     * @returns {string|undefined} Valor computado o undefined
     * 
     * @example
     * getComputedValue(myDiv, 'paddingTop')
     * // Returns: '20px'
     */
    function getComputedValue(element, cssProperty) {
        if (!element || !cssProperty) return undefined;
        
        try {
            // Remover clases del inspector temporalmente (Bug 7 fix)
            var removedClasses = [];
            
            INSPECTOR_CLASSES.forEach(function(className) {
                if (element.classList.contains(className)) {
                    removedClasses.push(className);
                    element.classList.remove(className);
                }
            });
            
            // Remover atributos que disparan estilos hover
            var removedAttributes = {};
            
            INSPECTOR_ATTRIBUTES.forEach(function(attr) {
                if (element.hasAttribute(attr)) {
                    removedAttributes[attr] = element.getAttribute(attr);
                    element.removeAttribute(attr);
                }
            });
            
            // Forzar reflow si removimos algo
            if (removedClasses.length > 0 || Object.keys(removedAttributes).length > 0) {
                void element.offsetHeight;
            }
            
            // Obtener valor computado
            var computed = window.getComputedStyle(element);
            var value = computed[cssProperty];
            
            // Restaurar atributos
            Object.keys(removedAttributes).forEach(function(attr) {
                element.setAttribute(attr, removedAttributes[attr]);
            });
            
            // Restaurar clases
            removedClasses.forEach(function(className) {
                element.classList.add(className);
            });
            
            // Retornar undefined si es vacío
            if (value === '' || value === undefined || value === null) {
                return undefined;
            }
            
            return value;
        } catch (e) {
            return undefined;
        }
    }

    /**
     * Obtiene el valor computado para una ruta de configuración.
     * Usa CONFIG_TO_CSS_MAP para traducir la ruta a propiedad CSS.
     * 
     * @param {HTMLElement} element - Elemento DOM
     * @param {string} configPath - Ruta de configuración (ej: 'padding.superior')
     * @returns {string|undefined} Valor computado o undefined
     * 
     * @example
     * getComputedValueForPath(myDiv, 'padding.superior')
     * // Returns: '20px'
     */
    function getComputedValueForPath(element, configPath) {
        if (!element || !configPath) return undefined;
        
        var CONFIG_TO_CSS_MAP = Gbn.ui.fieldUtils.CONFIG_TO_CSS_MAP;
        if (!CONFIG_TO_CSS_MAP) {
            console.warn('[GBN] computed-styles: CONFIG_TO_CSS_MAP no disponible');
            return undefined;
        }
        
        var cssProperty = CONFIG_TO_CSS_MAP[configPath];
        if (!cssProperty) return undefined;
        
        return getComputedValue(element, cssProperty);
    }

    /**
     * Obtiene múltiples valores computados de un elemento.
     * 
     * @param {HTMLElement} element - Elemento DOM
     * @param {Array<string>} configPaths - Array de rutas de configuración
     * @returns {Object} Objeto con los valores computados
     */
    function getMultipleComputedValues(element, configPaths) {
        var result = {};
        
        if (!element || !configPaths || !Array.isArray(configPaths)) {
            return result;
        }
        
        configPaths.forEach(function(path) {
            var value = getComputedValueForPath(element, path);
            if (value !== undefined) {
                result[path] = value;
            }
        });
        
        return result;
    }

    // Exportar funciones
    Gbn.ui.fieldUtils.getComputedValue = getComputedValue;
    Gbn.ui.fieldUtils.getComputedValueForPath = getComputedValueForPath;
    Gbn.ui.fieldUtils.getMultipleComputedValues = getMultipleComputedValues;
    
    // Exportar constantes para extensibilidad
    Gbn.ui.fieldUtils.INSPECTOR_CLASSES = INSPECTOR_CLASSES;
    Gbn.ui.fieldUtils.INSPECTOR_ATTRIBUTES = INSPECTOR_ATTRIBUTES;

})(window);
