;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};

    /**
     * Utilidades compartidas para el módulo Theme Settings.
     * 
     * Este módulo contiene funciones reutilizables para:
     * - Renderizado de tabs con iconos del IconRegistry
     * - Agrupación de campos por tab
     * - Ordenamiento de tabs
     */

    /**
     * Mapa de nombres de tabs a claves de iconos en GbnIcons.
     * Soporta variantes con/sin tilde y mayúsculas/minúsculas.
     */
    var tabIconMap = {
        // Español con tildes (estándar)
        'Contenido': 'tab.content',
        'Estilo': 'tab.style',
        'Avanzado': 'tab.advanced',
        'Layout': 'tab.layout',
        'Query': 'tab.query',
        'Interacción': 'tab.interaction',
        
        // Español minúsculas (legacy MenuComponent)
        'configuracion': 'tab.content',
        'configuración': 'tab.content',
        'estilo': 'tab.style',
        'movil': 'tab.mobile',
        'avanzado': 'tab.advanced',
        
        // Inglés (fallback/compatibility)
        'Content': 'tab.content',
        'Style': 'tab.style',
        'Advanced': 'tab.advanced',
        'Mobile': 'tab.mobile'
    };

    /**
     * Obtiene el icono SVG para un tab.
     * Usa GbnIcons si está disponible, con fallback a string vacío.
     * 
     * @param {string} tabName - Nombre del tab
     * @returns {string} SVG del icono o string vacío
     */
    function getTabIcon(tabName) {
        var iconKey = tabIconMap[tabName];
        
        if (!iconKey) {
            // Intentar normalizar el nombre (remover tildes, lowercase)
            var normalized = tabName.toLowerCase()
                .replace(/á/g, 'a')
                .replace(/é/g, 'e')
                .replace(/í/g, 'i')
                .replace(/ó/g, 'o')
                .replace(/ú/g, 'u');
            iconKey = tabIconMap[normalized];
        }
        
        if (iconKey && global.GbnIcons && global.GbnIcons.get) {
            return global.GbnIcons.get(iconKey);
        }
        
        return '';
    }

    /**
     * Agrupa campos por tab.
     * 
     * @param {Array} schema - Array de definiciones de campos
     * @param {string} defaultTab - Nombre del tab por defecto si no se especifica
     * @returns {Object} Objeto con tabs como keys y arrays de campos como values
     */
    function groupFieldsByTab(schema, defaultTab) {
        defaultTab = defaultTab || 'Contenido';
        var tabs = {};
        var hasTabs = false;

        schema.forEach(function(field) {
            var tabName = field.tab || defaultTab;
            if (field.tab) hasTabs = true;
            if (!tabs[tabName]) tabs[tabName] = [];
            tabs[tabName].push(field);
        });

        return {
            tabs: tabs,
            hasTabs: hasTabs
        };
    }

    /**
     * Ordena los nombres de tabs según un orden predefinido.
     * 
     * @param {Array} tabNames - Array de nombres de tabs
     * @param {Array} orderPreference - Array opcional con orden preferido
     * @returns {Array} Nombres ordenados
     */
    function sortTabNames(tabNames, orderPreference) {
        var order = orderPreference || ['Contenido', 'Estilo', 'Layout', 'Query', 'Interacción', 'Avanzado'];
        
        return tabNames.slice().sort(function(a, b) {
            var ia = order.indexOf(a);
            var ib = order.indexOf(b);
            
            // Ambos en la lista de orden
            if (ia !== -1 && ib !== -1) return ia - ib;
            // Solo 'a' en la lista
            if (ia !== -1) return -1;
            // Solo 'b' en la lista
            if (ib !== -1) return 1;
            // Ninguno en la lista, orden alfabético
            return a.localeCompare(b);
        });
    }

    /**
     * Crea la estructura de tabs con navegación.
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
        var tabs = options.tabs;
        var builder = options.builder;
        var mockBlock = options.mockBlock;
        var fieldIdPrefix = options.fieldIdPrefix || '';
        var tabOrder = options.tabOrder || ['Contenido', 'Estilo', 'Avanzado'];
        
        var tabNav = document.createElement('div');
        tabNav.className = 'gbn-panel-tabs';
        
        var tabContent = document.createElement('div');
        tabContent.className = 'gbn-panel-tabs-content';
        
        var tabNames = sortTabNames(Object.keys(tabs), tabOrder);
        var activeTab = tabNames[0];

        tabNames.forEach(function(name) {
            // Crear botón de tab
            var btn = document.createElement('button');
            btn.className = 'gbn-tab-btn' + (name === activeTab ? ' active' : '');
            btn.type = 'button';
            btn.innerHTML = getTabIcon(name) + '<span>' + name + '</span>';
            btn.title = name;
            
            btn.onclick = function() {
                // Desactivar todos los tabs
                var allBtns = tabNav.querySelectorAll('.gbn-tab-btn');
                var allPanes = tabContent.querySelectorAll('.gbn-tab-pane');
                
                for (var i = 0; i < allBtns.length; i++) {
                    allBtns[i].classList.remove('active');
                }
                for (var i = 0; i < allPanes.length; i++) {
                    allPanes[i].classList.remove('active');
                }
                
                // Activar tab seleccionado
                btn.classList.add('active');
                var pane = tabContent.querySelector('.gbn-tab-pane[data-tab="' + name + '"]');
                if (pane) pane.classList.add('active');
            };
            
            tabNav.appendChild(btn);

            // Crear panel de contenido
            var pane = document.createElement('div');
            pane.className = 'gbn-tab-pane' + (name === activeTab ? ' active' : '');
            pane.setAttribute('data-tab', name);
            
            var form = document.createElement('form');
            form.className = 'gbn-panel-form';
            
            tabs[name].forEach(function(field) {
                // Aplicar prefijo si existe
                var f = field;
                if (fieldIdPrefix) {
                    f = Gbn.utils.assign({}, field);
                    f.id = fieldIdPrefix + f.id;
                }
                
                var control = builder(mockBlock, f);
                if (control) form.appendChild(control);
            });
            
            pane.appendChild(form);
            tabContent.appendChild(pane);
        });

        return {
            tabNav: tabNav,
            tabContent: tabContent
        };
    }

    // API Pública
    Gbn.ui.theme.utils = {
        getTabIcon: getTabIcon,
        groupFieldsByTab: groupFieldsByTab,
        sortTabNames: sortTabNames,
        createTabsUI: createTabsUI,
        // Exponer mapa para extensibilidad
        tabIconMap: tabIconMap
    };

})(window);
