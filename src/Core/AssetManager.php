<?php
namespace Glory\Core;

use Glory\Core\GloryLogger;

/**
 * Clase base abstracta para la gestión de assets (CSS, JS).
 *
 * Proporciona funcionalidades comunes para definir, registrar y versionar assets,
 * permitiendo a las clases hijas (ScriptManager, StyleManager) implementar
 * la lógica específica de encolado y registro en WordPress.
 *
 * // @tarea Jules: Considerar la posibilidad de que esta clase AssetManager pueda ser inyectada
 * // o extendida para soportar diferentes fuentes de assets (CDN, etc.) en el futuro.
 * @author @wandorius
 */
abstract class AssetManager
{
    /** @var array Almacena la colección de assets definidos (scripts o estilos). */
    protected static array $assetsDefinidos = [];
    /** @var bool Estado global del modo desarrollo. Afecta el versionamiento de assets. */
    protected static bool $modoDesarrolloGlobal = false;
    /** @var string Versión por defecto para los assets, usualmente la versión del tema/plugin. */
    protected static string $versionTema = '1.0.0';

    /**
     * Establece el modo de desarrollo global.
     * Si está activado, los assets sin versión específica usarán el timestamp del archivo
     * como versión para evitar problemas de caché, si la clase hija lo implementa así.
     *
     * @param bool $activado True para activar el modo desarrollo global, false para desactivar.
     */
    public static function setGlobalDevMode(bool $activado): void
    {
        self::$modoDesarrolloGlobal = $activado;
    }

    /**
     * Establece la versión por defecto del tema o plugin.
     * Esta versión se usa para los assets que no especifican una propia y no están en modo desarrollo.
     *
     * @param string $version La versión a establecer (ej: '1.2.3').
     */
    public static function setThemeVersion(string $version): void
    {
        self::$versionTema = $version;
    }

    /**
     * Define un asset genérico (script o estilo).
     * Las clases hijas deben llamar a este método, proveyendo la configuración específica.
     *
     * @param string $identificador Identificador único para el asset.
     * @param array $configuracion Array asociativo con la configuración del asset.
     *                              Debe incluir al menos 'ruta'.
     * @return void
     */
    protected static function defineAsset(string $identificador, array $configuracion): void
    {
        if (empty($identificador)) {
            GloryLogger::error("El identificador del asset no puede estar vacío. Definición omitida.");
            return;
        }

        if (isset(self::$assetsDefinidos[$identificador])) {
            // Permitir redefinición silenciosa o loguear una advertencia si se prefiere.
            // Por ahora, la última definición prevalece.
            // GloryLogger::warning("El asset '{$identificador}' está siendo redefinido.");
        }

        // Asegurar que la ruta esté presente
        if (empty($configuracion['ruta'])) {
            GloryLogger::error("La ruta del asset '{$identificador}' no puede estar vacía. Definición omitida.");
            return;
        }

        self::$assetsDefinidos[$identificador] = $configuracion;
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
            if (trim($rutaRelativaCarpetaNormalizada, DIRECTORY_SEPARATOR) !== $extension) { // Compara con la extensión por si es la carpeta default
                GloryLogger::warning("AssetManager: Carpeta no encontrada en {$rutaCompletaCarpeta} al definir assets desde carpeta '{$rutaRelativaCarpeta}' con extensión '.{$extension}'.");
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
                    GloryLogger::error("AssetManager: El identificador generado está vacío para el archivo '{$nombreArchivoConExtension}' en la carpeta '{$rutaRelativaCarpeta}'. Omitiendo.");
                    continue;
                }

                // Las clases hijas deben implementar un método 'construirConfiguracionDesdeCarpeta'
                // o llamar a su propio 'define' aquí con los parámetros correctos.
                // Este es un punto que necesita ser abstracto o manejado por la clase hija.
                // Por ahora, dejaremos que la clase hija ensamble la configuración y llame a defineAsset.
                 $config = static::construirConfiguracionAssetDesdeCarpeta(
                    $rutaRelativaWeb,
                    $dependenciasDefault,
                    $opcionesDefault,
                    $modoDesarrolloCarpeta,
                    $identificador // Pasa el identificador para referencia si es necesario
                );

                if ($config) {
                     static::defineAsset($identificador, $config);
                }
            }
        } catch (\Exception $e) {
            GloryLogger::error("AssetManager: Error al iterar la carpeta {$rutaCompletaCarpeta} para la extensión '.{$extension}': " . $e->getMessage());
        }
    }

    /**
     * Método abstracto que las clases hijas deben implementar para construir la configuración
     * específica del asset (script o estilo) cuando se define desde una carpeta.
     */
    protected abstract static function construirConfiguracionAssetDesdeCarpeta(
        string $rutaRelativaWeb,
        array $dependenciasDefault,
        mixed $opcionesDefault,
        ?bool $modoDesarrollo,
        string $identificador
    ): array;


    /**
     * Método abstracto para que las clases hijas registren sus assets con WordPress.
     */
    public abstract static function register(): void;

    /**
     * Método abstracto para que las clases hijas encolen sus assets.
     */
    protected abstract static function enqueueItems(): void;

    /**
     * Calcula la versión de un asset.
     * Si el modo desarrollo está activo y no hay versión específica, usa el timestamp del archivo.
     *
     * @param string $rutaArchivoFisico Ruta física completa al archivo del asset.
     * @param string|null $versionEspecifica Versión específica del asset definida por el usuario.
     * @param bool|null $modoDesarrolloEspecifico Modo desarrollo específico para este asset.
     * @return string La versión calculada para el asset.
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
}
