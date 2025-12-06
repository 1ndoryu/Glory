;(function (global) {
    'use strict';

    /**
     * INSPECTOR - STATE MODULE
     * 
     * Maneja el estado del inspector (activo/inactivo, locked).
     * 
     * @module Gbn.ui.inspector.state
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.inspectorModules = Gbn.ui.inspectorModules || {};

    // Estado privado
    var active = false;
    var isLocked = false;
    var cfg = {};

    /**
     * Obtiene la clave de almacenamiento para el estado.
     * 
     * @returns {string|null} Clave de localStorage o null
     */
    function getStoreKey() {
        if (!cfg || !cfg.isEditor) { return null; }
        var parts = ['gbn-active'];
        if (cfg.userId) { parts.push(String(cfg.userId)); }
        parts.push(String(cfg.pageId || 'global'));
        return parts.join('-');
    }

    /**
     * Persiste el estado en localStorage.
     */
    function persistState() {
        var key = getStoreKey();
        if (!key) { return; }
        try { 
            global.localStorage.setItem(key, active ? '1' : '0'); 
        } catch (_) {}
    }

    /**
     * Lee el estado almacenado en localStorage.
     * 
     * @returns {boolean|null} Estado almacenado o null
     */
    function readStoredState() {
        var key = getStoreKey();
        if (!key) { return null; }
        try {
            var stored = global.localStorage.getItem(key);
            if (stored === '1') { return true; }
            if (stored === '0') { return false; }
        } catch (_) {}
        return null;
    }

    // API del módulo
    var inspectorState = {
        /**
         * Configura las opciones del inspector.
         * 
         * @param {Object} options - Opciones de configuración
         */
        configure: function(options) {
            cfg = options || {};
        },

        /**
         * Obtiene la configuración actual.
         * 
         * @returns {Object}
         */
        getConfig: function() {
            return cfg;
        },

        /**
         * Verifica si el inspector está activo.
         * 
         * @returns {boolean}
         */
        isActive: function() {
            return active;
        },

        /**
         * Establece el estado activo del inspector.
         * 
         * @param {boolean} value - Nuevo estado
         */
        setActive: function(value) {
            active = !!value;
            persistState();
        },

        /**
         * Verifica si el inspector está bloqueado.
         * 
         * @returns {boolean}
         */
        isLocked: function() {
            return isLocked;
        },

        /**
         * Establece el estado de bloqueo del inspector.
         * 
         * @param {boolean} value - Nuevo estado
         */
        setLocked: function(value) {
            isLocked = !!value;
        },

        /**
         * Lee el estado almacenado.
         * 
         * @returns {boolean|null}
         */
        readStoredState: readStoredState,

        /**
         * Persiste el estado actual.
         */
        persistState: persistState
    };

    // Exportar módulo
    Gbn.ui.inspectorModules.state = inspectorState;

})(typeof window !== 'undefined' ? window : this);
