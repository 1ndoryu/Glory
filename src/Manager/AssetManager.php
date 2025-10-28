<?php

namespace Glory\Manager;

use Glory\Core\GloryLogger;
use Glory\Core\GloryFeatures;
use Glory\Services\GestorCssCritico;
use Glory\Manager\OpcionManager;


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
    private static ?string $cssCritico = null;
    private static array $handlesEstilosAsincronos = [];

    private static string $cacheDir = GLORY_FRAMEWORK_PATH . '/cache';


    public static function define(string $tipo, string $handle, string $ruta, array $config = []): void
    {
        // Soporte flexible para la clave 'feature': puede ser
        // - string: 'modales'
        // - array asociativo: ['name' => 'modales', 'option' => 'glory_componente_modales_activado']
        // - array indexado: ['modales', 'glory_componente_modales_activado']
        // También se mantiene compatibilidad con 'feature_option' antigua.
        $featureName = null;
        $featureOptionKey = null;
        if (isset($config['feature'])) {
            if (is_array($config['feature'])) {
                $featureName = $config['feature']['name'] ?? ($config['feature'][0] ?? null);
                $featureOptionKey = $config['feature']['option'] ?? ($config['feature'][1] ?? null);
            } else {
                $featureName = (string) $config['feature'];
            }
        }
        // Compatibilidad: si se pasó feature_option por separado
        if (empty($featureOptionKey) && isset($config['feature_option'])) {
            $featureOptionKey = $config['feature_option'];
        }

        if (!empty($featureName) && GloryFeatures::isActive($featureName, $featureOptionKey) === false) {
            return;
        }

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
            'feature'   => $config['feature'] ?? null,
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

    public static function imprimirCssCritico(): void
    {
        // Inicialización perezosa: si aún no está calculado, obtenerlo ahora
        if (self::$cssCritico === null && \Glory\Core\GloryFeatures::isActive('cssCritico', 'glory_css_critico_activado') !== false) {
            self::$cssCritico = \Glory\Services\GestorCssCritico::getParaPaginaActual();
        }
        $bytes = self::$cssCritico ? strlen((string) self::$cssCritico) : 0;
        GloryLogger::info('AssetManager: hook wp_head -> imprimirCssCritico', [ 'has' => self::$cssCritico ? 1 : 0, 'bytes' => $bytes ]);
        if (!empty(self::$cssCritico)) {
            echo '<style id="glory-css-critico">' . self::$cssCritico . '</style>';
        }
    }

    public static function hacerEstilosAsincronos(string $tag, string $handle): string
    {
        if (!in_array($handle, self::$handlesEstilosAsincronos, true)) {
            return $tag;
        }

        GloryLogger::info('AssetManager: marcando estilo async', ['handle' => $handle]);
        $fallback = '<noscript>' . $tag . '</noscript>';
        $tag = str_replace("media='all'", "media='print' onload=\"this.media='all'; this.onload=null;\"", $tag);

        return $tag . $fallback;
    }


    public static function enqueueFrontendAssets(): void
    {
        // Respetar el flag global de GloryFeatures para cssCritico
        if (GloryFeatures::isActive('cssCritico', 'glory_css_critico_activado') === false) {
            self::$cssCritico = null;
        } else {
            self::$cssCritico = GestorCssCritico::getParaPaginaActual();
        }

        if (self::$cssCritico) {
            add_action('wp_head', [self::class, 'imprimirCssCritico'], 1);
            // Controlar si el resto de CSS debe ir asíncrono cuando hay crítico
            // Activado por defecto si la opción no existe; si existe y es false, se desactiva
            $optAsync = OpcionManager::get('glory_css_critico_async_resto');
            $asyncResto = ($optAsync === null) ? true : (bool) $optAsync;
            if ($asyncResto) {
                add_filter('style_loader_tag', [self::class, 'hacerEstilosAsincronos'], 999, 2);
                GloryLogger::info('AssetManager: CSS crítico activo; estilos pasarán a async');
            } else {
                GloryLogger::info('AssetManager: CSS crítico activo; estilos se mantienen síncronos (compatibilidad)');
            }
        } else {
            GloryLogger::info('AssetManager: sin CSS crítico para esta vista');
        }

        self::enqueueForArea('frontend');
    }


    public static function enqueueAdminAssets(): void
    {
        self::enqueueForArea('admin');
    }


    private static function enqueueForArea(string $currentArea): void
    {
        // (Logs temporales eliminados)

        foreach (self::$assets as $tipo => $assetsPorTipo) {
            foreach ($assetsPorTipo as $handle => $config) {
                if ($config['area'] !== 'both' && $config['area'] !== $currentArea) {
                    continue;
                }

                // Si el asset declara explícitamente una feature y esta está desactivada, omitirlo.
                if (!empty($config['feature'])) {
                    $featureName = null;
                    $featureOptionKey = null;
                    if (is_array($config['feature'])) {
                        $featureName = $config['feature']['name'] ?? ($config['feature'][0] ?? null);
                        $featureOptionKey = $config['feature']['option'] ?? ($config['feature'][1] ?? null);
                    } else {
                        $featureName = (string) $config['feature'];
                    }

                    // Compatibilidad con llave antigua
                    if (empty($featureOptionKey) && isset($config['feature_option'])) {
                        $featureOptionKey = $config['feature_option'];
                    }

                    if (!empty($featureName) && GloryFeatures::isActive($featureName, $featureOptionKey) === false) {
                        continue;
                    }
                }

                if ($tipo === self::ASSET_TYPE_STYLE && self::$cssCritico && $currentArea === 'frontend') {
                    self::$handlesEstilosAsincronos[] = $handle;
                }

                $yaRegistrado = ($tipo === self::ASSET_TYPE_SCRIPT)
                    ? wp_script_is($handle, 'registered')
                    : wp_style_is($handle, 'registered');

                if ($yaRegistrado) {
                    if ($tipo === self::ASSET_TYPE_SCRIPT) {
                        wp_enqueue_script($handle);
                    } else {
                        GloryLogger::info('AssetManager: enqueue estilo (registrado)', ['handle' => $handle]);
                        wp_enqueue_style($handle);
                    }
                    continue;
                }

                $rutaAsset = $config['ruta'];

                // (Logs temporales eliminados)

                // Soporte para URLs externas (CDN). Si la ruta es absoluta http(s) o protocol-relative, la usamos tal cual.
                $isExternal = (bool) preg_match('#^https?://#i', $rutaAsset) || strpos($rutaAsset, '//') === 0;

                if (!$isExternal) {
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
                } else {
                    // Ruta externa: no validar existencia en disco y usar la ruta tal cual.
                    $version = $config['ver'] ?? self::$versionTema;
                    $url = $rutaAsset;
                }

                if ($tipo === self::ASSET_TYPE_SCRIPT) {
                    wp_register_script($handle, $url, $config['deps'], $version, $config['in_footer']);
                    if (isset($config['localize']['nombreObjeto'], $config['localize']['datos'])) {
                        wp_localize_script($handle, $config['localize']['nombreObjeto'], $config['localize']['datos']);
                    }
                    wp_enqueue_script($handle);
                } else {
                    wp_register_style($handle, $url, $config['deps'], $version, $config['media']);
                    GloryLogger::info('AssetManager: enqueue estilo', ['handle' => $handle, 'url' => $url]);
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