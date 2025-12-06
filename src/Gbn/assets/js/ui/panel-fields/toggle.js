;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    // Helper para obtener iconos de forma segura
    function getIcon(key, fallback) {
        if (global.GbnIcons && global.GbnIcons.get) {
            return global.GbnIcons.get(key);
        }
        return fallback || '';
    }

    /**
     * Construye un campo toggle (on/off) con botones de íconos
     */
    function buildToggleField(block, field) {
        var u = utils();
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field gbn-field-icon-group gbn-field-toggle-group';
        
        var label = document.createElement('label');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || field.id;
        wrapper.appendChild(label);
        
        var container = document.createElement('div');
        container.className = 'gbn-icon-group-container';
        
        // [FIX] Usar getEffectiveValue para detectar estado computado (ej: hasBorder inferido)
        var effective = u.getEffectiveValue(block, field.id);
        var current = !!effective.value;
        
        var options = [
            { 
                value: false, 
                label: 'Desactivar', 
                icon: getIcon('action.close') 
            },
            { 
                value: true, 
                label: 'Activar', 
                icon: getIcon('action.check') 
            }
        ];
        
        options.forEach(function(opt) {
            var btn = document.createElement('button');
            btn.type = 'button';
            var isActive = current === opt.value;
            btn.className = 'gbn-icon-btn' + (isActive ? ' active' : '');
            btn.title = opt.label;
            btn.innerHTML = opt.icon;
            
            btn.addEventListener('click', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    api.updateConfigValue(block, field.id, opt.value);
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
    Gbn.ui.toggleField = { build: buildToggleField };

})(window);



