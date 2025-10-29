<?php

namespace Glory\Gbn;

use Glory\Core\GloryFeatures;
use Glory\Manager\AssetManager;
use Glory\Gbn\Config\RoleConfig;
use Glory\Gbn\Config\ContainerRegistry;

class GbnManager
{
    /** @var bool */
    protected static $booted = false;

    public static function bootstrap(): void
    {
        $isActive = method_exists(GloryFeatures::class, 'isActive') ? GloryFeatures::isActive('gbn', 'glory_gbn_activado') : true;
        if (!$isActive) { return; }
        if (self::$booted) { return; }
        self::$booted = true;
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_action('wp_footer', [self::class, 'injectEditButtons'], 5);
    }

    protected static function shouldBustVersion(): bool
    {
        return AssetManager::isGlobalDevMode()
            || (defined('LOCAL') && LOCAL)
            || (defined('WP_DEBUG') && WP_DEBUG);
    }

    protected static function resolveVersion(string $filePath): string
    {
        if (self::shouldBustVersion()) {
            $mtime = @filemtime($filePath);
            if ($mtime) {
                return (string) $mtime;
            }
        }
        $theme = wp_get_theme();
        $themeVersion = $theme->get('Version');
        return $themeVersion ?: '1.0.0';
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
        $verCss  = self::resolveVersion($cssPath);

        wp_enqueue_style('glory-gbn', $baseUrl . '/css/gbn.css', [], $verCss);

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
            $ver = self::resolveVersion($filePath);
            wp_enqueue_script(
                $handle,
                $baseUrl . $data['file'],
                $data['deps'],
                $ver,
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
            'roles' => RoleConfig::all(),
            'containers' => ContainerRegistry::all(),
            'devMode' => self::shouldBustVersion(),
        ]);
    }

    public static function injectEditButtons(): void
    {
        if (self::isBuilderActive()) { return; }
        if (!current_user_can('edit_posts')) { return; }
        echo '<div id="glory-gbn-root" class="gbn-toggle-wrapper" data-enabled="0">'
            . '<button type="button" id="gbn-toggle" class="gbn-toggle-btn" data-gbn-state="off" aria-pressed="false">Open GBN</button>'
            . '<div class="gbn-toggle-secondary">'
            . '  <button type="button" class="gbn-secondary-btn" data-gbn-action="theme" disabled aria-disabled="true">Theme settings</button>'
            . '  <button type="button" class="gbn-secondary-btn" data-gbn-action="page" disabled aria-disabled="true">Page settings</button>'
            . '  <button type="button" class="gbn-secondary-btn" data-gbn-action="restore" disabled aria-disabled="true">Restore defaults</button>'
            . '</div>'
            . '</div>';
    }
}

