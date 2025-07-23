<?php

namespace Glory\Core;

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
        if (defined('WP_CLI') && WP_CLI) {
            return;
        }

        if (!defined('GLORY_LICENSE_KEY') || empty(GLORY_LICENSE_KEY)) {
            self::killSwitch('Clave de licencia de Glory no definida.');
            return;
        }

        // Si la caché local dice 'active', todo está bien. Terminamos aquí.
        if (get_transient(self::TRANSIENT_KEY) === 'active') {
            return;
        }

        $response = self::performRemoteCheck();

        if ($response['success']) {
            // El servidor respondió.
            $status = $response['data']['status'] ?? 'invalid';

            if ($status === 'active') {
                // *** ÚNICO LUGAR DONDE SE GUARDA EN CACHÉ ***
                // La licencia está activa. Guardamos en caché por 1 hora.
                set_transient(self::TRANSIENT_KEY, 'active', self::CHECK_INTERVAL);
                // Guardamos la última comprobación exitosa para el periodo de gracia.
                update_option(self::TRANSIENT_KEY . '_data', ['last_check' => time(), 'status' => 'active']);
                return;
            } else {
                // La licencia es 'invalid', 'suspended', etc.
                // NO guardamos nada en caché. Simplemente borramos cualquier caché antigua.
                delete_transient(self::TRANSIENT_KEY);
                self::killSwitch("La licencia de Glory no es válida (estado: {$status}).");
                return;
            }
        } else {
            // El servidor NO respondió. Usamos el periodo de gracia.
            $statusData = get_option(self::TRANSIENT_KEY . '_data', ['last_check' => 0, 'status' => 'unknown']);
            $lastSuccess = $statusData['last_check'];
            $statusPrevio = $statusData['status'];

            // Si la última vez que tuvimos éxito estaba 'active' y no ha pasado mucho tiempo...
            if ($statusPrevio === 'active' && (time() - $lastSuccess) < self::GRACE_PERIOD) {
                // ...permitimos que funcione creando una caché temporal de 1 hora.
                set_transient(self::TRANSIENT_KEY, 'active', HOUR_IN_SECONDS);
                return;
            } else {
                // El periodo de gracia expiró o nunca fue activa.
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

    private static function killSwitch(string $message): void
    {
        if (current_user_can('manage_options')) {
            add_action('admin_notices', function () use ($message) {
                echo '<div class="notice notice-error"><p><strong>Error de Glory Framework:</strong> ' . esc_html($message) . ' Por favor, contacta con el soporte.</p></div>';
            });
        }
        if (!is_admin()) {
            wp_die('Error de configuración del sitio. Por favor, contacte con el administrador.', 'Error de Licencia', 503);
        }
    }
}
