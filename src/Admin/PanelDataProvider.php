<?
namespace Glory\Admin;

use Glory\Core\OpcionRegistry;
use Glory\Core\OpcionRepository;
use Glory\Core\GloryFeatures;
use Glory\Manager\AssetManager;

class PanelDataProvider
{
    public static function obtenerDatosParaPanel(): array
    {
        // 1. Obtener las definiciones directamente del registro, no del manager.
        $definiciones = OpcionRegistry::getDefiniciones();
        $datosPanel = [];
        $centinela = OpcionRepository::getCentinela();
        $isDev = (method_exists(AssetManager::class, 'isGlobalDevMode') && AssetManager::isGlobalDevMode()) || (defined('WP_DEBUG') && WP_DEBUG);

        // 2. Iterar para obtener el valor CRUDO de la base de datos.
        foreach ($definiciones as $key => $config) {
            // Obtiene el valor directamente del repositorio (acceso a DB).
            $valorDb = OpcionRepository::get($key);

            // Calcular el default efectivo: por defecto del archivo, pero si existe featureKey
            // y en el código se definió via GloryFeatures::enable/disable, ese valor del código
            // es el DEFAULT efectivo (aplica tanto en dev como en prod para "restaurar default").
            $defaultEfectivo = $config['valorDefault'] ?? null;
            if (!empty($config['featureKey'])) {
                $desdeCodigo = GloryFeatures::isEnabled($config['featureKey']);
                if ($desdeCodigo !== null) {
                    $defaultEfectivo = (bool) $desdeCodigo;
                }
            }

            if ($isDev && !empty($config['featureKey'])) {
                // En modo desarrollo, si el código fija un valor, sincronizamos la UI con ese valor
                $desdeCodigo = GloryFeatures::isEnabled($config['featureKey']);
                if ($desdeCodigo !== null) {
                    $config['valorActual'] = (bool) $desdeCodigo;
                } else {
                    $config['valorActual'] = ($valorDb === $centinela) ? $defaultEfectivo : $valorDb;
                }
            } else {
                // En producción o sin override de código: mostrar lo de BD si existe, si no el default efectivo
                $config['valorActual'] = ($valorDb === $centinela) ? $defaultEfectivo : $valorDb;
            }

            // Exponer también el default efectivo por si la vista desea indicarlo
            $config['valorDefaultEfectivo'] = $defaultEfectivo;
            
            $datosPanel[$key] = $config;
        }

        return $datosPanel;
    }
}