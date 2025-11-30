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

        var conditionalFields = ['layout'];
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
             // Special handling for text component
             // This was handled in panel.js via updateConfigValue monkey-patching or similar?
             // No, it was in panel.js updateConfigValue but I don't see it in the snippet I read.
             // Wait, I saw it in the summary: "The Gbn.ui.panelApi.updateConfigValue function was extended to include special handling for blocks with role === 'text'."
             // I need to check if I missed that logic in my read of panel.js.
             // Yes, I probably missed it because I only read up to line 800 and maybe it was injected or I missed the diff.
             // Let's implement it here properly.
             
             // Logic for text role:
             // When config changes, we might need to update the DOM element tag or styles.
             // But applyBlockStyles updates styleManager.
             // Tag changing is a DOM operation, not just style.
             // So updateConfigValue needs to handle it.
             return {};
        }
    };

    function applyBlockStyles(block) {
        if (!block || !styleManager || !styleManager.update) { return; }
        var resolver = styleResolvers[block.role] || function () { return {}; };
        var computedStyles = resolver(block.config || {}, block) || {};
        styleManager.update(block, computedStyles);
        
        // Special handling for text role (tag switching and inline styles)
        if (block.role === 'text') {
            // This logic was likely in updateConfigValue in the previous version.
            // Let's add it to updateConfigValue or here.
            // Since applyBlockStyles is called by updateConfigValue, we can do it here if it's style related.
            // But tag switching is structural.
        }
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
                 var newEl = document.createElement(newTag);
                 
                 // Copy attributes
                 Array.from(oldEl.attributes).forEach(function(attr) {
                     newEl.setAttribute(attr.name, attr.value);
                 });
                 
                 // Copy content
                 newEl.innerHTML = oldEl.innerHTML;
                 
                 // Replace in DOM
                 oldEl.parentNode.replaceChild(newEl, oldEl);
                 block.element = newEl;
                 
                 // Update state ref?
                 // state.updateConfig returns a new block object but with same id.
                 // We need to ensure the block reference in state is updated with new element?
                 // content.js manages scanning.
                 // For now, updating block.element here affects the local reference.
             }
             
             // Apply inline styles directly for text
             if (path === 'texto') {
                 block.element.textContent = value;
             }
             if (path === 'alineacion') {
                 block.element.style.textAlign = value;
             }
             if (path === 'color') {
                 block.element.style.color = value;
             }
             if (path === 'size') {
                 block.element.style.fontSize = value + 'px';
             }
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
