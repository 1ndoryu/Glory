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
     */
    function getStyles(config, block) {
        var styles = traits.getCommonStyles(config);

        // Propiedades específicas del form
        if (config.display) {
            styles['display'] = config.display;
        }
        if (config.gap) {
            styles['gap'] = config.gap;
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

        // Delegar a traits comunes
        return traits.handleCommonUpdate(el, path, value);
    }

    // Exportar el renderer
    Gbn.ui.renderers.form = {
        getStyles: getStyles,
        handleUpdate: handleUpdate
    };

})(typeof window !== 'undefined' ? window : this);
