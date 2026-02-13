<?php

namespace Glory\Manager;

use Glory\Core\GloryLogger;
use Glory\Core\GloryFeatures;
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
    private static array $handlesEstilosAsincronos = [];
    private static bool $asyncStylesEnabled = false; // Flag para CSS asincrono global


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
            'exclude_on' => $config['exclude_on'] ?? [],
        ];
    }

    /* Delega el escaneo de carpetas a FolderScanner (SRP) */
    public static function defineFolder(string $tipo, string $rutaCarpeta, array $configDefault = [], string $prefijoHandle = '', array $exclusiones = []): void
    {
        FolderScanner::scanFolder($tipo, $rutaCarpeta, $configDefault, $prefijoHandle, $exclusiones);
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

    /**
     * Activa la carga asincrona de CSS globalmente.
     * Llamar desde control.php o functions.php del tema.
     * 
     * Ejemplo: AssetManager::enableAsyncStyles();
     */
    public static function enableAsyncStyles(): void
    {
        self::$asyncStylesEnabled = true;
    }

    /**
     * Verifica si CSS asincrono esta habilitado
     */
    public static function isAsyncStylesEnabled(): bool
    {
        return self::$asyncStylesEnabled;
    }

    public static function hacerEstilosAsincronos(string $tag, string $handle): string
    {
        // Si asyncStylesEnabled esta activo, aplicar a TODOS los estilos
        // Si no, solo aplicar a los handles en $handlesEstilosAsincronos
        $shouldMakeAsync = self::$asyncStylesEnabled
            || in_array($handle, self::$handlesEstilosAsincronos, true);

        if (!$shouldMakeAsync) {
            return $tag;
        }

        // Evitar doble procesamiento
        if (strpos($tag, 'onload=') !== false) {
            return $tag;
        }

        $fallback = '<noscript>' . $tag . '</noscript>';
        $tag = str_replace("media='all'", "media='print' onload=\"this.media='all'; this.onload=null;\"", $tag);

        return $tag . $fallback;
    }


    public static function enqueueFrontendAssets(): void
    {
        // Determinar si activar CSS asincrono:
        // 1. Si asyncStylesEnabled esta activo (via enableAsyncStyles())
        // 2. Si la opcion glory_css_async_global esta activa en BD
        $globalAsyncOption = OpcionManager::get('glory_css_async_global');
        $shouldUseAsync = self::$asyncStylesEnabled
            || ($globalAsyncOption === true);

        // Aplicar CSS asincrono si corresponde
        if ($shouldUseAsync) {
            $optAsync = OpcionManager::get('glory_css_critico_async_resto');
            $asyncResto = ($optAsync === null) ? true : (bool) $optAsync;
            if ($asyncResto) {
                add_filter('style_loader_tag', [self::class, 'hacerEstilosAsincronos'], 999, 2);
            }
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

                // Verificar exclusiones por página
                if (!empty($config['exclude_on'])) {
                    $excludedPages = (array) $config['exclude_on'];
                    if (function_exists('is_page') && is_page($excludedPages)) {
                        continue;
                    }
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

                if ($tipo === self::ASSET_TYPE_STYLE && $currentArea === 'frontend') {
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
                    // GloryLogger::info('AssetManager: enqueue estilo', ['handle' => $handle, 'url' => $url]);
                    wp_enqueue_style($handle);
                }
            }
        }
    }


    public static function addDeferAttribute(string $tag, string $handle): string
    {
        // Si asyncStylesEnabled esta activo, aplicar defer a TODOS los scripts del frontend
        // excepto scripts inline o que ya tienen defer
        if (self::$asyncStylesEnabled) {
            // No aplicar a scripts inline o que ya tienen defer/async
            if (strpos($tag, ' defer') !== false || strpos($tag, ' async') !== false) {
                return $tag;
            }
            // Solo aplicar a scripts con src (no inline)
            if (strpos($tag, ' src=') !== false) {
                return str_replace(' src=', ' defer src=', $tag);
            }
        }

        // Fallback: aplicar solo a scripts en $deferredScripts
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
