<?php

namespace Glory\Core;

use Glory\Manager\AdminPageManager;
use Glory\Manager\AssetManager;
// use Glory\Manager\CreditosManager;
use Glory\Manager\DefaultContentManager;
use Glory\Manager\MenuManager;
use Glory\Manager\OpcionManager;
use Glory\Manager\PageManager;
use Glory\Manager\PostTypeManager;
use Glory\Integration\IntegrationsManager;
use Glory\Services\LicenseManager;
use Glory\Services\PerformanceProfiler;
use Glory\Core\GloryLogger;

use Glory\Admin\OpcionPanelController;
use Glory\Admin\DatabaseExportController;
use Glory\Handler\FormHandler;
use Glory\Admin\SyncController;
use Glory\Admin\TaxonomyMetaManager;
use Glory\Utility\AssetsUtility;
use Glory\Handler\PaginationAjaxHandler;
use Glory\Handler\BusquedaAjaxHandler;
use Glory\Handler\RealtimeAjaxHandler;
use Glory\Components\LogoRenderer;
use Glory\Components\ContentRender;
use Glory\Services\GestorCssCritico;
use Glory\Handler\ContentActionAjaxHandler;

class Setup
{
    public function __construct()
    {
        // Inicializar profiler de rendimiento (si está activo)
        PerformanceProfiler::init();

        PerformanceProfiler::start('Setup.constructor', 'core');

        // Inicializar logger solo si la feature no está desactivada
        if (GloryFeatures::isActive('gloryLogger') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => GloryLogger::init(),
                'GloryLogger.init',
                'logger'
            );
        }

        // Verificación de licencia (controlada por feature)
        if (GloryFeatures::isActive('licenseManager') !== false) {
            //LicenseManager::init();
        }

        // Formularios (FormHandler) - el propio constructor también checa la feature,
        // pero evitamos instanciarlo si la feature está desactivada.
        if (GloryFeatures::isActive('gloryForm') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => new FormHandler(),
                'FormHandler.constructor',
                'handler'
            );
        }

        if (GloryFeatures::isActive('paginacion') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => new PaginationAjaxHandler(),
                'PaginationAjaxHandler.constructor',
                'handler'
            );
        }

        if (GloryFeatures::isActive('gloryBusqueda') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => new BusquedaAjaxHandler(),
                'BusquedaAjaxHandler.constructor',
                'handler'
            );
        }

        if (GloryFeatures::isActive('gloryRealtime') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => new RealtimeAjaxHandler(),
                'RealtimeAjaxHandler.constructor',
                'handler'
            );
        }

        // Acciones agnósticas de contenido (eliminar, etc.)
        if (GloryFeatures::isActive('contentActions') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => new ContentActionAjaxHandler(),
                'ContentActionAjaxHandler.constructor',
                'handler'
            );
        }

        // Opciones y assets
        if (GloryFeatures::isActive('opcionManagerSync') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => OpcionManager::init(),
                'OpcionManager.init',
                'manager'
            );
        }

        if (GloryFeatures::isActive('assetManager') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => AssetsUtility::init(),
                'AssetsUtility.init',
                'utility'
            );
        }

        if (GloryFeatures::isActive('cssCritico') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => GestorCssCritico::init(),
                'GestorCssCritico.init',
                'service'
            );
        }

        // Créditos (cron programable)
        if (GloryFeatures::isActive('creditosManager') !== false) {
            // CreditosManager::init();
        }

        // Registro/registro de managers principales (condicionales para control de rendimiento)
        if (GloryFeatures::isActive('assetManager') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => AssetManager::register(),
                'AssetManager.register',
                'manager'
            );
        }

        if (GloryFeatures::isActive('pageManager') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => PageManager::register(),
                'PageManager.register',
                'manager'
            );
            PerformanceProfiler::medirFuncion(
                fn() => AdminPageManager::register(),
                'AdminPageManager.register',
                'manager'
            );
        }

        if (GloryFeatures::isActive('menu') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => MenuManager::register(),
                'MenuManager.register',
                'manager'
            );
        }

        if (GloryFeatures::isActive('postTypeManager') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => PostTypeManager::register(),
                'PostTypeManager.register',
                'manager'
            );
        }

        // Contenido por defecto (sincronización y hooks relacionados)
        if (GloryFeatures::isActive('defaultContentManager') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => DefaultContentManager::register(),
                'DefaultContentManager.register',
                'manager'
            );
        }

        if (GloryFeatures::isActive('syncManager') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => (new SyncController())->register(),
                'SyncController.register',
                'controller'
            );
        }

        if (GloryFeatures::isActive('taxonomyMetaManager') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => (new TaxonomyMetaManager())->register(),
                'TaxonomyMetaManager.register',
                'manager'
            );
        }

        if (GloryFeatures::isActive('logoRenderer') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => LogoRenderer::register_shortcode(),
                'LogoRenderer.register_shortcode',
                'renderer'
            );
        }

        if (GloryFeatures::isActive('integrationsManager') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => (new IntegrationsManager())->register(),
                'IntegrationsManager.register',
                'integration'
            );
            // Registrar integraciones específicas (Elementor)
            if (class_exists('Elementor\\Plugin')) {
                PerformanceProfiler::medirFuncion(
                    fn() => \Glory\Integration\Elementor\ElementorIntegration::register(),
                    'ElementorIntegration.register',
                    'integration'
                );
            }
        }

        if (GloryFeatures::isActive('opcionManagerSync') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => (new OpcionPanelController())->registerHooks(),
                'OpcionPanelController.registerHooks',
                'controller'
            );
        }

        // Panel de exportación de base de datos
        if (GloryFeatures::isActive('databaseExporter') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => (new DatabaseExportController())->registerHooks(),
                'DatabaseExportController.registerHooks',
                'controller'
            );
        }

        // Inicializar hooks de limpieza de caché para ContentRender
        PerformanceProfiler::medirFuncion(
            fn() => ContentRender::initHooks(),
            'ContentRender.initHooks',
            'component'
        );

        PerformanceProfiler::end('Setup.constructor');
    }
}