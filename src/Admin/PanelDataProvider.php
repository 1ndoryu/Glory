<?

namespace Glory\Admin;

use Glory\Manager\OpcionManager;
use Glory\Core\GloryLogger;

/**
 * Provee los datos necesarios para construir paneles de administración de opciones.
 *
 * Esta clase extrae la responsabilidad de preparar los datos para la UI
 * fuera de OpcionManager. Su única función es obtener las definiciones
 * y los valores actuales de las opciones para que un renderizador de paneles
 * pueda consumirlos.
 */
class PanelDataProvider
{
    /**
     * Obtiene todas las opciones registradas y las enriquece con sus valores actuales.
     *
     * Este método consume la API pública y limpia de OpcionManager para construir
     * un array de datos listo para ser consumido por un renderizador de paneles de administración.
     *
     * @return array Un array asociativo donde cada clave es la `$key` de la opción y el valor
     * es un array con la configuración completa de la opción más una entrada `valorActual`.
     */
    public static function obtenerDatosParaPanel(): array
    {
        // 1. Obtener las definiciones base, sin valores.
        $definiciones = OpcionManager::getDefinicionesRegistradas();
        $datosPanel = [];

        // 2. Iterar sobre cada definición para obtener su valor actual.
        foreach ($definiciones as $key => $config) {
            // Se utiliza el método get() público, que asegura que el valor está sincronizado y es correcto.
            $config['valorActual'] = OpcionManager::get($key, $config['valorDefault'] ?? null);
            $datosPanel[$key] = $config;
        }

        return $datosPanel;
    }
}
