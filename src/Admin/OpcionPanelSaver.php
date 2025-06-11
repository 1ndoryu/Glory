<?
namespace Glory\Admin;

use Glory\Core\GloryLogger;
use Glory\Core\OpcionRegistry;
use Glory\Core\OpcionRepository;

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

        foreach ($datosPost as $key => $valor) {
            $config = OpcionRegistry::getDefinicion($key);
            if (!$config) {
                // Omite claves que no corresponden a una opción definida (ej. action, nonce).
                $opcionesOmitidas[] = $key;
                continue;
            }

            // La sanitización debe ocurrir en el punto de entrada, antes de llamar a este método.
            // Aquí se confía en que el valor ya viene preparado.
            OpcionRepository::save($key, $valor);
            OpcionRepository::savePanelMeta($key, $config['hashVersionCodigo']);
            
            $opcionesGuardadas[] = $key;
        }

        if (!empty($opcionesOmitidas)) {
            GloryLogger::info('OpcionPanelSaver: Se omitieron claves no definidas al guardar.', ['claves' => $opcionesOmitidas]);
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
        
        if (empty($definiciones)) {
            return ['reseteadas' => 0];
        }

        foreach ($definiciones as $key => $config) {
            // Compara el slug de la sección de la configuración con el slug proporcionado.
            if (sanitize_title($config['seccion'] ?? 'general') !== $slugSeccion) {
                continue;
            }
            
            // Restaura el valor y elimina los metadatos del panel.
            OpcionRepository::save($key, $config['valorDefault']);
            OpcionRepository::deletePanelMeta($key);
            $camposProcesados++;
        }

        return ['reseteadas' => $camposProcesados];
    }
}