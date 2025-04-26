<?php
# App/Glory/Components/FormModalBuilder.php
namespace Glory\Components;

/**
 * Generates Modal HTML containing a configurable form.
 */
class FormModalBuilder
{
    private $config = [
        // Modal Settings
        'modal_id'           => 'glory-form-modal',
        'modal_class'        => 'glory-modal',
        // ... (otras configuraciones de modal) ...

        // Form Settings
        'form_aria_label'    => 'Modal Form',
        'form_data_attr'     => 'glory-modal-form', // JS hook for the form inside the modal
        'fields'             => [],
        'hidden_fields'      => [],
        'submit_value'       => 'Submit',
        'submit_class'       => 'form-submit w-button',
        'submit_data_wait'   => 'Processing...',

        // AJAX & Messaging Settings
        'ajax_action'        => 'glory_modal_form_submit', // AJAX action name this form submits to
        'nonce_action'       => 'glory_modal_form_nonce',  // *** The specific nonce action string for THIS form's AJAX action ***
        'failure_message'    => 'An error occurred. Please try again.',
        'target_form_id'     => null,
    ];

    public function __construct(array $userConfig = [])
    {
        // ... (resto del constructor sin cambios) ...
        if (isset($userConfig['fields']) && is_array($userConfig['fields'])) {
            $this->config['fields'] = array_merge($this->config['fields'], $userConfig['fields']);
            unset($userConfig['fields']);
        }
        if (isset($userConfig['hidden_fields']) && is_array($userConfig['hidden_fields'])) {
            $this->config['hidden_fields'] = array_merge($this->config['hidden_fields'], $userConfig['hidden_fields']);
            unset($userConfig['hidden_fields']);
        }
        $this->config = array_merge($this->config, $userConfig);

        foreach ($this->config['fields'] as $key => &$field) {
            if (!isset($field['id'])) {
                $field['id'] = $this->config['modal_id'] . '-' . ($field['name'] ?? 'field-' . $key);
            }
        }
        unset($field);
    }

    public function render(): string
    {
        // ... (variables de configuración como antes) ...
        $modalId = htmlspecialchars($this->config['modal_id'], ENT_QUOTES, 'UTF-8');
        $modalClass = htmlspecialchars($this->config['modal_class'], ENT_QUOTES, 'UTF-8');
        $modalTitle = htmlspecialchars($this->config['modal_title'], ENT_QUOTES, 'UTF-8');
        $closeButtonText = $this->config['close_button_text'];
        $closeButtonLabel = htmlspecialchars($this->config['close_button_label'], ENT_QUOTES, 'UTF-8');

        $formAriaLabel = htmlspecialchars($this->config['form_aria_label'], ENT_QUOTES, 'UTF-8');
        $formDataAttr = htmlspecialchars($this->config['form_data_attr'], ENT_QUOTES, 'UTF-8'); // JS Hook

        $submitValue = htmlspecialchars($this->config['submit_value'], ENT_QUOTES, 'UTF-8');
        $submitClass = htmlspecialchars($this->config['submit_class'], ENT_QUOTES, 'UTF-8');
        $submitDataWait = htmlspecialchars($this->config['submit_data_wait'], ENT_QUOTES, 'UTF-8');

        $ajaxAction = htmlspecialchars($this->config['ajax_action'], ENT_QUOTES, 'UTF-8'); // For data-action-update
        $nonceAction = htmlspecialchars($this->config['nonce_action'], ENT_QUOTES, 'UTF-8'); // For wp_nonce_field()

        // *** GENERATE THE NONCE FIELD FOR THIS FORM ***
        $nonceField = wp_nonce_field($nonceAction, '_ajax_nonce', true, false);

        $failureMessage = htmlspecialchars($this->config['failure_message'], ENT_QUOTES, 'UTF-8');
        $targetFormId = $this->config['target_form_id'] ? htmlspecialchars($this->config['target_form_id'], ENT_QUOTES, 'UTF-8') : '';
        $targetFormIdAttr = $targetFormId ? "data-target-form-id=\"{$targetFormId}\"" : ''; // For linking back

        $modalFailureDivId = $modalId . '-failure';

        // ... (Generación de $fieldsHtml como antes) ...
        $fieldsHtml = '';
        foreach ($this->config['fields'] as $field) {
            $fieldId = htmlspecialchars($field['id'], ENT_QUOTES, 'UTF-8');
            $fieldName = htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8');
            $fieldLabel = htmlspecialchars($field['label'] ?? ucfirst($fieldName), ENT_QUOTES, 'UTF-8');
            $fieldType = htmlspecialchars($field['type'] ?? 'text', ENT_QUOTES, 'UTF-8');
            $fieldRequired = isset($field['required']) && $field['required'] ? 'required=""' : '';
            $fieldPlaceholder = isset($field['placeholder']) ? htmlspecialchars($field['placeholder'], ENT_QUOTES, 'UTF-8') : '';
            $fieldValue = isset($field['value']) ? htmlspecialchars($field['value'], ENT_QUOTES, 'UTF-8') : '';
            $otherAttrs = '';
            if (!empty($field['attributes']) && is_array($field['attributes'])) {
                foreach ($field['attributes'] as $attr => $val) {
                    $otherAttrs .= ' ' . htmlspecialchars($attr, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"';
                }
            }
            $fieldsHtml .= <<<FIELDHTML
             <div class="glory-modal-field">
                 <label for="{$fieldId}">{$fieldLabel}</label>
                 <input type="{$fieldType}" id="{$fieldId}" name="{$fieldName}" value="{$fieldValue}" placeholder="{$fieldPlaceholder}" {$fieldRequired}{$otherAttrs}>
             </div>
             FIELDHTML;
        }


        // ... (Generación de $hiddenFieldsHtml como antes) ...
        $hiddenFieldsHtml = '';
        foreach ($this->config['hidden_fields'] as $hField) {
            $hFieldName = htmlspecialchars($hField['name'], ENT_QUOTES, 'UTF-8');
            $hFieldValue = isset($hField['value']) ? htmlspecialchars($hField['value'], ENT_QUOTES, 'UTF-8') : '';
            $hFieldDataAttr = isset($hField['data_attr']) ? 'data-' . htmlspecialchars($hField['data_attr'], ENT_QUOTES, 'UTF-8') : '';
            $hFieldId = isset($hField['id']) ? 'id="' . htmlspecialchars($hField['id'], ENT_QUOTES, 'UTF-8') . '"' : '';
            $hiddenFieldsHtml .= <<<HIDDENFIELDHTML
             <input type="hidden" name="{$hFieldName}" value="{$hFieldValue}" {$hFieldDataAttr} {$hFieldId}>
             HIDDENFIELDHTML;
        }

        // Assemble the modal HTML
        // *** REMOVED data-nonce-action from the main div ***
        // *** ADDED $nonceField inside the <form> ***
        return <<<HTML
        <div id="{$modalId}" class="{$modalClass}" style="display: none;" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{$modalId}-title" data-glory-modal data-action-update="{$ajaxAction}" {$targetFormIdAttr}>
            <div class="glory-modal-overlay" data-glory-modal-close></div>
            <div class="glory-modal-content" role="document">
                <button class="glory-modal-close-button" aria-label="{$closeButtonLabel}" data-glory-modal-close>{$closeButtonText}</button>
                <h2 id="{$modalId}-title" class="glory-modal-title">{$modalTitle}</h2>
                <form method="post" aria-label="{$formAriaLabel}" data-{$formDataAttr}>
                    {$nonceField} 
                    {$hiddenFieldsHtml}
                    {$fieldsHtml}

                    <!-- Modal-specific failure message -->
                    <div id="{$modalFailureDivId}" class="glory-form-message glory-form-failure glory-modal-failure u-hidden" tabindex="-1" role="alert" aria-live="assertive">
                       <div>{$failureMessage}</div>
                   </div>

                    <div class="glory-modal-actions">
                        <input type="submit" data-wait="{$submitDataWait}" class="{$submitClass}" value="{$submitValue}">
                    </div>
                </form>
            </div>
        </div>
        HTML;
    }

    // ... (métodos estáticos sin cambios) ...
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
