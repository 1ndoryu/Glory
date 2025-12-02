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

        // 1. Font Family (ancho completo)
        var fontRow = document.createElement('div');
        fontRow.style.width = '100%'; // Replaces gbn-typo-row
        // Removed gbn-spacing-input and gbn-typo-row
        
        var fontSelect = document.createElement('select');
        fontSelect.className = 'gbn-select';
        var fonts = ['Default', 'System', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat'];
        fonts.forEach(function(f) {
            var opt = document.createElement('option');
            opt.value = f;
            opt.textContent = f;
            fontSelect.appendChild(opt);
        });
        
        var fontData = getResponsiveData('font');
        if (fontData.val) fontSelect.value = fontData.val;
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
        
        function createInput(subId, placeholder, iconSvg, labelTitle) {
            var col = document.createElement('div');
            col.className = 'gbn-input-icon-wrapper'; // New wrapper class
            
            var iconContainer = document.createElement('div');
            iconContainer.className = 'gbn-input-icon';
            iconContainer.innerHTML = iconSvg;
            iconContainer.title = labelTitle;
            
            var inp = document.createElement('input');
            inp.type = 'text';
            inp.className = 'gbn-input gbn-input-with-icon'; // Added class for padding
            inp.placeholder = placeholder;
            
            var data = getResponsiveData(subId);
            if (data.val) inp.value = data.val;
            
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

        gridRow.appendChild(createInput('size', '16', iconSize, 'Size'));
        gridRow.appendChild(createInput('lineHeight', '1.5', iconLineHeight, 'Line Height'));
        gridRow.appendChild(createInput('letterSpacing', '0', iconSpacing, 'Letter Spacing'));
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
        // applySourceClasses(transformRow, transformData.source); // Disabled
        
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



