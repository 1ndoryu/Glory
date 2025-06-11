<?

namespace Glory\Handler;

use Glory\Core\GloryLogger;

class FormHandler
{
    private const PREFIJO_CLASE = 'Glory\\Handler\\Form\\';
    private const SUFIJO_CLASE = 'Handler';

    public function __construct()
    {
        add_action('wp_ajax_gloryFormHandler', [$this, 'manejarPeticion']);
        add_action('wp_ajax_nopriv_gloryFormHandler', [$this, 'manejarPeticion']);
    }

    public function manejarPeticion()
    {
        $subAccion = $_POST['subAccion'] ?? null;
        GloryLogger::info('Iniciando manejo de petición de formulario.', ['subAccion' => $subAccion]);

        try {
            /*
            desactivado temporalmente
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'glory_nonce')) {
                throw new \Exception('Falló la verificación de seguridad (nonce).');
            }
            */

            if (empty($subAccion)) {
                throw new \Exception('No se ha especificado una subAcción para el formulario.');
            }

            $subAccionSanitizada = sanitize_text_field($subAccion);
            $nombreClaseHandler = self::PREFIJO_CLASE . ucfirst($subAccionSanitizada) . self::SUFIJO_CLASE;

            if (!class_exists($nombreClaseHandler)) {
                throw new \Exception("El manejador para la acción '{$subAccionSanitizada}' no existe.");
            }

            $handler = new $nombreClaseHandler();

            if (!is_callable([$handler, 'procesar'])) {
                throw new \Exception("El método procesar() no se encuentra en el manejador '{$nombreClaseHandler}'.");
            }

            $respuesta = $handler->procesar($_POST, $_FILES);

            GloryLogger::info('Petición de formulario procesada exitosamente.', ['subAccion' => $subAccionSanitizada]);

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
