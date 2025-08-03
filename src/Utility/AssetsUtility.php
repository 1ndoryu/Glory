<?php

namespace Glory\Utility;

use Glory\Core\GloryLogger;
use Glory\Manager\AssetManager;


class AssetsUtility
{

    private static array $assetPaths = [];

    private static bool $isInitialized = false;


    public static function init(): void
    {
        if (self::$isInitialized) {
            return;
        }
        self::registerAssetPath('glory', 'Glory/assets/images');
        self::registerAssetPath('elements', 'Glory/assets/images/elements');
        self::registerAssetPath('tema', 'App/assets/images');
        self::$isInitialized = true;
    }


    public static function registerAssetPath(string $alias, string $path): void
    {
        self::$assetPaths[sanitize_key($alias)] = trim($path, '/\\');
    }


    public static function parseAssetReference(string $reference): array
    {
        if (strpos($reference, '::') !== false) {
            return explode('::', $reference, 2);
        }
        return ['glory', $reference];
    }


    private static function resolveAssetPath(string $alias, string $nombreArchivo): ?string
    {
        if (!isset(self::$assetPaths[$alias])) {
            GloryLogger::warning("AssetsUtility: El alias de ruta '{$alias}' no está registrado.");
            return null;
        }
        return self::$assetPaths[$alias] . '/' . ltrim($nombreArchivo, '/\\');
    }


    public static function getRandomDefaultImageName(string $alias = 'glory'): ?string
    {
        if (!self::$isInitialized) {
            self::init();
        }

        if (!isset(self::$assetPaths[$alias])) {
            GloryLogger::error("AssetsUtility: La ruta con alias '{$alias}' para imágenes aleatorias no está registrada.");
            return null;
        }

        $directorioImagenes = get_template_directory() . '/' . self::$assetPaths[$alias] . '/';
        $patronBusqueda = $directorioImagenes . 'default*.{jpg,jpeg,png,gif,webp}';
        $archivos = glob($patronBusqueda, GLOB_BRACE);

        if (empty($archivos)) {
            GloryLogger::warning("AssetsUtility: No se encontraron imágenes por defecto con el patrón '{$patronBusqueda}'.");
            return null;
        }

        return basename($archivos[array_rand($archivos)]);
    }


    public static function imagen(string $assetReference, array $atributos = []): void
    {
        if (!self::$isInitialized) self::init();

        list($alias, $nombreArchivo) = self::parseAssetReference($assetReference);
        $rutaRelativa = self::resolveAssetPath($alias, $nombreArchivo);

        if (!$rutaRelativa) return;

        $rutaLocal = get_template_directory() . '/' . $rutaRelativa;
        $urlBase = get_template_directory_uri() . '/' . $rutaRelativa;

        $ancho = $alto = null;
        if (file_exists($rutaLocal)) {
            $dimensiones = @getimagesize($rutaLocal);
            if ($dimensiones !== false) {
                [$ancho, $alto] = $dimensiones;
            }
        }

        $urlFinal = function_exists('jetpack_photon_url') ? jetpack_photon_url($urlBase) : $urlBase;

        if (!isset($atributos['alt'])) {
            $atributos['alt'] = ucwords(str_replace(['-', '_'], ' ', pathinfo($nombreArchivo, PATHINFO_FILENAME)));
        }

        if ($ancho && $alto) {
            if (!isset($atributos['width'])) $atributos['width'] = $ancho;
            if (!isset($atributos['height'])) $atributos['height'] = $alto;
        }

        $atributosString = '';
        foreach ($atributos as $clave => $valor) {
            $atributosString .= sprintf(' %s="%s"', esc_attr($clave), esc_attr($valor));
        }

        printf('<img src="%s"%s>', esc_url($urlFinal), $atributosString);
    }

    public static function imagenUrl(string $assetReference): ?string
    {
        if (!self::$isInitialized) self::init();

        list($alias, $nombreArchivo) = self::parseAssetReference($assetReference);
        $rutaRelativa = self::resolveAssetPath($alias, $nombreArchivo);

        if (!$rutaRelativa) {
            return null;
        }
        
        $rutaLocal = get_template_directory() . '/' . $rutaRelativa;
        if (!file_exists($rutaLocal)) {
            GloryLogger::warning("AssetsUtility: El archivo de asset '{$rutaRelativa}' no fue encontrado.");
            return null;
        }

        $urlBase = get_template_directory_uri() . '/' . $rutaRelativa;
        $urlFinal = function_exists('jetpack_photon_url') ? jetpack_photon_url($urlBase) : $urlBase;

        return esc_url($urlFinal);
    }


    public static function get_attachment_id_from_asset(string $assetReference): ?int
    {
        if (!self::$isInitialized) self::init();

        $cacheKey = 'glory_asset_id_' . md5($assetReference);
        $cachedId = get_transient($cacheKey);

        if ($cachedId !== false) {
            return $cachedId === 'null' ? null : (int)$cachedId;
        }

        list($alias, $nombreArchivo) = self::parseAssetReference($assetReference);
        $rutaAssetRelativa = self::resolveAssetPath($alias, $nombreArchivo);

        if (!$rutaAssetRelativa) {
            set_transient($cacheKey, 'null', HOUR_IN_SECONDS);
            return null;
        }

        $rutaAssetCompleta = get_template_directory() . '/' . $rutaAssetRelativa;

        if (!file_exists($rutaAssetCompleta)) {
            set_transient($cacheKey, 'null', HOUR_IN_SECONDS);
            return null;
        }

        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => '_glory_asset_source',
                    'value' => $rutaAssetRelativa,
                ],
            ],
        ];
        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $id = (int)$query->posts[0];
            set_transient($cacheKey, $id, HOUR_IN_SECONDS);
            return $id;
        }

        // Permitir importación en frontend si estamos en modo desarrollo
        if (!is_admin() && !AssetManager::isGlobalDevMode()) {
            return null;
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $archivoTemporal = wp_tempnam($nombreArchivo);
        copy($rutaAssetCompleta, $archivoTemporal);

        $datosArchivo = [
            'name' => basename($nombreArchivo),
            'tmp_name' => $archivoTemporal,
            'error' => 0,
            'size' => filesize($rutaAssetCompleta)
        ];

        $subida = wp_handle_sideload($datosArchivo, ['test_form' => false]);

        if (isset($subida['error'])) {
            GloryLogger::error("AssetsUtility: Error al subir el asset '{$nombreArchivo}'.", ['error' => $subida['error']]);
            if (file_exists($archivoTemporal)) @unlink($archivoTemporal);
            set_transient($cacheKey, 'null', HOUR_IN_SECONDS);
            return null;
        }

        $idAdjunto = wp_insert_attachment([
            'guid' => $subida['url'],
            'post_mime_type' => $subida['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($subida['file'])),
            'post_content' => '',
            'post_status' => 'inherit'
        ], $subida['file']);

        if (is_wp_error($idAdjunto)) {
            GloryLogger::error("AssetsUtility: Error al insertar el adjunto '{$nombreArchivo}'.", ['error' => $idAdjunto->get_error_message()]);
            set_transient($cacheKey, 'null', HOUR_IN_SECONDS);
            return null;
        }

        $metadatosAdjunto = wp_generate_attachment_metadata($idAdjunto, $subida['file']);
        wp_update_attachment_metadata($idAdjunto, $metadatosAdjunto);
        update_post_meta($idAdjunto, '_glory_asset_source', $rutaAssetRelativa);

        GloryLogger::info("AssetsUtility: El asset '{$nombreArchivo}' ha sido importado a la Biblioteca de Medios.", ['attachment_id' => $idAdjunto]);
        set_transient($cacheKey, $idAdjunto, HOUR_IN_SECONDS);

        return $idAdjunto;
    }
}