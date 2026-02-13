<?php

namespace Glory\Services\Sync;

use Glory\Utility\AssetsUtility;

/*
 * Reparación de la galería gestionada (_glory_default_galeria_ids) de un post.
 * Verifica existencia de archivos físicos y reconstruye IDs rotos.
 */
class GalleryRepair
{
    private FeaturedImageRepair $featuredImageRepair;

    public function __construct(FeaturedImageRepair $featuredImageRepair)
    {
        $this->featuredImageRepair = $featuredImageRepair;
    }

    /*
     * Repara la galería de un post: asigna desde definición, genera fallback
     * o repara attachment IDs cuyos archivos físicos no existen.
     */
    public function repairGallery(int $postId, array $fallbackAssets): void
    {
        $metaKey = '_glory_default_galeria_ids';
        $ids = get_post_meta($postId, $metaKey, true);

        if (!is_array($ids) || empty($ids)) {
            if (!empty($fallbackAssets)) {
                $newIds = [];
                foreach ($fallbackAssets as $asset) {
                    $aid = AssetsUtility::get_attachment_id_from_asset((string) $asset);
                    if ($aid) {
                        $newIds[] = (int) $aid;
                    }
                }
                if (!empty($newIds)) {
                    update_post_meta($postId, $metaKey, $newIds);
                    foreach ($newIds as $aid) {
                        wp_update_post(['ID' => $aid, 'post_parent' => $postId]);
                    }
                }
            } else {
                $fallbacks = $this->chooseFallbackGalleryForPost($postId, 3);
                if (!empty($fallbacks)) {
                    $newIds = [];
                    foreach ($fallbacks as $asset) {
                        $aid = AssetsUtility::get_attachment_id_from_asset((string) $asset);
                        if ($aid) {
                            $newIds[] = (int) $aid;
                        }
                    }
                    if (!empty($newIds)) {
                        update_post_meta($postId, $metaKey, $newIds);
                        foreach ($newIds as $aid) {
                            wp_update_post(['ID' => $aid, 'post_parent' => $postId]);
                        }
                    }
                }
            }
            return;
        }

        $changed = false;
        $repaired = [];
        foreach ($ids as $aid) {
            $aid = (int) $aid;
            if ($aid <= 0) {
                continue;
            }
            $attached = get_attached_file($aid);
            if ($attached && file_exists($attached)) {
                $repaired[] = $aid;
                continue;
            }

            /* Intentar resolver la referencia de asset original */
            $requested = get_post_meta($aid, '_glory_asset_requested', true);
            $source    = get_post_meta($aid, '_glory_asset_source', true);
            $assetRef  = (is_string($requested) && $requested !== '') ? $requested : ((is_string($source) && $source !== '') ? $source : null);
            if ($assetRef && strpos($assetRef, '::') === false) {
                $assetRef = $this->featuredImageRepair->guessAssetRefFromBasename(basename($assetRef)) ?: $assetRef;
            }

            $newId = null;
            if ($assetRef) {
                $newId = AssetsUtility::get_attachment_id_from_asset($assetRef);
            }

            if (!$newId && $attached) {
                $basename = basename($attached);
                $ref = $this->featuredImageRepair->guessAssetRefFromBasename($basename);
                if ($ref) {
                    $newId = AssetsUtility::get_attachment_id_from_asset($ref);
                }
            }

            if ($newId) {
                $file = get_attached_file($newId);
                if ($file && file_exists($file)) {
                    $uploads = wp_get_upload_dir();
                    $rel = ltrim(str_replace(trailingslashit($uploads['basedir']), '', $file), '/\\');
                    $url  = trailingslashit($uploads['baseurl']) . str_replace(DIRECTORY_SEPARATOR, '/', $rel);
                    wp_update_post(['ID' => (int) $newId, 'guid' => $url]);
                }
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

    /*
     * Genera una lista de assets de galería usando 'colors' como fuente,
     * con selección determinística basada en el ID del post.
     */
    private function chooseFallbackGalleryForPost(int $postId, int $count): array
    {
        $colorList = AssetsUtility::listImagesForAlias('colors');
        if (!is_array($colorList) || empty($colorList)) {
            return [];
        }
        $result = [];
        $total = count($colorList);
        $seed = abs(crc32('gallery|' . (string) $postId));
        for ($i = 0; $i < $count; $i++) {
            $idx = ($seed + $i * 13) % $total;
            $result[] = 'colors::' . $colorList[$idx];
        }
        return array_values(array_unique($result));
    }
}
