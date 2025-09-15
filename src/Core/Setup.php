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
use Glory\Core\GloryLogger;

use Glory\Admin\OpcionPanelController;
use Glory\Handler\FormHandler;
use Glory\Admin\SyncController;
use Glory\Admin\TaxonomyMetaManager;
use Glory\Utility\AssetsUtility;
use Glory\Handler\PaginationAjaxHandler;
use Glory\Handler\BusquedaAjaxHandler;
use Glory\Handler\RealtimeAjaxHandler;
use Glory\Components\LogoRenderer;
use Glory\Services\GestorCssCritico;
use Glory\Handler\ContentActionAjaxHandler;

class Setup
{
    public function __construct()
    {
        // Inicializar logger solo si la feature no está desactivada
        if (GloryFeatures::isActive('gloryLogger') !== false) {
            GloryLogger::init();
        }

        // Verificación de licencia (controlada por feature)
        if (GloryFeatures::isActive('licenseManager') !== false) {
            //LicenseManager::init();
        }

        // Formularios (FormHandler) - el propio constructor también checa la feature,
        // pero evitamos instanciarlo si la feature está desactivada.
        if (GloryFeatures::isActive('gloryForm') !== false) {
            new FormHandler();
        }

        if (GloryFeatures::isActive('paginacion') !== false) {
            new PaginationAjaxHandler();
        }

        if (GloryFeatures::isActive('gloryBusqueda') !== false) {
            new BusquedaAjaxHandler();
        }

        if (GloryFeatures::isActive('gloryRealtime') !== false) {
            new RealtimeAjaxHandler();
        }

        // Acciones agnósticas de contenido (eliminar, etc.)
        if (GloryFeatures::isActive('contentActions') !== false) {
            new ContentActionAjaxHandler();
        }

        // Opciones y assets
        if (GloryFeatures::isActive('opcionManagerSync') !== false) {
            OpcionManager::init();
        }

        if (GloryFeatures::isActive('assetManager') !== false) {
            AssetsUtility::init();
        }

        if (GloryFeatures::isActive('cssCritico') !== false) {
            GestorCssCritico::init();
        }

        // Créditos (cron programable)
        if (GloryFeatures::isActive('creditosManager') !== false) {
            // CreditosManager::init();
        }

        // Registro/registro de managers principales (condicionales para control de rendimiento)
        if (GloryFeatures::isActive('assetManager') !== false) {
            AssetManager::register();
        }

        if (GloryFeatures::isActive('pageManager') !== false) {
            PageManager::register();
            AdminPageManager::register();
        }

        if (GloryFeatures::isActive('menu') !== false) {
            MenuManager::register();
        }

        if (GloryFeatures::isActive('postTypeManager') !== false) {
            PostTypeManager::register();
        }

        // Contenido por defecto (sincronización y hooks relacionados)
        if (GloryFeatures::isActive('defaultContentManager') !== false) {
            DefaultContentManager::register();
        }

        if (GloryFeatures::isActive('syncManager') !== false) {
            (new SyncController())->register();
        }

        if (GloryFeatures::isActive('taxonomyMetaManager') !== false) {
            (new TaxonomyMetaManager())->register();
        }

        if (GloryFeatures::isActive('logoRenderer') !== false) {
            LogoRenderer::register_shortcode();
        }

        if (GloryFeatures::isActive('integrationsManager') !== false) {
            (new IntegrationsManager())->register();
            // Registrar integraciones específicas (Elementor)
            if (class_exists('Elementor\\Plugin')) {
                \Glory\Integration\Elementor\ElementorIntegration::register();
            }
        }

        if (GloryFeatures::isActive('opcionManagerSync') !== false) {
            (new OpcionPanelController())->registerHooks();
        }
    }
}