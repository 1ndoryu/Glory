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
        var iconSize = getIcon('typography.size');
        // Improved Line Height Icon (Vertical arrows with lines)
        var iconLineHeight = getIcon('typography.lineHeight'); 
        var iconSpacing = getIcon('typography.letterSpacing');

        // Pasar la propiedad CSS para leer valores computados
        gridRow.appendChild(createInput('size', '16', iconSize, 'Size', 'fontSize'));
        gridRow.appendChild(createInput('lineHeight', '1.5', iconLineHeight, 'Line Height', 'lineHeight'));
        gridRow.appendChild(createInput('letterSpacing', '0', iconSpacing, 'Letter Spacing', 'letterSpacing'));
        wrapper.appendChild(gridRow);

        // 3. Font Weight (grupo de íconos)
        var weightRow = document.createElement('div');
        weightRow.style.width = '100%';
        
        var weightGroup = document.createElement('div');
        weightGroup.className = 'gbn-icon-group-container';
        weightGroup.style.width = '100%';
        weightGroup.style.justifyContent = 'space-between';
        
        // Opciones de peso de fuente con iconos visuales
        var weights = [
            { val: '400', label: 'Normal / Light', icon: '<span style="font-weight:400">Aa</span>' },
            { val: '500', label: 'Medium', icon: '<span style="font-weight:500">Aa</span>' },
            { val: '600', label: 'Semibold', icon: '<span style="font-weight:600">Aa</span>' },
            { val: '700', label: 'Bold', icon: '<span style="font-weight:700">Aa</span>' }
        ];
        
        var weightData = getResponsiveData('weight');
        var currentWeight = weightData.val;
        // Si no hay valor guardado, leer del CSS computado
        if (!currentWeight) {
            var computedWeight = getComputedTypographyValue('fontWeight');
            if (computedWeight && computedWeight !== 'normal' && computedWeight !== '400') {
                currentWeight = computedWeight;
            }
        }
        
        weights.forEach(function(opt) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gbn-icon-btn' + (currentWeight === opt.val ? ' active' : '');
            btn.title = opt.label;
            btn.innerHTML = opt.icon;
            btn.style.fontSize = '11px';
            
            btn.addEventListener('click', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    api.updateConfigValue(block, baseId + '.weight', opt.val);
                    Array.from(weightGroup.children).forEach(function(b) {
                        b.classList.remove('active');
                    });
                    btn.classList.add('active');
                }
            });
            weightGroup.appendChild(btn);
        });
        
        weightRow.appendChild(weightGroup);
        wrapper.appendChild(weightRow);

        // 4. Text Transform (grupo de íconos)
        var transformRow = document.createElement('div');
        transformRow.style.width = '100%';
        
        var transformGroup = document.createElement('div');
        transformGroup.className = 'gbn-icon-group-container';
        transformGroup.style.width = '100%';
        transformGroup.style.justifyContent = 'space-between';
        
        var transforms = [
            { val: 'none', label: 'None', icon: getIcon('typography.transform.none') },
            { val: 'uppercase', label: 'Uppercase', icon: getIcon('typography.transform.uppercase') },
            { val: 'lowercase', label: 'Lowercase', icon: getIcon('typography.transform.lowercase') },
            { val: 'capitalize', label: 'Capitalize', icon: getIcon('typography.transform.capitalize') }
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



