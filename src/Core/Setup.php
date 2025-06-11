<?php
namespace Glory\Core;

use Glory\Handler\FormHandler;
use Glory\Core\OpcionConfigurator;

/**
 * Clase de inicialización principal.
 * Responsable de instanciar y "activar" los diferentes componentes del sistema.
 * @author @wandorius
 */
class Setup
{
    /**
     * Constructor de la clase Setup.
     * Inicializa los manejadores y servicios principales del tema o plugin.
     */
    public function __construct()
    {
        // Inicializa el sistema de logging.
        GloryLogger::init();

        // Inicializa el manejador de formularios.
        new FormHandler();
        
        // Inicializa y engancha el sincronizador de opciones del tema.
        OpcionConfigurator::inicializarSincronizacion();

        // Ejemplo de cómo se podrían inicializar otros componentes en el futuro:
        // new \Glory\Service\OtroServicio(); // Activa un nuevo servicio.
        // new \Glory\Handler\OtroHandler();  // Activa un nuevo manejador.
    }
}