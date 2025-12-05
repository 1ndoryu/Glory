;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };
    var sync = function() { return Gbn.ui.fieldSync; };

    /**
     * Construye un campo compuesto de dimensiones (width, height, maxWidth, maxHeight)
     * Utiliza una estructura visual mejorada con iconos SVG, clonando el estilo de 'spacing'.
     */
    function buildDimensionsField(block, field) {
        var u = utils();
        var wrapper = document.createElement('fieldset'); // Regresar a fieldset para usar estilos GBN nativos
        wrapper.className = 'gbn-field gbn-field-dimensions';
        
        var legend = document.createElement('legend');
        legend.textContent = field.etiqueta || 'Dimensiones';
        wrapper.appendChild(legend);
        
        // Indicador de sincronización
        if (sync() && sync().addSyncIndicator) {
            sync().addSyncIndicator(wrapper, block, field.id);
        }
        
        var grid = document.createElement('div');
        grid.className = 'gbn-spacing-grid'; // Clase global de forms.css
        // Asegurar grid layout explícito
        grid.style.display = 'grid';
        grid.style.gridTemplateColumns = '1fr 1fr';
        grid.style.gap = '6px';
        
        // Definición de iconos SVG
        var ICONS = {
            width: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16m-3-3l3 3-3 3M7 9l-3 3 3 3"/></svg>',
            height: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m-3-3l3 3 3-3M9 7l3-3 3 3"/></svg>',
            maxWidth: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h20M2 7v10M22 7v10"/></svg>',
            maxHeight: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M7 2h10M7 22h10"/></svg>'
        };

        // Estructura fija para asegurar UI consistente. Ignoramos labels raros de PHP.
        // Mapeamos ID de propiedad -> Label/Icono esperado.
        var structure = [
            { id: 'width', label: 'Ancho', icon: ICONS.width },
            { id: 'maxWidth', label: 'Max Ancho', icon: ICONS.maxWidth },
            { id: 'height', label: 'Alto', icon: ICONS.height },
            { id: 'maxHeight', label: 'Max Alto', icon: ICONS.maxHeight }
        ];

        // Configuración de subcampos
        // Si PHP manda "field.opciones", intentamos ver si hay override de labels, pero mantenemos nuestra estructura
        var phpOptions = field.opciones || [];
        
        // Helper interno para crear input
        function createInput(struct, index) {
            var subId = struct.id;
            var labelText = struct.label;
            
            // Si PHP mandó override de label para este ID, úsalo
            if (Array.isArray(phpOptions)) {
                var phpOption = phpOptions.find(function(o) { return o.id === subId || o === subId; });
                if (phpOption && phpOption.label) labelText = phpOption.label;
            }

            var item = document.createElement('label');
            item.className = 'gbn-spacing-input'; // Clase global forms.css
            item.setAttribute('data-field', subId);
            item.title = labelText; // Tooltip

            var iconSpan = document.createElement('span');
            iconSpan.className = 'gbn-spacing-icon'; // Clase global forms.css
            iconSpan.innerHTML = struct.icon;
            
            item.appendChild(iconSpan);

            // Input
            var input = document.createElement('input');
            input.type = 'text'; 
            input.className = 'gbn-dimension-input';
            
            // Estilos inline minimalistas para asegurar override
            input.style.width = '100%';
            input.style.border = 'none';
            input.style.background = 'transparent';
            input.style.textAlign = 'center';
            input.style.padding = '0';
            
            // DATA Binding
            // Asumimos que dimensions guarda valores planos en config raíz (config.width, config.height)
            // porque ImageComponent no anida en config.dimensions.
            var path = subId; 
            
            var u = utils();
            var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
            var source = u.getValueSource(block, path, breakpoint);
            var effective = u.getEffectiveValue(block, path);

            var displayValue = '';
            // Mostrar valor efectivo
            if (effective.value !== undefined && effective.value !== null) {
                displayValue = effective.value;
            }
            
            input.value = displayValue;
            
            // Placeholder: computado o auto
            var placeholder = effective.placeholder || 'auto';
            input.placeholder = placeholder;
            
            // Feedback Visual de Origen
            item.classList.remove('gbn-field-inherited', 'gbn-field-override', 'gbn-source-theme', 'gbn-source-computed');
            
            if (source === 'override') {
                item.classList.add('gbn-field-override');
            } else {
                item.classList.add('gbn-field-inherited');
                if (source === 'theme') item.classList.add('gbn-source-theme');
                else if (source === 'computed') item.classList.add('gbn-source-computed');
            }

            // --- EVENT HANDLING ---
            function updateValue(val) {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    var finalVal = val;
                    // Auto-append px si es numérico
                    if (val !== '' && !isNaN(val) && parseFloat(val) !== 0) {
                        finalVal += 'px';
                    }
                    api.updateConfigValue(block, path, val === '' ? null : finalVal);
                }
            }

            // Usar 'change' para commit final
            input.addEventListener('change', function(e) {
                updateValue(e.target.value.trim());
            });

            // Usar 'keydown' para Enter
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    updateValue(e.target.value.trim());
                    input.blur();
                }
            });

            // Feedback visual inmediato al escribir
            input.addEventListener('input', function() {
                if (input.value !== '') {
                    item.classList.remove('gbn-field-inherited', 'gbn-source-theme');
                    item.classList.add('gbn-field-override');
                }
            });

            item.appendChild(input);
            return item;
        }

        // Generar los 4 inputs fijos
        structure.forEach(function(s, i) {
            grid.appendChild(createInput(s, i));
        });

        wrapper.appendChild(grid);
        
        if (field.description) {
            u.appendFieldDescription(wrapper, field);
        }
        
        return wrapper;
    }

    // Registrar en el sistema
    if (Gbn.ui.panelFields && Gbn.ui.panelFields.registry) {
        Gbn.ui.panelFields.registry.register('dimensions', buildDimensionsField);
    }

})( typeof window !== 'undefined' ? window : this );
