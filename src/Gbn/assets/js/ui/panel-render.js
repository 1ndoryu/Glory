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
    
    /**
     * Obtiene el valor de Theme Settings para un rol y propiedad específicos
     * @param {string} role - Rol del bloque (principal, secundario, etc.)
     * @param {string} path - Ruta de la propiedad (ej: 'padding.superior', 'layout', 'gap')
     * @returns {*} Valor del Theme Settings o undefined
     */
    function getThemeSettingsValue(role, path) {
        if (!role || !path) return undefined;
        
        // Intentar desde Gbn.config.themeSettings (estado local)
        var themeSettings = Gbn.config && Gbn.config.themeSettings;
        
        // Si no hay estado local, intentar desde gloryGbnCfg (cargado del servidor)
        if (!themeSettings && typeof gloryGbnCfg !== 'undefined') {
            themeSettings = gloryGbnCfg.themeSettings;
        }
        
        if (!themeSettings || !themeSettings.components || !themeSettings.components[role]) {
            return undefined;
        }
        
        var comp = themeSettings.components[role];
        var segments = path.split('.');
        var cursor = comp;
        
        for (var i = 0; i < segments.length; i++) {
            if (cursor === null || cursor === undefined) return undefined;
            cursor = cursor[segments[i]];
        }
        
        // Solo retornar si hay valor válido
        if (cursor !== undefined && cursor !== null && cursor !== '') {
            return cursor;
        }
        
        return undefined;
    }
    
    /**
     * Obtiene el valor efectivo de configuración con fallback a Theme Settings
     * Prioridad: 1) block.config, 2) themeSettings.components[role]
     * @param {Object} config - Configuración del bloque
     * @param {string} role - Rol del bloque
     * @param {string} path - Ruta de la propiedad
     * @returns {*} Valor encontrado o undefined
     */
    function getConfigWithThemeFallback(config, role, path) {
        // 1. Buscar en config del bloque
        var segments = path.split('.');
        var cursor = config || {};
        
        for (var i = 0; i < segments.length; i++) {
            if (cursor === null || cursor === undefined) break;
            cursor = cursor[segments[i]];
        }
        
        // Si el valor existe en config, usarlo
        if (cursor !== undefined && cursor !== null && cursor !== '') {
            return cursor;
        }
        
        // 2. Fallback a Theme Settings
        return getThemeSettingsValue(role, path);
    }

    function updateConfigValue(block, path, value) {
        if (!block || !path) { return; }

        // Lógica Responsive: Si no estamos en desktop, usar setResponsiveValue
        var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
        
        if (breakpoint !== 'desktop' && Gbn.responsive && Gbn.responsive.setResponsiveValue) {
            var current = cloneConfig(block.config);
            
            // Usar helper para escribir en la estructura correcta dentro de current
            // Pasamos un objeto mock {config: current} porque setResponsiveValue espera block.config
            var mockBlock = { config: current };
            Gbn.responsive.setResponsiveValue(mockBlock, path, value, breakpoint);
            
            // Continuar con el flujo normal de guardado usando 'current' actualizado
            var updated = state.updateConfig(block.id, current);
            applyBlockStyles(updated);
            
            // Notify core
            if (Gbn.ui.panel && Gbn.ui.panel.updateActiveBlock) {
                Gbn.ui.panel.updateActiveBlock(updated);
            }
            
            if (Gbn.ui.panel && Gbn.ui.panel.flashStatus) {
                Gbn.ui.panel.flashStatus('Cambios aplicados (' + breakpoint + ')');
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
                if (Gbn.ui.panel && Gbn.ui.panel.refreshControls) {
                    Gbn.ui.panel.refreshControls(updated);
                }
            }
            
            return updated;
        }

        var current = cloneConfig(block.config);
        var segments = path.split('.');
        var cursor = current;

        // Removed fallback to inlineValue to allow clearing of values.
        // If value is null/undefined/empty, it should remain so in the config
        // to allow inheritance from Theme Settings or CSS defaults.

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
        if (spacingConfig === null || spacingConfig === undefined || spacingConfig === '') { return styles; }
        
        // Handle single value (string or number)
        if (typeof spacingConfig !== 'object') {
            var val = typeof spacingConfig === 'number' ? spacingConfig + 'px' : spacingConfig;
            // If it's a simple value, apply to all sides or just 'padding' shorthand?
            // Using shorthand 'padding' is better but our map uses specific sides.
            // Let's set all specific sides to ensure consistency with overrides.
            styles['padding-top'] = val;
            styles['padding-right'] = val;
            styles['padding-bottom'] = val;
            styles['padding-left'] = val;
            return styles;
        }
        
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
        principal: function (config, block) {
            var role = 'principal';
            var bp = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
            
            // Helper para obtener valor responsive (SOLO del bloque, sin fallback a tema)
            function get(path) {
                if (Gbn.responsive && Gbn.responsive.getBlockResponsiveValue) {
                     return Gbn.responsive.getBlockResponsiveValue(block, path, bp);
                }
                // Fallback legacy: solo config del bloque
                var segments = path.split('.');
                var cursor = config || {};
                for (var i = 0; i < segments.length; i++) {
                    if (cursor === null || cursor === undefined) break;
                    cursor = cursor[segments[i]];
                }
                return (cursor !== undefined && cursor !== null && cursor !== '') ? cursor : undefined;
            }
            
            // Padding responsive (SOLO del bloque)
            var paddingConfig;
            if (Gbn.responsive && Gbn.responsive.getBlockResponsiveValue) {
                paddingConfig = Gbn.responsive.getBlockResponsiveValue(block, 'padding', bp);
            } else {
                paddingConfig = config.padding;
            }
            var styles = extractSpacingStyles(paddingConfig);
            
            var height = get('height');
            if (height && height !== 'auto') {
                if (height === 'min-content') {
                    styles['height'] = 'min-content';
                } else if (height === '100vh') {
                    styles['height'] = '100vh';
                }
            }
            
            var alineacion = get('alineacion');
            if (alineacion && alineacion !== 'inherit') { styles['text-align'] = alineacion; }
            
            var maxAncho = get('maxAncho');
            if (maxAncho !== null && maxAncho !== undefined && maxAncho !== '') {
                var val = String(maxAncho).trim();
                if (/^-?\d+(\.\d+)?$/.test(val)) {
                    styles['max-width'] = val + 'px';
                } else {
                    styles['max-width'] = val;
                }
            }
            
            var fondo = get('fondo') || get('background');
            if (fondo) { styles.background = fondo; }
            
            // Layout logic for principal
            var layout = get('layout') || 'flex'; // Default to flex
            
            if (layout === 'grid') {
                styles.display = 'grid';
                var gridColumns = get('gridColumns');
                if (gridColumns) {
                    styles['grid-template-columns'] = 'repeat(' + gridColumns + ', 1fr)';
                }
                var gridRows = get('gridRows');
                if (gridRows && gridRows !== 'auto') {
                    styles['grid-template-rows'] = gridRows;
                }
                var gridGap = get('gridGap');
                var gap = get('gap');
                if (gridGap) { styles.gap = gridGap + 'px'; }
                else if (gap) { styles.gap = gap + 'px'; }
            } else if (layout === 'flex') {
                styles.display = 'flex';
                var direction = get('direction') || get('flexDirection');
                var wrap = get('wrap') || get('flexWrap');
                var justify = get('justify') || get('flexJustify');
                var align = get('align') || get('flexAlign');
                var gap = get('gap');
                
                if (direction) { styles['flex-direction'] = direction; }
                if (wrap) { styles['flex-wrap'] = wrap; }
                if (justify) { styles['justify-content'] = justify; }
                if (align) { styles['align-items'] = align; }
                if (gap) { styles.gap = gap + 'px'; }
            } else {
                styles.display = 'block';
            }
            
            return styles;
        },
        secundario: function (config, block) {
            var role = 'secundario';
            var bp = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
            
            // Helper para obtener valor responsive (SOLO del bloque, sin fallback a tema)
            function get(path) {
                if (Gbn.responsive && Gbn.responsive.getBlockResponsiveValue) {
                     return Gbn.responsive.getBlockResponsiveValue(block, path, bp);
                }
                // Fallback legacy: solo config del bloque
                var segments = path.split('.');
                var cursor = config || {};
                for (var i = 0; i < segments.length; i++) {
                    if (cursor === null || cursor === undefined) break;
                    cursor = cursor[segments[i]];
                }
                return (cursor !== undefined && cursor !== null && cursor !== '') ? cursor : undefined;
            }
            
            // Padding responsive (SOLO del bloque)
            var paddingConfig;
            if (Gbn.responsive && Gbn.responsive.getBlockResponsiveValue) {
                paddingConfig = Gbn.responsive.getBlockResponsiveValue(block, 'padding', bp);
            } else {
                paddingConfig = config.padding;
            }
            var styles = extractSpacingStyles(paddingConfig);
            
            var height = get('height');
            if (height && height !== 'auto') {
                if (height === 'min-content') {
                    styles['height'] = 'min-content';
                } else if (height === '100vh') {
                    styles['height'] = '100vh';
                }
            }
            
            // Width logic
            var width = get('width');
            if (width) {
                 var pct = parseFraction(width);
                 if (pct) {
                     styles.width = pct;
                     styles['flex-basis'] = pct;
                     styles['flex-shrink'] = '0';
                     styles['flex-grow'] = '0';
                 }
            }

            var gap = get('gap');
            if (gap !== null && gap !== undefined && gap !== '') {
                var gapVal = parseFloat(gap);
                if (!isNaN(gapVal)) { styles.gap = gapVal + 'px'; }
            }
            
            var layout = get('layout') || 'block'; // Default to block
            
            if (layout === 'grid') {
                styles.display = 'grid';
                var gridColumns = get('gridColumns');
                if (gridColumns) {
                    styles['grid-template-columns'] = 'repeat(' + gridColumns + ', 1fr)';
                }
                var gridRows = get('gridRows');
                if (gridRows && gridRows !== 'auto') {
                    styles['grid-template-rows'] = gridRows;
                }
                var gridGap = get('gridGap');
                if (gridGap) { styles.gap = gridGap + 'px'; }
            } else if (layout === 'flex') {
                styles.display = 'flex';
                var direction = get('direction') || get('flexDirection');
                var wrap = get('wrap') || get('flexWrap');
                var justify = get('justify') || get('flexJustify');
                var align = get('align') || get('flexAlign');
                
                if (direction) { styles['flex-direction'] = direction; }
                if (wrap) { styles['flex-wrap'] = wrap; }
                if (justify) { styles['justify-content'] = justify; }
                if (align) { styles['align-items'] = align; }
            } else {
                styles.display = 'block';
            }
            
            var fondo = get('fondo') || get('background');
            if (fondo) { styles.background = fondo; }
            
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
            
            // NUEVO: Detectar breakpoint activo
            var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) ? Gbn.responsive.getCurrentBreakpoint() : 'desktop';
            
            // Si estamos en Mobile/Tablet, escribir en _responsive[bp] en lugar de raíz
            var cursor;
            if (breakpoint !== 'desktop' && path.startsWith('components.')) {
                // Path ejemplo: "components.principal.padding.superior"
                var pathParts = path.split('.');
                var role = pathParts[1]; // "principal"
                
                console.log('[PanelRender] Updating Theme Responsive:', { role: role, path: path, bp: breakpoint, value: value });

                // Asegurar estructura _responsive en el componente
                if (!current.components) current.components = {};
                if (!current.components[role]) current.components[role] = {};
                if (!current.components[role]._responsive) current.components[role]._responsive = {};
                if (!current.components[role]._responsive[breakpoint]) current.components[role]._responsive[breakpoint] = {};
                
                // Navegar dentro de _responsive para escribir el valor
                var relativePath = pathParts.slice(2); // ['padding', 'superior']
                cursor = current.components[role]._responsive[breakpoint];
                
                for (var i = 0; i < relativePath.length - 1; i++) {
                    var key = relativePath[i];
                    if (!cursor[key] || typeof cursor[key] !== 'object') {
                        cursor[key] = {};
                    }
                    cursor = cursor[key];
                }
                cursor[relativePath[relativePath.length - 1]] = value;
            } else {
                // Desktop o paths no-components: escribir en raíz como siempre
                console.log('[PanelRender] Updating Theme Desktop:', { path: path, value: value });
                cursor = current;
                for (var i = 0; i < segments.length - 1; i++) {
                    var key = segments[i];
                    if (!cursor[key] || typeof cursor[key] !== 'object') {
                        cursor[key] = {};
                    }
                    cursor = cursor[key];
                }
                cursor[segments[segments.length - 1]] = value;
            }
            
            // DETECTAR CAMBIOS MANUALES: Marcar como 'manual' en __sync
            // Solo para desktop (los overrides responsive ya son "manuales" por definición)
            if (breakpoint === 'desktop' && path.startsWith('components.')) {
                // Ejemplo path: "components.principal.padding.superior"
                var pathParts = path.split('.');
                if (pathParts.length >= 3) {
                    var role = pathParts[1];      // "principal" o "secundario"
                    var prop = pathParts[2];      // "padding", "background", etc.
                    
                    // Asegurar que existe el objeto __sync
                    if (!current.components[role].__sync) {
                        current.components[role].__sync = {};
                    }
                    
                    // Marcar como modificado manualmente
                    current.components[role].__sync[prop] = 'manual';
                }
            }
            
            // Update block config reference
            block.config = current;
            
            // Sync to global config
            if (!Gbn.config) Gbn.config = {};
            Gbn.config.themeSettings = current;
            
            if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.applyThemeSettings) {
                Gbn.ui.panelTheme.applyThemeSettings(current);
            }
            
            // Dispatch event (incluir path y breakpoint)
            var event;
            if (typeof global.CustomEvent === 'function') {
                event = new CustomEvent('gbn:configChanged', { detail: { id: 'theme-settings', path: path, breakpoint: breakpoint } });
            } else {
                event = document.createEvent('CustomEvent');
                event.initCustomEvent('gbn:configChanged', false, false, { id: 'theme-settings', path: path, breakpoint: breakpoint });
            }
            global.dispatchEvent(event);
            
            // NUEVO: Disparar evento específico para actualización en tiempo real de defaults
            // Solo para desktop (los overrides responsive no afectan a otros bloques como "defaults")
            if (breakpoint === 'desktop' && path.startsWith('components.')) {
                var pathParts = path.split('.');
                if (pathParts.length >= 3) {
                    var role = pathParts[1];
                    // Reconstruir la propiedad relativa al componente (ej: 'padding.superior')
                    var property = pathParts.slice(2).join('.');
                    
                    var defaultsEvent;
                    var detail = { role: role, property: property, value: value };
                    
                    if (typeof global.CustomEvent === 'function') {
                        defaultsEvent = new CustomEvent('gbn:themeDefaultsChanged', { detail: detail });
                    } else {
                        defaultsEvent = document.createEvent('CustomEvent');
                        defaultsEvent.initCustomEvent('gbn:themeDefaultsChanged', false, false, detail);
                    }
                    global.dispatchEvent(defaultsEvent);
                }
            }
            
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

    /**
     * Aplica los nuevos defaults del tema a todos los bloques que no tienen override
     */
    function applyThemeDefaultsToBlocks(role, property, value) {
        if (!state || !state.all) return;
        
        var blocks = state.all();
        blocks.forEach(function(block) {
            if (block.role !== role) return;
            
            // Verificar si el bloque tiene un override para esta propiedad
            // Usamos getDefaultValueForPath para ver si hay valor configurado
            // Pero getDefaultValueForPath busca en defaults si no hay config.
            // Necesitamos chequear directamente block.config
            
            var hasOverride = false;
            var currentConfig = block.config || {};
            
            // Navegar por el objeto config para ver si existe la propiedad
            var segments = property.split('.');
            var cursor = currentConfig;
            for (var i = 0; i < segments.length; i++) {
                if (cursor === undefined || cursor === null) break;
                cursor = cursor[segments[i]];
            }
            
            // Si cursor tiene valor (y no es null/undefined/''), es un override
            if (cursor !== undefined && cursor !== null && cursor !== '') {
                hasOverride = true;
            }
            
            // Si NO tiene override, debemos re-aplicar estilos para que tome el nuevo default
            if (!hasOverride) {
                // Forzamos re-calculo de estilos. 
                // applyBlockStyles usa los defaults internamente si no hay config
                // Pero necesitamos asegurarnos que 'getThemeDefault' (usado en panel-fields) 
                // o la lógica de resolución de estilos use el nuevo valor.
                
                // En panel-render.js, 'styleResolvers' usa 'block.config'.
                // Si block.config está vacío, usa defaults?
                // Revisemos styleResolvers...
                // styleResolvers['principal'] usa config.padding etc.
                // Si config.padding es undefined, no aplica nada.
                // ESTO ES UN PROBLEMA: styleResolvers asume que config tiene todo o que CSS classes manejan defaults.
                // Pero queremos ver los cambios en tiempo real.
                
                // Si el sistema confía en clases CSS para defaults, entonces actualizar las variables CSS
                // (que ya hace panel-theme.js -> applyThemeSettings) debería ser suficiente.
                
                // PERO, si styleResolvers aplica estilos inline que SOBREESCRIBEN las clases,
                // entonces debemos asegurarnos que no haya estilos inline viejos.
                
                // Si no hay override, styleResolvers no debería generar estilo inline para esa propiedad.
                // Por lo tanto, el elemento debería caer en los estilos por defecto (CSS vars).
                
                // Si panel-theme.js ya actualizó las variables CSS globales (--gbn-principal-padding, etc),
                // entonces los elementos deberían actualizarse automáticamente por el navegador.
                
                // EXCEPCIÓN: Si previamente había un valor y se borró, styleManager podría haber dejado
                // un estilo inline vacío o algo así? No, styleManager limpia.
                
                // ENTONCES: La actualización visual "en tiempo real" para elementos NO editados
                // debería ser automática gracias a las variables CSS, SIEMPRE Y CUANDO
                // los defaults del tema se mapeen a variables CSS.
                
                // Revisemos panel-theme.js: applyThemeSettings SÍ mapea a variables:
                // --gbn-principal-padding, --gbn-principal-background, etc.
                
                // Y revisemos ContainerRegistry/CSS: ¿Los componentes usan estas variables?
                // Si no las usan, no se verá nada.
                // Asumamos que el sistema de variables CSS está conectado (según plan.md).
                
                // SIN EMBARGO, para propiedades que NO son variables CSS (como layout flex/grid options que generan estilos inline),
                // sí necesitamos re-aplicar.
                
                // Además, si el usuario tiene el panel abierto de un bloque afectado,
                // queremos que los placeholders se actualicen (eso ya lo hace panel-fields.js).
                
                // Vamos a forzar un re-apply por si acaso hay lógica JS que depende de defaults
                applyBlockStyles(block);
            }
        });
    }

    // Escuchar evento global
    if (typeof window !== 'undefined') {
        window.addEventListener('gbn:themeDefaultsChanged', function(e) {
            if (e.detail && e.detail.role) {
                applyThemeDefaultsToBlocks(e.detail.role, e.detail.property, e.detail.value);
            }
        });
        
        // Re-aplicar estilos cuando cambia el breakpoint para simular media queries en el editor
        window.addEventListener('gbn:breakpointChanged', function(e) {
            var bp = e.detail ? e.detail.current : 'desktop';
            
            // 1. Re-aplicar variables CSS del tema con el nuevo breakpoint
            if (Gbn.ui.theme.applicator && Gbn.ui.theme.applicator.applyThemeSettings) {
                // Obtener settings actuales (local o global)
                var settings = (Gbn.config && Gbn.config.themeSettings) || (gloryGbnCfg && gloryGbnCfg.themeSettings);
                if (settings) {
                    Gbn.ui.theme.applicator.applyThemeSettings(settings, bp);
                }
            }

            // 2. Re-aplicar estilos inline a bloques (si fuera necesario, aunque idealmente todo debería ser CSS vars)
            applyThemeStylesToAllBlocks(); 
            
            // 3. También actualizar el panel activo si hay uno seleccionado
            if (Gbn.ui.panel && Gbn.ui.panel.refreshControls && state.activeBlockId) {
                var block = state.get(state.activeBlockId);
                if (block) Gbn.ui.panel.refreshControls(block);
            }
        });
    }

    /**
     * Aplica los estilos basados en Theme Settings a todos los bloques existentes
     * Útil para aplicar cambios después de cargar Theme Settings del servidor
     */
    function applyThemeStylesToAllBlocks() {
        if (!state || !state.all) return;
        
        var blocks = state.all();
        blocks.forEach(function(block) {
            if (block.role === 'principal' || block.role === 'secundario') {
                applyBlockStyles(block);
            }
        });
    }

    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelRender = {
        renderBlockControls: renderBlockControls,
        updateConfigValue: updateConfigValue,
        applyBlockStyles: applyBlockStyles,
        applyThemeStylesToAllBlocks: applyThemeStylesToAllBlocks,
        getThemeSettingsValue: getThemeSettingsValue,
        getConfigWithThemeFallback: getConfigWithThemeFallback
    };

})(window);
