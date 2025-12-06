;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var traits = Gbn.ui.renderers.traits;

    /**
     * Renderer para LogoComponent.
     * 
     * Subcomponente del header que gestiona el logo.
     * Soporta modos: imagen, texto, SVG.
     */

    /**
     * Genera los estilos CSS para el logo.
     * @param {Object} config Configuración del bloque
     * @param {Object} block Bloque completo
     * @returns {Object} Objeto con propiedades CSS
     */
    function getStyles(config, block) {
        var styles = {};

        // Dimensiones
        if (config.maxHeight) {
            styles['max-height'] = config.maxHeight;
        }
        if (config.maxWidth && config.maxWidth !== 'auto') {
            styles['max-width'] = config.maxWidth;
        }

        // Estilos de texto (solo para modo texto)
        if (config.logoMode === 'text') {
            if (config.color) {
                styles['color'] = config.color;
            }
            if (config.fontSize) {
                styles['font-size'] = config.fontSize;
            }
            if (config.fontWeight) {
                styles['font-weight'] = config.fontWeight;
            }
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
        var link = el.querySelector('a');
        var img = el.querySelector('img');
        var textEl = el.querySelector('.gbn-logo-text, .glory-logo-text');

        // Modo de logo
        if (path === 'logoMode') {
            updateLogoMode(el, value, block.config);
            return true;
        }

        // Texto del logo
        if (path === 'logoText') {
            if (textEl) {
                textEl.textContent = value || getSiteName();
            }
            return true;
        }

        // Imagen del logo
        if (path === 'logoImage') {
            if (img) {
                img.src = value || '';
            } else if (value && block.config.logoMode === 'image') {
                // Crear imagen si no existe
                updateLogoMode(el, 'image', { logoImage: value });
            }
            return true;
        }

        // SVG del logo
        if (path === 'logoSvg') {
            var svgContainer = el.querySelector('.gbn-logo-svg');
            if (svgContainer) {
                svgContainer.innerHTML = value || '';
            }
            return true;
        }

        // URL del enlace
        if (path === 'linkUrl') {
            if (link) {
                link.href = value || '/';
            }
            return true;
        }

        // Dimensiones
        if (path === 'maxHeight') {
            if (img) img.style.maxHeight = value || '';
            el.style.maxHeight = value || '';
            return true;
        }

        if (path === 'maxWidth') {
            if (img) img.style.maxWidth = value || '';
            el.style.maxWidth = value || '';
            return true;
        }

        // Color de texto
        if (path === 'color') {
            if (textEl) textEl.style.color = value || '';
            if (link) link.style.color = value || '';
            return true;
        }

        // Tamaño de fuente
        if (path === 'fontSize') {
            if (textEl) textEl.style.fontSize = value || '';
            if (link) link.style.fontSize = value || '';
            return true;
        }

        // Peso de fuente
        if (path === 'fontWeight') {
            if (textEl) textEl.style.fontWeight = value || '';
            if (link) link.style.fontWeight = value || '';
            return true;
        }

        // Filtro de imagen
        if (path === 'filter') {
            if (img) {
                var filterValue = getFilterCss(value);
                img.style.filter = filterValue;
            }
            return true;
        }

        // Delegar a traits para spacing
        if (traits && traits.handleCommonUpdate) {
            return traits.handleCommonUpdate(el, path, value);
        }

        return false;
    }

    /**
     * Actualiza la estructura del logo según el modo seleccionado.
     * @param {HTMLElement} el Elemento del logo
     * @param {string} mode Modo: 'image', 'text', 'svg'
     * @param {Object} config Configuración actual
     */
    function updateLogoMode(el, mode, config) {
        var link = el.querySelector('a');
        if (!link) {
            link = document.createElement('a');
            link.href = config.linkUrl || '/';
            link.rel = 'home';
            el.appendChild(link);
        }

        // Limpiar contenido actual del link
        link.innerHTML = '';

        switch (mode) {
            case 'image':
                var img = document.createElement('img');
                img.src = config.logoImage || '';
                img.alt = getSiteName();
                img.className = 'gbn-logo-img';
                if (config.maxHeight) img.style.maxHeight = config.maxHeight;
                if (config.filter) img.style.filter = getFilterCss(config.filter);
                link.appendChild(img);
                break;

            case 'text':
                var textSpan = document.createElement('span');
                textSpan.className = 'gbn-logo-text';
                textSpan.textContent = config.logoText || getSiteName();
                if (config.color) textSpan.style.color = config.color;
                if (config.fontSize) textSpan.style.fontSize = config.fontSize;
                if (config.fontWeight) textSpan.style.fontWeight = config.fontWeight;
                link.appendChild(textSpan);
                break;

            case 'svg':
                var svgContainer = document.createElement('span');
                svgContainer.className = 'gbn-logo-svg';
                svgContainer.innerHTML = config.logoSvg || '';
                link.appendChild(svgContainer);
                break;

            default:
                // Fallback a texto
                var defaultText = document.createElement('span');
                defaultText.className = 'gbn-logo-text';
                defaultText.textContent = getSiteName();
                link.appendChild(defaultText);
        }
    }

    /**
     * Obtiene el valor CSS para el filtro de imagen.
     * @param {string} filterAlias Alias: 'none', 'white', 'black', 'grayscale'
     * @returns {string} Valor CSS del filtro
     */
    function getFilterCss(filterAlias) {
        switch (filterAlias) {
            case 'white':
                return 'brightness(0) invert(1)';
            case 'black':
                return 'brightness(0)';
            case 'grayscale':
                return 'grayscale(100%)';
            case 'none':
            default:
                return '';
        }
    }

    /**
     * Obtiene el nombre del sitio desde la configuración de GBN.
     * @returns {string} Nombre del sitio
     */
    function getSiteName() {
        return (window.gloryGbnCfg && window.gloryGbnCfg.siteTitle) || 'Logo';
    }

    // Exportar renderer
    Gbn.ui.renderers.logo = {
        getStyles: getStyles,
        handleUpdate: handleUpdate,
        updateLogoMode: updateLogoMode
    };

})(typeof window !== 'undefined' ? window : this);
