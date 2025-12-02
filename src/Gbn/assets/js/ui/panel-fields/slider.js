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
        
        // Usar getEffectiveValue para obtener valor correcto
        var effective = u.getEffectiveValue(block, field.id);
        var themeDefault = u.getThemeDefault(block.role, field.id);
        var displayValue;
        
        // Extraer valor numérico de valores con unidad (ej: "20px" -> 20)
        // Retorna null si el valor es "none", "auto" o no numérico
        function extractNumeric(val) {
            if (val === null || val === undefined || val === '') return null;
            if (typeof val === 'number') return val;
            // Ignorar valores CSS especiales que no son numéricos
            var strVal = String(val).toLowerCase().trim();
            if (strVal === 'none' || strVal === 'auto' || strVal === 'inherit' || strVal === 'initial') {
                return null;
            }
            var parsed = u.parseSpacingValue(val);
            var num = parseFloat(parsed.valor);
            // Verificar que sea un número válido
            return (!isNaN(num) && isFinite(num)) ? num : null;
        }
        
        var effectiveNumeric = extractNumeric(effective.value);
        var themeNumeric = extractNumeric(themeDefault);
        
        // Determinar origen para indicador visual
        var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
        var source = u.getValueSource(block, field.id, breakpoint);
        
        // Limpiar clases anteriores
        wrapper.classList.remove('gbn-field-inherited', 'gbn-field-override', 'gbn-source-theme', 'gbn-source-tablet', 'gbn-source-block');
        
        if (source === 'override') {
            wrapper.classList.add('gbn-field-override');
            input.value = effectiveNumeric;
            displayValue = effectiveNumeric + (field.unidad ? field.unidad : '');
        } else {
            wrapper.classList.add('gbn-field-inherited');
            if (source === 'theme') wrapper.classList.add('gbn-source-theme');
            else if (source === 'tablet') wrapper.classList.add('gbn-source-tablet');
            else if (source === 'block') wrapper.classList.add('gbn-source-block');
            
            if (effectiveNumeric !== null && !isNaN(effectiveNumeric)) {
                input.value = effectiveNumeric;
                displayValue = effectiveNumeric + (field.unidad ? field.unidad : '');
                // Agregar sufijo de origen
                if (source === 'theme') displayValue += ' (tema)';
                else if (source === 'tablet') displayValue += ' (tablet)';
                else if (source === 'block') displayValue += ' (desktop)';
                else displayValue += ' (auto)';
            } else {
                input.value = field.min !== undefined ? field.min : 0;
                displayValue = 'auto';
            }
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

