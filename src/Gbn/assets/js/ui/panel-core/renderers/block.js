;(function (global) {
    'use strict';

    /**
     * panel-core/renderers/block.js - Renderer para panel de bloques
     * 
     * Maneja la renderización del panel cuando se edita un bloque específico.
     * Determina la clase CSS basada en el rol del bloque.
     * 
     * Parte del REFACTOR-003: Refactorización de panel-core.js
     * 
     * @module panel-core/renderers/block
     */

    var Gbn = global.Gbn = global.Gbn || {};
    var utils = Gbn.utils;
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelCore = Gbn.ui.panelCore || {};
    Gbn.ui.panelCore.renderers = Gbn.ui.panelCore.renderers || {};

    var stateModule = Gbn.ui.panelCore.state;
    var modeManager = Gbn.ui.panelCore.modeManager;
    var activeBlockModule = Gbn.ui.panelCore.activeBlock;
    var domModule = Gbn.ui.panelCore.dom;

    /**
     * Renderiza el panel para editar un bloque específico.
     * 
     * @param {Object} block - El bloque a editar
     */
    function renderBlockPanel(block) {
        // Determinar clase CSS basada en el rol del bloque
        var modeClass = 'gbn-panel-component';
        if (block && block.role === 'principal') {
            modeClass = 'gbn-panel-primary';
        } else if (block && block.role === 'secundario') {
            modeClass = 'gbn-panel-secondary';
        }
        
        // Determinar título del panel
        var title = 'GBN Panel';
        if (block) {
            if (block.meta && block.meta.label) { 
                title = block.meta.label; 
            } else if (block.role) { 
                title = 'Configuración: ' + block.role; 
            }
        }
        
        // Configurar panel para el nuevo modo
        modeManager.setup('block', modeClass, title);
        activeBlockModule.set(block);
        
        var state = stateModule.get();
        
        if (!state.body) { return; }
        
        if (!block) { 
            domModule.renderPlaceholder(); 
            return; 
        }
        
        // Delegar renderizado de controles a panel-render.js
        if (Gbn.ui.panelRender && Gbn.ui.panelRender.renderBlockControls) {
            Gbn.ui.panelRender.renderBlockControls(block, state.body);
        } else {
            state.body.innerHTML = 'Error: panelRender no disponible';
        }
        
        if (utils && utils.debug) {
            utils.debug('[Panel] Bloque abierto: ' + (block ? block.id : null));
        }
    }

    // === EXPONER API ===
    Gbn.ui.panelCore.renderers.block = renderBlockPanel;

})(window);
