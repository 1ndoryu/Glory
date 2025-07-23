<?php

namespace Glory\Core;

// use Glory\Core\GloryLogger;

final class LicenseManager
{
    /** URL de tu servidor de licencias. */
    private const LICENSE_API_URL = 'https://wandori.us/wp-json/licensing/v1/verify';
    
    /** Clave para guardar el estado de la licencia localmente (cache). */
    private const TRANSIENT_KEY = 'glory_license_status_v2';
    
    /**
     * Con qué frecuencia (en segundos) se debe contactar al servidor.
     * Cambiado a 1 hora según tu solicitud para optimizar.
     */
    private const CHECK_INTERVAL = HOUR_IN_SECONDS;

    /** Cuánto tiempo (en segundos) permitimos que el framework funcione si el servidor no responde. */
    private const GRACE_PERIOD = 7 * DAY_IN_SECONDS;
    
    /**
     * Punto de entrada para iniciar la verificación.
     */
    public static function init(): void
    {
        add_action('after_setup_theme', [self::class, 'verifyLicense'], 1);
    }

    /**
     * Orquesta el proceso de verificación de la licencia.
     */
    public static function verifyLicense(): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            return;
        }
        
        if (!defined('GLORY_LICENSE_KEY') || empty(GLORY_LICENSE_KEY)) {
            // GloryLogger::error('LICENSE_CHECK: La constante GLORY_LICENSE_KEY no está definida.');
            self::killSwitch('Clave de licencia de Glory no definida.');
            return;
        }
        
        // Comprueba la caché local. Si es válida y activa, termina la ejecución aquí.
        if (get_transient(self::TRANSIENT_KEY) === 'active') {
            return;
        }
        
        // La caché no es válida o ha expirado. Realizar comprobación remota.
        $response = self::performRemoteCheck();
        
        $statusData = get_option(self::TRANSIENT_KEY . '_data', ['last_check' => 0, 'status' => 'unknown']);

        if ($response['success']) {
            // La comunicación con el servidor fue exitosa.
            $status = $response['data']['status'] ?? 'invalid';
            
            if ($status === 'active') {
                // Licencia activa. Guardamos en caché por el intervalo definido (1 hora).
                set_transient(self::TRANSIENT_KEY, 'active', self::CHECK_INTERVAL);
                update_option(self::TRANSIENT_KEY . '_data', ['last_check' => time(), 'status' => 'active']);
                return; // Todo en orden.
            } else {
                // Licencia suspendida, inválida, etc.
                delete_transient(self::TRANSIENT_KEY);
                update_option(self::TRANSIENT_KEY . '_data', ['last_check' => time(), 'status' => $status]);
                // GloryLogger::error('LICENSE_CHECK: Licencia NO VÁLIDA según el servidor.', ['status_recibido' => $status, 'mensaje_servidor' => ($response['data']['message'] ?? 'N/A')]);
                self::killSwitch("La licencia de Glory no es válida (estado: {$status}).");
                return;
            }

        } else {
            // La comunicación con el servidor falló. Activamos el modo de gracia.
            $lastSuccess = $statusData['last_check'];
            $statusPrevio = $statusData['status'];

            // Si la última vez que comprobamos estaba activa y no ha pasado el periodo de gracia...
            if ($statusPrevio === 'active' && (time() - $lastSuccess) < self::GRACE_PERIOD) {
                // ...permitimos el funcionamiento, pero creamos una caché corta (1 hora) para reintentar pronto.
                set_transient(self::TRANSIENT_KEY, 'active', HOUR_IN_SECONDS);
                // GloryLogger::warning('LICENSE_CHECK: Servidor inaccesible, pero se activó el PERIODO DE GRACIA.');
                return;
            } else {
                // El periodo de gracia ha expirado o la licencia nunca fue activa.
                // GloryLogger::critical('LICENSE_CHECK: Servidor inaccesible y el PERIODO DE GRACIA EXPIRÓ.');
                self::killSwitch('No se pudo verificar la licencia de Glory y el periodo de gracia ha expirado.');
                return;
            }
        }
    }

    /**
     * Realiza la llamada HTTP al servidor de licencias.
     */
    private static function performRemoteCheck(): array
    {
        $domain = home_url();
        $licenseKey = defined('GLORY_LICENSE_KEY') ? GLORY_LICENSE_KEY : '';
        
        $request_args = [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode(['license_key' => $licenseKey, 'domain' => $domain]),
        ];

        // GloryLogger::info('LICENSE_CHECK: Enviando petición al servidor.', ['url' => self::LICENSE_API_URL, 'args' => $request_args]);
        $response = wp_remote_post(self::LICENSE_API_URL, $request_args);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return ['success' => false, 'error' => "El servidor respondió con código HTTP {$response_code}."];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'La respuesta del servidor no es un JSON válido.'];
        }

        return ['success' => true, 'data' => $data];
    }
    
    /**
     * Acción a tomar cuando la licencia no es válida.
     */
    private static function killSwitch(string $message): void
    {
        // Solo muestra el mensaje de error a los administradores para no alertar a usuarios normales.
        if (current_user_can('manage_options')) {
            add_action('admin_notices', function() use ($message) {
                echo '<div class="notice notice-error"><p><strong>Error de Glory Framework:</strong> ' . esc_html($message) . ' Por favor, contacta con el soporte.</p></div>';
            });
        }
        
        // Para visitantes, muestra un error genérico y detiene la carga del sitio.
        if (!is_admin()) {
            wp_die('Error de configuración del sitio. Por favor, contacte con el administrador.', 'Error de Licencia', 503);
        }
    }
}