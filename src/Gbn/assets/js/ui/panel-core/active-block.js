;(function (global) {
    'use strict';

    /**
     * panel-core/active-block.js - Manejo del bloque activo
     * 
     * Controla qué bloque está actualmente seleccionado para edición.
     * Maneja las clases CSS de estado activo en los elementos.
     * 
     * Parte del REFACTOR-003: Refactorización de panel-core.js
     * 
     * @module panel-core/active-block
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelCore = Gbn.ui.panelCore || {};

    var stateModule = Gbn.ui.panelCore.state;

    /**
     * Establece el bloque activo para edición.
     * Limpia el estado del bloque anterior y aplica clases al nuevo.
     * 
     * @param {Object|null} nextBlock - El nuevo bloque activo (o null para deseleccionar)
     */
    function setActiveBlock(nextBlock) {
        var state = stateModule.get();
        var currentBlock = state.activeBlock;
        
        // Limpiar estado del bloque anterior
        if (currentBlock && currentBlock.element) {
            currentBlock.element.classList.remove('gbn-block-active');
            currentBlock.element.classList.remove('gbn-simulated-hover', 'gbn-simulated-focus');
            
            // Limpiar botón de control si existe
            if (currentBlock.element.__gbnBtn) {
                currentBlock.element.__gbnBtn.classList.remove('is-active');
            }
        }
        
        // Actualizar estado
        stateModule.set('activeBlock', nextBlock || null);
        
        // Aplicar estado al nuevo bloque
        if (nextBlock && nextBlock.element) {
            nextBlock.element.classList.add('gbn-block-active');
            
            // Activar botón de control si existe
            if (nextBlock.element.__gbnBtn) {
                nextBlock.element.__gbnBtn.classList.add('is-active');
            }
        }
    }

    /**
     * Obtiene el bloque actualmente activo.
     * @returns {Object|null} El bloque activo o null
     */
    function getActiveBlock() {
        var state = stateModule.get();
        return state.activeBlock;
    }

    // === EXPONER API ===
    Gbn.ui.panelCore.activeBlock = {
        set: setActiveBlock,
        get: getActiveBlock
    };

})(window);
