<?
namespace Glory\Core;

use Glory\Core\GloryLogger;
use Glory\Core\AssetManager;

/**
 * Gestiona la definición, registro y puesta en cola de scripts JavaScript en WordPress.
 * Hereda de AssetManager para funcionalidades comunes de gestión de assets.
 * @author @wandorius
 */
class ScriptManager extends AssetManager
{
    /** @var array Almacena la colección de scripts definidos, aislada de otros tipos de assets. */
    protected static array $assetsDefinidos = [];

    /**
     * Define un script para ser gestionado.
     *
     * @param string $identificador Identificador único para el script (handle).
     * @param string|null $ruta Ruta relativa al archivo del script desde la raíz del tema.
     * @param array $dependencias Array de identificadores de scripts de los que depende.
     * @param string|null $version Versión del script.
     * @param bool $enPiePagina True para cargar el script en el pie de página.
     * @param array|null $datosLocalizacion Datos para localizar con `wp_localize_script`.
     * @param bool|null $modoDesarrollo Define si este script específico debe usar timestamp como versión.
     */
    public static function define(
        string $identificador,
        ?string $ruta = null,
        array $dependencias = [],
        ?string $version = null,
        bool $enPiePagina = true,
        ?array $datosLocalizacion = null,
        ?bool $modoDesarrollo = null
    ): void {
        if (is_null($ruta)) {
            $ruta = 'js/' . $identificador . '.js';
        }

        if (!is_null($datosLocalizacion)) {
            if (empty($datosLocalizacion['nombreObjeto']) || !is_string($datosLocalizacion['nombreObjeto'])) {
                GloryLogger::error("ScriptManager: 'nombreObjeto' inválido o vacío para localización de script '{$identificador}'. Se omite localización.");
                $datosLocalizacion = null;
            } elseif (!isset($datosLocalizacion['datos']) || !is_array($datosLocalizacion['datos'])) {
                GloryLogger::error("ScriptManager: 'datos' inválidos (debe ser array) para localización de script '{$identificador}'. Se omite localización.");
                $datosLocalizacion = null;
            }
        }

        $configuracion = [
            'ruta' => $ruta,
            'dependencias' => $dependencias,
            'version' => $version,
            'enPiePagina' => $enPiePagina,
            'datosLocalizacion' => $datosLocalizacion,
            'modoDesarrollo' => $modoDesarrollo,
            'identificador' => $identificador
        ];

        static::defineAsset($identificador, $configuracion);
    }

    /**
     * Define automáticamente todos los scripts de una carpeta.
     *
     * @param string $rutaRelativaCarpeta Ruta de la carpeta relativa a la raíz del tema.
     * @param array $dependenciasDefault Dependencias por defecto.
     * @param bool $enPiePaginaDefault Si se cargan en el footer por defecto.
     * @param bool|null $modoDesarrolloCarpeta Modo desarrollo para la carpeta.
     * @param string $prefijoIdentificador Prefijo para los handles generados.
     * @param array $archivosExcluidos Archivos a excluir.
     */
    public static function defineFolder(
        string $rutaRelativaCarpeta = 'js',
        array $dependenciasDefault = [],
        bool $enPiePaginaDefault = true,
        ?bool $modoDesarrolloCarpeta = null,
        string $prefijoIdentificador = '',
        array $archivosExcluidos = []
    ): void {
        static::definirDesdeCarpeta(
            'js',
            $rutaRelativaCarpeta,
            $dependenciasDefault,
            $enPiePaginaDefault,
            $modoDesarrolloCarpeta,
            $prefijoIdentificador,
            $archivosExcluidos
        );
    }

    /**
     * Implementación del método abstracto para construir la configuración de un script.
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
            'enPiePagina' => $opcionesDefault ?? true,
            'datosLocalizacion' => null,
            'modoDesarrollo' => $modoDesarrollo,
            'identificador' => $identificador,
        ];
    }

    /**
     * Registra el hook para poner en cola los scripts definidos.
     */
    public static function register(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueItems'], 20);
    }
    
    /**
     * Implementación específica para encolar un script.
     * Este método es llamado por el `enqueueItems` de la clase base.
     */
    protected static function encolarActivoEspecifico(string $handle, array $config, bool $yaEncolado): void
    {
        $datosLocalizacion = $config['datosLocalizacion'] ?? null;

        if ($yaEncolado) {
            if (!empty($datosLocalizacion) && !empty($datosLocalizacion['nombreObjeto']) && is_array($datosLocalizacion['datos'])) {
                wp_localize_script($handle, $datosLocalizacion['nombreObjeto'], $datosLocalizacion['datos']);
            }
            return;
        }

        $registrado = wp_register_script(
            $handle,
            $config['urlArchivo'],
            $config['dependencias'] ?? [],
            $config['versionCalculada'],
            $config['enPiePagina'] ?? true
        );

        if (!$registrado) {
            GloryLogger::error("ScriptManager: wp_register_script devolvió false para '{$handle}'. Se omite.");
            return;
        }

        if (!empty($datosLocalizacion) && !empty($datosLocalizacion['nombreObjeto']) && isset($datosLocalizacion['datos'])) {
            $localizado = wp_localize_script($handle, $datosLocalizacion['nombreObjeto'], $datosLocalizacion['datos']);
            if (!$localizado) {
                GloryLogger::error("ScriptManager: wp_localize_script falló para '{$handle}'.");
            }
        }

        wp_enqueue_script($handle);
    }
}