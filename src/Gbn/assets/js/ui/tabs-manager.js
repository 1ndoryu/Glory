;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};

    /**
     * TabsManager: Singleton para gestión centralizada de tabs.
     * Unifica la lógica de tabs del Panel (bloques) y del Theme Settings.
     * 
     * Responsabilidades:
     * - Mapa centralizado de iconos (tabIconMap)
     * - Normalización de nombres de tabs
     * - Ordenamiento consistente
     * - Renderizado de UI de tabs (Nav + Content)
     */
    
    var TabsManager = {};

    /**
     * Mapa de nombres de tabs a claves de iconos en GbnIcons.
     * Unifica definicines de panel-render/tabs.js y theme/utils.js.
     */
    var tabIconMap = {
        // Español con tildes (estándar preferido)
        'Contenido': 'tab.content',
        'Configuración': 'tab.content',
        'Estilo': 'tab.style',
        'Interacción': 'tab.interaction',
        'Layout': 'tab.layout',
        'Query': 'tab.query',
        'Avanzado': 'tab.advanced',
        'Móvil': 'tab.mobile',
        'Movil': 'tab.mobile', // Variante sin tilde común

        // Español minúsculas (legacy/compatibility)
        'contenido': 'tab.content',
        'configuracion': 'tab.content',
        'configuración': 'tab.content',
        'estilo': 'tab.style',
        'interaccion': 'tab.interaction',
        'interacción': 'tab.interaction',
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
     * @param {string} tabName - Nombre del tab
     * @returns {string} SVG del icono o string vacío
     */
    function getTabIcon(tabName) {
        var Icons = global.GbnIcons;
        if (!Icons) return '';

        var iconKey = tabIconMap[tabName];
        
        if (!iconKey && tabName) {
            // Estrategia 1: Normalización simple (primera mayúscula)
            var capitalized = tabName.charAt(0).toUpperCase() + tabName.slice(1).toLowerCase();
            iconKey = tabIconMap[capitalized];

            // Estrategia 2: Normalización agresiva (lowercase, sin tildes)
            if (!iconKey) {
                var normalized = tabName.toLowerCase()
                    .replace(/á/g, 'a')
                    .replace(/é/g, 'e')
                    .replace(/í/g, 'i')
                    .replace(/ó/g, 'o')
                    .replace(/ú/g, 'u');
                iconKey = tabIconMap[normalized];
            }
        }
        
        return iconKey ? Icons.get(iconKey) : '';
    }

    /**
     * Agrupa campos por tab.
     * @param {Array} schema - Array de definiciones de campos
     * @param {string} defaultTab - Nombre del tab por defecto
     * @returns {Object} {tabs, hasTabs}
     */
    function groupFieldsByTab(schema, defaultTab) {
        defaultTab = defaultTab || 'Contenido';
        var tabs = {};
        var hasTabs = false;

        if (!Array.isArray(schema)) return { tabs: {}, hasTabs: false };

        schema.forEach(function(field) {
            var tabName = field.tab || defaultTab;
            if (field.tab) hasTabs = true;
            if (!tabs[tabName]) tabs[tabName] = [];
            tabs[tabName].push(field);
        });

        return { tabs: tabs, hasTabs: hasTabs };
    }

    /**
     * Ordena los nombres de tabs según prioridad.
     * @param {Array} tabNames - Array de nombres
     * @param {Array} orderPreference - Orden personalizado opcional
     * @returns {Array} Nombres ordenados
     */
    function sortTabNames(tabNames, orderPreference) {
        var order = orderPreference || ['Contenido', 'contenido', 'Estilo', 'estilo', 'Layout', 'Query', 'Interacción', 'Avanzado', 'avanzado'];
        
        return tabNames.slice().sort(function(a, b) {
            // Normalizar para comparación si es el orden default
            var aProc = a;
            var bProc = b;

            if (!orderPreference) {
                 // Si usamos orden default, intentamos ser flexibles con capitalization
                 aProc = a.charAt(0).toUpperCase() + a.slice(1).toLowerCase();
                 bProc = b.charAt(0).toUpperCase() + b.slice(1).toLowerCase();
                 var orderNorm = ['Contenido', 'Estilo', 'Layout', 'Query', 'Interacción', 'Avanzado'];
                 var ia = orderNorm.indexOf(aProc);
                 var ib = orderNorm.indexOf(bProc);
                 
                 if (ia !== -1 && ib !== -1) return ia - ib;
                 if (ia !== -1) return -1;
                 if (ib !== -1) return 1;
            } else {
                 // Si hay orden explícito, buscar coincidencia exacta primero
                 var ia = order.indexOf(a);
                 var ib = order.indexOf(b);
                 if (ia !== -1 && ib !== -1) return ia - ib;
                 if (ia !== -1) return -1;
                 if (ib !== -1) return 1;
            }

            return a.localeCompare(b);
        });
    }

    /**
     * Crea la estructura de UI para tabs.
     * Retorna los elementos DOM desconectados para que el caller los inserte.
     * 
     * @param {Object} options - Configuración
     * @param {Object} options.tabs - Objeto { tabName: [fields] }
     * @param {Function} options.builder - Función (block, field) => HTMLElement
     * @param {Object} options.block - El bloque/mockBlock actual
     * @param {string} [options.activeTab] - Tab inicial
     * @param {Function} [options.onTabChange] - Callback (tabName) => void
     * @param {string} [options.fieldIdPrefix] - Prefijo para IDs de campos
     * @param {Function} [options.formStyler] - Función (form) => void para estilos custom
     * 
     * @returns {Object} { tabNav, tabContent, activeTab }
     */
    function createTabsUI(options) {
        var tabs = options.tabs;
        var builder = options.builder;
        var block = options.block;
        var initialActiveTab = options.activeTab;
        var onTabChange = options.onTabChange;
        var fieldIdPrefix = options.fieldIdPrefix;
        var formStyler = options.formStyler;

        var tabNav = document.createElement('div');
        tabNav.className = 'gbn-panel-tabs';
        
        var tabContent = document.createElement('div');
        tabContent.className = 'gbn-panel-tabs-content';
        
        var tabNames = sortTabNames(Object.keys(tabs));
        var activeTab = initialActiveTab && tabNames.indexOf(initialActiveTab) !== -1 
            ? initialActiveTab 
            : tabNames[0];

        tabNames.forEach(function(name) {
            // 1. Crear Botón
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
                
                // Activar actual
                btn.classList.add('active');
                var pane = tabContent.querySelector('.gbn-tab-pane[data-tab="' + name + '"]');
                if (pane) pane.classList.add('active');
                
                if (onTabChange) onTabChange(name);
            };
            
            tabNav.appendChild(btn);

            // 2. Crear Panel Contenido
            var pane = document.createElement('div');
            pane.className = 'gbn-tab-pane' + (name === activeTab ? ' active' : '');
            pane.setAttribute('data-tab', name);
            
            var form = document.createElement('form');
            form.className = 'gbn-panel-form';
            
            if (formStyler) {
                formStyler(form);
            } else {
                // Estilos default (copiados de applyFormStyles)
                form.style.display = 'flex';
                form.style.flexDirection = 'column';
                form.style.gap = '12px';
            }
            
            tabs[name].forEach(function(field) {
                // Soporte para prefijo de ID
                var fieldDef = field;
                if (fieldIdPrefix) {
                    fieldDef = Gbn.utils.assign({}, field);
                    fieldDef.id = fieldIdPrefix + fieldDef.id;
                }

                var control = builder ? builder(block, fieldDef) : null;
                if (control) form.appendChild(control);
            });
            
            pane.appendChild(form);
            tabContent.appendChild(pane);
        });

        return {
            tabNav: tabNav,
            tabContent: tabContent,
            activeTab: activeTab
        };
    }

    // API Pública
    TabsManager.getTabIcon = getTabIcon;
    TabsManager.groupFieldsByTab = groupFieldsByTab;
    TabsManager.sortTabNames = sortTabNames;
    TabsManager.createTabsUI = createTabsUI;
    TabsManager.map = tabIconMap;

    Gbn.ui.tabsManager = TabsManager;

})(window);
