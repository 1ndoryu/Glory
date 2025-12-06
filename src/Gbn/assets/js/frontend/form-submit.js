;(function (global) {
    'use strict';

    /**
     * GBN FORM SUBMIT FRONTEND
     * 
     * Maneja el envío AJAX de formularios GBN en el frontend:
     * - Intercepta el submit de formularios con gloryForm
     * - Valida cliente-side antes de enviar
     * - Muestra estados de carga y mensajes de respuesta
     * - Soporta honeypot anti-spam
     * 
     * Este archivo se carga para todos los usuarios (no solo editores).
     * 
     * @module GbnFormSubmit
     * @since Fase 14.5
     */

    var GbnForm = global.GbnForm = global.GbnForm || {};

    /**
     * Configuración por defecto del módulo.
     */
    var defaults = {
        formSelector: '[gloryForm][data-ajax-submit="true"], [gloryform][data-ajax-submit="true"]',
        submitButtonSelector: '[glorySubmit], [glorysubmit], button[type="submit"], input[type="submit"]',
        loadingClass: 'gbn-form-loading',
        successClass: 'gbn-form-success',
        errorClass: 'gbn-form-error',
        messageClass: 'gbn-form-message',
        honeypotName: 'gbn_website'
    };

    /**
     * Estado para evitar envíos múltiples.
     */
    var formStates = new Map();

    /**
     * Inicializa todos los formularios GBN en la página.
     */
    function init() {
        var forms = document.querySelectorAll(defaults.formSelector);

        forms.forEach(function (form) {
            initializeForm(form);
        });

        // También inicializar formularios que se agreguen dinámicamente
        observeNewForms();
    }

    /**
     * Inicializa un formulario individual.
     * 
     * @param {HTMLFormElement} form Elemento del formulario
     */
    function initializeForm(form) {
        // Evitar doble inicialización
        if (form.dataset.gbnFormInit === 'true') {
            return;
        }
        form.dataset.gbnFormInit = 'true';

        // Inyectar campo honeypot si no existe
        injectHoneypot(form);

        // Vincular evento submit
        form.addEventListener('submit', function (event) {
            handleSubmit(event, form);
        });
    }

    /**
     * Inyecta el campo honeypot anti-spam en el formulario.
     * 
     * @param {HTMLFormElement} form Elemento del formulario
     */
    function injectHoneypot(form) {
        // Verificar si el honeypot está deshabilitado
        if (form.dataset.honeypot === 'false') {
            return;
        }

        // Verificar si ya existe
        if (form.querySelector('[name="' + defaults.honeypotName + '"]')) {
            return;
        }

        // Crear campo honeypot oculto
        var honeypot = document.createElement('div');
        honeypot.setAttribute('aria-hidden', 'true');
        honeypot.style.cssText = 'position: absolute; left: -9999px; top: -9999px; opacity: 0; pointer-events: none;';
        honeypot.innerHTML = '<input type="text" name="' + defaults.honeypotName + '" value="" tabindex="-1" autocomplete="off" />';

        form.appendChild(honeypot);
    }

    /**
     * Maneja el evento submit del formulario.
     * 
     * @param {Event} event Evento submit
     * @param {HTMLFormElement} form Elemento del formulario
     */
    function handleSubmit(event, form) {
        event.preventDefault();

        // Evitar envío múltiple
        var formId = form.getAttribute('data-form-id') || 'form-' + Date.now();
        if (formStates.get(formId) === 'submitting') {
            return;
        }

        // Validar formulario (HTML5 + custom)
        if (!validateForm(form)) {
            return;
        }

        // Iniciar envío
        submitForm(form, formId);
    }

    /**
     * Valida el formulario antes de enviar.
     * 
     * @param {HTMLFormElement} form Elemento del formulario
     * @returns {boolean} True si es válido
     */
    function validateForm(form) {
        // Usar validación nativa HTML5
        if (!form.checkValidity()) {
            form.reportValidity();
            return false;
        }

        return true;
    }

    /**
     * Envía el formulario vía AJAX.
     * 
     * @param {HTMLFormElement} form Elemento del formulario
     * @param {string} formId ID del formulario
     */
    function submitForm(form, formId) {
        formStates.set(formId, 'submitting');

        // UI: Estado de carga
        setFormState(form, 'loading');

        // Obtener configuración
        var config = getFormConfig(form);

        // Construir FormData
        var formData = new FormData(form);
        formData.append('action', 'gbn_form_submit');
        formData.append('formId', formId);
        
        // Agregar configuración de mensajes
        formData.append('_successMessage', config.successMessage);
        formData.append('_errorMessage', config.errorMessage);
        formData.append('_emailSubject', config.emailSubject);

        // Agregar nonce si está disponible
        var nonce = getNonce();
        if (nonce) {
            formData.append('nonce', nonce);
        }

        // Enviar petición AJAX
        var ajaxUrl = getAjaxUrl();

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            formStates.set(formId, 'idle');

            if (data.success) {
                setFormState(form, 'success');
                showMessage(form, data.data.message || config.successMessage, 'success');

                // Resetear formulario después de éxito
                if (config.resetOnSuccess) {
                    setTimeout(function () {
                        form.reset();
                        clearMessage(form);
                        setFormState(form, 'idle');
                    }, 3000);
                }
            } else {
                setFormState(form, 'error');
                showMessage(form, data.data.message || config.errorMessage, 'error');
            }
        })
        .catch(function (error) {
            formStates.set(formId, 'idle');
            setFormState(form, 'error');
            showMessage(form, config.errorMessage, 'error');

            console.error('[GBN Form] Error:', error);
        });
    }

    /**
     * Obtiene la configuración del formulario desde data attributes.
     * 
     * @param {HTMLFormElement} form Elemento del formulario
     * @returns {Object} Configuración del formulario
     */
    function getFormConfig(form) {
        return {
            successMessage: form.dataset.successMessage || '¡Formulario enviado con éxito!',
            errorMessage: form.dataset.errorMessage || 'Hubo un error al enviar el formulario. Por favor, inténtalo de nuevo.',
            emailSubject: form.dataset.emailSubject || 'Nuevo mensaje de formulario: ' + (form.dataset.formId || 'contacto'),
            resetOnSuccess: form.dataset.resetOnSuccess !== 'false'
        };
    }

    /**
     * Establece el estado visual del formulario.
     * 
     * @param {HTMLFormElement} form Elemento del formulario
     * @param {string} state Estado: 'idle', 'loading', 'success', 'error'
     */
    function setFormState(form, state) {
        // Remover todas las clases de estado
        form.classList.remove(defaults.loadingClass, defaults.successClass, defaults.errorClass);
        
        // Obtener botón submit
        var submitBtn = form.querySelector(defaults.submitButtonSelector);

        switch (state) {
            case 'loading':
                form.classList.add(defaults.loadingClass);
                if (submitBtn) {
                    submitBtn.disabled = true;
                    // Guardar texto original y mostrar texto de carga
                    if (!submitBtn.dataset.originalText) {
                        submitBtn.dataset.originalText = submitBtn.textContent || submitBtn.value;
                    }
                    var loadingText = submitBtn.dataset.loadingText || 'Enviando...';
                    if (submitBtn.tagName === 'INPUT') {
                        submitBtn.value = loadingText;
                    } else {
                        submitBtn.textContent = loadingText;
                    }
                }
                break;

            case 'success':
                form.classList.add(defaults.successClass);
                restoreSubmitButton(submitBtn);
                break;

            case 'error':
                form.classList.add(defaults.errorClass);
                restoreSubmitButton(submitBtn);
                break;

            case 'idle':
            default:
                restoreSubmitButton(submitBtn);
                break;
        }
    }

    /**
     * Restaura el botón submit a su estado original.
     * 
     * @param {HTMLElement} submitBtn Botón submit
     */
    function restoreSubmitButton(submitBtn) {
        if (!submitBtn) return;

        submitBtn.disabled = false;
        var originalText = submitBtn.dataset.originalText;
        if (originalText) {
            if (submitBtn.tagName === 'INPUT') {
                submitBtn.value = originalText;
            } else {
                submitBtn.textContent = originalText;
            }
        }
    }

    /**
     * Muestra un mensaje de resultado en el formulario.
     * 
     * @param {HTMLFormElement} form Elemento del formulario
     * @param {string} message Mensaje a mostrar
     * @param {string} type Tipo: 'success' o 'error'
     */
    function showMessage(form, message, type) {
        // Buscar o crear contenedor de mensaje
        var messageEl = form.querySelector('.' + defaults.messageClass);
        
        if (!messageEl) {
            messageEl = document.createElement('div');
            messageEl.className = defaults.messageClass;
            // Insertar después del último campo pero antes del botón submit
            var submitBtn = form.querySelector(defaults.submitButtonSelector);
            if (submitBtn && submitBtn.parentNode) {
                submitBtn.parentNode.insertBefore(messageEl, submitBtn);
            } else {
                form.appendChild(messageEl);
            }
        }

        // Aplicar estilos según tipo
        messageEl.className = defaults.messageClass + ' gbn-form-message-' + type;
        messageEl.textContent = message;
        messageEl.style.display = 'block';

        // Aplicar estilos inline para asegurar visibilidad
        messageEl.style.padding = '12px 16px';
        messageEl.style.marginTop = '16px';
        messageEl.style.marginBottom = '16px';
        messageEl.style.borderRadius = '6px';
        messageEl.style.fontSize = '14px';
        messageEl.style.fontWeight = '500';

        if (type === 'success') {
            messageEl.style.backgroundColor = '#d4edda';
            messageEl.style.color = '#155724';
            messageEl.style.border = '1px solid #c3e6cb';
        } else {
            messageEl.style.backgroundColor = '#f8d7da';
            messageEl.style.color = '#721c24';
            messageEl.style.border = '1px solid #f5c6cb';
        }

        // Scroll al mensaje
        messageEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Limpia el mensaje del formulario.
     * 
     * @param {HTMLFormElement} form Elemento del formulario
     */
    function clearMessage(form) {
        var messageEl = form.querySelector('.' + defaults.messageClass);
        if (messageEl) {
            messageEl.style.display = 'none';
            messageEl.textContent = '';
        }
    }

    /**
     * Obtiene la URL de AJAX.
     * 
     * @returns {string} URL de admin-ajax.php
     */
    function getAjaxUrl() {
        if (global.gloryGbnCfg && global.gloryGbnCfg.ajaxUrl) {
            return global.gloryGbnCfg.ajaxUrl;
        }
        if (global.ajax_params && global.ajax_params.ajax_url) {
            return global.ajax_params.ajax_url;
        }
        return '/wp-admin/admin-ajax.php';
    }

    /**
     * Obtiene el nonce de seguridad.
     * 
     * @returns {string|null} Nonce o null si no está disponible
     */
    function getNonce() {
        if (global.gloryGbnCfg && global.gloryGbnCfg.nonce) {
            return global.gloryGbnCfg.nonce;
        }
        return null;
    }

    /**
     * Observa el DOM para inicializar formularios agregados dinámicamente.
     */
    function observeNewForms() {
        if (!global.MutationObserver) return;

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== Node.ELEMENT_NODE) return;

                    // Verificar si es un formulario GBN
                    if (node.matches && node.matches(defaults.formSelector)) {
                        initializeForm(node);
                    }

                    // Buscar formularios dentro del nodo agregado
                    if (node.querySelectorAll) {
                        node.querySelectorAll(defaults.formSelector).forEach(initializeForm);
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // =========================================================================
    // API PÚBLICA
    // =========================================================================

    GbnForm.init = init;
    GbnForm.initializeForm = initializeForm;
    GbnForm.submitForm = submitForm;
    GbnForm.validateForm = validateForm;
    GbnForm.showMessage = showMessage;
    GbnForm.clearMessage = clearMessage;
    GbnForm.setFormState = setFormState;

    // =========================================================================
    // AUTO-INICIALIZACIÓN
    // =========================================================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(typeof window !== 'undefined' ? window : this);
