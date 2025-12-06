;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};

    /**
     * Estado global del módulo Theme Settings.
     * 
     * Este módulo centraliza el estado de navegación del panel de Theme Settings
     * para permitir persistencia entre cambios de breakpoint y re-renders.
     * 
     * Arquitectura:
     * - currentView: Vista actual ('menu', 'text', 'colors', 'pages', 'components')
     * - componentState: Estado de la vista de componentes (rol seleccionado, render function)
     * - activeConfigChangeHandler: Handler del evento configChanged para cleanup
     */
    var state = {
        // Vista actual del panel ('menu' = menú principal, otros = secciones)
        currentView: 'menu',
        
        // Estado de navegación de componentes (para Bug 6 fix - persistencia entre breakpoints)
        componentState: {
            currentDetailRole: null,
            renderComponentDetail: null
        },
        
        // Handler activo del evento configChanged (para cleanup y evitar memory leaks)
        activeConfigChangeHandler: null
    };

    /**
     * Obtiene la vista actual
     * @returns {string} ID de la vista actual
     */
    function getCurrentView() {
        return state.currentView;
    }

    /**
     * Establece la vista actual
     * @param {string} viewId - ID de la vista ('menu', 'text', 'colors', 'pages', 'components')
     */
    function setCurrentView(viewId) {
        state.currentView = viewId;
    }

    /**
     * Obtiene el estado de componentes
     * @returns {Object} Estado de componentes {currentDetailRole, renderComponentDetail}
     */
    function getComponentState() {
        return state.componentState;
    }

    /**
     * Actualiza el rol de componente actualmente seleccionado
     * @param {string|null} role - Rol del componente o null para limpiar
     */
    function setCurrentDetailRole(role) {
        state.componentState.currentDetailRole = role;
    }

    /**
     * Guarda la función renderComponentDetail para acceso desde listeners
     * @param {Function|null} fn - Función de render o null
     */
    function setRenderComponentDetail(fn) {
        state.componentState.renderComponentDetail = fn;
    }

    /**
     * Obtiene el handler activo de configChanged
     * @returns {Function|null}
     */
    function getActiveConfigChangeHandler() {
        return state.activeConfigChangeHandler;
    }

    /**
     * Establece el handler de configChanged (y limpia el anterior si existe)
     * @param {Function|null} handler - Nuevo handler o null para limpiar
     */
    function setActiveConfigChangeHandler(handler) {
        // Limpiar handler anterior si existe
        if (state.activeConfigChangeHandler) {
            window.removeEventListener('gbn:configChanged', state.activeConfigChangeHandler);
        }
        state.activeConfigChangeHandler = handler;
    }

    /**
     * Resetea todo el estado a valores iniciales.
     * Debe llamarse cuando se cierra el panel para evitar estados residuales.
     */
    function resetState() {
        state.currentView = 'menu';
        state.componentState.currentDetailRole = null;
        state.componentState.renderComponentDetail = null;
        
        // Limpiar listener para evitar memory leaks
        if (state.activeConfigChangeHandler) {
            window.removeEventListener('gbn:configChanged', state.activeConfigChangeHandler);
            state.activeConfigChangeHandler = null;
        }
    }

    // API Pública
    Gbn.ui.theme.state = {
        getCurrentView: getCurrentView,
        setCurrentView: setCurrentView,
        getComponentState: getComponentState,
        setCurrentDetailRole: setCurrentDetailRole,
        setRenderComponentDetail: setRenderComponentDetail,
        getActiveConfigChangeHandler: getActiveConfigChangeHandler,
        setActiveConfigChangeHandler: setActiveConfigChangeHandler,
        resetState: resetState
    };

})(window);
