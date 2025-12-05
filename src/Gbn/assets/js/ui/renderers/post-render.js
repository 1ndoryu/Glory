;(function (global) {
    'use strict';

    /**
     * POST RENDER RENDERER
     * 
     * Renderer para el componente contenedor PostRender.
     * Maneja el layout del grid/flex y solicita preview desde el backend.
     * 
     * @module Gbn.ui.renderers.postRender
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

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

        // Layout del contenedor según displayMode
        var displayMode = config.displayMode || 'grid';

        if (displayMode === 'grid') {
            var columns = parseInt(config.gridColumns, 10) || 3;
            var gap = config.gap || '20px';

            styles['display'] = 'grid';
            styles['grid-template-columns'] = 'repeat(' + columns + ', 1fr)';
            styles['gap'] = traits.normalizeSize(gap);
        } else if (displayMode === 'flex') {
            styles['display'] = 'flex';
            styles['flex-direction'] = config.flexDirection || 'row';
            styles['flex-wrap'] = config.flexWrap || 'wrap';
            styles['align-items'] = config.alignItems || 'stretch';
            styles['justify-content'] = config.justifyContent || 'flex-start';
            styles['gap'] = traits.normalizeSize(config.gap) || '20px';
        } else {
            styles['display'] = 'block';
        }

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
        
        if (path === 'displayMode') {
            // Cambiar modo de visualización
            applyDisplayMode(el, value, block.config);
            return true;
        }

        if (path === 'gridColumns') {
            if (block.config.displayMode === 'grid') {
                var cols = parseInt(value, 10) || 3;
                el.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
            }
            return true;
        }

        if (path === 'gap') {
            el.style.gap = traits.normalizeSize(value) || '20px';
            return true;
        }

        // Flex options
        if (path === 'flexDirection') {
            el.style.flexDirection = value || 'row';
            return true;
        }

        if (path === 'flexWrap') {
            el.style.flexWrap = value || 'wrap';
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

        // === PROPIEDADES DE QUERY - Requieren refresh del preview ===
        
        var queryProps = ['postType', 'postsPerPage', 'orderBy', 'order', 'status', 'offset', 'postIn', 'postNotIn'];
        if (queryProps.indexOf(path) !== -1) {
            // Solicitar nuevo preview cuando cambia la query
            requestPreview(block);
            return true;
        }

        // === DELEGAR A TRAITS COMUNES ===
        return traits.handleCommonUpdate(el, path, value);
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
            el.style.flexDirection = config.flexDirection || 'row';
            el.style.flexWrap = config.flexWrap || 'wrap';
            el.style.alignItems = config.alignItems || 'stretch';
            el.style.justifyContent = config.justifyContent || 'flex-start';
            el.style.gap = traits.normalizeSize(config.gap) || '20px';
        } else {
            el.style.display = 'block';
        }
    }

    /**
     * Solicita un preview del contenido desde el backend.
     * Se usa cuando cambian propiedades de query (postType, order, etc.)
     * 
     * @param {Object} block Referencia al bloque
     */
    function requestPreview(block) {
        if (!block || !block.element || !block.config) return;

        var el = block.element;
        var config = block.config;

        // Mostrar indicador de carga
        el.classList.add('gbn-loading');

        // Para el MVP, solo mostramos un mensaje
        // TODO: Implementar AJAX request real en Fase 13.4
        console.log('[PostRender] Requesting preview with config:', config);

        // Simular carga para feedback visual
        setTimeout(function() {
            el.classList.remove('gbn-loading');
            
            // Placeholder: mostrar mensaje de preview
            var existingPlaceholder = el.querySelector('.gbn-pr-preview-message');
            if (!existingPlaceholder) {
                var msg = document.createElement('div');
                msg.className = 'gbn-pr-preview-message';
                msg.style.cssText = 'padding: 20px; text-align: center; background: #f5f5f5; border-radius: 8px; color: #666; margin: 10px;';
                msg.innerHTML = '<p style="margin:0">📋 <strong>' + (config.postType || 'post') + '</strong> - ' + 
                               (config.postsPerPage || 6) + ' items, ordenados por ' + (config.orderBy || 'date') + '</p>' +
                               '<p style="margin:8px 0 0; font-size: 12px;">Preview real disponible en Fase 13.4</p>';
                el.appendChild(msg);
            } else {
                existingPlaceholder.innerHTML = '<p style="margin:0">📋 <strong>' + (config.postType || 'post') + '</strong> - ' + 
                                               (config.postsPerPage || 6) + ' items, ordenados por ' + (config.orderBy || 'date') + '</p>';
            }
        }, 300);
    }

    /**
     * Inicializa el componente PostRender cuando se detecta en el DOM.
     * 
     * @param {Object} block Referencia al bloque
     */
    function init(block) {
        if (!block || !block.element) return;

        // Aplicar estilos iniciales
        var styles = getStyles(block.config || {}, block);
        Object.keys(styles).forEach(function(prop) {
            block.element.style[prop.replace(/-([a-z])/g, function(g) { return g[1].toUpperCase(); })] = styles[prop];
        });

        // Marcar como inicializado
        block.element.dataset.gbnInitialized = 'true';
    }

    // Exportar renderer
    Gbn.ui.renderers.postRender = {
        getStyles: getStyles,
        handleUpdate: handleUpdate,
        init: init,
        requestPreview: requestPreview
    };

})(typeof window !== 'undefined' ? window : this);
