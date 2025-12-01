;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    /**
     * Construye un campo slider/range
     */
    function buildSliderField(block, field) {
        var u = utils();
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field gbn-field-range';
        
        // Header con label y badge de valor
        var header = document.createElement('div');
        header.className = 'gbn-field-header';
        
        var label = document.createElement('span');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || field.id;
        
        var valueBadge = document.createElement('span');
        valueBadge.className = 'gbn-field-value';
        
        header.appendChild(label);
        header.appendChild(valueBadge);
        wrapper.appendChild(header);
        
        // Input range
        var input = document.createElement('input');
        input.type = 'range';
        if (field.min !== undefined) input.min = field.min;
        if (field.max !== undefined) input.max = field.max;
        input.step = field.paso || 1;
        
        var current = u.getConfigValue(block, field.id);
        var themeDefault = u.getThemeDefault(block.role, field.id);
        var displayValue = current;
        
        if (current === null || current === undefined || current === '') {
            wrapper.classList.add('gbn-field-inherited');
            if (themeDefault !== undefined && themeDefault !== null) {
                input.value = themeDefault;
                displayValue = themeDefault + (field.unidad ? field.unidad : '') + ' (auto)';
            } else {
                input.value = field.min !== undefined ? field.min : 0;
                displayValue = 'auto';
            }
        } else {
            wrapper.classList.add('gbn-field-override');
            input.value = current;
            displayValue = input.value + (field.unidad ? field.unidad : '');
        }
        
        valueBadge.textContent = displayValue;
        input.dataset.configPath = field.id;
        input.dataset.role = block.role;
        input.dataset.prop = field.id;
        
        input.addEventListener('input', function () {
            var value = input.value.trim();
            var numeric = parseFloat(value);
            
            wrapper.classList.remove('gbn-field-inherited');
            wrapper.classList.add('gbn-field-override');
            
            if (isNaN(numeric) || value === '') {
                valueBadge.textContent = 'auto';
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    api.updateConfigValue(block, field.id, null);
                }
            } else {
                valueBadge.textContent = numeric + (field.unidad ? field.unidad : '');
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    api.updateConfigValue(block, field.id, numeric);
                }
            }
        });
        
        // Doble click para resetear
        input.addEventListener('dblclick', function() {
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, field.id, null);
                wrapper.classList.remove('gbn-field-override');
                wrapper.classList.add('gbn-field-inherited');
                
                var def = u.getThemeDefault(block.role, field.id);
                if (def !== undefined && def !== null) {
                    input.value = def;
                    valueBadge.textContent = def + (field.unidad ? field.unidad : '') + ' (auto)';
                } else {
                    valueBadge.textContent = 'auto';
                }
            }
        });
        
        wrapper.appendChild(input);
        u.appendFieldDescription(wrapper, field);
        
        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.sliderField = { build: buildSliderField };

})(window);

