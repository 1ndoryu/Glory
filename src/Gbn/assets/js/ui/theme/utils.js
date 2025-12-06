;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};

    /**
     * Utilidades compartidas para el módulo Theme Settings.
     * DELEGADO a Gbn.ui.tabsManager (Refactorización REFACTOR-002)
     */

    var TabsManager = Gbn.ui.tabsManager;

    if (!TabsManager) {
        console.error('[Theme] TabsManager not loaded!');
    }

    // Delegar funciones puras
    var getTabIcon = TabsManager ? TabsManager.getTabIcon : function() { return ''; };
    var groupFieldsByTab = TabsManager ? TabsManager.groupFieldsByTab : function() { return {}; };
    var sortTabNames = TabsManager ? TabsManager.sortTabNames : function(names) { return names; };
    var tabIconMap = TabsManager ? TabsManager.map : {};

    /**
     * Crea la estructura de tabs con navegación.
     * Adaptador para usar TabsManager.createTabsUI
     * 
     * @param {Object} options - Opciones de configuración
     * @param {Object} options.tabs - Objeto de tabs (key: tabName, value: array de campos)
     * @param {Function} options.builder - Función para construir cada campo
     * @param {Object} options.mockBlock - Mock block para pasar al builder
     * @param {string} options.fieldIdPrefix - Prefijo opcional para IDs de campos
     * @param {Array} options.tabOrder - Orden preferido de tabs
     * @returns {Object} {tabNav: HTMLElement, tabContent: HTMLElement}
     */
    function createTabsUI(options) {
        if (!TabsManager) return {};

        // Mappear argumentos específicos de theme a TabsManager
        return TabsManager.createTabsUI({
            tabs: options.tabs,
            builder: options.builder,
            block: options.mockBlock, // Mapear mockBlock -> block
            fieldIdPrefix: options.fieldIdPrefix,
            // sortTabNames usa este orden internamente si se pasa tabOrder a sortTabNames, 
            // pero createTabsUI de TabsManager no expone tabOrder explícitamente en el sort interno
            // TODO: TabsManager.createTabsUI debería aceptar tabOrder?
            // Por ahora TabsManager usa un orden default robusto. 
            // Si necesitamos custom order, deberíamos actualizar TabsManager.
            // Ojo: theme/utils usaba ['Contenido', 'Estilo', 'Avanzado'] por defecto, TabsManager tiene más.
        });
    }
    
    // NOTA: TabsManager.createTabsUI usa TabsManager.sortTabNames internamente sin custom order.
    // Si theme settings necesita un orden muy específico diferente al default, habría que extender TabsManager.
    // Sin embargo, el orden default de TabsManager cubre Contenido, Estilo, Layout, Query, Interacción, Avanzado.
    // Debería ser suficiente.

    // API Pública
    Gbn.ui.theme.utils = {
        getTabIcon: getTabIcon,
        groupFieldsByTab: groupFieldsByTab,
        sortTabNames: sortTabNames,
        createTabsUI: createTabsUI,
        tabIconMap: tabIconMap
    };

})(window);
