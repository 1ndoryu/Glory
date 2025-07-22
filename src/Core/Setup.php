<?

namespace Glory\Core;

use Glory\Handler\FormHandler;
use Glory\Manager\OpcionManager;
use Glory\Admin\SyncController;

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
    }
}