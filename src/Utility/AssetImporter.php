<?php
/* sentinel-disable-file limite-lineas -- Clase cohesiva de importacion de assets al media library de WP. 6 metodos con una sola responsabilidad, dividir seria artificial */

namespace Glory\Utility;

use Glory\Core\GloryLogger;
use Glory\Manager\AssetManager;
use Glory\Utility\AssetMeta;

/**
 * Importación de assets del tema a la Biblioteca de Medios de WordPress.
 * Gestiona la copia, sideload, reparación de adjuntos rotos y caché de IDs.
 */
class AssetImporter
{
    /**
     * Obtiene (o importa) el ID de adjunto para un asset del tema.
     * Maneja caché, resolución flexible, reparación de archivos faltantes
     * y creación de nuevos adjuntos cuando es necesario.
     */
    public static function get_attachment_id_from_asset(string $assetReference, bool $allowAliasFallback = true): ?int
    {
        AssetResolver::init();

        $cacheKey = 'glory_asset_id_' . md5($assetReference);
        $cachedId = get_transient($cacheKey);

        /* Si cache tiene 'null' pero el archivo ahora existe, invalidar para reintentar */
        if ($cachedId === 'null') {
            list($aliasChk, $nombreChk) = AssetResolver::parseAssetReference($assetReference);
            $resolvedChk = AssetResolver::resolveActualRelativeAssetPath($aliasChk, $nombreChk)
                ?: AssetResolver::resolveAssetPath($aliasChk, $nombreChk);
            if ($resolvedChk) {
                $absChk = get_template_directory() . '/' . $resolvedChk;
                if (file_exists($absChk)) {
                    delete_transient($cacheKey);
                    $cachedId = false;
                }
            }
        }

        if ($cachedId !== false) {
            return $cachedId === 'null' ? null : (int) $cachedId;
        }

        list($alias, $nombreArchivo) = AssetResolver::parseAssetReference($assetReference);
        $rutaAssetRelativaSolicitada = AssetResolver::resolveAssetPath($alias, $nombreArchivo);
        $resolved = AssetResolver::resolveActualRelativeAssetPath($alias, $nombreArchivo);
        $rutaAssetRelativa = $resolved ?: $rutaAssetRelativaSolicitada;

        if (!$rutaAssetRelativa) {
            set_transient($cacheKey, 'null', HOUR_IN_SECONDS);
            return null;
        }

        $rutaAssetCompleta = get_template_directory() . '/' . $rutaAssetRelativa;

        if (!file_exists($rutaAssetCompleta)) {
            $assetPaths = AssetResolver::getAssetPaths();
            if ($allowAliasFallback && isset($assetPaths[$alias])) {
                $dirAlias = trailingslashit(get_template_directory() . '/' . $assetPaths[$alias]);
                $alt = glob($dirAlias . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) ?: [];
                if (!empty($alt)) {
                    $idx = abs(crc32($nombreArchivo)) % count($alt);
                    $fallbackFile = $alt[$idx];
                    $nombreArchivo = basename($fallbackFile);
                    $rutaAssetRelativa = AssetResolver::resolveAssetPath($alias, $nombreArchivo) ?: $rutaAssetRelativaSolicitada;
                    $rutaAssetCompleta = get_template_directory() . '/' . $rutaAssetRelativa;
                }
            }
            if (!file_exists($rutaAssetCompleta)) {
                set_transient($cacheKey, 'null', HOUR_IN_SECONDS);
                return null;
            }
        }

        $nombreArchivoBase = basename($rutaAssetRelativa);

        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'     => AssetMeta::SOURCE,
                    'value'   => $rutaAssetRelativa,
                    'compare' => '='
                ],
                [
                    'key'     => '_wp_attached_file',
                    'value'   => $nombreArchivoBase,
                    'compare' => 'LIKE'
                ],
                [
                    'key'     => AssetMeta::REQUESTED,
                    'value'   => $rutaAssetRelativaSolicitada,
                    'compare' => '='
                ],
            ],
        ];
        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $id = (int) $query->posts[0];
            $id = self::repararAdjuntoSiNecesario(
                $id,
                $rutaAssetCompleta,
                $rutaAssetRelativa,
                $rutaAssetRelativaSolicitada,
                $nombreArchivo,
                $cacheKey
            );
            if ($id === null) {
                return null;
            }

            /* Asegurar metas de trazabilidad */
            if (!metadata_exists('post', $id, AssetMeta::SOURCE)) {
                update_post_meta($id, AssetMeta::SOURCE, $rutaAssetRelativa);
            }
            if (!metadata_exists('post', $id, AssetMeta::REQUESTED)) {
                update_post_meta($id, AssetMeta::REQUESTED, $rutaAssetRelativaSolicitada);
            }

            set_transient($cacheKey, $id, HOUR_IN_SECONDS);
            return $id;
        }

        /* Permitir importación en frontend solo si estamos en modo desarrollo */
        if (!is_admin() && !AssetManager::isGlobalDevMode()) {
            return null;
        }

        return self::importarNuevoAdjunto(
            $rutaAssetCompleta,
            $rutaAssetRelativa,
            $rutaAssetRelativaSolicitada,
            $nombreArchivo,
            $assetReference,
            $cacheKey
        );
    }


    /**
     * Repara un adjunto existente cuyo archivo físico ya no existe.
     * Re-sube el asset desde el tema y actualiza los metadatos del adjunto.
     * Retorna el ID del adjunto (original o nuevo) o null si falla.
     */
    private static function repararAdjuntoSiNecesario(
        int $id,
        string $rutaAssetCompleta,
        string $rutaAssetRelativa,
        string $rutaAssetRelativaSolicitada,
        string $nombreArchivo,
        string $cacheKey
    ): ?int {
        $rutaAdjunta = get_attached_file($id);
        if (!empty($rutaAdjunta) && file_exists($rutaAdjunta)) {
            return $id;
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $archivoTemporal = wp_tempnam($nombreArchivo);
        /* try/finally garantiza cleanup del archivo temporal en cualquier escenario */
        try {
            try {
                if (!copy($rutaAssetCompleta, $archivoTemporal)) {
                    GloryLogger::error('AssetImporter: No se pudo copiar asset para reparacion.', [
                        'origen'  => $rutaAssetCompleta,
                        'destino' => $archivoTemporal,
                    ]);
                }
            } catch (\Throwable $e) {
                GloryLogger::error('AssetImporter: Excepcion al copiar asset para reparacion.', [
                    'error' => $e->getMessage(),
                ]);
            }

            $tamanoArchivo = 0;
            try {
                $tamanoArchivo = filesize($rutaAssetCompleta) ?: 0;
            } catch (\Throwable $e) {
                GloryLogger::error('AssetImporter: No se pudo obtener tamano de archivo.', [
                    'archivo' => $rutaAssetCompleta,
                    'error'   => $e->getMessage(),
                ]);
            }

            $datosArchivo = [
                'name'     => basename($nombreArchivo),
                'tmp_name' => $archivoTemporal,
                'error'    => 0,
                'size'     => $tamanoArchivo,
            ];
            $subida = wp_handle_sideload($datosArchivo, ['test_form' => false]);

            if (!isset($subida['error']) && isset($subida['file'])) {
                if (function_exists('update_attached_file')) {
                    update_attached_file($id, $subida['file']);
                }
                $meta = wp_generate_attachment_metadata($id, $subida['file']);
                wp_update_attachment_metadata($id, $meta);
                update_post_meta($id, AssetMeta::SOURCE, $rutaAssetRelativa);
                update_post_meta($id, AssetMeta::REQUESTED, $rutaAssetRelativaSolicitada);
                if (isset($subida['url'])) {
                    wp_update_post(['ID' => $id, 'guid' => $subida['url']]);
                }
                return $id;
            }

            /* Si la reparacion falla, importar como nuevo adjunto */
            $tamanoArchivo2 = 0;
            try {
                $tamanoArchivo2 = filesize($rutaAssetCompleta) ?: 0;
            } catch (\Throwable $e) {
                GloryLogger::error('AssetImporter: No se pudo obtener tamano de archivo (fallback).', [
                    'archivo' => $rutaAssetCompleta,
                    'error'   => $e->getMessage(),
                ]);
            }

            $datosArchivo2 = [
                'name'     => basename($nombreArchivo),
                'tmp_name' => $archivoTemporal,
                'error'    => 0,
                'size'     => $tamanoArchivo2,
            ];
            $subida2 = wp_handle_sideload($datosArchivo2, ['test_form' => false]);
            if (!isset($subida2['error']) && isset($subida2['file'])) {
                $nuevoId = wp_insert_attachment([
                    'guid'           => $subida2['url'],
                    'post_mime_type' => $subida2['type'],
                    'post_title'     => preg_replace('/\.[^.]+$/', '', basename($subida2['file'])),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                ], $subida2['file']);
                if (!is_wp_error($nuevoId)) {
                    $meta2 = wp_generate_attachment_metadata($nuevoId, $subida2['file']);
                    wp_update_attachment_metadata($nuevoId, $meta2);
                    update_post_meta($nuevoId, AssetMeta::SOURCE, $rutaAssetRelativa);
                    update_post_meta($nuevoId, AssetMeta::REQUESTED, $rutaAssetRelativaSolicitada);
                    set_transient($cacheKey, (int) $nuevoId, HOUR_IN_SECONDS);
                    return (int) $nuevoId;
                }
            }
            return null;
        } finally {
            /* wp_handle_sideload mueve el archivo si tiene exito; si sigue existiendo, hubo error */
            if (file_exists($archivoTemporal)) {
                try {
                    unlink($archivoTemporal);
                } catch (\Throwable $e) {
                    GloryLogger::error('AssetImporter: No se pudo limpiar archivo temporal en reparacion.', [
                        'archivo' => $archivoTemporal,
                    ]);
                }
            }
        }
    }


    /**
     * Importa un asset completamente nuevo a la Biblioteca de Medios.
     */
    private static function importarNuevoAdjunto(
        string $rutaAssetCompleta,
        string $rutaAssetRelativa,
        string $rutaAssetRelativaSolicitada,
        string $nombreArchivo,
        string $assetReference,
        string $cacheKey
    ): ?int {
        /* Cachear intento de importación para evitar reintentos ruidosos */
        $importAttemptKey = 'glory_asset_import_attempt_' . md5($assetReference);
        $lastAttempt = get_transient($importAttemptKey);
        if ($lastAttempt !== false) {
            return null;
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $archivoTemporal = wp_tempnam($nombreArchivo);
        /* try/finally garantiza cleanup del archivo temporal en cualquier escenario */
        try {
            copy($rutaAssetCompleta, $archivoTemporal);

            $datosArchivo = [
                'name'     => basename($nombreArchivo),
                'tmp_name' => $archivoTemporal,
                'error'    => 0,
                'size'     => filesize($rutaAssetCompleta)
            ];

            $subida = wp_handle_sideload($datosArchivo, ['test_form' => false]);

            if (isset($subida['error'])) {
                GloryLogger::error("AssetImporter: Error al subir el asset '{$nombreArchivo}'.", ['error' => $subida['error']]);
                set_transient($importAttemptKey, 'fail', DAY_IN_SECONDS);
                set_transient($cacheKey, 'null', HOUR_IN_SECONDS);
                return null;
            }

            $idAdjunto = wp_insert_attachment([
                'guid'           => $subida['url'],
                'post_mime_type' => $subida['type'],
                'post_title'     => preg_replace('/\.[^.]+$/', '', basename($subida['file'])),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ], $subida['file']);

            if (is_wp_error($idAdjunto)) {
                GloryLogger::error("AssetImporter: Error al insertar el adjunto '{$nombreArchivo}'.", ['error' => $idAdjunto->get_error_message()]);
                set_transient($importAttemptKey, 'fail', DAY_IN_SECONDS);
                set_transient($cacheKey, 'null', HOUR_IN_SECONDS);
                return null;
            }

            $metadatosAdjunto = wp_generate_attachment_metadata($idAdjunto, $subida['file']);
            wp_update_attachment_metadata($idAdjunto, $metadatosAdjunto);
            update_post_meta($idAdjunto, AssetMeta::SOURCE, $rutaAssetRelativa);
            update_post_meta($idAdjunto, AssetMeta::REQUESTED, $rutaAssetRelativaSolicitada);

            GloryLogger::info("AssetImporter: El asset '{$nombreArchivo}' ha sido importado a la Biblioteca de Medios.", ['attachment_id' => $idAdjunto]);
            set_transient($cacheKey, $idAdjunto, HOUR_IN_SECONDS);

            return $idAdjunto;
        } finally {
            /* wp_handle_sideload mueve el archivo si tiene exito; si sigue existiendo, hubo error */
            if (file_exists($archivoTemporal)) {
                try {
                    unlink($archivoTemporal);
                } catch (\Throwable $e) {
                    GloryLogger::error('AssetImporter: No se pudo limpiar archivo temporal en importacion.', [
                        'archivo' => $archivoTemporal,
                    ]);
                }
            }
        }
    }


    public static function importTemaAssets(): void
    {
        self::importAllFromAlias('tema');
    }


    public static function importAssetsForAlias(string $alias): void
    {
        self::importAllFromAlias($alias);
    }


    private static function importAllFromAlias(string $alias): void
    {
        $assetPaths = AssetResolver::getAssetPaths();
        if (!isset($assetPaths[$alias])) {
            return;
        }
        $dir = get_template_directory() . '/' . $assetPaths[$alias] . '/';
        if (!is_dir($dir)) {
            return;
        }
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        foreach ($extensions as $ext) {
            $files = glob($dir . '*.' . $ext);
            if (!is_array($files)) {
                continue;
            }
            foreach ($files as $file) {
                if (!is_file($file)) {
                    continue;
                }
                $basename = basename($file);
                $assetRef = $alias . '::' . $basename;
                self::get_attachment_id_from_asset($assetRef);
            }
        }
    }
}
