<?php

namespace Glory\Services\Sync;

use Glory\Utility\AssetsUtility;

class MediaIntegrityService
{
    /**
     * Verifica y repara imágenes de un post (miniatura y galería gestionada) si faltan archivos.
     */
    public function repairPostMedia(int $postId, array $definition = []): void
    {
        $this->repairFeaturedImage($postId, isset($definition['imagenDestacadaAsset']) ? (string) $definition['imagenDestacadaAsset'] : null);
        $this->repairGallery($postId, isset($definition['galeriaAssets']) && is_array($definition['galeriaAssets']) ? $definition['galeriaAssets'] : []);
    }

    private function repairFeaturedImage(int $postId, ?string $fallbackAssetRef): void
    {
        $thumbId = (int) get_post_thumbnail_id($postId);
        if ($thumbId <= 0) {
            if (is_string($fallbackAssetRef) && $fallbackAssetRef !== '') {
                $aid = AssetsUtility::get_attachment_id_from_asset($fallbackAssetRef);
                if ($aid) {
                    set_post_thumbnail($postId, $aid);
                }
            }
            return;
        }

        $attached = get_attached_file($thumbId);
        if ($attached && file_exists($attached)) {
            return;
        }

        $requested = get_post_meta($thumbId, '_glory_asset_requested', true);
        $source    = get_post_meta($thumbId, '_glory_asset_source', true);
        $assetRef  = (is_string($requested) && $requested !== '') ? $requested : ((is_string($source) && $source !== '') ? $source : null);

        if ($assetRef && strpos($assetRef, '::') === false) {
            $basename = basename($assetRef);
            $assetRef = $this->guessAssetRefFromBasename($basename) ?: $assetRef;
        }

        if (!$assetRef && is_string($fallbackAssetRef) && $fallbackAssetRef !== '') {
            $assetRef = $fallbackAssetRef;
        }

        if ($assetRef) {
            $aid = AssetsUtility::get_attachment_id_from_asset($assetRef);
            if ($aid) {
                set_post_thumbnail($postId, $aid);
                return;
            }
        }

        $brokenBasename = $attached ? basename($attached) : '';
        if ($brokenBasename !== '') {
            $ref = $this->guessAssetRefFromBasename($brokenBasename);
            if ($ref) {
                $aid = AssetsUtility::get_attachment_id_from_asset($ref);
                if ($aid) {
                    set_post_thumbnail($postId, $aid);
                    return;
                }
            }
        }
    }

    private function repairGallery(int $postId, array $fallbackAssets): void
    {
        $metaKey = '_glory_default_galeria_ids';
        $ids = get_post_meta($postId, $metaKey, true);
        if (!is_array($ids) || empty($ids)) {
            if (!empty($fallbackAssets)) {
                $newIds = [];
                foreach ($fallbackAssets as $asset) {
                    $aid = AssetsUtility::get_attachment_id_from_asset((string) $asset);
                    if ($aid) { $newIds[] = (int) $aid; }
                }
                if (!empty($newIds)) {
                    update_post_meta($postId, $metaKey, $newIds);
                    foreach ($newIds as $aid) {
                        wp_update_post(['ID' => $aid, 'post_parent' => $postId]);
                    }
                }
            }
            return;
        }

        $changed = false;
        $repaired = [];
        foreach ($ids as $aid) {
            $aid = (int) $aid;
            if ($aid <= 0) { continue; }
            $attached = get_attached_file($aid);
            if ($attached && file_exists($attached)) {
                $repaired[] = $aid;
                continue;
            }

            $requested = get_post_meta($aid, '_glory_asset_requested', true);
            $source    = get_post_meta($aid, '_glory_asset_source', true);
            $assetRef  = (is_string($requested) && $requested !== '') ? $requested : ((is_string($source) && $source !== '') ? $source : null);
            if ($assetRef && strpos($assetRef, '::') === false) {
                $assetRef = $this->guessAssetRefFromBasename(basename($assetRef)) ?: $assetRef;
            }

            $newId = null;
            if ($assetRef) {
                $newId = AssetsUtility::get_attachment_id_from_asset($assetRef);
            }

            if (!$newId && $attached) {
                $basename = basename($attached);
                $ref = $this->guessAssetRefFromBasename($basename);
                if ($ref) {
                    $newId = AssetsUtility::get_attachment_id_from_asset($ref);
                }
            }

            if ($newId) {
                $repaired[] = (int) $newId;
                $changed = true;
                wp_update_post(['ID' => (int) $newId, 'post_parent' => $postId]);
            } else {
                $changed = true;
            }
        }

        if ($changed) {
            $repaired = array_values(array_unique(array_map('intval', $repaired)));
            if (!empty($repaired)) {
                update_post_meta($postId, $metaKey, $repaired);
            } else {
                delete_post_meta($postId, $metaKey);
            }
        }
    }

    private function guessAssetRefFromBasename(string $basename): ?string
    {
        $aliases = ['glory', 'tema', 'elements', 'colors', 'logos'];
        foreach ($aliases as $alias) {
            $ref = $alias . '::' . $basename;
            $maybe = AssetsUtility::imagenUrl($ref);
            if (is_string($maybe) && $maybe !== '') {
                return $ref;
            }
        }
        return null;
    }
}

 
