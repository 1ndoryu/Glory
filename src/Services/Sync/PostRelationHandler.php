<?php

namespace Glory\Services\Sync;

use Glory\Core\GloryLogger;
use Glory\Utility\AssetsUtility;


class PostRelationHandler
{
    private const META_CLAVE_GALERIA_IDS = '_glory_default_galeria_ids';

    private int $postId;

    public function __construct(int $postId)
    {
        $this->postId = $postId;
    }


    public function setRelations(array $definicion): void
    {
        $this->setFeaturedImage($definicion);
        $this->setGallery($definicion);
        $this->setTerms($definicion);
    }


    public function setFeaturedImage(array $definicion): void
    {
        if (!isset($definicion['imagenDestacadaAsset'])) {
            return;
        }

        $assetReference = $this->resolveFeaturedImageAsset($definicion);

        if ($assetReference) {
            $attachmentId = AssetsUtility::get_attachment_id_from_asset($assetReference);
            if ($attachmentId) {
                set_post_thumbnail($this->postId, $attachmentId);
            } else {
                GloryLogger::warning("RelationHandler: No se pudo obtener/importar la imagen destacada '{$assetReference}' para el post ID {$this->postId}.");
            }
        } else {
            delete_post_thumbnail($this->postId);
        }
    }


    public function setGallery(array $definicion): void
    {
        $galleryIds = $this->resolveGalleryIds($definicion);

        if (!empty($galleryIds)) {
            update_post_meta($this->postId, self::META_CLAVE_GALERIA_IDS, $galleryIds);
            foreach ($galleryIds as $attachmentId) {
                wp_update_post(['ID' => $attachmentId, 'post_parent' => $this->postId]);
            }
        } else {
            delete_post_meta($this->postId, self::META_CLAVE_GALERIA_IDS);
        }
    }


    public function setTerms(array $definicion): void
    {
        $postType = get_post_type($this->postId);
        if (!$postType) return;

        $taxonomies = get_object_taxonomies($postType);
        $meta = $definicion['metaEntrada'] ?? [];

        foreach ($taxonomies as $taxonomia) {
            if (isset($meta[$taxonomia])) {
                $termNames = is_array($meta[$taxonomia]) ? $meta[$taxonomia] : array_map('trim', explode(',', $meta[$taxonomia]));
                // Asegurar que existan términos; si no existen, crearlos
                $termIds = [];
                foreach ($termNames as $name) {
                    $term = get_term_by('name', $name, $taxonomia);
                    if (!$term) {
                        $created = wp_insert_term($name, $taxonomia);
                        if (!is_wp_error($created) && isset($created['term_id'])) {
                            $termIds[] = (int) $created['term_id'];
                        }
                    } else {
                        $termIds[] = (int) $term->term_id;
                    }
                }
                if (!empty($termIds)) {
                    wp_set_object_terms($this->postId, $termIds, $taxonomia, false);
                }
            }
        }
    }


    private function resolveFeaturedImageAsset(array $datosPost): ?string
    {
        $assetRef = $datosPost['imagenDestacadaAsset'] ?? null;
        if (!is_string($assetRef)) {
            return null;
        }

        if (str_starts_with($assetRef, 'random')) {
            $parts = explode('::', $assetRef, 2);
            $alias = $parts[1] ?? 'glory';
            $randomImage = AssetsUtility::getRandomDefaultImageName($alias);
            return $randomImage ? "{$alias}::{$randomImage}" : null;
        }
        return $assetRef;
    }


    private function resolveGalleryIds(array $definicion): array
    {
        $assets = $definicion['galeriaAssets'] ?? [];
        if (empty($assets) && !empty($definicion['imagenDestacadaAsset'])) {
            $base = preg_replace('/\.[^.]+$/', '', $definicion['imagenDestacadaAsset']);
            for ($i = 1; $i <= 3; $i++) {
                $assets[] = "{$base}{$i}.jpg";
            }
        }

        $ids = [];
        foreach ($assets as $asset) {
            $id = AssetsUtility::get_attachment_id_from_asset($asset);
            if ($id) $ids[] = $id;
        }
        return array_unique(array_map('intval', $ids));
    }
}
