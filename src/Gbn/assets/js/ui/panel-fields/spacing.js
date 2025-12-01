;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };
    var sync = function() { return Gbn.ui.fieldSync; };

    /**
     * Construye un campo de spacing (padding/margin) con 4 direcciones
     */
    function buildSpacingField(block, field) {
        var u = utils();
        var wrapper = document.createElement('fieldset');
        wrapper.className = 'gbn-field gbn-field-spacing';
        
        var legend = document.createElement('legend');
        legend.textContent = field.etiqueta || field.id;
        wrapper.appendChild(legend);
        
        // Indicador de sincronización
        if (sync() && sync().addSyncIndicator) {
            sync().addSyncIndicator(wrapper, block, field.id);
        }
        
        var unidades = Array.isArray(field.unidades) && field.unidades.length 
            ? field.unidades 
            : ['px'];
        var campos = Array.isArray(field.campos) && field.campos.length 
            ? field.campos 
            : ['superior', 'derecha', 'inferior', 'izquierda'];
        
        var baseConfig = u.getConfigValue(block, field.id);
        if (!baseConfig && field.defecto !== undefined) {
            if (typeof field.defecto === 'object') {
                baseConfig = field.defecto;
            } else {
                baseConfig = { 
                    superior: field.defecto, 
                    derecha: field.defecto, 
                    inferior: field.defecto, 
                    izquierda: field.defecto 
                };
            }
        }
        baseConfig = baseConfig || {};
        
        // Detectar unidad actual
        var unidadActual = unidades[0];
        for (var i = 0; i < campos.length; i += 1) {
            var parsed = u.parseSpacingValue(baseConfig[campos[i]], unidades[0]);
            if (parsed.unidad) {
                unidadActual = parsed.unidad;
                break;
            }
        }
        
        // Selector de unidad
        var unitSelect = document.createElement('select');
        unitSelect.className = 'gbn-spacing-unit';
        unidades.forEach(function (opt) {
            var option = document.createElement('option');
            option.value = opt;
            option.textContent = opt;
            unitSelect.appendChild(option);
        });
        if (unidades.indexOf(unidadActual) !== -1) {
            unitSelect.value = unidadActual;
        }
        wrapper.dataset.unit = unitSelect.value;
        
        // Grid de inputs
        var grid = document.createElement('div');
        grid.className = 'gbn-spacing-grid';
        
        function handleSpacingInput(event) {
            var input = event.target;
            var value = input.value.trim();
            var unit = wrapper.dataset.unit || unitSelect.value || 'px';
            
            if (input.__gbnUnit) {
                input.__gbnUnit.textContent = unit;
            }
            
            var path = input.dataset.configPath;
            var numericVal = parseFloat(value);
            var finalValue = value === '' ? null : (isNaN(numericVal) ? null : numericVal + unit);
            
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, path, finalValue);
            }
        }
        
        campos.forEach(function (nombre) {
            var parsed = u.parseSpacingValue(baseConfig[nombre], unitSelect.value);
            var item = document.createElement('label');
            item.className = 'gbn-spacing-input';
            item.setAttribute('data-field', nombre);
            
            // Ícono
            var iconSpan = document.createElement('span');
            iconSpan.className = 'gbn-spacing-icon';
            iconSpan.title = nombre.charAt(0).toUpperCase() + nombre.slice(1);
            iconSpan.innerHTML = u.ICONS[nombre] || nombre.charAt(0);
            item.appendChild(iconSpan);
            
            // Input
            var input = document.createElement('input');
            input.type = 'number';
            if (field.min !== undefined) input.min = field.min;
            if (field.max !== undefined) input.max = field.max;
            if (field.paso !== undefined) input.step = field.paso;
            
            // Placeholder dinámico
            var themeDefault = u.getThemeDefault(block.role, field.id + '.' + nombre);
            var placeholder = '-';
            
            if (themeDefault !== undefined && themeDefault !== null) {
                var parsedTheme = u.parseSpacingValue(themeDefault, unitSelect.value);
                placeholder = parsedTheme.valor;
            }
            
            // Heredado vs override
            if (parsed.valor === '' || parsed.valor === null || parsed.valor === undefined) {
                item.classList.add('gbn-field-inherited');
            } else {
                item.classList.add('gbn-field-override');
            }
            
            input.value = parsed.valor;
            input.placeholder = placeholder;
            input.dataset.configPath = field.id + '.' + nombre;
            input.dataset.role = block.role;
            input.dataset.prop = field.id + '.' + nombre;
            
            input.addEventListener('input', handleSpacingInput);
            input.addEventListener('input', function() {
                if (input.value === '') {
                    item.classList.add('gbn-field-inherited');
                    item.classList.remove('gbn-field-override');
                } else {
                    item.classList.remove('gbn-field-inherited');
                    item.classList.add('gbn-field-override');
                }
            });
            
            item.appendChild(input);
            
            // Label de unidad
            var unitLabel = document.createElement('span');
            unitLabel.className = 'gbn-spacing-unit-label';
            unitLabel.textContent = unitSelect.value;
            input.__gbnUnit = unitLabel;
            item.appendChild(unitLabel);
            
            grid.appendChild(item);
        });
        
        // Cambio de unidad global
        unitSelect.addEventListener('change', function () {
            wrapper.dataset.unit = unitSelect.value;
            var inputs = grid.querySelectorAll('input[data-config-path]');
            inputs.forEach(function (input) {
                if (input.__gbnUnit) {
                    input.__gbnUnit.textContent = unitSelect.value;
                }
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (!api || !api.updateConfigValue || !block) return;
                if (input.value === '') {
                    api.updateConfigValue(block, input.dataset.configPath, null);
                } else {
                    api.updateConfigValue(block, input.dataset.configPath, input.value + unitSelect.value);
                }
            });
        });
        
        wrapper.appendChild(unitSelect);
        wrapper.appendChild(grid);
        u.appendFieldDescription(wrapper, field);
        
        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.spacingField = { build: buildSpacingField };

})(window);

