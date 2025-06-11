<?
namespace Glory\Admin;

use Glory\Core\OpcionRegistry;
use Glory\Core\OpcionRepository;

class PanelDataProvider
{
    public static function obtenerDatosParaPanel(): array
    {
        // 1. Obtener las definiciones directamente del registro, no del manager.
        $definiciones = OpcionRegistry::getDefiniciones();
        $datosPanel = [];
        $centinela = OpcionRepository::getCentinela();

        // 2. Iterar para obtener el valor CRUDO de la base de datos.
        foreach ($definiciones as $key => $config) {
            // Obtiene el valor directamente del repositorio (acceso a DB).
            $valorDb = OpcionRepository::get($key);

            // Si el repositorio devuelve el centinela, no hay valor en la DB.
            // Usamos el 'valorDefault' definido en el código.
            // De lo contrario, usamos el valor que está en la DB, sea cual sea.
            $config['valorActual'] = ($valorDb === $centinela)
                ? ($config['valorDefault'] ?? null)
                : $valorDb;
            
            $datosPanel[$key] = $config;
        }

        return $datosPanel;
    }
}