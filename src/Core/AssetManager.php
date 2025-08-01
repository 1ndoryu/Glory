<?php

namespace Glory\Core;

use Glory\Core\GloryLogger;


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
    private static array $deferredScripts = [];

    private static string $cacheDir = GLORY_FRAMEWORK_PATH . '/cache';


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

        $config['defer'] = $config['defer'] ?? true;
        if ($tipo === self::ASSET_TYPE_SCRIPT && $config['defer']) {
            self::$deferredScripts[] = $handle;
        }

        self::$assets[$tipo][$handle] = [
            'ruta'      => $ruta,
            'deps'      => $config['deps'] ?? [],
            'ver'       => $config['ver'] ?? null,
            'in_footer' => $config['in_footer'] ?? true,
            'media'     => $config['media'] ?? 'all',
            'localize'  => $config['localize'] ?? null,
            'dev_mode'  => $config['dev_mode'] ?? null,
            'area'      => $config['area'] ?? 'frontend',
            'defer'     => $config['defer'],
        ];
    }

    private static function getCacheFilePath(string $cacheKey): string
    {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
        return self::$cacheDir . '/glory_assets_' . md5($cacheKey) . '.php';
    }


    public static function defineFolder(string $tipo, string $rutaCarpeta, array $configDefault = [], string $prefijoHandle = '', array $exclusiones = []): void
    {
        self::$modoDesarrolloGlobal = (defined('WP_DEBUG') && WP_DEBUG);
        $cacheKey = $tipo . $rutaCarpeta . $prefijoHandle . serialize($exclusiones) . serialize(array_keys($configDefault));
        $cacheFile = self::getCacheFilePath($cacheKey);

        if (!self::$modoDesarrolloGlobal && file_exists($cacheFile)) {
            $cachedAssets = include $cacheFile;
            if (is_array($cachedAssets)) {
                foreach ($cachedAssets as $handle => $config) {
                    self::define($tipo, $handle, $config['ruta'], array_merge($configDefault, $config));
                }
                return;
            }
        }

        $extension = ($tipo === self::ASSET_TYPE_SCRIPT) ? 'js' : 'css';
        $directorioTema = wp_normalize_path(get_template_directory());
        $rutaCompleta = rtrim($directorioTema, '/') . '/' . ltrim($rutaCarpeta, '/');

        if (!is_dir($rutaCompleta)) {
            GloryLogger::warning("AssetManager: La carpeta de assets '{$rutaCarpeta}' no fue encontrada en '{$rutaCompleta}'.");
            return;
        }

        $discoveredAssets = [];
        try {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($rutaCompleta, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (strtolower($file->getExtension()) !== strtolower($extension) || in_array($file->getFilename(), $exclusiones, true)) {
                    continue;
                }

                $rutaParaWeb = str_replace($directorioTema, '', wp_normalize_path($file->getPathname()));
                $handle = $prefijoHandle . sanitize_title($file->getBasename('.' . $extension));

                self::define($tipo, $handle, $rutaParaWeb, $configDefault);
                $discoveredAssets[$handle] = ['ruta' => $rutaParaWeb];
            }

            if (!self::$modoDesarrolloGlobal) {
                $cacheContent = '<?php return ' . var_export($discoveredAssets, true) . ';';
                file_put_contents($cacheFile, $cacheContent, LOCK_EX);
            }

        } catch (\Exception $e) {
            GloryLogger::error("AssetManager: Error al iterar la carpeta '{$rutaCompleta}': " . $e->getMessage());
        }
    }


    public static function register(): void
    {
        if (!self::$hooksRegistrados) {
            add_action('wp_enqueue_scripts', [self::class, 'enqueueFrontendAssets'], 20);
            add_action('admin_enqueue_scripts', [self::class, 'enqueueAdminAssets'], 20);
            add_filter('script_loader_tag', [self::class, 'addDeferAttribute'], 10, 2);
            self::$hooksRegistrados = true;
        }
    }


    public static function enqueueFrontendAssets(): void
    {
        self::enqueueForArea('frontend');
    }


    public static function enqueueAdminAssets(): void
    {
        self::enqueueForArea('admin');
    }


    private static function enqueueForArea(string $currentArea): void
    {
        foreach (self::$assets as $tipo => $assetsPorTipo) {
            foreach ($assetsPorTipo as $handle => $config) {
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

                $rutaAsset = $config['ruta'];
                if (!self::$modoDesarrolloGlobal) {
                    $extension = pathinfo($rutaAsset, PATHINFO_EXTENSION);
                    if ($extension === 'js' || $extension === 'css') {
                        $minRuta = preg_replace("/\.$extension$/", ".min.$extension", $rutaAsset);
                        $minRutaFisica = get_template_directory() . $minRuta;
                        if (file_exists(wp_normalize_path($minRutaFisica))) {
                            $rutaAsset = $minRuta;
                        }
                    }
                }

                $rutaFisica = get_template_directory() . $rutaAsset;
                if (!file_exists(wp_normalize_path($rutaFisica))) {
                    GloryLogger::error("AssetManager: Archivo no encontrado '{$rutaAsset}' para el handle '{$handle}'.");
                    continue;
                }

                $version = self::calcularVersion($rutaFisica, $config['ver'], $config['dev_mode']);
                $url = get_template_directory_uri() . $rutaAsset;

                if ($tipo === self::ASSET_TYPE_SCRIPT) {
                    wp_register_script($handle, $url, $config['deps'], $version, $config['in_footer']);
                    if (isset($config['localize']['nombreObjeto'], $config['localize']['datos'])) {
                        wp_localize_script($handle, $config['localize']['nombreObjeto'], $config['localize']['datos']);
                    }
                    wp_enqueue_script($handle);
                } else {
                    wp_register_style($handle, $url, $config['deps'], $version, $config['media']);
                    wp_enqueue_style($handle);
                }
            }
        }
    }


    public static function addDeferAttribute(string $tag, string $handle): string
    {
        if (in_array($handle, self::$deferredScripts, true)) {
            if (strpos($tag, ' defer') === false) {
                return str_replace(' src=', ' defer src=', $tag);
            }
        }
        return $tag;
    }


    public static function setGlobalDevMode(bool $activado): void
    {
        self::$modoDesarrolloGlobal = $activado;
    }
    
    public static function isGlobalDevMode(): bool
    {
        return self::$modoDesarrolloGlobal;
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