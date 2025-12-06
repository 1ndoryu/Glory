;(function (global) {
    'use strict';

    /**
     * panel-core/state.js - Estado privado del panel
     * 
     * Centraliza todo el estado mutable del panel en un solo módulo
     * para facilitar el debugging y prevenir inconsistencias.
     * 
     * Parte del REFACTOR-003: Refactorización de panel-core.js (644→~110 líneas)
     * 
     * @module panel-core/state
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelCore = Gbn.ui.panelCore || {};

    // === ESTADO DEL PANEL (Privado) ===
    var panelState = {
        root: null,           // Elemento raíz del panel (#gbn-panel)
        body: null,           // Contenedor del body (.gbn-body)
        title: null,          // Elemento de título
        footer: null,         // Botón footer primario
        notice: null,         // Elemento de estado/notificación
        form: null,           // Formulario activo (si existe)
        activeBlock: null,    // Bloque actualmente editándose
        mode: 'idle',         // Modos: 'idle', 'block', 'theme', 'page', 'restore'
        statusTimer: null,    // Timer para mensajes flash
        listenersBound: false // Flag para evitar duplicar listeners
    };

    // Lista completa de clases de modo para limpieza
    var MODE_CLASSES = [
        'gbn-panel-primary',
        'gbn-panel-secondary',
        'gbn-panel-component',
        'gbn-panel-theme',
        'gbn-panel-page',
        'gbn-panel-restore'
    ];

    /**
     * Obtiene el estado completo del panel (solo lectura conceptual).
     * @returns {Object} Estado actual del panel
     */
    function getState() {
        return panelState;
    }

    /**
     * Actualiza una propiedad del estado.
     * @param {string} key - Clave a actualizar
     * @param {*} value - Nuevo valor
     */
    function setState(key, value) {
        if (panelState.hasOwnProperty(key)) {
            panelState[key] = value;
        }
    }

    /**
     * Obtiene las clases de modo disponibles.
     * @returns {string[]} Lista de clases CSS de modo
     */
    function getModeClasses() {
        return MODE_CLASSES;
    }

    // === EXPONER API ===
    Gbn.ui.panelCore.state = {
        get: getState,
        set: setState,
        getModeClasses: getModeClasses
    };

})(window);
