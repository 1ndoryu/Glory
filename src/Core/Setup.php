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
        
        // Llama al inicializador del nuevo OpcionManager unificado.
        OpcionManager::init();
    }
}