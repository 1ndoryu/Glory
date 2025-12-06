;(function (global) {
    'use strict';

    /**
     * panel-core.js - Orquestador del Panel GBN (Refactorizado)
     * 
     * Este archivo es el punto de entrada del sistema de panel.
     * Orquesta los módulos especializados y expone la API pública.
     * 
     * REFACTOR-003 Completado:
     * - Antes: 644 líneas en un solo archivo
     * - Después: ~120 líneas + 8 módulos especializados
     * 
     * Módulos del sistema:
     * - panel-core/state.js          → Estado privado del panel
     * - panel-core/status.js         → Funciones de notificación
     * - panel-core/active-block.js   → Manejo de bloque activo
     * - panel-core/mode-manager.js   → Transiciones de modo
     * - panel-core/dom.js            → Montaje y listeners
     * - panel-core/renderers/block.js   → Render de bloque
     * - panel-core/renderers/theme.js   → Render de tema
     * - panel-core/renderers/page.js    → Render de página
     * - panel-core/renderers/restore.js → Render de restauración
     * 
     * @module panel-core
     */

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;

    // Verificar que los módulos necesarios estén cargados
    var panelCore = Gbn.ui.panelCore;
    if (!panelCore) {
        console.error('[panel-core] Error: módulos de panelCore no cargados');
        return;
    }

    var stateModule = panelCore.state;
    var statusModule = panelCore.status;
    var activeBlockModule = panelCore.activeBlock;
    var modeManager = panelCore.modeManager;
    var domModule = panelCore.dom;
    var renderers = panelCore.renderers;

    // === API PÚBLICA DEL PANEL ===

    var panel = {
        /**
         * Inicializa el panel (monta en el DOM si es necesario).
         */
        init: function () { 
            domModule.ensureMounted(); 
        },
        
        /**
         * Verifica si el panel está abierto.
         * @returns {boolean} True si está abierto
         */
        isOpen: function () { 
            var state = stateModule.get();
            return !!(state.root && state.root.classList.contains('is-open')); 
        },
        
        /**
         * Abre el panel para editar un bloque.
         * @param {Object} block - El bloque a editar
         */
        open: function (block) {
            renderers.block(block);
        },
        
        /**
         * Abre el panel de configuración del tema.
         */
        openTheme: function () {
            renderers.theme();
        },
        
        /**
         * Abre el panel de configuración de página.
         */
        openPage: function () {
            renderers.page();
        },
        
        /**
         * Abre el panel de restauración de valores.
         */
        openRestore: function () {
            renderers.restore();
        },
        
        /**
         * Cierra el panel y limpia completamente el estado.
         */
        close: function () {
            var state = stateModule.get();
            
            if (utils && utils.debug) {
                utils.debug('[Panel] Cerrando, modo actual: ' + state.mode);
            }
            
            // Limpiar el estado del modo actual
            try {
                modeManager.cleanup();
            } catch (e) {
                console.error('[Panel] Error durante limpieza de modo:', e);
            }
            
            if (state.root) { 
                state.root.classList.remove('is-open'); 
                state.root.setAttribute('aria-hidden', 'true');
                
                // Limpiar TODAS las clases de modo
                var modeClasses = stateModule.getModeClasses();
                for (var i = 0; i < modeClasses.length; i++) {
                    state.root.classList.remove(modeClasses[i]);
                }
            }
            
            // Remover la clase de docking del body
            document.body.classList.remove('gbn-panel-open');
            
            domModule.renderPlaceholder(); 
            stateModule.set('mode', 'idle');
            
            if (utils && utils.debug) {
                utils.debug('[Panel] Cerrado exitosamente, modo: idle');
            }
        },
        
        // API adicional para otros módulos
        setStatus: statusModule.set,
        flashStatus: statusModule.flash,
        updateActiveBlock: activeBlockModule.set,
        getActiveBlock: activeBlockModule.get,
        
        /**
         * Refresca los controles del panel para un bloque.
         * @param {Object} block - El bloque a refrescar
         */
        refreshControls: function(block) {
            var state = stateModule.get();
            if (state.activeBlock && state.activeBlock.id === block.id && state.body) {
                if (Gbn.ui.panelRender && Gbn.ui.panelRender.renderBlockControls) {
                    Gbn.ui.panelRender.renderBlockControls(block, state.body);
                }
            }
        },
        
        /**
         * Obtiene el modo actual del panel.
         * @returns {string} Modo actual ('idle', 'block', 'theme', 'page', 'restore')
         */
        getMode: function() { 
            var state = stateModule.get();
            return state.mode; 
        }
    };

    // === API PARA PANEL-FIELDS.JS ===
    // Expone métodos que necesitan los campos del panel
    
    var panelApi = {
        getActiveBlock: activeBlockModule.get,
        
        updateConfigValue: function (block, path, value) { 
            if (Gbn.ui.panelRender && Gbn.ui.panelRender.updateConfigValue) {
                return Gbn.ui.panelRender.updateConfigValue(block, path, value);
            }
        },
        
        flashStatus: statusModule.flash,
        
        applyBlockStyles: function (block) { 
            if (Gbn.ui.panelRender && Gbn.ui.panelRender.applyBlockStyles) {
                return Gbn.ui.panelRender.applyBlockStyles(block);
            }
        }
    };

    // Exponer APIs
    Gbn.ui.panelApi = panelApi;
    Gbn.ui.panel = panel;

})(window);
