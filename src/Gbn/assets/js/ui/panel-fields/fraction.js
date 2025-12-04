;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    /**
     * Construye un selector de fracciones de ancho (1/2, 1/3, etc.)
     */
    /**
     * Construye un selector de fracciones de ancho (1/2, 1/3, etc.)
     * Ahora con input manual para valores arbitrarios (Smart Dimension Control).
     */
    function buildFractionSelectorField(block, field) {
        var u = utils();
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field gbn-field-fraction';
        
        // Header con Label
        var header = document.createElement('div');
        header.className = 'gbn-field-header';
        
        var label = document.createElement('label');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || field.id;
        header.appendChild(label);
        wrapper.appendChild(header);
        
        // Input Manual
        var inputWrapper = document.createElement('div');
        inputWrapper.className = 'gbn-fraction-input-wrapper';
        
        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'gbn-input-text gbn-fraction-manual-input';
        input.placeholder = 'Ej: 100%, 500px, auto';
        
        inputWrapper.appendChild(input);
        wrapper.appendChild(inputWrapper);
        
        // Botones de Presets
        var container = document.createElement('div');
        container.className = 'gbn-fraction-container';
        
        var fractions = [
            { val: '1/1', label: '1/1', equivalents: ['100%'] },
            { val: '5/6', label: '5/6', equivalents: ['83.3333%', '83.33%'] },
            { val: '4/5', label: '4/5', equivalents: ['80%'] },
            { val: '3/4', label: '3/4', equivalents: ['75%'] },
            { val: '2/3', label: '2/3', equivalents: ['66.6666%', '66.66%'] },
            { val: '3/5', label: '3/5', equivalents: ['60%'] },
            { val: '1/2', label: '1/2', equivalents: ['50%'] },
            { val: '2/5', label: '2/5', equivalents: ['40%'] },
            { val: '1/3', label: '1/3', equivalents: ['33.3333%', '33.33%'] },
            { val: '1/4', label: '1/4', equivalents: ['25%'] },
            { val: '1/5', label: '1/5', equivalents: ['20%'] },
            { val: '1/6', label: '1/6', equivalents: ['16.6666%', '16.66%'] }
        ];
        
        // Lógica responsive
        var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
        var source = u.getValueSource(block, field.id, breakpoint);
        var current = u.getResponsiveConfigValue(block, field.id, breakpoint);
        
        // Inicializar valor del input
        if (current) {
            input.value = current;
        }

        // Clases visuales de herencia
        wrapper.classList.remove('gbn-field-inherited', 'gbn-field-override', 'gbn-source-theme', 'gbn-source-tablet', 'gbn-source-block');
        if (source === 'override') {
             wrapper.classList.add('gbn-field-override');
        } else {
             wrapper.classList.add('gbn-field-inherited');
             if (source === 'theme') wrapper.classList.add('gbn-source-theme');
             else if (source === 'tablet') wrapper.classList.add('gbn-source-tablet');
             else if (source === 'block') wrapper.classList.add('gbn-source-block');
        }
        
        // Función para actualizar estado activo de botones
        function updateActiveButton(val) {
            Array.from(container.children).forEach(function(btn) {
                var btnVal = btn.dataset.val;
                var frac = fractions.find(function(f) { return f.val === btnVal; });
                
                var isActive = (val === btnVal);
                // Chequear equivalentes (ej: si val es '50%', activar '1/2')
                if (!isActive && frac && frac.equivalents && frac.equivalents.indexOf(val) !== -1) {
                    isActive = true;
                }
                
                if (isActive) btn.classList.add('active');
                else btn.classList.remove('active');
            });
        }

        // Crear botones
        fractions.forEach(function(frac) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gbn-fraction-btn';
            btn.textContent = frac.label;
            btn.dataset.val = frac.val; // Guardar valor para referencia
            
            btn.addEventListener('click', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    // Al hacer click en botón, guardamos la fracción (ej: '1/2')
                    api.updateConfigValue(block, field.id, frac.val);
                    
                    // Actualizar UI localmente
                    input.value = frac.val;
                    updateActiveButton(frac.val);
                    
                    // Actualizar visualmente a override
                    wrapper.classList.remove('gbn-field-inherited', 'gbn-source-theme', 'gbn-source-tablet', 'gbn-source-block');
                    wrapper.classList.add('gbn-field-override');
                }
            });
            container.appendChild(btn);
        });
        
        // Inicializar estado activo
        updateActiveButton(current);
        
        // Evento Input Manual
        input.addEventListener('change', function(e) {
            var val = e.target.value.trim();
            var api = Gbn.ui && Gbn.ui.panelApi;
            
            if (api && api.updateConfigValue && block) {
                // Si está vacío, quizás deberíamos borrar la config?
                // Por ahora guardamos lo que escriba.
                api.updateConfigValue(block, field.id, val);
                
                updateActiveButton(val);
                
                wrapper.classList.remove('gbn-field-inherited', 'gbn-source-theme', 'gbn-source-tablet', 'gbn-source-block');
                wrapper.classList.add('gbn-field-override');
            }
        });
        
        wrapper.appendChild(container);
        u.appendFieldDescription(wrapper, field);
        
        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.fractionField = { build: buildFractionSelectorField };

})(window);



