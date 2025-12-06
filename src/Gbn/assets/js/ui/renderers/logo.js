;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var traits = Gbn.ui.renderers.traits;

    /**
     * Renderer para LogoComponent (Refactorizado BUG-016)
     * Soporta traits comunes y modos: imagen, texto, SVG.
     * Usa variables CSS para propiedades de hijos (--gbn-logo-*)
     */

    /**
     * Helper para filtro CSS
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
                return 'none';
        }
    }

    /**
     * Obtiene el nombre del sitio
     */
    function getSiteName() {
        return (window.gloryGbnCfg && window.gloryGbnCfg.siteTitle) || 'Logo';
    }

    /**
     * Genera los estilos CSS para el logo.
     */
    function getStyles(config, block) {
        // Usar traits comunes (Spacing, Typography, Dimensions, Color)
        var styles = traits.getCommonStyles(config);

        // Estilos específicos directos (Wrapper)
        if (config.maxHeight) styles['max-height'] = traits.normalizeSize(config.maxHeight);
        if (config.maxWidth) styles['max-width'] = traits.normalizeSize(config.maxWidth);

        // Variables CSS para hijos (Image)
        // Se aplican al wrapper y heredan/úsan los hijos vía CSS en theme-styles.css
        if (config.logoMode === 'image') {
            if (config.objectFit) {
                styles['--gbn-logo-object-fit'] = config.objectFit;
            }
            if (config.filter) {
                styles['--gbn-logo-filter'] = getFilterCss(config.filter);
            }
        }

        return styles;
    }

    /**
     * Actualiza la estructura del logo según el modo seleccionado.
     */
    function updateLogoMode(el, mode, config) {
        var link = el.querySelector('a');
        if (!link) {
            link = document.createElement('a');
            link.href = config.linkUrl || '/';
            link.rel = 'home';
            link.className = 'gbn-logo-link';
            el.appendChild(link);
        }

        // Limpiar contenido actual del link
        link.innerHTML = '';

        switch (mode) {
            case 'image':
                var img = document.createElement('img');
                // Si no hay imagen, usar placeholder transparente o nada
                img.src = config.logoImage || ''; 
                img.alt = getSiteName();
                img.className = 'gbn-logo-img';
                
                // NOTA: Ya no aplicamos estilos inline para filter/object-fit
                // Se manejan via CSS Variables en el wrapper (handleUpdate)
                
                link.appendChild(img);
                break;

            case 'text':
                var textSpan = document.createElement('span');
                textSpan.className = 'gbn-logo-text';
                textSpan.textContent = config.logoText || getSiteName();
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
     * Maneja actualizaciones en tiempo real.
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;
        var link = el.querySelector('a');
        var img = el.querySelector('img');
        var textEl = el.querySelector('.gbn-logo-text');

        // Primero intentar traits comunes
        if (traits.handleCommonUpdate(el, path, value)) {
            return true;
        }

        // --- Propiedades Estructurales ---

        if (path === 'logoMode') {
            updateLogoMode(el, value, block.config);
            return true;
        }

        if (path === 'logoText') {
            if (textEl) {
                textEl.textContent = value || getSiteName();
            } else if (block.config.logoMode === 'text') {
                updateLogoMode(el, 'text', block.config);
            }
            return true;
        }

        if (path === 'logoImage') {
            if (img) {
                img.src = value || '';
            } else if (block.config.logoMode === 'image') {
                updateLogoMode(el, 'image', block.config);
            }
            return true;
        }

        if (path === 'logoSvg') {
            var svgContainer = el.querySelector('.gbn-logo-svg');
            if (svgContainer) {
                svgContainer.innerHTML = value || '';
            } else if (block.config.logoMode === 'svg') {
                updateLogoMode(el, 'svg', block.config);
            }
            return true;
        }

        if (path === 'linkUrl') {
            if (link) link.href = value || '/';
            return true;
        }

        // --- Propiedades de Estilo (Wrapper + Variables) ---

        if (path === 'maxHeight') {
            var val = traits.normalizeSize(value);
            el.style.maxHeight = val || '';
            // No necesitamos aplicarlo a img si usamos CSS correctamente (img height: auto)
            // Pero mantenemos width auto en CSS.
            return true;
        }

        if (path === 'maxWidth') {
            el.style.maxWidth = traits.normalizeSize(value) || '';
            return true;
        }
        
        // Uso de Variables CSS para propiedades de imagen
        if (path === 'objectFit') {
            el.style.setProperty('--gbn-logo-object-fit', value || 'contain');
            return true;
        }

        if (path === 'filter') {
            el.style.setProperty('--gbn-logo-filter', getFilterCss(value));
            return true;
        }

        return false;
    }

    // Exportar renderer
    Gbn.ui.renderers.logo = {
        getStyles: getStyles,
        handleUpdate: handleUpdate,
        updateLogoMode: updateLogoMode
    };

})(typeof window !== 'undefined' ? window : this);
