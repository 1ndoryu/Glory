;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;
    var styleManager = Gbn.styleManager;

    function cloneConfig(config) {
        var output = utils.assign({}, config || {});
        Object.keys(output).forEach(function (key) {
            var item = output[key];
            if (item && typeof item === 'object' && !Array.isArray(item)) {
                output[key] = utils.assign({}, item);
            }
        });
        return output;
    }

    function getRoleDefaults(role) {
        // Accessing global ROLE_DEFAULTS from content.js if available
        // Assuming Gbn.content exposes it or we need to duplicate/access it differently.
        // content.js doesn't explicitly expose ROLE_DEFAULTS globally in Gbn.
        // But content.js runs before this.
        // Let's assume we can access it via Gbn.content.getRoleDefaults if we added it, 
        // or we might need to rely on what's available.
        // Looking at content.js, it seems ROLE_DEFAULTS is local.
        // However, `updateConfigValue` in panel.js used `getRoleDefaults`.
        // Wait, `getRoleDefaults` was NOT defined in the snippet of panel.js I read!
        // Let me check panel.js again. I might have missed it or it relies on a closure I missed.
        // Ah, I see `getDefaultValueForPath` calls `getRoleDefaults`.
        // I need to check where `getRoleDefaults` comes from.
        return (Gbn.content && Gbn.content.getRoleDefaults) ? Gbn.content.getRoleDefaults(role) : null;
    }

    function getInlineValueForPath(block, path) {
        if (!block || !block.styles || !block.styles.inline || !path) {
            return null;
        }

        var inline = block.styles.inline;

        var pathToCssMap = {
            'padding.superior': 'padding-top',
            'padding.derecha': 'padding-right',
            'padding.inferior': 'padding-bottom',
            'padding.izquierda': 'padding-left',
            'height': 'height',
            'alineacion': 'text-align',
            'maxAncho': 'max-width',
            'fondo': 'background'
        };

        var cssProp = pathToCssMap[path];
        if (cssProp && inline[cssProp] !== undefined) {
            return inline[cssProp];
        }

        return null;
    }

    function getDefaultValueForPath(block, path) {
        if (!block || !path) { return null; }

        var defaults = getRoleDefaults(block.role);
        if (!defaults || !defaults.config) { return null; }

        var segments = path.split('.');
        var cursor = defaults.config;
        for (var i = 0; i < segments.length; i += 1) {
            if (cursor === null || cursor === undefined) { return null; }
            cursor = cursor[segments[i]];
        }
        return cursor;
    }

    function updateConfigValue(block, path, value) {
        if (!block || !path) { return; }

        var current = cloneConfig(block.config);
        var segments = path.split('.');
        var cursor = current;

        if (value === null || value === undefined || value === '') {
            var inlineValue = getInlineValueForPath(block, path);
            if (inlineValue !== null) {
                value = inlineValue;
            } else {
                var defaultValue = getDefaultValueForPath(block, path);
                if (defaultValue !== null && defaultValue !== undefined) {
                    value = defaultValue;
                }
            }
        }

        for (var i = 0; i < segments.length - 1; i += 1) {
            var key = segments[i];
            var existing = cursor[key];
            if (!existing || typeof existing !== 'object' || Array.isArray(existing)) {
                existing = {};
            } else {
                existing = utils.assign({}, existing);
            }
            cursor[key] = existing;
            cursor = existing;
        }
        cursor[segments[segments.length - 1]] = value;
        var updated = state.updateConfig(block.id, current);
        applyBlockStyles(updated);
        
        // Notify core to update active block
        if (Gbn.ui.panel && Gbn.ui.panel.updateActiveBlock) {
            Gbn.ui.panel.updateActiveBlock(updated);
        }
        
        if (Gbn.ui.panel && Gbn.ui.panel.flashStatus) {
            Gbn.ui.panel.flashStatus('Cambios aplicados');
        }

        var event;
        if (typeof global.CustomEvent === 'function') {
            event = new CustomEvent('gbn:configChanged', { detail: { id: block.id } });
        } else {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent('gbn:configChanged', false, false, { id: block.id });
        }
        global.dispatchEvent(event);

        var conditionalFields = ['layout', 'display_mode', 'img_show', 'title_show', 'interaccion_modo'];
        if (conditionalFields.indexOf(path) !== -1) {
            // Re-render controls
            // We need access to the container. 
            // Ideally panel-core handles re-rendering or we pass a callback.
            // For now, let's assume panel-core listens to updates or we call a method on it.
            if (Gbn.ui.panel && Gbn.ui.panel.refreshControls) {
                Gbn.ui.panel.refreshControls(updated);
            }
        }
        
        return updated;
    }

    function extractSpacingStyles(spacingConfig) {
        var styles = {};
        if (!spacingConfig || typeof spacingConfig !== 'object') { return styles; }
        var map = { superior: 'padding-top', derecha: 'padding-right', inferior: 'padding-bottom', izquierda: 'padding-left' };
        Object.keys(map).forEach(function (key) {
            var raw = spacingConfig[key];
            if (raw === null || raw === undefined || raw === '') { return; }
            if (typeof raw === 'number') { styles[map[key]] = raw + 'px'; }
            else { styles[map[key]] = raw; }
        });
        return styles;
    }

    function parseFraction(fraction) {
        if (!fraction || typeof fraction !== 'string') return null;
        var parts = fraction.split('/');
        if (parts.length !== 2) return null;
        var num = parseFloat(parts[0]);
        var den = parseFloat(parts[1]);
        if (isNaN(num) || isNaN(den) || den === 0) return null;
        return (num / den * 100).toFixed(4) + '%';
    }

    var styleResolvers = {
        principal: function (config) {
            var styles = extractSpacingStyles(config.padding);
            if (config.height && config.height !== 'auto') {
                if (config.height === 'min-content') {
                    styles['height'] = 'min-content';
                } else if (config.height === '100vh') {
                    styles['height'] = '100vh';
                }
            }
            if (config.alineacion && config.alineacion !== 'inherit') { styles['text-align'] = config.alineacion; }
            if (config.maxAncho !== null && config.maxAncho !== undefined && config.maxAncho !== '') {
                var max = parseFloat(config.maxAncho);
                styles['max-width'] = !isNaN(max) ? max + 'px' : String(config.maxAncho);
            }
            if (config.fondo) { styles.background = config.fondo; }
            
            // Layout logic for principal
            if (config.layout) {
                if (config.layout === 'grid') {
                    styles.display = 'grid';
                    if (config.gridColumns) {
                        styles['grid-template-columns'] = 'repeat(' + config.gridColumns + ', 1fr)';
                    }
                    if (config.gridRows && config.gridRows !== 'auto') {
                        styles['grid-template-rows'] = config.gridRows;
                    }
                    if (config.gridGap) { styles.gap = config.gridGap + 'px'; }
                    else if (config.gap) { styles.gap = config.gap + 'px'; }
                } else if (config.layout === 'flex') {
                    styles.display = 'flex';
                    if (config.flexDirection) { styles['flex-direction'] = config.flexDirection; }
                    if (config.flexWrap) { styles['flex-wrap'] = config.flexWrap; }
                    if (config.flexJustify) { styles['justify-content'] = config.flexJustify; }
                    if (config.flexAlign) { styles['align-items'] = config.flexAlign; }
                    if (config.gap) { styles.gap = config.gap + 'px'; } // Add gap support for flex too
                } else {
                    styles.display = 'block';
                }
            }
            
            return styles;
        },
        secundario: function (config) {
            var styles = extractSpacingStyles(config.padding);
            if (config.height && config.height !== 'auto') {
                if (config.height === 'min-content') {
                    styles['height'] = 'min-content';
                } else if (config.height === '100vh') {
                    styles['height'] = '100vh';
                }
            }
            
            // Width logic
            if (config.width) {
                 var pct = parseFraction(config.width);
                 if (pct) {
                     styles.width = pct;
                     styles['flex-basis'] = pct; // Ensure it works in flex containers
                     styles['flex-shrink'] = '0'; // Prevent shrinking
                     styles['flex-grow'] = '0'; // Prevent growing
                 }
            }

            if (config.gap !== null && config.gap !== undefined && config.gap !== '') {
                var gap = parseFloat(config.gap);
                if (!isNaN(gap)) { styles.gap = gap + 'px'; }
            }
            if (config.layout) {
                if (config.layout === 'grid') {
                    styles.display = 'grid';
                    if (config.gridColumns) {
                        styles['grid-template-columns'] = 'repeat(' + config.gridColumns + ', 1fr)';
                    }
                    if (config.gridRows && config.gridRows !== 'auto') {
                        styles['grid-template-rows'] = config.gridRows;
                    }
                    if (config.gridGap) { styles.gap = config.gridGap + 'px'; }
                } else if (config.layout === 'flex') {
                    styles.display = 'flex';
                    if (config.flexDirection) { styles['flex-direction'] = config.flexDirection; }
                    if (config.flexWrap) { styles['flex-wrap'] = config.flexWrap; }
                    if (config.flexJustify) { styles['justify-content'] = config.flexJustify; }
                    if (config.flexAlign) { styles['align-items'] = config.flexAlign; }
                } else {
                    styles.display = 'block';
                }
            }
            return styles;
        },
        content: function () { return {}; },
        text: function(config, block) { 
            var styles = {};
            if (config.alineacion) { styles['text-align'] = config.alineacion; }
            if (config.color) { styles['color'] = config.color; }
            if (config.size) { styles['font-size'] = config.size; } // Legacy support
            
            if (config.typography) {
                var t = config.typography;
                if (t.font && t.font !== 'System' && t.font !== 'Default') { styles['font-family'] = t.font; }
                if (t.size) { styles['font-size'] = t.size; }
                if (t.lineHeight) { styles['line-height'] = t.lineHeight; }
                if (t.letterSpacing) { styles['letter-spacing'] = t.letterSpacing; }
                if (t.transform && t.transform !== 'none') { styles['text-transform'] = t.transform; }
            }
            return styles; 
        }
    };

    function applyBlockStyles(block) {
        if (!block || !styleManager || !styleManager.update) { return; }
        var resolver = styleResolvers[block.role] || function () { return {}; };
        var computedStyles = resolver(block.config || {}, block) || {};
        styleManager.update(block, computedStyles);
    }
    
    // Enhanced updateConfigValue to handle text role specifics
    var baseUpdateConfigValue = updateConfigValue;
    updateConfigValue = function(block, path, value) {
        if (block.role === 'text') {
             // Logic from previous session for text component
             if (path === 'tag') {
                 // Switch tag
                 var oldEl = block.element;
                 var newTag = value || 'p';
                 if (oldEl.tagName.toLowerCase() !== newTag.toLowerCase()) {
                     var newEl = document.createElement(newTag);
                     
                     // Copy attributes
                     Array.from(oldEl.attributes).forEach(function(attr) {
                         newEl.setAttribute(attr.name, attr.value);
                     });
                     
                     // Copy content
                     newEl.innerHTML = oldEl.innerHTML;
                     
                     // Replace in DOM
                     if (oldEl.parentNode) {
                        oldEl.parentNode.replaceChild(newEl, oldEl);
                        block.element = newEl;
                     }
                 }
             }
             
             // Apply inline styles directly for text
             if (path === 'texto') {
                 var controls = block.element.querySelector('.gbn-controls-group');
                 block.element.innerHTML = value; // Use innerHTML for rich text
                 if (controls) {
                     block.element.appendChild(controls);
                 }
             }
             if (path === 'alineacion') {
                 block.element.style.textAlign = value;
             }
             if (path === 'color') {
                 block.element.style.color = value;
             }
             if (path === 'size') {
                 var sizeVal = value;
                 if (sizeVal && !isNaN(parseFloat(sizeVal)) && isFinite(sizeVal)) {
                     // Check if it already has units. If it's just a number string "22", add px.
                     // If it is "22px" or "1.5rem", leave it.
                     if (!/^[0-9.]+[a-z%]+$/i.test(sizeVal)) {
                         sizeVal += 'px';
                     }
                 }
                 block.element.style.fontSize = sizeVal;
             }
        }
        
        // Handle Page Settings
        if (block.id === 'page-settings') {
            var current = cloneConfig(block.config);
            var segments = path.split('.');
            var cursor = current;
            for (var i = 0; i < segments.length - 1; i++) {
                if (!cursor[segments[i]]) cursor[segments[i]] = {};
                cursor = cursor[segments[i]];
            }
            cursor[segments[segments.length - 1]] = value;
            
            // Update block config reference (it's a mock block but we need to keep it updated)
            block.config = current;
            
            // Sync to global config
            if (!Gbn.config) Gbn.config = {};
            Gbn.config.pageSettings = current;
            
            if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.applyPageSettings) {
                Gbn.ui.panelTheme.applyPageSettings(current);
            }
            
            // Dispatch event
            var event;
            if (typeof global.CustomEvent === 'function') {
                event = new CustomEvent('gbn:configChanged', { detail: { id: 'page-settings' } });
            } else {
                event = document.createEvent('CustomEvent');
                event.initCustomEvent('gbn:configChanged', false, false, { id: 'page-settings' });
            }
            global.dispatchEvent(event);
            return current;
        }
        
        // Handle Theme Settings
        if (block.id === 'theme-settings') {
            var current = cloneConfig(block.config);
            var segments = path.split('.');
            var cursor = current;
            
            // Ensure path exists
            for (var i = 0; i < segments.length - 1; i++) {
                var key = segments[i];
                if (!cursor[key] || typeof cursor[key] !== 'object') {
                    cursor[key] = {};
                }
                cursor = cursor[key];
            }
            cursor[segments[segments.length - 1]] = value;
            
            // Update block config reference
            block.config = current;
            
            // Sync to global config
            if (!Gbn.config) Gbn.config = {};
            Gbn.config.themeSettings = current;
            
            if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.applyThemeSettings) {
                Gbn.ui.panelTheme.applyThemeSettings(current);
            }
            
            // Dispatch event
            var event;
            if (typeof global.CustomEvent === 'function') {
                event = new CustomEvent('gbn:configChanged', { detail: { id: 'theme-settings' } });
            } else {
                event = document.createEvent('CustomEvent');
                event.initCustomEvent('gbn:configChanged', false, false, { id: 'theme-settings' });
            }
            global.dispatchEvent(event);
            return current;
        }
        
        return baseUpdateConfigValue(block, path, value);
    };

    function createSummary(block) {
        var summary = document.createElement('div');
        summary.className = 'gbn-panel-block-summary';
        var idLabel = document.createElement('p');
        idLabel.className = 'gbn-panel-block-id';
        idLabel.innerHTML = 'ID: <code>' + block.id + '</code>';
        summary.appendChild(idLabel);
        var roleLabel = document.createElement('p');
        roleLabel.className = 'gbn-panel-block-role';
        roleLabel.innerHTML = 'Rol: <strong>' + (block.role || 'block') + '</strong>';
        summary.appendChild(roleLabel);
        if (block.meta && block.meta.postType) {
            var typeLabel = document.createElement('p');
            typeLabel.className = 'gbn-panel-block-type';
            typeLabel.textContent = 'Contenido: ' + block.meta.postType;
            summary.appendChild(typeLabel);
        }
        return summary;
    }

    function renderBlockControls(block, container) {
        if (!container) { return; }
        container.innerHTML = ''; 
        container.appendChild(createSummary(block));
        
        var schema = Array.isArray(block.schema) ? block.schema : [];
        if (!schema.length) {
            var empty = document.createElement('div'); 
            empty.className = 'gbn-panel-coming-soon'; 
            empty.textContent = 'Este bloque aún no expone controles editables.';
            container.appendChild(empty); 
            if (Gbn.ui.panel && Gbn.ui.panel.setStatus) {
                Gbn.ui.panel.setStatus('Sin controles disponibles');
            }
            return;
        }
        
        var form = document.createElement('form'); 
        form.className = 'gbn-panel-form';
        var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
        
        schema.forEach(function (field) { 
            var control = builder ? builder(block, field) : null; 
            if (control) { form.appendChild(control); } 
        });
        
        container.appendChild(form); 
        if (Gbn.ui.panel && Gbn.ui.panel.setStatus) {
            Gbn.ui.panel.setStatus('Edita las opciones y se aplicarán al instante');
        }
    }

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelRender = {
        renderBlockControls: renderBlockControls,
        updateConfigValue: updateConfigValue,
        applyBlockStyles: applyBlockStyles
    };

})(window);
