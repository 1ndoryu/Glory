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
        
        // Usar getEffectiveValue para leer valor de config, computedStyle o theme
        var effective = u.getEffectiveValue(block, field.id);
        var current = effective.value;
        
        // Para propiedades de layout (display, flexDirection, etc), 
        // mapear valores CSS a valores de opciones
        if (effective.source === 'computed' && current) {
            // Normalizar valores CSS a los valores esperados por las opciones
            var normalized = current.toLowerCase().replace(/-/g, '');
            // Buscar coincidencia en opciones
            opciones.forEach(function(opt) {
                var optNorm = String(opt.valor).toLowerCase().replace(/-/g, '');
                if (optNorm === normalized || opt.valor === current) {
                    current = opt.valor;
                }
            });
        }
        
        // Determinar el valor por defecto (primera opción si existe)
        var defaultValue = opciones.length > 0 ? opciones[0].valor : null;
        
        // Verificar si el valor actual existe en las opciones
        var valueExists = opciones.some(function(opt) {
            return opt.valor === current;
        });

        if (current !== undefined && current !== null && current !== '' && valueExists) {
            select.value = current;
            wrapper.classList.add('gbn-field-override');
        } else {
            // Sin valor guardado o valor no coincidente: usar la primera opción como default visual
            if (defaultValue !== null) {
                select.value = defaultValue;
            }
            wrapper.classList.add('gbn-field-inherited');
        }
        
        select.addEventListener('change', function () {
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                var newValue = select.value;
                // Si se selecciona el valor default (primera opción), limpiar el config
                // para que herede del tema o CSS
                if (newValue === defaultValue && effective.source !== 'config') {
                    newValue = null;
                    wrapper.classList.remove('gbn-field-override');
                    wrapper.classList.add('gbn-field-inherited');
                } else {
                    wrapper.classList.add('gbn-field-override');
                    wrapper.classList.remove('gbn-field-inherited');
                }
                api.updateConfigValue(block, field.id, newValue);
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

