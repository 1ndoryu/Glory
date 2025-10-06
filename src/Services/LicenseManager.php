<?php

namespace Glory\Services;

use Glory\Core\GloryLogger;

final class LicenseManager
{
    private const LICENSE_API_URL = 'https://wandori.us/wp-json/licensing/v1/verify';
    private const TRANSIENT_KEY = 'glory_license_status_v2';
    private const CHECK_INTERVAL = HOUR_IN_SECONDS;
    private const GRACE_PERIOD = 7 * DAY_IN_SECONDS;

    public static function init(): void
    {
        add_action('after_setup_theme', [self::class, 'verifyLicense'], 1);
    }

    public static function verifyLicense(): void
    {
        if (defined('WP_CLI') && WP_CLI) return;
        
        GloryLogger::info('LICENSE_CHECK: --- Iniciando Verificación de Licencia ---');

        if (!defined('GLORY_LICENSE_KEY') || empty(GLORY_LICENSE_KEY)) {
            GloryLogger::error('LICENSE_CHECK_FAIL: La constante GLORY_LICENSE_KEY no está definida.');
            self::killSwitch('Clave de licencia de Glory no definida.');
            return;
        }
        
        GloryLogger::info('LICENSE_CHECK: Usando clave: ' . GLORY_LICENSE_KEY);
        
        $cachedStatus = get_transient(self::TRANSIENT_KEY);
        if ($cachedStatus === 'active') {
            GloryLogger::info('LICENSE_CHECK_SUCCESS: Verificación exitosa desde la caché local.');
            return;
        }

        GloryLogger::info('LICENSE_CHECK: Caché local vacía o expirada. Contactando al servidor...');
        
        $response = self::performRemoteCheck();
        
        if ($response['success']) {
            $status = $response['data']['status'] ?? 'invalid';
            GloryLogger::info('LICENSE_CHECK: El servidor respondió exitosamente.', ['status' => $status, 'data' => $response['data']]);
            
            if ($status === 'active') {
                set_transient(self::TRANSIENT_KEY, 'active', self::CHECK_INTERVAL);
                update_option(self::TRANSIENT_KEY . '_data', ['last_check' => time(), 'status' => 'active']);
                GloryLogger::info('LICENSE_CHECK_SUCCESS: Licencia activada/verificada remotamente.');
                return;
            } else {
                delete_transient(self::TRANSIENT_KEY);
                GloryLogger::error('LICENSE_CHECK_FAIL: El servidor devolvió un estado no válido.', ['status' => $status]);
                self::killSwitch("La licencia de Glory no es válida (estado: {$status}).");
                return;
            }
        } else {
            GloryLogger::error('LICENSE_CHECK_FAIL: La comunicación con el servidor falló.', ['error' => $response['error']]);
            $statusData = get_option(self::TRANSIENT_KEY . '_data', ['last_check' => 0, 'status' => 'unknown']);
            $lastSuccess = $statusData['last_check'];
            $statusPrevio = $statusData['status'];

            GloryLogger::info('LICENSE_CHECK: Evaluando periodo de gracia.', ['last_success_status' => $statusPrevio, 'last_success_time' => $lastSuccess]);
            
            if ($statusPrevio === 'active' && (time() - $lastSuccess) < self::GRACE_PERIOD) {
                set_transient(self::TRANSIENT_KEY, 'active', HOUR_IN_SECONDS);
                GloryLogger::warning('LICENSE_CHECK_SUCCESS: Servidor inaccesible, se activó el periodo de gracia.');
                return;
            } else {
                GloryLogger::critical('LICENSE_CHECK_FAIL: El periodo de gracia expiró o la licencia nunca fue activada.');
                self::killSwitch('No se pudo verificar la licencia de Glory y el periodo de gracia ha expirado.');
                return;
            }
        }
    }

    private static function performRemoteCheck(): array
    {
        $domain = home_url();
        $licenseKey = defined('GLORY_LICENSE_KEY') ? GLORY_LICENSE_KEY : '';
        $request_args = [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode(['license_key' => $licenseKey, 'domain' => $domain]),
        ];

        GloryLogger::info('LICENSE_CHECK: Detalles de la petición saliente.', ['url' => self::LICENSE_API_URL, 'body' => $request_args['body']]);
        
        $response = wp_remote_post(self::LICENSE_API_URL, $request_args);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        GloryLogger::info('LICENSE_CHECK: Respuesta cruda del servidor.', ['http_code' => $response_code, 'body' => $response_body]);
        
        if ($response_code !== 200) {
            return ['success' => false, 'error' => "Código HTTP {$response_code}."];
        }

        $data = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'JSON inválido en la respuesta.'];
        }
        return ['success' => true, 'data' => $data];
    }
    
    private static function killSwitch(string $message): void
    {
        if (current_user_can('manage_options')) {
            add_action('admin_notices', function() use ($message) {
                echo '<div class="notice notice-error"><p><strong>Error de Glory Framework:</strong> ' . esc_html($message) . ' Por favor, contacta con el soporte.</p></div>';
            });
        }
        if (!is_admin()) {
            wp_die('Error de configuración del sitio. Por favor, contacte con el administrador.', 'Error de Licencia', 503);
        }
    }
}