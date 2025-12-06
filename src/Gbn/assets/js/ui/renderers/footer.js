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

        // Colores
        if (config.backgroundColor) {
            styles['background-color'] = config.backgroundColor;
        }
        if (config.textColor) {
            styles['color'] = config.textColor;
        }

        // Padding usando traits
        if (config.padding) {
            var spacingStyles = traits ? traits.getSpacingStyles(config.padding, 'padding') : {};
            Object.assign(styles, spacingStyles);
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
        var links = el.querySelectorAll('a');

        // Color de fondo
        if (path === 'backgroundColor') {
            el.style.backgroundColor = value || '';
            return true;
        }

        // Color de texto
        if (path === 'textColor') {
            el.style.color = value || '';
            return true;
        }

        // Color de enlaces
        if (path === 'linkColor') {
            links.forEach(function(link) {
                link.style.color = value || '';
            });
            el.dataset.linkColor = value || '';
            return true;
        }

        // Color hover de enlaces
        if (path === 'linkColorHover') {
            el.dataset.linkColorHover = value || '';
            applyLinkHoverStyles(el, value);
            return true;
        }

        // Texto de copyright
        if (path === 'copyrightText') {
            var copyrightEl = el.querySelector('.gbn-footer-bottom p, .gbn-footer-copyright');
            if (copyrightEl) {
                var text = value || '';
                // Reemplazar {year} con el año actual
                text = text.replace(/{year}/g, new Date().getFullYear());
                copyrightEl.innerHTML = text;
            }
            return true;
        }

        // Mostrar redes sociales
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

        // Gap entre columnas
        if (path === 'gap') {
            if (contentArea) {
                contentArea.style.gap = value || '';
            }
            return true;
        }

        // Ancho máximo del contenedor
        if (path === 'containerMaxWidth') {
            if (container) {
                container.style.maxWidth = value || '';
                container.style.margin = '0 auto';
            }
            return true;
        }

        // Clases personalizadas
        if (path === 'customClass') {
            // Limpiar clases anteriores
            el.className = el.className.replace(/\bgbn-custom-\S+/g, '').trim();
            if (value) {
                value.split(' ').forEach(function(cls) {
                    if (cls) el.classList.add(cls);
                });
            }
            return true;
        }

        // Delegar a traits para spacing y propiedades comunes
        if (traits && traits.handleCommonUpdate) {
            return traits.handleCommonUpdate(el, path, value);
        }

        return false;
    }

    /**
     * Aplica estilos hover a los enlaces del footer.
     * @param {HTMLElement} el Elemento del footer
     * @param {string} hoverColor Color hover
     */
    function applyLinkHoverStyles(el, hoverColor) {
        var links = el.querySelectorAll('a');
        var originalColor = el.dataset.linkColor || '';

        links.forEach(function(link) {
            // Remover listeners anteriores
            if (link._hoverIn) {
                link.removeEventListener('mouseenter', link._hoverIn);
                link.removeEventListener('mouseleave', link._hoverOut);
            }

            link._hoverIn = function() {
                link.style.color = hoverColor || '';
            };
            link._hoverOut = function() {
                link.style.color = originalColor;
            };

            link.addEventListener('mouseenter', link._hoverIn);
            link.addEventListener('mouseleave', link._hoverOut);
        });
    }

    /**
     * Actualiza el texto del copyright con placeholders.
     * @param {HTMLElement} footerElement Elemento del footer
     * @param {Object} config Configuración del footer
     */
    function updateCopyright(footerElement, config) {
        var copyrightEl = footerElement.querySelector('.gbn-footer-bottom p, .gbn-footer-copyright');
        if (copyrightEl && config.copyrightText) {
            var text = config.copyrightText;
            text = text.replace(/{year}/g, new Date().getFullYear());
            text = text.replace(/{siteName}/g, window.gloryGbnCfg && window.gloryGbnCfg.siteTitle || '');
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
