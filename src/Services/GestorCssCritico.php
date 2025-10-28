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
    private const PREFIJO_LOCK = 'glory_css_critico_lock_';

    public static function init(): void
    {
        add_action('save_post', [self::class, 'limpiarCachePost']);
        add_action('admin_bar_menu', [self::class, 'registrarMenuAdminBar'], 100);
        add_action('wp_ajax_glory_limpiar_css_critico', [self::class, 'manejarAjaxLimpiarCache']);
        add_action('wp_ajax_glory_generar_css_critico', [self::class, 'manejarAjaxGenerarCritico']);
        add_action('wp_ajax_glory_generar_css_critico_all', [self::class, 'manejarAjaxGenerarCriticoAll']);
        add_action('glory_generate_critical_css_event', [self::class, 'cronGenerarCss'], 10, 1);
        // Si está presente ?noAjax=1, inyectar bandera global para desactivar navegación AJAX
        add_action('wp_head', [self::class, 'imprimirBanderaNoAjax'], 0);
    }

    public static function getParaPaginaActual(): ?string
    {
        // Si la feature ha sido desactivada explícitamente o la opción en BD la desactiva,
        // no generar CSS crítico. Usar isActive para combinar override por código + opción en BD.
        if (GloryFeatures::isActive('cssCritico', 'glory_css_critico_activado') === false) {
            GloryLogger::info('CssCritico: desactivado por GloryFeatures');
            return null;
        }

        if (!OpcionManager::get('glory_css_critico_activado')) {
            GloryLogger::info('CssCritico: desactivado por opción BD (glory_css_critico_activado)');
            return null;
        }

        if (is_admin()) {
            GloryLogger::info('CssCritico: no se aplica en admin');
            return null;
        }

        // Determinar clave de cache: por post (singular) o por URL (home/otros)
        $cssCacheado = null;
        $currentUrl = home_url(add_query_arg([], $_SERVER['REQUEST_URI'] ?? '/'));
        GloryLogger::info('CssCritico: resolviendo para URL actual', ['url' => $currentUrl]);

        if (is_singular()) {
            $postId = get_queried_object_id();
            if ($postId) {
                $claveCache = self::getClaveCache($postId);
                $cssCacheado = get_transient($claveCache);
                if ($cssCacheado) {
                    GloryLogger::info('CssCritico: cache HIT por postId', ['postId' => $postId, 'bytes' => strlen((string)$cssCacheado)]);
                    return $cssCacheado;
                }
                GloryLogger::info('CssCritico: cache MISS por postId', ['postId' => $postId]);
            }
        } else {
            $claveCacheUrl = self::getClaveCacheParaUrl($currentUrl);
            $cssCacheado = get_transient($claveCacheUrl);
            if ($cssCacheado) {
                GloryLogger::info('CssCritico: cache HIT por URL exacta', ['bytes' => strlen((string)$cssCacheado)]);
                return $cssCacheado;
            }
            GloryLogger::info('CssCritico: cache MISS por URL exacta');

            // Fallbacks de URL para evitar misses por query params o variaciones menores
            // 1) Sin el parámetro noAjax (si viene en la URL)
            if (isset($_GET['noAjax']) && $_GET['noAjax'] === '1') {
                $urlSinNoAjax = remove_query_arg('noAjax', $currentUrl);
                $cssCacheado = get_transient(self::getClaveCacheParaUrl($urlSinNoAjax));
                if ($cssCacheado) {
                    GloryLogger::info('CssCritico: cache HIT sin noAjax', ['bytes' => strlen((string)$cssCacheado)]);
                    return $cssCacheado;
                }
                GloryLogger::info('CssCritico: cache MISS sin noAjax');
            }

            // 2) Sin ningún query param (canónica por path)
            $path = wp_parse_url($currentUrl, PHP_URL_PATH) ?: '/';
            $urlSinQuery = home_url($path);
            if ($urlSinQuery !== $currentUrl) {
                $cssCacheado = get_transient(self::getClaveCacheParaUrl($urlSinQuery));
                if ($cssCacheado) {
                    GloryLogger::info('CssCritico: cache HIT por URL sin query', ['bytes' => strlen((string)$cssCacheado)]);
                    return $cssCacheado;
                }
                GloryLogger::info('CssCritico: cache MISS por URL sin query');
            }

            // 3) Home canónica (por seguridad)
            if (is_front_page() || is_home()) {
                $homeCanonica = home_url('/');
                $cssCacheado = get_transient(self::getClaveCacheParaUrl($homeCanonica));
                if ($cssCacheado) {
                    GloryLogger::info('CssCritico: cache HIT home canónica', ['bytes' => strlen((string)$cssCacheado)]);
                    return $cssCacheado;
                }
                GloryLogger::info('CssCritico: cache MISS home canónica');
            }
        }

        // Respetar modo: solo generar automáticamente si la opción está activa
        if (!OpcionManager::get('glory_css_critico_auto')) {
            GloryLogger::info('CssCritico: auto OFF, no se agenda generación');
            return null;
        }

        // Auto-generación en background: programar WP-Cron y no bloquear la petición
        $lockKey = self::getLockKey($currentUrl);
        if (!get_transient($lockKey)) {
            set_transient($lockKey, 1, 5 * MINUTE_IN_SECONDS);
            if (!wp_next_scheduled('glory_generate_critical_css_event', [$currentUrl])) {
                wp_schedule_single_event(time() + 5, 'glory_generate_critical_css_event', [$currentUrl]);
                GloryLogger::info('CssCritico: generación programada por WP-Cron', ['url' => $currentUrl]);
            }
        }

        GloryLogger::info('CssCritico: no hay CSS cacheado aún');
        return null;
    }

    private static function generarParaUrl(string $url): ?string
    {
        $modo = OpcionManager::get('glory_critical_css_mode') ?: 'local';
        if ($modo === 'local') {
            return LocalCriticalCss::generate($url);
        }
        if ($modo !== 'remote') {
            return null;
        }

        // REMOTO: usar endpoint configurado
        $configEndpoint = $_ENV['GLORY_CRITICAL_CSS_API'] ?? getenv('GLORY_CRITICAL_CSS_API') ?: null;
        if (!$configEndpoint) {
            $configEndpoint = OpcionManager::get('glory_critical_css_api_url');
        }
        if (!$configEndpoint) {
            return null;
        }
        $apiUrl = $configEndpoint;

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
            GloryLogger::error('La API de CSS crítico devolvió un error.', ['response' => $body, 'endpoint' => $apiUrl]);
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

    public static function registrarMenuAdminBar(\WP_Admin_Bar $admin_bar): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Menú principal
        $admin_bar->add_node([
            'id'    => 'glory-critical-css',
            'title' => 'CSS crítico',
            'href'  => '#',
        ]);

        // Sub: Limpiar caché
        $admin_bar->add_node([
            'id'     => 'glory-critical-css-clear',
            'parent' => 'glory-critical-css',
            'title'  => __('Limpiar caché CSS crítico', 'glory'),
            'href'   => '#',
            'meta'   => ['onclick' => 'gloryLimpiarCssCritico(event)']
        ]);

        // Sub: Generar esta página (oculto en admin)
        if (!is_admin()) {
            $admin_bar->add_node([
                'id'     => 'glory-critical-css-generate-current',
                'parent' => 'glory-critical-css',
                'title'  => __('Generar (esta página)', 'glory'),
                'href'   => '#',
                'meta'   => ['onclick' => 'gloryGenerarCssCritico(event)']
            ]);
        }

        // Sub: Generar todas (background)
        $admin_bar->add_node([
            'id'     => 'glory-critical-css-generate-all',
            'parent' => 'glory-critical-css',
            'title'  => __('Generar para todas (background)', 'glory'),
            'href'   => '#',
            'meta'   => ['onclick' => 'gloryGenerarCssCriticoAll(event)']
        ]);

        // Imprimir helpers JS tanto en head como en footer para garantizar disponibilidad temprana
        add_action('admin_head', [self::class, 'imprimirScriptsAdminBar']);
        add_action('wp_head', [self::class, 'imprimirScriptsAdminBar']);
        add_action('admin_footer', [self::class, 'imprimirScriptsAdminBar']);
        add_action('wp_footer', [self::class, 'imprimirScriptsAdminBar']);
    }

    public static function imprimirScriptsAdminBar(): void
    {
        if (!current_user_can('manage_options')) return;
        ?>
        <script>
        (function(w){
            var AU = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
            function postAjax(payload){
                try {
                    if (w.fetch) {
                        var params = new URLSearchParams();
                        for (var k in payload) { if (Object.prototype.hasOwnProperty.call(payload, k)) params.append(k, payload[k]); }
                        return fetch(AU, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params })
                            .then(function(r){ return r.text(); })
                            .then(function(t){ try { return JSON.parse(t); } catch(_){ return { success: false, data: t }; } });
                    }
                } catch(_e) {}
                if (w.jQuery && w.jQuery.post) {
                    return new Promise(function(resolve){ w.jQuery.post(AU, payload, function(resp){ resolve(resp); }); });
                }
                alert('AJAX no disponible en este contexto.');
                return Promise.resolve({ success: false, message: 'AJAX no disponible' });
            }
            w.gloryLimpiarCssCritico = function(event){
                event && event.preventDefault && event.preventDefault();
                if (!confirm('<?php _e("¿Estás seguro de que quieres limpiar toda la caché de CSS crítico?", "glory"); ?>')) { return; }
                postAjax({ action: 'glory_limpiar_css_critico' }).then(function(response){
                    alert(response && response.data ? response.data : 'Listo');
                });
            };
            w.gloryGenerarCssCritico = function(event){
                event && event.preventDefault && event.preventDefault();
                var url = w.location && w.location.href ? w.location.href : '';
                postAjax({ action: 'glory_generar_css_critico', url: url }).then(function(response){
                    if (response && response.success) {
                        alert('CSS crítico generado (' + (response.data && response.data.bytes ? response.data.bytes : 0) + ' bytes).');
                    } else {
                        alert('No se pudo generar CSS crítico.');
                    }
                });
            };
            w.gloryGenerarCssCriticoAll = function(event){
                event && event.preventDefault && event.preventDefault();
                if (!confirm('<?php _e("¿Programar generación para todas las páginas? Se ejecutará en background.", "glory"); ?>')) { return; }
                postAjax({ action: 'glory_generar_css_critico_all' }).then(function(response){
                    alert(response && response.success ? 'Programado en background' : 'No se pudo programar');
                });
            };
        })(window);
        </script>
        <?php
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
        GloryLogger::info('CssCritico: AJAX generar solicitado', ['url' => $url]);
        $css = self::generarParaUrl($url);
        if (!$css) {
            GloryLogger::error('CssCritico: AJAX generar FALLÓ', ['url' => $url]);
            wp_send_json_error(__('No se pudo generar CSS crítico.', 'glory'));
        }
        set_transient(self::getClaveCacheParaUrl($url), $css, self::EXPIRACION_CACHE);
        $pid = function_exists('url_to_postid') ? (int) url_to_postid($url) : 0;
        if ($pid > 0) {
            self::guardarCssParaPost($pid, $css);
            GloryLogger::info('CssCritico: generado por AJAX y guardado también por postId', ['url' => $url, 'postId' => $pid, 'bytes' => strlen($css)]);
        } else {
            GloryLogger::info('CssCritico: generado por AJAX y guardado por URL (sin postId)', ['url' => $url, 'bytes' => strlen($css)]);
        }
        wp_send_json_success(['message' => __('CSS crítico generado', 'glory'), 'bytes' => strlen($css)]);
    }

    public static function manejarAjaxGenerarCriticoAll(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No autorizado', 'glory'));
        }
        $urls = [home_url('/')];
        $q = new \WP_Query([
            'post_type'      => ['page','post'],
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'fields'         => 'ids',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        foreach ($q->posts as $pid) { $urls[] = get_permalink($pid); }
        $when = time() + 5;
        foreach (array_unique($urls) as $u) {
            if (!wp_next_scheduled('glory_generate_critical_css_event', [$u])) {
                wp_schedule_single_event($when, 'glory_generate_critical_css_event', [$u]);
                $when += 2; // espaciar
            }
        }
        wp_send_json_success('OK');
    }

    private static function getClaveCacheParaUrl(string $url): string
    {
        return self::PREFIJO_CLAVE_CACHE . 'url_' . md5($url);
    }

    // APIs públicas para herramientas (CLI, admin) --------------------------------------
    public static function guardarCssParaUrl(string $url, string $css, ?int $expiracion = null): void
    {
        $exp = $expiracion ?? self::EXPIRACION_CACHE;
        set_transient(self::getClaveCacheParaUrl($url), $css, $exp);
    }
    public static function guardarCssParaPost(int $postId, string $css, ?int $expiracion = null): void
    {
        if ($postId > 0) {
            $exp = $expiracion ?? self::EXPIRACION_CACHE;
            set_transient(self::getClaveCache($postId), $css, $exp);
        }
    }

    private static function getLockKey(string $url): string
    {
        return self::PREFIJO_LOCK . md5($url);
    }

    public static function cronGenerarCss(string $url): void
    {
        $css = self::generarParaUrl($url);
        if ($css) {
            set_transient(self::getClaveCacheParaUrl($url), $css, self::EXPIRACION_CACHE);
            $pid = function_exists('url_to_postid') ? (int) url_to_postid($url) : 0;
            if ($pid > 0) {
                self::guardarCssParaPost($pid, $css);
                GloryLogger::info('CssCritico: cron guardó CSS crítico (URL y postId)', ['url' => $url, 'postId' => $pid, 'bytes' => strlen($css)]);
            } else {
                GloryLogger::info('CssCritico: cron guardó CSS crítico (solo URL)', ['url' => $url, 'bytes' => strlen($css)]);
            }
        }
        GloryLogger::info('CssCritico: cron limpieza de lock');
        delete_transient(self::getLockKey($url));
    }

    // Render de estado en panel de opciones
    public static function renderAdminStatus(): void
    {
        $items = [];
        $home = home_url('/');
        $items[] = ['tipo' => 'url', 'label' => 'Home', 'url' => $home];
        $q = new \WP_Query([
            'post_type'      => ['page', 'post'],
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'fields'         => 'ids',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        foreach ($q->posts as $pid) {
            $items[] = ['tipo' => 'post', 'id' => $pid, 'label' => get_the_title($pid), 'url' => get_permalink($pid)];
        }
        echo '<table class="widefat"><thead><tr><th>Página</th><th>Estado</th><th>Tamaño</th></tr></thead><tbody>';
        foreach ($items as $it) {
            if ($it['tipo'] === 'url') {
                $css = get_transient(self::getClaveCacheParaUrl($it['url']));
            } else {
                $css = get_transient(self::getClaveCache((int)$it['id']));
            }
            $ok = is_string($css) && $css !== '';
            $size = $ok ? strlen($css) : 0;
            printf('<tr><td><a href="%s" target="_blank">%s</a></td><td>%s</td><td>%s</td></tr>', esc_url($it['url']), esc_html($it['label']), $ok ? 'Generado' : 'Pendiente', $ok ? $size . ' bytes' : '-');
        }
        echo '</tbody></table>';
    }

    public static function imprimirBanderaNoAjax(): void
    {
        if (is_admin()) return;
        $noAjax = isset($_GET['noAjax']) && (string) $_GET['noAjax'] === '1';
        if (!$noAjax) return;
        ?>
        <script>
        (function(w){
            // Bandera global para cualquier script que necesite detectar modo congelado
            w.__GLORY_NO_AJAX__ = true;
            // Config de navegación AJAX de Glory: abortar e inhabilitar
            var cfg = w.gloryNavConfig || {};
            cfg.enabled = false;
            cfg.shouldAbortInit = function(){ return true; };
            cfg.shouldSkipAjax = function(){ return true; };
            w.gloryNavConfig = cfg;
        })(window);
        </script>
        <?php
    }

    private static function getClaveCache(int $postId): string
    {
        return self::PREFIJO_CLAVE_CACHE . $postId;
    }
}
