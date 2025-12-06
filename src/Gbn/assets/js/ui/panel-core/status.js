;(function (global) {
    'use strict';

    /**
     * panel-core/status.js - Funciones de estado y notificación
     * 
     * Maneja los mensajes de estado del panel (footer status).
     * Proporciona funciones para mensajes persistentes y flash (temporales).
     * 
     * Parte del REFACTOR-003: Refactorización de panel-core.js
     * 
     * @module panel-core/status
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelCore = Gbn.ui.panelCore || {};

    var stateModule = Gbn.ui.panelCore.state;

    /**
     * Establece el texto de estado del panel (persistente).
     * @param {string} text - Texto a mostrar
     */
    function setPanelStatus(text) {
        var state = stateModule.get();
        if (state.notice) {
            state.notice.textContent = text;
        }
    }

    /**
     * Muestra un mensaje flash temporal (1.6 segundos).
     * Útil para feedback de acciones como "Guardado", "Error", etc.
     * @param {string} text - Texto a mostrar temporalmente
     */
    function flashPanelStatus(text) {
        var state = stateModule.get();
        if (!state.notice) { return; }
        
        state.notice.textContent = text;
        
        // Limpiar timer anterior si existe
        if (state.statusTimer) { 
            clearTimeout(state.statusTimer); 
        }
        
        // Restaurar mensaje por defecto después de 1.6 segundos
        var timer = setTimeout(function () {
            state.notice.textContent = 'Cambios en vivo';
        }, 1600);
        
        stateModule.set('statusTimer', timer);
    }

    // === EXPONER API ===
    Gbn.ui.panelCore.status = {
        set: setPanelStatus,
        flash: flashPanelStatus
    };

})(window);
