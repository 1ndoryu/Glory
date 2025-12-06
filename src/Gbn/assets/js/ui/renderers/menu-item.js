;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var traits = Gbn.ui.renderers.traits;

    /**
     * Renderer para MenuItemComponent.
     * 
     * Maneja items individuales del menú de navegación.
     */

    /**
     * Genera los estilos CSS para un menu item.
     * @param {Object} config Configuración del bloque
     * @param {Object} block Bloque completo
     * @returns {Object} Objeto con propiedades CSS
     */
    function getStyles(config, block) {
        var styles = {};

        // Color de texto
        if (config.color) {
            styles['color'] = config.color;
        }

        // Tamaño de fuente
        if (config.fontSize) {
            styles['font-size'] = config.fontSize;
        }

        // Peso de fuente
        if (config.fontWeight) {
            styles['font-weight'] = config.fontWeight;
        }

        // Padding usando traits
        if (config.padding && traits && traits.getSpacingStyles) {
            var spacingStyles = traits.getSpacingStyles(config.padding, 'padding');
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

        // Texto del enlace
        if (path === 'linkText') {
            if (link) {
                link.textContent = value || 'Enlace';
            }
            return true;
        }

        // URL del enlace
        if (path === 'linkUrl') {
            if (link) {
                link.href = value || '#';
            }
            return true;
        }

        // Target del enlace
        if (path === 'linkTarget') {
            if (link) {
                if (value === '_blank') {
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                } else {
                    link.removeAttribute('target');
                    link.removeAttribute('rel');
                }
            }
            return true;
        }

        // Color de texto
        if (path === 'color') {
            if (link) link.style.color = value || '';
            return true;
        }

        // Color hover
        if (path === 'colorHover') {
            el.dataset.hoverColor = value || '';
            applyHoverStyles(el, value);
            return true;
        }

        // Tamaño de fuente
        if (path === 'fontSize') {
            if (link) link.style.fontSize = value || '';
            return true;
        }

        // Peso de fuente
        if (path === 'fontWeight') {
            if (link) link.style.fontWeight = value || '';
            return true;
        }

        // Clase personalizada
        if (path === 'customClass') {
            el.className = el.className.replace(/\bgbn-custom-\S+/g, '').trim();
            if (value) {
                value.split(' ').forEach(function(cls) {
                    if (cls) el.classList.add(cls);
                });
            }
            return true;
        }

        // Marcar como activo
        if (path === 'isActive') {
            if (value) {
                el.classList.add('current-menu-item');
            } else {
                el.classList.remove('current-menu-item');
            }
            return true;
        }

        // Tiene submenú
        if (path === 'hasSubmenu') {
            if (value) {
                el.classList.add('menu-item-has-children');
                // Crear submenú vacío si no existe
                if (!el.querySelector('.sub-menu')) {
                    var subMenu = document.createElement('ul');
                    subMenu.className = 'sub-menu menu menu-level-2';
                    el.appendChild(subMenu);
                }
            } else {
                el.classList.remove('menu-item-has-children');
                var existingSubmenu = el.querySelector('.sub-menu');
                if (existingSubmenu && existingSubmenu.children.length === 0) {
                    existingSubmenu.remove();
                }
            }
            return true;
        }

        // Delegar a traits para spacing
        if (traits && traits.handleCommonUpdate) {
            return traits.handleCommonUpdate(link || el, path, value);
        }

        return false;
    }

    /**
     * Aplica estilos hover al enlace.
     * @param {HTMLElement} el Elemento del menu item
     * @param {string} hoverColor Color hover
     */
    function applyHoverStyles(el, hoverColor) {
        var link = el.querySelector('a');
        if (!link) return;

        var originalColor = link.style.color || '';

        // Remover listeners anteriores
        if (link._hoverIn) {
            link.removeEventListener('mouseenter', link._hoverIn);
            link.removeEventListener('mouseleave', link._hoverOut);
        }

        if (hoverColor) {
            link._hoverIn = function() {
                link.style.color = hoverColor;
            };
            link._hoverOut = function() {
                link.style.color = originalColor;
            };

            link.addEventListener('mouseenter', link._hoverIn);
            link.addEventListener('mouseleave', link._hoverOut);
        }
    }

    // Exportar renderer
    Gbn.ui.renderers.menuItem = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
