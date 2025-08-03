<?php

namespace Glory\Core;

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
use Glory\Components\LogoRenderer;

class Setup
{
    public function __construct()
    {
        GloryLogger::init();
        new FormHandler();
        new PaginationAjaxHandler();

        OpcionManager::init();
        AssetsUtility::init();

        AssetManager::register();
        PageManager::register();
        MenuManager::register();
        PostTypeManager::register();

        (new SyncController())->register();
        (new TaxonomyMetaManager())->register();
        LogoRenderer::register_shortcode();
        (new IntegrationsManager())->register();
        (new OpcionPanelController())->registerHooks();
    }
}