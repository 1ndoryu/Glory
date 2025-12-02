;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    var state = Gbn.state;
    var styleManager = Gbn.styleManager;

    var shared = Gbn.ui.renderers.shared;
    var cloneConfig = shared.cloneConfig;
    var getThemeSettingsValue = shared.getThemeSettingsValue;
    var getConfigWithThemeFallback = shared.getConfigWithThemeFallback;


    


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

        // Delegate to role-specific update handler if available
        var role = block.id === 'page-settings' ? 'pageSettings' : 
                   block.id === 'theme-settings' ? 'themeSettings' : 
                   block.role;
                   
        if (role && Gbn.ui.renderers[role] && Gbn.ui.renderers[role].handleUpdate) {
            var result = Gbn.ui.renderers[role].handleUpdate(block, path, value);
            if (result === true) {
                // Handled
            } else if (typeof result === 'object') {
                return result;
            }
        }

        var current = cloneConfig(block.config);
        var segments = path.split('.');
        var cursor = current;

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
            if (Gbn.ui.panel && Gbn.ui.panel.refreshControls) {
                Gbn.ui.panel.refreshControls(updated);
            }
        }
        
        return updated;
    }

    var styleResolvers = {
        principal: function (config, block) {
            return Gbn.ui.renderers.principal ? Gbn.ui.renderers.principal.getStyles(config, block) : {};
        },
        secundario: function (config, block) {
            return Gbn.ui.renderers.secundario ? Gbn.ui.renderers.secundario.getStyles(config, block) : {};
        },
        content: function () { return {}; },
        text: function(config, block) { 
            return Gbn.ui.renderers.text ? Gbn.ui.renderers.text.getStyles(config, block) : {};
        }
    };

    function applyBlockStyles(block) {
        if (!block || !styleManager || !styleManager.update) { return; }
        var resolver = styleResolvers[block.role] || function () { return {}; };
        var computedStyles = resolver(block.config || {}, block) || {};
        styleManager.update(block, computedStyles);
    }
    


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
