;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelRender = Gbn.ui.panelRender || {};

    /**
     * Config Updater - Lógica de actualización de configuración
     * 
     * Este módulo contiene la lógica compleja para actualizar la configuración
     * de un bloque, incluyendo:
     * - Actualización de estados (hover/focus) - Fase 10
     * - Delegación a renderers específicos
     * - Lógica responsive
     * - Actualización genérica de desktop
     */

    var utils = Gbn.utils;
    var state = Gbn.state;
    var styleManager = Gbn.styleManager;

    /**
     * Clona la configuración de un bloque profundamente
     */
    function cloneConfig(config) {
        var shared = Gbn.ui.renderers && Gbn.ui.renderers.shared;
        return shared && shared.cloneConfig 
            ? shared.cloneConfig(config) 
            : JSON.parse(JSON.stringify(config || {}));
    }

    /**
     * Mapea direcciones de spacing español a CSS
     */
    var spacingDirectionMap = {
        'superior': 'Top',
        'derecha': 'Right',
        'inferior': 'Bottom',
        'izquierda': 'Left'
    };

    /**
     * Obtiene la propiedad CSS para un path de config
     * @param {string} path - Path de configuración
     * @returns {string} Nombre de propiedad CSS
     */
    function getCssPropertyForPath(path) {
        var fieldUtils = Gbn.ui.fieldUtils || {};
        var map = fieldUtils.CONFIG_TO_CSS_MAP || {};
        var cssProp = map[path];
        
        // Manejo especial para spacing (padding.*, margin.*)
        if (!cssProp && (path.indexOf('padding.') === 0 || path.indexOf('margin.') === 0)) {
            var parts = path.split('.');
            var spType = parts[0];
            var dir = parts[1];
            
            if (spacingDirectionMap[dir]) {
                cssProp = spType + spacingDirectionMap[dir];
            }
        }
        
        // Fallback: usar el último segmento del path
        if (!cssProp) {
            var segments = path.split('.');
            cssProp = segments[segments.length - 1];
        }
        
        return cssProp;
    }

    /**
     * Actualiza un valor de configuración en un estado (hover/focus)
     */
    function updateStateValue(block, path, value, editingState) {
        var cssProp = getCssPropertyForPath(path);
        
        if (Gbn.services.stateStyles && Gbn.services.stateStyles.setStateProperty) {
            Gbn.services.stateStyles.setStateProperty(block, editingState, cssProp, value);
            
            var updatedBlock = state.get(block.id);
            var targetElement = (updatedBlock && updatedBlock.element) || block.element;
            
            if (targetElement) {
                var simClass = 'gbn-simulated-' + editingState;
                if (!targetElement.classList.contains(simClass)) {
                    targetElement.classList.add(simClass);
                }
            }
            
            // Construir estilos del estado
            var stateStyles = {};
            stateStyles[cssProp] = value;
            
            if (updatedBlock && updatedBlock.config && updatedBlock.config._states && 
                updatedBlock.config._states[editingState]) {
                stateStyles = Object.assign({}, updatedBlock.config._states[editingState]);
            }
            
            // Aplicar visualmente
            var targetBlock = updatedBlock || block;
            if (styleManager && styleManager.applyStateCss && targetBlock.id) {
                styleManager.applyStateCss(targetBlock, editingState, stateStyles);
            }
            
            // Disparar evento
            dispatchConfigChanged(block.id, { state: editingState });
            
            // Feedback visual
            if (Gbn.ui.panel && Gbn.ui.panel.flashStatus) {
                Gbn.ui.panel.flashStatus('Cambio en ' + editingState + ' aplicado');
            }
            
            return updatedBlock || block;
        }
        
        return block;
    }

    /**
     * Dispara el evento gbn:configChanged
     */
    function dispatchConfigChanged(blockId, detail) {
        detail = detail || {};
        detail.id = blockId;
        
        var event;
        if (typeof global.CustomEvent === 'function') {
            event = new CustomEvent('gbn:configChanged', { detail: detail });
        } else {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent('gbn:configChanged', false, false, detail);
        }
        global.dispatchEvent(event);
    }

    /**
     * Actualiza configuración a través de un renderer específico
     */
    function updateViaRenderer(block, path, value, role) {
        var result = Gbn.ui.renderers[role].handleUpdate(block, path, value);
        
        if (result === true) {
            if (Gbn.log) Gbn.log.info('Renderer handled update completely', { role: role });
            
            // Renderers auto-gestionados
            var selfManagedRenderers = ['themeSettings', 'pageSettings'];
            if (selfManagedRenderers.indexOf(role) !== -1) {
                return state.get(block.id);
            }
            
            // Guardar valor en config del state
            var freshBlock = state.get(block.id) || block;
            var current = cloneConfig(freshBlock.config);
            setNestedValue(current, path, value);
            var updated = state.updateConfig(block.id, current);
            
            // Disparar evento
            dispatchConfigChanged(block.id, { path: path });
            
            // Refresh si es campo condicional
            checkConditionalRefresh(updated, path);
            
            return updated;
            
        } else if (typeof result === 'object') {
            if (Gbn.log) Gbn.log.info('Renderer returned updated config', { role: role });
            dispatchConfigChanged(block.id, { path: path });
            return result;
        }
        
        return null; // Indica que el renderer no manejó completamente
    }

    /**
     * Establece un valor anidado en un objeto
     */
    function setNestedValue(obj, path, value) {
        var segments = path.split('.');
        var cursor = obj;
        
        for (var i = 0; i < segments.length - 1; i++) {
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
    }

    /**
     * Verifica si necesita refresh por campo condicional
     */
    function checkConditionalRefresh(block, path) {
        var conditionalTriggers = ['hasBorder', 'layout', 'display_mode', 'img_show', 'title_show', 'interaccion_modo', 'fieldType', 'logoMode'];
        if (conditionalTriggers.indexOf(path) !== -1) {
            if (Gbn.ui.panel && Gbn.ui.panel.refreshControls) {
                Gbn.ui.panel.refreshControls(block);
            }
        }
    }

    /**
     * Actualización responsive (no-desktop)
     */
    function updateResponsive(block, path, value, breakpoint) {
        var freshBlock = state.get(block.id) || block;
        var current = cloneConfig(freshBlock.config);
        
        var mockBlock = { config: current };
        Gbn.responsive.setResponsiveValue(mockBlock, path, value, breakpoint);
        
        var updated = state.updateConfig(block.id, current);
        
        var styleResolvers = Gbn.ui.panelRender.styleResolvers;
        if (styleResolvers && styleResolvers.applyBlockStyles) {
            styleResolvers.applyBlockStyles(updated);
        }
        
        if (Gbn.ui.panel && Gbn.ui.panel.updateActiveBlock) {
            Gbn.ui.panel.updateActiveBlock(updated);
        }
        
        if (Gbn.ui.panel && Gbn.ui.panel.flashStatus) {
            Gbn.ui.panel.flashStatus('Cambios aplicados (' + breakpoint + ')');
        }

        dispatchConfigChanged(block.id);
        checkConditionalRefresh(updated, path);
        
        return updated;
    }

    /**
     * Actualización genérica desktop
     */
    function updateDesktop(block, path, value) {
        var freshBlock = state.get(block.id) || block;
        var current = cloneConfig(freshBlock.config);
        
        setNestedValue(current, path, value);
        
        var updated = state.updateConfig(block.id, current);
        
        var styleResolvers = Gbn.ui.panelRender.styleResolvers;
        if (styleResolvers && styleResolvers.applyBlockStyles) {
            styleResolvers.applyBlockStyles(updated);
        }
        
        if (Gbn.ui.panel && Gbn.ui.panel.updateActiveBlock) {
            Gbn.ui.panel.updateActiveBlock(updated);
        }
        
        if (Gbn.ui.panel && Gbn.ui.panel.flashStatus) {
            Gbn.ui.panel.flashStatus('Cambios aplicados');
        }

        dispatchConfigChanged(block.id);
        checkConditionalRefresh(updated, path);
        
        return updated;
    }

    /**
     * Función principal de actualización de configuración
     * 
     * @param {Object} block - Bloque a actualizar
     * @param {string} path - Path de la propiedad
     * @param {*} value - Nuevo valor
     * @returns {Object} Bloque actualizado
     */
    function updateConfigValue(block, path, value) {
        if (!block || !path) return;

        var panelState = Gbn.ui.panelRender.state;
        var currentEditingState = panelState ? panelState.getCurrentEditingState() : 'normal';

        // 1. Actualización de estados (Hover/Focus)
        if (currentEditingState !== 'normal') {
            return updateStateValue(block, path, value, currentEditingState);
        }

        // 2. Delegar a renderer específico
        var role = block.id === 'page-settings' ? 'pageSettings' : 
                   block.id === 'theme-settings' ? 'themeSettings' : 
                   block.role;
                   
        if (role && Gbn.ui.renderers[role] && Gbn.ui.renderers[role].handleUpdate) {
            if (Gbn.log) Gbn.log.info('Delegating update to renderer', { role: role, path: path });
            var result = updateViaRenderer(block, path, value, role);
            if (result) return result;
        }

        // 3. Lógica responsive
        var breakpoint = (Gbn.responsive && Gbn.responsive.getCurrentBreakpoint) 
            ? Gbn.responsive.getCurrentBreakpoint() 
            : 'desktop';
        
        if (breakpoint !== 'desktop' && Gbn.responsive && Gbn.responsive.setResponsiveValue) {
            return updateResponsive(block, path, value, breakpoint);
        }

        // 4. Actualización desktop genérica
        return updateDesktop(block, path, value);
    }

    // API Pública
    Gbn.ui.panelRender.configUpdater = {
        updateConfigValue: updateConfigValue,
        getCssPropertyForPath: getCssPropertyForPath,
        dispatchConfigChanged: dispatchConfigChanged
    };

})(window);
