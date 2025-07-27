<?php
// Glory/src/Services/Sync/PostRelationHandler.php

namespace Glory\Services\Sync;

use Glory\Core\GloryLogger;
use Glory\Utility\AssetsUtility;

/**
 * Gestiona las relaciones de un post (imagen destacada, galería, términos).
 * Su única responsabilidad es conectar un post con sus assets y taxonomías.
 */
class PostRelationHandler
{
    private const META_CLAVE_GALERIA_IDS = '_glory_default_galeria_ids';

    private int $postId;

    public function __construct(int $postId)
    {
        $this->postId = $postId;
    }

    /**
     * Orquesta la asignación de todas las relaciones definidas.
     */
    public function setRelations(array $definicion): void
    {
        $this->setFeaturedImage($definicion);
        $this->setGallery($definicion);
        $this->setTerms($definicion);
    }

    /**
     * Asigna o elimina la imagen destacada.
     */
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

    /**
     * Asigna o elimina la galería.
     */
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

    /**
     * Asigna los términos (categorías).
     */
    public function setTerms(array $definicion): void
    {
        $categoriasRaw = $definicion['metaEntrada']['categoria'] ?? null;
        $taxonomia = 'category'; // Asumimos 'category', podría ser parametrizable

        if (empty($categoriasRaw) || !is_string($categoriasRaw)) {
            wp_set_object_terms($this->postId, null, $taxonomia);
            return;
        }

        $nombresCategorias = array_filter(array_map('trim', explode(',', $categoriasRaw)));
        wp_set_object_terms($this->postId, $nombresCategorias, $taxonomia, false);
    }

    /**
     * Resuelve la referencia del asset para la imagen destacada, manejando la lógica 'random'.
     */
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

    /**
     * Resuelve los IDs de los assets para la galería.
     */
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
