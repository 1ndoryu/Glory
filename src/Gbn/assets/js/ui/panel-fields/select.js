;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    /**
     * Construye un campo select/dropdown
     */
    function buildSelectField(block, field) {
        var u = utils();
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field';
        
        var label = document.createElement('label');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || field.id;
        wrapper.appendChild(label);
        
        var select = document.createElement('select');
        select.className = 'gbn-select';
        
        var opciones = Array.isArray(field.opciones) ? field.opciones : [];
        opciones.forEach(function (opt) {
            var option = document.createElement('option');
            option.value = opt.valor;
            option.textContent = opt.etiqueta || opt.valor;
            select.appendChild(option);
        });
        
        var current = u.getConfigValue(block, field.id);
        if (current !== undefined && current !== null && current !== '') {
            select.value = current;
        }
        
        select.addEventListener('change', function () {
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, field.id, select.value);
            }
        });
        
        wrapper.appendChild(select);
        u.appendFieldDescription(wrapper, field);
        
        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.selectField = { build: buildSelectField };

})(window);

