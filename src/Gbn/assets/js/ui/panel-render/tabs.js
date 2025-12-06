;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.panelRender = Gbn.ui.panelRender || {};

    /**
     * Utilidades para renderizado de tabs en el panel
     * 
     * Proporciona funciones reutilizables para:
     * - Agrupar campos por tab
     * - Ordenar tabs según prioridad
     * - Obtener iconos de tabs desde IconRegistry
     * - Crear la UI de tabs con navegación
     */

    /**
     * Mapa de nombres de tab a claves de iconos
     */
    var tabIconMap = {
        'Contenido': 'tab.content',
        'Configuración': 'tab.content',
        'Estilo': 'tab.style',
        'Interacción': 'tab.interaction',
        'Layout': 'tab.layout',
        'Query': 'tab.query',
        'Avanzado': 'tab.advanced',
        'Móvil': 'tab.mobile',
        'Movil': 'tab.mobile'
    };

    /**
     * Obtiene el icono para un nombre de tab
     * @param {string} name - Nombre del tab
     * @returns {string} SVG del icono o string vacío
     */
    function getTabIcon(name) {
        var Icons = global.GbnIcons;
        if (!Icons) return '';
        
        var key = tabIconMap[name];
        return key ? Icons.get(key) : '';
    }

    /**
     * Agrupa campos por tab
     * @param {Array} schema - Schema de campos
     * @param {string} defaultTab - Tab por defecto
     * @returns {Object} {tabs, hasTabs}
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

        return { tabs: tabs, hasTabs: hasTabs };
    }

    /**
     * Ordena los nombres de tabs según prioridad
     * @param {Array} tabNames - Array de nombres
     * @returns {Array} Nombres ordenados
     */
    function sortTabNames(tabNames) {
        var order = ['Contenido', 'Estilo', 'Avanzado'];
        
        return tabNames.slice().sort(function(a, b) {
            var ia = order.indexOf(a);
            var ib = order.indexOf(b);
            if (ia !== -1 && ib !== -1) return ia - ib;
            if (ia !== -1) return -1;
            if (ib !== -1) return 1;
            return a.localeCompare(b);
        });
    }

    /**
     * Aplica estilos comunes a un formulario del panel
     * @param {HTMLElement} form
     */
    function applyFormStyles(form) {
        form.style.display = 'flex';
        form.style.flexDirection = 'column';
        form.style.gap = '12px';
        form.style.marginBottom = '100px'; // Prevenir overlap con footer
    }

    /**
     * Renderiza tabs con sus contenidos
     * 
     * @param {Object} options - Opciones de configuración
     * @param {Object} options.tabs - Campos agrupados por tab
     * @param {Object} options.block - Bloque actual
     * @param {Function} options.builder - Función buildField
     * @param {HTMLElement} options.tabsContainer - Contenedor para nav de tabs (opcional)
     * @param {HTMLElement} options.contentContainer - Contenedor para contenido
     * @param {string} options.activeTab - Tab activo inicial
     * @param {Function} options.onTabChange - Callback al cambiar tab
     * @returns {Object} {tabNav, tabContent, activeTab}
     */
    function renderTabs(options) {
        var tabs = options.tabs;
        var block = options.block;
        var builder = options.builder;
        var contentContainer = options.contentContainer;
        var externalTabsContainer = options.tabsContainer;
        var initialActiveTab = options.activeTab;
        var onTabChange = options.onTabChange;
        
        var tabNav = document.createElement('div');
        tabNav.className = 'gbn-panel-tabs';
        tabNav.style.display = 'flex';
        tabNav.style.gap = '4px';
        tabNav.style.padding = '10px';
        tabNav.style.paddingTop = '0';
        
        var tabContent = document.createElement('div');
        tabContent.className = 'gbn-panel-tabs-content';
        
        var tabNames = sortTabNames(Object.keys(tabs));
        var activeTab = initialActiveTab && tabNames.indexOf(initialActiveTab) !== -1 
            ? initialActiveTab 
            : tabNames[0];

        tabNames.forEach(function(name) {
            // Crear botón de tab
            var btn = document.createElement('button');
            btn.className = 'gbn-tab-btn' + (name === activeTab ? ' active' : '');
            btn.type = 'button';
            btn.innerHTML = getTabIcon(name) + '<span>' + name + '</span>';
            btn.title = name;
            
            btn.onclick = function() {
                // Desactivar todos
                var allBtns = tabNav.querySelectorAll('.gbn-tab-btn');
                var allPanes = tabContent.querySelectorAll('.gbn-tab-pane');
                
                for (var i = 0; i < allBtns.length; i++) {
                    allBtns[i].classList.remove('active');
                }
                for (var i = 0; i < allPanes.length; i++) {
                    allPanes[i].classList.remove('active');
                }
                
                // Activar seleccionado
                btn.classList.add('active');
                var pane = tabContent.querySelector('.gbn-tab-pane[data-tab="' + name + '"]');
                if (pane) pane.classList.add('active');
                
                // Callback
                if (onTabChange) onTabChange(name);
            };
            
            tabNav.appendChild(btn);

            // Crear panel de contenido
            var pane = document.createElement('div');
            pane.className = 'gbn-tab-pane' + (name === activeTab ? ' active' : '');
            pane.setAttribute('data-tab', name);
            
            var form = document.createElement('form');
            form.className = 'gbn-panel-form';
            applyFormStyles(form);
            
            tabs[name].forEach(function(field) {
                var control = builder ? builder(block, field) : null;
                if (control) form.appendChild(control);
            });
            
            pane.appendChild(form);
            tabContent.appendChild(pane);
        });

        // Colocar nav de tabs
        if (externalTabsContainer) {
            externalTabsContainer.appendChild(tabNav);
        } else {
            contentContainer.appendChild(tabNav);
        }
        contentContainer.appendChild(tabContent);

        return {
            tabNav: tabNav,
            tabContent: tabContent,
            activeTab: activeTab
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
