<?php

namespace Glory\Manager;

use Glory\Core\GloryLogger;

/*
 * Responsable de escanear carpetas del tema para descubrir assets
 * y delegarlos a AssetManager::define(). Extraído de AssetManager
 * para cumplir SRP.
 */
final class FolderScanner
{
    public const ASSET_TYPE_SCRIPT = 'script';
    public const ASSET_TYPE_STYLE  = 'style';

    private static string $cacheDir = GLORY_FRAMEWORK_PATH . '/cache';


    private static function getCacheFilePath(string $cacheKey): string
    {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
        return self::$cacheDir . '/glory_assets_' . md5($cacheKey) . '.php';
    }


    public static function scanFolder(string $tipo, string $rutaCarpeta, array $configDefault = [], string $prefijoHandle = '', array $exclusiones = []): void
    {
        AssetManager::setGlobalDevMode(defined('WP_DEBUG') && WP_DEBUG);
        $modoDesarrollo = AssetManager::isGlobalDevMode();

        $cacheKey = $tipo . $rutaCarpeta . $prefijoHandle . serialize($exclusiones) . serialize(array_keys($configDefault));
        $cacheFile = self::getCacheFilePath($cacheKey);

        if (!$modoDesarrollo && file_exists($cacheFile)) {
            $cachedAssets = include $cacheFile;
            if (is_array($cachedAssets)) {
                foreach ($cachedAssets as $handle => $config) {
                    AssetManager::define($tipo, $handle, $config['ruta'], array_merge($configDefault, $config));
                }
                return;
            }
        }

        $extension = ($tipo === self::ASSET_TYPE_SCRIPT) ? 'js' : 'css';
        $directorioTema = wp_normalize_path(get_template_directory());
        $rutaCompleta = rtrim($directorioTema, '/') . '/' . ltrim($rutaCarpeta, '/');

        if (!is_dir($rutaCompleta)) {
            GloryLogger::warning("FolderScanner: La carpeta de assets '{$rutaCarpeta}' no fue encontrada en '{$rutaCompleta}'.");
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

                AssetManager::define($tipo, $handle, $rutaParaWeb, $configDefault);
                $discoveredAssets[$handle] = ['ruta' => $rutaParaWeb];
            }

            if (!$modoDesarrollo) {
                $cacheContent = '<?php return ' . var_export($discoveredAssets, true) . ';';
                file_put_contents($cacheFile, $cacheContent, LOCK_EX);
            }
        } catch (\Exception $e) {
            GloryLogger::error("FolderScanner: Error al iterar la carpeta '{$rutaCompleta}': " . $e->getMessage());
        }
    }
}
