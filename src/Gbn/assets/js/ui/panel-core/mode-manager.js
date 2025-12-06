;(function (global) {
    'use strict';

    /**
     * panel-core/mode-manager.js - Manejo de transiciones de modo
     * 
     * Centraliza TODA la lógica de transición entre modos del panel.
     * Esto es CRÍTICO para evitar bugs como:
     * - BUG-002: Tabs duplicados al cambiar de modo
     * - Bug de docking persistente
     * 
     * Parte del REFACTOR-003: Refactorización de panel-core.js
     * 
     * @module panel-core/mode-manager
     */

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelCore = Gbn.ui.panelCore || {};

    var stateModule = Gbn.ui.panelCore.state;

    /**
     * Limpia completamente el estado del modo anterior antes de cambiar.
     * Esta función es CRÍTICA para evitar el bug de docking persistente.
     * 
     * [FIX BUG-002] También limpia las áreas de tabs y estados del header/footer
     * para evitar que tabs de un modo anterior persistan en el nuevo modo.
     */
    function cleanupCurrentMode() {
        var state = stateModule.get();
        var currentMode = state.mode;
        
        if (utils && utils.debug) {
            utils.debug('[Panel] Limpiando modo actual: ' + currentMode);
        }
        
        // 1. Limpiar estado específico de Theme Settings
        if (currentMode === 'theme') {
            if (Gbn.ui.theme && Gbn.ui.theme.render && Gbn.ui.theme.render.resetState) {
                Gbn.ui.theme.render.resetState();
                if (utils && utils.debug) {
                    utils.debug('[Panel] Estado de Theme Settings reseteado');
                }
            }
        }
        
        // 2. [FIX BUG-002] Limpiar área de tabs del header
        // Esto previene que las tabs del panel anterior persistan
        var tabsContainer = document.querySelector('.gbn-panel-header-tabs-area');
        if (tabsContainer) {
            tabsContainer.innerHTML = '';
        }
        
        // 3. [FIX BUG-002] Limpiar área de estados del footer
        var footerStatesContainer = document.querySelector('.gbn-footer-states-area');
        if (footerStatesContainer) {
            footerStatesContainer.innerHTML = '';
        }
        
        // 4. Limpiar clases de simulación del bloque activo
        if (state.activeBlock && state.activeBlock.element) {
            state.activeBlock.element.classList.remove('gbn-simulated-hover', 'gbn-simulated-focus');
        }
        
        // 5. Limpiar bloque activo usando el helper de activeBlock
        if (Gbn.ui.panelCore.activeBlock && Gbn.ui.panelCore.activeBlock.set) {
            Gbn.ui.panelCore.activeBlock.set(null);
        }
    }

    /**
     * Configura la UI del panel para el nuevo modo.
     * Centraliza toda la lógica común de apertura.
     * 
     * @param {string} newMode - El nuevo modo del panel
     * @param {string} panelClass - La clase CSS a agregar al panel
     * @param {string} title - El título a mostrar en el header
     */
    function setupPanelForMode(newMode, panelClass, title) {
        // Asegurar que el panel esté montado
        if (Gbn.ui.panelCore.dom && Gbn.ui.panelCore.dom.ensureMounted) {
            Gbn.ui.panelCore.dom.ensureMounted();
        }
        
        // [CRÍTICO] Primero limpiar el modo anterior
        cleanupCurrentMode();
        
        var state = stateModule.get();
        
        // Actualizar el modo
        stateModule.set('mode', newMode);
        
        if (state.root) {
            // Abrir el panel
            state.root.classList.add('is-open');
            state.root.setAttribute('aria-hidden', 'false');
            
            // [CRÍTICO] Agregar clase de docking al body
            document.body.classList.add('gbn-panel-open');
            
            // Limpiar TODAS las clases de modo anteriores
            var modeClasses = stateModule.getModeClasses();
            for (var i = 0; i < modeClasses.length; i++) {
                state.root.classList.remove(modeClasses[i]);
            }
            
            // Agregar la clase del nuevo modo
            if (panelClass) {
                state.root.classList.add(panelClass);
            }
        }
        
        // Actualizar título
        if (state.title) {
            state.title.textContent = title || 'GBN Panel';
        }
        
        if (utils && utils.debug) {
            utils.debug('[Panel] Modo cambiado a: ' + newMode);
        }
    }

    // === EXPONER API ===
    Gbn.ui.panelCore.modeManager = {
        cleanup: cleanupCurrentMode,
        setup: setupPanelForMode
    };

})(window);
