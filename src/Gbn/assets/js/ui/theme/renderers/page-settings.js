;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};
    Gbn.ui.theme.renderers = Gbn.ui.theme.renderers || {};

    /**
     * Renderizador para Page Settings Form.
     * 
     * Responsabilidad: Renderizar el formulario de configuración de página individual.
     * Campos: background, padding, maxAncho, custom_css
     */

    /**
     * Renderiza el formulario de configuración de página.
     * 
     * @param {Object} settings - Configuración actual de la página
     * @param {HTMLElement} container - Contenedor donde renderizar
     * @param {HTMLElement} footer - Botón del footer (se deshabilita)
     */
    function render(settings, container, footer) {
        if (!container) return;
        container.innerHTML = '';
        
        var mockBlock = {
            id: 'page-settings',
            role: 'page',
            config: settings || {}
        };

        var builder = Gbn.ui && Gbn.ui.panelFields && Gbn.ui.panelFields.buildField;
        if (!builder) {
            container.innerHTML = 'Error: panelFields no disponible';
            return;
        }

        // Schema de campos para Page Settings
        var schema = [
            // Tab: Estilo
            { tipo: 'color', id: 'background', etiqueta: 'Color de Fondo', defecto: '#ffffff', tab: 'Estilo' },
            { tipo: 'spacing', id: 'padding', etiqueta: 'Padding', defecto: 20, tab: 'Estilo' },
            { tipo: 'text', id: 'maxAncho', etiqueta: 'Ancho Máximo', defecto: '100%', tab: 'Estilo' },
            
            // Tab: Avanzado
            { 
                tipo: 'textarea', 
                id: 'custom_css', 
                etiqueta: 'CSS Personalizado', 
                tab: 'Avanzado', 
                description: 'CSS específico para esta página. Usa "selector" para referirte al body.' 
            }
        ];

        // Usar utilidades de tabs
        var utils = Gbn.ui.theme.utils;
        var grouped = utils.groupFieldsByTab(schema, 'Estilo');
        var tabOrder = ['Estilo', 'Avanzado'];
        
        var fieldsContainer = document.createElement('div');
        fieldsContainer.className = 'gbn-component-fields';
        
        var tabsUI = utils.createTabsUI({
            tabs: grouped.tabs,
            builder: builder,
            mockBlock: mockBlock,
            tabOrder: tabOrder
        });

        fieldsContainer.appendChild(tabsUI.tabNav);
        fieldsContainer.appendChild(tabsUI.tabContent);
        container.appendChild(fieldsContainer);
        
        // Deshabilitar botón del footer (usamos guardado global)
        if (footer) {
            footer.disabled = true;
            footer.textContent = 'Usa Guardar en el Dock';
        }
    }

    // Exportar
    Gbn.ui.theme.renderers.pageSettings = {
        render: render
    };

})(window);
