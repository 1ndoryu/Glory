<?php

namespace Glory\Gbn\Config;

/**
 * ScriptManifest - Registro centralizado de scripts JS para GBN
 * 
 * Este archivo fue extraído de GbnManager.php (Diciembre 2025) para:
 * - Separar la definición de scripts de la lógica de enqueue
 * - Facilitar el mantenimiento y adición de nuevos scripts
 * - Seguir el principio SRP (Single Responsibility)
 * 
 * IMPORTANTE: El orden de los scripts importa por las dependencias.
 * Modificar con cuidado.
 * 
 * @package Glory\Gbn\Config
 */
class ScriptManifest
{
    /**
     * Scripts que se cargan SIEMPRE (frontend público)
     */
    public static function getFrontendScripts(): array
    {
        return [
            'glory-gbn-core' => [
                'file' => '/js/core/utils.js',
                'deps' => ['jquery', 'glory-ajax'],
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
    }

    /**
     * Scripts de iconos (base del IconRegistry JS)
     */
    public static function getIconScripts(): array
    {
        return [
            'glory-gbn-icons-index' => [
                'file' => '/js/ui/icons/index.js',
                'deps' => [],
            ],
            'glory-gbn-icons-layout' => [
                'file' => '/js/ui/icons/layout-icons.js',
                'deps' => ['glory-gbn-icons-index'],
            ],
            'glory-gbn-icons-dimensions' => [
                'file' => '/js/ui/icons/dimensions-icons.js',
                'deps' => ['glory-gbn-icons-index'],
            ],
            'glory-gbn-icons-action' => [
                'file' => '/js/ui/icons/action-icons.js',
                'deps' => ['glory-gbn-icons-index'],
            ],
            'glory-gbn-icons-state' => [
                'file' => '/js/ui/icons/state-icons.js',
                'deps' => ['glory-gbn-icons-index'],
            ],
            'glory-gbn-icons-tab' => [
                'file' => '/js/ui/icons/tab-icons.js',
                'deps' => ['glory-gbn-icons-index'],
            ],
            'glory-gbn-icons-theme' => [
                'file' => '/js/ui/icons/theme-icons.js',
                'deps' => ['glory-gbn-icons-index'],
            ],
            'glory-gbn-icons-typography' => [
                'file' => '/js/ui/icons/typography-icons.js',
                'deps' => ['glory-gbn-icons-index'],
            ],
            'glory-gbn-icons-spacing' => [
                'file' => '/js/ui/icons/spacing-icons.js',
                'deps' => ['glory-gbn-icons-index'],
            ],
        ];
    }

    /**
     * Scripts de servicios del builder
     */
    public static function getServiceScripts(): array
    {
        return [
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
            'glory-gbn-state-styles' => [
                'file' => '/js/services/state-styles.js',
                'deps' => ['glory-gbn-style-generator'],
            ],
            'glory-gbn-diagnostics' => [
                'file' => '/js/services/diagnostics.js',
                'deps' => ['glory-gbn-services'],
            ],
        ];
    }

    /**
     * Scripts de panel-fields (módulos refactorizados Diciembre 2025)
     */
    public static function getPanelFieldScripts(): array
    {
        return [
            // 1. Módulos base
            'glory-gbn-ui-fields-deep-access' => [
                'file' => '/js/ui/panel-fields/deep-access.js',
                'deps' => ['glory-gbn-persistence'],
            ],
            'glory-gbn-ui-fields-css-map' => [
                'file' => '/js/ui/panel-fields/css-map.js',
                'deps' => ['glory-gbn-ui-fields-deep-access'],
            ],
            // 2. Módulos nivel 2
            'glory-gbn-ui-fields-theme-defaults' => [
                'file' => '/js/ui/panel-fields/theme-defaults.js',
                'deps' => ['glory-gbn-ui-fields-deep-access'],
            ],
            'glory-gbn-ui-fields-computed-styles' => [
                'file' => '/js/ui/panel-fields/computed-styles.js',
                'deps' => ['glory-gbn-ui-fields-css-map'],
            ],
            'glory-gbn-ui-fields-helpers' => [
                'file' => '/js/ui/panel-fields/helpers.js',
                'deps' => ['glory-gbn-ui-fields-deep-access', 'glory-gbn-icons-spacing'],
            ],
            'glory-gbn-ui-fields-state-utils' => [
                'file' => '/js/ui/panel-fields/state-utils.js',
                'deps' => ['glory-gbn-ui-fields-css-map'],
            ],
            // 3. Módulos nivel medio
            'glory-gbn-ui-fields-config-values' => [
                'file' => '/js/ui/panel-fields/config-values.js',
                'deps' => ['glory-gbn-ui-fields-theme-defaults', 'glory-gbn-ui-fields-computed-styles'],
            ],
            // 4. Módulos nivel alto
            'glory-gbn-ui-fields-effective-value' => [
                'file' => '/js/ui/panel-fields/effective-value.js',
                'deps' => ['glory-gbn-ui-fields-config-values', 'glory-gbn-ui-fields-state-utils', 'glory-gbn-ui-fields-helpers'],
            ],
            'glory-gbn-ui-fields-condition-handler' => [
                'file' => '/js/ui/panel-fields/condition-handler.js',
                'deps' => ['glory-gbn-ui-fields-effective-value'],
            ],
            // 5. Orquestador
            'glory-gbn-ui-fields-utils' => [
                'file' => '/js/ui/panel-fields/utils.js',
                'deps' => [
                    'glory-gbn-ui-fields-deep-access',
                    'glory-gbn-ui-fields-css-map',
                    'glory-gbn-ui-fields-theme-defaults',
                    'glory-gbn-ui-fields-computed-styles',
                    'glory-gbn-ui-fields-config-values',
                    'glory-gbn-ui-fields-effective-value',
                    'glory-gbn-ui-fields-condition-handler',
                    'glory-gbn-ui-fields-state-utils',
                    'glory-gbn-ui-fields-helpers',
                    'glory-gbn-icons-index',
                    'glory-gbn-icons-spacing',
                ],
            ],
            // 6. Campos individuales
            'glory-gbn-ui-fields-registry' => [
                'file' => '/js/ui/panel-fields/registry.js',
                'deps' => ['glory-gbn-ui-fields-utils'],
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
                'deps' => ['glory-gbn-ui-fields-utils', 'glory-gbn-icons-typography'],
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
                'deps' => ['glory-gbn-ui-fields-utils', 'glory-gbn-icons-dimensions'],
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
            'glory-gbn-ui-panel-fields' => [
                'file' => '/js/ui/panel-fields.js',
                'deps' => ['glory-gbn-ui-fields-index'],
            ],
        ];
    }

    /**
     * Obtiene TODOS los scripts del builder (para editores)
     */
    public static function getBuilderScripts(): array
    {
        return array_merge(
            self::getIconScripts(),
            self::getServiceScripts(),
            self::getPanelFieldScripts(),
            self::getRendererScripts(),
            self::getPanelRenderScripts(),
            self::getThemeScripts(),
            self::getUIScripts()
        );
    }

    /**
     * Scripts de renderers de componentes
     */
    public static function getRendererScripts(): array
    {
        return [
            'glory-gbn-ui-renderers-traits' => [
                'file' => '/js/ui/renderers/renderer-traits.js',
                'deps' => ['glory-gbn-ui-fields-utils'],
            ],
            'glory-gbn-ui-renderers-shared' => [
                'file' => '/js/ui/renderers/shared.js',
                'deps' => ['glory-gbn-ui-fields-utils', 'glory-gbn-ui-renderers-traits'],
            ],
            'glory-gbn-ui-renderers-layout-flex' => [
                'file' => '/js/ui/renderers/layout-flex.js',
                'deps' => ['glory-gbn-ui-renderers-shared'],
            ],
            'glory-gbn-ui-renderers-layout-grid' => [
                'file' => '/js/ui/renderers/layout-grid.js',
                'deps' => ['glory-gbn-ui-renderers-shared'],
            ],
            'glory-gbn-ui-renderers-style-composer' => [
                'file' => '/js/ui/renderers/style-composer.js',
                'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-layout-flex', 'glory-gbn-ui-renderers-layout-grid'],
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
            'glory-gbn-ui-renderers-header' => [
                'file' => '/js/ui/renderers/header.js',
                'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
            ],
            'glory-gbn-ui-renderers-logo' => [
                'file' => '/js/ui/renderers/logo.js',
                'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
            ],
            'glory-gbn-ui-renderers-menu' => [
                'file' => '/js/ui/renderers/menu.js',
                'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
            ],
            'glory-gbn-ui-renderers-footer' => [
                'file' => '/js/ui/renderers/footer.js',
                'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
            ],
            'glory-gbn-ui-renderers-menu-item' => [
                'file' => '/js/ui/renderers/menu-item.js',
                'deps' => ['glory-gbn-ui-renderers-shared', 'glory-gbn-ui-renderers-traits'],
            ],
        ];
    }

    /**
     * Scripts de panel-render (módulos refactorizados)
     */
    public static function getPanelRenderScripts(): array
    {
        return [
            'glory-gbn-ui-panel-render-state' => [
                'file' => '/js/ui/panel-render/state.js',
                'deps' => ['glory-gbn-state'],
            ],
            'glory-gbn-ui-panel-render-style-resolvers' => [
                'file' => '/js/ui/panel-render/style-resolvers.js',
                'deps' => ['glory-gbn-ui-panel-render-state', 'glory-gbn-style'],
            ],
            'glory-gbn-ui-panel-render-state-selector' => [
                'file' => '/js/ui/panel-render/state-selector.js',
                'deps' => ['glory-gbn-ui-panel-render-state', 'glory-gbn-icons-state'],
            ],
            'glory-gbn-ui-panel-render-tabs' => [
                'file' => '/js/ui/panel-render/tabs.js',
                'deps' => ['glory-gbn-ui-panel-render-state', 'glory-gbn-icons-tab'],
            ],
            'glory-gbn-ui-panel-render-config-updater' => [
                'file' => '/js/ui/panel-render/config-updater.js',
                'deps' => [
                    'glory-gbn-ui-panel-render-state',
                    'glory-gbn-ui-panel-render-style-resolvers',
                    'glory-gbn-responsive',
                    'glory-gbn-state-styles',
                ],
            ],
            'glory-gbn-ui-panel-render-theme-propagation' => [
                'file' => '/js/ui/panel-render/theme-propagation.js',
                'deps' => ['glory-gbn-ui-panel-render-style-resolvers'],
            ],
            'glory-gbn-ui-panel-render' => [
                'file' => '/js/ui/panel-render.js',
                'deps' => self::getPanelRenderDependencies(),
            ],
        ];
    }

    /**
     * Dependencias del panel-render principal
     */
    private static function getPanelRenderDependencies(): array
    {
        return [
            'glory-gbn-ui-panel-render-state',
            'glory-gbn-ui-panel-render-style-resolvers',
            'glory-gbn-ui-panel-render-state-selector',
            'glory-gbn-ui-panel-render-tabs',
            'glory-gbn-ui-panel-render-config-updater',
            'glory-gbn-ui-panel-render-theme-propagation',
            'glory-gbn-ui-panel-fields',
            'glory-gbn-ui-renderers-principal',
            'glory-gbn-ui-renderers-secundario',
            'glory-gbn-ui-renderers-text',
            'glory-gbn-ui-renderers-button',
            'glory-gbn-ui-renderers-image',
            'glory-gbn-ui-renderers-page-settings',
            'glory-gbn-ui-renderers-theme-settings',
            'glory-gbn-ui-renderers-post-render',
            'glory-gbn-ui-renderers-post-item',
            'glory-gbn-ui-renderers-post-field',
            'glory-gbn-ui-renderers-form',
            'glory-gbn-ui-renderers-input',
            'glory-gbn-ui-renderers-textarea',
            'glory-gbn-ui-renderers-select',
            'glory-gbn-ui-renderers-submit',
            'glory-gbn-ui-renderers-header',
            'glory-gbn-ui-renderers-logo',
            'glory-gbn-ui-renderers-menu',
            'glory-gbn-ui-renderers-footer',
            'glory-gbn-ui-renderers-menu-item',
            'glory-gbn-icons-index',
            'glory-gbn-icons-layout',
            'glory-gbn-icons-dimensions',
            'glory-gbn-icons-action',
            'glory-gbn-icons-state',
            'glory-gbn-icons-tab',
            'glory-gbn-icons-theme',
            'glory-gbn-icons-typography',
            'glory-gbn-icons-spacing',
        ];
    }

    /**
     * Scripts del módulo theme
     */
    public static function getThemeScripts(): array
    {
        return [
            'glory-gbn-ui-theme-state' => [
                'file' => '/js/ui/theme/state.js',
                'deps' => ['glory-gbn-state'],
            ],
            'glory-gbn-ui-theme-utils' => [
                'file' => '/js/ui/theme/utils.js',
                'deps' => ['glory-gbn-ui-theme-state', 'glory-gbn-icons-tab', 'glory-gbn-icons-theme'],
            ],
            'glory-gbn-ui-theme-renderer-page-settings' => [
                'file' => '/js/ui/theme/renderers/page-settings.js',
                'deps' => ['glory-gbn-ui-theme-utils', 'glory-gbn-ui-panel-fields'],
            ],
            'glory-gbn-ui-theme-renderer-menu' => [
                'file' => '/js/ui/theme/renderers/menu.js',
                'deps' => ['glory-gbn-ui-theme-utils', 'glory-gbn-icons-theme'],
            ],
            'glory-gbn-ui-theme-renderer-section-text' => [
                'file' => '/js/ui/theme/renderers/section-text.js',
                'deps' => ['glory-gbn-ui-theme-utils'],
            ],
            'glory-gbn-ui-theme-renderer-section-colors' => [
                'file' => '/js/ui/theme/renderers/section-colors.js',
                'deps' => ['glory-gbn-ui-theme-utils'],
            ],
            'glory-gbn-ui-theme-renderer-section-pages' => [
                'file' => '/js/ui/theme/renderers/section-pages.js',
                'deps' => ['glory-gbn-ui-theme-utils'],
            ],
            'glory-gbn-ui-theme-renderer-section-components' => [
                'file' => '/js/ui/theme/renderers/section-components.js',
                'deps' => ['glory-gbn-ui-theme-utils'],
            ],
            'glory-gbn-ui-theme-render' => [
                'file' => '/js/ui/theme/render.js',
                'deps' => [
                    'glory-gbn-ui-panel-fields',
                    'glory-gbn-theme-applicator',
                    'glory-gbn-ui-theme-state',
                    'glory-gbn-ui-theme-utils',
                    'glory-gbn-ui-theme-renderer-page-settings',
                    'glory-gbn-ui-theme-renderer-menu',
                    'glory-gbn-ui-theme-renderer-section-text',
                    'glory-gbn-ui-theme-renderer-section-colors',
                    'glory-gbn-ui-theme-renderer-section-pages',
                    'glory-gbn-ui-theme-renderer-section-components',
                ],
            ],
            'glory-gbn-ui-theme-index' => [
                'file' => '/js/ui/theme/index.js',
                'deps' => ['glory-gbn-ui-theme-render'],
            ],
        ];
    }

    /**
     * Scripts de UI general
     */
    public static function getUIScripts(): array
    {
        return [
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
    }
}
