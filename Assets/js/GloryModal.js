// Glory/Assets/js/GloryModal.js

class GloryModal {
    constructor() {
        this.activeModal = null;
        this.focusableElements = 'button, [href], input:not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])';
        this._bindGlobalEvents();
        // console.log('GloryModal Initialized');
    }

    _bindGlobalEvents() {
        // Listener for delegated trigger clicks (e.g., a button that opens a modal)
        document.body.addEventListener('click', event => {
            const trigger = event.target.closest('[data-glory-modal-open]');
            if (trigger) {
                event.preventDefault();
                const targetSelector = trigger.getAttribute('data-glory-modal-open');
                this.openModal(targetSelector);
            }

            // Listener for delegated close clicks (buttons inside/outside modal)
            const closeTrigger = event.target.closest('[data-glory-modal-close]');
            // Check if the click is on the overlay *itself* or a dedicated close button
            if (closeTrigger && (closeTrigger.classList.contains('glory-modal-overlay') || closeTrigger.classList.contains('glory-modal-close-button'))) {
                event.preventDefault();
                const modalToClose = closeTrigger.closest('[data-glory-modal]');
                if (modalToClose) {
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

        if (!modalElement || modalElement === this.activeModal) {
            if (!modalElement) console.error(`GloryModal: Modal not found with selector "${modalSelectorOrElement}"`);
            return;
        }

        // Close any currently active modal first
        if (this.activeModal) {
            this.closeModal(this.activeModal, false); // Close without animation maybe?
        }

        this.activeModal = modalElement;
        modalElement.style.display = 'flex'; // Or 'block', depending on your CSS
        // Force repaint/reflow before adding class for transition
        void modalElement.offsetWidth;
        modalElement.setAttribute('aria-hidden', 'false');
        modalElement.classList.add('glory-modal--is-opening'); // Optional: For CSS transitions

        // Add class to body or specific elements if needed (e.g., hide nav)
        document.body.classList.add('glory-modal-is-active');
        const navElement = document.querySelector('.nav'); // Adjust selector as needed
        if (navElement) navElement.classList.add('nav-hidden-by-modal');

        this._trapFocus(modalElement);

        // Clean up animation class after transition
        modalElement.addEventListener(
            'transitionend',
            () => {
                modalElement.classList.remove('glory-modal--is-opening');
            },
            {once: true}
        );

        // Dispatch event
        modalElement.dispatchEvent(new CustomEvent('glory.modal.opened', {bubbles: true}));
        // console.log(`GloryModal: Opened modal "${modalElement.id}"`);
    }

    closeModal(modalSelectorOrElement, animate = true) {
        const modalElement = typeof modalSelectorOrElement === 'string' ? document.querySelector(modalSelectorOrElement) : modalSelectorOrElement;

        if (!modalElement || modalElement !== this.activeModal) {
            // if (!modalElement) console.error(`GloryModal: Modal not found for closing.`); // Might be noisy
            return;
        }

        this.activeModal = null;
        modalElement.setAttribute('aria-hidden', 'true');

        // Remove body/nav classes
        document.body.classList.remove('glory-modal-is-active');
        const navElement = document.querySelector('.nav'); // Adjust selector
        if (navElement) navElement.classList.remove('nav-hidden-by-modal');

        const hideModal = () => {
            modalElement.style.display = 'none';
            modalElement.classList.remove('glory-modal--is-closing'); // Clean up class
            this.hideModalMessage(modalElement); // Hide messages on close
            // Dispatch event
            modalElement.dispatchEvent(new CustomEvent('glory.modal.closed', {bubbles: true}));
            // console.log(`GloryModal: Closed modal "${modalElement.id}"`);
        };

        if (animate) {
            modalElement.classList.add('glory-modal--is-closing'); // Add class for closing animation
            modalElement.addEventListener('transitionend', hideModal, {once: true});
            // Fallback timeout in case transitionend doesn't fire
            setTimeout(() => {
                // Check if still closing (in case interrupted)
                if (modalElement.classList.contains('glory-modal--is-closing')) {
                    hideModal();
                }
            }, 500); // Adjust timeout based on your transition duration
        } else {
            hideModal();
        }
    }

    _trapFocus(modalElement) {
        const focusableEls = modalElement.querySelectorAll(this.focusableElements);
        const firstFocusableEl = focusableEls[0];
        const lastFocusableEl = focusableEls[focusableEls.length - 1];

        // Set initial focus
        if (firstFocusableEl) {
            // Delay slightly to ensure modal is visible/rendered
            setTimeout(() => firstFocusableEl.focus(), 50);
        }

        modalElement.addEventListener('keydown', e => {
            if (e.key !== 'Tab') return;

            if (e.shiftKey) {
                /* shift + tab */
                if (document.activeElement === firstFocusableEl) {
                    lastFocusableEl.focus();
                    e.preventDefault();
                }
            } else {
                /* tab */
                if (document.activeElement === lastFocusableEl) {
                    firstFocusableEl.focus();
                    e.preventDefault();
                }
            }
        });
    }

    // --- Modal Messaging ---
    // Note: Assumes standard message structure inside the modal

    showModalMessage(modalElement, type, message = '') {
        if (!modalElement) return;
        const modalId = modalElement.id;
        if (!modalId) return; // Requires modal to have an ID

        // Only handle 'failure' type for now, as success usually closes modal
        if (type !== 'failure') return;

        const failureSelector = `#${modalId}-failure`; // Assumes ID convention
        const failureDiv = modalElement.querySelector(failureSelector);

        if (failureDiv) {
            const messageTextDiv = failureDiv.querySelector('div'); // Assumes inner div for text
            if (messageTextDiv && message) {
                messageTextDiv.textContent = message;
                failureDiv.classList.remove('u-hidden'); // Assumes 'u-hidden' class
                failureDiv.setAttribute('aria-hidden', 'false');
            } else if (!message) {
                // Hide if message is empty
                this.hideModalMessage(modalElement);
            }
        } else if (message) {
            console.warn(`GloryModal: Failure message container ("${failureSelector}") not found in modal "${modalId}".`);
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
    }

    // --- Static instance or getter for global access ---
    static getInstance() {
        if (!GloryModal.instance) {
            GloryModal.instance = new GloryModal();
        }
        return GloryModal.instance;
    }
}


window.GloryModalService = GloryModal.getInstance();
