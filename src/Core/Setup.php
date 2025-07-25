<?

namespace Glory\Core;

use Glory\Handler\FormHandler;
use Glory\Manager\OpcionManager;
use Glory\Admin\SyncController;
use Glory\Admin\TaxonomyMetaManager;
use Glory\Core\LicenseManager;

class Setup
{
    public function __construct()
    {   
        #LicenseManager::init();
        GloryLogger::init();
        new FormHandler();
        OpcionManager::init();
        AssetManager::register();
        PageManager::register();
        (new SyncController())->register();
        (new TaxonomyMetaManager())->register(); 
        
    }
}