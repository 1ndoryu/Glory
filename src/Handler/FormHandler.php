<?php
// Glory/src/Handler/formHandler.php

namespace Glory\Handler;

use Glory\Core\GloryLogger;
use Glory\Core\GloryFeatures;

class FormHandler
{
    private const PREFIJO_CLASE_DEFAULT = 'Glory\\Handler\\Form\\';
    private const SUFIJO_CLASE = 'Handler';

    /**
     * @var array Almacena los namespaces donde buscar manejadores.
     * Se da prioridad a los namespaces de la aplicación (añadidos primero).
     */
    private static array $handlerNamespaces = [
        self::PREFIJO_CLASE_DEFAULT
    ];

    public function __construct()
    {
        // No registrar los hooks si la feature fue desactivada explicitamente
        // Usar isActive para combinar override por código + opción en BD.
        if (GloryFeatures::isActive('gloryForm', 'glory_componente_glory_form_activado') === false) {
            return;
        }

        add_action('wp_ajax_gloryFormHandler', [$this, 'manejarPeticion']);
        add_action('wp_ajax_nopriv_gloryFormHandler', [$this, 'manejarPeticion']);
    }
    
    /**
     * Permite a la aplicación registrar namespaces adicionales para los manejadores de formularios.
     *
     * @param string $namespace El namespace base para los manejadores (ej. 'App\\Handlers\\Form\\').
     */
    public static function registerHandlerNamespace(string $namespace): void
    {
        // Añade el nuevo namespace al principio del array para darle prioridad.
        array_unshift(self::$handlerNamespaces, $namespace);
        self::$handlerNamespaces = array_unique(self::$handlerNamespaces);
    }

    public function manejarPeticion()
    {
        $subAccion = $_POST['subAccion'] ?? null;
        GloryLogger::info('Iniciando manejo de petición de formulario.', ['subAccion' => $subAccion]);

        try {
            if (empty($subAccion)) {
                throw new \Exception('No se ha especificado una subAcción para el formulario.');
            }
            
            $subAccionSanitizada = sanitize_text_field($subAccion);
            $nombreClaseHandler = null;
            $handlerEncontrado = false;

            // Iterar sobre los namespaces registrados para encontrar el manejador
            foreach (self::$handlerNamespaces as $namespace) {
                $nombreClasePotencial = $namespace . ucfirst($subAccionSanitizada) . self::SUFIJO_CLASE;
                if (class_exists($nombreClasePotencial)) {
                    $nombreClaseHandler = $nombreClasePotencial;
                    $handlerEncontrado = true;
                    break;
                }
            }

            if (!$handlerEncontrado) {
                throw new \Exception("El manejador para la acción '{$subAccionSanitizada}' no existe en ninguna de las rutas registradas.");
            }

            $handler = new $nombreClaseHandler();

            if (!is_callable([$handler, 'procesar'])) {
                throw new \Exception("El método procesar() no se encuentra en el manejador '{$nombreClaseHandler}'.");
            }

            $respuesta = $handler->procesar($_POST, $_FILES);
            GloryLogger::info('Petición de formulario procesada exitosamente.', ['subAccion' => $subAccionSanitizada, 'handler' => $nombreClaseHandler]);
            wp_send_json_success($respuesta);
        } catch (\Exception $e) {
            GloryLogger::error(
                'Error al procesar petición de formulario: ' . $e->getMessage(),
                [
                    'subAccion' => $subAccion,
                    'postData'  => $_POST,
                    'excepcion' => [
                        'mensaje' => $e->getMessage(),
                        'archivo' => $e->getFile(),
                        'linea'   => $e->getLine(),
                    ],
                ]
            );
            wp_send_json_error(['alert' => $e->getMessage()]);
        }
    }
}
