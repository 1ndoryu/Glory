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

        // 1. Font Family (ancho completo)
        var fontRow = document.createElement('div');
        fontRow.className = 'gbn-typo-row';
        
        var fontSelect = document.createElement('select');
        fontSelect.className = 'gbn-select';
        var fonts = ['Default', 'System', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat'];
        fonts.forEach(function(f) {
            var opt = document.createElement('option');
            opt.value = f;
            opt.textContent = f;
            fontSelect.appendChild(opt);
        });
        
        var currentFont = u.getConfigValue(block, baseId + '.font');
        if (currentFont) fontSelect.value = currentFont;
        
        fontSelect.addEventListener('change', function() {
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, baseId + '.font', fontSelect.value);
            }
        });
        fontRow.appendChild(fontSelect);
        wrapper.appendChild(fontRow);

        // 2. Size, Line Height, Letter Spacing (3 columnas)
        var gridRow = document.createElement('div');
        gridRow.className = 'gbn-typo-grid';
        
        function createInput(subId, placeholder, labelText) {
            var col = document.createElement('div');
            col.className = 'gbn-typo-col';
            
            var lbl = document.createElement('label');
            lbl.textContent = labelText;
            
            var inp = document.createElement('input');
            inp.type = 'text';
            inp.className = 'gbn-input';
            inp.placeholder = placeholder;
            
            var val = u.getConfigValue(block, baseId + '.' + subId);
            if (val) inp.value = val;
            
            inp.addEventListener('input', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) {
                    var v = inp.value.trim();
                    // Auto-agregar px si es número puro
                    if (v !== '' && !isNaN(parseFloat(v)) && isFinite(v)) {
                        v += 'px';
                    }
                    api.updateConfigValue(block, baseId + '.' + subId, v === '' ? null : v);
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
        transformRow.className = 'gbn-typo-row';
        
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
        
        var currentTransform = u.getConfigValue(block, baseId + '.transform');
        
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


