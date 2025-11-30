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
        // Registrar endpoints AJAX de GBN en init
        add_action('init', [GbnManager::class, 'registerAjax']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_action('wp_footer', [self::class, 'injectEditButtons'], 5);
    }

    public static function registerAjax(): void
    {
        // Usa el registrador centralizado de GBN para ajax
        if (class_exists(\Glory\Gbn\GbnAjaxHandler::class)) {
            \Glory\Gbn\GbnAjaxHandler::register();
        }
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
                    false
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
            'glory-gbn-persistence' => [
                'file' => '/js/services/persistence.js',
                'deps' => ['glory-gbn-services'],
            ],
            'glory-gbn-ui-panel-fields' => [
                'file' => '/js/ui/panel-fields.js',
                'deps' => ['glory-gbn-persistence'],
            ],
            'glory-gbn-ui-panel' => [
                'file' => '/js/ui/panel.js',
                'deps' => ['glory-gbn-ui-panel-fields'],
            ],
            'glory-gbn-ui-dragdrop' => [
                'file' => '/js/ui/drag-drop.js',
                'deps' => ['glory-gbn-ui-panel'],
            ],
            'glory-gbn-ui-library' => [
                'file' => '/js/ui/library.js',
                'deps' => ['glory-gbn-ui-panel'],
            ],
            'glory-gbn-ui-dock' => [
                'file' => '/js/ui/dock.js',
                'deps' => ['glory-gbn-ui-panel'],
            ],
            'glory-gbn-ui-inspector' => [
                'file' => '/js/ui/inspector.js',
                'deps' => ['glory-gbn-ui-dragdrop', 'glory-gbn-ui-library', 'glory-gbn-ui-dock'],
            ],
            'glory-gbn' => [
                'file' => '/js/gbn.js',
                'deps' => ['glory-gbn-ui-inspector'],
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

        $pageId = get_queried_object_id();
        $presets = [
            'config' => [],
            'styles' => []
        ];
        if ($pageId) {
            $savedCfg = get_post_meta($pageId, 'gbn_config', true);
            $savedSty = get_post_meta($pageId, 'gbn_styles', true);
            if (is_array($savedCfg)) { $presets['config'] = $savedCfg; }
            if (is_array($savedSty)) { $presets['styles'] = $savedSty; }
        }

        $localizedData = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('glory_gbn_nonce'),
            'siteTitle' => get_bloginfo('name'),
            'enabled' => true,
            'initialActive' => false,
            'pageId' => $pageId,
            'userId' => get_current_user_id(),
            'isEditor' => current_user_can('edit_posts'),
            'roles' => RoleConfig::all(),
            'containers' => ContainerRegistry::all(),
            'devMode' => self::shouldBustVersion(),
            'presets' => $presets,
            'contentMode' => method_exists(\Glory\Manager\PageManager::class, 'getModoContenidoParaPagina') && $pageId 
                ? \Glory\Manager\PageManager::getModoContenidoParaPagina($pageId) 
                : 'code',
        ];
        // Asegurar que la config esté disponible antes de cualquier script consumidor
        wp_localize_script('glory-gbn-core', 'gloryGbnCfg', $localizedData);
        // Proveer ajax_params para gloryAjax si no lo define otro módulo
        wp_localize_script('glory-gbn-core', 'ajax_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
        // Mantener compatibilidad: también localizamos en el script final
        wp_localize_script('glory-gbn', 'gloryGbnCfg', $localizedData);
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

