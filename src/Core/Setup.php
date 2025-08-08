<?php

namespace Glory\Core;

use Glory\Manager\AdminPageManager;
use Glory\Manager\AssetManager;
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
use Glory\Components\LogoRenderer;
use Glory\Services\GestorCssCritico;

class Setup
{
    public function __construct()
    {
        GloryLogger::init();
        new FormHandler();
        new PaginationAjaxHandler();
        new BusquedaAjaxHandler();

        OpcionManager::init();
        AssetsUtility::init();
        GestorCssCritico::init();

        AssetManager::register();
        PageManager::register();
        AdminPageManager::register();
        MenuManager::register();
        PostTypeManager::register();

        (new SyncController())->register();
        (new TaxonomyMetaManager())->register();
        LogoRenderer::register_shortcode();
        (new IntegrationsManager())->register();
        (new OpcionPanelController())->registerHooks();
    }
}