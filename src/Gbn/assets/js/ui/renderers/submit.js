;(function (global) {
    'use strict';

    /**
     * SUBMIT RENDERER - Botón de Envío
     * 
     * Maneja botones submit con estados y texto de carga.
     * 
     * @module Gbn.ui.renderers.submit
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera los estilos inline para el botón submit.
     */
    function getStyles(config, block) {
        var styles = traits.getCommonStyles(config);

        // Width
        if (config.width && config.width !== 'auto') {
            styles['width'] = config.width;
            if (config.width === '100%') {
                styles['display'] = 'block';
            }
        }

        // Cursor
        if (config.cursor) {
            styles['cursor'] = config.cursor;
        }

        // Transition
        if (config.transition) {
            styles['transition'] = config.transition;
        }

        return styles;
    }

    /**
     * Maneja actualizaciones en tiempo real.
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;

        // Propiedades específicas del submit
        if (path === 'texto') {
            el.textContent = value || 'Enviar';
            return true;
        }

        if (path === 'loadingText') {
            el.setAttribute('data-loading-text', value || 'Enviando...');
            return true;
        }

        if (path === 'width') {
            el.style.width = value === '100%' ? '100%' : '';
            el.style.display = value === '100%' ? 'block' : '';
            return true;
        }

        if (path === 'cursor') {
            el.style.cursor = value || 'pointer';
            return true;
        }

        if (path === 'transition') {
            el.style.transition = value || '';
            return true;
        }

        // Delegar a traits comunes
        return traits.handleCommonUpdate(el, path, value);
    }

    /**
     * Función para leer valores computados del elemento.
     */
    function getComputedValues(block) {
        if (!block || !block.element) return {};
        
        try {
            var computed = window.getComputedStyle(block.element);
            return {
                width: computed.width,
                backgroundColor: computed.backgroundColor,
                color: computed.color,
                cursor: computed.cursor,
                transition: computed.transition,
                borderRadius: computed.borderRadius,
                paddingTop: computed.paddingTop,
                paddingRight: computed.paddingRight,
                paddingBottom: computed.paddingBottom,
                paddingLeft: computed.paddingLeft
            };
        } catch (e) {
            return {};
        }
    }

    // Exportar el renderer
    Gbn.ui.renderers.submit = {
        getStyles: getStyles,
        handleUpdate: handleUpdate,
        getComputedValues: getComputedValues
    };

})(typeof window !== 'undefined' ? window : this);
