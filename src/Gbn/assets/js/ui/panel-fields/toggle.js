;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

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
                icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' 
            },
            { 
                value: true, 
                label: 'Activar', 
                icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>' 
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



