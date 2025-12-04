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
        
        // Usar getEffectiveValue para leer config O estilo computado (clases/inline)
        var effective = u.getEffectiveValue(block, field.id);
        var current = effective.value;

        // Inicializar valor del input
        if (current !== undefined && current !== null && current !== '') {
            var displayVal = String(current);
            
            // [BUG-SYNC FIX] Si es un valor computado en px, intentar calcular el % original
            // Esto permite mostrar "30%" en lugar de "406.188px" cuando el CSS dice width: 30%
            if (effective.source === 'computed' && displayVal.indexOf('px') !== -1) {
                var pxValue = parseFloat(displayVal);
                
                if (block.element && block.element.parentElement && !isNaN(pxValue)) {
                    var parentWidth = block.element.parentElement.offsetWidth;
                    
                    if (parentWidth > 0) {
                        // Calcular el porcentaje
                        var percentValue = (pxValue / parentWidth) * 100;
                        
                        // Lista de porcentajes/fracciones comunes para hacer matching
                        var commonPercentages = [
                            { percent: 100, display: '100%' },
                            { percent: 83.3333, display: '83.33%' },
                            { percent: 80, display: '80%' },
                            { percent: 75, display: '75%' },
                            { percent: 66.6666, display: '66.66%' },
                            { percent: 60, display: '60%' },
                            { percent: 50, display: '50%' },
                            { percent: 40, display: '40%' },
                            { percent: 33.3333, display: '33.33%' },
                            { percent: 30, display: '30%' },
                            { percent: 25, display: '25%' },
                            { percent: 20, display: '20%' },
                            { percent: 16.6666, display: '16.66%' },
                            { percent: 10, display: '10%' }
                        ];
                        
                        var tolerance = 0.5; // ±0.5% de tolerancia para matching
                        var matched = false;
                        
                        for (var i = 0; i < commonPercentages.length; i++) {
                            if (Math.abs(percentValue - commonPercentages[i].percent) <= tolerance) {
                                displayVal = commonPercentages[i].display;
                                matched = true;
                                break;
                            }
                        }
                        
                        // Si no coincide con ninguno común, mostrar el % calculado con 2 decimales
                        if (!matched) {
                            // Redondear a 2 decimales para mostrar limpio
                            var roundedPercent = Math.round(percentValue * 100) / 100;
                            displayVal = roundedPercent + '%';
                        }
                    }
                }
            }
            
            input.value = displayVal;
            
            if (effective.source === 'computed') {
                wrapper.classList.add('gbn-source-computed');
                wrapper.title = 'Valor heredado de CSS/Clase (' + displayVal + ')';
            }
        }

        // Clases visuales de herencia
        wrapper.classList.remove('gbn-field-inherited', 'gbn-field-override', 'gbn-source-theme', 'gbn-source-tablet', 'gbn-source-block', 'gbn-source-computed');
        
        if (source === 'override') {
             wrapper.classList.add('gbn-field-override');
        } else {
             wrapper.classList.add('gbn-field-inherited');
             if (source === 'theme') wrapper.classList.add('gbn-source-theme');
             else if (source === 'tablet') wrapper.classList.add('gbn-source-tablet');
             else if (source === 'block') wrapper.classList.add('gbn-source-block');
             
             // Si el source efectivo fue computed, añadir la clase también
             if (effective.source === 'computed') wrapper.classList.add('gbn-source-computed');
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



