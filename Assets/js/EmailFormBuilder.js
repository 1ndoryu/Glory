// App/Glory/js/GloryEmailSignup.js

/**
 * Glory Email Signup Handler
 *
 * Handles AJAX submission for email signup forms, registers the user,
 * and opens a modal to collect additional details (first/last name).
 */
class GloryEmailSignup {
    constructor() {
        this.signupForms = document.querySelectorAll('[data-glory-signup-form]');
        this.modals = document.querySelectorAll('[data-glory-modal]');
        this.activeModal = null; // Track the currently open modal
        this.ajaxUrl = window.gloryGlobalData?.ajax_url || '/wp-admin/admin-ajax.php';

        this._bindEvents();
    }

    _bindEvents() {
        this.signupForms.forEach(formWrapper => {
            const form = formWrapper.querySelector('form');
            if (form) {
                form.addEventListener('submit', (e) => this._handleEmailSubmit(e, formWrapper));
            }
        });

        this.modals.forEach(modal => {
            const form = modal.querySelector('[data-glory-user-details-form]');
            const closeButtons = modal.querySelectorAll('[data-glory-modal-close]');

            if (form) {
                form.addEventListener('submit', (e) => this._handleUserDetailsSubmit(e, modal));
            }

            closeButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                     e.preventDefault();
                     this._closeModal(modal);
                });
            });

             // Optional: Close modal on Escape key press
             document.addEventListener('keydown', (e) => {
                 if (e.key === 'Escape' && this.activeModal === modal && modal.getAttribute('aria-hidden') === 'false') {
                     this._closeModal(modal);
                 }
             });
        });


    }

    _showLoading(formElement, show = true) {
        const submitButton = formElement.querySelector('input[type="submit"]');
        if (!submitButton) return;

        if (show) {
            submitButton.dataset.originalValue = submitButton.value;
            submitButton.value = submitButton.dataset.wait || 'Processing...';
            submitButton.disabled = true;
        } else {
            submitButton.value = submitButton.dataset.originalValue || 'Submit';
            submitButton.disabled = false;
        }
    }

    _showMessage(formWrapper, type, message = '') {
        const formId = formWrapper.querySelector('form')?.id || '';
        if (!formId) return;

        const successSelector = `#${formId}-success`;
        const failureSelector = `#${formId}-failure`;

        const successDiv = formWrapper.querySelector(successSelector) || document.getElementById(`${formId}-success`);
        const failureDiv = formWrapper.querySelector(failureSelector) || document.getElementById(`${formId}-failure`);
        const messageContainer = (type === 'success' ? successDiv : failureDiv);
        const otherContainer = (type === 'success' ? failureDiv : successDiv);

        if (otherContainer) {
            otherContainer.classList.add('u-hidden'); // Assumes u-hidden hides the element
             otherContainer.setAttribute('aria-hidden', 'true');
        }

        if (messageContainer) {
             const messageTextDiv = messageContainer.querySelector('div'); // Get the inner div
             if (messageTextDiv && message) {
                 messageTextDiv.textContent = message; // Update text content
             }
            messageContainer.classList.remove('u-hidden');
            messageContainer.setAttribute('aria-hidden', 'false');
        } else {
            console.warn(`Message container (${type}) not found for form ${formId}`);
        }
    }

     _showModalMessage(modalElement, type, message = '') {
         const modalId = modalElement.id;
         if (!modalId) return;

         // Only handling failure messages inside modal for now
         if (type !== 'failure') return;

         const failureSelector = `#${modalId}-failure`;
         const failureDiv = modalElement.querySelector(failureSelector);

         if (failureDiv) {
             const messageTextDiv = failureDiv.querySelector('div');
             if (messageTextDiv && message) {
                 messageTextDiv.textContent = message;
             }
             failureDiv.classList.remove('u-hidden');
             failureDiv.setAttribute('aria-hidden', 'false');
         }
     }

      _hideModalMessage(modalElement) {
         const failureDiv = modalElement.querySelector('.glory-modal-failure');
          if (failureDiv) {
             failureDiv.classList.add('u-hidden');
             failureDiv.setAttribute('aria-hidden', 'true');
         }
     }

    async _handleEmailSubmit(event, formWrapper) {
        event.preventDefault();
        const form = event.target;
        const emailInput = form.querySelector('input[type="email"]');
        const nonceInput = form.querySelector('input[name="_ajax_nonce"]');
        const actionRegister = formWrapper.dataset.actionRegister;
        const modalTargetSelector = formWrapper.dataset.modalTarget;

        if (!emailInput || !nonceInput || !actionRegister || !modalTargetSelector) {
            console.error('Form is missing required elements or data attributes.');
            this._showMessage(formWrapper, 'failure', 'Client-side configuration error.');
            return;
        }

        const email = emailInput.value;
        const nonce = nonceInput.value;
        const modalElement = document.querySelector(modalTargetSelector);

        if (!modalElement) {
             console.error(`Target modal "${modalTargetSelector}" not found.`);
             this._showMessage(formWrapper, 'failure', 'Client-side setup error (modal missing).');
             return;
        }

        this._showLoading(form, true);
        // Immediately hide previous messages
        this._showMessage(formWrapper, 'success'); // Hide success msg
        this._showMessage(formWrapper, 'failure'); // Hide failure msg


        const formData = new FormData();
        formData.append('action', actionRegister);
        formData.append('email', email);
        formData.append('_ajax_nonce', nonce);

        try {
            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData,
            });

            const result = await response.json();

            if (result.success) {
                // User registered successfully
                // Optionally hide the email form's input wrapper
                 const inputWrapper = formWrapper.querySelector('[data-glory-input-wrapper]');
                 if(inputWrapper) inputWrapper.style.display = 'none';

                // Update the target modal's hidden user_id input
                const userIdInput = modalElement.querySelector('[data-glory-user-id-input]');
                if (userIdInput && result.data?.userId) {
                    userIdInput.value = result.data.userId;
                     // Now open the modal
                     this._openModal(modalElement);
                } else {
                     console.error('Could not find user ID input in modal or userId missing in response.');
                    this._showMessage(formWrapper, 'failure', 'Account created, but profile step failed. Please contact support.');
                }
                // Don't show the main form success message yet, wait for modal completion
                // this._showMessage(formWrapper, 'success', result.data?.message || 'Account created! Please complete your profile.');

            } else {
                // Registration failed
                this._showMessage(formWrapper, 'failure', result.data?.message || 'Registration failed. Please try again.');
            }

        } catch (error) {
            console.error('Email Signup AJAX Error:', error);
            this._showMessage(formWrapper, 'failure', 'A network error occurred. Please check your connection.');
        } finally {
            this._showLoading(form, false);
        }
    }

    async _handleUserDetailsSubmit(event, modalElement) {
        event.preventDefault();
        const form = event.target;
        const actionUpdate = modalElement.dataset.actionUpdate;
        const targetFormId = modalElement.dataset.targetFormId; // ID of the original email form
        const nonceAction = modalElement.dataset.nonceAction; // Get nonce action name

         // Find the original form wrapper to show final messages
        const originalFormWrapper = document.querySelector(`[data-glory-signup-form] form#${targetFormId}`)?.closest('[data-glory-signup-form]');

        if (!actionUpdate || !targetFormId || !nonceAction || !originalFormWrapper) {
            console.error('Modal form is missing required data attributes or original form wrapper not found.');
            this._showModalMessage(modalElement, 'failure', 'Client-side configuration error.');
            return;
        }

         // Find the nonce field within the *original* form wrapper
         const nonceInput = originalFormWrapper.querySelector(`input[name="_ajax_nonce"][value][data-nonce-action="${nonceAction}"]`) || originalFormWrapper.querySelector('input[name="_ajax_nonce"]');

         if (!nonceInput) {
             console.error(`Nonce field for action "${nonceAction}" not found in original form wrapper.`);
             this._showModalMessage(modalElement, 'failure', 'Security token missing.');
             return;
         }

        this._showLoading(form, true);
        this._hideModalMessage(modalElement); // Hide previous modal errors

        const formData = new FormData(form); // Gets first_name, last_name, user_id
        formData.append('action', actionUpdate);
        formData.append('_ajax_nonce', nonceInput.value); // Use the nonce from the original form

        try {
             const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData,
            });

            const result = await response.json();

            if (result.success) {
                 // Details updated successfully
                 this._closeModal(modalElement);
                 // Show success message in the *original* form's success div
                 this._showMessage(originalFormWrapper, 'success', result.data?.message || 'Profile updated successfully!');

            } else {
                 // Update failed
                this._showModalMessage(modalElement, 'failure', result.data?.message || 'Could not save details.');
            }

        } catch (error) {
            console.error('User Details Update AJAX Error:', error);
            this._showModalMessage(modalElement, 'failure', 'A network error occurred.');
        } finally {
             this._showLoading(form, false);
        }
    }

    _openModal(modalElement) {
        if (!modalElement) return;
        // Trap focus - basic implementation
        // TODO: Implement more robust focus trapping
        modalElement.style.display = 'block'; // Or 'flex', depending on your CSS
        modalElement.setAttribute('aria-hidden', 'false');
        this.activeModal = modalElement;

        // Focus on the first focusable element inside the modal
        const focusableElements = modalElement.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusableElements.length > 0) {
             focusableElements[0].focus();
        }
    }

    _closeModal(modalElement) {
        if (!modalElement) return;
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
         this._hideModalMessage(modalElement); // Clear any modal errors on close
         if (this.activeModal === modalElement) {
             this.activeModal = null;
         }

          // Optional: Return focus to the element that opened the modal if possible
          // This requires more tracking, skipping for simplicity now.
    }
}

// Initialize the handler once the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new GloryEmailSignup();
});