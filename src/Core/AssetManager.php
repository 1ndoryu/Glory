<?
namespace Glory\Core;

use Glory\Core\GloryLogger;

/**
 * Clase base abstracta para la gestión de assets (CSS, JS).
 *
 * Proporciona funcionalidades comunes para definir, registrar y versionar assets,
 * permitiendo a las clases hijas (ScriptManager, StyleManager) implementar
 * la lógica específica de encolado y registro en WordPress.
 *
 * @author @wandorius
 */
abstract class AssetManager
{
    /** @var array Almacena la colección de assets definidos (scripts o estilos). Cada clase hija tendrá su propia instancia. */
    protected static array $assetsDefinidos = [];
    /** @var bool Estado global del modo desarrollo. Afecta el versionamiento de assets. */
    protected static bool $modoDesarrolloGlobal = false;
    /** @var string Versión por defecto para los assets, usualmente la versión del tema/plugin. */
    protected static string $versionTema = '1.0.0';

    /**
     * Define un asset genérico (script o estilo).
     * Las clases hijas deben llamar a este método, proveyendo la configuración específica.
     *
     * @param string $identificador Identificador único para el asset.
     * @param array $configuracion Array asociativo con la configuración del asset.
     * Debe incluir al menos 'ruta'.
     * @return void
     */
    protected static function defineAsset(string $identificador, array $configuracion): void
    {
        if (empty($identificador)) {
            GloryLogger::error("AssetManager: El identificador del asset no puede estar vacío. Definición omitida.");
            return;
        }

        if (isset(static::$assetsDefinidos[$identificador])) {
            // Permitir redefinición silenciosa o loguear una advertencia si se prefiere.
        }

        if (empty($configuracion['ruta'])) {
            GloryLogger::error("AssetManager: La ruta del asset '{$identificador}' no puede estar vacía. Definición omitida.");
            return;
        }

        static::$assetsDefinidos[$identificador] = $configuracion;
    }

    /**
     * Define automáticamente todos los archivos de una extensión específica dentro de una carpeta.
     *
     * @param string $extension Extensión del archivo a buscar (ej. 'js', 'css').
     * @param string $rutaRelativaCarpeta Ruta de la carpeta relativa a la raíz del tema.
     * @param array $dependenciasDefault Dependencias por defecto para los assets de esta carpeta.
     * @param mixed $opcionesDefault Opciones específicas del tipo de asset (ej. $enPiePaginaDefault para JS, $mediosDefault para CSS).
     * @param bool|null $modoDesarrolloCarpeta Modo desarrollo para todos los assets de esta carpeta.
     * @param string $prefijoIdentificador Prefijo a añadir a los identificadores generados.
     * @param array $archivosExcluidos Array de nombres de archivo a excluir.
     * @return void
     */
    protected static function definirDesdeCarpeta(
        string $extension,
        string $rutaRelativaCarpeta,
        array $dependenciasDefault = [],
        mixed $opcionesDefault = null, // Para enPiePagina/medios
        ?bool $modoDesarrolloCarpeta = null,
        string $prefijoIdentificador = '',
        array $archivosExcluidos = []
    ): void {
        $directorioTema = get_template_directory();
        $rutaRelativaCarpetaNormalizada = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $rutaRelativaCarpeta);
        $rutaCompletaCarpeta = rtrim($directorioTema, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($rutaRelativaCarpetaNormalizada, DIRECTORY_SEPARATOR);

        if (!is_dir($rutaCompletaCarpeta)) {
            if (trim($rutaRelativaCarpetaNormalizada, DIRECTORY_SEPARATOR) !== $extension) {
                GloryLogger::warning("AssetManager: Carpeta no encontrada en '{$rutaCompletaCarpeta}' al definir assets desde '{$rutaRelativaCarpeta}' con extensión '.{$extension}'.");
            }
            return;
        }

        try {
            $directoryIterator = new \RecursiveDirectoryIterator($rutaCompletaCarpeta, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
            $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($iterator as $fileInfo) {
                if (strtolower($fileInfo->getExtension()) !== strtolower($extension)) {
                    continue;
                }

                $nombreArchivoConExtension = $fileInfo->getFilename();
                if (in_array($nombreArchivoConExtension, $archivosExcluidos)) {
                    continue;
                }

                $rutaRelativaWeb = ltrim(str_replace([$directorioTema, DIRECTORY_SEPARATOR], ['', '/'], $fileInfo->getPathname()), '/');

                $subPath = ltrim(str_replace($rutaCompletaCarpeta, '', $fileInfo->getPath()), DIRECTORY_SEPARATOR);
                $baseName = $fileInfo->getBasename('.' . $extension);
                $pathForId = !empty($subPath) ? str_replace(DIRECTORY_SEPARATOR, '-', $subPath) . '-' : '';
                $identificadorCrudo = $prefijoIdentificador . $pathForId . $baseName;
                $identificador = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $identificadorCrudo));
                $identificador = trim($identificador, '-');

                if (empty($identificador)) {
                    GloryLogger::error("AssetManager: Identificador generado vacío para archivo '{$nombreArchivoConExtension}' en carpeta '{$rutaRelativaCarpeta}'. Se omite.");
                    continue;
                }
                
                 $config = static::construirConfiguracionAssetDesdeCarpeta(
                    $rutaRelativaWeb,
                    $dependenciasDefault,
                    $opcionesDefault,
                    $modoDesarrolloCarpeta,
                    $identificador 
                );

                if ($config) {
                     static::defineAsset($identificador, $config);
                }
            }
        } catch (\Exception $e) {
            GloryLogger::error("AssetManager: Error al iterar carpeta '{$rutaCompletaCarpeta}' para extensión '.{$extension}': " . $e->getMessage());
        }
    }
    
    /**
     * Pone en cola todos los assets definidos para la clase hija (scripts o estilos).
     * Este método contiene la lógica común y no debe ser sobrescrito (es final).
     * Llama al método abstracto `encolarActivoEspecifico` para el encolado real.
     */
    final public static function enqueueItems(): void
    {
        if (empty(static::$assetsDefinidos)) {
            return;
        }

        foreach (static::$assetsDefinidos as $handle => $config) {
            if (wp_script_is($handle, 'enqueued') || wp_style_is($handle, 'enqueued')) {
                static::encolarActivoEspecifico($handle, $config, true); // Manejar localización si ya está en cola
                continue;
            }

            $rutaRelativa = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $config['ruta']), '/');
            $rutaArchivoFisico = get_template_directory() . '/' . $rutaRelativa;

            if (!file_exists($rutaArchivoFisico)) {
                GloryLogger::error(static::class . ": Archivo '{$rutaArchivoFisico}' (handle: '{$handle}') no encontrado. Se omite.");
                continue;
            }

            $config['versionCalculada'] = static::calcularVersionAsset(
                $rutaArchivoFisico,
                $config['version'] ?? null,
                $config['modoDesarrollo'] ?? null
            );

            $config['urlArchivo'] = get_template_directory_uri() . '/' . $rutaRelativa;
            
            static::encolarActivoEspecifico($handle, $config, false);
        }
    }

    /**
     * Establece el modo de desarrollo global.
     */
    public static function setGlobalDevMode(bool $activado): void
    {
        self::$modoDesarrolloGlobal = $activado;
    }

    /**
     * Establece la versión por defecto del tema o plugin.
     */
    public static function setThemeVersion(string $version): void
    {
        self::$versionTema = $version;
    }

    /**
     * Calcula la versión de un asset.
     */
    protected static function calcularVersionAsset(string $rutaArchivoFisico, ?string $versionEspecifica, ?bool $modoDesarrolloEspecifico): string
    {
        if (!is_null($versionEspecifica)) {
            return $versionEspecifica;
        }

        $esDesarrollo = $modoDesarrolloEspecifico ?? self::$modoDesarrolloGlobal;

        if ($esDesarrollo) {
            $tiempoModificacion = @filemtime($rutaArchivoFisico);
            if ($tiempoModificacion) {
                return (string)$tiempoModificacion;
            }
        }
        return self::$versionTema;
    }

    /**
     * Método abstracto que las clases hijas deben implementar para construir la configuración
     * específica del asset (script o estilo) cuando se define desde una carpeta.
     */
    abstract protected static function construirConfiguracionAssetDesdeCarpeta(
        string $rutaRelativaWeb,
        array $dependenciasDefault,
        mixed $opcionesDefault,
        ?bool $modoDesarrollo,
        string $identificador
    ): array;

    /**
     * Método abstracto que las clases hijas deben implementar.
     * Contiene la lógica específica para registrar y encolar un tipo de asset (script o estilo).
     *
     * @param string $handle El identificador del asset.
     * @param array $config La configuración completa del asset, incluyendo 'urlArchivo' y 'versionCalculada'.
     * @param bool $yaEncolado Indica si el asset ya estaba encolado (útil para `wp_localize_script`).
     */
    abstract protected static function encolarActivoEspecifico(string $handle, array $config, bool $yaEncolado): void;

    /**
     * Método abstracto para que las clases hijas registren sus assets con WordPress.
     */
    abstract public static function register(): void;
}