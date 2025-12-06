;(function (global) {
    'use strict';

    /**
     * panel-core/renderers/page.js - Renderer para panel de página
     * 
     * Maneja la renderización del panel de configuración de página.
     * Similar a theme pero con settings específicos de la página actual.
     * 
     * Parte del REFACTOR-003: Refactorización de panel-core.js
     * 
     * @module panel-core/renderers/page
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
     * Renderiza el panel de configuración de página.
     * Usa cache local si existe, sino carga desde servidor.
     */
    function renderPagePanel() {
        modeManager.setup('page', 'gbn-panel-page', 'Configuración de Página');
        
        var state = stateModule.get();
        if (!state.body) { return; }
        
        var localSettings = Gbn.config && Gbn.config.pageSettings;
        
        if (localSettings && Object.keys(localSettings).length > 0) {
            if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderPageSettingsForm) {
                var currentFooter = state.root.querySelector('.gbn-footer-primary');
                if (currentFooter) stateModule.set('footer', currentFooter);
                state = stateModule.get();
                Gbn.ui.panelTheme.renderPageSettingsForm(localSettings, state.body, state.footer);
            }
            statusModule.set('Listo (desde cache local)');
            return;
        }
        
        state.body.innerHTML = '<div class="gbn-panel-loading">Cargando configuración...</div>';
        stateModule.set('form', null);
        statusModule.set('Cargando...');

        if (Gbn.persistence && typeof Gbn.persistence.getPageSettings === 'function') {
            Gbn.persistence.getPageSettings().then(function(res) {
                // [FIX] Si el panel ya no está en modo page (usuario lo cerró), abortar.
                state = stateModule.get();
                if (state.mode !== 'page') {
                    if (utils && utils.debug) {
                        utils.debug('[Panel] Carga de página abortada: panel cerrado o cambio de modo');
                    }
                    return;
                }

                if (res && res.success) {
                    var settings = res.data || {};
                    if (!Gbn.config) Gbn.config = {};
                    Gbn.config.pageSettings = settings;
                    
                    if (Gbn.ui.panelTheme && Gbn.ui.panelTheme.renderPageSettingsForm) {
                        var currentFooter = state.root.querySelector('.gbn-footer-primary');
                        if (currentFooter) stateModule.set('footer', currentFooter);
                        state = stateModule.get();
                        Gbn.ui.panelTheme.renderPageSettingsForm(settings, state.body, state.footer);
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
    Gbn.ui.panelCore.renderers.page = renderPagePanel;

})(window);
