<?php

namespace Glory\Services\Sync;

use Glory\Core\GloryLogger;
use Glory\Utility\AssetsUtility;

/*
 * Reparación de imagen destacada (thumbnail) de un post.
 * Incluye: asignación desde definición, fallback determinístico,
 * reparación de archivo físico y helpers de resolución de assets.
 */
class FeaturedImageRepair
{
    public const META_FALLBACK_LAST_ATTEMPT = '_glory_featured_fallback_last_attempt';
    public const META_FALLBACK_ASSET        = '_glory_featured_fallback_asset';
    public const META_FALLBACK_STATUS       = '_glory_featured_fallback_status';
    private const FALLBACK_COOLDOWN_SECONDS = 86400;

    /*
     * Punto de entrada: verifica y repara la imagen destacada de un post.
     */
    public function repairFeaturedImage(int $postId, ?string $definedAssetRef): void
    {
        $thumbId = (int) get_post_thumbnail_id($postId);

        /* Sin thumbnail: intentar asignar desde definición o fallback */
        if ($thumbId <= 0) {
            GloryLogger::info("MediaIntegrity: Post {$postId} sin thumbnail, intentando asignar.", [
                'definedAsset' => $definedAssetRef ?? 'ninguno',
            ]);

            if (is_string($definedAssetRef) && $definedAssetRef !== '') {
                if (AssetsUtility::assetExists($definedAssetRef)) {
                    $aid = AssetsUtility::get_attachment_id_from_asset($definedAssetRef);
                    if ($aid) {
                        set_post_thumbnail($postId, $aid);
                        GloryLogger::info("MediaIntegrity: Thumbnail asignado desde definicion.", [
                            'postId' => $postId,
                            'attachmentId' => $aid,
                            'asset' => $definedAssetRef,
                        ]);
                        return;
                    }
                }
            }

            $preferredAlias = null;
            if (is_string($definedAssetRef) && $definedAssetRef !== '') {
                $parts = AssetsUtility::parseAssetReference($definedAssetRef);
                $preferredAlias = $parts[0] ?? null;
            }
            $this->assignFallbackFeaturedImageIfAllowed($postId, $preferredAlias);
            return;
        }

        /* Hay thumbnail: verificar si coincide con la definición */
        if (is_string($definedAssetRef) && $definedAssetRef !== '') {
            $currentAssetRequested = get_post_meta($thumbId, '_glory_asset_requested', true);
            $currentAssetSource = get_post_meta($thumbId, '_glory_asset_source', true);
            $currentAsset = is_string($currentAssetRequested) && $currentAssetRequested !== ''
                ? $currentAssetRequested
                : (is_string($currentAssetSource) && $currentAssetSource !== '' ? $currentAssetSource : '');

            $definedExpanded = $this->expandAssetReferenceLocal($definedAssetRef);

            if ($currentAsset !== $definedExpanded) {
                GloryLogger::info("MediaIntegrity: Thumbnail difiere de definicion, forzando cambio.", [
                    'postId' => $postId,
                    'thumbId' => $thumbId,
                    'currentAsset' => $currentAsset,
                    'definedAsset' => $definedExpanded,
                    'definedOriginal' => $definedAssetRef,
                ]);

                if (AssetsUtility::assetExists($definedAssetRef)) {
                    $existingAid = AssetsUtility::findExistingAttachmentIdForAsset($definedAssetRef);

                    GloryLogger::info("MediaIntegrity: Busqueda de attachment existente.", [
                        'postId' => $postId,
                        'definedAsset' => $definedAssetRef,
                        'existingAid' => $existingAid ?: 'ninguno',
                        'currentThumbId' => $thumbId,
                    ]);

                    if ($existingAid && $existingAid !== $thumbId) {
                        set_post_thumbnail($postId, $existingAid);
                        GloryLogger::info("MediaIntegrity: Thumbnail cambiado a attachment existente.", [
                            'postId' => $postId,
                            'oldThumbId' => $thumbId,
                            'newThumbId' => $existingAid,
                            'asset' => $definedAssetRef,
                        ]);
                        return;
                    }

                    GloryLogger::info("MediaIntegrity: Forzando importacion de nuevo attachment.", [
                        'postId' => $postId,
                        'definedAsset' => $definedAssetRef,
                    ]);

                    $newAid = AssetsUtility::get_attachment_id_from_asset($definedAssetRef, false);

                    GloryLogger::info("MediaIntegrity: Resultado de importacion.", [
                        'postId' => $postId,
                        'newAid' => $newAid ?: 'fallo',
                        'currentThumbId' => $thumbId,
                    ]);

                    if ($newAid && $newAid !== $thumbId) {
                        set_post_thumbnail($postId, $newAid);
                        GloryLogger::info("MediaIntegrity: Thumbnail cambiado a nuevo attachment importado.", [
                            'postId' => $postId,
                            'oldThumbId' => $thumbId,
                            'newThumbId' => $newAid,
                            'asset' => $definedAssetRef,
                        ]);
                        return;
                    } elseif ($newAid === $thumbId) {
                        /* Sistema devolvió mismo attachment, actualizar metas para evitar loop */
                        GloryLogger::warning("MediaIntegrity: Importacion devolvio mismo attachment. Posible problema de cache o busqueda.", [
                            'postId' => $postId,
                            'thumbId' => $thumbId,
                            'definedAsset' => $definedAssetRef,
                        ]);
                        update_post_meta($thumbId, '_glory_asset_requested', $definedExpanded);
                        update_post_meta($thumbId, '_glory_asset_source', $definedExpanded);
                        return;
                    } else {
                        GloryLogger::warning("MediaIntegrity: No se pudo importar attachment para asset definido.", [
                            'postId' => $postId,
                            'asset' => $definedAssetRef,
                        ]);
                    }
                } else {
                    GloryLogger::warning("MediaIntegrity: Asset definido no existe fisicamente.", [
                        'postId' => $postId,
                        'asset' => $definedAssetRef,
                    ]);
                }
            }
        }

        /* Verificar que el archivo físico del thumbnail exista */
        $attached = get_attached_file($thumbId);
        if ($attached && file_exists($attached)) {
            return;
        }

        GloryLogger::info("MediaIntegrity: Archivo fisico de thumbnail no existe, reparando.", [
            'postId' => $postId,
            'thumbId' => $thumbId,
            'attached' => $attached,
        ]);

        $requested = get_post_meta($thumbId, '_glory_asset_requested', true);
        $source    = get_post_meta($thumbId, '_glory_asset_source', true);
        $assetRef  = (is_string($requested) && $requested !== '') ? $requested : ((is_string($source) && $source !== '') ? $source : null);

        if ($assetRef && strpos($assetRef, '::') === false) {
            $basename = basename($assetRef);
            $assetRef = $this->guessAssetRefFromBasename($basename) ?: $assetRef;
        }

        if (!$assetRef) {
            if (is_string($definedAssetRef) && $definedAssetRef !== '') {
                $assetRef = $definedAssetRef;
            } else {
                $assetRef = $this->chooseFallbackAssetForPost($postId);
            }
        }

        if ($assetRef) {
            if (AssetsUtility::assetExists($assetRef)) {
                $aid = AssetsUtility::get_attachment_id_from_asset($assetRef);
                if ($aid) {
                    $file = get_attached_file($aid);
                    if ($file && file_exists($file)) {
                        $uploads = wp_get_upload_dir();
                        $rel = ltrim(str_replace(trailingslashit($uploads['basedir']), '', $file), '/\\');
                        $url  = trailingslashit($uploads['baseurl']) . str_replace(DIRECTORY_SEPARATOR, '/', $rel);
                        wp_update_post(['ID' => $aid, 'guid' => $url]);
                    }
                    set_post_thumbnail($postId, $aid);
                    return;
                }
            }
        }

        $brokenBasename = $attached ? basename($attached) : '';
        if ($brokenBasename !== '') {
            $ref = $this->guessAssetRefFromBasename($brokenBasename);
            if ($ref) {
                if (AssetsUtility::assetExists($ref)) {
                    $aid = AssetsUtility::findExistingAttachmentIdForAsset($ref);
                    if ($aid) {
                        set_post_thumbnail($postId, $aid);
                        return;
                    }
                }
            }
            $this->assignFallbackFeaturedImageIfAllowed($postId, null);
        }
    }

    /*
     * Expande una referencia de asset (alias::archivo) a ruta relativa completa.
     */
    private function expandAssetReferenceLocal(string $assetReference): string
    {
        if (strpos($assetReference, '::') === false) {
            return $assetReference;
        }

        $parsed = AssetsUtility::parseAssetReference($assetReference);
        if (!is_array($parsed) || count($parsed) !== 2) {
            return $assetReference;
        }

        list($alias, $nombreArchivo) = $parsed;

        /* Usar el mapa centralizado de AssetResolver en vez de duplicarlo */
        \Glory\Utility\AssetResolver::init();
        $aliasMap = \Glory\Utility\AssetResolver::getAssetPaths();

        $basePath = $aliasMap[$alias] ?? null;
        if ($basePath === null) {
            return $assetReference;
        }

        return $basePath . '/' . ltrim($nombreArchivo, '/\\');
    }

    /*
     * Asigna un fallback determinístico como imagen destacada.
     * Respeta una ventana de cooldown de 24h para no reintentar en bucle.
     */
    private function assignFallbackFeaturedImageIfAllowed(int $postId, ?string $preferredAlias = null): void
    {
        $last = (int) get_post_meta($postId, self::META_FALLBACK_LAST_ATTEMPT, true);
        $now  = time();
        $cooldown = defined('DAY_IN_SECONDS') ? (int) constant('DAY_IN_SECONDS') : self::FALLBACK_COOLDOWN_SECONDS;
        if ($last && ($now - $last) < $cooldown) {
            return;
        }

        $ref = $this->chooseFallbackAssetForPost($postId, $preferredAlias);
        if (!is_string($ref) || $ref === '' || !AssetsUtility::assetExists($ref)) {
            update_post_meta($postId, self::META_FALLBACK_LAST_ATTEMPT, $now);
            update_post_meta($postId, self::META_FALLBACK_STATUS, 'fail');
            return;
        }

        $aid = AssetsUtility::get_attachment_id_from_asset($ref, false);
        if ($aid) {
            set_post_thumbnail($postId, $aid);
            update_post_meta($postId, self::META_FALLBACK_LAST_ATTEMPT, $now);
            update_post_meta($postId, self::META_FALLBACK_ASSET, $ref);
            update_post_meta($postId, self::META_FALLBACK_STATUS, 'success');
            GloryLogger::info("MediaIntegrity: Fallback de destacada asignado '{$ref}' para post {$postId}.");
        } else {
            update_post_meta($postId, self::META_FALLBACK_LAST_ATTEMPT, $now);
            update_post_meta($postId, self::META_FALLBACK_ASSET, $ref);
            update_post_meta($postId, self::META_FALLBACK_STATUS, 'fail');
            GloryLogger::warning("MediaIntegrity: Fallback de destacada falló para post {$postId} con '{$ref}'.");
        }
    }

    /*
     * Elige un asset de fallback determinístico basado en el ID del post.
     * Prioriza: alias preferido > colors > defaults de glory.
     * Público porque lo usa ContentSanitizer.
     */
    public function chooseFallbackAssetForPost(int $postId, ?string $preferredAlias = null): string
    {
        if (is_string($preferredAlias) && $preferredAlias !== '') {
            $list = AssetsUtility::listImagesForAlias($preferredAlias);
            if (is_array($list) && !empty($list)) {
                $idx = abs(crc32((string) $postId)) % count($list);
                return $preferredAlias . '::' . $list[$idx];
            }
        }

        $colorList = AssetsUtility::listImagesForAlias('colors');
        if (is_array($colorList) && !empty($colorList)) {
            $idx = abs(crc32((string) $postId)) % count($colorList);
            return 'colors::' . $colorList[$idx];
        }

        $candidates = ['default.jpg', 'default1.jpg', 'default2.jpg', 'default3.jpg', 'default4.jpg'];
        $pool = [];
        foreach ($candidates as $name) {
            $ref = 'glory::' . $name;
            if (AssetsUtility::assetExists($ref)) {
                $pool[] = $ref;
            }
        }
        if (!empty($pool)) {
            $idx = abs(crc32((string) $postId)) % count($pool);
            return $pool[$idx];
        }

        return 'glory::default.jpg';
    }

    /*
     * Intenta adivinar la referencia alias::archivo a partir del nombre de archivo.
     * Público porque lo usa GalleryRepair.
     */
    public function guessAssetRefFromBasename(string $basename): ?string
    {
        $aliases = ['colors', 'glory', 'tema', 'elements', 'logos'];
        foreach ($aliases as $alias) {
            $ref = $alias . '::' . $basename;
            if (AssetsUtility::assetExists($ref)) {
                return $ref;
            }
        }
        return null;
    }
}
