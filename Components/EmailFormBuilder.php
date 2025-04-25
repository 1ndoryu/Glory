<?php
# App/Glory/Components/EmailFormBuilder.php
namespace App\Glory\Components;

/**
 * Generates the initial email signup form HTML.
 */
class EmailFormBuilder
{
    // Default config remains largely the same, add modal target
    private $config = [
        'form_id'                 => 'signup-form',
        'form_name'               => 'wf-form-Signup-Form-Hero',
        'form_data_name'          => 'Signup Form Hero',
        // 'form_method'             => 'get', // Method will be ignored by JS, but keep for non-JS fallback? Let's remove.
        'form_aria_label'         => 'Signup Form Hero',
        'email_name'              => 'email',
        'email_data_name'         => 'Email',
        'email_placeholder'       => 'Enter your e-mail',
        'email_id'                => 'email',
        'email_maxlength'         => 256,
        'email_required'          => true,
        'submit_value'            => 'Sign up',
        'submit_data_wait'        => 'Processing...', // Changed default wait text
        'success_message'         => 'Thank you! Your account is created. Please complete your profile.', // Updated success message
        'failure_message'         => 'Oops! Something went wrong. Please try again.', // Generic failure
        'wrapper_class'           => 'glory-signup-wrapper form-wrap w-form', // Added glory class
        'input_wrap_class'        => 'input-wrap',
        'input_class'             => 'input w-input',
        'input_bg_class'          => 'input-bg u-rainbow u-blur-perf',
        'submit_class'            => 'form-submit w-button',
        'success_wrapper_class'   => 'glory-form-message glory-form-success w-form-done', // Added glory class
        'failure_wrapper_class'   => 'glory-form-message glory-form-failure u-hidden w-form-fail', // Added glory class, hidden by default
        'modal_target_id'         => 'user-details-modal', // Default ID for the modal to target
        'ajax_action_register'    => 'glory_register_email', // AJAX action for registration
        'nonce_action'            => 'glory_email_signup_nonce', // Nonce action name
    ];

    /**
     * Constructor.
     * @param array $userConfig Overrides default settings.
     */
    public function __construct(array $userConfig = [])
    {
        // Ensure modal_target_id is unique if multiple forms are on the page
        if (isset($userConfig['form_id'])) {
            $this->config['modal_target_id'] = $userConfig['form_id'] . '-modal';
            $this->config['email_id'] = $userConfig['form_id'] . '-email'; // Ensure email ID is unique too
        } elseif (isset($this->config['form_id'])) {
            $this->config['modal_target_id'] = $this->config['form_id'] . '-modal';
            $this->config['email_id'] = $this->config['form_id'] . '-email';
        }

        $this->config = array_merge($this->config, $userConfig);
    }

    /**
     * Renders the HTML for the signup form.
     * @return string The generated HTML string.
     */
    public function render(): string
    {
        // Sanitize configuration values
        $formId = htmlspecialchars($this->config['form_id'], ENT_QUOTES, 'UTF-8');
        $formName = htmlspecialchars($this->config['form_name'], ENT_QUOTES, 'UTF-8');
        $formDataName = htmlspecialchars($this->config['form_data_name'], ENT_QUOTES, 'UTF-8');
        $formAriaLabel = htmlspecialchars($this->config['form_aria_label'], ENT_QUOTES, 'UTF-8');

        $emailName = htmlspecialchars($this->config['email_name'], ENT_QUOTES, 'UTF-8');
        $emailDataName = htmlspecialchars($this->config['email_data_name'], ENT_QUOTES, 'UTF-8');
        $emailPlaceholder = htmlspecialchars($this->config['email_placeholder'], ENT_QUOTES, 'UTF-8');
        $emailId = htmlspecialchars($this->config['email_id'], ENT_QUOTES, 'UTF-8');
        $emailMaxlength = (int)$this->config['email_maxlength'];
        $emailRequiredAttr = $this->config['email_required'] ? 'required=""' : '';

        $submitValue = htmlspecialchars($this->config['submit_value'], ENT_QUOTES, 'UTF-8');
        $submitDataWait = htmlspecialchars($this->config['submit_data_wait'], ENT_QUOTES, 'UTF-8');

        $successMessage = htmlspecialchars($this->config['success_message'], ENT_QUOTES, 'UTF-8');
        $failureMessage = htmlspecialchars($this->config['failure_message'], ENT_QUOTES, 'UTF-8');

        // --- Add data attributes for JS ---
        $wrapperClass = htmlspecialchars($this->config['wrapper_class'], ENT_QUOTES, 'UTF-8');
        $modalTargetId = htmlspecialchars($this->config['modal_target_id'], ENT_QUOTES, 'UTF-8');
        $ajaxActionRegister = htmlspecialchars($this->config['ajax_action_register'], ENT_QUOTES, 'UTF-8');
        $nonceAction = htmlspecialchars($this->config['nonce_action'], ENT_QUOTES, 'UTF-8');
        $nonceField = wp_nonce_field($nonceAction, '_ajax_nonce', true, false); // Generate nonce field

        // Generate unique IDs for message divs
        $successDivId = $formId . '-success';
        $failureDivId = $formId . '-failure';

        return <<<HTML
        <div class="{$wrapperClass}" data-glory-signup-form data-modal-target="#{$modalTargetId}" data-action-register="{$ajaxActionRegister}" data-nonce-action="{$nonceAction}">
            <form id="{$formId}" name="{$formName}" data-name="{$formDataName}" method="post" aria-label="{$formAriaLabel}">
                {$nonceField} <!-- Nonce field for security -->
                <div class="{$this->config['input_wrap_class']}" data-glory-input-wrapper>
                    <input class="{$this->config['input_class']}" maxlength="{$emailMaxlength}" name="{$emailName}" data-name="{$emailDataName}" placeholder="{$emailPlaceholder}" type="email" id="{$emailId}" {$emailRequiredAttr}>
                    <div class="{$this->config['input_bg_class']}"></div>
                    <input type="submit" data-wait="{$submitDataWait}" class="{$this->config['submit_class']}" value="{$submitValue}">
                </div>
                 <!-- Messages outside the input wrapper but inside the main form div -->
                 <div id="{$successDivId}" class="{$this->config['success_wrapper_class']}" tabindex="-1" role="alert" aria-live="polite">
                    <div>{$successMessage}</div>
                 </div>
                 <div id="{$failureDivId}" class="{$this->config['failure_wrapper_class']}" tabindex="-1" role="alert" aria-live="assertive">
                    <div>{$failureMessage}</div>
                </div>
            </form>

        </div>
        HTML;
    }

    // Static methods remain the same
    public static function build(array $config = []): string
    {
        $builder = new self($config);
        return $builder->render();
    }

    public static function display(array $config = []): void
    {
        $builder = new self($config);
        echo $builder->render();
    }

    // --- New method to get the associated modal ID ---
    public function getModalId(): string
    {
        return $this->config['modal_target_id'];
    }

    // --- New method to get the form ID ---
    public function getFormId(): string
    {
        return $this->config['form_id'];
    }

    // --- Static helper combining form and modal ---
    /**
     * Displays the Email Form and its associated (hidden) User Details Modal.
     *
     * @param array $formConfig Configuration for the email form.
     * @param array $modalConfig Configuration for the user details modal.
     * @return void Outputs directly.
     */
    public static function displayWithModal(array $formConfig = [], array $modalConfig = []): void
    {
        $emailForm = new self($formConfig);
        $modalId = $emailForm->getModalId(); // Get the ID the form expects

        // Ensure the modal builder uses the same ID
        $modalConfig['modal_id'] = $modalId;
        // Ensure the modal uses the same nonce action by default
        if (!isset($modalConfig['nonce_action'])) {
            $modalConfig['nonce_action'] = $emailForm->config['nonce_action'];
        }
        // Ensure the modal knows which form success/failure message divs to target
        $modalConfig['target_form_id'] = $emailForm->getFormId();


        $modalForm = new UserDetailsModalBuilder($modalConfig);

        // Output the email form first, then the hidden modal
        echo $emailForm->render();
        echo $modalForm->render();
    }
}

/**
 * Generates the User Details Modal HTML (initially hidden).
 */
class UserDetailsModalBuilder
{
    private $config = [
        'modal_id' => 'user-details-modal',
        'modal_class' => 'glory-modal', 
        'modal_title' => 'Complete Your Profile',
        'form_aria_label' => 'User Details Form',
        'fname_label' => 'First Name',
        'fname_name' => 'first_name',
        'fname_id' => 'modal-first-name',
        'fname_required' => true,
        'lname_label' => 'Last Name',
        'lname_name' => 'last_name',
        'lname_id' => 'modal-last-name',
        'lname_required' => false,
        'submit_value' => 'Save Profile',
        'submit_data_wait' => 'Saving...',
        'ajax_action_update' => 'glory_update_user_details', // AJAX action for updating
        'nonce_action' => 'glory_email_signup_nonce', // Default nonce action (can be overridden)
        'failure_message' => 'Could not save details. Please try again.',
        'target_form_id' => 'signup-form', // ID of the original form for messaging context
    ];

    /**
     * Constructor.
     * @param array $userConfig Overrides default settings.
     */
    public function __construct(array $userConfig = [])
    {
        // Ensure unique input IDs if modal ID changes
        if (isset($userConfig['modal_id'])) {
            $baseId = $userConfig['modal_id'];
            $this->config['fname_id'] = $baseId . '-fname';
            $this->config['lname_id'] = $baseId . '-lname';
        } elseif (isset($this->config['modal_id'])) {
            $baseId = $this->config['modal_id'];
            $this->config['fname_id'] = $baseId . '-fname';
            $this->config['lname_id'] = $baseId . '-lname';
        }
        $this->config = array_merge($this->config, $userConfig);
    }

    /**
     * Renders the HTML for the modal.
     * @return string The generated HTML string.
     */
    public function render(): string
    {
        $modalId = htmlspecialchars($this->config['modal_id'], ENT_QUOTES, 'UTF-8');
        $modalClass = htmlspecialchars($this->config['modal_class'], ENT_QUOTES, 'UTF-8');
        $modalTitle = htmlspecialchars($this->config['modal_title'], ENT_QUOTES, 'UTF-8');
        $formAriaLabel = htmlspecialchars($this->config['form_aria_label'], ENT_QUOTES, 'UTF-8');

        $fnameLabel = htmlspecialchars($this->config['fname_label'], ENT_QUOTES, 'UTF-8');
        $fnameName = htmlspecialchars($this->config['fname_name'], ENT_QUOTES, 'UTF-8');
        $fnameId = htmlspecialchars($this->config['fname_id'], ENT_QUOTES, 'UTF-8');
        $fnameRequired = $this->config['fname_required'] ? 'required=""' : '';

        $lnameLabel = htmlspecialchars($this->config['lname_label'], ENT_QUOTES, 'UTF-8');
        $lnameName = htmlspecialchars($this->config['lname_name'], ENT_QUOTES, 'UTF-8');
        $lnameId = htmlspecialchars($this->config['lname_id'], ENT_QUOTES, 'UTF-8');
        $lnameRequired = $this->config['lname_required'] ? 'required=""' : '';

        $submitValue = htmlspecialchars($this->config['submit_value'], ENT_QUOTES, 'UTF-8');
        $submitDataWait = htmlspecialchars($this->config['submit_data_wait'], ENT_QUOTES, 'UTF-8');

        $ajaxActionUpdate = htmlspecialchars($this->config['ajax_action_update'], ENT_QUOTES, 'UTF-8');
        $nonceAction = htmlspecialchars($this->config['nonce_action'], ENT_QUOTES, 'UTF-8');
        // Nonce field is added by the primary form, but we need the action name for JS
        // $nonceField = wp_nonce_field($nonceAction, '_ajax_nonce', true, false); // Nonce is already on page

        $failureMessage = htmlspecialchars($this->config['failure_message'], ENT_QUOTES, 'UTF-8');
        $targetFormId = htmlspecialchars($this->config['target_form_id'], ENT_QUOTES, 'UTF-8');

        // Unique IDs for modal messages
        $modalFailureDivId = $modalId . '-failure';

        // Add `aria-modal`, `role` and initially hidden state
        // We need a wrapper for overlay and the modal content itself
        return <<<HTML
        <div id="{$modalId}" class="{$modalClass}" style="display: none;" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{$modalId}-title" data-glory-modal data-action-update="{$ajaxActionUpdate}" data-nonce-action="{$nonceAction}" data-target-form-id="{$targetFormId}">
            <div class="glory-modal-overlay" data-glory-modal-close></div> <!-- Overlay for background click close -->
            <div class="glory-modal-content" role="document">
                <button class="glory-modal-close-button" aria-label="Close" data-glory-modal-close>×</button>
                <h2 id="{$modalId}-title" class="glory-modal-title">{$modalTitle}</h2>
                <form method="post" aria-label="{$formAriaLabel}" data-glory-user-details-form>
                    <!-- Hidden field to store user ID -->
                    <input type="hidden" name="user_id" value="" data-glory-user-id-input>

                    <div class="glory-modal-field">
                        <label for="{$fnameId}">{$fnameLabel}</label>
                        <input type="text" id="{$fnameId}" name="{$fnameName}" {$fnameRequired}>
                    </div>
                    <div class="glory-modal-field">
                        <label for="{$lnameId}">{$lnameLabel}</label>
                        <input type="text" id="{$lnameId}" name="{$lnameName}" {$lnameRequired}>
                    </div>

                    <!-- Modal-specific failure message -->
                    <div id="{$modalFailureDivId}" class="glory-form-message glory-form-failure glory-modal-failure u-hidden w-form-fail" tabindex="-1" role="alert" aria-live="assertive">
                       <div>{$failureMessage}</div>
                   </div>

                    <div class="glory-modal-actions">
                        <input type="submit" data-wait="{$submitDataWait}" class="form-submit w-button" value="{$submitValue}">
                    </div>
                </form>
            </div>
        </div>
        HTML;
    }

    // Static methods
    public static function build(array $config = []): string
    {
        $builder = new self($config);
        return $builder->render();
    }

    public static function display(array $config = []): void
    {
        $builder = new self($config);
        echo $builder->render();
    }
}
