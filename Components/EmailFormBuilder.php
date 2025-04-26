<?php
# App/Glory/Components/EmailFormBuilder.php
namespace Glory\Components;

use Glory\Class\GloryLogger;

/**
 * Generates the initial email signup form HTML.
 */
class EmailFormBuilder
{
    private $config = [
        'form_id'                 => 'signup-form',
        'form_name'               => 'wf-form-Signup-Form-Hero',
        'form_data_name'          => 'Signup Form Hero',
        'form_aria_label'         => 'Signup Form Hero',
        'email_name'              => 'email',
        'email_data_name'         => 'Email',
        'email_placeholder'       => 'Enter your e-mail',
        'email_id'                => 'email', 
        'email_maxlength'         => 256,
        'email_required'          => true,
        'submit_value'            => 'Sign up',
        'submit_data_wait'        => 'Processing...',
        'success_message'         => 'Thank you! Check your email to complete registration.', // Adjusted message slightly
        'failure_message'         => 'Oops! Something went wrong. Please try again.',
        'wrapper_class'           => 'glory-signup-wrapper form-wrap w-form',
        'input_wrap_class'        => 'input-wrap',
        'input_class'             => 'input w-input',
        'input_bg_class'          => 'input-bg u-rainbow u-blur-perf',
        'submit_class'            => 'form-submit w-button',
        'success_wrapper_class'   => 'glory-form-message glory-form-success w-form-done',
        'failure_wrapper_class'   => 'glory-form-message glory-form-failure u-hidden w-form-fail',
        'modal_target_id'         => 'user-details-modal', // ID of the modal *to open* on success
        'ajax_action_register'    => 'glory_register_email',
        'nonce_action'            => 'glory_email_signup_action', // Nonce action for *this* form
    ];

    /**
     * Constructor.
     * @param array $userConfig Overrides default settings.
     */
    public function __construct(array $userConfig = [])
    {
        GloryLogger::info("EmailFormBuilder::__construct() called", ['userConfig' => $userConfig]);
        // Ensure modal_target_id and email_id are unique based on form_id
        $baseId = $userConfig['form_id'] ?? $this->config['form_id'];
        $this->config['modal_target_id'] = $baseId . '-modal'; // Default modal ID derived from form ID
        $this->config['email_id'] = $baseId . '-email';

        $this->config = array_merge($this->config, $userConfig); // Merge user config last
    }

    /**
     * Renders the HTML for the signup form.
     * @return string The generated HTML string.
     */
    public function render(): string
    {
        GloryLogger::info("EmailFormBuilder::render() called");
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

        $wrapperClass = htmlspecialchars($this->config['wrapper_class'], ENT_QUOTES, 'UTF-8');
        // Use the derived or user-provided modal target ID
        $modalTargetId = htmlspecialchars($this->config['modal_target_id'], ENT_QUOTES, 'UTF-8');
        $ajaxActionRegister = htmlspecialchars($this->config['ajax_action_register'], ENT_QUOTES, 'UTF-8');
        $nonceAction = htmlspecialchars($this->config['nonce_action'], ENT_QUOTES, 'UTF-8');
        $nonceField = wp_nonce_field($nonceAction, '_ajax_nonce', true, false); // Generate nonce field specific to this action

        $successDivId = $formId . '-success';
        $failureDivId = $formId . '-failure';

        // NOTE: data-modal-target now includes the '#' for direct use as a selector
        return <<<HTML
        <div class="{$wrapperClass}" data-glory-signup-form data-modal-target="#{$modalTargetId}" data-action-register="{$ajaxActionRegister}" data-nonce-action="{$nonceAction}">
            <form id="{$formId}" name="{$formName}" data-name="{$formDataName}" method="post" aria-label="{$formAriaLabel}">
                {$nonceField}
                <div class="{$this->config['input_wrap_class']}" data-glory-input-wrapper>
                    <input class="{$this->config['input_class']}" maxlength="{$emailMaxlength}" name="{$emailName}" data-name="{$emailDataName}" placeholder="{$emailPlaceholder}" type="email" id="{$emailId}" {$emailRequiredAttr}>
                    <div class="{$this->config['input_bg_class']}"></div>
                    <input type="submit" data-wait="{$submitDataWait}" class="{$this->config['submit_class']}" value="{$submitValue}">
                </div>
                 <div id="{$successDivId}" class="{$this->config['success_wrapper_class']} u-hidden" tabindex="-1" role="alert" aria-live="polite">
                    <div>{$successMessage}</div>
                 </div>
                 <div id="{$failureDivId}" class="{$this->config['failure_wrapper_class']} u-hidden" tabindex="-1" role="alert" aria-live="assertive">
                    <div>{$failureMessage}</div>
                </div>
            </form>
        </div>
        HTML;
    }

    // Static methods remain the same
    public static function build(array $config = []): string
    {
        try {
            $builder = new self($config);
            return $builder->render();
        } catch (\Throwable $th) {
            GloryLogger::error("EmailFormBuilder::build() - Error building form", ['error' => $th->getMessage(), 'config' => $config]);
            return '';
        }
    }

    public static function display(array $config = []): void
    {
        try {
            $builder = new self($config);
            echo $builder->render();
        } catch (\Throwable $th) {GloryLogger::error("EmailFormBuilder::display() - Error displaying form", ['error' => $th->getMessage(), 'config' => $config]);}
    }
}
