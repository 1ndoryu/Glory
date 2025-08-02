<?php

namespace Glory\Core;

use Glory\Admin\OpcionPanelController;
use Glory\Handler\FormHandler;
use Glory\Manager\OpcionManager;
use Glory\Admin\SyncController;
use Glory\Admin\TaxonomyMetaManager;
use Glory\Core\LicenseManager;
use Glory\Utility\AssetsUtility;
use Glory\Handler\PaginationAjaxHandler;
use Glory\Core\MenuManager;
use Glory\Components\LogoRenderer;
use Glory\Core\IntegrationsManager;

class Setup
{
    public function __construct()
    {
        #error_log('GLORY DEBUG: Iniciando Setup...');
        
        #error_log('GLORY DEBUG: -> GloryLogger::init()');
        GloryLogger::init();
        
        #error_log('GLORY DEBUG: -> new FormHandler()');
        new FormHandler();
        
        #error_log('GLORY DEBUG: -> new PaginationAjaxHandler()');
        new PaginationAjaxHandler();
        
        #error_log('GLORY DEBUG: -> OpcionManager::init()');
        OpcionManager::init();
        
        #error_log('GLORY DEBUG: -> AssetsUtility::init()');
        AssetsUtility::init();
        
        #error_log('GLORY DEBUG: -> AssetManager::register()');
        AssetManager::register();
        
        #error_log('GLORY DEBUG: -> PageManager::register()');
        PageManager::register();
        
        #error_log('GLORY DEBUG: -> MenuManager::register()');
        MenuManager::register();
        
        #error_log('GLORY DEBUG: -> new SyncController()');
        (new SyncController())->register();
        
        #error_log('GLORY DEBUG: -> new TaxonomyMetaManager()');
        (new TaxonomyMetaManager())->register();
        
        #error_log('GLORY DEBUG: -> LogoRenderer::register_shortcode()');
        LogoRenderer::register_shortcode();
        
        #error_log('GLORY DEBUG: -> new IntegrationsManager()');
        (new IntegrationsManager())->register();
        
        #error_log('GLORY DEBUG: -> new OpcionPanelController()');
        (new OpcionPanelController())->registerHooks();
        
        #error_log('GLORY DEBUG: Setup completado con éxito.');
    }
}