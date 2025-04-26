// Assets/js/EmailFormBuilder.js

class GloryEmailSignup {
    constructor() {
        this.signupForms = document.querySelectorAll('[data-glory-signup-form]');
        this._bindEvents();
        this._bindSuccessHandler(); // Add listener for modal success
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

    // NEW: Listen for successful submissions from modals potentially opened by these forms
    _bindSuccessHandler() {
         document.addEventListener('glory.modalForm.success', (event) => {
             const { modalId, originalTargetId, responseData } = event.detail;

             // Iterate through the signup forms managed by *this* instance
             this.signupForms.forEach(formWrapper => {
                 const form = formWrapper.querySelector('form');
                 if (!form) return;

                 const expectedModalSelector = formWrapper.dataset.modalTarget; // e.g., "#hero-signup-modal"
                 const expectedModalId = expectedModalSelector ? expectedModalSelector.substring(1) : null; // Remove '#'

                 // Check if the success event matches:
                 // 1. The modal ID matches the one this form targets.
                 // 2. The originalTargetId from the event matches this form's actual ID.
                 if (expectedModalId && modalId === expectedModalId && originalTargetId === form.id) {
                     console.log(`GloryEmailSignup (${form.id}): Received success event from modal ${modalId}`, responseData);
                     const successMessage = responseData?.message || 'Profile updated successfully!';
                     // Use this component's own message display utility
                     this._showMessage(formWrapper, 'success', successMessage);
                 }
             });
         });
    }


    async _handleEmailSubmit(event, formWrapper) {
        event.preventDefault();
        const form = event.target;
        const emailInput = form.querySelector('input[type="email"]');
        // Nonce should be specific to the *email registration* action here
        const nonceInput = form.querySelector('input[name="_ajax_nonce"]');
        const actionRegister = formWrapper.dataset.actionRegister; // Action for *this* email form
        const modalTargetSelector = formWrapper.dataset.modalTarget; // Selector for the *profile* modal

        if (!emailInput || !nonceInput || !nonceInput.value || !actionRegister || !modalTargetSelector) {
            console.error('GloryEmailSignup: Form lacks required elements/attributes (email, nonce, actionRegister, modalTarget).', formWrapper);
            this._showMessage(formWrapper, 'failure', 'Client-side configuration error.');
            return;
        }

        const email = emailInput.value;
        const nonce = nonceInput.value;

        // Find the target modal *element*
        const modalElement = document.querySelector(modalTargetSelector);
        if (!modalElement) {
             console.error(`GloryEmailSignup: Target modal "${modalTargetSelector}" not found.`);
             this._showMessage(formWrapper, 'failure', 'Client-side setup error (modal missing).');
             return;
        }

        this._showLoading(form, true);
        this._showMessage(formWrapper, 'success', ''); // Clear previous messages
        this._showMessage(formWrapper, 'failure', '');

        const requestData = {
            email: email,
            _ajax_nonce: nonce // Use the nonce from *this* form for the registration action
        };

        try {
            if (typeof GloryAjax !== 'function') throw new Error("GloryAjax function not found.");

            const result = await GloryAjax(actionRegister, requestData);

            if (result.success) {
                form.reset(); // Reset the email form

                // Populate the user ID in the modal *before* opening it
                const userIdInput = modalElement.querySelector('[data-glory-user-id-input]'); // Find input by data attribute
                if (userIdInput && result.data?.userId) {
                    userIdInput.value = result.data.userId;

                    // Open the modal using the service
                    if (window.GloryModalService) {
                        window.GloryModalService.openModal(modalElement); // Pass element directly
                    } else {
                         console.error("GloryEmailSignup: GloryModalService not found to open modal.");
                         this._showMessage(formWrapper, 'failure', 'Account created, but profile step failed (modal service).');
                    }
                } else {
                     console.error('GloryEmailSignup: Could not find user ID input [data-glory-user-id-input] in modal or userId missing in AJAX response.', { modal: modalElement.id, response: result.data });
                     this._showMessage(formWrapper, 'success', 'Account created! Profile update step encountered an issue.');
                     // Decide if you still want to open the modal or show an error/alternative flow
                }

            } else {
                const errorMessage = result.data?.message || result.message || 'Registration failed. Please try again.';
                this._showMessage(formWrapper, 'failure', errorMessage);
            }
        } catch (error) {
            console.error('GloryEmailSignup: Unexpected error during email submission:', error);
            this._showMessage(formWrapper, 'failure', 'A client-side error occurred during registration.');
        } finally {
            this._showLoading(form, false);
        }
    }

    // --- Utility functions specific to the Email Signup form ---

    _showLoading(formElement, show = true) {
        const submitButton = formElement.querySelector('button[type="submit"], input[type="submit"]');
        if (!submitButton) return;
        if (show) {
             submitButton.disabled = true;
             if (!submitButton.dataset.originalValue) {
                submitButton.dataset.originalValue = submitButton.type === 'submit' ? submitButton.value : submitButton.textContent;
             }
             const waitText = submitButton.dataset.wait || 'Processing...';
             if (submitButton.type === 'submit') submitButton.value = waitText;
             else submitButton.textContent = waitText;
        } else {
             if (submitButton.dataset.originalValue) {
                if (submitButton.type === 'submit') submitButton.value = submitButton.dataset.originalValue;
                else submitButton.textContent = submitButton.dataset.originalValue;
                delete submitButton.dataset.originalValue;
             }
              submitButton.disabled = false;
        }
    }

     _showMessage(formWrapper, type, message = '') {
         const form = formWrapper.querySelector('form');
         if (!form || !form.id) {
            console.warn('GloryEmailSignup: Cannot show message, form or form ID missing.', formWrapper);
            return;
         };
         const successSelector = `#${form.id}-success`;
         const failureSelector = `#${form.id}-failure`;

         // Preferentially find messages *within* the specific formWrapper
         let successDiv = formWrapper.querySelector(successSelector);
         let failureDiv = formWrapper.querySelector(failureSelector);

         // Fallback to document search if needed (less robust if IDs aren't unique)
         // if (!successDiv) successDiv = document.querySelector(successSelector);
         // if (!failureDiv) failureDiv = document.querySelector(failureSelector);

         const messageContainer = type === 'success' ? successDiv : failureDiv;
         const otherContainer = type === 'success' ? failureDiv : successDiv;

         // Hide the other message type
         if (otherContainer) {
             otherContainer.classList.add('u-hidden');
             otherContainer.setAttribute('aria-hidden', 'true');
             const otherMessageTextDiv = otherContainer.querySelector('div'); // Assume inner div for text
             if (otherMessageTextDiv) otherMessageTextDiv.textContent = '';
         }

         // Show the target message type
         if (messageContainer) {
             if (message && message.length > 0) {
                 const messageTextDiv = messageContainer.querySelector('div'); // Assume inner div for text
                 if (messageTextDiv) {
                     messageTextDiv.textContent = message;
                     messageContainer.classList.remove('u-hidden');
                     messageContainer.setAttribute('aria-hidden', 'false');
                 } else {
                     // If no inner div, maybe set textContent of the container itself? Adapt as needed.
                     messageContainer.textContent = message;
                     messageContainer.classList.remove('u-hidden');
                     messageContainer.setAttribute('aria-hidden', 'false');
                      console.warn(`GloryEmailSignup: Message container (${type}) for form "${form.id}" lacks inner div for text.`);
                 }
             } else {
                 // Hide if message is empty
                 messageContainer.classList.add('u-hidden');
                 messageContainer.setAttribute('aria-hidden', 'true');
                 const messageTextDiv = messageContainer.querySelector('div');
                 if (messageTextDiv) messageTextDiv.textContent = '';
                 // else messageContainer.textContent = ''; // Clear container text if no inner div
             }
         } else if (message && message.length > 0) {
             // Only warn if trying to show a non-empty message but container is missing
             console.warn(`GloryEmailSignup: Message container (${type}) "${type === 'success' ? successSelector : failureSelector}" not found for form "${form.id}".`);
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