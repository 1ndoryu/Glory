;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelRender = Gbn.ui.panelRender || {};

    /**
     * Estado del Panel de Edición
     * 
     * Centraliza el estado mutable del panel para evitar variables globales
     * dispersas y facilitar el debugging.
     * 
     * Estados manejados:
     * - currentEditingState: 'normal' | 'hover' | 'focus' (Fase 10)
     * - lastBlockId: ID del último bloque editado (para detectar cambios)
     * - lastActiveTab: Nombre del tab activo (para persistencia entre refrescos)
     */
    var panelState = {
        // Estado de edición actual (normal, hover, focus)
        currentEditingState: 'normal',
        
        // ID del último bloque editado (para detectar cambios de bloque)
        lastBlockId: null,
        
        // Tab activo persistido entre refrescos del panel
        lastActiveTab: null
    };

    /**
     * Obtiene el estado de edición actual
     * @returns {string} 'normal' | 'hover' | 'focus'
     */
    function getCurrentEditingState() {
        return panelState.currentEditingState;
    }

    /**
     * Establece el estado de edición
     * @param {string} state - 'normal' | 'hover' | 'focus'
     */
    function setCurrentEditingState(state) {
        panelState.currentEditingState = state;
    }

    /**
     * Obtiene el ID del último bloque editado
     * @returns {string|null}
     */
    function getLastBlockId() {
        return panelState.lastBlockId;
    }

    /**
     * Establece el ID del último bloque editado
     * @param {string|null} blockId
     */
    function setLastBlockId(blockId) {
        panelState.lastBlockId = blockId;
    }

    /**
     * Obtiene el nombre del tab activo
     * @returns {string|null}
     */
    function getLastActiveTab() {
        return panelState.lastActiveTab;
    }

    /**
     * Establece el tab activo
     * @param {string|null} tabName
     */
    function setLastActiveTab(tabName) {
        panelState.lastActiveTab = tabName;
    }

    /**
     * Resetea el estado al seleccionar un nuevo bloque
     * @param {Object} block - Nuevo bloque seleccionado
     */
    function resetForNewBlock(block) {
        if (block && block.id !== panelState.lastBlockId) {
            panelState.currentEditingState = 'normal';
            panelState.lastBlockId = block.id;
            panelState.lastActiveTab = null;
            
            // Limpiar clases de simulación del elemento
            if (block.element) {
                block.element.classList.remove('gbn-simulated-hover', 'gbn-simulated-focus');
            }
            
            return true; // Indica que hubo cambio de bloque
        }
        return false;
    }

    // API Pública
    Gbn.ui.panelRender.state = {
        getCurrentEditingState: getCurrentEditingState,
        setCurrentEditingState: setCurrentEditingState,
        getLastBlockId: getLastBlockId,
        setLastBlockId: setLastBlockId,
        getLastActiveTab: getLastActiveTab,
        setLastActiveTab: setLastActiveTab,
        resetForNewBlock: resetForNewBlock,
        // Acceso directo para compatibilidad
        get currentEditingState() { return panelState.currentEditingState; }
    };

})(window);
