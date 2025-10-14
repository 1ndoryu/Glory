<?php

namespace Glory\Gbn;

use Glory\Core\GloryFeatures;

class GbnManager
{
    public static function bootstrap(): void
    {
        $isActive = method_exists(GloryFeatures::class, 'isActive') ? GloryFeatures::isActive('gbn', 'glory_gbn_activado') : true;
        if (!$isActive) { return; }
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_action('wp_footer', [self::class, 'injectEditButtons'], 20);
    }

    public static function isBuilderActive(): bool
    {
        if (isset($_GET['fb-edit'])) { return true; }
        if (function_exists('fusion_is_builder_frame') && (fusion_is_builder_frame() || function_exists('fusion_is_preview_frame') && fusion_is_preview_frame())) {
            return true;
        }
        return false;
    }

    public static function enqueueAssets(): void
    {
        if (self::isBuilderActive()) { return; }
        if (!current_user_can('edit_posts')) { return; }

        $baseDir = get_template_directory() . '/Glory/src/Gbn/assets';
        $baseUrl = get_template_directory_uri() . '/Glory/src/Gbn/assets';
        $cssPath = $baseDir . '/css/gbn.css';
        $jsPath  = $baseDir . '/js/gbn.js';
        $verCss  = defined('WP_DEBUG') && WP_DEBUG ? (string) @filemtime($cssPath) : '1.0';
        $verJs   = defined('WP_DEBUG') && WP_DEBUG ? (string) @filemtime($jsPath)  : '1.0';

        wp_enqueue_style('glory-gbn', $baseUrl . '/css/gbn.css', [], $verCss ?: '1.0');
        wp_enqueue_script('glory-gbn', $baseUrl . '/js/gbn.js', [ 'jquery' ], $verJs ?: '1.0', true);
        wp_localize_script('glory-gbn', 'gloryGbnCfg', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('glory_gbn_nonce'),
            'siteTitle' => get_bloginfo('name'),
        ]);
    }

    public static function injectEditButtons(): void
    {
        if (self::isBuilderActive()) { return; }
        if (!current_user_can('edit_posts')) { return; }
        echo '<div id="glory-gbn-root" data-enabled="1" style="display:none"></div>';
    }
}

\add_action('init', [\Glory\Gbn\GbnManager::class, 'bootstrap']);




