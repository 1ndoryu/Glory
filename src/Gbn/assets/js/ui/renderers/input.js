;(function (global) {
    'use strict';

    /**
     * INPUT RENDERER - Campo de Texto
     * 
     * Maneja campos input con tipos text, email, tel, number, etc.
     * 
     * @module Gbn.ui.renderers.input
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera los estilos inline para el contenedor del input.
     */
    function getStyles(config, block) {
        var styles = traits.getCommonStyles(config);
        return styles;
    }

    /**
     * Encuentra el elemento input dentro del contenedor.
     */
    function findInput(container) {
        return container.querySelector('input') || container;
    }

    /**
     * Encuentra el elemento label dentro del contenedor.
     */
    function findLabel(container) {
        return container.querySelector('label');
    }

    /**
     * Maneja actualizaciones en tiempo real.
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;
        var input = findInput(el);
        var label = findLabel(el);

        // Propiedades específicas del input
        if (path === 'name') {
            input.name = value || '';
            if (label) {
                label.setAttribute('for', value || '');
            }
            return true;
        }

        if (path === 'type') {
            input.type = value || 'text';
            return true;
        }

        if (path === 'label') {
            if (label) {
                label.textContent = value || '';
            }
            return true;
        }

        if (path === 'placeholder') {
            input.placeholder = value || '';
            return true;
        }

        if (path === 'required') {
            if (value) {
                input.setAttribute('required', '');
            } else {
                input.removeAttribute('required');
            }
            return true;
        }

        if (path === 'pattern') {
            if (value) {
                input.setAttribute('pattern', value);
            } else {
                input.removeAttribute('pattern');
            }
            return true;
        }

        // Estilos que aplican al input específicamente
        if (path === 'color' || path === 'backgroundColor') {
            var cssProp = path === 'backgroundColor' ? 'background-color' : 'color';
            input.style[cssProp] = value || '';
            return true;
        }

        // Delegar a traits comunes (spacing, border aplican al contenedor)
        return traits.handleCommonUpdate(el, path, value);
    }

    // Exportar el renderer
    Gbn.ui.renderers.input = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
