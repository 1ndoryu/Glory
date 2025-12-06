;(function (global) {
    'use strict';

    /**
     * FORM RENDERER - Componente de Formulario
     * 
     * Maneja el contenedor <form> con configuración AJAX, honeypot, etc.
     * 
     * @module Gbn.ui.renderers.form
     */

    var Gbn = global.Gbn = global.Gbn || {};
    Gbn.ui = Gbn.ui || {};
    Gbn.ui.renderers = Gbn.ui.renderers || {};

    var traits = Gbn.ui.renderers.traits;

    /**
     * Genera los estilos inline para el formulario.
     * 
     * BUG-007 FIX: Ahora soporta layout flex y grid con columnas configurables.
     */
    function getStyles(config, block) {
        var styles = traits.getCommonStyles(config);

        // Layout mode (flex, grid, block)
        var layout = config.layout || 'flex';
        if (layout === 'flex') {
            styles['display'] = 'flex';
            
            // Flex direction
            if (config.direction) {
                styles['flex-direction'] = config.direction;
            }
            
            // Flex wrap
            if (config.wrap) {
                styles['flex-wrap'] = config.wrap;
            }
            
            // Justify content
            if (config.justifyContent) {
                styles['justify-content'] = config.justifyContent;
            }
            
            // Align items
            if (config.alignItems) {
                styles['align-items'] = config.alignItems;
            }
        } else if (layout === 'grid') {
            styles['display'] = 'grid';
            
            // Grid columns
            var columns = config.gridColumns || 2;
            styles['grid-template-columns'] = 'repeat(' + columns + ', 1fr)';
            
            // Grid gap (usa el mismo campo gap)
            if (config.gridGap) {
                styles['gap'] = config.gridGap + 'px';
            }
        } else {
            styles['display'] = 'block';
        }
        
        // Gap (para flex)
        if (config.gap && layout === 'flex') {
            styles['gap'] = config.gap + 'px';
        }

        return styles;
    }

    /**
     * Maneja actualizaciones en tiempo real.
     */
    function handleUpdate(block, path, value) {
        if (!block || !block.element) return false;
        var el = block.element;

        // Propiedades específicas del form (atributos)
        if (path === 'formId') {
            if (value) {
                el.setAttribute('data-form-id', value);
            } else {
                el.removeAttribute('data-form-id');
            }
            return true;
        }

        if (path === 'action') {
            el.setAttribute('action', value || '');
            return true;
        }

        if (path === 'method') {
            el.setAttribute('method', value || 'POST');
            return true;
        }

        if (path === 'ajaxSubmit') {
            if (value) {
                el.setAttribute('data-ajax-submit', 'true');
            } else {
                el.removeAttribute('data-ajax-submit');
            }
            return true;
        }

        if (path === 'honeypot') {
            // Activar/desactivar campo honeypot
            var honeypotField = el.querySelector('.gbn-honeypot');
            if (value && !honeypotField) {
                // Crear campo honeypot (invisible para bots, visible para usuarios)
                var hp = document.createElement('div');
                hp.className = 'gbn-honeypot';
                hp.style.cssText = 'position:absolute;left:-9999px;opacity:0;';
                hp.innerHTML = '<input type="text" name="website_hp" tabindex="-1" autocomplete="off">';
                el.insertBefore(hp, el.firstChild);
            } else if (!value && honeypotField) {
                honeypotField.remove();
            }
            return true;
        }

        if (path === 'successMessage' || path === 'errorMessage' || path === 'emailSubject') {
            // Convertir camelCase a kebab-case para el data attribute
            // successMessage -> data-success-message, emailSubject -> data-email-subject
            el.setAttribute('data-' + path.replace(/([A-Z])/g, '-$1').toLowerCase(), value);
            return true;
        }

        // === LAYOUT PROPERTIES (BUG-007 FIX) ===
        // Estas propiedades requieren re-render de estilos completo
        if (path === 'layout') {
            // Cambio de modo layout: flex, grid, block
            // Necesita re-render completo para aplicar todos los estilos relacionados
            if (value === 'flex') {
                el.style.display = 'flex';
                el.style.removeProperty('grid-template-columns');
            } else if (value === 'grid') {
                el.style.display = 'grid';
                // Default 2 columnas para formularios
                var cols = block.config && block.config.gridColumns || 2;
                el.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
            } else {
                el.style.display = 'block';
                el.style.removeProperty('grid-template-columns');
            }
            return true;
        }

        if (path === 'direction') {
            el.style.flexDirection = value;
            return true;
        }

        if (path === 'wrap') {
            el.style.flexWrap = value;
            return true;
        }

        if (path === 'justifyContent') {
            el.style.justifyContent = value;
            return true;
        }

        if (path === 'alignItems') {
            el.style.alignItems = value;
            return true;
        }

        if (path === 'gridColumns') {
            el.style.gridTemplateColumns = 'repeat(' + value + ', 1fr)';
            return true;
        }

        if (path === 'gridGap' || path === 'gap') {
            el.style.gap = value + 'px';
            return true;
        }

        // Delegar a traits comunes
        return traits.handleCommonUpdate(el, path, value);
    }

    // Exportar el renderer
    Gbn.ui.renderers.form = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
