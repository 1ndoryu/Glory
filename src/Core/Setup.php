<?

namespace Glory\Core;

use Glory\Handler\FormHandler;
use Glory\Manager\OpcionManager;
use Glory\Admin\SyncController;
use Glory\Admin\TaxonomyMetaManager;

class Setup
{
    public function __construct()
    {
        GloryLogger::init();
        new FormHandler();
        OpcionManager::init();
        AssetManager::register();
        PageManager::register();
        (new SyncController())->register();
        (new TaxonomyMetaManager())->register(); 
    }
}