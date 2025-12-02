;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    /**
     * Construye un selector de fracciones de ancho (1/2, 1/3, etc.)
     */
    function buildFractionSelectorField(block, field) {
        var u = utils();
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field gbn-field-fraction';
        
        var label = document.createElement('label');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || field.id;
        wrapper.appendChild(label);
        
        var container = document.createElement('div');
        container.className = 'gbn-fraction-container';
        
        var fractions = [
            { val: '1/1', label: '1/1' },
            { val: '5/6', label: '5/6' },
            { val: '4/5', label: '4/5' },
            { val: '3/4', label: '3/4' },
            { val: '2/3', label: '2/3' },
            { val: '3/5', label: '3/5' },
            { val: '1/2', label: '1/2' },
            { val: '2/5', label: '2/5' },
            { val: '1/3', label: '1/3' },
            { val: '1/4', label: '1/4' },
            { val: '1/5', label: '1/5' },
            { val: '1/6', label: '1/6' }
        ];
        
        // Lógica responsive
        var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
        var source = u.getValueSource(block, field.id, breakpoint);
        var current = u.getResponsiveConfigValue(block, field.id, breakpoint);
        
        // Clases visuales
        wrapper.classList.remove('gbn-field-inherited', 'gbn-field-override', 'gbn-source-theme', 'gbn-source-tablet', 'gbn-source-block');
        if (source === 'override') {
             wrapper.classList.add('gbn-field-override');
        } else {
             wrapper.classList.add('gbn-field-inherited');
             if (source === 'theme') wrapper.classList.add('gbn-source-theme');
             else if (source === 'tablet') wrapper.classList.add('gbn-source-tablet');
             else if (source === 'block') wrapper.classList.add('gbn-source-block');
        }
        
        fractions.forEach(function(frac) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gbn-fraction-btn' + (current === frac.val ? ' active' : '');
            btn.textContent = frac.label;
            
            btn.addEventListener('click', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    api.updateConfigValue(block, field.id, frac.val);
                    Array.from(container.children).forEach(function(b) {
                        b.classList.remove('active');
                    });
                    btn.classList.add('active');
                    
                    // Actualizar visualmente a override
                    wrapper.classList.remove('gbn-field-inherited', 'gbn-source-theme', 'gbn-source-tablet', 'gbn-source-block');
                    wrapper.classList.add('gbn-field-override');
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
    Gbn.ui.fractionField = { build: buildFractionSelectorField };

})(window);



