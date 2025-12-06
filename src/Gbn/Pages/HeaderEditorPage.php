<?php

namespace Glory\Gbn\Pages;

use Glory\Gbn\Services\TemplateService;
use Glory\Gbn\Config\RoleConfig;
use Glory\Gbn\Config\ContainerRegistry;

class HeaderEditorPage
{
    public static function register()
    {
        add_submenu_page(
            'themes.php',
            'Editar Header',
            'Header GBN',
            'edit_theme_options',
            'gbn-edit-header',
            [self::class, 'render']
        );
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
    }

    public static function enqueueAssets($hook)
    {
        if ($hook !== 'appearance_page_gbn-edit-header') {
            return;
        }

        $baseDir = get_template_directory() . '/Glory/src/Gbn/assets';
        $baseUrl = get_template_directory_uri() . '/Glory/src/Gbn/assets';
        
        // CSS
        $cssFiles = [
            'variables' => 'variables.css',
            'layout' => 'layout.css',
            'components' => 'components.css',
            'formComponents' => 'formComponents.css',
            'gbn' => 'gbn.css',
            'theme-styles' => 'theme-styles.css',
            'forms' => 'forms.css',
            'interactive' => 'interactive.css',
            'modals' => 'modals.css'
        ];

        foreach ($cssFiles as $handle => $file) {
            wp_enqueue_style('glory-gbn-' . $handle, $baseUrl . '/css/' . $file, [], '1.0.0');
        }

        // Scripts - Copiado de GbnManager para asegurar compatibilidad
            // 3. Frontend Scripts (Always loaded)
            $frontendScripts = [
                'glory-gbn-core' => [
                    'file' => '/js/core/utils.js',
                    'deps' => ['jquery'], // glory-ajax manually handled below
                    'ver'  => time(), 
                ],
                'glory-gbn-validator' => [
                    'file' => '/js/core/validator.js',
                    'deps' => ['glory-gbn-core'],
                ],
                'glory-gbn-logger' => [
                    'file' => '/js/services/logger.js',
                    'deps' => ['glory-gbn-core'],
                ],
                'glory-gbn-store' => [
                    'file' => '/js/core/store.js',
                    'deps' => ['glory-gbn-core', 'glory-gbn-validator'],
                ],
                'glory-gbn-state' => [
                    'file' => '/js/core/state.js',
                    'deps' => ['glory-gbn-store'],
                ],
                'glory-gbn-css-sync' => [
                    'file' => '/js/services/css-sync.js',
                    'deps' => ['glory-gbn-core'],
                ],
                'glory-gbn-theme-applicator' => [
                    'file' => '/js/ui/theme/applicator.js',
                    'deps' => ['glory-gbn-state', 'glory-gbn-css-sync'],
                ],
                'glory-gbn-style' => [
                    'file' => '/js/render/styleManager.js',
                    'deps' => ['glory-gbn-state', 'glory-gbn-theme-applicator'],
                ],
                'glory-gbn-content-roles' => [
                    'file' => '/js/services/content/roles.js',
                    'deps' => ['glory-gbn-style'],
                ],
                'glory-gbn-content-config' => [
                    'file' => '/js/services/content/config.js',
                    'deps' => ['glory-gbn-content-roles'],
                ],
                'glory-gbn-content-dom' => [
                    'file' => '/js/services/content/dom.js',
                    'deps' => ['glory-gbn-content-config'],
                ],
                'glory-gbn-content-builder' => [
                    'file' => '/js/services/content/builder.js',
                    'deps' => ['glory-gbn-content-dom'],
                ],
                'glory-gbn-content-scanner' => [
                    'file' => '/js/services/content/scanner.js',
                    'deps' => ['glory-gbn-content-builder'],
                ],
                'glory-gbn-content-hydrator' => [
                    'file' => '/js/services/content/hydrator.js',
                    'deps' => ['glory-gbn-content-scanner'],
                ],
                'glory-gbn-services' => [
                    'file' => '/js/services/content.js',
                    'deps' => ['glory-gbn-content-hydrator'],
                ],
                'glory-gbn-front' => [
                    'file' => '/js/gbn-front.js',
                    'deps' => ['glory-gbn-services'],
                ],
                'glory-gbn-post-render-frontend' => [
                    'file' => '/js/frontend/post-render-frontend.js',
                    'deps' => [],
                ],
                'glory-gbn-form-submit' => [
                    'file' => '/js/frontend/form-submit.js',
                    'deps' => [],
                ],
            ];
    
            // 4. Builder Scripts (Only for editors)
            $builderScripts = [
                'glory-gbn-persistence' => [
                    'file' => '/js/services/persistence.js',
                    'deps' => ['glory-gbn-services'],
                ],
                'glory-gbn-responsive' => [
                    'file' => '/js/services/responsive.js',
                    'deps' => ['glory-gbn-services'],
                ],
                'glory-gbn-style-generator' => [
                    'file' => '/js/services/style-generator.js',
                    'deps' => ['glory-gbn-services'],
                ],
                // Fase 10: Servicio de estados hover/focus
                'glory-gbn-state-styles' => [
                    'file' => '/js/services/state-styles.js',
                    'deps' => ['glory-gbn-style-generator'],
                ],
                'glory-gbn-diagnostics' => [
                    'file' => '/js/services/diagnostics.js',
                    'deps' => ['glory-gbn-services'],
                ],
                // Panel Fields - Módulos refactorizados
                'glory-gbn-ui-fields-registry' => [
                    'file' => '/js/ui/panel-fields/registry.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-utils' => [
                    'file' => '/js/ui/panel-fields/utils.js',
                    'deps' => ['glory-gbn-persistence'],
                ],
                'glory-gbn-ui-fields-sync' => [
                    'file' => '/js/ui/panel-fields/sync.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-header' => [
                    'file' => '/js/ui/panel-fields/header.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-spacing' => [
                    'file' => '/js/ui/panel-fields/spacing.js',
                    'deps' => ['glory-gbn-ui-fields-sync', 'glory-gbn-ui-fields-registry'],
                ],
                'glory-gbn-ui-fields-slider' => [
                    'file' => '/js/ui/panel-fields/slider.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-select' => [
                    'file' => '/js/ui/panel-fields/select.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-toggle' => [
                    'file' => '/js/ui/panel-fields/toggle.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-text' => [
                    'file' => '/js/ui/panel-fields/text.js',
                    'deps' => ['glory-gbn-ui-fields-utils', 'glory-gbn-ui-fields-registry'],
                ],
                'glory-gbn-ui-fields-color-utils' => [
                    'file' => '/js/ui/panel-fields/color-utils.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-color' => [
                    'file' => '/js/ui/panel-fields/color.js',
                    'deps' => ['glory-gbn-ui-fields-sync', 'glory-gbn-ui-fields-registry', 'glory-gbn-ui-fields-color-utils'],
                ],
                'glory-gbn-ui-fields-typography' => [
                    'file' => '/js/ui/panel-fields/typography.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-icon-group' => [
                    'file' => '/js/ui/panel-fields/icon-group.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-fraction' => [
                    'file' => '/js/ui/panel-fields/fraction.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-rich-text' => [
                    'file' => '/js/ui/panel-fields/rich-text.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-image' => [
                    'file' => '/js/ui/panel-fields/image.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-dimensions' => [
                    'file' => '/js/ui/panel-fields/dimensions.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-fields-index' => [
                    'file' => '/js/ui/panel-fields/index.js',
                    'deps' => [
                        'glory-gbn-ui-fields-registry',
                        'glory-gbn-ui-fields-header',
                        'glory-gbn-ui-fields-spacing',
                        'glory-gbn-ui-fields-slider',
                        'glory-gbn-ui-fields-select',
                        'glory-gbn-ui-fields-toggle',
                        'glory-gbn-ui-fields-text',
                        'glory-gbn-ui-fields-color',
                        'glory-gbn-ui-fields-typography',
                        'glory-gbn-ui-fields-icon-group',
                        'glory-gbn-ui-fields-fraction',
                        'glory-gbn-ui-fields-rich-text',
                        'glory-gbn-ui-fields-image',
                        'glory-gbn-ui-fields-dimensions',
                    ],
                ],
                // Wrapper de compatibilidad
                'glory-gbn-ui-panel-fields' => [
                    'file' => '/js/ui/panel-fields.js',
                    'deps' => ['glory-gbn-ui-fields-index'],
                ],
                // Renderers - Módulos refactorizados
                // Fase 11: Traits centralizados para eliminar código duplicado en renderers
                'glory-gbn-ui-renderers-traits' => [
                    'file' => '/js/ui/renderers/renderer-traits.js',
                    'deps' => ['glory-gbn-ui-fields-utils'],
                ],
                'glory-gbn-ui-renderers-shared' => [
                    'file' => '/js/ui/renderers/shared.js',
                    'deps' => ['glory-gbn-ui-fields-utils', 'glory-gbn-ui-renderers-traits'],
                ],
                'glory-gbn-ui-renderers-style-composer' => [
                    'file' => '/js/ui/renderers/style-composer.js',
                    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-layout-flex', 'glory-gbn-ui-renderers-layout-grid'],
                ],
                'glory-gbn-ui-renderers-layout-flex' => [
                    'file' => '/js/ui/renderers/layout-flex.js',
                    'deps' => ['glory-gbn-ui-renderers-shared'],
                ],
                'glory-gbn-ui-renderers-layout-grid' => [
                    'file' => '/js/ui/renderers/layout-grid.js',
                    'deps' => ['glory-gbn-ui-renderers-shared'],
                ],
                'glory-gbn-ui-renderers-principal' => [
                    'file' => '/js/ui/renderers/principal.js',
                    'deps' => ['glory-gbn-ui-renderers-style-composer'],
                ],
                'glory-gbn-ui-renderers-secundario' => [
                    'file' => '/js/ui/renderers/secundario.js',
                    'deps' => ['glory-gbn-ui-renderers-style-composer'],
                ],
                'glory-gbn-ui-renderers-text' => [
                    'file' => '/js/ui/renderers/text.js',
                    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
                ],
                'glory-gbn-ui-renderers-button' => [
                    'file' => '/js/ui/renderers/button.js',
                    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
                ],
                'glory-gbn-ui-renderers-image' => [
                    'file' => '/js/ui/renderers/image.js',
                    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
                ],
                'glory-gbn-ui-renderers-page-settings' => [
                    'file' => '/js/ui/renderers/page-settings.js',
                    'deps' => ['glory-gbn-ui-renderers-shared'],
                ],
                'glory-gbn-ui-renderers-theme-settings' => [
                    'file' => '/js/ui/renderers/theme-settings.js',
                    'deps' => ['glory-gbn-ui-renderers-shared'],
                ],
                // Fase 13: PostRender
                'glory-gbn-ui-renderers-post-render' => [
                    'file' => '/js/ui/renderers/post-render.js',
                    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
                ],
                'glory-gbn-ui-renderers-post-item' => [
                    'file' => '/js/ui/renderers/post-item.js',
                    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
                ],
                'glory-gbn-ui-renderers-post-field' => [
                    'file' => '/js/ui/renderers/post-field.js',
                    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
                ],
                // Fase 14: Form Components
                'glory-gbn-ui-renderers-form' => [
                    'file' => '/js/ui/renderers/form.js',
                    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
                ],
                'glory-gbn-ui-renderers-input' => [
                    'file' => '/js/ui/renderers/input.js',
                    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
                ],
                'glory-gbn-ui-renderers-textarea' => [
                    'file' => '/js/ui/renderers/textarea.js',
                    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
                ],
                'glory-gbn-ui-renderers-select' => [
                    'file' => '/js/ui/renderers/select.js',
                    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
                ],
                'glory-gbn-ui-renderers-submit' => [
                    'file' => '/js/ui/renderers/submit.js',
                    'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
                ],
    
                'glory-gbn-ui-panel-render' => [
                    'file' => '/js/ui/panel-render.js',
                    'deps' => [
                        'glory-gbn-ui-panel-fields',
                        'glory-gbn-ui-renderers-principal',
                        'glory-gbn-ui-renderers-secundario',
                        'glory-gbn-ui-renderers-text',
                        'glory-gbn-ui-renderers-button',
                        'glory-gbn-ui-renderers-image',
                        'glory-gbn-ui-renderers-page-settings',
                        'glory-gbn-ui-renderers-theme-settings',
                        'glory-gbn-ui-renderers-post-render', // Fase 13
                        'glory-gbn-ui-renderers-post-item',
                        'glory-gbn-ui-renderers-post-field',
                        'glory-gbn-ui-renderers-form', // Fase 14
                        'glory-gbn-ui-renderers-input',
                        'glory-gbn-ui-renderers-textarea',
                        'glory-gbn-ui-renderers-select',
                        'glory-gbn-ui-renderers-submit',
                    ],
                ],
                'glory-gbn-theme-applicator' => [
                    'file' => '/js/ui/theme/applicator.js',
                    'deps' => ['glory-gbn-state', 'glory-gbn-css-sync'],
                ],
                'glory-gbn-ui-theme-render' => [
                    'file' => '/js/ui/theme/render.js',
                    'deps' => ['glory-gbn-ui-panel-fields', 'glory-gbn-theme-applicator'],
                ],
                'glory-gbn-ui-theme-index' => [
                    'file' => '/js/ui/theme/index.js',
                    'deps' => ['glory-gbn-ui-theme-render'],
                ],
                'glory-gbn-ui-panel' => [
                    'file' => '/js/ui/panel-core.js',
                    'deps' => ['glory-gbn-ui-panel-render', 'glory-gbn-ui-theme-index'],
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
                'glory-gbn-debug-overlay' => [
                    'file' => '/js/ui/debug/overlay.js',
                    'deps' => ['glory-gbn-ui-panel'],
                ],
                'glory-gbn-ui-context-menu' => [
                    'file' => '/js/ui/context-menu.js',
                    'deps' => ['glory-gbn-ui-panel'],
                ],
                'glory-gbn-store-subscriber' => [
                    'file' => '/js/ui/store-subscriber.js',
                    'deps' => ['glory-gbn-store', 'glory-gbn-ui-panel-render'],
                ],
                'glory-gbn' => [
                    'file' => '/js/gbn.js',
                    'deps' => ['glory-gbn-ui-inspector', 'glory-gbn-debug-overlay', 'glory-gbn-store-subscriber', 'glory-gbn-logger', 'glory-gbn-ui-context-menu'],
                ],
            ];

        // Register AJAX shim first
        if (!wp_script_is('glory-ajax', 'registered')) {
            wp_register_script(
                'glory-ajax',
                get_template_directory_uri() . '/Glory/assets/js/genericAjax/gloryAjax.js',
                ['jquery'],
                '1.0',
                false
            );
        }
        wp_localize_script('glory-ajax', 'ajax_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
        wp_enqueue_script('glory-ajax');

        // Enqueue Frontend Scripts
        foreach ($frontendScripts as $handle => $data) {
            $ver = isset($data['ver']) ? $data['ver'] : '1.0';
            wp_enqueue_script($handle, $baseUrl . $data['file'], $data['deps'], $ver, true);
        }

        // Enqueue Builder Scripts
        foreach ($builderScripts as $handle => $data) {
            $ver = isset($data['ver']) ? $data['ver'] : '1.0';
            wp_enqueue_script($handle, $baseUrl . $data['file'], $data['deps'], $ver, true);
        }

        // Localize
        $presets = [
            'config' => TemplateService::getHeaderConfig(),
            'styles' => TemplateService::getHeaderStyles(),
        ];

        $localizedData = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('glory_gbn_nonce'),
            'siteTitle' => get_bloginfo('name'),
            'enabled' => true,
            'initialActive' => false,
            'pageId' => 0,
            'context' => 'header', // VITAL for persistence
            'userId' => get_current_user_id(),
            'isEditor' => true,
            'roles' => RoleConfig::all(),
            'containers' => ContainerRegistry::all(),
            'roleSchemas' => ContainerRegistry::rolePayload(),
            'devMode' => true,
            'presets' => $presets,
            'contentMode' => 'editor',
            'themeSettings' => get_option('gbn_theme_settings', []),
            'pageSettings' => [],
        ];

        wp_localize_script('glory-gbn-core', 'gloryGbnCfg', $localizedData);
        wp_localize_script('glory-gbn', 'gloryGbnCfg', $localizedData);
    }

    public static function render()
    {
        $content = TemplateService::getHeaderContent();
        ?>
        <div class="wrap gbn-editor-page">
            <h1 class="wp-heading-inline">Gestor de Cabecera (Header)</h1>
            <p>Edita el diseño de la cabecera que se aplicará en todo el sitio.</p>
            <hr class="wp-header-end">
            
            <div id="gbn-canvas-wrapper" style="background: #fff; min-height: 500px; padding: 0; border: 1px solid #ccd0d4; margin-top: 20px; position: relative;">
                <!-- IMPORTANT: Data Root for GBN to scan -->
                <!-- We apply a wrapper class to isolate styles if needed -->
                <div data-gbn-root class="gbn-canvas-root">
                    <?php echo $content; ?>
                </div>
            </div>
            
            <!-- GBN usually places its UI in fixed position, so we just provide the container -->
            <!-- We add a manual trigger button just in case the UI panel doesn't auto-show or for robust saving -->
            <div class="gbn-actions" style="margin-top: 20px; text-align: right;">
                 <button id="gbn-manual-save-btn" class="button button-primary button-large">Guardar Header</button>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Manual save trigger to ensure context is passed
            $('#gbn-manual-save-btn').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true).text('Guardando...');
                
                if(Gbn && Gbn.persistence) {
                    Gbn.persistence.savePageConfig().then(function(res) {
                        btn.prop('disabled', false).text('Guardar Header');
                        if(res.success) {
                            alert('¡Header guardado correctamente!');
                        } else {
                            alert('Error: ' + (res.data ? res.data.message : 'Desconocido'));
                        }
                    }).catch(function(err) {
                        btn.prop('disabled', false).text('Guardar Header');
                        alert('Error de conexión');
                    });
                } else {
                    alert('GBN no está cargado correctamente');
                    btn.prop('disabled', false).text('Guardar Header');
                }
            });
        });
        </script>
        <style>
            /* Ensure canvas allows absolute positioning of GBN tools if they are inside */
            .gbn-canvas-root {
                min-height: 400px;
                padding: 20px;
                background: #f0f0f1; /* Visual contrast */
            }
        </style>
        <?php
    }
}
