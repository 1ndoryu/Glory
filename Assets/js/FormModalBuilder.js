// Assets/js/GloryModalForm.js

class GloryModalForm {
    constructor() {
        // Use event delegation on the body for forms added dynamically
        document.body.addEventListener('submit', this._handleModalFormSubmit.bind(this));
        // console.log('GloryModalForm Initialized');
    }

    async _handleModalFormSubmit(event) {
        // Check if the submitted form is one we should handle
        const form = event.target;
        if (!form.matches('[data-glory-modal-form]')) {
            // Check for the data attribute set in FormModalBuilder
            return;
        }

        event.preventDefault();
        const modalElement = form.closest('[data-glory-modal]');
        if (!modalElement) {
            console.error('GloryModalForm: Could not find parent modal element for form.', form);
            return;
        }

        // Get AJAX details from the modal container's data attributes
        const actionUpdate = modalElement.dataset.actionUpdate;
        // const nonceAction = modalElement.dataset.nonceAction; // Nonce should be submitted with the form data
        const targetFormId = modalElement.dataset.targetFormId; // For messaging on original form

        // Find nonce *within the main page context* if needed, or assume it's inside the modal form if generated there.
        // For the user details update, we expect the nonce from the *original* signup form.
        let nonceInput;
        let nonce;
        if (targetFormId) {
            // Attempt to find nonce in the original form wrapper referenced by targetFormId
            const originalFormWrapper = document.querySelector(`#${targetFormId}`)?.closest('[data-glory-signup-form]');
            if (originalFormWrapper) {
                nonceInput = originalFormWrapper.querySelector('input[name="_ajax_nonce"]');
            }
        }

        // Fallback: Check inside the modal form itself (if the nonce was rendered there)
        if (!nonceInput) {
            nonceInput = form.querySelector('input[name="_ajax_nonce"]');
        }

        if (!actionUpdate || !nonceInput || !nonceInput.value) {
            console.error('GloryModalForm: Missing required data for modal form submission.', {
                modal: modalElement.id,
                form: form.id,
                actionUpdate: actionUpdate,
                nonceInput: nonceInput});
            GloryModalService.showModalMessage(modalElement, 'failure', 'Client configuration error (action/nonce).');
            return;
        }
        nonce = nonceInput.value;

        // Gather form data
        const formData = new FormData(form);
        const requestData = {};
        formData.forEach((value, key) => {
            requestData[key] = value;
        });

        // IMPORTANT: Add the nonce to the request data
        requestData['_ajax_nonce'] = nonce;

        this._showLoading(form, true);
        GloryModalService.hideModalMessage(modalElement); // Clear previous errors

        try {
            // Assume GloryAjax function exists globally
            if (typeof GloryAjax !== 'function') throw new Error('GloryAjax function not found.');

            const result = await GloryAjax(actionUpdate, requestData);

            if (result.success) {
                GloryModalService.closeModal(modalElement);

                // Show success message on the *original* form, if targetFormId is set
                if (targetFormId) {
                    const originalFormWrapper = document.querySelector(`#${targetFormId}`)?.closest('[data-glory-signup-form]');
                    if (originalFormWrapper) {
                        const successMessage = result.data?.message || 'Action completed successfully!';
                        // We need access to the _showMessage utility or replicate its logic
                        this._showExternalFormMessage(originalFormWrapper, 'success', successMessage);
                    } else {
                        console.warn(`GloryModalForm: Target form wrapper "#${targetFormId}" not found for success message.`);
                        // Optionally, show a generic success alert or notification
                    }
                } else {
                    // Generic success feedback if no target form
                    alert(result.data?.message || 'Success!');
                }
                // Optional: Reset the modal form if it might be reopened
                form.reset();
            } else {
                const errorMessage = result.data?.message || result.message || 'An error occurred.';
                GloryModalService.showModalMessage(modalElement, 'failure', errorMessage);
            }
        } catch (error) {
            console.error('GloryModalForm: Error during AJAX submission:', error);
            GloryModalService.showModalMessage(modalElement, 'failure', 'A client-side error occurred during submission.');
        } finally {
            this._showLoading(form, false);
        }
    }

    // Utility: Show loading state on modal submit button
    _showLoading(formElement, show = true) {
        const submitButton = formElement.querySelector('button[type="submit"], input[type="submit"]');
        if (!submitButton) return;

        if (show) {
            submitButton.dataset.originalValue = submitButton.type === 'submit' ? submitButton.value : submitButton.textContent;
            const waitText = submitButton.dataset.wait || 'Processing...';
            if (submitButton.type === 'submit') {
                submitButton.value = waitText;
            } else {
                submitButton.textContent = waitText;
            }
            submitButton.disabled = true;
        } else {
            const originalValue = submitButton.dataset.originalValue || (submitButton.type === 'submit' ? 'Submit' : 'Submit');
            if (submitButton.type === 'submit') {
                submitButton.value = originalValue;
            } else {
                submitButton.textContent = originalValue;
            }
            submitButton.disabled = false;
            delete submitButton.dataset.originalValue; // Clean up
        }
    }

    // Utility: Show message on an external form (like the original email signup)
    // This replicates the logic from the original _showMessage, adapting it
    _showExternalFormMessage(formWrapper, type, message = '') {
        const form = formWrapper.querySelector('form');
        if (!form) return;

        const successSelector = `#${form.id}-success`;
        const failureSelector = `#${form.id}-failure`;
        const successDiv = formWrapper.querySelector(successSelector);
        const failureDiv = formWrapper.querySelector(failureSelector);

        const messageContainer = type === 'success' ? successDiv : failureDiv;
        const otherContainer = type === 'success' ? failureDiv : successDiv;

        // Hide the other container
        if (otherContainer) {
            otherContainer.classList.add('u-hidden');
            otherContainer.setAttribute('aria-hidden', 'true');
            const otherMessageTextDiv = otherContainer.querySelector('div');
            if (otherMessageTextDiv) otherMessageTextDiv.textContent = '';
        }

        // Show the target container
        if (messageContainer) {
            const messageTextDiv = messageContainer.querySelector('div');
            if (messageTextDiv && message) {
                messageTextDiv.textContent = message;
                messageContainer.classList.remove('u-hidden');
                messageContainer.setAttribute('aria-hidden', 'false');
            } else if (!message) {
                // Hide if message is empty
                messageContainer.classList.add('u-hidden');
                messageContainer.setAttribute('aria-hidden', 'true');
                if (messageTextDiv) messageTextDiv.textContent = '';
            }
        } else if (message) {
            console.warn(`GloryModalForm: Message container (${type}) not found for external form "${form.id}".`);
        }
    }
}

// Initialize on theme ready
document.addEventListener('themePageReady', () => {
    // Check dependencies (GloryModalService should be initialized by its own file)
    if (typeof GloryAjax === 'function' && typeof window.GloryModalService !== 'undefined') {
        new GloryModalForm();
    } else {
        if (typeof GloryAjax !== 'function') console.error('GloryModalForm could not initialize: GloryAjax function is not defined.');
        if (typeof window.GloryModalService === 'undefined') console.error('GloryModalForm could not initialize: GloryModalService is not defined.');
    }
});
