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
        $verCss  = defined('WP_DEBUG') && WP_DEBUG ? (string) @filemtime($cssPath) : '1.0';

        wp_enqueue_style('glory-gbn', $baseUrl . '/css/gbn.css', [], $verCss ?: '1.0');

        if (!wp_script_is('glory-ajax', 'enqueued')) {
            if (!wp_script_is('glory-ajax', 'registered')) {
                $ajaxFile = get_template_directory() . '/Glory/assets/js/genericAjax/gloryAjax.js';
                $verAjax  = defined('WP_DEBUG') && WP_DEBUG ? (string) @filemtime($ajaxFile) : '1.0';
                wp_register_script(
                    'glory-ajax',
                    get_template_directory_uri() . '/Glory/assets/js/genericAjax/gloryAjax.js',
                    ['jquery'],
                    $verAjax ?: '1.0',
                    true
                );
            }
            wp_enqueue_script('glory-ajax');
        }

        $scripts = [
            'glory-gbn-core' => [
                'file' => '/js/core/utils.js',
                'deps' => ['jquery', 'glory-ajax'],
            ],
            'glory-gbn-state' => [
                'file' => '/js/core/state.js',
                'deps' => ['glory-gbn-core'],
            ],
            'glory-gbn-style' => [
                'file' => '/js/render/styleManager.js',
                'deps' => ['glory-gbn-state'],
            ],
            'glory-gbn-services' => [
                'file' => '/js/services/content.js',
                'deps' => ['glory-gbn-style'],
            ],
            'glory-gbn-ui' => [
                'file' => '/js/ui/overlay.js',
                'deps' => ['glory-gbn-services'],
            ],
            'glory-gbn' => [
                'file' => '/js/gbn.js',
                'deps' => ['glory-gbn-ui'],
            ],
        ];

        foreach ($scripts as $handle => $data) {
            $filePath = $baseDir . $data['file'];
            $ver = defined('WP_DEBUG') && WP_DEBUG ? (string) @filemtime($filePath) : '1.0';
            wp_enqueue_script(
                $handle,
                $baseUrl . $data['file'],
                $data['deps'],
                $ver ?: '1.0',
                true
            );
        }

        wp_localize_script('glory-gbn', 'gloryGbnCfg', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('glory_gbn_nonce'),
            'siteTitle' => get_bloginfo('name'),
            'enabled' => true,
            'initialActive' => false,
            'pageId' => get_queried_object_id(),
            'userId' => get_current_user_id(),
            'isEditor' => current_user_can('edit_posts'),
        ]);
    }

    public static function injectEditButtons(): void
    {
        if (self::isBuilderActive()) { return; }
        if (!current_user_can('edit_posts')) { return; }
        echo '<div id="glory-gbn-root" class="gbn-toggle-wrapper" data-enabled="0">'
            . '<button type="button" id="gbn-toggle" class="gbn-toggle-btn" data-gbn-state="off" aria-pressed="false">Open GBN</button>'
            . '</div>';
    }
}

\add_action('init', [\Glory\Gbn\GbnManager::class, 'bootstrap']);




