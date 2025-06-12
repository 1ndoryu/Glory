<?

namespace Glory\Core;

use Glory\Handler\FormHandler;
use Glory\Manager\OpcionManager;

class Setup
{
    public function __construct()
    {
        GloryLogger::init();
        new FormHandler();
        OpcionManager::init();
        AssetManager::register();
        PageManager::register();
    }
}
