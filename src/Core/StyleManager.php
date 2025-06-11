<?
namespace Glory\Core;

use Glory\Core\GloryLogger;
use Glory\Core\AssetManager;

/**
 * Gestiona la definición, registro y puesta en cola de hojas de estilo CSS en WordPress.
 * Hereda de AssetManager para funcionalidades comunes de gestión de assets.
 * @author @wandorius
 */
class StyleManager extends AssetManager
{
    /** @var array Almacena la colección de estilos definidos, aislada de otros tipos de assets. */
    protected static array $assetsDefinidos = [];

    /**
     * Define una hoja de estilo para ser gestionada.
     *
     * @param string $handle Identificador único para el estilo (manejador).
     * @param string|null $rutaCss Ruta relativa al archivo CSS.
     * @param array $dependencias Dependencias de otros estilos.
     * @param string|null $version Versión del estilo.
     * @param string $medios Tipo de medio (ej: 'all', 'screen').
     * @param bool|null $modoDesarrolloEspecifico Modo desarrollo para este estilo.
     */
    public static function define(
        string $handle,
        ?string $rutaCss = null,
        array $dependencias = [],
        ?string $version = null,
        string $medios = 'all',
        ?bool $modoDesarrolloEspecifico = null
    ): void {
        if (is_null($rutaCss)) {
            $rutaCss = 'css/' . $handle . '.css';
        }

        $configuracion = [
            'ruta' => $rutaCss,
            'dependencias' => $dependencias,
            'version' => $version,
            'medios' => $medios,
            'modoDesarrollo' => $modoDesarrolloEspecifico,
            'identificador' => $handle
        ];

        static::defineAsset($handle, $configuracion);
    }

    /**
     * Define automáticamente todos los estilos de una carpeta.
     *
     * @param string $rutaRelativaCarpeta Ruta de la carpeta relativa a la raíz del tema.
     * @param array $dependenciasDefault Dependencias por defecto.
     * @param string $mediosDefault Medios por defecto.
     * @param bool|null $modoDesarrolloCarpeta Modo desarrollo para la carpeta.
     * @param string $prefijoManejador Prefijo para los handles generados.
     * @param array $archivosExcluidos Archivos a excluir.
     */
    public static function defineFolder(
        string $rutaRelativaCarpeta = 'css',
        array $dependenciasDefault = [],
        string $mediosDefault = 'all',
        ?bool $modoDesarrolloCarpeta = null,
        string $prefijoManejador = '',
        array $archivosExcluidos = []
    ): void {
        static::definirDesdeCarpeta(
            'css',
            $rutaRelativaCarpeta,
            $dependenciasDefault,
            $mediosDefault,
            $modoDesarrolloCarpeta,
            $prefijoManejador,
            $archivosExcluidos
        );
    }

    /**
     * Implementación del método abstracto para construir la configuración de un estilo.
     */
    protected static function construirConfiguracionAssetDesdeCarpeta(
        string $rutaRelativaWeb,
        array $dependenciasDefault,
        mixed $opcionesDefault,
        ?bool $modoDesarrollo,
        string $identificador
    ): array {
        return [
            'ruta' => $rutaRelativaWeb,
            'dependencias' => $dependenciasDefault,
            'version' => null,
            'medios' => $opcionesDefault ?? 'all',
            'modoDesarrollo' => $modoDesarrollo,
            'identificador' => $identificador,
        ];
    }

    /**
     * Registra el hook para poner en cola los estilos definidos.
     */
    public static function register(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueItems'], 15);
    }
    
    /**
     * Implementación específica para encolar una hoja de estilo.
     * Este método es llamado por el `enqueueItems` de la clase base.
     */
    protected static function encolarActivoEspecifico(string $handle, array $config, bool $yaEncolado): void
    {
        if ($yaEncolado) {
            return;
        }

        wp_enqueue_style(
            $handle,
            $config['urlArchivo'],
            $config['dependencias'] ?? [],
            $config['versionCalculada'],
            $config['medios'] ?? 'all'
        );
    }
}