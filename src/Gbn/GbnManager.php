<?php

namespace Glory\Gbn;

use Glory\Core\GloryFeatures;
use Glory\Manager\AssetManager;
use Glory\Gbn\Config\RoleConfig;
use Glory\Gbn\Config\ContainerRegistry;
use Glory\Gbn\Config\ScriptManifest;

class GbnManager
{
    /** @var bool */
    protected static $booted = false;

    public static function bootstrap(): void
    {
        $isActive = method_exists(GloryFeatures::class, 'isActive') ? GloryFeatures::isActive('gbn', 'glory_gbn_activado') : true;
        if (!$isActive) {
            return;
        }
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        // Cargar componentes dinámicos
        if (class_exists(\Glory\Gbn\Components\ComponentLoader::class)) {
            \Glory\Gbn\Components\ComponentLoader::load();
        }

        // Registrar Panel de Control (Diagnóstico)
        if (class_exists(\Glory\Gbn\Diagnostics\ControlPanelManager::class)) {
            \Glory\Gbn\Diagnostics\ControlPanelManager::register();
        }

        // Fase 15: Registrar páginas de admin para Header/Footer
        if (class_exists(\Glory\Gbn\Pages\HeaderEditorPage::class)) {
            \Glory\Gbn\Pages\HeaderEditorPage::register();
        }
        if (class_exists(\Glory\Gbn\Pages\FooterEditorPage::class)) {
            \Glory\Gbn\Pages\FooterEditorPage::register();
        }

        // Registrar endpoints AJAX de GBN en init
        add_action('init', [GbnManager::class, 'registerAjax']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);

        // Filter frontend content to remove internal GBN attributes
        // Must run AFTER PostRender processing (priority 20 vs 15)
        add_filter('the_content', [\Glory\Gbn\Components\PostRender\PostRenderProcessor::class, 'processContent'], 15);
        add_filter('the_content', [self::class, 'filterFrontendContent'], 20);
        
        // Fase 13.5: Invalidar cache de PostRender cuando cambian posts
        add_action('save_post', [\Glory\Gbn\Services\PostRenderService::class, 'clearCacheOnPostChange'], 10, 1);
        add_action('delete_post', [\Glory\Gbn\Services\PostRenderService::class, 'clearCacheOnPostChange'], 10, 1);
        add_action('transition_post_status', function($new, $old, $post) {
            if ($new !== $old) {
                \Glory\Gbn\Services\PostRenderService::clearCacheOnPostChange($post->ID);
            }
        }, 10, 3);
    }

    public static function registerAjax(): void
    {
        // Usa el registrador centralizado de GBN para ajax
        if (class_exists(\Glory\Gbn\GbnAjaxHandler::class)) {
            \Glory\Gbn\GbnAjaxHandler::register();
        }

        // Fase 15: Registrar handlers AJAX para templates Header/Footer
        if (class_exists(\Glory\Gbn\Handlers\TemplateAjaxHandler::class)) {
            \Glory\Gbn\Handlers\TemplateAjaxHandler::register();
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
        if (isset($_GET['fb-edit'])) {
            return true;
        }
        if (function_exists('fusion_is_builder_frame') && (fusion_is_builder_frame() || function_exists('fusion_is_preview_frame') && fusion_is_preview_frame())) {
            return true;
        }
        return false;
    }

    public static function enqueueAssets(): void
    {
        if (self::isBuilderActive()) {
            return;
        }
        if (is_page('gbn-control-panel')) {
            return;
        }

        // Remove early return for non-editors to allow frontend assets
        // if (!current_user_can('edit_posts')) { return; }

        $baseDir = get_template_directory() . '/Glory/src/Gbn/assets';
        $baseUrl = get_template_directory_uri() . '/Glory/src/Gbn/assets';

        // 1. Frontend CSS (Always loaded)
        $frontendCss = [
            'variables'        => 'variables.css',
            'layout'           => 'layout.css',
            'components'       => 'components.css',
            'formComponents'   => 'formComponents.css', // Fase 14: Form Components
            'gbn'              => 'gbn.css',
            'theme-styles'     => 'theme-styles.css'
        ];

        // 2. Builder CSS (Only for editors)
        $builderCss = [
            'forms'       => 'forms.css',
            'interactive' => 'interactive.css',
            'modals'      => 'modals.css'
        ];

        foreach ($frontendCss as $handle => $file) {
            $path = $baseDir . '/css/' . $file;
            $ver  = self::resolveVersion($path);
            wp_enqueue_style('glory-gbn-' . $handle, $baseUrl . '/css/' . $file, [], $ver);
        }

        if (current_user_can('edit_posts')) {
            wp_enqueue_media(); // Habilitar galería de medios WP
            foreach ($builderCss as $handle => $file) {
                $path = $baseDir . '/css/' . $file;
                $ver  = self::resolveVersion($path);
                wp_enqueue_style('glory-gbn-' . $handle, $baseUrl . '/css/' . $file, [], $ver);
            }
        }

        // Inyectar CSS Responsive generado por GBN
        $pageId = get_queried_object_id();
        if ($pageId) {
            $responsiveCss = get_post_meta($pageId, 'gbn_responsive_css', true);
            if ($responsiveCss) {
                wp_add_inline_style('glory-gbn-gbn', $responsiveCss);
            }
        }

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

        // 3. Frontend Scripts (Always loaded) - Desde ScriptManifest (refactorizado Dic 2025)
        $frontendScripts = ScriptManifest::getFrontendScripts();

        // 4. Builder Scripts (Only for editors) - Desde ScriptManifest (refactorizado Dic 2025)
        $builderScripts = ScriptManifest::getBuilderScripts();


        foreach ($frontendScripts as $handle => $data) {
            $filePath = $baseDir . $data['file'];
            $ver = isset($data['ver']) ? $data['ver'] : self::resolveVersion($filePath);
            wp_enqueue_script(
                $handle,
                $baseUrl . $data['file'],
                $data['deps'],
                $ver,
                true
            );
        }

        if (current_user_can('edit_posts')) {
            foreach ($builderScripts as $handle => $data) {
                $filePath = $baseDir . $data['file'];
                $ver = isset($data['ver']) ? $data['ver'] : self::resolveVersion($filePath);
                wp_enqueue_script(
                    $handle,
                    $baseUrl . $data['file'],
                    $data['deps'],
                    $ver,
                    true
                );
            }
        }

        $pageId = get_queried_object_id();
        $presets = [
            'config' => [],
            'styles' => []
        ];
        if ($pageId) {
            $savedCfg = get_post_meta($pageId, 'gbn_config', true);
            $savedSty = get_post_meta($pageId, 'gbn_styles', true);
            if (is_array($savedCfg)) {
                $presets['config'] = $savedCfg;
            }
            if (is_array($savedSty)) {
                $presets['styles'] = $savedSty;
            }
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
            'roleSchemas' => ContainerRegistry::rolePayload(), // Schemas completos para automatización
            'devMode' => self::shouldBustVersion(),
            'presets' => $presets,
            'contentMode' => method_exists(\Glory\Manager\PageManager::class, 'getModoContenidoParaPagina') && $pageId
                ? \Glory\Manager\PageManager::getModoContenidoParaPagina($pageId)
                : 'code',
            'themeSettings' => get_option('gbn_theme_settings', []),
            'pageSettings' => $pageId ? get_post_meta($pageId, 'gbn_page_settings', true) : [],
        ];
        // Asegurar que la config esté disponible antes de cualquier script consumidor
        wp_localize_script('glory-gbn-core', 'gloryGbnCfg', $localizedData);
        // Proveer ajax_params para gloryAjax si no lo define otro módulo
        // Localizar a glory-ajax para que esté disponible cuando el script se ejecute
        wp_localize_script('glory-ajax', 'ajax_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
        // Mantener compatibilidad: también localizamos en el script final
        wp_localize_script('glory-gbn', 'gloryGbnCfg', $localizedData);
    }

    // injectEditButtons removed as it is no longer needed

    /**
     * Filters the frontend content to remove internal GBN attributes
     * that shouldn't be visible to non-admin users.
     * 
     * NOTA: Los atributos glory* (gloryDiv, gloryPostField, etc.) se PRESERVAN
     * porque son necesarios para los selectores CSS en theme-styles.css.
     * Solo se limpian atributos internos de configuración.
     *
     * @param string $content
     * @return string
     */
    public static function filterFrontendContent($content)
    {
        // Don't filter for editors or builder frame
        if (current_user_can('edit_posts') || isset($_GET['fb-edit']) || self::isBuilderActive()) {
            return $content;
        }

        // Solo limpiar atributos INTERNOS de configuración
        // Los atributos glory* se PRESERVAN para que funcionen los selectores CSS
        // Atributos a limpiar:
        // - data-gbn-schema: Contiene schema JSON del componente (innecesario en frontend)
        // - data-gbn-config: Contiene configuración JSON (innecesario en frontend)
        // Atributos que se PRESERVAN:
        // - glory*: Necesarios para selectores CSS (:where([gloryPostField="title"]))
        // - data-gbn-id: Útil para anchors y debugging
        // - data-gbn-role: Útil para selectores CSS alternativos
        // - data-gbn-post-*: Útiles para JS del frontend (filtros, paginación)
        $patterns = [
            '/ (data-gbn-schema|data-gbn-config)(=\"[^\"]*\")?/i'
        ];

        return preg_replace($patterns, '', $content);
    }
}
