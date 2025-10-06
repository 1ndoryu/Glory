<?
namespace Glory\Admin;

use Glory\Core\GloryLogger;
use Glory\Core\OpcionRegistry;
use Glory\Core\OpcionRepository;
use Glory\Manager\OpcionManager;

/**
 * Gestiona todas las operaciones de escritura para el panel de opciones.
 *
 * Su responsabilidad es tomar los datos enviados desde la UI del panel
 * y persistirlos utilizando el OpcionRepository, aplicando la lógica
 * de metadatos necesaria para marcar las opciones como "guardadas desde el panel".
 */
class OpcionPanelSaver
{
    /**
     * Guarda los valores de las opciones enviados desde un panel de administración.
     *
     * @param array $datosPost Los datos del formulario (ej. $_POST).
     * @return array Un resumen de la operación.
     */
    public static function guardarDesdePanel(array $datosPost): array
    {
        $opcionesGuardadas = [];
        $opcionesOmitidas = [];

        // Procesar solo las opciones registradas para garantizar que los checkboxes
        // que no vienen en $_POST se guarden como false.
        $definiciones = OpcionRegistry::getDefiniciones();
        foreach ($definiciones as $key => $config) {
            // Determinar el valor a guardar: si el campo está en los datos POST, usarlo;
            // si no está y es un checkbox/toggle, guardar false; en otro caso, omitir.
            if (array_key_exists($key, $datosPost)) {
                $valor = $datosPost[$key];
            } else {
                $tipo = $config['tipo'] ?? 'text';
                if (in_array($tipo, ['checkbox', 'toggle'], true)) {
                    $valor = false;
                } else {
                    // No recibimos valor para este campo y no es checkbox -> no tocar
                    continue;
                }
            }

            // Guardar valor y metadatos
            OpcionRepository::save($key, $valor);
            OpcionRepository::savePanelMeta($key, $config['hashVersionCodigo'] ?? '');
            $opcionesGuardadas[] = $key;
        }

        // Invalidar cache de lecturas para reflejar cambios inmediatamente
        if (class_exists(OpcionManager::class)) {
            OpcionManager::clearCache();
        }

        return ['guardadas' => count($opcionesGuardadas), 'omitidas' => count($opcionesOmitidas)];
    }

    /**
     * Resetea todas las opciones de una sección a sus valores por defecto definidos en el código.
     *
     * @param string $slugSeccion El slug de la sección a resetear.
     * @return array Un resumen del reseteo.
     */
    public static function resetearSeccion(string $slugSeccion): array
    {
        $definiciones = OpcionRegistry::getDefiniciones();
        $camposProcesados = 0;
        $isDev = (defined('WP_DEBUG') && WP_DEBUG) || (class_exists('Glory\\Manager\\AssetManager') && \Glory\Manager\AssetManager::isGlobalDevMode());
        
        if (empty($definiciones)) {
            return ['reseteadas' => 0];
        }

        foreach ($definiciones as $key => $config) {
            // Compara el slug de la sección de la configuración con el slug proporcionado.
            if (sanitize_title($config['seccion'] ?? 'general') !== $slugSeccion) {
                continue;
            }
            
            // Default efectivo: si hay featureKey y GloryFeatures tiene override, usarlo como default
            $defaultEfectivo = $config['valorDefault'] ?? null;
            if (!empty($config['featureKey']) && class_exists('Glory\\Core\\GloryFeatures')) {
                $desdeCodigo = \Glory\Core\GloryFeatures::isEnabled($config['featureKey']);
                if ($desdeCodigo !== null) {
                    $defaultEfectivo = (bool) $desdeCodigo;
                }
            }

            // Restaura el valor y elimina los metadatos del panel.
            OpcionRepository::save($key, $defaultEfectivo);
            OpcionRepository::deletePanelMeta($key);
            $camposProcesados++;
        }

        // Invalidar cache de lecturas tras resetear
        if (class_exists(OpcionManager::class)) {
            OpcionManager::clearCache();
        }

        return ['reseteadas' => $camposProcesados];
    }
}