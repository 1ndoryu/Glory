;(function(global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.theme = Gbn.ui.theme || {};
    Gbn.ui.theme.renderers = Gbn.ui.theme.renderers || {};

    /**
     * Renderizador de la sección Text de Theme Settings.
     * 
     * Responsabilidad: Configuración de tipografía global (párrafos, h1-h6).
     */

    /**
     * Genera el schema para la sección de texto.
     * @returns {Array} Schema de campos
     */
    function getSchema() {
        var schema = [
            { tipo: 'header', etiqueta: 'Párrafos (p)' },
            { tipo: 'typography', id: 'text.p', etiqueta: 'Tipografía' },
            { tipo: 'color', id: 'text.p.color', etiqueta: 'Color Texto', defecto: '#333333' }
        ];
        
        // Agregar headers h1-h6
        var tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        tags.forEach(function(tag) {
            schema.push({ tipo: 'header', etiqueta: tag.toUpperCase() });
            schema.push({ tipo: 'typography', id: 'text.' + tag, etiqueta: 'Tipografía' });
            schema.push({ 
                tipo: 'color', 
                id: 'text.' + tag + '.color', 
                etiqueta: 'Color ' + tag.toUpperCase(), 
                defecto: '' 
            });
        });
        
        return schema;
    }

    /**
     * Renderiza la sección de texto.
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
    Gbn.ui.theme.renderers.sectionText = {
        render: render,
        getSchema: getSchema
    };

})(window);
