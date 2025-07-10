<?php

namespace Glory\Core;

use Glory\Core\GloryLogger;

/**
 * Gestor Unificado de Assets (Scripts y Estilos) para WordPress.
 *
 * Esta clase gestiona la definición, registro y puesta en cola de todos los assets,
 * controlando si se cargan en el 'frontend', 'admin', o en 'both'.
 *
 * @author @wandorius (Refactorizado por Gemini)
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
     * Define un nuevo asset (script o estilo) para ser gestionado.
     *
     * @param string $tipo 'script' o 'style'.
     * @param string $handle El identificador (handle) único para el asset.
     * @param string $ruta La ruta relativa al archivo desde la raíz del tema.
     * @param array  $config Configuración adicional:
     * - 'deps' (array): Dependencias.
     * - 'ver' (string|null): Versión. Si es null, se autocalcula.
     * - 'in_footer' (bool): (Scripts) Cargar en el footer.
     * - 'media' (string): (Estilos) Media target.
     * - 'localize' (array|null): (Scripts) Datos para wp_localize_script.
     * - 'dev_mode' (bool|null): Forzar modo desarrollo para este asset.
     * - 'area' (string): Dónde cargar el asset. Opciones: 'frontend' (default), 'admin', 'both'.
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
            'area'      => $config['area'] ?? 'frontend', // <-- Default a 'frontend'
        ];
    }

    /**
     * Define automáticamente todos los assets de una extensión dentro de una carpeta.
     *
     * @param string $tipo 'script' o 'style'.
     * @param string $rutaCarpeta Ruta de la carpeta relativa a la raíz del tema.
     * @param array  $configDefault Configuración por defecto, incluyendo 'area'.
     * @param string $prefijoHandle Prefijo para los handles.
     * @param array  $exclusiones Nombres de archivo a excluir.
     */
    public static function defineFolder(string $tipo, string $rutaCarpeta, array $configDefault = [], string $prefijoHandle = '', array $exclusiones = []): void
    {
        $extension = ($tipo === self::ASSET_TYPE_SCRIPT) ? 'js' : 'css';
        $directorioTema = wp_normalize_path(get_template_directory());
        $rutaCarpetaNormalizada = wp_normalize_path($rutaCarpeta);
        $rutaCompleta = rtrim($directorioTema, '/') . '/' . ltrim($rutaCarpetaNormalizada, '/');

        if (!is_dir($rutaCompleta)) {
            GloryLogger::warning("AssetManager: La carpeta de assets '{$rutaCarpeta}' no fue encontrada en '{$rutaCompleta}'.");
            return;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($rutaCompleta, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (strtolower($file->getExtension()) !== strtolower($extension) || in_array($file->getFilename(), $exclusiones, true)) {
                    continue;
                }

                $rutaParaWeb = str_replace($directorioTema, '', wp_normalize_path($file->getPathname()));
                $handle = $prefijoHandle . sanitize_title($file->getBasename('.' . $extension));
                self::define($tipo, $handle, $rutaParaWeb, $configDefault);
            }
        } catch (\Exception $e) {
            GloryLogger::error("AssetManager: Error al iterar la carpeta '{$rutaCompleta}': " . $e->getMessage());
        }
    }

    /**
     * Registra los hooks de WordPress para encolar los assets en las áreas correctas.
     */
    public static function register(): void
    {
        if (!self::$hooksRegistrados) {
            add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontendAssets'], 20);
            add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets'], 20);
            self::$hooksRegistrados = true;
        }
    }

    /**
     * Callback para encolar los assets del frontend.
     */
    public static function enqueueFrontendAssets(): void
    {
        self::enqueueForArea('frontend');
    }

    /**
     * Callback para encolar los assets del admin.
     */
    public static function enqueueAdminAssets(): void
    {
        self::enqueueForArea('admin');
    }

    /**
     * Lógica principal para encolar assets según el área especificada.
     *
     * @param string $currentArea El área actual ('frontend' o 'admin').
     */
    private static function enqueueForArea(string $currentArea): void
    {
        foreach (self::$assets as $tipo => $assetsPorTipo) {
            foreach ($assetsPorTipo as $handle => $config) {
                // Comprueba si el asset debe cargarse en el área actual
                if ($config['area'] !== 'both' && $config['area'] !== $currentArea) {
                    continue;
                }

                $yaRegistrado = ($tipo === self::ASSET_TYPE_SCRIPT)
                    ? wp_script_is($handle, 'registered')
                    : wp_style_is($handle, 'registered');

                if ($yaRegistrado) {
                    if ($tipo === self::ASSET_TYPE_SCRIPT) {
                        wp_enqueue_script($handle);
                    } else {
                        wp_enqueue_style($handle);
                    }
                    continue;
                }

                $rutaFisica = get_template_directory() . $config['ruta'];
                if (!file_exists(wp_normalize_path($rutaFisica))) {
                    GloryLogger::error("AssetManager: Archivo no encontrado '{$config['ruta']}' para el handle '{$handle}'.");
                    continue;
                }

                $version = self::calcularVersion($rutaFisica, $config['ver'], $config['dev_mode']);
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
