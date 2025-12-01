;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;

    function getDeepValue(obj, path) {
        if (!obj || !path) return undefined;
        var value = obj;
        var segments = path.split('.');
        for (var i = 0; i < segments.length; i += 1) {
            if (value === null || value === undefined) { return undefined; }
            value = value[segments[i]];
        }
        return value;
    }

    function getThemeDefault(role, path) {
        if (!role) return undefined;
        // Access global config
        var themeSettings = (typeof gloryGbnCfg !== 'undefined' && gloryGbnCfg.themeSettings) ? gloryGbnCfg.themeSettings : (Gbn.config && Gbn.config.themeSettings ? Gbn.config.themeSettings : null);
        
        if (!themeSettings || !themeSettings.components || !themeSettings.components[role]) {
            return undefined;
        }
        
        return getDeepValue(themeSettings.components[role], path);
    }

    function getConfigValue(block, path) {
        if (!block || !path) { return undefined; }
        
        // 1. Try Block Config
        var value = getDeepValue(block.config, path);
        if (value !== undefined && value !== null && value !== '') {
            return value;
        }

        // 2. Try Theme Default (if applicable)
        // Skip for Theme/Page settings editing to avoid circular logic or confusion
        if (block.role && block.role !== 'theme' && block.role !== 'page') {
            var themeVal = getThemeDefault(block.role, path);
            if (themeVal !== undefined && themeVal !== null && themeVal !== '') {
                return themeVal;
            }
        }

        return undefined;
    }

    function appendFieldDescription(container, field) {
        if (!field || !field.descripcion) { return; }
        var hint = document.createElement('p');
        hint.className = 'gbn-field-hint';
        hint.textContent = field.descripcion;
        container.appendChild(hint);
    }

    function parseSpacingValue(raw, fallbackUnit) {
        if (raw === null || raw === undefined || raw === '') { return { valor: '', unidad: fallbackUnit || 'px' }; }
        if (typeof raw === 'number') { return { valor: String(raw), unidad: fallbackUnit || 'px' }; }
        var match = /^(-?\d+(?:\.\d+)?)([a-z%]*)$/i.exec(String(raw).trim());
        if (!match) { return { valor: String(raw), unidad: fallbackUnit || 'px' }; }
        return { valor: match[1], unidad: match[2] || fallbackUnit || 'px' };
    }

    var ICONS = {
        superior: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke-opacity="0.3"></rect><path d="M4 6h16"></path></svg>',
        derecha: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke-opacity="0.3"></rect><path d="M18 4v16"></path></svg>',
        inferior: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke-opacity="0.3"></rect><path d="M4 18h16"></path></svg>',
        izquierda: '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2" stroke-opacity="0.3"></rect><path d="M6 4v16"></path></svg>'
    };

    function buildSpacingField(block, field) {
        var wrapper = document.createElement('fieldset');
        wrapper.className = 'gbn-field gbn-field-spacing';
        var legend = document.createElement('legend'); legend.textContent = field.etiqueta || field.id; wrapper.appendChild(legend);
        
        // Agregar indicador de sincronización para Panel de Tema
        addSyncIndicator(wrapper, block, field.id);
        
        var unidades = Array.isArray(field.unidades) && field.unidades.length ? field.unidades : ['px'];
        var campos = Array.isArray(field.campos) && field.campos.length ? field.campos : ['superior', 'derecha', 'inferior', 'izquierda'];
        var baseConfig = getConfigValue(block, field.id);
        if (!baseConfig && field.defecto !== undefined) {
            if (typeof field.defecto === 'object') baseConfig = field.defecto;
            else {
                baseConfig = { superior: field.defecto, derecha: field.defecto, inferior: field.defecto, izquierda: field.defecto };
            }
        }
        baseConfig = baseConfig || {};
        var unidadActual = unidades[0];
        for (var i = 0; i < campos.length; i += 1) { var parsed = parseSpacingValue(baseConfig[campos[i]], unidades[0]); if (parsed.unidad) { unidadActual = parsed.unidad; break; } }
        var unitSelect = document.createElement('select'); unitSelect.className = 'gbn-spacing-unit';
        unidades.forEach(function (opt) { var option = document.createElement('option'); option.value = opt; option.textContent = opt; unitSelect.appendChild(option); });
        if (unidades.indexOf(unidadActual) !== -1) { unitSelect.value = unidadActual; }
        wrapper.dataset.unit = unitSelect.value;
        var grid = document.createElement('div'); grid.className = 'gbn-spacing-grid';
        function handleSpacingInput(event) {
            var input = event.target; var value = input.value.trim(); var unit = wrapper.dataset.unit || unitSelect.value || 'px';
            if (input.__gbnUnit) { input.__gbnUnit.textContent = unit; }
            var path = input.dataset.configPath;
            // Strip any existing unit from value if user typed it
            var numericVal = parseFloat(value);
            var finalValue = value === '' ? null : (isNaN(numericVal) ? null : numericVal + unit);
            var api = Gbn.ui && Gbn.ui.panelApi; 
            if (api && api.updateConfigValue && block) { api.updateConfigValue(block, path, finalValue); }
        }
        campos.forEach(function (nombre) {
            var parsed = parseSpacingValue(baseConfig[nombre], unitSelect.value);
            var item = document.createElement('label'); 
            item.className = 'gbn-spacing-input'; 
            item.setAttribute('data-field', nombre);
            
            // Icono en lugar de texto
            var iconSpan = document.createElement('span');
            iconSpan.className = 'gbn-spacing-icon';
            iconSpan.title = nombre.charAt(0).toUpperCase() + nombre.slice(1);
            iconSpan.innerHTML = ICONS[nombre] || nombre.charAt(0);
            item.appendChild(iconSpan);

            var input = document.createElement('input'); input.type = 'number';
            if (field.min !== undefined) { input.min = field.min; }
            if (field.max !== undefined) { input.max = field.max; }
            if (field.paso !== undefined) { input.step = field.paso; }
            
            // Lógica de Placeholder Dinámico y Herencia
            var themeDefault = getThemeDefault(block.role, field.id + '.' + nombre);
            var placeholder = '-';
            var isInherited = false;
            
            if (themeDefault !== undefined && themeDefault !== null) {
                 var parsedTheme = parseSpacingValue(themeDefault, unitSelect.value);
                 placeholder = parsedTheme.valor;
            }
            
            // Determinar si el valor es heredado (campo vacío) o directo
            if (parsed.valor === '' || parsed.valor === null || parsed.valor === undefined) {
                isInherited = true;
                item.classList.add('gbn-field-inherited');
            } else {
                item.classList.add('gbn-field-override');
            }
            
            input.value = parsed.valor; 
            input.placeholder = placeholder; 
            input.dataset.configPath = field.id + '.' + nombre; 
            
            // Guardar referencia para actualizaciones en tiempo real
            input.dataset.role = block.role;
            input.dataset.prop = field.id + '.' + nombre;
            
            input.addEventListener('input', handleSpacingInput);
            
            // Actualizar estado visual al cambiar
            input.addEventListener('input', function() {
                if (input.value === '') {
                    item.classList.add('gbn-field-inherited');
                    item.classList.remove('gbn-field-override');
                } else {
                    item.classList.remove('gbn-field-inherited');
                    item.classList.add('gbn-field-override');
                }
            });
            
            item.appendChild(input);
            var unitLabel = document.createElement('span'); unitLabel.className = 'gbn-spacing-unit-label'; unitLabel.textContent = unitSelect.value; input.__gbnUnit = unitLabel; item.appendChild(unitLabel);
            grid.appendChild(item);
        });
        unitSelect.addEventListener('change', function () {
            wrapper.dataset.unit = unitSelect.value;
            var inputs = grid.querySelectorAll('input[data-config-path]');
            inputs.forEach(function (input) {
                if (input.__gbnUnit) { input.__gbnUnit.textContent = unitSelect.value; }
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (!api || !api.updateConfigValue || !block) { return; }
                if (input.value === '') { api.updateConfigValue(block, input.dataset.configPath, null); }
                else { api.updateConfigValue(block, input.dataset.configPath, input.value + unitSelect.value); }
            });
        });
        wrapper.appendChild(unitSelect);
        wrapper.appendChild(grid);
        appendFieldDescription(wrapper, field);
        return wrapper;
    }

    function buildSliderField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field gbn-field-range';
        var header = document.createElement('div'); header.className = 'gbn-field-header';
        var label = document.createElement('span'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id;
        var valueBadge = document.createElement('span'); valueBadge.className = 'gbn-field-value'; header.appendChild(label); header.appendChild(valueBadge); wrapper.appendChild(header);
        var input = document.createElement('input'); input.type = 'range';
        if (field.min !== undefined) { input.min = field.min; }
        if (field.max !== undefined) { input.max = field.max; }
        input.step = field.paso || 1; 
        var current = getConfigValue(block, field.id);
        
        // Lógica de Placeholder/Default para Slider
        var themeDefault = getThemeDefault(block.role, field.id);
        var displayValue = current;
        var isInherited = false;
        
        if (current === null || current === undefined || current === '') { 
            isInherited = true;
            wrapper.classList.add('gbn-field-inherited');
            // Si no hay valor directo, usamos el default del tema para el slider visual
            if (themeDefault !== undefined && themeDefault !== null) {
                input.value = themeDefault;
                displayValue = themeDefault + (field.unidad ? field.unidad : '') + ' (auto)';
            } else {
                input.value = field.min !== undefined ? field.min : 0;
                displayValue = 'auto';
            }
        } else {
            wrapper.classList.add('gbn-field-override');
            input.value = current;
            displayValue = input.value + (field.unidad ? field.unidad : '');
        }
        
        valueBadge.textContent = displayValue; 
        input.dataset.configPath = field.id;
        
        // Guardar referencia para actualizaciones
        input.dataset.role = block.role;
        input.dataset.prop = field.id;
        
        input.addEventListener('input', function () {
            var value = input.value.trim();
            var numeric = parseFloat(value);
            
            wrapper.classList.remove('gbn-field-inherited');
            wrapper.classList.add('gbn-field-override');
            
            if (isNaN(numeric) || value === '') {
                // Esto es difícil de lograr con un range input, pero por si acaso
                valueBadge.textContent = 'auto';
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) { api.updateConfigValue(block, field.id, null); }
            } else {
                valueBadge.textContent = numeric + (field.unidad ? field.unidad : '');
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) { api.updateConfigValue(block, field.id, numeric); }
            }
        });
        
        // Doble click para resetear a auto/heredado
        input.addEventListener('dblclick', function() {
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) { 
                api.updateConfigValue(block, field.id, null); 
                // La UI se actualizará cuando el config cambie y se re-renderice, 
                // o podemos forzar actualización visual aquí
                wrapper.classList.remove('gbn-field-override');
                wrapper.classList.add('gbn-field-inherited');
                
                var def = getThemeDefault(block.role, field.id);
                if (def !== undefined && def !== null) {
                    input.value = def;
                    valueBadge.textContent = def + (field.unidad ? field.unidad : '') + ' (auto)';
                } else {
                    valueBadge.textContent = 'auto';
                }
            }
        });
        wrapper.appendChild(input); appendFieldDescription(wrapper, field); return wrapper;
    }

    function buildSelectField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field';
        var label = document.createElement('label'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id; wrapper.appendChild(label);
        var select = document.createElement('select'); select.className = 'gbn-select';
        var opciones = Array.isArray(field.opciones) ? field.opciones : [];
        opciones.forEach(function (opt) { var option = document.createElement('option'); option.value = opt.valor; option.textContent = opt.etiqueta || opt.valor; select.appendChild(option); });
        var current = getConfigValue(block, field.id); if (current !== undefined && current !== null && current !== '') { select.value = current; }
        select.addEventListener('change', function () {
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) { api.updateConfigValue(block, field.id, select.value); }
        });
        wrapper.appendChild(select); appendFieldDescription(wrapper, field); return wrapper;
    }

    function buildToggleField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field gbn-field-icon-group gbn-field-toggle-group';
        var label = document.createElement('label'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id; wrapper.appendChild(label);
        
        var container = document.createElement('div');
        container.className = 'gbn-icon-group-container';
        
        var current = !!getConfigValue(block, field.id);
        
        // Define options for False (Off) and True (On)
        var options = [
            { 
                value: false, 
                label: 'Desactivar', 
                icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' 
            },
            { 
                value: true, 
                label: 'Activar', 
                icon: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>' 
            }
        ];
        
        options.forEach(function(opt) {
            var btn = document.createElement('button');
            btn.type = 'button';
            // Check equality strictly or loosely? current is boolean. opt.value is boolean.
            var isActive = current === opt.value;
            btn.className = 'gbn-icon-btn' + (isActive ? ' active' : '');
            btn.title = opt.label;
            btn.innerHTML = opt.icon;
            
            btn.addEventListener('click', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) { 
                    api.updateConfigValue(block, field.id, opt.value);
                    // Update UI locally
                    Array.from(container.children).forEach(function(b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                }
            });
            container.appendChild(btn);
        });
        
        wrapper.appendChild(container);
        appendFieldDescription(wrapper, field); 
        return wrapper;
    }

    function buildTextField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field';
        var label = document.createElement('label'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id; wrapper.appendChild(label);
        var input = document.createElement('input'); input.type = 'text'; input.className = 'gbn-input';
        
        var current = getConfigValue(block, field.id);
        var themeDefault = getThemeDefault(block.role, field.id);
        
        if (current === undefined || current === null) { 
            // Es heredado
            wrapper.classList.add('gbn-field-inherited');
            if (themeDefault !== undefined && themeDefault !== null) {
                input.placeholder = themeDefault;
            } else {
                input.placeholder = field.defecto || '';
            }
        } else {
            // Es override
            wrapper.classList.add('gbn-field-override');
            input.value = current;
        }
        
        // Guardar referencia
        input.dataset.role = block.role;
        input.dataset.prop = field.id;
        
        input.addEventListener('input', function () {
            var value = input.value.trim();
            
            if (value === '') {
                wrapper.classList.add('gbn-field-inherited');
                wrapper.classList.remove('gbn-field-override');
            } else {
                wrapper.classList.remove('gbn-field-inherited');
                wrapper.classList.add('gbn-field-override');
            }
            
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) { api.updateConfigValue(block, field.id, value === '' ? null : value); }
        });
        wrapper.appendChild(input); appendFieldDescription(wrapper, field); return wrapper;
    }

    function buildColorField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field gbn-field-color';
        var label = document.createElement('label'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id; wrapper.appendChild(label);
        
        // Agregar indicador de sincronización para Panel de Tema
        addSyncIndicator(wrapper, block, field.id);
        
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
            if (api && api.updateConfigValue && block) { api.updateConfigValue(block, field.id, value === '' ? null : value); }
        }
        
        var current = getConfigValue(block, field.id);
        var themeDefault = getThemeDefault(block.role, field.id);
        
        if (current === undefined || current === null || current === '') {
            // Heredado
            wrapper.classList.add('gbn-field-inherited');
            if (themeDefault) {
                inputColor.value = themeDefault;
                inputText.placeholder = themeDefault;
            } else {
                inputColor.value = field.defecto || '#000000';
                inputText.placeholder = field.defecto || '#000000';
            }
        } else {
            // Override
            wrapper.classList.add('gbn-field-override');
            inputColor.value = current;
            inputText.value = current;
        }
        
        // Guardar referencia
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
        
        // Color Palette Toggle
        var toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'gbn-color-palette-toggle';
        toggleBtn.innerHTML = '▼';
        toggleBtn.title = 'Mostrar/Ocultar Paleta';
        
        var palette = document.createElement('div');
        palette.className = 'gbn-color-palette';
        palette.style.display = 'none';
        
        toggleBtn.onclick = function() {
            palette.style.display = palette.style.display === 'none' ? 'flex' : 'none';
            toggleBtn.classList.toggle('active');
        };

        // Default colors with names
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
        
        // Try to get theme colors if available
        var themeSettings = (typeof gloryGbnCfg !== 'undefined' && gloryGbnCfg.themeSettings) ? gloryGbnCfg.themeSettings : (Gbn.config && Gbn.config.themeSettings ? Gbn.config.themeSettings : null);
        var themeColors = (themeSettings && themeSettings.colors) ? themeSettings.colors : null;
        
        if (themeColors) {
            var mapped = [];
            
            // Standard colors
            Object.keys(themeColors).forEach(function(key) {
                if (key !== 'custom' && themeColors[key]) {
                    mapped.push({ val: themeColors[key], name: key.charAt(0).toUpperCase() + key.slice(1) });
                }
            });
            
            // Custom colors
            if (themeColors.custom && Array.isArray(themeColors.custom)) {
                themeColors.custom.forEach(function(c) {
                    if (c.value && c.name) {
                        mapped.push({ val: c.value, name: c.name });
                    }
                });
            }
            
            if (mapped.length) {
                defaultColors = mapped.concat(defaultColors.filter(function(d) {
                    return !mapped.some(function(m) { return m.val.toLowerCase() === d.val.toLowerCase(); });
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
        
        // Only show toggle if palette is not explicitly hidden via config
        if (!field.hidePalette) {
            container.appendChild(toggleBtn);
        }
        
        wrapper.appendChild(container);
        
        if (!field.hidePalette) {
            wrapper.appendChild(palette);
        }
        
        appendFieldDescription(wrapper, field); 
        return wrapper;
    }

    function buildTypographyField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field gbn-field-typography';
        
        // Header
        var header = document.createElement('div'); header.className = 'gbn-field-header';
        var label = document.createElement('span'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || 'Typography';
        header.appendChild(label); wrapper.appendChild(header);

        var baseId = field.id; // e.g., 'text.p'

        // 1. Font Family (Full Width)
        var fontRow = document.createElement('div'); fontRow.className = 'gbn-typo-row';
        var fontSelect = document.createElement('select'); fontSelect.className = 'gbn-select';
        var fonts = ['Default', 'System', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat'];
        fonts.forEach(function(f) {
            var opt = document.createElement('option'); opt.value = f; opt.textContent = f; fontSelect.appendChild(opt);
        });
        var currentFont = getConfigValue(block, baseId + '.font');
        if (currentFont) fontSelect.value = currentFont;
        fontSelect.addEventListener('change', function() {
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) { api.updateConfigValue(block, baseId + '.font', fontSelect.value); }
        });
        fontRow.appendChild(fontSelect);
        wrapper.appendChild(fontRow);

        // 2. Size, Line Height, Letter Spacing (3 Cols)
        var gridRow = document.createElement('div'); gridRow.className = 'gbn-typo-grid';
        
        function createInput(subId, placeholder, labelText) {
            var col = document.createElement('div'); col.className = 'gbn-typo-col';
            var lbl = document.createElement('label'); lbl.textContent = labelText;
            var inp = document.createElement('input'); inp.type = 'text'; inp.className = 'gbn-input'; inp.placeholder = placeholder;
            var val = getConfigValue(block, baseId + '.' + subId);
            if (val) inp.value = val;
            inp.addEventListener('input', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) { 
                    // Auto-append px for size/spacing if number
                    var v = inp.value.trim();
                    // If it's a pure number, append px. If it has unit, leave it.
                    if (v !== '' && !isNaN(parseFloat(v)) && isFinite(v)) {
                         v += 'px';
                    }
                    api.updateConfigValue(block, baseId + '.' + subId, v === '' ? null : v); 
                }
            });
            col.appendChild(lbl); col.appendChild(inp);
            return col;
        }

        gridRow.appendChild(createInput('size', '16px', 'Size'));
        gridRow.appendChild(createInput('lineHeight', '1.5', 'Line Height'));
        gridRow.appendChild(createInput('letterSpacing', '0px', 'Spacing'));
        wrapper.appendChild(gridRow);

        // 3. Text Transform (Icon Group)
        var transformRow = document.createElement('div'); transformRow.className = 'gbn-typo-row';
        var transformLabel = document.createElement('label'); transformLabel.className = 'gbn-field-label'; transformLabel.textContent = 'Text Transform';
        transformRow.appendChild(transformLabel);
        
        var transformGroup = document.createElement('div'); transformGroup.className = 'gbn-icon-group-container';
        var transforms = [
            { val: 'none', label: 'None', icon: '&mdash;' }, // Dash
            { val: 'uppercase', label: 'Uppercase', icon: 'AB' },
            { val: 'lowercase', label: 'Lowercase', icon: 'ab' },
            { val: 'capitalize', label: 'Capitalize', icon: 'Ab' }
        ];
        var currentTransform = getConfigValue(block, baseId + '.transform');
        
        transforms.forEach(function(opt) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gbn-icon-btn' + (currentTransform === opt.val ? ' active' : '');
            btn.title = opt.label;
            btn.innerHTML = opt.icon;
            btn.style.fontSize = '11px'; // Text icons need adjustment
            btn.style.fontWeight = '600';
            
            btn.addEventListener('click', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) { 
                    api.updateConfigValue(block, baseId + '.transform', opt.val);
                    Array.from(transformGroup.children).forEach(function(b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                }
            });
            transformGroup.appendChild(btn);
        });
        transformRow.appendChild(transformGroup);
        wrapper.appendChild(transformRow);

        return wrapper;
    }

    function buildIconGroupField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field gbn-field-icon-group';
        var label = document.createElement('label'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id; wrapper.appendChild(label);
        
        var container = document.createElement('div');
        container.className = 'gbn-icon-group-container';
        
        var current = getConfigValue(block, field.id);
        var opciones = Array.isArray(field.opciones) ? field.opciones : [];
        
        opciones.forEach(function(opt) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gbn-icon-btn' + (current === opt.valor ? ' active' : '');
            btn.title = opt.etiqueta || opt.valor;
            btn.innerHTML = opt.icon || opt.etiqueta || opt.valor; // Expects SVG or text
            
            btn.addEventListener('click', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) { 
                    api.updateConfigValue(block, field.id, opt.valor);
                    // Update UI locally
                    Array.from(container.children).forEach(function(b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                }
            });
            container.appendChild(btn);
        });
        
        wrapper.appendChild(container);
        appendFieldDescription(wrapper, field);
        return wrapper;
    }

    function buildFractionSelectorField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field gbn-field-fraction';
        var label = document.createElement('label'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id; wrapper.appendChild(label);
        
        var container = document.createElement('div');
        container.className = 'gbn-fraction-container';
        
        var fractions = [
            { val: '1/1', label: '1/1' },
            { val: '5/6', label: '5/6' },
            { val: '4/5', label: '4/5' },
            { val: '3/4', label: '3/4' },
            { val: '2/3', label: '2/3' },
            { val: '3/5', label: '3/5' },
            { val: '1/2', label: '1/2' },
            { val: '2/5', label: '2/5' },
            { val: '1/3', label: '1/3' },
            { val: '1/4', label: '1/4' },
            { val: '1/5', label: '1/5' },
            { val: '1/6', label: '1/6' }
        ];
        
        var current = getConfigValue(block, field.id);
        
        fractions.forEach(function(frac) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gbn-fraction-btn' + (current === frac.val ? ' active' : '');
            btn.textContent = frac.label;
            
            btn.addEventListener('click', function() {
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) { 
                    api.updateConfigValue(block, field.id, frac.val);
                    Array.from(container.children).forEach(function(b) { b.classList.remove('active'); });
                    btn.classList.add('active');
                }
            });
            container.appendChild(btn);
        });
        
        wrapper.appendChild(container);
        appendFieldDescription(wrapper, field);
        wrapper.appendChild(container);
        appendFieldDescription(wrapper, field);
        return wrapper;
    }

    function buildRichTextField(block, field) {
        var wrapper = document.createElement('div'); wrapper.className = 'gbn-field gbn-field-rich-text';
        var label = document.createElement('label'); label.className = 'gbn-field-label'; label.textContent = field.etiqueta || field.id; wrapper.appendChild(label);
        
        var container = document.createElement('div');
        container.className = 'gbn-rich-text-container';
        
        // Toolbar
        var toolbar = document.createElement('div');
        toolbar.className = 'gbn-rich-text-toolbar';
        
        var actions = [
            { cmd: 'bold', icon: '<b>B</b>', title: 'Negrita' },
            { cmd: 'italic', icon: '<i>I</i>', title: 'Cursiva' }
        ];
        
        actions.forEach(function(action) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'gbn-rich-text-btn';
            btn.innerHTML = action.icon;
            btn.title = action.title;
            btn.addEventListener('click', function() {
                document.execCommand(action.cmd, false, null);
                // Sync content after command
                var content = editor.innerHTML;
                var api = Gbn.ui && Gbn.ui.panelApi;
                if (api && api.updateConfigValue && block) { api.updateConfigValue(block, field.id, content); }
            });
            toolbar.appendChild(btn);
        });
        
        container.appendChild(toolbar);
        
        // Editor Area
        var editor = document.createElement('div');
        editor.className = 'gbn-rich-text-editor';
        editor.contentEditable = true;
        
        var current = getConfigValue(block, field.id);
        if (current === undefined || current === null) { current = field.defecto || ''; }
        editor.innerHTML = current;
        
        editor.addEventListener('input', function() {
            var content = editor.innerHTML;
            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) { api.updateConfigValue(block, field.id, content); }
        });
        
        // Paste handling to strip styles? For now let it be.
        
        container.appendChild(editor);
        wrapper.appendChild(container);
        appendFieldDescription(wrapper, field);
        return wrapper;
    }

    function shouldShowField(block, field) {
        if (!field || !field.condicion || !Array.isArray(field.condicion)) {
            return true;
        }
        
        var cond = field.condicion;
        var key, operator, value;

        // Support [key, value] (implicit equality)
        if (cond.length === 2) {
            key = cond[0];
            operator = '==';
            value = cond[1];
        } 
        // Support [key, operator, value]
        else if (cond.length === 3) {
            key = cond[0];
            operator = cond[1];
            value = cond[2];
        } else {
            return true; // Malformed, show by default
        }

        var current = getConfigValue(block, key);

        switch (operator) {
            case '==': return current === value;
            case '!=': return current !== value;
            case 'in': return Array.isArray(value) && value.indexOf(current) !== -1;
            case '!in': return Array.isArray(value) && value.indexOf(current) === -1;
            default: return true;
        }
    }

    function buildHeaderField(block, field) {
        var wrapper = document.createElement('div'); 
        wrapper.className = 'gbn-field-header-separator';
        var label = document.createElement('h4'); 
        label.textContent = field.etiqueta || field.id; 
        wrapper.appendChild(label);
        return wrapper;
    }

    function buildField(block, field) {
        if (!field) { return null; }
        // Headers might not have ID, allow them
        if (field.tipo !== 'header' && !field.id) { return null; }
        
        if (!shouldShowField(block, field)) { return null; }
        switch (field.tipo) {
            case 'header': return buildHeaderField(block, field);
            case 'spacing': return buildSpacingField(block, field);
            case 'slider': return buildSliderField(block, field);
            case 'select': return buildSelectField(block, field);
            case 'toggle': return buildToggleField(block, field);
            case 'color': return buildColorField(block, field);
            case 'typography': return buildTypographyField(block, field);
            case 'icon_group': return buildIconGroupField(block, field);
            case 'fraction': return buildFractionSelectorField(block, field);
            case 'rich_text': return buildRichTextField(block, field);
            case 'text':
            default: return buildTextField(block, field);
        }
    }

    /**
     * Agrega indicador visual de estado de sincronización para campos del Panel de Tema
     * Muestra si el campo está sincronizado con CSS o modificado manualmente
     */
    function addSyncIndicator(wrapper, block, fieldId) {
        // Solo para Panel de Tema
        if (!block || block.role !== 'theme') return;
        
        // Solo para campos de componentes
        if (!fieldId || !fieldId.startsWith('components.')) return;
        
        var pathParts = fieldId.split('.');
        if (pathParts.length < 3) return;
        
        var role = pathParts[1];      // "principal" o "secundario"
        var prop = pathParts[2];      // "padding", "background", etc.
        
        // Obtener estado de sincronización
        var syncState = 'css'; // default: sincronizado
        if (block.config && block.config.components && block.config.components[role]) {
            var comp = block.config.components[role];
            if (comp.__sync && comp.__sync[prop]) {
                syncState = comp.__sync[prop];
            }
        }
        
        // Crear indicador
        var indicator = document.createElement('span');
        indicator.className = 'gbn-sync-indicator gbn-sync-' + syncState;
        indicator.style.marginLeft = '8px';
        indicator.style.padding = '2px 8px';
        indicator.style.borderRadius = '4px';
        indicator.style.fontSize = '11px';
        indicator.style.fontWeight = '600';
        indicator.style.textTransform = 'uppercase';
        
        if (syncState === 'css') {
            indicator.innerHTML = '🔗 CSS';
            indicator.style.backgroundColor = '#e3f2fd';
            indicator.style.color = '#1976d2';
            indicator.title = 'Sincronizado con código CSS';
        } else {
            indicator.innerHTML = '✏️ Manual';
            indicator.style.backgroundColor = '#fff3e0';
            indicator.style.color = '#f57c00';
            indicator.title = 'Modificado manualmente - No sincroniza con CSS';
        }
        
        // Buscar dónde agregarlo (legend o label)
        var target = wrapper.querySelector('legend') || wrapper.querySelector('.gbn-field-label');
        if (target) {
            target.appendChild(indicator);
        }
    }

    /**
     * Actualiza los placeholders de todos los campos visibles basados en los nuevos defaults del tema
     */
    function updatePlaceholdersFromTheme(role, property, newValue) {
        // Buscar todos los inputs que coincidan con el rol y propiedad
        // Nota: property puede ser parcial (ej: 'padding' afecta a 'padding.superior', etc.)
        
        var inputs = document.querySelectorAll('#gbn-panel input[data-role="' + role + '"]');
        
        inputs.forEach(function(input) {
            var prop = input.dataset.prop;
            if (!prop) return;
            
            // Verificar si la propiedad coincide o es hija
            if (prop === property || prop.startsWith(property + '.')) {
                
                // Si el input tiene valor, es un override, no tocamos el valor pero sí el placeholder
                // Si el input está vacío (heredado), actualizamos placeholder y visualmente
                
                var newVal = getThemeDefault(role, prop);
                
                // Caso especial para spacing
                if (input.closest('.gbn-spacing-input')) {
                    // Recalcular valor basado en unidad actual
                    var wrapper = input.closest('.gbn-field-spacing');
                    var unit = wrapper ? wrapper.dataset.unit : 'px';
                    
                    if (newVal !== undefined && newVal !== null) {
                        var parsed = parseSpacingValue(newVal, unit);
                        input.placeholder = parsed.valor;
                    } else {
                        input.placeholder = '-';
                    }
                } 
                // Caso especial para slider
                else if (input.type === 'range') {
                    var wrapper = input.closest('.gbn-field-range');
                    var badge = wrapper.querySelector('.gbn-field-value');
                    
                    if (input.value === '' || wrapper.classList.contains('gbn-field-inherited')) {
                        // Actualizar valor visual si es heredado
                        if (newVal !== undefined && newVal !== null) {
                            input.value = newVal;
                            if (badge) badge.textContent = newVal + ' (auto)';
                        } else {
                            if (badge) badge.textContent = 'auto';
                        }
                    }
                }
                // Caso standard text/color
                else {
                    if (newVal !== undefined && newVal !== null) {
                        input.placeholder = newVal;
                        // Para color inputs, si es heredado, actualizar valor también
                        if (input.type === 'color' && input.closest('.gbn-field-inherited')) {
                            input.value = newVal;
                        }
                    } else {
                        input.placeholder = '';
                    }
                }
            }
        });
    }

    // Escuchar evento global de cambio de defaults
    if (typeof window !== 'undefined') {
        window.addEventListener('gbn:themeDefaultsChanged', function(e) {
            if (e.detail && e.detail.role) {
                updatePlaceholdersFromTheme(e.detail.role, e.detail.property, e.detail.value);
            }
        });
    }

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelFields = { buildField: buildField, addSyncIndicator: addSyncIndicator, updatePlaceholdersFromTheme: updatePlaceholdersFromTheme };
})(window);


