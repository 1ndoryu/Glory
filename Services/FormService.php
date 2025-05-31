<?php
# /Glory/Services/FormService.php

namespace Glory\Services;

use Glory\Components\formManagerComponent;

if (!defined('ABSPATH')) {
    exit;
}

class FormService
{
    /**
     * Procesa el envío de formularios AJAX.
     * Esta función es enganchada a las acciones wp_ajax_ y wp_ajax_nopriv_.
     */
    public function procesarEnvioFormularioAjax(): void
    {
        // 1. Obtener formId
        if (!isset($_POST['_glory_form_id']) || empty($_POST['_glory_form_id'])) {
            wp_send_json_error([
                'message' => __('Error: Identificador de formulario no proporcionado.', 'glory-domain'),
                'debug_info' => '_glory_form_id missing or empty.'
            ], 400);
            return;
        }
        $formId = sanitize_text_field(wp_unslash($_POST['_glory_form_id']));

        // 2. Verificar Nonce
        $nonceFieldName = formManagerComponent::AJAX_NONCE_FIELD_NAME;
        $nonceAction = 'glory_form_nonce_' . $formId;
        $nonceValueFromPost = isset($_POST[$nonceFieldName]) ? sanitize_text_field(wp_unslash($_POST[$nonceFieldName])) : null;

        if (!$nonceValueFromPost) {
            wp_send_json_error([
                'message' => __('Error de seguridad: Falta el token. Recargue e intente de nuevo.', 'glory-domain'),
                'debug_info' => 'Nonce field (' . $nonceFieldName . ') missing.',
                'expected_nonce_action' => $nonceAction
            ], 403);
            return;
        }

        if (!wp_verify_nonce($nonceValueFromPost, $nonceAction)) {
            wp_send_json_error([
                'message' => __('Error de seguridad: Verificación fallida. Sesión expirada?. Recargue e intente de nuevo.', 'glory-domain'),
                'debug_info' => 'wp_verify_nonce failed.',
                'expected_nonce_action' => $nonceAction,
                'received_nonce_value' => $nonceValueFromPost
            ], 403);
            return;
        }

        // 3. Recopilar y Sanitizar Datos
        $datosFormulario = [];
        $camposExcluidos = [
            'action',
            $nonceFieldName,
            '_glory_form_id',
            'submit',
            'g-recaptcha-response'
        ];

        foreach ($_POST as $key => $value) {
            if (strpos($key, 'wp_') === 0 || strpos($key, '_wp') === 0 || strpos($key, 'wordpress_') === 0) continue;
            if (in_array($key, $camposExcluidos, true)) continue;

            if (is_array($value)) {
                $datosFormulario[$key] = array_map('sanitize_text_field', array_map('wp_unslash', $value));
            } else {
                $datosFormulario[$key] = sanitize_text_field(wp_unslash($value));
            }
        }
        
        // 4. Guardar los datos
        $optionKey = 'glory_form_data_' . $formId;
        $enviosExistentes = get_option($optionKey, []);
        if (!is_array($enviosExistentes)) {
            $enviosExistentes = [];
        }

        $nuevoEnvio = [
            'timestamp' => current_time('timestamp', true),
            'dateTimeFormatted' => current_time('mysql'),
            'formData' => $datosFormulario,
            'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? null,
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        $enviosExistentes[] = $nuevoEnvio;
        update_option($optionKey, $enviosExistentes, false);
        
        // 5. Enviar respuesta de éxito
        $successMessage = __('¡Formulario enviado con éxito!', 'glory-domain');
        wp_send_json_success([
            'message' => $successMessage,
            'form_id' => $formId,
        ]);
    }

    /**
     * Registra los hooks de WordPress para el procesamiento de formularios AJAX.
     */
    public function registrarHooks(): void
    {
        $gloryAjaxAction = formManagerComponent::AJAX_PROCESS_FORM_ACTION;
        
        add_action('wp_ajax_' . $gloryAjaxAction, [$this, 'procesarEnvioFormularioAjax']);
        add_action('wp_ajax_nopriv_' . $gloryAjaxAction, [$this, 'procesarEnvioFormularioAjax']);
    }
}

$formService = new FormService();
$formService->registrarHooks();