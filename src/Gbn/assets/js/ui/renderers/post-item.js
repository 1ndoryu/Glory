;(function (global) {
    'use strict';

    /**
     * POST ITEM RENDERER
     * 
     * Renderer para el componente PostItem (template de cada item).
     * Solo existe uno en el DOM, pero visualmente se replica por cada post.
     * Los estilos se aplican vía clase CSS scoped para afectar a todos los clones.
     * 
     * @module Gbn.ui.renderers.postItem
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    // Referencia a traits para funciones compartidas
    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera estilos CSS para el item.
     * 
     * @param {Object} config Configuración del bloque
     * @param {Object} block Referencia al bloque
     * @returns {Object} Estilos CSS como objeto
     */
    function getStyles(config, block) {
        var styles = traits.getCommonStyles(config);

        // Layout interno del item
        var display = config.display || 'flex';
        styles['display'] = display;

        if (display === 'flex') {
            styles['flex-direction'] = config.flexDirection || 'column';
            styles['align-items'] = config.alignItems || 'stretch';
            styles['justify-content'] = config.justifyContent || 'flex-start';
            styles['gap'] = traits.normalizeSize(config.gap) || '12px';
        }

        // Background
        if (config.backgroundColor) {
            styles['background-color'] = config.backgroundColor;
        }

        // Hover effects se aplican vía CSS class, no inline
        if (config.linkBehavior === 'card' || config.linkBehavior === 'button') {
            styles['cursor'] = config.cursor || 'pointer';
        }

        // Transition para efectos suaves
        styles['transition'] = 'box-shadow 0.3s ease, transform 0.3s ease';

        return styles;
    }

    /**
     * Maneja actualizaciones en tiempo real del componente.
     * 
     * @param {Object} block Referencia al bloque
     * @param {string} path Path de la propiedad modificada
     * @param {*} value Nuevo valor
     * @returns {boolean} true si se manejó la actualización
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;

        // === PROPIEDADES DE LAYOUT ===
        
        if (path === 'display') {
            el.style.display = value || 'flex';
            return true;
        }

        if (path === 'flexDirection') {
            el.style.flexDirection = value || 'column';
            return true;
        }

        if (path === 'alignItems') {
            el.style.alignItems = value || 'stretch';
            return true;
        }

        if (path === 'justifyContent') {
            el.style.justifyContent = value || 'flex-start';
            return true;
        }

        if (path === 'gap') {
            el.style.gap = traits.normalizeSize(value) || '12px';
            return true;
        }

        // === BACKGROUND ===
        
        if (path === 'backgroundColor') {
            el.style.backgroundColor = value || '';
            return true;
        }

        // === HOVER EFFECT ===
        
        if (path === 'hoverEffect') {
            applyHoverEffect(block, value);
            return true;
        }

        // === LINK BEHAVIOR ===
        
        if (path === 'linkBehavior') {
            if (value === 'card' || value === 'button') {
                el.style.cursor = block.config.cursor || 'pointer';
            } else {
                el.style.cursor = '';
            }
            return true;
        }

        if (path === 'cursor') {
            if (block.config.linkBehavior !== 'none') {
                el.style.cursor = value || 'pointer';
            }
            return true;
        }

        // === DELEGAR A TRAITS COMUNES ===
        return traits.handleCommonUpdate(el, path, value);
    }

    /**
     * Aplica efecto hover al item.
     * Los efectos se aplican vía CSS class para que afecten también a los clones.
     * 
     * @param {Object} block Referencia al bloque
     * @param {string} effect Tipo de efecto (none, lift, scale, glow)
     */
    function applyHoverEffect(block, effect) {
        var el = block.element;
        
        // Remover clases de efectos previas
        el.classList.remove('gbn-hover-lift', 'gbn-hover-scale', 'gbn-hover-glow');

        // Aplicar nueva clase
        if (effect === 'lift') {
            el.classList.add('gbn-hover-lift');
        } else if (effect === 'scale') {
            el.classList.add('gbn-hover-scale');
        } else if (effect === 'glow') {
            el.classList.add('gbn-hover-glow');
        }

        // También inyectar CSS para los efectos si no existe
        ensureHoverStyles();
    }

    /**
     * Asegura que los estilos CSS de hover existan en el documento.
     */
    function ensureHoverStyles() {
        if (document.getElementById('gbn-post-item-hover-styles')) return;

        var style = document.createElement('style');
        style.id = 'gbn-post-item-hover-styles';
        style.textContent = [
            '.gbn-hover-lift:hover { box-shadow: 0 8px 16px rgba(0,0,0,0.1); transform: translateY(-4px); }',
            '.gbn-hover-scale:hover { transform: scale(1.02); }',
            '.gbn-hover-glow:hover { box-shadow: 0 0 20px rgba(59, 130, 246, 0.3); }',
        ].join('\n');

        document.head.appendChild(style);
    }

    /**
     * Inicializa el componente PostItem.
     * 
     * @param {Object} block Referencia al bloque
     */
    function init(block) {
        if (!block || !block.element) return;

        var config = block.config || {};

        // Aplicar estilos iniciales
        var styles = getStyles(config, block);
        Object.keys(styles).forEach(function(prop) {
            var jsProp = prop.replace(/-([a-z])/g, function(g) { return g[1].toUpperCase(); });
            block.element.style[jsProp] = styles[prop];
        });

        // Aplicar hover effect si existe
        if (config.hoverEffect && config.hoverEffect !== 'none') {
            applyHoverEffect(block, config.hoverEffect);
        }

        // Marcar como inicializado
        block.element.dataset.gbnInitialized = 'true';
    }

    // Exportar renderer
    Gbn.ui.renderers.postItem = {
        getStyles: getStyles,
        handleUpdate: handleUpdate,
        init: init
    };

})(typeof window !== 'undefined' ? window : this);
