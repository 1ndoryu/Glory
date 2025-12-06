;(function (global) {
    'use strict';

    /**
     * POST RENDER - STYLES MODULE
     * 
     * Maneja la generación de estilos CSS y aplicación de layout
     * para el componente PostRender.
     * 
     * @module Gbn.ui.renderers.postRender.styles
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};
    Gbn.ui.renderers.postRenderModules = Gbn.ui.renderers.postRenderModules || {};

    // Referencia a traits para funciones compartidas
    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera estilos CSS para el contenedor PostRender.
     * 
     * @param {Object} config Configuración del bloque
     * @param {Object} block Referencia al bloque
     * @returns {Object} Estilos CSS como objeto
     */
    function getStyles(config, block) {
        var styles = traits.getCommonStyles(config);

        // Layout del contenedor según campo 'layout' (SchemaConstants::FIELD_LAYOUT)
        // NOTA: PHP usa 'layout', no 'displayMode' (FIX BUG-014)
        var displayMode = config.layout || 'grid';

        if (displayMode === 'grid') {
            var columns = parseInt(config.gridColumns, 10) || 3;
            var gap = config.gap || '20px';

            styles['display'] = 'grid';
            styles['grid-template-columns'] = 'repeat(' + columns + ', 1fr)';
            styles['gap'] = traits.normalizeSize(gap);
        } else if (displayMode === 'flex') {
            styles['display'] = 'flex';
            // FIX BUG-014: PHP usa 'direction' y 'wrap' (SchemaConstants)
            styles['flex-direction'] = config.direction || 'row';
            styles['flex-wrap'] = config.wrap || 'wrap';
            styles['align-items'] = config.alignItems || 'stretch';
            styles['justify-content'] = config.justifyContent || 'flex-start';
            styles['gap'] = traits.normalizeSize(config.gap) || '20px';
        } else {
            styles['display'] = 'block';
        }

        return styles;
    }

    /**
     * Aplica el patrón de layout al contenedor.
     * 
     * @param {HTMLElement} el Elemento contenedor
     * @param {string} pattern Patrón (none, alternado_lr, masonry)
     */
    function applyLayoutPattern(el, pattern) {
        // Limpiar patrones previos
        el.removeAttribute('data-pattern');
        
        if (pattern && pattern !== 'none') {
            el.setAttribute('data-pattern', pattern);
        }
    }

    /**
     * Aplica el efecto hover a los items del contenedor.
     * 
     * @param {HTMLElement} el Elemento contenedor
     * @param {string} effect Efecto (none, lift, scale, glow)
     */
    function applyHoverEffect(el, effect) {
        // Limpiar efectos previos de todos los items (case-insensitive)
        var items = el.querySelectorAll('[gloryPostItem], [glorypostitem]');
        items.forEach(function(item) {
            item.classList.remove('gbn-hover-lift', 'gbn-hover-scale', 'gbn-hover-glow');
        });

        // Aplicar nuevo efecto
        if (effect && effect !== 'none') {
            var effectClass = 'gbn-hover-' + effect;
            items.forEach(function(item) {
                item.classList.add(effectClass);
            });
        }
    }

    /**
     * Aplica el modo de visualización al elemento.
     * 
     * @param {HTMLElement} el Elemento contenedor
     * @param {string} mode Modo (grid, flex, block)
     * @param {Object} config Configuración actual
     */
    function applyDisplayMode(el, mode, config) {
        // Limpiar estilos de layout previos
        el.style.display = '';
        el.style.gridTemplateColumns = '';
        el.style.flexDirection = '';
        el.style.flexWrap = '';
        el.style.alignItems = '';
        el.style.justifyContent = '';

        if (mode === 'grid') {
            var cols = parseInt(config.gridColumns, 10) || 3;
            el.style.display = 'grid';
            el.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
            el.style.gap = traits.normalizeSize(config.gap) || '20px';
        } else if (mode === 'flex') {
            el.style.display = 'flex';
            // FIX BUG-014: PHP usa 'direction' y 'wrap' (SchemaConstants)
            el.style.flexDirection = config.direction || 'row';
            el.style.flexWrap = config.wrap || 'wrap';
            el.style.alignItems = config.alignItems || 'stretch';
            el.style.justifyContent = config.justifyContent || 'flex-start';
            el.style.gap = traits.normalizeSize(config.gap) || '20px';
        } else {
            el.style.display = 'block';
        }
    }

    // Exportar módulo
    Gbn.ui.renderers.postRenderModules.styles = {
        getStyles: getStyles,
        applyLayoutPattern: applyLayoutPattern,
        applyHoverEffect: applyHoverEffect,
        applyDisplayMode: applyDisplayMode
    };

})(typeof window !== 'undefined' ? window : this);
