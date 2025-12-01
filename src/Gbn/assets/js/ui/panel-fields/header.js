;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};

    /**
     * Construye un separador/header de sección
     */
    function buildHeaderField(block, field) {
        var wrapper = document.createElement('div');
        wrapper.className = 'gbn-field-header-separator';
        
        var label = document.createElement('h4');
        label.textContent = field.etiqueta || field.id;
        wrapper.appendChild(label);
        
        return wrapper;
    }

    // Exportar
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.headerField = { build: buildHeaderField };

})(window);

