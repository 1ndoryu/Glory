;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelRender = Gbn.ui.panelRender || {};

    /**
     * Utilidades para renderizado de tabs en el panel.
     * DELEGADO a Gbn.ui.tabsManager (Refactorización REFACTOR-002)
     */
    
    var TabsManager = Gbn.ui.tabsManager;

    if (!TabsManager) {
        console.error('TabsManager not loaded!');
    }

    // Delegar funciones puras
    var getTabIcon = TabsManager ? TabsManager.getTabIcon : function() { return ''; };
    var groupFieldsByTab = TabsManager ? TabsManager.groupFieldsByTab : function() { return {}; };
    var sortTabNames = TabsManager ? TabsManager.sortTabNames : function(names) { return names; };
    var tabIconMap = TabsManager ? TabsManager.map : {};

    /**
     * Aplica estilos comunes a un formulario del panel.
     * Mantenido aquí por retrocompatibilidad específica del panel.
     * @param {HTMLElement} form
     */
    function applyFormStyles(form) {
        form.style.display = 'flex';
        form.style.flexDirection = 'column';
        form.style.gap = '12px';
        form.style.marginBottom = '100px'; // Prevenir overlap con footer
    }

    /**
     * Renderiza tabs con sus contenidos.
     * Adaptador para usar TabsManager.createTabsUI
     */
    function renderTabs(options) {
        if (!TabsManager) return {};

        var contentContainer = options.contentContainer;
        var externalTabsContainer = options.tabsContainer;

        // TabsManager.createTabsUI retorna elementos desconectados
        var ui = TabsManager.createTabsUI({
            tabs: options.tabs,
            block: options.block,
            builder: options.builder,
            activeTab: options.activeTab,
            onTabChange: options.onTabChange,
            formStyler: applyFormStyles // Pasar función de estilos específica
        });

        // Insertar en el DOM
        if (externalTabsContainer) {
            externalTabsContainer.appendChild(ui.tabNav);
        } else {
            contentContainer.appendChild(ui.tabNav);
        }
        contentContainer.appendChild(ui.tabContent);

        return {
            tabNav: ui.tabNav,
            tabContent: ui.tabContent,
            activeTab: ui.activeTab
        };
    }

    // API Pública
    Gbn.ui.panelRender.tabs = {
        getTabIcon: getTabIcon,
        groupFieldsByTab: groupFieldsByTab,
        sortTabNames: sortTabNames,
        applyFormStyles: applyFormStyles,
        renderTabs: renderTabs,
        tabIconMap: tabIconMap
    };

})(window);
