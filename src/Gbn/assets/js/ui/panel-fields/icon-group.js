;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    /**
     * Construye un grupo de botones con íconos para selección única
     */
    function buildIconGroupField(block, field) {
        var u = utils();
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field gbn-field-icon-group';
        
        var label = document.createElement('label');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || field.id;
        wrapper.appendChild(label);
        
        var container = document.createElement('div');
        container.className = 'gbn-icon-group-container';
        
        // Usar getEffectiveValue para leer valor de config, computedStyle o theme
        var effective = u.getEffectiveValue(block, field.id);
        var current = effective.value;
        var opciones = Array.isArray(field.opciones) ? field.opciones : [];
        
        // Para propiedades de layout, normalizar valor computado a opciones
        if (effective.source === 'computed' && current) {
            opciones.forEach(function(opt) {
                if (opt.valor === current || 
                    String(opt.valor).toLowerCase() === String(current).toLowerCase()) {
                    current = opt.valor;
                }
            });
        }
        
        // Indicar visualmente si es heredado o override
        if (effective.source === 'none') {
            wrapper.classList.add('gbn-field-inherited');
        } else {
            wrapper.classList.add('gbn-field-override');
        }
        
        opciones.forEach(function(opt) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gbn-icon-btn' + (current === opt.valor ? ' active' : '');
            btn.title = opt.etiqueta || opt.valor;
            btn.innerHTML = opt.icon || opt.etiqueta || opt.valor;
            
            btn.addEventListener('click', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    api.updateConfigValue(block, field.id, opt.valor);
                    Array.from(container.children).forEach(function(b) {
                        b.classList.remove('active');
                    });
                    btn.classList.add('active');
                }
            });
            container.appendChild(btn);
        });
        
        wrapper.appendChild(container);
        u.appendFieldDescription(wrapper, field);
        
        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.iconGroupField = { build: buildIconGroupField };

})(window);

