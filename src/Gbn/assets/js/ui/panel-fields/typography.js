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

        // Helper para aplicar clases visuales
        function applySourceClasses(element, source) {
            element.classList.remove('gbn-field-inherited', 'gbn-field-override', 'gbn-source-theme', 'gbn-source-tablet', 'gbn-source-block');
            if (source === 'override') {
                element.classList.add('gbn-field-override');
            } else {
                element.classList.add('gbn-field-inherited');
                if (source === 'theme') element.classList.add('gbn-source-theme');
                else if (source === 'tablet') element.classList.add('gbn-source-tablet');
                else if (source === 'block') element.classList.add('gbn-source-block');
            }
        }

        // 1. Font Family (ancho completo)
        var fontRow = document.createElement('div');
        fontRow.className = 'gbn-typo-row gbn-spacing-input'; // Usar clase spacing-input para el indicador
        
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
        applySourceClasses(fontRow, fontData.source);
        
        fontSelect.addEventListener('change', function() {
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, baseId + '.font', fontSelect.value);
                // Actualizar visualmente a override
                applySourceClasses(fontRow, 'override');
            }
        });
        fontRow.appendChild(fontSelect);
        wrapper.appendChild(fontRow);

        // 2. Size, Line Height, Letter Spacing (3 columnas)
        var gridRow = document.createElement('div');
        gridRow.className = 'gbn-typo-grid';
        
        function createInput(subId, placeholder, labelText) {
            var col = document.createElement('div');
            col.className = 'gbn-typo-col gbn-spacing-input'; // Clase para indicador
            
            var lbl = document.createElement('label');
            lbl.textContent = labelText;
            
            var inp = document.createElement('input');
            inp.type = 'text';
            inp.className = 'gbn-input';
            inp.placeholder = placeholder;
            
            var data = getResponsiveData(subId);
            if (data.val) inp.value = data.val;
            applySourceClasses(col, data.source);
            
            inp.addEventListener('input', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    var v = inp.value.trim();
                    // Auto-agregar px si es número puro
                    if (v !== '' && !isNaN(parseFloat(v)) && isFinite(v)) {
                        v += 'px';
                    }
                    api.updateConfigValue(block, baseId + '.' + subId, v === '' ? null : v);
                    
                    // Actualizar visualmente
                    if (v !== '') applySourceClasses(col, 'override');
                    else applySourceClasses(col, 'inherited'); // Simplificado, idealmente recargar
                }
            });
            
            col.appendChild(lbl);
            col.appendChild(inp);
            return col;
        }

        gridRow.appendChild(createInput('size', '16px', 'Size'));
        gridRow.appendChild(createInput('lineHeight', '1.5', 'Line Height'));
        gridRow.appendChild(createInput('letterSpacing', '0px', 'Spacing'));
        wrapper.appendChild(gridRow);

        // 3. Text Transform (grupo de íconos)
        var transformRow = document.createElement('div');
        transformRow.className = 'gbn-typo-row gbn-spacing-input'; // Clase para indicador
        
        var transformLabel = document.createElement('label');
        transformLabel.className = 'gbn-field-label';
        transformLabel.textContent = 'Text Transform';
        transformRow.appendChild(transformLabel);
        
        var transformGroup = document.createElement('div');
        transformGroup.className = 'gbn-icon-group-container';
        
        var transforms = [
            { val: 'none', label: 'None', icon: '&mdash;' },
            { val: 'uppercase', label: 'Uppercase', icon: 'AB' },
            { val: 'lowercase', label: 'Lowercase', icon: 'ab' },
            { val: 'capitalize', label: 'Capitalize', icon: 'Ab' }
        ];
        
        var transformData = getResponsiveData('transform');
        var currentTransform = transformData.val;
        applySourceClasses(transformRow, transformData.source);
        
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
                    applySourceClasses(transformRow, 'override');
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


