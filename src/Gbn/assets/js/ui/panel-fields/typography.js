;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };

    /**
     * Construye un campo de tipografía compuesto (font, size, lineHeight, spacing, transform)
     */
    function buildTypographyField(block, field) {
        var u = utils();
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field gbn-field-typography';
        
        // Header
        var header = document.createElement('div');
        header.className = 'gbn-field-header';
        
        var label = document.createElement('span');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || 'Typography';
        
        header.appendChild(label);
        wrapper.appendChild(header);

        var baseId = field.id;

        // Helper para obtener valor responsive y source
        function getResponsiveData(subId) {
            var path = baseId + '.' + subId;
            var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
            var val = u.getResponsiveConfigValue(block, path, breakpoint);
            var source = u.getValueSource(block, path, breakpoint);
            return { val: val, source: source };
        }

        // Helper para aplicar clases visuales (Simplificado/Desactivado para este componente por petición)
        function applySourceClasses(element, source) {
            // User requested to remove gbn-field-inherited
            element.classList.remove('gbn-field-inherited', 'gbn-field-override', 'gbn-source-theme', 'gbn-source-tablet', 'gbn-source-block');
            // if (source === 'override') {
            //     element.classList.add('gbn-field-override');
            // } else {
            //     element.classList.add('gbn-field-inherited');
            //     if (source === 'theme') element.classList.add('gbn-source-theme');
            //     else if (source === 'tablet') element.classList.add('gbn-source-tablet');
            //     else if (source === 'block') element.classList.add('gbn-source-block');
            // }
        }

        /**
         * Helper: Lee valor computado del DOM
         * Sincronización bidireccional: CSS -> Panel (REGLA DE ORO de reglas.md)
         */
        function getComputedTypographyValue(property) {
            if (!block || !block.element) return null;
            try {
                var computed = window.getComputedStyle(block.element);
                return computed[property] || null;
            } catch (e) {
                return null;
            }
        }

        /**
         * Parsea font-family del CSS para mostrar valor limpio
         */
        function parseFontFamily(cssValue) {
            if (!cssValue) return 'Default';
            var fonts = cssValue.split(',');
            if (fonts.length > 0) {
                var firstFont = fonts[0].trim().replace(/['"]/g, '');
                if (firstFont === 'system-ui' || firstFont === '-apple-system' || firstFont === 'BlinkMacSystemFont') {
                    return 'System';
                }
                return firstFont || 'Default';
            }
            return 'Default';
        }

        /**
         * Parsea font-size del CSS (ej: "16px" -> "16")
         */
        function parseFontSize(cssValue) {
            if (!cssValue) return '';
            var match = /(\d+(?:\.\d+)?)(px|rem|em|%)?/i.exec(cssValue);
            if (match) return match[1];
            return cssValue;
        }

        // 1. Font Family (ancho completo)
        var fontRow = document.createElement('div');
        fontRow.style.width = '100%';
        
        var fontSelect = document.createElement('select');
        fontSelect.className = 'gbn-select';
        
        // Lista base de fuentes + Genéricas CSS
        var fonts = [
            'Default', 
            'System', 
            'Inter', 
            'Roboto', 
            'Open Sans', 
            'Lato', 
            'Montserrat',
            'Orbitron', // Agregada por contexto del usuario
            'monospace',
            'serif',
            'sans-serif',
            'cursive',
            'fantasy'
        ];

        // Función para renderizar opciones
        function renderFontOptions(selectedVal) {
            fontSelect.innerHTML = '';
            var found = false;
            
            fonts.forEach(function(f) {
                var opt = document.createElement('option');
                opt.value = f;
                // Capitalizar nombres genéricos para mejor UX
                if (['monospace', 'serif', 'sans-serif', 'cursive', 'fantasy'].indexOf(f) !== -1) {
                    opt.textContent = f.charAt(0).toUpperCase() + f.slice(1) + ' (Genérica)';
                } else {
                    opt.textContent = f;
                }
                fontSelect.appendChild(opt);
                
                if (f.toLowerCase() === String(selectedVal).toLowerCase()) {
                    found = true;
                }
            });
            
            // Si el valor seleccionado no está en la lista, agregar opción "Personalizada"
            if (!found && selectedVal && selectedVal !== 'Default') {
                var customOpt = document.createElement('option');
                customOpt.value = selectedVal;
                customOpt.textContent = 'Personalizada: ' + selectedVal;
                fontSelect.appendChild(customOpt);
            }
        }
        
        var fontData = getResponsiveData('font');
        var currentFontVal = 'Default';

        // Si hay valor en config, usarlo. Si no, leer del CSS computado.
        if (fontData.val) {
            currentFontVal = fontData.val;
        } else {
            var computedFont = getComputedTypographyValue('fontFamily');
            var parsedFont = parseFontFamily(computedFont);
            currentFontVal = parsedFont;
        }
        
        renderFontOptions(currentFontVal);
        fontSelect.value = currentFontVal;
        // applySourceClasses(fontRow, fontData.source); // Disabled
        
        fontSelect.addEventListener('change', function() {
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, baseId + '.font', fontSelect.value);
                // applySourceClasses(fontRow, 'override');
            }
        });
        fontRow.appendChild(fontSelect);
        wrapper.appendChild(fontRow);

        // 2. Size, Line Height, Letter Spacing (3 columnas)
        var gridRow = document.createElement('div');
        gridRow.className = 'gbn-typo-grid';
        
        function createInput(subId, placeholder, iconSvg, labelTitle, cssProperty) {
            var col = document.createElement('div');
            col.className = 'gbn-input-icon-wrapper';
            
            var iconContainer = document.createElement('div');
            iconContainer.className = 'gbn-input-icon';
            iconContainer.innerHTML = iconSvg;
            iconContainer.title = labelTitle;
            
            var inp = document.createElement('input');
            inp.type = 'text';
            inp.className = 'gbn-input gbn-input-with-icon';
            inp.placeholder = placeholder;
            
            var data = getResponsiveData(subId);
            // Si hay valor en config, usarlo
            if (data.val) {
                inp.value = data.val;
            } else if (cssProperty) {
                // Si no hay valor guardado, leer del CSS computado como placeholder
                var computedVal = getComputedTypographyValue(cssProperty);
                if (computedVal) {
                    if (subId === 'size') {
                        inp.placeholder = parseFontSize(computedVal);
                    } else if (subId === 'lineHeight') {
                        inp.placeholder = computedVal;
                    } else if (subId === 'letterSpacing') {
                        inp.placeholder = computedVal === 'normal' ? '0' : computedVal;
                    }
                }
            }
            
            inp.addEventListener('input', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    var v = inp.value.trim();
                    if (v !== '' && !isNaN(parseFloat(v)) && isFinite(v)) {
                        v += 'px';
                    }
                    api.updateConfigValue(block, baseId + '.' + subId, v === '' ? null : v);
                }
            });
            
            col.appendChild(iconContainer);
            col.appendChild(inp);
            return col;
        }

        // Icons
        var iconSize = '<svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>';
        // Improved Line Height Icon (Vertical arrows with lines)
        var iconLineHeight = '<svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none"><path d="M3 6h18M3 12h18M3 18h18M12 6v12M9 9l3-3 3 3M9 15l3 3 3-3"/></svg>'; 
        var iconSpacing = '<svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none"><path d="M4 12h16m-3-3l3 3-3 3M7 9l-3 3 3 3"/></svg>';

        // Pasar la propiedad CSS para leer valores computados
        gridRow.appendChild(createInput('size', '16', iconSize, 'Size', 'fontSize'));
        gridRow.appendChild(createInput('lineHeight', '1.5', iconLineHeight, 'Line Height', 'lineHeight'));
        gridRow.appendChild(createInput('letterSpacing', '0', iconSpacing, 'Letter Spacing', 'letterSpacing'));
        wrapper.appendChild(gridRow);

        // 3. Text Transform (grupo de íconos)
        var transformRow = document.createElement('div');
        transformRow.style.width = '100%'; // Replaces gbn-typo-row
        // Removed gbn-typo-row
        
        // Removed Label as requested
        
        var transformGroup = document.createElement('div');
        transformGroup.className = 'gbn-icon-group-container';
        transformGroup.style.width = '100%'; // Ensure full width
        transformGroup.style.justifyContent = 'space-between'; // Distribute evenly
        
        var transforms = [
            { val: 'none', label: 'None', icon: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' }, // Changed to X/Cancel icon for None
            { val: 'uppercase', label: 'Uppercase', icon: 'AB' },
            { val: 'lowercase', label: 'Lowercase', icon: 'ab' },
            { val: 'capitalize', label: 'Capitalize', icon: 'Ab' }
        ];
        
        var transformData = getResponsiveData('transform');
        var currentTransform = transformData.val;
        // Si no hay valor guardado, leer del CSS computado
        if (!currentTransform) {
            var computedTransform = getComputedTypographyValue('textTransform');
            if (computedTransform && computedTransform !== 'none') {
                currentTransform = computedTransform;
            }
        }
        
        transforms.forEach(function(opt) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gbn-icon-btn' + (currentTransform === opt.val ? ' active' : '');
            btn.title = opt.label;
            btn.innerHTML = opt.icon;
            btn.style.fontSize = '11px';
            btn.style.fontWeight = '600';
            
            btn.addEventListener('click', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    api.updateConfigValue(block, baseId + '.transform', opt.val);
                    Array.from(transformGroup.children).forEach(function(b) {
                        b.classList.remove('active');
                    });
                    btn.classList.add('active');
                    // applySourceClasses(transformRow, 'override');
                }
            });
            transformGroup.appendChild(btn);
        });
        
        transformRow.appendChild(transformGroup);
        wrapper.appendChild(transformRow);

        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.typographyField = { build: buildTypographyField };

})(window);



