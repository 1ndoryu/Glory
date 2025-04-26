// Assets/js/EmailFormBuilder.js (Refactored)

class GloryEmailSignup {
    constructor() {
        this.signupForms = document.querySelectorAll('[data-glory-signup-form]');
        this._bindEvents();
        // console.log('GloryEmailSignup Initialized');
    }

    _bindEvents() {
        this.signupForms.forEach(formWrapper => {
            const form = formWrapper.querySelector('form');
            if (form) {
                form.addEventListener('submit', e => this._handleEmailSubmit(e, formWrapper));
            }
        });
    }

    async _handleEmailSubmit(event, formWrapper) {
        event.preventDefault();
        const form = event.target;
        const emailInput = form.querySelector('input[type="email"]');
        const nonceInput = form.querySelector('input[name="_ajax_nonce"]');
        const actionRegister = formWrapper.dataset.actionRegister;
        const modalTargetSelector = formWrapper.dataset.modalTarget; // e.g., "#hero-signup-modal"

        // Basic validation
        if (!emailInput || !nonceInput || !nonceInput.value || !actionRegister || !modalTargetSelector) {
            console.error('GloryEmailSignup: Form lacks required elements/attributes (email, nonce, actionRegister, modalTarget).', formWrapper);
            this._showMessage(formWrapper, 'failure', 'Client-side configuration error.');
            return;
        }

        const email = emailInput.value;
        const nonce = nonceInput.value;

        // Check if modal target exists *before* submitting AJAX
        const modalElement = document.querySelector(modalTargetSelector);
        if (!modalElement) {
             console.error(`GloryEmailSignup: Target modal "${modalTargetSelector}" not found.`);
             this._showMessage(formWrapper, 'failure', 'Client-side setup error (modal missing).');
             return;
        }

        this._showLoading(form, true);
        this._showMessage(formWrapper, 'success', ''); // Clear messages
        this._showMessage(formWrapper, 'failure', '');

        const requestData = {
            email: email,
            _ajax_nonce: nonce
        };

        try {
            // Assume GloryAjax function exists globally
            if (typeof GloryAjax !== 'function') throw new Error("GloryAjax function not found.");

            const result = await GloryAjax(actionRegister, requestData);

            if (result.success) {
                form.reset();

                // --- Trigger Modal Opening ---
                // Find the hidden user ID input *inside the target modal*
                const userIdInput = modalElement.querySelector('[data-glory-user-id-input]'); // Use data attribute

                if (userIdInput && result.data?.userId) {
                    userIdInput.value = result.data.userId; // Set the user ID value

                    // Use the GloryModal service to open the modal
                    if (window.GloryModalService) {
                        window.GloryModalService.openModal(modalElement); // Pass the element directly
                    } else {
                         console.error("GloryEmailSignup: GloryModalService not found to open modal.");
                         this._showMessage(formWrapper, 'failure', 'Account created, but profile step failed (modal service).');
                    }
                } else {
                     console.error('GloryEmailSignup: Could not find user ID input in modal or userId missing in response.', { modal: modalElement.id, response: result });
                     // Show success message for email registration, but indicate profile step issue
                     this._showMessage(formWrapper, 'success', 'Account created! Please complete your profile later.');
                     // Consider if you still want to open the modal even without the ID populated
                }

            } else {
                const errorMessage = result.data?.message || result.message || 'Registration failed. Please try again.';
                this._showMessage(formWrapper, 'failure', errorMessage);
            }
        } catch (error) {
            console.error('GloryEmailSignup: Unexpected error during email submission:', error);
            this._showMessage(formWrapper, 'failure', 'A client-side error occurred.');
        } finally {
            this._showLoading(form, false);
        }
    }

    // --- Utilities remain the same, specific to *this* form ---

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
            delete submitButton.dataset.originalValue;
        }
    }

     _showMessage(formWrapper, type, message = '') {
         const form = formWrapper.querySelector('form');
         if (!form) return;
         const successSelector = `#${form.id}-success`;
         const failureSelector = `#${form.id}-failure`;
         let successDiv = formWrapper.querySelector(successSelector);
         let failureDiv = formWrapper.querySelector(failureSelector);

         // Check document if not found within wrapper (less ideal, assumes unique IDs globally)
         if (!successDiv) successDiv = document.getElementById(`${form.id}-success`);
         if (!failureDiv) failureDiv = document.getElementById(`${form.id}-failure`);

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
             console.warn(`GloryEmailSignup: Message container (${type}) not found for form "${form.id}".`);
         }
     }

}

// Initialization
document.addEventListener('themePageReady', () => {
    // Check dependencies
    if (typeof GloryAjax === 'function' && typeof ajaxUrl !== 'undefined' && typeof window.GloryModalService !== 'undefined') {
        new GloryEmailSignup();
    } else {
         if(typeof GloryAjax !== 'function') console.error('GloryEmailSignup could not initialize: GloryAjax function is not defined.');
         if(typeof ajaxUrl === 'undefined') console.error('GloryEmailSignup could not initialize: global ajaxUrl variable is not defined.');
         if(typeof window.GloryModalService === 'undefined') console.error('GloryEmailSignup could not initialize: GloryModalService is not defined.');
    }
});