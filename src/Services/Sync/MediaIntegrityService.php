<?php

namespace Glory\Services\Sync;

/*
 * Orquestador de integridad de medios de un post.
 * Delega en FeaturedImageRepair, GalleryRepair y ContentSanitizer.
 */
class MediaIntegrityService
{
    private FeaturedImageRepair $featuredImageRepair;
    private GalleryRepair $galleryRepair;
    private ContentSanitizer $contentSanitizer;

    public function __construct()
    {
        $this->featuredImageRepair = new FeaturedImageRepair();
        $this->galleryRepair       = new GalleryRepair($this->featuredImageRepair);
        $this->contentSanitizer    = new ContentSanitizer($this->featuredImageRepair);
    }

    /*
     * Verifica y repara imágenes de un post (miniatura, galería y contenido).
     */
    public function repairPostMedia(int $postId, array $definition = []): void
    {
        $definedAssetRef = isset($definition['imagenDestacadaAsset'])
            ? (string) $definition['imagenDestacadaAsset']
            : null;

        $galeriaAssets = isset($definition['galeriaAssets']) && is_array($definition['galeriaAssets'])
            ? $definition['galeriaAssets']
            : [];

        $this->featuredImageRepair->repairFeaturedImage($postId, $definedAssetRef);
        $this->galleryRepair->repairGallery($postId, $galeriaAssets);
        $this->contentSanitizer->sanitizeContentAndMetaUploads($postId);
    }
}
