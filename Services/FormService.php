<?php
# /Glory/Services/FormService.php

namespace Glory\Services;

use Glory\Components\formManagerComponent;

if (!defined('ABSPATH')) {
    exit;
}

class FormService
{
    const AJAX_DELETE_SINGLE_SUBMISSION_ACTION = 'glory_delete_single_submission';
    const AJAX_DELETE_ALL_SUBMISSIONS_ACTION = 'glory_delete_all_submissions';

    /**
     * Procesa el envío de formularios AJAX.
     * Esta función es enganchada a las acciones wp_ajax_ y wp_ajax_nopriv_.
     */
    public function procesarEnvioFormularioAjax(): void
    {
        $this->agregarCabecerasCors(); // Añadir esta línea al principio

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
            'g-recaptcha-response' // Ejemplo de campo a excluir (reCAPTCHA)
            // Añadir aquí cualquier otro nombre de campo que no deba guardarse
        ];

        foreach ($_POST as $key => $value) {
            // Excluir campos internos de WordPress o Glory que no son datos del usuario
            if (strpos($key, 'wp_') === 0 || strpos($key, '_wp') === 0 || strpos($key, 'wordpress_') === 0) continue;
            if (in_array($key, $camposExcluidos, true)) continue;

            // Sanitización
            if (is_array($value)) {
                $datosFormulario[$key] = array_map('sanitize_text_field', array_map('wp_unslash', $value));
            } else {
                $datosFormulario[$key] = sanitize_text_field(wp_unslash($value));
            }
        }

        // 4. Guardar los datos
        $optionKey = 'glory_form_data_' . $formId;
        $enviosExistentes = get_option($optionKey, []);
        if (!is_array($enviosExistentes)) { // Por si la opción existe pero no es un array
            $enviosExistentes = [];
        }

        $nuevoEnvio = [
            'timestamp' => current_time('timestamp', true), // GMT timestamp
            'dateTimeFormatted' => current_time('mysql'), // Formato YYYY-MM-DD HH:MM:SS con zona horaria de WP
            'formData' => $datosFormulario,
            'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? null, // Considerar anonimización si es necesario
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ];
        $enviosExistentes[] = $nuevoEnvio; // Añade al final del array
        update_option($optionKey, $enviosExistentes, false); // 'false' para no autoload en sitios con muchos datos

        // 5. Enviar respuesta de éxito
        $successMessage = __('¡Formulario enviado con éxito!', 'glory-domain');
        // Hook para permitir modificar el mensaje de éxito
        $successMessage = apply_filters("glory_form_success_message_{$formId}", $successMessage, $datosFormulario);
        $successMessage = apply_filters("glory_form_success_message", $successMessage, $formId, $datosFormulario);

        wp_send_json_success([
            'message' => $successMessage,
            'form_id' => $formId,
            // 'redirect_url' => 'url_opcional_si_quieres_redirigir_con_js'
        ]);
    }

    /**
     * Maneja la solicitud AJAX para borrar un único envío de formulario.
     */
    public function handleDeleteSingleSubmission(): void
    {
        $this->agregarCabecerasCors(); // Añadir esta línea al principio

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', 'glory-domain')], 403);
            return;
        }

        if (!isset($_POST['form_id'], $_POST['submission_index'], $_POST['nonce'])) {
            wp_send_json_error(['message' => __('Datos incompletos para la solicitud.', 'glory-domain')], 400);
            return;
        }

        $formId = sanitize_text_field(wp_unslash($_POST['form_id']));
        $submissionIndex = sanitize_text_field(wp_unslash($_POST['submission_index'])); // Clave del array
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));

        $nonceAction = 'glory_delete_single_submission_' . $formId . '_' . $submissionIndex;
        if (!wp_verify_nonce($nonce, $nonceAction)) {
            wp_send_json_error(['message' => __('Error de seguridad al verificar el nonce. Inténtalo de nuevo.', 'glory-domain')], 403);
            return;
        }

        $optionKey = 'glory_form_data_' . $formId;
        $submissions = get_option($optionKey, []);

        if (!is_array($submissions)) {
            $submissions = [];
        }

        if (array_key_exists($submissionIndex, $submissions)) {
            unset($submissions[$submissionIndex]);
            // Las claves se conservan, no es necesario reindexar a menos que se requiera un array estrictamente numérico y secuencial.
            // Para la lógica actual de visualización, mantener las claves originales o las que queden tras `unset` es correcto.
        } else {
            wp_send_json_error(['message' => __('El mensaje a borrar no fue encontrado.', 'glory-domain')], 404);
            return;
        }

        update_option($optionKey, $submissions, false);
        wp_send_json_success(['message' => __('Mensaje borrado correctamente.', 'glory-domain')]);
    }
    /**
     * Maneja la solicitud AJAX para borrar todos los envíos de un formulario.
     */
    public function handleDeleteAllSubmissions(): void
    {
        $this->agregarCabecerasCors(); // Añadir esta línea al principio

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción.', 'glory-domain')], 403);
            return;
        }

        if (!isset($_POST['form_id'], $_POST['nonce'])) {
            wp_send_json_error(['message' => __('Datos incompletos para la solicitud.', 'glory-domain')], 400);
            return;
        }

        $formId = sanitize_text_field(wp_unslash($_POST['form_id']));
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));

        $nonceAction = 'glory_delete_all_submissions_' . $formId;
        if (!wp_verify_nonce($nonce, $nonceAction)) {
            wp_send_json_error(['message' => __('Error de seguridad al verificar el nonce. Inténtalo de nuevo.', 'glory-domain')], 403);
            return;
        }

        $optionKey = 'glory_form_data_' . $formId;

        // Guardar un array vacío en lugar de delete_option para que el formId siga "activo"
        // y se muestre "No hay envíos para este formulario todavía." en el panel.
        update_option($optionKey, [], false);
        wp_send_json_success(['message' => __('Todos los mensajes para este formulario han sido borrados.', 'glory-domain')]);
    }
    
    public function agregarCabecerasCors(): void
    {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ORIGIN'])) : '';
        // Lista de orígenes permitidos. Deberías restringir esto lo máximo posible.
        // Para desarrollo, tu origen con el puerto :10022 es necesario.
        // En producción, esto podría ser el mismo dominio o no necesitarse si el frontend y backend están en el mismo origen.
        $allowed_origins = [
            'http://tasklist.local:10022', // Servidor de desarrollo frontend
            'http://tasklist.local',    // Si también accedes a WP desde aquí sin puerto y necesitas CORS
        ];

        if (in_array($origin, $allowed_origins, true)) {
            header("Access-Control-Allow-Origin: " . $origin);
            header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Métodos que permites
            header("Access-Control-Allow-Headers: Content-Type, X-Requested-With"); // Cabeceras que tu JS podría enviar
            header("Access-Control-Allow-Credentials: true"); // Necesario si envías cookies (ej. para sesiones de WP)
        }

        // Manejar solicitudes OPTIONS (preflight)
        // El navegador envía una solicitud OPTIONS antes de POST/PUT etc. a diferentes orígenes
        // para verificar si la solicitud real está permitida.
        if ('OPTIONS' === strtoupper($_SERVER['REQUEST_METHOD'])) {
            if (in_array($origin, $allowed_origins, true)) {
                status_header(200); // Respuesta OK para la solicitud OPTIONS
            } else {
                status_header(403); // Forbidden si el origen no está permitido
            }
            exit; // Terminar ejecución para solicitudes OPTIONS después de enviar cabeceras.
        }
    }

    /**
     * Registra los hooks de WordPress para el procesamiento de formularios AJAX y borrado.
     */
    public function registrarHooks(): void
    {
        // Hook para el envío de formularios
        $gloryAjaxAction = formManagerComponent::AJAX_PROCESS_FORM_ACTION;
        add_action('wp_ajax_' . $gloryAjaxAction, [$this, 'procesarEnvioFormularioAjax']);
        add_action('wp_ajax_nopriv_' . $gloryAjaxAction, [$this, 'procesarEnvioFormularioAjax']);

        // Hooks para el borrado de envíos (solo para usuarios logueados con permisos)
        add_action('wp_ajax_' . self::AJAX_DELETE_SINGLE_SUBMISSION_ACTION, [$this, 'handleDeleteSingleSubmission']);
        add_action('wp_ajax_' . self::AJAX_DELETE_ALL_SUBMISSIONS_ACTION, [$this, 'handleDeleteAllSubmissions']);
    }
}

$formService = new FormService();
$formService->registrarHooks();
