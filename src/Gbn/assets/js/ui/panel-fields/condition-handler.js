;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fieldUtils = Gbn.ui.fieldUtils || {};

    /**
     * Módulo de manejo de condiciones.
     * 
     * Evalúa condiciones de campos para determinar si deben mostrarse.
     * Soporta múltiples formatos de condición para compatibilidad.
     * 
     * Formatos soportados:
     * - [key, value] → Equivale a [key, '==', value]
     * - [key, operator, value] → Formato estándar
     * 
     * Operadores soportados:
     * - '==' : Igualdad
     * - '!=' : Diferencia
     * - 'in' : Valor está en array
     * - '!in' : Valor NO está en array
     * - '===' : Igualdad estricta (legacy, tratado como '==')
     * 
     * @module condition-handler
     */

    /**
     * Evalúa si un campo debe mostrarse basado en su condición.
     * 
     * Usa getEffectiveValue para incluir valores computados del DOM.
     * Para Theme Settings con campos de componentes, la condición
     * se evalúa relativa al componente.
     * 
     * @param {Object} block - Bloque actual
     * @param {Object} field - Definición del campo
     * @returns {boolean} true si el campo debe mostrarse
     * 
     * @example
     * // Campo con condición
     * var field = { id: 'flexDirection', condicion: ['layout', '==', 'flex'] };
     * shouldShowField(myBlock, field)
     * // Returns: true si layout es 'flex'
     */
    function shouldShowField(block, field) {
        if (!field || !field.condicion || !Array.isArray(field.condicion)) {
            return true;
        }
        
        var cond = field.condicion;
        var key, operator, value;

        // Parsear formato de condición
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
        
        // Normalizar operador '===' a '==' (legacy MenuComponent)
        if (operator === '===') {
            operator = '==';
        }

        var current;
        var getDeepValue = Gbn.ui.fieldUtils.getDeepValue;
        var getEffectiveValue = Gbn.ui.fieldUtils.getEffectiveValue;
        
        // Para Theme Settings con campos de componentes
        // Si field.id es 'components.{role}.{prop}', buscar condición en 'components.{role}.{key}'
        if (field.id && field.id.indexOf('components.') === 0) {
            var parts = field.id.split('.');
            if (parts.length >= 3) {
                var componentPath = parts.slice(0, 2).join('.') + '.' + key;
                current = getDeepValue(block.config, componentPath);
            }
        }
        
        // Si no encontramos valor con la lógica de componentes, usar getEffectiveValue
        if (current === undefined || current === null) {
            var effective = getEffectiveValue(block, key);
            current = effective.value;
            
            // Para 'layout', mapear valores desde computedStyle 'display'
            if (key === 'layout' && effective.source === 'computed') {
                if (current === 'flex') current = 'flex';
                else if (current === 'grid') current = 'grid';
                else if (current === 'block' || current === 'block flow') current = 'block';
            }
        }

        // Evaluar condición según operador
        switch (operator) {
            case '==':
                return current === value;
            case '!=':
                return current !== value;
            case 'in':
                return Array.isArray(value) && value.indexOf(current) !== -1;
            case '!in':
                return Array.isArray(value) && value.indexOf(current) === -1;
            case '>':
                return parseFloat(current) > parseFloat(value);
            case '<':
                return parseFloat(current) < parseFloat(value);
            case '>=':
                return parseFloat(current) >= parseFloat(value);
            case '<=':
                return parseFloat(current) <= parseFloat(value);
            default:
                return true;
        }
    }

    /**
     * Evalúa múltiples condiciones (AND lógico).
     * 
     * @param {Object} block - Bloque actual
     * @param {Array} conditions - Array de condiciones
     * @returns {boolean} true si TODAS las condiciones se cumplen
     */
    function shouldShowFieldMultiple(block, conditions) {
        if (!conditions || !Array.isArray(conditions) || conditions.length === 0) {
            return true;
        }
        
        return conditions.every(function(cond) {
            return shouldShowField(block, { condicion: cond });
        });
    }

    // Exportar funciones
    Gbn.ui.fieldUtils.shouldShowField = shouldShowField;
    Gbn.ui.fieldUtils.shouldShowFieldMultiple = shouldShowFieldMultiple;

})(window);
