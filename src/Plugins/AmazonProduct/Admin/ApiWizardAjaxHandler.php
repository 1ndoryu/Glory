<?php

namespace Glory\Plugins\AmazonProduct\Admin;

/**
 * Handler de AJAX para el wizard de configuracion de API.
 * 
 * Maneja las peticiones AJAX para probar la conexion y guardar la API Key.
 */
class ApiWizardAjaxHandler
{
    public static function init(): void
    {
        add_action('wp_ajax_amazon_test_api_connection', [self::class, 'testApiConnection']);
        add_action('wp_ajax_amazon_save_api_key', [self::class, 'saveApiKey']);
    }

    /**
     * Prueba la conexion con la API de RapidAPI.
     */
    public static function testApiConnection(): void
    {
        check_ajax_referer('api_wizard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta accion.');
            return;
        }

        $apiKey = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($apiKey)) {
            wp_send_json_error('La API Key es requerida.');
            return;
        }

        // Probar la conexion con la API
        $testResult = self::performApiTest($apiKey);

        if ($testResult['success']) {
            wp_send_json_success($testResult['message']);
        } else {
            wp_send_json_error($testResult['message']);
        }
    }

    /**
     * Guarda la API Key en la base de datos.
     */
    public static function saveApiKey(): void
    {
        check_ajax_referer('api_wizard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para realizar esta accion.');
            return;
        }

        $apiKey = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($apiKey)) {
            wp_send_json_error('La API Key es requerida.');
            return;
        }

        // Guardar la API Key
        update_option('amazon_api_key', $apiKey);

        // Asegurar que el host de la API este configurado
        $currentHost = get_option('amazon_api_host', '');
        if (empty($currentHost)) {
            update_option('amazon_api_host', 'amazon-data.p.rapidapi.com');
        }

        // Asegurar que el provider este configurado como rapidapi
        update_option('amazon_api_provider', 'rapidapi');

        wp_send_json_success('API Key guardada correctamente.');
    }

    /**
     * Realiza una peticion de prueba a la API.
     */
    private static function performApiTest(string $apiKey): array
    {
        $apiHost = get_option('amazon_api_host', 'amazon-data.p.rapidapi.com');

        // Endpoint de prueba - buscar un producto simple
        $testUrl = 'https://' . $apiHost . '/search?query=test&country=ES&page=1';

        $response = wp_remote_get($testUrl, [
            'headers' => [
                'X-RapidAPI-Key' => $apiKey,
                'X-RapidAPI-Host' => $apiHost,
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Error de conexion: ' . $response->get_error_message(),
            ];
        }

        $responseCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($responseCode === 200) {
            $data = json_decode($body, true);
            if (isset($data['results']) || isset($data['data'])) {
                return [
                    'success' => true,
                    'message' => 'Conexion exitosa! La API esta funcionando correctamente.',
                ];
            }
        }

        // Manejar errores especificos
        if ($responseCode === 401 || $responseCode === 403) {
            return [
                'success' => false,
                'message' => 'API Key invalida o sin permisos. Verifica que la clave sea correcta.',
            ];
        }

        if ($responseCode === 429) {
            return [
                'success' => false,
                'message' => 'Limite de llamadas excedido. Espera un momento e intenta de nuevo.',
            ];
        }

        return [
            'success' => false,
            'message' => 'Error inesperado (codigo ' . $responseCode . '). Verifica tu configuracion.',
        ];
    }
}
