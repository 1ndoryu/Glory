<?php

namespace Glory\Gbn\Diagnostics;

use Glory\Manager\PageManager;

class ControlPanelManager
{
    public static function register(): void
    {
        // Definir la página gestionada por Glory
        if (class_exists(PageManager::class)) {
            PageManager::define(
                'gbn-control-panel',
                self::class . '::renderPage',
                null,
                ['administrator']
            );
        }

        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_action('wp_enqueue_scripts', [self::class, 'dequeueUnwantedAssets'], 9999);
    }

    public static function enqueueAssets(): void
    {
        if (!is_page('gbn-control-panel')) {
            return;
        }

        $themeVersion = wp_get_theme()->get('Version');
        $uri = get_template_directory_uri() . '/Glory/src/Gbn/assets/control-panel';

        wp_enqueue_style('gbn-control-panel', $uri . '/style.css', [], $themeVersion);
        wp_enqueue_script('gbn-control-panel', $uri . '/app.js', ['jquery'], $themeVersion, true);

        wp_localize_script('gbn-control-panel', 'gbnControlData', [
            'nonce' => wp_create_nonce('glory_gbn_nonce'),
            'apiUrl' => admin_url('admin-ajax.php'),
        ]);
    }

    public static function dequeueUnwantedAssets(): void
    {
        if (!is_page('gbn-control-panel')) {
            return;
        }

        // 1. Remove GBN UI elements (Dock, Inspector, Overlay)
        wp_dequeue_script('glory-gbn-ui-dock');
        wp_dequeue_script('glory-gbn-ui-inspector');
        wp_dequeue_script('glory-gbn-debug-overlay');
        // Also remove the main GBN script if it initializes UI
        // wp_dequeue_script('glory-gbn'); 

        // 2. Remove App Assets (Styles & Scripts from /App/assets/)
        // Handled natively by AssetManager 'exclude_on' configuration in App/Config/assets.php
    }

    public static function renderPage(): void
    {
        // Renderizar documento HTML independiente para evitar Header/Footer del tema
?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>

        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>GBN Control Center</title>
            <?php wp_head(); ?>
            <style>
                /* Force reset layout */
                body {
                    margin: 0;
                    padding: 0;
                    background: #0d1117;
                    overflow: hidden;
                }

                #wpadminbar {
                    display: none !important;
                }

                html {
                    margin-top: 0 !important;
                }
            </style>
        </head>

        <body>
            <div id="gbn-control-app">
                <div class="gbn-cp-loading">
                    <h1>GBN Control Center</h1>
                    <p>Cargando sistema...</p>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>

        </html>
<?php
        exit; // Detener ejecución para evitar que PageManager cargue TemplateGlory.php
    }
}
