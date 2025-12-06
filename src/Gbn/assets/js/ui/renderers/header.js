;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var traits = Gbn.ui.renderers.traits;

    /**
     * Renderer para HeaderComponent.
     * 
     * Maneja la visualización y actualización en tiempo real del header.
     * Replica la funcionalidad de Glory HeaderRenderer pero para el builder GBN.
     */

    /**
     * Genera los estilos CSS para el header.
     * @param {Object} config Configuración del bloque
     * @param {Object} block Bloque completo
     * @returns {Object} Objeto con propiedades CSS
     */
    function getStyles(config, block) {
        var styles = {};

        // Fondo
        if (config.backgroundColor) {
            styles['background-color'] = config.backgroundColor;
        }

        // Padding usando traits
        if (config.padding) {
            var spacingStyles = traits ? traits.getSpacingStyles(config.padding, 'padding') : {};
            Object.assign(styles, spacingStyles);
        }

        // Z-Index
        if (config.zIndex) {
            styles['z-index'] = config.zIndex;
        }

        // Backdrop blur para efecto glassmorphism
        if (config.backdropBlur && parseInt(config.backdropBlur) > 0) {
            var blurValue = config.backdropBlur + 'px';
            styles['backdrop-filter'] = 'blur(' + blurValue + ')';
            styles['-webkit-backdrop-filter'] = 'blur(' + blurValue + ')';
        }

        // Fixed positioning
        if (config.isFixed) {
            styles['position'] = 'fixed';
            styles['top'] = '0';
            styles['left'] = '0';
            styles['width'] = '100%';
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

        // Propiedades específicas del header
        if (path === 'backgroundColor') {
            el.style.backgroundColor = value || '';
            return true;
        }

        if (path === 'backgroundColorScrolled') {
            // Almacenar valor para uso con scroll
            el.dataset.scrolledBg = value || '';
            return true;
        }

        if (path === 'isFixed') {
            if (value) {
                el.style.position = 'fixed';
                el.style.top = '0';
                el.style.left = '0';
                el.style.width = '100%';
            } else {
                el.style.position = '';
                el.style.top = '';
                el.style.left = '';
                el.style.width = '';
            }
            return true;
        }

        if (path === 'showScrollEffect') {
            // Toggle del efecto de scroll
            el.dataset.scrollEffect = value ? 'true' : 'false';
            return true;
        }

        if (path === 'scrolledClass') {
            el.dataset.scrolledClass = value || 'scrolled';
            return true;
        }

        if (path === 'zIndex') {
            el.style.zIndex = value || '';
            return true;
        }

        if (path === 'backdropBlur') {
            var blur = parseInt(value) || 0;
            if (blur > 0) {
                el.style.backdropFilter = 'blur(' + blur + 'px)';
                el.style.webkitBackdropFilter = 'blur(' + blur + 'px)';
            } else {
                el.style.backdropFilter = '';
                el.style.webkitBackdropFilter = '';
            }
            return true;
        }

        if (path === 'containerMaxWidth') {
            var container = el.querySelector('.siteMenuContainer, .gbn-header-container');
            if (container) {
                container.style.maxWidth = value || '';
            }
            return true;
        }

        if (path === 'customClass') {
            // Limpiar clases anteriores y agregar nueva
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
     * Inicializa el comportamiento de scroll para el header.
     * Llamado después de que el header está en el DOM.
     * 
     * @param {HTMLElement} headerElement Elemento del header
     * @param {Object} config Configuración del header
     */
    function initScrollBehavior(headerElement, config) {
        if (!headerElement || !config.showScrollEffect) return;

        var scrolledClass = config.scrolledClass || 'scrolled';
        var scrolledBg = config.backgroundColorScrolled || 'rgba(255, 255, 255, 0.9)';
        var originalBg = config.backgroundColor || 'transparent';

        function handleScroll() {
            if (window.scrollY > 20) {
                headerElement.classList.add(scrolledClass);
                headerElement.style.backgroundColor = scrolledBg;
            } else {
                headerElement.classList.remove(scrolledClass);
                headerElement.style.backgroundColor = originalBg;
            }
        }

        // Remover listener anterior si existe
        if (headerElement._scrollHandler) {
            window.removeEventListener('scroll', headerElement._scrollHandler);
        }

        headerElement._scrollHandler = handleScroll;
        window.addEventListener('scroll', handleScroll, { passive: true });

        // Ejecutar una vez para estado inicial
        handleScroll();
    }

    // Exportar renderer
    Gbn.ui.renderers.header = {
        getStyles: getStyles,
        handleUpdate: handleUpdate,
        initScrollBehavior: initScrollBehavior
    };

})(typeof window !== 'undefined' ? window : this);
