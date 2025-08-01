<?php

namespace Glory\Core;

// 1. AÑADE ESTA LÍNEA AL PRINCIPIO CON LOS DEMÁS 'use'
use Glory\Admin\OpcionPanelController; 
use Glory\Handler\FormHandler;
use Glory\Manager\OpcionManager;
use Glory\Admin\SyncController;
use Glory\Admin\TaxonomyMetaManager;
use Glory\Core\LicenseManager;
use Glory\Utility\AssetsUtility;
use Glory\Handler\PaginationAjaxHandler;
use Glory\Core\MenuManager;

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
        (new SyncController())->register();
        (new TaxonomyMetaManager())->register();

        // 2. AÑADE ESTA LÍNEA AL FINAL DEL CONSTRUCTOR
        (new OpcionPanelController())->registerHooks();
    }
}