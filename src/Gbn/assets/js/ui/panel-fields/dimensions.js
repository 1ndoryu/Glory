;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };
    var sync = function() { return Gbn.ui.fieldSync; };

    /**
     * Construye un campo compuesto de dimensiones (width, height, maxWidth, maxHeight)
     * Utiliza una estructura visual similar a 'spacing', agrupando opciones relacionadas.
     * 
     * Espera que 'field' contenga:
     * - options: Array de objetos { id: 'width', label: 'Ancho', units: [...] }
     * o
     * - fields: Array de IDs de campos a mostrar si se usa un modo automático (no implementado aún)
     * 
     * Para simplificar la primera versión, vamos a asumir que recibe un array de sub-campos definidos
     * en el schema PHP bajo 'campos' o 'options'.
     */
    function buildDimensionsField(block, field) {
        var u = utils();
        var wrapper = document.createElement('fieldset');
        wrapper.className = 'gbn-field gbn-field-dimensions';
        
        var legend = document.createElement('legend');
        legend.textContent = field.etiqueta || 'Dimensiones';
        wrapper.appendChild(legend);
        
        // Indicador de sincronización (para el grupo en general, o por campo individual)
        // Por ahora, lo ponemos en el wrapper principal
        if (sync() && sync().addSyncIndicator) {
            sync().addSyncIndicator(wrapper, block, field.id); // field.id puede ser 'dimensions'
        }
        
        var grid = document.createElement('div');
        grid.className = 'gbn-spacing-grid gbn-dimensions-grid'; // Reusamos gbn-spacing-grid para layout 2x2
        
        // Obtener sub-campos definidos. Si no hay, usar defaults.
        var subFields = field.opciones || [
            { id: 'width', label: 'Ancho', icon: '↔' },
            { id: 'maxWidth', label: 'Max', icon: '⇥' },
            { id: 'height', label: 'Alto', icon: '↕' },
            { id: 'maxHeight', label: 'Max', icon: 'Bottom' } // Icono improvisado
        ];

        // Helper para crear un input individual
        function createInput(subField) {
            var subId = subField.id; // ej: 'width'
            
            // Icono
            var iconHtml = subField.icon || (subField.id === 'width' ? '↔' : '↕');
            if (subField.id === 'maxWidth' || subField.id === 'maxHeight') iconHtml = 'Max';

            var item = document.createElement('label');
            item.className = 'gbn-spacing-input'; // Reusamos estilos de spacing
            item.setAttribute('data-field', subId);

            var iconSpan = document.createElement('span');
            iconSpan.className = 'gbn-spacing-icon';
            iconSpan.title = subField.label;
            iconSpan.innerHTML = iconHtml;
            // Ajustar estilo si es texto largo como "Max"
            if (iconHtml.length > 2) {
                iconSpan.style.fontSize = '10px';
                iconSpan.style.fontWeight = 'bold';
                iconSpan.style.width = 'auto';
                iconSpan.style.paddingRight = '4px';
            }
            item.appendChild(iconSpan);

            // Input
            var input = document.createElement('input');
            input.type = 'text'; // Text para permitir %, px, auto
            input.className = 'gbn-dimension-input';
            
            // Obtener valor efectivo (config > computed > theme default)
            // Nota: Aquí subId es el path directo (ej: 'width') porque dimensions no suele anidar en 'dimensions.width'
            // sI el schema definió ids planos en el componente
            var path = subField.id; 
            
            // Determinar origen y valor inicial
            // Usamos una lógica simplificada similar a spacing.js pero adaptada a inputs de texto libre
            var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
            var source = u.getValueSource(block, path, breakpoint);
            var effective = u.getEffectiveValue(block, path);

            var displayValue = '';
            if (effective.value !== undefined && effective.value !== null) {
                displayValue = effective.value;
            } else if (effective.placeholder && effective.placeholder !== 'auto') {
                // Mostrar placeholder del tema como value si es significativo? No, como placeholder
            }
            
            input.value = displayValue;
            input.placeholder = effective.placeholder || 'auto';
            
            // Data attrs
            input.dataset.configPath = path;
            input.dataset.role = block.role;
            input.dataset.source = source;

            // Clases de origen
            // Limpiar clases
            item.classList.remove('gbn-field-inherited', 'gbn-field-override', 'gbn-source-theme', 'gbn-source-tablet', 'gbn-source-block');
            
            if (source === 'override') {
                item.classList.add('gbn-field-override');
            } else {
                item.classList.add('gbn-field-inherited');
                if (source === 'theme') item.classList.add('gbn-source-theme');
                else if (source === 'tablet') item.classList.add('gbn-source-tablet');
                else if (source === 'block') item.classList.add('gbn-source-block');
            }

            // Event Listener
            input.addEventListener('change', function(e) {
                var val = e.target.value.trim();
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    api.updateConfigValue(block, path, val === '' ? null : val);
                }
            });

            // Input visual feedback (override style on type)
            input.addEventListener('input', function() {
                if (input.value !== '') {
                    item.classList.remove('gbn-field-inherited', 'gbn-source-theme', 'gbn-source-tablet', 'gbn-source-block');
                    item.classList.add('gbn-field-override');
                }
            });

            item.appendChild(input);
            return item;
        }

        subFields.forEach(function(sub) {
            grid.appendChild(createInput(sub));
        });

        wrapper.appendChild(grid);
        u.appendFieldDescription(wrapper, field);
        
        return wrapper;
    }

    // Registrar en el sistema
    if (Gbn.ui.panelFields && Gbn.ui.panelFields.registry) {
        Gbn.ui.panelFields.registry.register('dimensions', buildDimensionsField);
    }

})( typeof window !== 'undefined' ? window : this );
