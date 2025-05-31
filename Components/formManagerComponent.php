<?php
# /Glory/Components/formManagerComponent.php

namespace Glory\Components;

if (!defined('ABSPATH')) {
    exit;
}

class formManagerComponent
{
    private string $id;
    private string $actionUrlOriginal; // URL de redirección original (para fallback muy básico o referencia)
    private string $method;
    private array $attributes = [];
    private array $fields = [];
    private string $htmlAntesCampos = '';
    private string $htmlDespuesCampos = '';

    private static bool $adminHooksInicializados = false;

    const AJAX_PROCESS_FORM_ACTION = 'glory_process_submitted_form';
    const AJAX_NONCE_FIELD_NAME = '_glory_form_ajax_nonce';

    public function __construct(string $id, string $actionUrl, string $method = 'POST')
    {
        $this->id = $id;
        $this->actionUrlOriginal = $actionUrl;
        $this->method = strtoupper($method);
        $this->attributes['id'] = $this->id;

        self::registrarAccionesGlobales(); // Renombrado para reflejar mejor su propósito actual
        self::registrarFormularioActivo($this->id);
    }

    private static function registrarAccionesGlobales(): void
    {
        if (!self::$adminHooksInicializados) {
            // La acción 'admin_menu' para agregar la página de admin se movió a formAdminPanel
            add_action('wp_footer', [self::class, 'mostrarMensajesFeedback']);
            self::$adminHooksInicializados = true;
        }
    }

    private static function registrarFormularioActivo(string $formId): void
    {
        $formulariosActivos = get_option('glory_active_form_ids', []);
        if (!is_array($formulariosActivos)) {
            $formulariosActivos = [];
        }
        if (!in_array($formId, $formulariosActivos)) {
            $formulariosActivos[] = $formId;
            update_option('glory_active_form_ids', array_unique($formulariosActivos));
        }
    }

    // Este método ahora genera el HTML para ser usado por formAdminPanel
    public static function generarHtmlPanelAdmin(): string
    {
        $html = '<div class="wrap">';
        $html .= '<h1>' . esc_html__('Datos Recopilados por Formularios Glory', 'glory-domain') . '</h1>';

        $activeFormIds = get_option('glory_active_form_ids', []);
        if (empty($activeFormIds) || !is_array($activeFormIds)) {
            $html .= '<p>' . esc_html__('No hay formularios activos o no se han recibido datos aún.', 'glory-domain') . '</p>';
            $html .= '</div>';
            return $html;
        }

        foreach ($activeFormIds as $formId) {
            $html .= '<h2>' . esc_html__('Datos del formulario:', 'glory-domain') . ' ' . esc_html($formId) . '</h2>';
            $formDataKey = 'glory_form_data_' . $formId;
            $submissions = get_option($formDataKey, []);

            if (empty($submissions) || !is_array($submissions)) {
                $html .= '<p>' . esc_html__('No hay envíos para este formulario todavía.', 'glory-domain') . '</p>';
                continue;
            }

            $html .= '<table class="wp-list-table widefat fixed striped">';
            $html .= '<thead><tr>';

            $firstSubmissionWithData = null;
            foreach ($submissions as $sub) {
                if (isset($sub['formData']) && is_array($sub['formData']) && !empty($sub['formData'])) {
                    $firstSubmissionWithData = $sub;
                    break;
                }
            }

            $headers = [];
            if ($firstSubmissionWithData) {
                foreach (array_keys($firstSubmissionWithData['formData']) as $headerKey) {
                    $headers[] = $headerKey;
                    $html .= '<th>' . esc_html(ucwords(str_replace(['_', '-'], ' ', $headerKey))) . '</th>';
                }
            } else {
                $html .= '<th>' . esc_html__('Datos', 'glory-domain') . '</th>';
            }

            $dateColumnHeader = esc_html__('Fecha de Envío', 'glory-domain');
            $html .= '<th>' . $dateColumnHeader . '</th>';

            $html .= '</tr></thead>';
            $html .= '<tbody>';

            foreach (array_reverse($submissions) as $submissionData) {
                $html .= '<tr>';
                if (isset($submissionData['formData']) && is_array($submissionData['formData'])) {
                    if (!empty($headers)) {
                        foreach ($headers as $key) {
                            $html .= '<td>' . esc_html($submissionData['formData'][$key] ?? '') . '</td>';
                        }
                    } else {
                        $html .= '<td>' . esc_html(print_r($submissionData['formData'], true)) . '</td>';
                    }
                    if (isset($submissionData['dateTimeFormatted'])) {
                        $html .= '<td>' . esc_html($submissionData['dateTimeFormatted']) . '</td>';
                    } elseif (isset($submissionData['timestamp'])) {
                        $html .= '<td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $submissionData['timestamp'])) . '</td>';
                    } else {
                        $html .= '<td>' . esc_html__('N/A', 'glory-domain') . '</td>';
                    }
                } else {
                    $columnCount = (!empty($headers) ? count($headers) : 1) + 1;
                    $html .= '<td colspan="' . esc_attr($columnCount) . '">' . esc_html__('Datos no disponibles en el formato esperado.', 'glory-domain') . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            $html .= '<hr style="margin: 20px 0;">';
        }
        $html .= '</div>';
        return $html;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        $this->attributes['id'] = $id;
        return $this;
    }

    public function setAction(string $actionUrl): self
    {
        $this->actionUrlOriginal = $actionUrl;
        return $this;
    }

    public function setMethod(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function addAttribute(string $name, string $value): self
    {
        $name = strtolower($name);
        if (in_array($name, ['id', 'action', 'method'])) {
            return $this;
        }
        if ($name === 'class') {
            $currentClass = $this->attributes['class'] ?? '';
            $newClasses = explode(' ', $value);
            $existingClasses = explode(' ', $currentClass);
            $allClasses = array_unique(array_merge($existingClasses, $newClasses));
            $this->attributes['class'] = trim(implode(' ', array_filter($allClasses)));
        } else {
            $this->attributes[$name] = $value;
        }
        return $this;
    }

    public function addDataAttribute(string $name, string $value): self
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        if (!empty($name)) {
            $this->attributes['data-' . $name] = $value;
        }
        return $this;
    }

    public function addField(array $fieldConfig): self
    {
        $type = $fieldConfig['type'] ?? 'text';
        $requiresName = !in_array($type, ['html']);
        $defaultName = null;

        if ($requiresName || isset($fieldConfig['name'])) {
            $defaultName = $fieldConfig['name'] ?? uniqid('field_name_');
        } elseif ($type === 'submit' && !isset($fieldConfig['name'])) {
            $defaultName = null;
        }

        $defaultId = $fieldConfig['id'] ?? ($defaultName ? $this->id . '_' . $defaultName : uniqid($this->id . '_field_id_'));
        if ($type === 'html') $defaultId = null;

        $defaults = [
            'type' => $type,
            'name' => $defaultName,
            'id' => $defaultId,
            'label' => '',
            'value' => '',
            'placeholder' => '',
            'required' => false,
            'classInput' => '',
            'classContenedor' => 'glory-input-holder',
            'attributesInput' => [],
            'rows' => 7,
            'htmlContent' => '',
            'wrapInDiv' => !in_array($type, ['hidden', 'html']),
            'labelBeforeInput' => true,
        ];

        $this->fields[] = array_merge($defaults, $fieldConfig);
        return $this;
    }


    public static function mostrarMensajesFeedback(): void
    {
        if (is_admin() || !isset($_GET['glory_form_status'], $_GET['form_id'])) {
            return;
        }

        $formId = sanitize_text_field(wp_unslash($_GET['form_id']));
        $status = sanitize_text_field(wp_unslash($_GET['glory_form_status']));
        $feedbackTransientKey = 'glory_form_feedback_' . $formId;
        $feedback = get_transient($feedbackTransientKey);

        if ($feedback && is_array($feedback) && isset($feedback['message'], $feedback['type'])) {
            if ($status === $feedback['type']) {
                $message = esc_html($feedback['message']);
                $type = esc_attr($feedback['type']);

                $style = 'padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; z-index: 9999; position: relative;';
                if ($type === 'success') {
                    $style .= 'color: #155724; background-color: #d4edda; border-color: #c3e6cb;';
                } elseif ($type === 'error') {
                    $style .= 'color: #721c24; background-color: #f8d7da; border-color: #f5c6cb;';
                } else {
                    $style .= 'color: #004085; background-color: #cce5ff; border-color: #b8daff;';
                }

                echo '<div id="glory-form-feedback-' . esc_attr($formId) . '" class="glory-form-feedback glory-form-feedback-' . $type . '" style="' . $style . '">';
                echo $message;
                echo '</div>';
                delete_transient($feedbackTransientKey);
            }
        }
    }

    public function render(): string
    {
        $renderAttributes = $this->attributes;
        $renderAttributes['action'] = esc_url(admin_url('admin-ajax.php'));
        $renderAttributes['method'] = 'POST';

        $currentClassesArray = isset($this->attributes['class']) ? explode(' ', $this->attributes['class']) : [];
        if (!in_array($this->id, $currentClassesArray, true)) {
            array_unshift($currentClassesArray, $this->id);
        }
        $currentClassesArray[] = 'glory-ajax-form';

        $finalClasses = trim(implode(' ', array_unique(array_filter($currentClassesArray))));
        if (!empty($finalClasses)) {
            $renderAttributes['class'] = $finalClasses;
        }
        $renderAttributes['id'] = $this->attributes['id'] ?? $this->id;

        $formAttributesString = '';
        foreach ($renderAttributes as $name => $value) {
            if ($value === true) $formAttributesString .= " " . esc_attr($name);
            elseif ($value !== false && $value !== null && ($name !== 'value' || $value !== '')) {
                $formAttributesString .= " " . esc_attr($name) . '="' . esc_attr((string)$value) . '"';
            }
        }

        $formHtml = "<form" . $formAttributesString . ">\n";
        $formHtml .= '    <div class="glory-form-ajax-response" data-form-id="' . esc_attr($this->id) . '"></div>' . "\n";

        $formHtml .= sprintf(
            '    <input type="hidden" name="action" value="%s">' . "\n",
            esc_attr(self::AJAX_PROCESS_FORM_ACTION)
        );
        $formHtml .= sprintf(
            '    <input type="hidden" name="_glory_form_id" value="%s">' . "\n",
            esc_attr($this->id)
        );
        $nonceActionString = 'glory_form_nonce_' . $this->id;
        $formHtml .= sprintf(
            '    <input type="hidden" name="%s" value="%s">' . "\n",
            esc_attr(self::AJAX_NONCE_FIELD_NAME),
            esc_attr(wp_create_nonce($nonceActionString))
        );

        $formHtml .= $this->htmlAntesCampos;

        foreach ($this->fields as $fieldConfig) {
            $formHtml .= $this->renderizarCampo($fieldConfig);
        }

        $formHtml .= $this->htmlDespuesCampos;
        $formHtml .= "</form>\n";

        return $formHtml;
    }

    public function addHtmlAntesCampos(string $html): self
    {
        $this->htmlAntesCampos .= $html;
        return $this;
    }

    public function addHtmlDespuesCampos(string $html): self
    {
        $this->htmlDespuesCampos .= $html;
        return $this;
    }

    private function renderizarCampo(array $config): string
    {
        $fieldHtml = "";
        $type = $config['type'];
        $name = $config['name'];
        $id = $config['id'];
        $value = $config['value'];
        $label = $config['label'];
        $placeholder = $config['placeholder'];
        $required = $config['required'];
        $classInput = $config['classInput'];
        $classContenedor = $config['classContenedor'];
        $userAttributesInput = $config['attributesInput'];
        $rows = $config['rows'];
        $htmlContent = $config['htmlContent'];
        $wrapInDiv = $config['wrapInDiv'];
        $labelBeforeInput = $config['labelBeforeInput'];

        $elementAttributes = [];
        if ($name) $elementAttributes['name'] = $name;
        if ($id && $type !== 'html') $elementAttributes['id'] = $id;
        if (!in_array($type, ['textarea', 'html', 'button', 'submit'])) {
            $elementAttributes['type'] = $type;
        }
        if (in_array($type, ['text', 'email', 'tel', 'password', 'hidden', 'search', 'url', 'date', 'month', 'week', 'time', 'datetime-local', 'number', 'range', 'color', 'file', 'radio', 'checkbox'])) {
            if ($value !== '' || $type === 'hidden') {
                $elementAttributes['value'] = $value;
            }
        }
        if ($placeholder && !in_array($type, ['hidden', 'submit', 'button', 'html', 'checkbox', 'radio', 'range', 'color', 'file'])) {
            $elementAttributes['placeholder'] = $placeholder;
        }
        if ($required && !in_array($type, ['hidden', 'html', 'button'])) {
            $elementAttributes['required'] = true;
        }
        if ($classInput && $type !== 'html') {
            $elementAttributes['class'] = $classInput;
        }
        $finalAttributes = array_merge($elementAttributes, $userAttributesInput);
        $buttonDisplayContent = '';

        if ($type === 'submit' || $type === 'button') {
            $finalAttributes['type'] = $type;
            if (!empty($htmlContent)) {
                $buttonDisplayContent = $htmlContent;
            } else {
                $buttonTextFallback = $value ?: ucfirst($type);
                $buttonDisplayContent = esc_html($buttonTextFallback);
            }
        }

        $inputAttributesString = '';
        foreach ($finalAttributes as $attrName => $attrValue) {
            if ($attrValue === true) {
                $inputAttributesString .= " " . esc_attr($attrName);
            } elseif ($attrValue === false || $attrValue === null) {
                // No renderizar atributos false o null
            } else {
                $inputAttributesString .= " " . esc_attr($attrName) . '="' . esc_attr((string)$attrValue) . '"';
            }
        }

        $inputElementHtml = "";
        switch ($type) {
            case 'textarea':
                $inputElementHtml = '<textarea' . $inputAttributesString . ' rows="' . (int)$rows . '">' . esc_textarea($value) . '</textarea>';
                break;
            case 'submit':
            case 'button':
                $inputElementHtml = '<button' . $inputAttributesString . '>' . $buttonDisplayContent . '</button>';
                break;
            case 'html':
                $inputElementHtml = $htmlContent;
                break;
            default:
                $inputElementHtml = '<input' . $inputAttributesString . '>';
                break;
        }

        $labelHtml = '';
        if ($label && $type !== 'html' && $type !== 'hidden') {
            $labelFor = ($id) ? ' for="' . esc_attr($id) . '"' : '';
            $labelHtml = '<label' . $labelFor . '>' . esc_html($label) . '</label>';
        }

        if ($wrapInDiv) {
            $divClassAttr = !empty($classContenedor) ? ' class="' . esc_attr($classContenedor) . '"' : '';
            $fieldHtml .= '<div' . $divClassAttr . '>';
            if ($labelBeforeInput && $labelHtml) $fieldHtml .= $labelHtml . "\n    ";
            $fieldHtml .= $inputElementHtml;
            if (!$labelBeforeInput && $labelHtml) $fieldHtml .= "\n    " . $labelHtml;
            $fieldHtml .= "\n</div>\n";
        } else {
            if ($labelBeforeInput && $labelHtml) $fieldHtml .= $labelHtml . ($type !== 'hidden' ? "\n    " : " ");
            $fieldHtml .= $inputElementHtml;
            if (!$labelBeforeInput && $labelHtml) $fieldHtml .= ($type !== 'hidden' ? "\n    " : " ") . $labelHtml;
            $fieldHtml .= "\n";
        }
        return $fieldHtml;
    }
}