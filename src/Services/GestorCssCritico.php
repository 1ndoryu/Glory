<?php

namespace Glory\Services;

use Glory\Core\GloryLogger;
use Glory\Manager\OpcionManager;

class GestorCssCritico
{
    private const PREFIJO_CLAVE_CACHE = 'glory_css_critico_';
    private const EXPIRACION_CACHE = DAY_IN_SECONDS;

    public static function init(): void
    {
        add_action('save_post', [self::class, 'limpiarCachePost']);
        add_action('admin_bar_menu', [self::class, 'agregarBotonLimpiarCache'], 100);
        add_action('wp_ajax_glory_limpiar_css_critico', [self::class, 'manejarAjaxLimpiarCache']);
    }

    public static function getParaPaginaActual(): ?string
    {
        if (!OpcionManager::get('glory_css_critico_activado')) {
            return null;
        }

        if (is_admin() || !is_singular()) {
            return null;
        }

        $postId = get_queried_object_id();
        if (!$postId) {
            return null;
        }

        $claveCache = self::getClaveCache($postId);
        $cssCacheado = get_transient($claveCache);

        if ($cssCacheado) {
            return $cssCacheado;
        }

        $cssGenerado = self::generarParaUrl(get_permalink($postId));

        if ($cssGenerado) {
            set_transient($claveCache, $cssGenerado, self::EXPIRACION_CACHE);
            return $cssGenerado;
        }

        return null;
    }

    private static function generarParaUrl(string $url): ?string
    {
        $apiUrl = 'https://critical-css-api.glorycat.workers.dev/';

        $response = wp_remote_post($apiUrl, [
            'body' => json_encode(['url' => $url]),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            GloryLogger::error('Fallo en la petición a la API de CSS crítico.', ['error' => $response->get_error_message()]);
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (wp_remote_retrieve_response_code($response) !== 200 || empty($data['critical_css'])) {
            GloryLogger::error('La API de CSS crítico devolvió un error.', ['response' => $body]);
            return null;
        }

        return $data['critical_css'];
    }

    public static function limpiarCachePost(int $postId): void
    {
        if (wp_is_post_revision($postId)) {
            return;
        }
        $claveCache = self::getClaveCache($postId);
        delete_transient($claveCache);
    }

    public static function agregarBotonLimpiarCache(\WP_Admin_Bar $admin_bar): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $admin_bar->add_node([
            'id'    => 'glory-limpiar-css-critico',
            'title' => '<span class="ab-icon"></span>' . __('Limpiar Caché de CSS Crítico', 'glory'),
            'href'  => '#',
            'meta'  => [
                'onclick' => 'gloryLimpiarCssCritico(event)',
            ],
        ]);

        add_action('admin_footer', function() {
            ?>
            <script>
            function gloryLimpiarCssCritico(event) {
                event.preventDefault();
                if (!confirm('<?php _e("¿Estás seguro de que quieres limpiar toda la caché de CSS crítico?", "glory"); ?>')) {
                    return;
                }
                jQuery.post(ajaxurl, { action: 'glory_limpiar_css_critico' }, function(response) {
                    alert(response.data);
                });
            }
            </script>
            <?php
        });
    }

    public static function manejarAjaxLimpiarCache(): void
    {
        global $wpdb;
        $prefijo = $wpdb->esc_like(self::PREFIJO_CLAVE_CACHE);
        $sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_{$prefijo}%' OR option_name LIKE '_transient_timeout_{$prefijo}%'";
        $wpdb->query($sql);
        wp_send_json_success(__('La caché de CSS crítico se ha limpiado correctamente.', 'glory'));
    }

    private static function getClaveCache(int $postId): string
    {
        return self::PREFIJO_CLAVE_CACHE . $postId;
    }
}
