;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    /**
     * Construye un campo de texto simple
     */
    function buildTextField(block, field) {
        var u = utils();
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field';
        
        var label = document.createElement('label');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || field.id;
        wrapper.appendChild(label);
        
        var input;
        if (field.tipo === 'textarea') {
            input = document.createElement('textarea');
            input.className = 'gbn-input gbn-textarea';
            input.style.minHeight = '100px';
            input.style.fontFamily = 'monospace';
            input.style.fontSize = '12px';
        } else {
            input = document.createElement('input');
            input.type = 'text';
            input.className = 'gbn-input';
        }
        
        var current = u.getConfigValue(block, field.id);
        var themeDefault = u.getThemeDefault(block.role, field.id);
        
        // Lógica de lectura de estilos computados (Computed Styles)
        // Permite mostrar el valor real del CSS (clases, inline) cuando no hay config guardada
        var computedVal = null;

        if (current === undefined || current === null) {
            // Intentar leer del DOM
            var effective = u.getEffectiveValue(block, field.id);
            if (effective.source === 'computed' && effective.value) {
                computedVal = effective.value;
                
                // Para valores numéricos que son 0px, a veces es mejor ignorarlos si el default es diferente,
                // pero por consistencia mostramos lo que el navegador dice.
                // Excepción: si es '0px' y queremos placeholder vacío, podemos filtrar aquí.
                if ((field.id === 'borderWidth' || field.id === 'borderRadius') && parseFloat(computedVal) === 0) {
                    computedVal = null; 
                }
            }
        }
        
        if (current === undefined || current === null) {
            wrapper.classList.add('gbn-field-inherited');
            if (computedVal !== null) {
                // Mostrar valor computado del CSS
                input.value = computedVal;
                input.placeholder = computedVal; // Feedback visual
                // Marcar visualmente que viene del CSS/Clase
                wrapper.classList.add('gbn-source-computed'); 
                wrapper.title = 'Valor heredado de CSS/Clase';
            } else if (themeDefault !== undefined && themeDefault !== null) {
                input.placeholder = themeDefault;
            } else {
                input.placeholder = field.defecto || '';
            }
        } else {
            wrapper.classList.add('gbn-field-override');
            input.value = current;
        }
        
        input.dataset.role = block.role;
        input.dataset.prop = field.id;
        
        input.addEventListener('input', function () {
            var value = input.value.trim();
            
            if (value === '') {
                wrapper.classList.add('gbn-field-inherited');
                wrapper.classList.remove('gbn-field-override');
            } else {
                wrapper.classList.remove('gbn-field-inherited');
                wrapper.classList.add('gbn-field-override');
            }
            
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, field.id, value === '' ? null : value);
            }
        });
        
        wrapper.appendChild(input);
        u.appendFieldDescription(wrapper, field);
        
        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.textField = { build: buildTextField };

    if (Gbn.ui.panelFields && Gbn.ui.panelFields.registry) {
        Gbn.ui.panelFields.registry.register('text', buildTextField);
    }

})(window);

