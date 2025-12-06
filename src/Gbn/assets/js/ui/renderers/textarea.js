;(function (global) {
    'use strict';

    /**
     * TEXTAREA RENDERER - Área de Texto
     * 
     * Maneja campos textarea con configuración de filas.
     * 
     * @module Gbn.ui.renderers.textarea
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera los estilos inline para el contenedor del textarea.
     */
    function getStyles(config, block) {
        var styles = traits.getCommonStyles(config);
        return styles;
    }

    /**
     * Encuentra el elemento textarea dentro del contenedor.
     */
    function findTextarea(container) {
        return container.querySelector('textarea') || container;
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
        var textarea = findTextarea(el);
        var label = findLabel(el);

        // Propiedades específicas del textarea
        if (path === 'name') {
            textarea.name = value || '';
            if (label) {
                label.setAttribute('for', value || '');
            }
            return true;
        }

        if (path === 'label') {
            if (label) {
                label.textContent = value || '';
            }
            return true;
        }

        if (path === 'placeholder') {
            textarea.placeholder = value || '';
            return true;
        }

        if (path === 'rows') {
            textarea.rows = parseInt(value, 10) || 4;
            return true;
        }

        if (path === 'required') {
            if (value) {
                textarea.setAttribute('required', '');
            } else {
                textarea.removeAttribute('required');
            }
            return true;
        }

        if (path === 'maxlength') {
            if (value) {
                textarea.setAttribute('maxlength', value);
            } else {
                textarea.removeAttribute('maxlength');
            }
            return true;
        }

        // Estilos que aplican al textarea específicamente
        if (path === 'color' || path === 'backgroundColor') {
            var cssProp = path === 'backgroundColor' ? 'background-color' : 'color';
            textarea.style[cssProp] = value || '';
            return true;
        }

        // Delegar a traits comunes
        return traits.handleCommonUpdate(el, path, value);
    }

    // Exportar el renderer
    Gbn.ui.renderers.textarea = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
