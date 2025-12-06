;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Módulo de acceso profundo a objetos.
     * 
     * Proporciona funciones para leer y escribir valores anidados
     * usando notación de punto (ej: 'padding.superior').
     * 
     * @module deep-access
     */

    /**
     * Obtiene un valor anidado de un objeto usando notación de punto.
     * 
     * @param {Object} obj - Objeto fuente
     * @param {string} path - Ruta en notación punto (ej: 'padding.superior')
     * @returns {*} Valor encontrado o undefined
     * 
     * @example
     * getDeepValue({ padding: { superior: '20px' } }, 'padding.superior')
     * // Returns: '20px'
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
     * Establece un valor anidado en un objeto usando notación de punto.
     * Crea objetos intermedios si no existen.
     * 
     * @param {Object} obj - Objeto destino
     * @param {string} path - Ruta en notación punto
     * @param {*} value - Valor a establecer
     * @returns {Object} Objeto modificado
     * 
     * @example
     * setDeepValue({}, 'padding.superior', '20px')
     * // Returns: { padding: { superior: '20px' } }
     */
    function setDeepValue(obj, path, value) {
        if (!obj || !path) return obj;
        var segments = path.split('.');
        var current = obj;
        
        for (var i = 0; i < segments.length - 1; i += 1) {
            var segment = segments[i];
            if (current[segment] === undefined || current[segment] === null) {
                current[segment] = {};
            }
            current = current[segment];
        }
        
        current[segments[segments.length - 1]] = value;
        return obj;
    }

    /**
     * Elimina un valor anidado de un objeto usando notación de punto.
     * 
     * @param {Object} obj - Objeto a modificar
     * @param {string} path - Ruta en notación punto
     * @returns {boolean} true si se eliminó, false si no existía
     */
    function deleteDeepValue(obj, path) {
        if (!obj || !path) return false;
        var segments = path.split('.');
        var current = obj;
        
        for (var i = 0; i < segments.length - 1; i += 1) {
            if (current[segments[i]] === undefined) return false;
            current = current[segments[i]];
        }
        
        var lastSegment = segments[segments.length - 1];
        if (current.hasOwnProperty(lastSegment)) {
            delete current[lastSegment];
            return true;
        }
        return false;
    }

    /**
     * Verifica si existe un valor en una ruta anidada.
     * 
     * @param {Object} obj - Objeto a verificar
     * @param {string} path - Ruta en notación punto
     * @returns {boolean} true si existe (incluyendo null), false si undefined
     */
    function hasDeepValue(obj, path) {
        if (!obj || !path) return false;
        var value = obj;
        var segments = path.split('.');
        for (var i = 0; i < segments.length; i += 1) {
            if (value === null || value === undefined) return false;
            if (!value.hasOwnProperty(segments[i])) return false;
            value = value[segments[i]];
        }
        return true;
    }

    // Exportar funciones
    Gbn.ui.fieldUtils.getDeepValue = getDeepValue;
    Gbn.ui.fieldUtils.setDeepValue = setDeepValue;
    Gbn.ui.fieldUtils.deleteDeepValue = deleteDeepValue;
    Gbn.ui.fieldUtils.hasDeepValue = hasDeepValue;

})(window);
