/**
 * Glory Email Signup Handler
 *
 * Handles AJAX submission for email signup forms using GloryAjax, registers the user,
 * and opens a modal to collect additional details (first/last name).
 */

class GloryEmailSignup {
    constructor() {
        this.signupForms = document.querySelectorAll('[data-glory-signup-form]');
        this.modals = document.querySelectorAll('[data-glory-modal]');
        this.activeModal = null;

        // Verificar si la FUNCIÓN GloryAjax existe
        if (typeof GloryAjax !== 'function') {
            console.error('Error: La función GloryAjax no está definida. Asegúrate de que se cargue antes que GloryEmailSignup.js');
            // Podrías querer detener la inicialización aquí si es crítico
            // return;
        }
        // Asumiendo que ajaxUrl se define globalmente (p. ej. vía wp_localize_script)
        if (typeof ajaxUrl === 'undefined') {
            console.error('Error: La variable global ajaxUrl no está definida. Asegúrate de usar wp_localize_script.');
            // return;
        }

        this._bindEvents();
    }

    _bindEvents() {
        this.signupForms.forEach(formWrapper => {
            const form = formWrapper.querySelector('form');
            if (form) {
                form.addEventListener('submit', e => this._handleEmailSubmit(e, formWrapper));
            }
        });

        this.modals.forEach(modal => {
            const form = modal.querySelector('[data-glory-user-details-form]');
            const closeButtons = modal.querySelectorAll('[data-glory-modal-close]');

            if (form) {
                form.addEventListener('submit', e => this._handleUserDetailsSubmit(e, modal));
            }

            closeButtons.forEach(button => {
                button.addEventListener('click', e => {
                    e.preventDefault();
                    this._closeModal(modal);
                });
            });

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape' && this.activeModal === modal && modal.getAttribute('aria-hidden') === 'false') {
                    this._closeModal(modal);
                }
            });
        });
    }

    _showLoading(formElement, show = true) {
        const submitButton = formElement.querySelector('button[type="submit"], input[type="submit"]');
        if (!submitButton) return;
        if (show) {
            submitButton.dataset.originalValue = submitButton.type === 'submit' ? submitButton.value : submitButton.textContent;
            const waitText = submitButton.dataset.wait || 'Processing...';
            if (submitButton.type === 'submit') submitButton.value = waitText;
            else submitButton.textContent = waitText;
            submitButton.disabled = true;
        } else {
            const originalValue = submitButton.dataset.originalValue || (submitButton.type === 'submit' ? 'Submit' : 'Submit');
            if (submitButton.type === 'submit') submitButton.value = originalValue;
            else submitButton.textContent = originalValue;
            submitButton.disabled = false;
        }
    }

    _showMessage(formWrapper, type, message = '') {
        const form = formWrapper.querySelector('form');
        const successSelector = form ? `#${form.id}-success` : '.glory-form-success';
        const failureSelector = form ? `#${form.id}-failure` : '.glory-form-failure';
        let successDiv = formWrapper.querySelector(successSelector);
        let failureDiv = formWrapper.querySelector(failureSelector);
        if (!successDiv && form?.id) successDiv = document.getElementById(`${form.id}-success`);
        if (!failureDiv && form?.id) failureDiv = document.getElementById(`${form.id}-failure`);
        const messageContainer = type === 'success' ? successDiv : failureDiv;
        const otherContainer = type === 'success' ? failureDiv : successDiv;
        if (otherContainer) {
            otherContainer.classList.add('u-hidden');
            otherContainer.setAttribute('aria-hidden', 'true');
            const otherMessageTextDiv = otherContainer.querySelector('div');
            if (otherMessageTextDiv) otherMessageTextDiv.textContent = '';
        }
        if (messageContainer) {
            const messageTextDiv = messageContainer.querySelector('div');
            if (messageTextDiv && message) {
                messageTextDiv.textContent = message;
                messageContainer.classList.remove('u-hidden');
                messageContainer.setAttribute('aria-hidden', 'false');
            } else if (!message) {
                messageContainer.classList.add('u-hidden');
                messageContainer.setAttribute('aria-hidden', 'true');
                if (messageTextDiv) messageTextDiv.textContent = '';
            }
        } else if (message) {
            console.warn(`Message container (${type}) not found for form ${form?.id || 'wrapper'}`);
        }
    }

    _showModalMessage(modalElement, type, message = '') {
        const modalId = modalElement.id;
        if (!modalId) return;
        if (type !== 'failure') return;
        const failureSelector = `#${modalId}-failure` || '.glory-modal-failure';
        const failureDiv = modalElement.querySelector(failureSelector);
        if (failureDiv) {
            const messageTextDiv = failureDiv.querySelector('div');
            if (messageTextDiv && message) {
                messageTextDiv.textContent = message;
                failureDiv.classList.remove('u-hidden');
                failureDiv.setAttribute('aria-hidden', 'false');
            } else if (!message) {
                failureDiv.classList.add('u-hidden');
                failureDiv.setAttribute('aria-hidden', 'true');
                if (messageTextDiv) messageTextDiv.textContent = '';
            }
        } else if (message) {
            console.warn(`Failure message container not found in modal ${modalId}`);
        }
    }
    _hideModalMessage(modalElement) {
        this._showModalMessage(modalElement, 'failure', '');
    }
    _openModal(modalElement) {
        if (!modalElement) return;

        const navElement = document.querySelector('.nav'); // <<< USA TU SELECTOR CORRECTO AQUÍ
        if (navElement) {
            navElement.classList.add('nav-hidden-by-modal');
            // console.log('Nav hidden by modal'); // Opcional: para depuración
        } else {
            console.warn('Elemento .nav no encontrado para ocultar.'); // Opcional: advertencia si no se encuentra
        }
        modalElement.style.display = 'Flex';
        modalElement.setAttribute('aria-hidden', 'false');
        this.activeModal = modalElement;
        const focusableElements = modalElement.querySelectorAll('button, [href], input:not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusableElements.length > 0) {
            focusableElements[0].focus();
        }
    }
    _closeModal(modalElement) {
        if (!modalElement) return;

        const navElement = document.querySelector('.nav'); // <<< USA TU SELECTOR CORRECTO AQUÍ
        if (navElement) {
            navElement.classList.remove('nav-hidden-by-modal');
            // console.log('Nav shown after modal close'); // Opcional: para depuración
        }
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
        this._hideModalMessage(modalElement);
        if (this.activeModal === modalElement) {
            this.activeModal = null;
        }
    }
    // --- FIN DE MÉTODOS OMITIDOS ---

    async _handleEmailSubmit(event, formWrapper) {
        event.preventDefault();
        const form = event.target;
        const emailInput = form.querySelector('input[type="email"]');
        const nonceInput = form.querySelector('input[name="_ajax_nonce"]'); // Nombre estándar
        const actionRegister = formWrapper.dataset.actionRegister; // Ej: 'glory_register_email'
        const modalTargetSelector = formWrapper.dataset.modalTarget;

        // Validación (igual que antes)
        if (!emailInput || !nonceInput || !nonceInput.value || !actionRegister || !modalTargetSelector) {
            console.error('Error: Form lacks required elements (email, nonce[value], action) or data attributes (modalTarget).');
            this._showMessage(formWrapper, 'failure', 'Client-side configuration error.');
            return;
        }

        const email = emailInput.value;
        const nonce = nonceInput.value; // El valor del nonce
        const modalElement = document.querySelector(modalTargetSelector);

        if (!modalElement) {
            console.error(`Target modal "${modalTargetSelector}" not found.`);
            this._showMessage(formWrapper, 'failure', 'Client-side setup error (modal missing).');
            return;
        }

        this._showLoading(form, true);
        this._showMessage(formWrapper, 'success', '');
        this._showMessage(formWrapper, 'failure', '');

        // **** CAMBIO CLAVE ****
        // Prepara los datos INCLUYENDO el nonce
        const requestData = {
            email: email,
            _ajax_nonce: nonce // Pasar el nonce aquí con el nombre esperado por check_ajax_referer
        };

        try {
            // Llama a la función GloryAjax pasando la acción y los datos (que contienen el nonce)
            const result = await GloryAjax(actionRegister, requestData);

            // El resto del manejo de la respuesta es similar, asumiendo que PHP devuelve
            // { success: true/false, data: ... } via wp_send_json_success/error
            if (result.success) {
                const inputWrapper = formWrapper.querySelector('[data-glory-input-wrapper]');
                if (inputWrapper) {
                    // inputWrapper.style.display = 'none'; // Avoid hiding the input wrapper
                    form.reset(); // Reset the form instead
                }
                const userIdInput = modalElement.querySelector('[data-glory-user-id-input]');
                if (userIdInput && result.data?.userId) {
                    userIdInput.value = result.data.userId;
                    this._openModal(modalElement);
                } else {
                    console.error('Error: Could not find user ID input in modal or userId missing in response.', result);
                    this._showMessage(formWrapper, 'failure', 'Account created, but profile step failed. Please contact support.');
                }
            } else {
                // Mostrar mensaje de error desde la respuesta (result.message o result.data.message)
                const errorMessage = result.data?.message || result.message || 'Registration failed. Please try again.';
                this._showMessage(formWrapper, 'failure', errorMessage);
            }
        } catch (error) {
            // El catch aquí es menos probable si GloryAjax ya lo maneja, pero por si acaso
            console.error('Unexpected error during email submission:', error);
            this._showMessage(formWrapper, 'failure', 'A client-side error occurred.');
        } finally {
            this._showLoading(form, false);
        }
    }

    async _handleUserDetailsSubmit(event, modalElement) {
        event.preventDefault();
        const form = event.target; // Formulario del modal
        const actionUpdate = modalElement.dataset.actionUpdate; // Ej: 'glory_update_user_details'
        const targetFormId = modalElement.dataset.targetFormId;
        const originalFormWrapper = document.querySelector(`[data-glory-signup-form] form#${targetFormId}`)?.closest('[data-glory-signup-form]');

        // Validación (igual que antes)
        if (!actionUpdate || !targetFormId || !originalFormWrapper) {
            console.error('Error: Modal form lacks required data attributes (actionUpdate, targetFormId) or original form wrapper not found.');
            this._showModalMessage(modalElement, 'failure', 'Client-side configuration error.');
            return;
        }

        const nonceInput = originalFormWrapper.querySelector('input[name="_ajax_nonce"]'); // Obtener nonce del form original
        if (!nonceInput || !nonceInput.value) {
            console.error(`Nonce field ('_ajax_nonce') not found or empty in original form wrapper (#${targetFormId}).`);
            this._showModalMessage(modalElement, 'failure', 'Security token missing.');
            return;
        }
        const nonce = nonceInput.value; // El mismo nonce action

        // Extraer datos del formulario modal
        const userIdInput = form.querySelector('input[name="user_id"][data-glory-user-id-input]');
        const firstNameInput = form.querySelector('input[name="first_name"]');
        const lastNameInput = form.querySelector('input[name="last_name"]');

        if (!userIdInput || !userIdInput.value) {
            console.error('User ID input not found or empty in modal form.');
            this._showModalMessage(modalElement, 'failure', 'User identifier missing.');
            return;
        }

        // **** CAMBIO CLAVE ****
        const requestData = {
            user_id: userIdInput.value,
            first_name: firstNameInput ? firstNameInput.value : '',
            last_name: lastNameInput ? lastNameInput.value : '',
            _ajax_nonce: nonce // Pasar el nonce aquí
        };

        this._showLoading(form, true);
        this._hideModalMessage(modalElement);

        try {
            // Llama a la función GloryAjax pasando la acción y los datos (con nonce)
            const result = await GloryAjax(actionUpdate, requestData);

            // Manejo de respuesta similar al anterior
            if (result.success) {
                this._closeModal(modalElement);
                const successMessage = result.data?.message || 'Profile updated successfully!';
                this._showMessage(originalFormWrapper, 'success', successMessage);
            } else {
                const errorMessage = result.data?.message || result.message || 'Error saving details.';
                this._showModalMessage(modalElement, 'failure', errorMessage);
            }
        } catch (error) {
            console.error('Unexpected error during user details submission:', error);
            this._showModalMessage(modalElement, 'failure', 'A client-side error occurred.');
        } finally {
            this._showLoading(form, false);
        }
    }
}

// Inicialización (igual que antes, pero verifica la función GloryAjax)
document.addEventListener('themePageReady', () => {
    // Asegúrate que tanto la función GloryAjax como la variable ajaxUrl estén listas
    if (typeof GloryAjax === 'function' && typeof ajaxUrl !== 'undefined') {
        new GloryEmailSignup();
    } else {
        if (typeof GloryAjax !== 'function') console.error('GloryEmailSignup could not initialize: GloryAjax function is not defined.');
        if (typeof ajaxUrl === 'undefined') console.error('GloryEmailSignup could not initialize: global ajaxUrl variable is not defined (use wp_localize_script).');
    }
});
