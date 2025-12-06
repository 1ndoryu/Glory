;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var traits = Gbn.ui.renderers.traits;

    /**
     * Renderer para FooterComponent.
     * 
     * Contenedor principal del footer del sitio.
     */

    /**
     * Genera los estilos CSS para el footer.
     * @param {Object} config Configuración del bloque
     * @param {Object} block Bloque completo
     * @returns {Object} Objeto con propiedades CSS
     */
    function getStyles(config, block) {
        var styles = {};

        // 1. Colores y Variables CSS
        if (config.backgroundColor) {
            styles['background-color'] = config.backgroundColor;
        }
        if (config.color) { // Standardized from textColor
            styles['color'] = config.color;
        }
        
        // Variables para enlaces (Sincronización con CSS)
        if (config.linkColor) {
            styles['--gbn-footer-link-color'] = config.linkColor;
        }
        if (config.linkColorHover) {
            styles['--gbn-footer-link-hover-color'] = config.linkColorHover;
        }

        // 2. Traits Integration
        if (traits) {
            // Spacing
            if (config.padding) {
                Object.assign(styles, traits.getSpacingStyles(config.padding, 'padding'));
            }
            // Typography (Standard)
            if (config.typography) {
                 Object.assign(styles, traits.getTypographyStyles(config.typography));
            }
            // Text Align
            if (config.textAlign) {
                styles['text-align'] = config.textAlign;
            }
             // Background (Image, Size, Position)
            if (traits.getBackgroundStyles && (config.backgroundImage || config.backgroundColor)) {
                 Object.assign(styles, traits.getBackgroundStyles(config));
            }
        }

        return styles;
    }

    /**
     * Maneja actualizaciones en tiempo real desde el panel.
     * @param {Object} block Bloque
     * @param {string} path Ruta de la propiedad
     * @param {*} value Nuevo valor
     * @returns {boolean} True si se manejó, false para delegar
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;
        var container = el.querySelector('.gbn-footer-container');
        var contentArea = el.querySelector('.gbn-footer-content');

        // --- Traits Delegation (Prioridad Alta) ---
        if (traits && traits.handleCommonUpdate) {
            if (traits.handleCommonUpdate(el, path, value)) {
                return true;
            }
        }

        // --- Campos Específicos ---

        // Color de fondo
        if (path === 'backgroundColor') {
            el.style.backgroundColor = value || '';
            return true;
        }

        // Color de texto (Estandarizado)
        if (path === 'color') {
            el.style.color = value || '';
            return true;
        }

        // Enlaces
        if (path === 'linkColor') {
            el.style.setProperty('--gbn-footer-link-color', value || '');
            // Actualización visual inmediata (Legacy fallback)
            var links = el.querySelectorAll('a');
            links.forEach(function(link) { 
                link.style.color = value || ''; 
            });
            el.dataset.linkColor = value || '';
            return true;
        }

        if (path === 'linkColorHover') {
            el.style.setProperty('--gbn-footer-link-hover-color', value || '');
            applyLinkHoverStyles(el, value);
            return true;
        }

        // Copyright
        if (path === 'copyrightText') {
            updateCopyright(el, { copyrightText: value });
            return true;
        }

        // Redes Sociales
        if (path === 'showSocialLinks') {
            var socialContainer = el.querySelector('.gbn-footer-social');
            if (socialContainer) {
                socialContainer.style.display = value ? 'flex' : 'none';
            }
            return true;
        }

        // Layout de columnas
        if (path === 'columnsLayout') {
            if (contentArea) {
                var columns = parseInt(value) || 3;
                contentArea.style.display = 'grid';
                contentArea.style.gridTemplateColumns = 'repeat(' + columns + ', 1fr)';
            }
            return true;
        }

        // Gap
        if (path === 'gap') {
            if (contentArea) {
                contentArea.style.gap = value || '';
            }
            return true;
        }

        // Ancho Máximo
        if (path === 'containerMaxWidth') {
            if (container) {
                container.style.maxWidth = value || '';
                container.style.margin = '0 auto';
            }
            return true;
        }

        return false;
    }

    /**
     * Aplica estilos hover a enlaces (JS fallback).
     */
    function applyLinkHoverStyles(el, hoverColor) {
        var links = el.querySelectorAll('a');
        var originalColor = el.style.getPropertyValue('--gbn-footer-link-color') || el.dataset.linkColor || '';

        links.forEach(function(link) {
            if (link._hoverIn) {
                link.removeEventListener('mouseenter', link._hoverIn);
                link.removeEventListener('mouseleave', link._hoverOut);
            }

            if (hoverColor) {
                link._hoverIn = function() { link.style.color = hoverColor; };
                link._hoverOut = function() { link.style.color = originalColor; };
                link.addEventListener('mouseenter', link._hoverIn);
                link.addEventListener('mouseleave', link._hoverOut);
            }
        });
    }

    /**
     * Actualiza el texto del copyright.
     */
    function updateCopyright(footerElement, config) {
        var copyrightEl = footerElement.querySelector('.gbn-footer-bottom p, .gbn-footer-copyright');
        if (copyrightEl && config.copyrightText) {
            var text = config.copyrightText;
            text = text.replace(/{year}/g, new Date().getFullYear());
            text = text.replace(/{siteName}/g, (global.gloryGbnCfg && global.gloryGbnCfg.siteTitle) || '');
            copyrightEl.innerHTML = text;
        }
    }

    // Exportar renderer
    Gbn.ui.renderers.footer = {
        getStyles: getStyles,
        handleUpdate: handleUpdate,
        updateCopyright: updateCopyright
    };

})(typeof window !== 'undefined' ? window : this);
