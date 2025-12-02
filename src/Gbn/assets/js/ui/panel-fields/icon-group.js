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
        var defaultValue = opciones.length > 0 ? opciones[0].valor : null;
        
        // Para propiedades de layout, normalizar valor computado a opciones
        if (effective.source === 'computed' && current) {
            // Mapear valores CSS a valores de opciones
            var found = false;
            opciones.forEach(function(opt) {
                if (opt.valor === current || 
                    String(opt.valor).toLowerCase() === String(current).toLowerCase()) {
                    current = opt.valor;
                    found = true;
                }
            });
            // Si no se encontró coincidencia exacta, intentar mapeo especial
            if (!found && field.id === 'layout') {
                if (current === 'flex') current = 'flex';
                else if (current === 'grid') current = 'grid';
                else current = 'block';
            }
        }
        
        // Determinar origen para indicador visual
        var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
        var source = u.getValueSource(block, field.id, breakpoint);
        
        // Limpiar clases anteriores
        wrapper.classList.remove('gbn-field-inherited', 'gbn-field-override', 'gbn-source-theme', 'gbn-source-tablet', 'gbn-source-block');
        
        if (source === 'override') {
            wrapper.classList.add('gbn-field-override');
        } else {
            wrapper.classList.add('gbn-field-inherited');
            if (source === 'theme') wrapper.classList.add('gbn-source-theme');
            else if (source === 'tablet') wrapper.classList.add('gbn-source-tablet');
            else if (source === 'block') wrapper.classList.add('gbn-source-block');
        }
        
        // Si no hay valor, usar el default (primera opción)
        if (current === undefined || current === null || current === '') {
            current = defaultValue;
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

