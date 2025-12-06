;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelRender = Gbn.ui.panelRender || {};

    /**
     * Theme Propagation - Propagación de cambios del tema a bloques
     * 
     * Este módulo maneja:
     * - Aplicar defaults del tema a bloques que no tienen override
     * - Escuchar eventos de cambio de tema y breakpoint
     * - Re-aplicar estilos cuando cambia el contexto
     */

    var state = Gbn.state;

    /**
     * Aplica los nuevos defaults del tema a todos los bloques que no tienen override
     * 
     * @param {string} role - Rol del componente afectado
     * @param {string} property - Propiedad modificada
     * @param {*} value - Nuevo valor
     */
    function applyThemeDefaultsToBlocks(role, property, value) {
        if (!state || !state.all) return;
        
        var blocks = state.all();
        var styleResolvers = Gbn.ui.panelRender.styleResolvers;
        
        blocks.forEach(function(block) {
            if (block.role !== role) return;
            
            // Verificar si el bloque tiene un override para esta propiedad
            var hasOverride = false;
            var currentConfig = block.config || {};
            
            // Navegar por el objeto config
            var segments = property.split('.');
            var cursor = currentConfig;
            for (var i = 0; i < segments.length; i++) {
                if (cursor === undefined || cursor === null) break;
                cursor = cursor[segments[i]];
            }
            
            // Si cursor tiene valor, es un override
            if (cursor !== undefined && cursor !== null && cursor !== '') {
                hasOverride = true;
            }
            
            // Si NO tiene override, re-aplicar estilos
            if (!hasOverride && styleResolvers && styleResolvers.applyBlockStyles) {
                styleResolvers.applyBlockStyles(block);
            }
        });
    }

    /**
     * Aplica los estilos basados en Theme Settings a todos los bloques existentes
     */
    function applyThemeStylesToAllBlocks() {
        if (!state || !state.all) return;
        
        var blocks = state.all();
        var styleResolvers = Gbn.ui.panelRender.styleResolvers;
        
        if (!styleResolvers || !styleResolvers.applyBlockStyles) return;
        
        blocks.forEach(function(block) {
            if (block.role === 'principal' || block.role === 'secundario') {
                styleResolvers.applyBlockStyles(block);
            }
        });
    }

    /**
     * Inicializa los listeners de eventos globales
     */
    function initEventListeners() {
        if (typeof window === 'undefined') return;
        
        // Listener: Cambios en defaults del tema
        window.addEventListener('gbn:themeDefaultsChanged', function(e) {
            if (e.detail && e.detail.role) {
                applyThemeDefaultsToBlocks(e.detail.role, e.detail.property, e.detail.value);
            }
        });
        
        // Listener: Cambio de breakpoint
        window.addEventListener('gbn:breakpointChanged', function(e) {
            var bp = e.detail ? e.detail.current : 'desktop';
            
            // 1. Re-aplicar variables CSS del tema
            if (Gbn.ui.theme.applicator && Gbn.ui.theme.applicator.applyThemeSettings) {
                var settings = (Gbn.config && Gbn.config.themeSettings) || 
                               (gloryGbnCfg && gloryGbnCfg.themeSettings);
                if (settings) {
                    Gbn.ui.theme.applicator.applyThemeSettings(settings, bp);
                }
            }

            // 2. Re-aplicar estilos inline a bloques
            applyThemeStylesToAllBlocks();
            
            // 3. Actualizar panel activo
            if (Gbn.ui.panel && Gbn.ui.panel.refreshControls && state.activeBlockId) {
                var block = state.get(state.activeBlockId);
                if (block) Gbn.ui.panel.refreshControls(block);
            }
        });
    }

    // Inicializar listeners
    initEventListeners();

    // API Pública
    Gbn.ui.panelRender.themePropagation = {
        applyThemeDefaultsToBlocks: applyThemeDefaultsToBlocks,
        applyThemeStylesToAllBlocks: applyThemeStylesToAllBlocks
    };

})(window);
