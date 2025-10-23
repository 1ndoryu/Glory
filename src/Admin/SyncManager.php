<?php

namespace Glory\Admin;

use Glory\Core\GloryLogger;
use Glory\Services\DefaultContentSynchronizer;
use Glory\Manager\OpcionManager;
use Glory\Manager\PageManager;
use Glory\Manager\AssetManager;

class SyncManager
{

    private static bool $showAdminBar = true;


    private static bool $showResetButton = true;

    public static function setAdminBarVisible(bool $visible): void
    {
        self::$showAdminBar = $visible;
    }

    public static function setResetButtonVisible(bool $visible): void
    {
        self::$showResetButton = $visible;
    }

    public function registerHooks(): void
    {
        add_action('admin_bar_menu', [$this, 'addSyncButtons'], 999);
        add_action('init', [$this, 'handleSyncActions'], 20);
        add_action('admin_notices', [$this, 'showSyncNotice']);
        // Nuevo hook para la sincronización automática
        add_action('init', [$this, 'performAutomaticSyncIfDevMode'], 15);
    }

    /**
     * Realiza una sincronización completa si el modo de desarrollo global está activado.
     */
    public function performAutomaticSyncIfDevMode(): void
    {
        $devMode = AssetManager::isGlobalDevMode();
        $wpDebug = (defined('WP_DEBUG') && WP_DEBUG);
        // GloryLogger::info('SyncManager: Estado de desarrollo', [
        //     'globalDevMode' => $devMode ? 'on' : 'off',
        //     'wpDebug' => $wpDebug ? 'on' : 'off',
        // ]);

        // Limitar auto-sync al área de administración
        if (!is_admin()) {
            // GloryLogger::info('SyncManager: DEV activado, omitiendo auto-sync en frontend.');
            return;
        }

        if ($devMode) {
            // GloryLogger::info('SyncManager: Modo DEV activado, ejecutando sincronización automática.');
            $this->runFullSync();
        }
    }

    public function addSyncButtons(\WP_Admin_Bar $wp_admin_bar): void
    {
        if (!current_user_can('manage_options') || !self::$showAdminBar) {
            return;
        }

        $wp_admin_bar->add_node([
            'id'    => 'glory_sync_group',
            'title' => 'Glory Sync',
            'href'  => '#'
        ]);

        $wp_admin_bar->add_node([
            'id'     => 'glory_force_sync',
            'parent' => 'glory_sync_group',
            'title'  => 'Sincronizar Todo',
            'href'   => add_query_arg('glory_action', 'sync'),
            'meta'   => [
                'title' => 'Sincroniza Opciones, Páginas y Contenido por Defecto desde el código a la base de datos.',
            ],
        ]);

        if (self::$showResetButton) {
            $wp_admin_bar->add_node([
                'id'     => 'glory_reset_default',
                'parent' => 'glory_sync_group',
                'title'  => 'Restablecer a Default',
                'href'   => add_query_arg('glory_action', 'reset'),
                'meta'   => [
                    'title' => 'Restablece el contenido modificado manualmente a su estado original definido en el código.',
                ],
            ]);
        }

        $wp_admin_bar->add_node([
            'id'     => 'glory_clear_cache',
            'parent' => 'glory_sync_group',
            'title'  => 'Borrar Caché de Glory',
            'href'   => add_query_arg([
                'glory_action' => 'clear_cache',
                'nocache' => time(),
            ]),
            'meta'   => [
                'title' => 'Elimina toda la caché de contenido (transients) generada por Glory.',
            ],
        ]);
    }

    public function handleSyncActions(): void
    {
        if (!isset($_GET['glory_action']) || !current_user_can('manage_options')) {
            return;
        }

        $action = sanitize_key($_GET['glory_action']);
        $redirect_url = remove_query_arg('glory_action');

        if ($action === 'sync') {
            GloryLogger::info('Sincronización manual forzada por el usuario desde la barra de admin.');
            $this->runFullSync();
            $redirect_url = add_query_arg('glory_sync_notice', 'sync_success', $redirect_url);

        } elseif ($action === 'reset') {
            GloryLogger::info('Restablecimiento a default forzado por el usuario desde la barra de admin.');
            $sync = new DefaultContentSynchronizer();
            $sync->restablecer();
            // Reaplicar definiciones de páginas y SEO por defecto
            if (\Glory\Core\GloryFeatures::isActive('pageManager') !== false) {
                PageManager::procesarPaginasDefinidas();
                PageManager::reconciliarPaginasGestionadas();
            }
            $redirect_url = add_query_arg('glory_sync_notice', 'reset_success', $redirect_url);

        } elseif ($action === 'clear_cache') {
            $this->clearAllCaches();
            GloryLogger::info('Todas las cachés limpiadas manualmente por el usuario.');
            $redirect_url = add_query_arg([
                'glory_sync_notice' => 'cache_cleared',
                'nocache' => time(),
            ], $redirect_url);
        }

        wp_safe_redirect($redirect_url);
        exit;
    }

    private function clearAllCaches(): void
    {
        // 1) Limpiar caché en memoria de Glory
        OpcionManager::clearCache();

        // 2) Limpiar caché de objeto de WordPress
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        // 3) Borrar transients (incluye timeouts) y site transients
        global $wpdb;
        // Options table (single-site transients y también site_transients en single-site)
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_timeout_%'");
        // Multisite: site transients viven en sitemeta
        if (is_multisite() && !empty($wpdb->sitemeta)) {
            $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_%'");
            $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '_site_transient_timeout_%'");
        }

        // 4) Borrar archivos de caché del framework (assets discovery cache)
        if (defined('GLORY_FRAMEWORK_PATH')) {
            $cacheDir = rtrim(GLORY_FRAMEWORK_PATH, '/\\') . '/cache';
            if (is_dir($cacheDir) && is_readable($cacheDir)) {
                foreach (glob($cacheDir . '/*') ?: [] as $cacheFile) {
                    if (is_file($cacheFile) && is_writable($cacheFile)) {
                        @unlink($cacheFile);
                    }
                }
            }
        }

        // 5) Limpiar caches de plugins populares si existen
        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        // LiteSpeed Cache
        if (function_exists('litespeed_purge_all')) {
            litespeed_purge_all();
        } else {
            do_action('litespeed_purge_all');
        }
        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        } else {
            do_action('wpsc_clear_cache');
        }
        // WP Fastest Cache
        if (function_exists('wpfc_clear_all_cache')) {
            wpfc_clear_all_cache(true);
        }
        // Hummingbird
        do_action('wphb_clear_page_cache');
        do_action('wphb_clear_minify_cache');
        // Cache Enabler
        do_action('cache_enabler_clear_complete_cache');
        // SG Optimizer
        if (function_exists('sg_cachepress_purge_cache')) {
            sg_cachepress_purge_cache();
        }
        if (function_exists('sg_cachepress_purge_everything')) {
            sg_cachepress_purge_everything();
        }
        // Autoptimize
        if (class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')) {
            \autoptimizeCache::clearall();
        }
        // Swift Performance
        do_action('swift_performance_clear_all_cache');
        // Kinsta, WP Engine, Nginx Helper, Cloudflare, Pantheon, etc.
        do_action('kinsta_cache_flush');
        do_action('wpe_purge_all_caches');
        do_action('rt_nginx_helper_purge_all');
        do_action('cloudflare_purge_all');
        do_action('pantheon_cache_clear');

        // 6) Flush rewrite rules
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }

        // 7) Opcache reset (si está disponible)
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }
    
    private function runFullSync(): void {
        OpcionManager::sincronizarTodasLasOpciones();

        // Solo ejecutar PageManager si la feature está activa
        if (\Glory\Core\GloryFeatures::isActive('pageManager') !== false) {
            PageManager::procesarPaginasDefinidas();
            PageManager::reconciliarPaginasGestionadas();
        }

        $sync = new DefaultContentSynchronizer();
        $sync->sincronizar();
    }

    public function showSyncNotice(): void
    {
        if (isset($_GET['glory_sync_notice'])) {
            $notice_type = sanitize_key($_GET['glory_sync_notice']);
            $message = '';

            if ($notice_type === 'sync_success') {
                $message = '<strong>Sincronización de Glory completada con éxito.</strong>';
            } elseif ($notice_type === 'reset_success') {
                $message = '<strong>Contenido de Glory restablecido a default con éxito.</strong>';
            } elseif ($notice_type === 'cache_cleared') {
                $message = '<strong>Caché de Glory borrada con éxito.</strong>';
            }

            if ($message) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
            }
        }
    }
}