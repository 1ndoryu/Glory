;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};
    Gbn.ui.theme.renderers = Gbn.ui.theme.renderers || {};

    /**
     * Renderizador del menú principal de Theme Settings.
     * 
     * Responsabilidad: Mostrar las opciones principales (Texto, Colores, Páginas, Componentes)
     * con sus iconos correspondientes del IconRegistry.
     */

    /**
     * Opciones del menú principal con iconos del registro.
     * @returns {Array} Opciones del menú
     */
    function getMenuOptions() {
        var Icons = global.GbnIcons;
        
        return [
            { 
                id: 'text', 
                label: 'Texto', 
                icon: Icons ? Icons.get('theme.text') : ''
            },
            { 
                id: 'colors', 
                label: 'Colores', 
                icon: Icons ? Icons.get('theme.colors') : ''
            },
            { 
                id: 'pages', 
                label: 'Páginas', 
                icon: Icons ? Icons.get('theme.pages') : ''
            },
            {
                id: 'components',
                label: 'Componentes',
                icon: Icons ? Icons.get('theme.components') : ''
            }
        ];
    }

    /**
     * Renderiza el menú principal de Theme Settings.
     * 
     * @param {HTMLElement} container - Contenedor donde renderizar
     * @param {Function} onNavigate - Callback cuando se selecciona una opción
     */
    function render(container, onNavigate) {
        var menuContainer = document.createElement('div');
        menuContainer.className = 'gbn-theme-menu';
        
        var options = getMenuOptions();
        
        options.forEach(function(opt) {
            var btn = document.createElement('button');
            btn.className = 'gbn-theme-menu-btn';
            btn.innerHTML = opt.icon + '<span>' + opt.label + '</span>';
            btn.onclick = function() {
                if (onNavigate) onNavigate(opt.id);
            };
            menuContainer.appendChild(btn);
        });
        
        container.appendChild(menuContainer);
    }

    // Exportar
    Gbn.ui.theme.renderers.menu = {
        render: render,
        getMenuOptions: getMenuOptions
    };

})(window);
