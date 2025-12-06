;(function (global) {
    'use strict';

    /**
     * panel-core/renderers/theme.js - Renderer para panel de tema
     * 
     * Maneja la renderización del panel de configuración del tema.
     * Carga settings desde el servidor o usa cache local si existe.
     * 
     * Parte del REFACTOR-003: Refactorización de panel-core.js
     * 
     * @module panel-core/renderers/theme
     */

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelCore = Gbn.ui.panelCore || {};
    Gbn.ui.panelCore.renderers = Gbn.ui.panelCore.renderers || {};

    var stateModule = Gbn.ui.panelCore.state;
    var modeManager = Gbn.ui.panelCore.modeManager;
    var statusModule = Gbn.ui.panelCore.status;

    /**
     * Renderiza el panel de configuración del tema.
     * Usa cache local si existe, sino carga desde servidor.
     */
    function renderThemePanel() {
        modeManager.setup('theme', 'gbn-panel-theme', 'Configuración del Tema');
        
        var state = stateModule.get();
        if (!state.body) { return; }
        
        // Verificar si hay estado local (cambios no guardados)
        var localSettings = Gbn.config && Gbn.config.themeSettings;
        
        if (localSettings && Object.keys(localSettings).length > 0) {
            if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderThemeSettingsForm) {
                var currentFooter = state.root.querySelector('.gbn-footer-primary');
                if (currentFooter) stateModule.set('footer', currentFooter);
                state = stateModule.get(); // Refrescar
                Gbn.ui.panelTheme.renderThemeSettingsForm(localSettings, state.body, state.footer);
            }
            statusModule.set('Listo (desde cache local)');
            return;
        }
        
        // Cargar del servidor
        state.body.innerHTML = '<div class="gbn-panel-loading">Cargando configuración...</div>';
        stateModule.set('form', null);
        statusModule.set('Cargando...');

        if (Gbn.persistence && typeof Gbn.persistence.getThemeSettings === 'function') {
            Gbn.persistence.getThemeSettings().then(function(res) {
                // [FIX] Si el panel ya no está en modo theme (usuario lo cerró), abortar.
                state = stateModule.get();
                if (state.mode !== 'theme') {
                    if (utils && utils.debug) {
                        utils.debug('[Panel] Carga de tema abortada: panel cerrado o cambio de modo');
                    }
                    return;
                }

                if (res && res.success) {
                    var settings = res.data || {};
                    if (!Gbn.config) Gbn.config = {};
                    Gbn.config.themeSettings = settings;
                    
                    if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderThemeSettingsForm) {
                        var currentFooter = state.root.querySelector('.gbn-footer-primary');
                        if (currentFooter) stateModule.set('footer', currentFooter);
                        state = stateModule.get();
                        Gbn.ui.panelTheme.renderThemeSettingsForm(settings, state.body, state.footer);
                    } else {
                        state.body.innerHTML = 'Error: panelTheme no disponible';
                    }
                    statusModule.set('Listo');
                } else {
                    state.body.innerHTML = '<div class="gbn-panel-error">Error al cargar configuración.</div>';
                    statusModule.set('Error');
                }
            }).catch(function() {
                state = stateModule.get();
                state.body.innerHTML = '<div class="gbn-panel-error">Error de conexión.</div>';
                statusModule.set('Error');
            });
        } else {
            state.body.innerHTML = '<div class="gbn-panel-error">Persistencia no disponible.</div>';
        }
    }

    // === EXPONER API ===
    Gbn.ui.panelCore.renderers.theme = renderThemePanel;

})(window);
