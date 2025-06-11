<?
namespace Glory\Core;

use Glory\Core\GloryLogger;

/**
 * Gestor Unificado de Assets (Scripts y Estilos) para WordPress.
 *
 * Esta clase reemplaza la jerarquía anterior de AssetManager, ScriptManager y StyleManager.
 * Gestiona la definición, registro, localización y puesta en cola de todos los assets
 * (CSS y JS) desde un único punto, eliminando la duplicación de código y simplificando la API.
 *
 * @author @wandorius 
 */
final class AssetManager
{
    private const ASSET_TYPE_SCRIPT = 'script';
    private const ASSET_TYPE_STYLE  = 'style';

    private static array $assets = [
        self::ASSET_TYPE_SCRIPT => [],
        self::ASSET_TYPE_STYLE  => [],
    ];
    private static bool $modoDesarrolloGlobal = false;
    private static string $versionTema = '1.0.0';
    private static bool $hooksRegistrados = false;

    /**
     * Define un nuevo asset (script o estilo) para ser gestionado por el framework.
     *
     * @param string $tipo 'script' o 'style'.
     * @param string $handle El identificador (handle) único para el asset.
     * @param string $ruta La ruta relativa al archivo desde la raíz del tema (ej. '/Assets/js/mi-script.js').
     * @param array  $config Configuración adicional para el asset:
     * - 'deps' (array): Dependencias.
     * - 'ver' (string|null): Versión específica. Si es null, se autocalcula.
     * - 'in_footer' (bool): (Solo para scripts) Cargar en el footer.
     * - 'media' (string): (Solo para estilos) El media target.
     * - 'localize' (array|null): (Solo para scripts) Datos para wp_localize_script.
     * Debe ser un array con 'nombreObjeto' y 'datos'.
     * - 'dev_mode' (bool|null): Forzar modo desarrollo para este asset.
     */
    public static function define(string $tipo, string $handle, string $ruta, array $config = []): void
    {
        if ($tipo !== self::ASSET_TYPE_SCRIPT && $tipo !== self::ASSET_TYPE_STYLE) {
            GloryLogger::error("AssetManager: Tipo de asset '{$tipo}' inválido para '{$handle}'.");
            return;
        }

        if (empty($handle) || empty($ruta)) {
            GloryLogger::error("AssetManager: El handle y la ruta no pueden estar vacíos para el tipo '{$tipo}'.");
            return;
        }

        if (isset($config['localize']) && $tipo === self::ASSET_TYPE_STYLE) {
            GloryLogger::warning("AssetManager: La opción 'localize' no es aplicable a estilos. Omitiendo para '{$handle}'.");
            unset($config['localize']);
        }

        self::$assets[$tipo][$handle] = [
            'ruta'      => $ruta,
            'deps'      => $config['deps'] ?? [],
            'ver'       => $config['ver'] ?? null,
            'in_footer' => $config['in_footer'] ?? true,
            'media'     => $config['media'] ?? 'all',
            'localize'  => $config['localize'] ?? null,
            'dev_mode'  => $config['dev_mode'] ?? null,
        ];
    }

    /**
     * Define automáticamente todos los assets de una extensión específica dentro de una carpeta.
     *
     * @param string $tipo 'script' o 'style'.
     * @param string $rutaCarpeta Ruta de la carpeta relativa a la raíz del tema.
     * @param array  $configDefault Configuración por defecto para los assets de la carpeta.
     * @param string $prefijoHandle Prefijo para los handles generados.
     * @param array  $exclusiones Nombres de archivo a excluir.
     */
    public static function defineFolder(string $tipo, string $rutaCarpeta, array $configDefault = [], string $prefijoHandle = '', array $exclusiones = []): void
    {
        $extension = ($tipo === self::ASSET_TYPE_SCRIPT) ? 'js' : 'css';
        $directorioTema = get_template_directory();
        $rutaCompleta = $directorioTema . $rutaCarpeta;

        if (!is_dir($rutaCompleta)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($rutaCompleta, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->getExtension() !== $extension || in_array($file->getFilename(), $exclusiones, true)) {
                continue;
            }

            $handle = $prefijoHandle . sanitize_title($file->getBasename('.' . $extension));
            $rutaRelativa = $rutaCarpeta . str_replace($rutaCompleta, '', $file->getPathname());

            self::define($tipo, $handle, $rutaRelativa, $configDefault);
        }
    }
    
    /**
     * Pone en cola todos los assets definidos. Se engancha a 'wp_enqueue_scripts'.
     */
    public static function enqueueAssets(): void
    {
        foreach (self::$assets as $tipo => $assetsPorTipo) {
            foreach ($assetsPorTipo as $handle => $config) {
                
                $rutaCompleta = get_template_directory() . $config['ruta'];
                if (!file_exists($rutaCompleta)) {
                    GloryLogger::error("AssetManager: Archivo no encontrado '{$config['ruta']}' para el handle '{$handle}'.");
                    continue;
                }

                $version = self::calcularVersion($rutaCompleta, $config['ver'], $config['dev_mode']);
                $url = get_template_directory_uri() . $config['ruta'];

                if ($tipo === self::ASSET_TYPE_SCRIPT) {
                    wp_register_script($handle, $url, $config['deps'], $version, $config['in_footer']);
                    if (isset($config['localize']['nombreObjeto'], $config['localize']['datos'])) {
                        wp_localize_script($handle, $config['localize']['nombreObjeto'], $config['localize']['datos']);
                    }
                    wp_enqueue_script($handle);
                } else { // 'style'
                    wp_enqueue_style($handle, $url, $config['deps'], $version, $config['media']);
                }
            }
        }
    }

    /**
     * Registra los hooks de WordPress para encolar los assets.
     */
    public static function register(): void
    {
        if (!self::$hooksRegistrados) {
            add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets'], 20);
            add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets'], 20);
            self::$hooksRegistrados = true;
        }
    }

    public static function setGlobalDevMode(bool $activado): void
    {
        self::$modoDesarrolloGlobal = $activado;
    }

    public static function setThemeVersion(string $version): void
    {
        self::$versionTema = $version;
    }

    private static function calcularVersion(string $rutaArchivo, ?string $versionEspecifica, ?bool $modoDevEspecifico): string
    {
        if ($versionEspecifica !== null) {
            return $versionEspecifica;
        }

        $esDesarrollo = $modoDevEspecifico ?? self::$modoDesarrolloGlobal;

        if ($esDesarrollo) {
            $tiempoModificacion = @filemtime($rutaArchivo);
            return $tiempoModificacion ? (string)$tiempoModificacion : self::$versionTema;
        }

        return self::$versionTema;
    }
}