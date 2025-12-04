;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = function() { return Gbn.ui.fieldUtils; };
    var sync = function() { return Gbn.ui.fieldSync; };

    /**
     * Construye un campo de color con picker y paleta global
     */
    /**
     * Construye un campo de color con picker y paleta global
     */
    function buildColorField(block, field) {
        var u = utils();
        var colorUtils = Gbn.ui.colorUtils;
        var allowTransparency = field.permiteTransparencia === true;

        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field gbn-field-color';
        
        var header = document.createElement('div');
        header.className = 'gbn-field-header';
        
        var label = document.createElement('label');
        label.className = 'gbn-field-label';
        label.textContent = field.etiqueta || field.id;
        header.appendChild(label);
        wrapper.appendChild(header);
        
        // Indicador de sincronización
        if (sync() && sync().addSyncIndicator) {
            sync().addSyncIndicator(wrapper, block, field.id);
        }
        
        var container = document.createElement('div');
        container.className = 'gbn-color-container';
        if (allowTransparency) {
            container.classList.add('has-transparency');
        }

        // Preview Wrapper (para transparencia)
        var previewWrapper = document.createElement('div');
        previewWrapper.className = 'gbn-color-preview-wrapper';
        
        // Input nativo de color (Solo HEX)
        var inputColor = document.createElement('input');
        inputColor.type = 'color';
        inputColor.className = 'gbn-color-picker';
        
        // Preview visual (Checkered background + RGBA color)
        var visualPreview = document.createElement('div');
        visualPreview.className = 'gbn-color-visual-preview';
        
        if (allowTransparency) {
            previewWrapper.appendChild(visualPreview);
            // El input color se hace invisible pero clickable sobre el preview
            inputColor.classList.add('is-overlay');
            previewWrapper.appendChild(inputColor);
            container.appendChild(previewWrapper);
        } else {
            container.appendChild(inputColor);
        }
        
        var inputsColumn = document.createElement('div');
        inputsColumn.className = 'gbn-color-inputs-col';
        
        var inputText = document.createElement('input');
        inputText.type = 'text';
        inputText.className = 'gbn-color-text gbn-input';
        inputText.placeholder = 'ej: #ff5733';
        
        inputsColumn.appendChild(inputText);

        // Slider de Opacidad
        var opacityContainer = null;
        var opacitySlider = null;
        var opacityValue = null;

        if (allowTransparency) {
            opacityContainer = document.createElement('div');
            opacityContainer.className = 'gbn-opacity-container';
            
            opacitySlider = document.createElement('input');
            opacitySlider.type = 'range';
            opacitySlider.min = '0';
            opacitySlider.max = '100';
            opacitySlider.step = '1';
            opacitySlider.className = 'gbn-opacity-slider';
            
            opacityValue = document.createElement('span');
            opacityValue.className = 'gbn-opacity-value';
            opacityValue.textContent = '100%';

            opacityContainer.appendChild(opacitySlider);
            opacityContainer.appendChild(opacityValue);
            inputsColumn.appendChild(opacityContainer);
        }

        container.appendChild(inputsColumn);
        
        // Estado interno
        var currentHex = '#000000';
        var currentAlpha = 1;

        function updateUI(hex, alpha) {
            // Actualizar input color (siempre hex)
            if (hex) inputColor.value = hex;
            
            // Actualizar slider y texto de opacidad
            if (allowTransparency) {
                var alphaPercent = Math.round(alpha * 100);
                opacitySlider.value = alphaPercent;
                opacityValue.textContent = alphaPercent + '%';
                
                // Actualizar preview visual
                visualPreview.style.backgroundColor = colorUtils.hexToRgba(hex, alpha);
            }

            // Actualizar input texto
            if (allowTransparency) {
                if (alpha < 1) {
                    inputText.value = colorUtils.hexToRgba(hex, alpha);
                } else {
                    inputText.value = hex;
                }
            } else {
                inputText.value = hex;
            }
        }

        function updateModel() {
            var valueToSave;
            if (allowTransparency) {
                if (currentAlpha < 1) {
                    valueToSave = colorUtils.hexToRgba(currentHex, currentAlpha);
                } else {
                    valueToSave = currentHex;
                }
            } else {
                valueToSave = currentHex;
            }

            var api = Gbn.ui && Gbn.ui.panelApi;
            if (api && api.updateConfigValue && block) {
                api.updateConfigValue(block, field.id, valueToSave);
            }
        }
        
        // Leer valor computado (solo si hay elemento DOM)
        var computedColor = null;
        if (block.element) {
            computedColor = u.getComputedValue(block.element, 'backgroundColor');
        }
        
        // [FIX] Para Theme Settings y Page Settings (mockBlocks sin element),
        // leer directamente de config usando getDeepValue ya que getEffectiveValue
        // depende de block.element para computed styles
        var configValue = null;
        if (block.config && u.getDeepValue) {
            configValue = u.getDeepValue(block.config, field.id);
        }
        
        // Obtener valor efectivo (funciona bien para bloques normales con element)
        var effective = u.getEffectiveValue(block, field.id);
        var themeDefault = u.getThemeDefault(block.role, field.id);
        
        // Parsear valor inicial - prioridad: configValue > effective.value > computedColor > defecto del field > fallback
        var initialValue = configValue || effective.value || computedColor || field.defecto || '#000000';
        var parsed = colorUtils.parseColor(initialValue);
        
        if (parsed) {
            currentHex = colorUtils.toHex(parsed);
            currentAlpha = parsed.a;
        }

        // Inicializar UI
        updateUI(currentHex, currentAlpha);
        
        // Event Listeners
        inputColor.addEventListener('input', function() {
            currentHex = inputColor.value;
            updateUI(currentHex, currentAlpha);
            updateModel();
        });

        if (allowTransparency) {
            opacitySlider.addEventListener('input', function() {
                currentAlpha = parseInt(opacitySlider.value) / 100;
                updateUI(currentHex, currentAlpha);
                updateModel();
            });
        }
        
        inputText.addEventListener('change', function() {
            var val = inputText.value.trim();
            var parsed = colorUtils.parseColor(val);
            
            if (parsed) {
                currentHex = colorUtils.toHex(parsed);
                currentAlpha = allowTransparency ? parsed.a : 1;
                updateUI(currentHex, currentAlpha);
                updateModel();
            }
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

        // Colores por defecto y del tema
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
                    if (c.value && c.name) mapped.push({ val: c.value, name: c.name });
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
                var parsed = colorUtils.parseColor(c.val);
                if (parsed) {
                    currentHex = colorUtils.toHex(parsed);
                    currentAlpha = parsed.a; 
                    updateUI(currentHex, currentAlpha);
                    updateModel();
                }
            });
            palette.appendChild(swatch);
        });
        
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

    if (Gbn.ui.panelFields && Gbn.ui.panelFields.registry) {
        Gbn.ui.panelFields.registry.register('color', buildColorField);
    }

})(window);

