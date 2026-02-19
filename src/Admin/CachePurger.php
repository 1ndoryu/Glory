<?php

namespace Glory\Admin;

use Glory\Manager\OpcionManager;

/*
 * Responsable de purgar todas las capas de caché del sitio.
 * Extraído de SyncManager para cumplir SRP.
 * Soporta caché de Glory, WordPress, transients, archivos,
 * y plugins de caché populares.
 */
final class CachePurger
{
    public static function purgeAll(): void
    {
        /* 1) Caché en memoria de Glory */
        OpcionManager::clearCache();

        /* 2) Caché de objeto de WordPress */
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        /* 3) Transients y site transients (prepare + esc_like obligatorio) */
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like('_transient_') . '%'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like('_transient_timeout_') . '%'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like('_site_transient_') . '%'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like('_site_transient_timeout_') . '%'));
        if (is_multisite() && !empty($wpdb->sitemeta)) {
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $wpdb->esc_like('_site_transient_') . '%'));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $wpdb->esc_like('_site_transient_timeout_') . '%'));
        }

        /* 4) Archivos de caché del framework (assets discovery) */
        if (defined('GLORY_FRAMEWORK_PATH')) {
            $cacheDir = rtrim(GLORY_FRAMEWORK_PATH, '/\\') . '/cache';
            if (is_dir($cacheDir) && is_readable($cacheDir)) {
                foreach (glob($cacheDir . '/*') ?: [] as $cacheFile) {
                    if (is_file($cacheFile) && is_writable($cacheFile)) {
                        try {
                            unlink($cacheFile);
                        } catch (\Throwable $e) {
                            /* Fallo al eliminar archivo de caché: no es crítico, logear si hay logger */
                            error_log('[CachePurger] No se pudo eliminar: ' . $cacheFile . ' — ' . $e->getMessage());
                        }
                    }
                }
            }
        }

        /* 5) Plugins de caché populares */
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        if (function_exists('litespeed_purge_all')) {
            litespeed_purge_all();
        } else {
            do_action('litespeed_purge_all');
        }
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        } else {
            do_action('wpsc_clear_cache');
        }
        if (function_exists('wpfc_clear_all_cache')) {
            wpfc_clear_all_cache(true);
        }
        do_action('wphb_clear_page_cache');
        do_action('wphb_clear_minify_cache');
        do_action('cache_enabler_clear_complete_cache');
        if (function_exists('sg_cachepress_purge_cache')) {
            sg_cachepress_purge_cache();
        }
        if (function_exists('sg_cachepress_purge_everything')) {
            sg_cachepress_purge_everything();
        }
        if (class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')) {
            \autoptimizeCache::clearall();
        }
        do_action('swift_performance_clear_all_cache');
        do_action('kinsta_cache_flush');
        do_action('wpe_purge_all_caches');
        do_action('rt_nginx_helper_purge_all');
        do_action('cloudflare_purge_all');
        do_action('pantheon_cache_clear');

        /* 6) Rewrite rules */
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }

        /* 7) Opcache */
        if (function_exists('opcache_reset')) {
            try {
                opcache_reset();
            } catch (\Throwable $e) {
                error_log('[CachePurger] opcache_reset falló: ' . $e->getMessage());
            }
        }
    }
}
