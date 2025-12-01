;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };
    var sync = function() { return Gbn.ui.fieldSync; };

    /**
     * Construye un campo de color con picker y paleta global
     */
    function buildColorField(block, field) {
        var u = utils();
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field gbn-field-color';
        
        var label = document.createElement('label');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || field.id;
        wrapper.appendChild(label);
        
        // Indicador de sincronización
        if (sync() && sync().addSyncIndicator) {
            sync().addSyncIndicator(wrapper, block, field.id);
        }
        
        var container = document.createElement('div');
        container.className = 'gbn-color-container';

        var inputColor = document.createElement('input');
        inputColor.type = 'color';
        inputColor.className = 'gbn-color-picker';
        
        var inputText = document.createElement('input');
        inputText.type = 'text';
        inputText.className = 'gbn-color-text gbn-input';
        inputText.placeholder = 'ej: #ff5733';
        
        function update(value) {
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, field.id, value === '' ? null : value);
            }
        }
        
        // Función para convertir rgb() a hex
        function rgbToHex(rgb) {
            if (!rgb || rgb === 'transparent' || rgb === 'rgba(0, 0, 0, 0)') return null;
            if (rgb.startsWith('#')) return rgb;
            var match = rgb.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
            if (!match) return null;
            var r = parseInt(match[1], 10);
            var g = parseInt(match[2], 10);
            var b = parseInt(match[3], 10);
            return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
        }
        
        // Usar getEffectiveValue para obtener el valor correcto
        var effective = u.getEffectiveValue(block, field.id);
        var themeDefault = u.getThemeDefault(block.role, field.id);
        
        // Si no hay valor en config ni computedStyle devolvió algo diferente,
        // intentamos leer directamente del computedStyle para colores
        if (effective.source === 'none' && block.element) {
            var computedBg = u.getComputedValue(block.element, 'backgroundColor');
            if (computedBg && computedBg !== 'transparent' && computedBg !== 'rgba(0, 0, 0, 0)') {
                var hexBg = rgbToHex(computedBg);
                var hexTheme = themeDefault ? rgbToHex(themeDefault) : null;
                // Si es diferente al theme default, mostrarlo
                if (hexBg && hexBg !== hexTheme) {
                    effective.value = hexBg;
                    effective.source = 'computed';
                }
            }
        }
        
        // Convertir valores a hex si vienen en rgb
        if (effective.value) {
            var hexValue = rgbToHex(effective.value);
            if (hexValue) effective.value = hexValue;
        }
        
        if (effective.source === 'none' || !effective.value) {
            wrapper.classList.add('gbn-field-inherited');
            if (themeDefault) {
                inputColor.value = themeDefault;
                inputText.placeholder = themeDefault;
            } else {
                inputColor.value = field.defecto || '#000000';
                inputText.placeholder = field.defecto || '#000000';
            }
        } else {
            wrapper.classList.add('gbn-field-override');
            inputColor.value = effective.value;
            inputText.value = effective.value;
        }
        
        inputColor.dataset.role = block.role;
        inputColor.dataset.prop = field.id;
        
        inputColor.addEventListener('input', function() {
            inputText.value = inputColor.value;
            update(inputColor.value);
        });
        
        inputText.addEventListener('input', function() {
            var val = inputText.value.trim();
            if (val && val.match(/^#(?:[0-9a-fA-F]{3}){1,2}$/)) {
                inputColor.value = val;
            }
            update(val);
        });
        
        // Toggle de paleta
        var toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'gbn-color-toggle';
        toggleBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>';
        toggleBtn.title = 'Mostrar/Ocultar Paleta Global';
        
        var palette = document.createElement('div');
        palette.className = 'gbn-color-palette';
        palette.style.display = 'none';
        
        toggleBtn.onclick = function() {
            palette.style.display = palette.style.display === 'none' ? 'flex' : 'none';
            toggleBtn.classList.toggle('active');
        };

        // Colores por defecto
        var defaultColors = [
            { val: '#007bff', name: 'Primary' },
            { val: '#6c757d', name: 'Secondary' },
            { val: '#28a745', name: 'Success' },
            { val: '#dc3545', name: 'Danger' },
            { val: '#ffc107', name: 'Warning' },
            { val: '#17a2b8', name: 'Info' },
            { val: '#f8f9fa', name: 'Light' },
            { val: '#343a40', name: 'Dark' },
            { val: '#ffffff', name: 'White' },
            { val: '#000000', name: 'Black' }
        ];
        
        // Colores del tema
        var themeSettings = (typeof gloryGbnCfg !== 'undefined' && gloryGbnCfg.themeSettings) 
            ? gloryGbnCfg.themeSettings 
            : (Gbn.config && Gbn.config.themeSettings ? Gbn.config.themeSettings : null);
        var themeColors = (themeSettings && themeSettings.colors) ? themeSettings.colors : null;
        
        if (themeColors) {
            var mapped = [];
            
            Object.keys(themeColors).forEach(function(key) {
                if (key !== 'custom' && themeColors[key]) {
                    mapped.push({ 
                        val: themeColors[key], 
                        name: key.charAt(0).toUpperCase() + key.slice(1) 
                    });
                }
            });
            
            if (themeColors.custom && Array.isArray(themeColors.custom)) {
                themeColors.custom.forEach(function(c) {
                    if (c.value && c.name) {
                        mapped.push({ val: c.value, name: c.name });
                    }
                });
            }
            
            if (mapped.length) {
                defaultColors = mapped.concat(defaultColors.filter(function(d) {
                    return !mapped.some(function(m) {
                        return m.val.toLowerCase() === d.val.toLowerCase();
                    });
                }));
            }
        }

        defaultColors.forEach(function(c) {
            var swatch = document.createElement('button');
            swatch.type = 'button';
            swatch.className = 'gbn-color-swatch';
            swatch.style.backgroundColor = c.val;
            swatch.title = c.name + ' (' + c.val + ')';
            swatch.addEventListener('click', function() {
                inputColor.value = c.val;
                inputText.value = c.val;
                update(c.val);
            });
            palette.appendChild(swatch);
        });
        
        container.appendChild(inputColor);
        container.appendChild(inputText);
        
        if (!field.hidePalette) {
            container.appendChild(toggleBtn);
        }
        
        wrapper.appendChild(container);
        
        if (!field.hidePalette) {
            wrapper.appendChild(palette);
        }
        
        u.appendFieldDescription(wrapper, field);
        
        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.colorField = { build: buildColorField };

})(window);

