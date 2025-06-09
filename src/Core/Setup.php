<?
namespace Glory\Core;

use Glory\Handler\FormHandler;

/**
 * Clase de inicialización principal.
 * Responsable de instanciar y "activar" los diferentes componentes del sistema.
 */
class Setup
{
    public function __construct()
    {

        new FormHandler();
        
        GloryLogger::init();
        // Si en el futuro añades más sistemas, los activarías aquí:
        // new \Glory\Service\OtroServicio();
        // new \Glory\Handler\OtroHandler();
    }
}