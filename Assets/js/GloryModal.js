// Glory/Assets/js/GloryModal.js

class GloryModal {
    constructor() {
        this.activeModal = null;
        this.focusableElements = 'button, [href], input:not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])';
        this._bindGlobalEvents();
        // console.log('GloryModal Initialized');
    }

    _bindGlobalEvents() {
        // Listener for delegated trigger clicks
        document.body.addEventListener('click', event => {
            const trigger = event.target.closest('[data-glory-modal-open]');
            if (trigger) {
                event.preventDefault();
                const targetSelector = trigger.getAttribute('data-glory-modal-open');
                this.openModal(targetSelector);
                return; // Stop further processing for this click
            }

            // Listener for delegated close clicks (buttons inside/outside modal, overlay)
            const closeTrigger = event.target.closest('[data-glory-modal-close]');
            if (closeTrigger) {
                const modalToClose = closeTrigger.closest('[data-glory-modal]');
                // Only close if the trigger is the overlay itself or a dedicated button *within* the modal structure
                if (modalToClose && (closeTrigger === modalToClose || closeTrigger.classList.contains('glory-modal-close-button') || closeTrigger.classList.contains('glory-modal-overlay'))) {
                    event.preventDefault();
                    this.closeModal(modalToClose);
                }
            }
        });

        // Listener for Escape key
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && this.activeModal) {
                this.closeModal(this.activeModal);
            }
        });
    }

    openModal(modalSelectorOrElement) {
        const modalElement = typeof modalSelectorOrElement === 'string' ? document.querySelector(modalSelectorOrElement) : modalSelectorOrElement;

        if (!modalElement) {
            console.error(`GloryModal: Modal not found with selector "${modalSelectorOrElement}"`);
            return;
        }
        if (modalElement === this.activeModal) {
            // console.log(`GloryModal: Modal "${modalElement.id}" is already active.`);
            return; // Already open
        }

        // Close any currently active modal first
        if (this.activeModal) {
            this.closeModal(this.activeModal, false); // Close without animation if opening another
        }

        this.activeModal = modalElement;

        // Dispatch before display/focus (might be useful)
        // modalElement.dispatchEvent(new CustomEvent('glory.modal.beforeOpen', { bubbles: true, detail: { modal: modalElement } }));

        modalElement.style.display = 'flex'; // Or 'block', depending on your CSS
        // Force repaint/reflow before adding class for transition
        void modalElement.offsetWidth;
        modalElement.setAttribute('aria-hidden', 'false');
        modalElement.classList.add('glory-modal--is-opening'); // Optional: For CSS transitions

        document.body.classList.add('glory-modal-is-active');
        // Example: Hiding nav (adjust selector)
        const navElement = document.querySelector('.nav');
        if (navElement) navElement.classList.add('nav-hidden-by-modal');

        this._trapFocus(modalElement);

        // Event listener for transition end (if using CSS transitions)
        const handleOpenTransitionEnd = () => {
            modalElement.classList.remove('glory-modal--is-opening');
            // Dispatch afterOpen *after* transition completes for visual readiness
            modalElement.dispatchEvent(new CustomEvent('glory.modal.afterOpen', {bubbles: true, detail: {modal: modalElement}}));
            // console.log(`GloryModal: Dispatched glory.modal.afterOpen for "${modalElement.id}"`);
        };

        modalElement.addEventListener('transitionend', handleOpenTransitionEnd, {once: true});

        // Fallback or if no transitions: Dispatch immediately after focus setup
        // Adjust timing if transitions are long or focus needs delay
        setTimeout(() => {
            // Check if transition already fired (removeEventListener works even if not added)
            modalElement.removeEventListener('transitionend', handleOpenTransitionEnd);
            // If not already open (e.g., quickly closed again) and event not fired
            if (this.activeModal === modalElement && !modalElement.classList.contains('glory-modal--is-opening')) {
                // Check a flag or re-evaluate if needed, this dispatch might be redundant or fire too early without transitions
                // For simplicity without a flag: Assuming if no transition, it's effectively 'open' now
                // Or dispatch slightly delayed after setting display flex
            }
        }, 50); // Small delay should be okay, might need adjustment based on CSS

        // If no transitions, dispatch sooner:
        // modalElement.dispatchEvent(new CustomEvent('glory.modal.afterOpen', { bubbles: true, detail: { modal: modalElement } }));
        // console.log(`GloryModal: Dispatched glory.modal.afterOpen for "${modalElement.id}"`);
    }

    closeModal(modalSelectorOrElement, animate = true) {
        const modalElement = typeof modalSelectorOrElement === 'string' ? document.querySelector(modalSelectorOrElement) : modalSelectorOrElement;

        // Only proceed if this is the currently active modal
        if (!modalElement || modalElement !== this.activeModal) {
            return;
        }

        // Dispatch before starting the close process
        modalElement.dispatchEvent(new CustomEvent('glory.modal.beforeClose', {bubbles: true, detail: {modal: modalElement}}));
        // console.log(`GloryModal: Dispatched glory.modal.beforeClose for "${modalElement.id}"`);

        // Set activeModal to null *immediately* to prevent race conditions
        this.activeModal = null;
        modalElement.setAttribute('aria-hidden', 'true');

        document.body.classList.remove('glory-modal-is-active');
        // Example: Showing nav again (adjust selector)
        const navElement = document.querySelector('.nav');
        if (navElement) navElement.classList.remove('nav-hidden-by-modal');

        // Internal function to handle the actual hiding and final event
        const hideModalComplete = () => {
            // Ensure it wasn't reopened in the meantime
            if (modalElement.style.display !== 'none') {
                modalElement.style.display = 'none';
                modalElement.classList.remove('glory-modal--is-closing'); // Clean up animation class
                this.hideModalMessage(modalElement); // Hide internal messages on close
                // Dispatch final event
                modalElement.dispatchEvent(new CustomEvent('glory.modal.afterClose', {bubbles: true, detail: {modal: modalElement}}));
                // console.log(`GloryModal: Dispatched glory.modal.afterClose for "${modalElement.id}"`);
            }
        };

        if (animate && modalElement.style.display !== 'none') {
            modalElement.classList.add('glory-modal--is-closing'); // Add class for closing animation

            modalElement.addEventListener('transitionend', hideModalComplete, {once: true});

            // Fallback timeout in case transitionend doesn't fire (e.g., display:none interrupted it)
            // Match this timeout roughly to your CSS transition duration
            setTimeout(() => {
                modalElement.removeEventListener('transitionend', hideModalComplete); // Clean up listener
                hideModalComplete(); // Ensure it hides even if event didn't fire
            }, 500); // Adjust timeout (e.g., 500ms)
        } else {
            // If no animation or already hidden, hide immediately
            hideModalComplete();
        }
    }

    _trapFocus(modalElement) {
        const focusableEls = Array.from(modalElement.querySelectorAll(this.focusableElements)).filter(el => el.offsetParent !== null); // Only visible elements
        if (focusableEls.length === 0) return; // No focusable elements

        const firstFocusableEl = focusableEls[0];
        const lastFocusableEl = focusableEls[focusableEls.length - 1];

        // Set initial focus (delay slightly to ensure modal is visually ready and elements are focusable)
        setTimeout(() => {
            const elementToFocus = modalElement.querySelector('[autofocus]') || firstFocusableEl;
            elementToFocus?.focus();
        }, 100); // Increased delay slightly

        const keydownHandler = e => {
            // If the modal is no longer active, remove the listener
            if (!this.activeModal || this.activeModal !== modalElement) {
                modalElement.removeEventListener('keydown', keydownHandler);
                return;
            }

            if (e.key !== 'Tab') return;

            // Re-query focusable elements in case content changed dynamically
            const currentFocusableEls = Array.from(modalElement.querySelectorAll(this.focusableElements)).filter(el => el.offsetParent !== null);
            if (currentFocusableEls.length === 0) return;
            const currentFirstEl = currentFocusableEls[0];
            const currentLastEl = currentFocusableEls[currentFocusableEls.length - 1];

            if (e.shiftKey) {
                /* shift + tab */
                if (document.activeElement === currentFirstEl || !modalElement.contains(document.activeElement)) {
                    currentLastEl.focus();
                    e.preventDefault();
                }
            } else {
                /* tab */
                if (document.activeElement === currentLastEl || !modalElement.contains(document.activeElement)) {
                    currentFirstEl.focus();
                    e.preventDefault();
                }
            }
        };

        // Add listener, remove on close (implicitly handled by activeModal check, but explicit removal is safer)
        modalElement.addEventListener('keydown', keydownHandler);

        // Add cleanup when modal closes
        modalElement.addEventListener(
            'glory.modal.beforeClose',
            () => {
                modalElement.removeEventListener('keydown', keydownHandler);
            },
            {once: true}
        );
    }

    // --- Modal Messaging (Internal - for errors within the modal) ---
    showModalMessage(modalElement, type, message = '') {
        if (!modalElement) return;
        const modalId = modalElement.id;
        if (!modalId) {
            console.warn('GloryModal: Cannot show message in modal without an ID.');
            return;
        }

        // Primarily for failure messages shown *inside* the modal during its operation
        const failureSelector = `#${modalId}-failure`; // Assumes standard ID convention
        const failureDiv = modalElement.querySelector(failureSelector);

        if (type === 'failure') {
            if (failureDiv) {
                const messageTextDiv = failureDiv.querySelector('div'); // Assumes inner div for text
                if (messageTextDiv) {
                    messageTextDiv.textContent = message || 'An unknown error occurred.';
                    failureDiv.classList.remove('u-hidden');
                    failureDiv.setAttribute('aria-hidden', 'false');
                }
            } else if (message) {
                console.warn(`GloryModal: Failure message container ("${failureSelector}") not found in modal "${modalId}".`);
            }
        } else {
            // Optionally handle other types like 'success' if needed internally,
            // but usually success closes the modal.
            // console.log(`GloryModal: showModalMessage called with unhandled type "${type}"`);
        }
    }

    hideModalMessage(modalElement) {
        if (!modalElement) return;
        const modalId = modalElement.id;
        if (!modalId) return;

        const failureSelector = `#${modalId}-failure`;
        const failureDiv = modalElement.querySelector(failureSelector);

        if (failureDiv) {
            const messageTextDiv = failureDiv.querySelector('div');
            if (messageTextDiv) messageTextDiv.textContent = '';
            failureDiv.classList.add('u-hidden');
            failureDiv.setAttribute('aria-hidden', 'true');
        }
        // Add similar logic for other message types (e.g., success) if they exist internally
    }

    // --- Static instance or getter for global access ---
    static getInstance() {
        if (!GloryModal.instance) {
            GloryModal.instance = new GloryModal();
        }
        return GloryModal.instance;
    }
}

// Ensure the service is available globally
window.GloryModalService = GloryModal.getInstance();
