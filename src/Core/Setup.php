<?php

namespace Glory\Core;

use Glory\Manager\AdminPageManager;
use Glory\Manager\AssetManager;
use Glory\Manager\DefaultContentManager;
use Glory\Manager\MenuManager;
use Glory\Manager\OpcionManager;
use Glory\Manager\PageManager;
use Glory\Manager\PostTypeManager;
use Glory\Services\PerformanceProfiler;
use Glory\Core\GloryLogger;

use Glory\Admin\OpcionPanelController;
use Glory\Admin\SyncController;
use Glory\Utility\AssetsUtility;
use Glory\Admin\SeoMetabox;
use Glory\Seo\SeoFrontendRenderer;
use Glory\Plugins\AmazonProduct\AmazonProductPlugin;
use Glory\Api\PageBlocksController;

/**
 * Clase principal de inicialización del framework Glory.
 *
 * Se encarga de orquestar la carga de todos los componentes, servicios y manejadores
 * del sistema, basándose en la configuración de funcionalidades activas.
 */
class Setup
{
    /**
     * Constructor de la clase Setup.
     *
     * Ejecuta la secuencia de arranque del framework:
     * 1. Inicializa herramientas de diagnóstico y logging.
     * 2. Instancia manejadores de peticiones (Handlers).
     * 3. Inicializa managers y utilidades.
     * 4. Registra hooks y componentes de administración.
     * 5. Carga integraciones con terceros.
     */
    public function __construct()
    {
        // Inicializar profiler de rendimiento (si está activo)
        PerformanceProfiler::init();

        PerformanceProfiler::start('Setup.constructor', 'core');

        // Inicializar logger solo si la funcionalidad no está desactivada
        if (GloryFeatures::isActive('gloryLogger') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => GloryLogger::init(),
                'GloryLogger.init',
                'logger'
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



        // Registro de managers principales
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

            // Registrar metabox SEO reutilizable
            PerformanceProfiler::medirFuncion(
                fn() => (new SeoMetabox())->registerHooks(),
                'SeoMetabox.registerHooks',
                'admin'
            );
            // Registrar renderizado SEO en frontend (title, meta, JSON-LD)
            if (GloryFeatures::isActive('seoFrontend') !== false) {
                PerformanceProfiler::medirFuncion(
                    fn() => SeoFrontendRenderer::register(),
                    'SeoFrontendRenderer.register',
                    'seo'
                );
            }

            /* Registrar REST API del Page Builder */
            if (GloryFeatures::isActive('pageBuilder') !== false) {
                PerformanceProfiler::medirFuncion(
                    fn() => PageBlocksController::register(),
                    'PageBlocksController.register',
                    'api'
                );
            }
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



        if (GloryFeatures::isActive('opcionManagerSync') !== false) {
            PerformanceProfiler::medirFuncion(
                fn() => (new OpcionPanelController())->registerHooks(),
                'OpcionPanelController.registerHooks',
                'controller'
            );
        }



        if (GloryFeatures::isActive('amazonProduct') !== false) {
            (new AmazonProductPlugin())->init();
        }

        PerformanceProfiler::end('Setup.constructor');
    }
}
