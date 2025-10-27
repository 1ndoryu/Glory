<?php

namespace Glory\Admin;

use Glory\Core\GloryLogger;
use Glory\Services\DefaultContentSynchronizer;
use Glory\Manager\OpcionManager;
use Glory\Manager\PageManager;
use Glory\Manager\AssetManager;
use Glory\Manager\MenuManager;

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

        if (!$devMode) return;

        // En admin: sincronización completa (opciones, páginas, default content)
        if (is_admin()) {
            $this->runFullSync();
            return;
        }

        // En frontend (modo DEV): sincronizar únicamente páginas gestionadas (ligero)
        if (\Glory\Core\GloryFeatures::isActive('pageManager') !== false) {
            PageManager::procesarPaginasDefinidas();
            PageManager::reconciliarPaginasGestionadas();
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
                // Restaurar el HTML del código en todas las páginas gestionadas en modo editor
                $this->resyncAllManagedPagesHtml();
                // Restablecer metadatos SEO (título, descripción, canónica, FAQ, breadcrumb) a los valores por defecto
                $this->resyncAllManagedPagesSeoDefaults();
            }
            // Restablecer menús desde el código
            MenuManager::restablecerMenusDesdeCodigo();
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

    private function resyncAllManagedPagesHtml(): void
    {
        // Buscar todas las páginas gestionadas por Glory
        $pages = get_posts([
            'post_type'      => 'page',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_page_manager_managed',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        if (empty($pages)) {
            return;
        }

        foreach ($pages as $page) {
            $postId = (int) $page->ID;
            $modo = (string) get_post_meta($postId, '_glory_content_mode', true);
            $slug = (string) get_post_field('post_name', $postId);
            if ($slug === '') {
                continue;
            }
            $handler = PageManager::getHandlerPorSlug($slug);
            if (!$handler || !function_exists($handler)) {
                continue;
            }

            // Asegurar que quedan en modo 'editor' para mantener sincronización automática posterior
            if ($modo !== 'editor') {
                update_post_meta($postId, '_glory_content_mode', 'editor');
            }

            // Obtener HTML limpio desde el handler utilizando la misma rutina que PageManager
            $html = PageManager::renderHandlerParaCopiar($handler);
            if (!is_string($html) || $html === '') {
                continue;
            }

            // Actualizar contenido y hash normalizado para futuras comparaciones
            wp_update_post([
                'ID' => $postId,
                'post_content' => $html,
            ]);
            $normalized = preg_replace('/\s+/', ' ', trim((string) $html));
            update_post_meta($postId, '_glory_content_hash', hash('sha256', (string) $normalized));
        }
    }

    private function resyncAllManagedPagesSeoDefaults(): void
    {
        // Buscar todas las páginas gestionadas por Glory
        $pages = get_posts([
            'post_type'      => 'page',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'meta_key'       => '_page_manager_managed',
            'meta_value'     => true,
        ]);

        if (empty($pages)) {
            \Glory\Core\GloryLogger::warning('SyncManager: No se encontraron páginas gestionadas para restablecer SEO.');
            return;
        }

        foreach ($pages as $page) {
            $postId = (int) $page->ID;
            $slug = (string) get_post_field('post_name', $postId);
            if ($slug === '') { continue; }

            $def = PageManager::getDefaultSeoForSlug($slug);
            if (!is_array($def) || empty($def)) {
                // Si no hay definición por defecto, limpiar a valores vacíos seguros
                update_post_meta($postId, '_glory_seo_title', '');
                update_post_meta($postId, '_glory_seo_desc', '');
                update_post_meta($postId, '_glory_seo_canonical', '');
                update_post_meta($postId, '_glory_seo_faq', wp_json_encode([], JSON_UNESCAPED_UNICODE));
                update_post_meta($postId, '_glory_seo_breadcrumb', wp_json_encode([], JSON_UNESCAPED_UNICODE));
                \Glory\Core\GloryLogger::info('SyncManager: SEO default vacío para slug, metadatos limpiados.', [ 'slug' => $slug, 'postId' => $postId ]);
                continue;
            }

            $title = isset($def['title']) ? (string) $def['title'] : '';
            $desc = isset($def['desc']) ? (string) $def['desc'] : '';
            $canonical = isset($def['canonical']) ? (string) $def['canonical'] : '';
            if ($canonical !== '' && substr($canonical, -1) !== '/') { $canonical .= '/'; }
            $faq = isset($def['faq']) && is_array($def['faq']) ? $def['faq'] : [];
            $bc = isset($def['breadcrumb']) && is_array($def['breadcrumb']) ? $def['breadcrumb'] : [];

            // Borrar cualquier metadato previo duplicado y reinsertar limpio
            delete_post_meta($postId, '_glory_seo_title');
            delete_post_meta($postId, '_glory_seo_desc');
            delete_post_meta($postId, '_glory_seo_canonical');
            delete_post_meta($postId, '_glory_seo_faq');
            delete_post_meta($postId, '_glory_seo_breadcrumb');

            add_post_meta($postId, '_glory_seo_title', $title);
            add_post_meta($postId, '_glory_seo_desc', $desc);
            add_post_meta($postId, '_glory_seo_canonical', $canonical);
            add_post_meta($postId, '_glory_seo_faq', wp_json_encode($faq, JSON_UNESCAPED_UNICODE));
            add_post_meta($postId, '_glory_seo_breadcrumb', wp_json_encode($bc, JSON_UNESCAPED_UNICODE));
            \Glory\Core\GloryLogger::info('SyncManager: SEO restablecido a default.', [
                'slug' => $slug,
                'postId' => $postId,
                'faqCount' => is_array($faq) ? count($faq) : 0,
                'bcCount' => is_array($bc) ? count($bc) : 0,
            ]);
        }
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