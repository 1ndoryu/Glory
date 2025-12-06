;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};

    /**
     * Panel Render - Orquestador Principal
     * 
     * Este módulo es el punto de entrada para el renderizado del panel de edición.
     * 
     * REFACTORIZACIÓN (Diciembre 2025):
     * La lógica se dividió en módulos separados siguiendo SRP:
     * - panel-render/state.js: Estado del panel (editingState, lastBlockId, lastActiveTab)
     * - panel-render/style-resolvers.js: Mapa de resolvers por rol
     * - panel-render/state-selector.js: UI de selector Normal/Hover/Focus
     * - panel-render/tabs.js: Utilidades para tabs
     * - panel-render/config-updater.js: Lógica de actualización de config
     * - panel-render/theme-propagation.js: Propagación de cambios del tema
     * 
     * Este archivo ahora actúa como orquestador, delegando a los módulos específicos.
     */

    // Referencias a módulos internos
    var panelRender = Gbn.ui.panelRender = Gbn.ui.panelRender || {};

    // Referencias a utilidades compartidas
    var shared = Gbn.ui.renderers && Gbn.ui.renderers.shared;

    /**
     * Crea el elemento de resumen del bloque (ID, Rol, etc.)
     * Actualmente oculto por solicitud del usuario.
     * 
     * @param {Object} block - Bloque a describir
     * @returns {HTMLElement}
     */
    function createSummary(block) {
        var summary = document.createElement('div');
        summary.className = 'gbn-panel-block-summary';
        
        // [MOD] Usuario solicitó ocultar ID y Rol
        // Se mantiene la estructura por si se reactiva en el futuro
        
        if (block.meta && block.meta.postType) {
            var typeLabel = document.createElement('p');
            typeLabel.className = 'gbn-panel-block-type';
            typeLabel.textContent = 'Contenido: ' + block.meta.postType;
            summary.appendChild(typeLabel);
        }
        
        return summary;
    }

    /**
     * Renderiza los controles del panel para un bloque
     * 
     * @param {Object} block - Bloque a editar
     * @param {HTMLElement} container - Contenedor del panel
     */
    function renderBlockControls(block, container) {
        if (!container) return;
        
        // Obtener referencias a módulos
        var panelState = panelRender.state;
        var styleResolvers = panelRender.styleResolvers;
        var stateSelector = panelRender.stateSelector;
        var tabs = panelRender.tabs;
        
        // Reset estado si cambia de bloque
        if (panelState && panelState.resetForNewBlock) {
            panelState.resetForNewBlock(block);
        }
        
        // Guardar scroll position
        var savedScrollTop = container.scrollTop;
        
        container.innerHTML = '';
        
        // Ubicar contenedores externos (header para tabs, footer para estados)
        var tabsContainer = document.querySelector('.gbn-header-tabs-area');
        var footerStatesContainer = document.querySelector('.gbn-footer-states-area');
        
        // Limpiar contenedores externos
        if (tabsContainer) tabsContainer.innerHTML = '';
        if (footerStatesContainer) footerStatesContainer.innerHTML = '';

        // Renderizar selector de estados en el footer (si rol lo soporta)
        if (styleResolvers && styleResolvers.supportsStates && 
            styleResolvers.supportsStates(block.role)) {
            if (footerStatesContainer && stateSelector && stateSelector.render) {
                stateSelector.render(footerStatesContainer, block);
            }
        }
        
        // Obtener schema del bloque
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

        // Agrupar campos por tab
        var grouped = tabs.groupFieldsByTab(schema, 'Contenido');
        var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;

        if (!grouped.hasTabs) {
            // Sin tabs: lista plana
            var form = document.createElement('form');
            form.className = 'gbn-panel-form';
            tabs.applyFormStyles(form);
            
            schema.forEach(function(field) {
                var control = builder ? builder(block, field) : null;
                if (control) form.appendChild(control);
            });
            container.appendChild(form);
        } else {
            // Con tabs: usar módulo de tabs
            var lastActiveTab = panelState ? panelState.getLastActiveTab() : null;
            
            tabs.renderTabs({
                tabs: grouped.tabs,
                block: block,
                builder: builder,
                tabsContainer: tabsContainer,
                contentContainer: container,
                activeTab: lastActiveTab,
                onTabChange: function(tabName) {
                    if (panelState && panelState.setLastActiveTab) {
                        panelState.setLastActiveTab(tabName);
                    }
                }
            });
        }
        
        // Status de ayuda
        if (Gbn.ui.panel && Gbn.ui.panel.setStatus) {
            Gbn.ui.panel.setStatus('Edita las opciones y se aplicarán al instante');
        }

        // Restaurar scroll position
        if (savedScrollTop > 0) {
            setTimeout(function() {
                container.scrollTop = savedScrollTop;
            }, 0);
        }
    }

    /**
     * Actualiza un valor de configuración del bloque.
     * Delega al módulo configUpdater.
     */
    function updateConfigValue(block, path, value) {
        var configUpdater = panelRender.configUpdater;
        if (configUpdater && configUpdater.updateConfigValue) {
            return configUpdater.updateConfigValue(block, path, value);
        }
        console.warn('[Panel Render] configUpdater module not loaded');
        return block;
    }

    /**
     * Aplica estilos a un bloque.
     * Delega al módulo styleResolvers.
     */
    function applyBlockStyles(block) {
        var styleResolvers = panelRender.styleResolvers;
        if (styleResolvers && styleResolvers.applyBlockStyles) {
            styleResolvers.applyBlockStyles(block);
        }
    }

    /**
     * Aplica estilos del tema a todos los bloques.
     * Delega al módulo themePropagation.
     */
    function applyThemeStylesToAllBlocks() {
        var themePropagation = panelRender.themePropagation;
        if (themePropagation && themePropagation.applyThemeStylesToAllBlocks) {
            themePropagation.applyThemeStylesToAllBlocks();
        }
    }

    /**
     * Obtiene el estado de edición actual
     */
    function getCurrentState() {
        var panelState = panelRender.state;
        return panelState ? panelState.getCurrentEditingState() : 'normal';
    }

    /**
     * Obtiene un valor de Theme Settings
     */
    function getThemeSettingsValue(role, property) {
        if (shared && shared.getThemeSettingsValue) {
            return shared.getThemeSettingsValue(role, property);
        }
        return undefined;
    }

    /**
     * Obtiene config con fallback a Theme Settings
     */
    function getConfigWithThemeFallback(block, path) {
        if (shared && shared.getConfigWithThemeFallback) {
            return shared.getConfigWithThemeFallback(block, path);
        }
        return undefined;
    }

    // API Pública (manteniendo compatibilidad con código existente)
    Gbn.ui.panelRender = Gbn.ui.panelRender || {};
    Gbn.ui.panelRender.renderBlockControls = renderBlockControls;
    Gbn.ui.panelRender.updateConfigValue = updateConfigValue;
    Gbn.ui.panelRender.applyBlockStyles = applyBlockStyles;
    Gbn.ui.panelRender.applyThemeStylesToAllBlocks = applyThemeStylesToAllBlocks;
    Gbn.ui.panelRender.getThemeSettingsValue = getThemeSettingsValue;
    Gbn.ui.panelRender.getConfigWithThemeFallback = getConfigWithThemeFallback;
    Gbn.ui.panelRender.getCurrentState = getCurrentState;
    Gbn.ui.panelRender.createSummary = createSummary;

})(window);
