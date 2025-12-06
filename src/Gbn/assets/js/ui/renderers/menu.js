;(function (global) {
    'use strict';

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var traits = Gbn.ui.renderers.traits;

    /**
     * Renderer para MenuComponent.
     * 
     * Subcomponente del header que gestiona el menú de navegación.
     * Soporta menús dinámicos de WordPress y manuales.
     */

    /**
     * Genera los estilos CSS para el menú.
     * @param {Object} config Configuración del bloque
     * @param {Object} block Bloque completo
     * @returns {Object} Objeto con propiedades CSS
     */
    function getStyles(config, block) {
        var styles = {};

        // Layout / Direction (Standardized)
        var dir = config.direction || (config.layout === 'vertical' ? 'column' : 'row'); // Default row

        if (dir === 'row') {
            styles['display'] = 'flex';
            styles['flex-direction'] = 'row';
            styles['align-items'] = 'center';
        } else if (dir === 'column') {
            styles['display'] = 'flex';
            styles['flex-direction'] = 'column';
            styles['align-items'] = 'flex-start';
        }

        // Gap entre items (Standardized unit)
        if (config.gap) {
            styles['gap'] = typeof config.gap === 'number' ? config.gap + 'px' : config.gap;
        }

        // Tipografía (HasTypography)
        // Nota: HasTypography guarda en config.typography = { size, weight, transform ... }
        var typ = config.typography || {};
        
        if (typ.size) {
            styles['font-size'] = typ.size;
        }
        if (typ.weight) {
            styles['font-weight'] = typ.weight;
        }
        if (typ.transform) {
            styles['text-transform'] = typ.transform;
        }
        
        // Color (replaces linkColor) - Se aplica via CSS heredado o handleUpdate
        // Si se aplica a wrapper, 'a' debe heredar.
        if (config.color) {
            styles['color'] = config.color;
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
        var menuList = el.querySelector('ul');
        var links = el.querySelectorAll('a');

        // Fuente del menú
        if (path === 'menuSource') {
            el.dataset.menuSource = value;
            // Nota: El cambio de fuente requiere reload del contenido desde PHP
            return true;
        }

        // Orientación del layout (Standardized)
        if (path === 'layout' || path === 'direction') {
            if (menuList) {
                var isRow;
                if (path === 'layout') isRow = (value === 'horizontal');
                else isRow = (value === 'row');

                if (isRow) {
                    menuList.style.display = 'flex';
                    menuList.style.flexDirection = 'row';
                    menuList.style.alignItems = 'center';
                } else {
                    menuList.style.display = 'flex';
                    menuList.style.flexDirection = 'column';
                    menuList.style.alignItems = 'flex-start';
                }
            }
            return true;
        }

        // Gap entre items
        if (path === 'gap') {
            if (menuList) {
                var val = typeof value === 'number' ? value + 'px' : value;
                menuList.style.gap = val || '';
            }
            return true;
        }

        // Color de enlaces (ahora 'color' por HasTypography)
        if (path === 'color') {
            links.forEach(function(link) {
                link.style.color = value || '';
            });
            // Guardar para aplicar a nuevos links y hover
            el.dataset.linkColor = value || '';
            return true;
        }

        // Color hover
        if (path === 'linkColorHover') {
            el.dataset.linkColorHover = value || '';
            applyHoverStyles(el, value);
            return true;
        }

        // Tamaño, Peso, Transformación (via typography.*)
        if (path === 'typography.size') {
            links.forEach(function(link) {
                link.style.fontSize = value || '';
            });
            return true;
        }

        if (path === 'typography.weight') {
            links.forEach(function(link) {
                link.style.fontWeight = value || '';
            });
            return true;
        }

        if (path === 'typography.transform') {
            links.forEach(function(link) {
                link.style.textTransform = value || '';
            });
            return true;
        }
        
        // Typography Composite (si se actualiza todo el objeto)
        if (path === 'typography') {
            var val = value || {};
            links.forEach(function(link) {
                if(val.size) link.style.fontSize = val.size;
                if(val.weight) link.style.fontWeight = val.weight;
                if(val.transform) link.style.textTransform = val.transform;
            });
            return true;
        }

        // Items manuales (formato: Título|URL por línea)
        if (path === 'manualItems') {
            if (block.config.menuSource === 'manual') {
                renderManualMenu(el, value);
            }
            return true;
        }

        // ID del menú
        if (path === 'menuId') {
            if (menuList) {
                menuList.id = value || 'mainMenu';
            }
            return true;
        }

        // Configuración móvil
        if (path === 'mobileBreakpoint') {
            el.dataset.mobileBreakpoint = value || '768px';
            return true;
        }

        if (path === 'mobileBackgroundColor') {
            el.dataset.mobileBg = value || '';
            return true;
        }

        if (path === 'mobileAnimation') {
            el.dataset.mobileAnimation = value || 'slideDown';
            return true;
        }

        // Delegar a traits para propiedades comunes
        if (traits && traits.handleCommonUpdate) {
            return traits.handleCommonUpdate(el, path, value);
        }

        return false;
    }

    /**
     * Aplica estilos hover a los enlaces del menú.
     * @param {HTMLElement} el Elemento del menú
     * @param {string} hoverColor Color hover
     */
    function applyHoverStyles(el, hoverColor) {
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
     * Renderiza un menú manual a partir del texto de configuración.
     * Formato: "Título|URL" por línea.
     * 
     * @param {HTMLElement} el Elemento del menú
     * @param {string} itemsText Texto con los items
     */
    function renderManualMenu(el, itemsText) {
        var menuList = el.querySelector('ul');
        if (!menuList) {
            menuList = document.createElement('ul');
            menuList.className = 'menu menu-level-1 gbn-menu-list';
            el.appendChild(menuList);
        }

        // Limpiar menú actual
        menuList.innerHTML = '';

        if (!itemsText) return;

        var lines = itemsText.split('\n').filter(function(line) {
            return line.trim() !== '';
        });

        lines.forEach(function(line) {
            var parts = line.split('|');
            var title = (parts[0] || '').trim();
            var url = (parts[1] || '#').trim();

            if (title) {
                var li = document.createElement('li');
                li.className = 'gbn-menu-item';

                var a = document.createElement('a');
                a.href = url;
                a.textContent = title;

                // Aplicar estilos actuales
                if (el.dataset.linkColor) {
                    a.style.color = el.dataset.linkColor;
                }

                li.appendChild(a);
                menuList.appendChild(li);
            }
        });

        // Re-aplicar estilos hover si existen
        if (el.dataset.linkColorHover) {
            applyHoverStyles(el, el.dataset.linkColorHover);
        }
    }

    /**
     * Inicializa el comportamiento del menú hamburguesa para móvil.
     * @param {HTMLElement} menuElement Elemento del menú
     * @param {HTMLElement} burgerButton Botón hamburguesa
     * @param {Object} config Configuración del menú
     */
    function initMobileMenu(menuElement, burgerButton, config) {
        if (!menuElement || !burgerButton) return;

        // Remover listener anterior
        if (burgerButton._clickHandler) {
            burgerButton.removeEventListener('click', burgerButton._clickHandler);
        }

        burgerButton._clickHandler = function() {
            var headerParent = burgerButton.closest('[gloryHeader], .siteMenuW');
            if (headerParent) {
                headerParent.classList.toggle('open');
                document.body.classList.toggle('menu-open');
            }
        };

        burgerButton.addEventListener('click', burgerButton._clickHandler);
    }

    // Exportar renderer
    Gbn.ui.renderers.menu = {
        getStyles: getStyles,
        handleUpdate: handleUpdate,
        renderManualMenu: renderManualMenu,
        initMobileMenu: initMobileMenu
    };

})(typeof window !== 'undefined' ? window : this);
