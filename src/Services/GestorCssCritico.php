<?php

namespace Glory\Services;

use Glory\Core\GloryLogger;
use Glory\Core\GloryFeatures;
use Glory\Manager\OpcionManager;
use Glory\Services\LocalCriticalCss;

class GestorCssCritico
{
    private const PREFIJO_CLAVE_CACHE = 'glory_css_critico_';
    private const EXPIRACION_CACHE = DAY_IN_SECONDS;

    public static function init(): void
    {
        add_action('save_post', [self::class, 'limpiarCachePost']);
        add_action('admin_bar_menu', [self::class, 'agregarBotonLimpiarCache'], 100);
        add_action('wp_ajax_glory_limpiar_css_critico', [self::class, 'manejarAjaxLimpiarCache']);
        add_action('wp_ajax_glory_generar_css_critico', [self::class, 'manejarAjaxGenerarCritico']);
    }

    public static function getParaPaginaActual(): ?string
    {
        // Si la feature ha sido desactivada explícitamente o la opción en BD la desactiva,
        // no generar CSS crítico. Usar isActive para combinar override por código + opción en BD.
        if (GloryFeatures::isActive('cssCritico', 'glory_css_critico_activado') === false) {
            return null;
        }

        if (!OpcionManager::get('glory_css_critico_activado')) {
            return null;
        }

        if (is_admin()) {
            return null;
        }

        // Determinar clave de cache: por post (singular) o por URL (home/otros)
        $cssCacheado = null;
        $currentUrl = home_url(add_query_arg([], $_SERVER['REQUEST_URI'] ?? '/'));

        if (is_singular()) {
            $postId = get_queried_object_id();
            if ($postId) {
                $claveCache = self::getClaveCache($postId);
                $cssCacheado = get_transient($claveCache);
                if ($cssCacheado) { return $cssCacheado; }
            }
        } else {
            $claveCacheUrl = self::getClaveCacheParaUrl($currentUrl);
            $cssCacheado = get_transient($claveCacheUrl);
            if ($cssCacheado) { return $cssCacheado; }
        }

        // Respetar modo: solo generar automáticamente si la opción está activa
        if (!OpcionManager::get('glory_css_critico_auto')) {
            return null;
        }

        // Auto-generación en primera visita
        $cssGenerado = self::generarParaUrl($currentUrl);
        if ($cssGenerado) {
            if (is_singular() && !empty($postId)) {
                set_transient(self::getClaveCache($postId), $cssGenerado, self::EXPIRACION_CACHE);
            } else {
                set_transient(self::getClaveCacheParaUrl($currentUrl), $cssGenerado, self::EXPIRACION_CACHE);
            }
            return $cssGenerado;
        }

        return null;
    }

    private static function generarParaUrl(string $url): ?string
    {
        // Modo local: usar generador con Penthouse/Puppeteer (sin servicios externos)
        $modo = OpcionManager::get('glory_critical_css_mode') ?: 'remote';
        if ($modo === 'local') {
            $cssLocal = LocalCriticalCss::generate($url);
            if ($cssLocal) { return $cssLocal; }
            // Si local falla, continuar con remoto si hubiera
        }

        // Permitir configurar el endpoint por ENV u opción de WP, con fallback al default
        $configEndpoint = $_ENV['GLORY_CRITICAL_CSS_API'] ?? getenv('GLORY_CRITICAL_CSS_API') ?: null;
        if (!$configEndpoint) {
            $configEndpoint = OpcionManager::get('glory_critical_css_api_url');
        }
        $apiUrl = $configEndpoint ?: 'https://critical-css-api.glorycat.workers.dev/';

        $payload = json_encode(['url' => $url]);
        $args = [
            'body'    => $payload,
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 15,
        ];

        $response = wp_remote_post($apiUrl, $args);

        if (is_wp_error($response)) {
            GloryLogger::error('Fallo en la petición a la API de CSS crítico.', [
                'error'   => $response->get_error_message(),
                'endpoint'=> $apiUrl,
            ]);
            // Intentar un endpoint de respaldo si está configurado
            $backup = OpcionManager::get('glory_critical_css_api_backup_url');
            if ($backup && filter_var($backup, FILTER_VALIDATE_URL)) {
                $response = wp_remote_post($backup, $args);
                if (is_wp_error($response)) {
                    GloryLogger::error('Fallo también en el endpoint de respaldo de CSS crítico.', [
                        'error'   => $response->get_error_message(),
                        'endpoint'=> $backup,
                    ]);
                    return null;
                }
            } else {
                return null;
            }
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (wp_remote_retrieve_response_code($response) !== 200 || empty($data['critical_css'])) {
            GloryLogger::error('La API de CSS crítico devolvió un error.', [
                'response' => $body,
                'endpoint' => isset($backup) && $backup ? $backup : $apiUrl,
            ]);
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
            function gloryGenerarCssCritico(event) {
                event.preventDefault();
                var url = window.location.href;
                jQuery.post(ajaxurl, { action: 'glory_generar_css_critico', url: url }, function(response) {
                    if (response && response.success) {
                        alert('CSS crítico generado (' + (response.data && response.data.bytes ? response.data.bytes : 0) + ' bytes).');
                    } else {
                        alert('No se pudo generar CSS crítico.');
                    }
                });
            }
            </script>
            <?php
        });

        // Botón para generar CSS crítico de la URL actual
        $admin_bar->add_node([
            'id'    => 'glory-generar-css-critico',
            'title' => '<span class="ab-icon"></span>' . __('Generar CSS Crítico (esta página)', 'glory'),
            'href'  => '#',
            'meta'  => [
                'onclick' => 'gloryGenerarCssCritico(event)',
            ],
        ]);
    }

    public static function manejarAjaxLimpiarCache(): void
    {
        global $wpdb;
        $prefijo = $wpdb->esc_like(self::PREFIJO_CLAVE_CACHE);
        $sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_{$prefijo}%' OR option_name LIKE '_transient_timeout_{$prefijo}%'";
        $wpdb->query($sql);
        wp_send_json_success(__('La caché de CSS crítico se ha limpiado correctamente.', 'glory'));
    }

    public static function manejarAjaxGenerarCritico(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No autorizado', 'glory'));
        }
        $url = isset($_POST['url']) && is_string($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (!$url) {
            $url = wp_get_referer() ?: home_url('/');
        }
        $css = self::generarParaUrl($url);
        if (!$css) {
            wp_send_json_error(__('No se pudo generar CSS crítico.', 'glory'));
        }
        set_transient(self::getClaveCacheParaUrl($url), $css, self::EXPIRACION_CACHE);
        wp_send_json_success(['message' => __('CSS crítico generado', 'glory'), 'bytes' => strlen($css)]);
    }

    private static function getClaveCacheParaUrl(string $url): string
    {
        return self::PREFIJO_CLAVE_CACHE . 'url_' . md5($url);
    }

    private static function getClaveCache(int $postId): string
    {
        return self::PREFIJO_CLAVE_CACHE . $postId;
    }
}
