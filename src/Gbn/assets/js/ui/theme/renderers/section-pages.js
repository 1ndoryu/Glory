;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};
    Gbn.ui.theme.renderers = Gbn.ui.theme.renderers || {};

    /**
     * Renderizador de la sección Pages de Theme Settings.
     * 
     * Responsabilidad: Configuración de defaults para páginas.
     */

    /**
     * Genera el schema para la sección de páginas.
     * @returns {Array} Schema de campos
     */
    function getSchema() {
        return [
            { tipo: 'header', etiqueta: 'Defaults de Página' },
            { tipo: 'color', id: 'pages.background', etiqueta: 'Fondo Default', defecto: '#ffffff' },
            { tipo: 'spacing', id: 'pages.padding', etiqueta: 'Padding Default', defecto: 20 },
            { tipo: 'text', id: 'pages.maxAncho', etiqueta: 'Ancho Máximo', defecto: '100%' }
        ];
    }

    /**
     * Renderiza la sección de páginas.
     * 
     * @param {HTMLElement} content - Contenedor de contenido
     * @param {Object} mockBlock - Mock block para el builder
     * @param {Function} builder - Función buildField
     */
    function render(content, mockBlock, builder) {
        var schema = getSchema();
        
        schema.forEach(function(field) {
            var control = builder(mockBlock, field);
            if (control) content.appendChild(control);
        });
    }

    // Exportar
    Gbn.ui.theme.renderers.sectionPages = {
        render: render,
        getSchema: getSchema
    };

})(window);
