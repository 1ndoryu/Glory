<?php

namespace Glory\Utility;

use Glory\Core\GloryLogger;

/**
 * Listado, selección aleatoria y renderizado de imágenes desde los alias de assets.
 * Provee utilidades para obtener listas de imágenes, seleccionar al azar
 * y generar etiquetas <img> o URLs.
 */
class AssetLister
{
    /**
     * Retorna el nombre de un archivo de imagen aleatoria que coincida con "default*"
     * dentro del alias indicado.
     */
    public static function getRandomDefaultImageName(string $alias = 'glory'): ?string
    {
        AssetResolver::init();

        if (!AssetResolver::hasAlias($alias)) {
            GloryLogger::error("AssetLister: La ruta con alias '{$alias}' para imágenes aleatorias no está registrada.");
            return null;
        }

        $assetPaths = AssetResolver::getAssetPaths();
        $directorioImagenes = get_template_directory() . '/' . $assetPaths[$alias] . '/';
        $patronBusqueda = $directorioImagenes . 'default*.{jpg,jpeg,png,gif,webp}';
        $archivos = glob($patronBusqueda, GLOB_BRACE);

        if (empty($archivos)) {
            GloryLogger::warning("AssetLister: No se encontraron imágenes por defecto con el patrón '{$patronBusqueda}'.");
            return null;
        }

        return basename($archivos[array_rand($archivos)]);
    }


    /**
     * Retorna un arreglo de nombres de archivo (basename) de imágenes aleatorias y únicas
     * dentro del alias de assets indicado. Útil para sembrar contenido sin repetición.
     */
    public static function getRandomUniqueImagesFromAlias(
        string $alias,
        int $cantidad,
        array $extensiones = ['jpg', 'jpeg', 'png', 'gif', 'webp']
    ): array {
        AssetResolver::init();

        if (!AssetResolver::hasAlias($alias)) {
            GloryLogger::error("AssetLister: Alias '{$alias}' no registrado para selección aleatoria.");
            return [];
        }

        $assetPaths = AssetResolver::getAssetPaths();
        $directorioImagenes = trailingslashit(get_template_directory() . '/' . $assetPaths[$alias]);

        $archivos = [];
        foreach ($extensiones as $ext) {
            $glob = glob($directorioImagenes . '*.' . $ext, GLOB_NOSORT);
            if (is_array($glob)) {
                $archivos = array_merge($archivos, $glob);
            }
        }

        if (empty($archivos)) {
            GloryLogger::warning("AssetLister: No se encontraron imágenes en '{$directorioImagenes}'.");
            return [];
        }

        shuffle($archivos);
        $seleccionados = array_slice($archivos, 0, max(0, $cantidad));
        return array_values(array_map('basename', $seleccionados));
    }


    /**
     * Lista todas las imágenes disponibles para un alias dado, retornando solo basenames.
     * Ordena alfabéticamente para selección determinística.
     */
    public static function listImagesForAlias(
        string $alias,
        array $extensiones = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']
    ): array {
        AssetResolver::init();

        if (!AssetResolver::hasAlias($alias)) {
            GloryLogger::error("AssetLister: Alias '{$alias}' no registrado para listado de imágenes.");
            return [];
        }

        $assetPaths = AssetResolver::getAssetPaths();
        $directorioImagenes = trailingslashit(get_template_directory() . '/' . $assetPaths[$alias]);
        $archivos = [];
        foreach ($extensiones as $ext) {
            $glob = glob($directorioImagenes . '*.' . $ext, GLOB_NOSORT);
            if (is_array($glob)) {
                foreach ($glob as $ruta) {
                    if (is_file($ruta)) {
                        $archivos[] = basename($ruta);
                    }
                }
            }
        }

        if (empty($archivos)) {
            return [];
        }

        sort($archivos, SORT_NATURAL | SORT_FLAG_CASE);
        return array_values($archivos);
    }


    /**
     * Lista imágenes para un alias filtrando por tamaño mínimo en bytes.
     * Si no hay suficientes, retorna sin filtrar.
     */
    public static function listImagesForAliasWithMinSize(
        string $alias,
        int $minBytes = 0,
        array $extensiones = ['jpg', 'jpeg', 'png', 'gif', 'webp']
    ): array {
        $lista = self::listImagesForAlias($alias, $extensiones);
        if (empty($lista) || $minBytes <= 0) {
            return $lista;
        }
        $assetPaths = AssetResolver::getAssetPaths();
        $dir = trailingslashit(get_template_directory() . '/' . ($assetPaths[$alias] ?? ''));
        $filtradas = [];
        foreach ($lista as $nombre) {
            $ruta = $dir . $nombre;
            if (is_file($ruta)) {
                try {
                    $size = (int) filesize($ruta);
                } catch (\Throwable $e) {
                    $size = 0;
                }
                if ($size >= $minBytes) {
                    $filtradas[] = $nombre;
                }
            }
        }
        return !empty($filtradas) ? $filtradas : $lista;
    }


    /**
     * Selecciona N imágenes aleatorias del alias con filtro de tamaño mínimo opcional.
     */
    public static function pickRandomImages(
        string $alias,
        int $cantidad,
        int $minBytes = 0,
        array $extensiones = ['jpg', 'jpeg', 'png', 'gif', 'webp']
    ): array {
        $pool = $minBytes > 0
            ? self::listImagesForAliasWithMinSize($alias, $minBytes, $extensiones)
            : self::listImagesForAlias($alias, $extensiones);
        if (empty($pool)) {
            return [];
        }
        shuffle($pool);
        return array_slice($pool, 0, max(0, $cantidad));
    }


    /**
     * Renderiza directamente una etiqueta <img> para el asset referido.
     */
    public static function imagen(string $assetReference, array $atributos = []): void
    {
        AssetResolver::init();

        list($alias, $nombreArchivo) = AssetResolver::parseAssetReference($assetReference);
        $rutaRelativa = AssetResolver::resolveAssetPath($alias, $nombreArchivo);

        if (!$rutaRelativa) {
            return;
        }

        $rutaLocal = get_template_directory() . '/' . $rutaRelativa;
        $urlBase = get_template_directory_uri() . '/' . $rutaRelativa;

        $ancho = $alto = null;
        if (file_exists($rutaLocal)) {
            try {
                $dimensiones = getimagesize($rutaLocal);
            } catch (\Throwable $e) {
                $dimensiones = false;
            }
            if ($dimensiones !== false) {
                [$ancho, $alto] = $dimensiones;
            }
        }

        $urlFinal = function_exists('jetpack_photon_url') ? jetpack_photon_url($urlBase) : $urlBase;

        if (!isset($atributos['alt'])) {
            $atributos['alt'] = ucwords(str_replace(['-', '_'], ' ', pathinfo($nombreArchivo, PATHINFO_FILENAME)));
        }

        if ($ancho && $alto) {
            if (!isset($atributos['width'])) {
                $atributos['width'] = $ancho;
            }
            if (!isset($atributos['height'])) {
                $atributos['height'] = $alto;
            }
        }

        $atributosString = '';
        foreach ($atributos as $clave => $valor) {
            $atributosString .= sprintf(' %s="%s"', esc_attr($clave), esc_attr($valor));
        }

        printf('<img src="%s"%s>', esc_url($urlFinal), $atributosString);
    }


    /**
     * Retorna la URL pública del asset referido, o null si no existe.
     * Evita Jetpack Photon para SVG.
     */
    public static function imagenUrl(string $assetReference): ?string
    {
        AssetResolver::init();

        list($alias, $nombreArchivo) = AssetResolver::parseAssetReference($assetReference);
        $rutaRelativa = AssetResolver::resolveActualRelativeAssetPath($alias, $nombreArchivo)
            ?: AssetResolver::resolveAssetPath($alias, $nombreArchivo);

        if (!$rutaRelativa) {
            return null;
        }

        $rutaLocal = get_template_directory() . '/' . $rutaRelativa;
        if (!file_exists($rutaLocal)) {
            return null;
        }

        $urlBase = get_template_directory_uri() . '/' . $rutaRelativa;
        $ext = strtolower((string) pathinfo($rutaRelativa, PATHINFO_EXTENSION));
        /* Evitar Jetpack Photon para SVG (no soportado y puede romper URLs) */
        if ($ext === 'svg') {
            $urlFinal = $urlBase;
        } else {
            $urlFinal = function_exists('jetpack_photon_url') ? jetpack_photon_url($urlBase) : $urlBase;
        }

        return esc_url($urlFinal);
    }
}
