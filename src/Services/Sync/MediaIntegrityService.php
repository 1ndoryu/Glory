<?php

namespace Glory\Services\Sync;

use Glory\Core\GloryLogger;
use Glory\Utility\AssetsUtility;

class MediaIntegrityService
{
    private const META_FALLBACK_LAST_ATTEMPT = '_glory_featured_fallback_last_attempt';
    private const META_FALLBACK_ASSET       = '_glory_featured_fallback_asset';
    private const META_FALLBACK_STATUS      = '_glory_featured_fallback_status'; // success|fail
    private const FALLBACK_COOLDOWN_SECONDS = 86400; // 24h

    /**
     * Verifica y repara imágenes de un post (miniatura y galería gestionada) si faltan archivos.
     */
    public function repairPostMedia(int $postId, array $definition = []): void
    {
        $this->repairFeaturedImage($postId, isset($definition['imagenDestacadaAsset']) ? (string) $definition['imagenDestacadaAsset'] : null);
        $this->repairGallery($postId, isset($definition['galeriaAssets']) && is_array($definition['galeriaAssets']) ? $definition['galeriaAssets'] : []);
        $this->sanitizeContentAndMetaUploads($postId);
    }

    private function repairFeaturedImage(int $postId, ?string $fallbackAssetRef): void
    {
        $thumbId = (int) get_post_thumbnail_id($postId);
        if ($thumbId <= 0) {
            if (is_string($fallbackAssetRef) && $fallbackAssetRef !== '') {
                if (AssetsUtility::assetExists($fallbackAssetRef)) {
                    // Importar o reutilizar el adjunto para el asset definido (incluye SVG)
                    $aid = AssetsUtility::get_attachment_id_from_asset($fallbackAssetRef);
                    if ($aid) {
                        set_post_thumbnail($postId, $aid);
                        return;
                    }
                }
            }
            // Intentar asignar un fallback determinístico (preferir el alias original) con ventana anti-reintento
            $preferredAlias = null;
            if (is_string($fallbackAssetRef) && $fallbackAssetRef !== '') {
                $parts = AssetsUtility::parseAssetReference($fallbackAssetRef);
                $preferredAlias = $parts[0] ?? null;
            }
            $this->assignFallbackFeaturedImageIfAllowed($postId, $preferredAlias);
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

        if (!$assetRef) {
            if (is_string($fallbackAssetRef) && $fallbackAssetRef !== '') {
                $assetRef = $fallbackAssetRef;
            } else {
                $assetRef = $this->chooseFallbackAssetForPost($postId);
            }
        }

        if ($assetRef) {
            if (AssetsUtility::assetExists($assetRef)) {
                // Intentar obtener (o importar) el adjunto para el asset original
                $aid = AssetsUtility::get_attachment_id_from_asset($assetRef);
                if ($aid) {
                    // Asegurar que GUID apunte a URL válida (por si el adjunto existía con GUID roto)
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

        // Agotar opciones: intentar fallback con ventana anti-reintento (sin alias preferido)
        $this->assignFallbackFeaturedImageIfAllowed($postId, null);
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
            } else {
                // Generar una galería mínima de 3 imágenes por defecto
                $fallbacks = $this->chooseFallbackGalleryForPost($postId, 3);
                if (!empty($fallbacks)) {
                    $newIds = [];
                    foreach ($fallbacks as $asset) {
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
                // Actualizar GUID por si acaso
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

    private function guessAssetRefFromBasename(string $basename): ?string
    {
        // Probar primero en 'colors'
        $aliases = ['colors', 'glory', 'tema', 'elements', 'logos'];
        foreach ($aliases as $alias) {
            $ref = $alias . '::' . $basename;
            if (AssetsUtility::assetExists($ref)) {
                return $ref;
            }
        }
        return null;
    }

    private function chooseFallbackAssetForPost(int $postId, ?string $preferredAlias = null): string
    {
        // 1) Intentar en el alias preferido (si se definió uno en la definición original)
        if (is_string($preferredAlias) && $preferredAlias !== '') {
            $list = \Glory\Utility\AssetsUtility::listImagesForAlias($preferredAlias);
            if (is_array($list) && !empty($list)) {
                $idx = abs(crc32((string) $postId)) % count($list);
                return $preferredAlias . '::' . $list[$idx];
            }
        }

        // 2) Intentar en 'colors'
        $colorList = \Glory\Utility\AssetsUtility::listImagesForAlias('colors');
        if (is_array($colorList) && !empty($colorList)) {
            $idx = abs(crc32((string) $postId)) % count($colorList);
            return 'colors::' . $colorList[$idx];
        }

        // 3) Intentar defaults de 'glory'
        $candidates = ['default.jpg','default1.jpg','default2.jpg','default3.jpg','default4.jpg'];
        $pool = [];
        foreach ($candidates as $name) {
            $ref = 'glory::' . $name;
            if (AssetsUtility::assetExists($ref)) { $pool[] = $ref; }
        }
        if (!empty($pool)) {
            $idx = abs(crc32((string) $postId)) % count($pool);
            return $pool[$idx];
        }

        return 'glory::default.jpg';
    }

    private function assignFallbackFeaturedImageIfAllowed(int $postId, ?string $preferredAlias = null): void
    {
        $last = (int) get_post_meta($postId, self::META_FALLBACK_LAST_ATTEMPT, true);
        $now  = time();
        $cooldown = defined('DAY_IN_SECONDS') ? (int) constant('DAY_IN_SECONDS') : self::FALLBACK_COOLDOWN_SECONDS;
        if ($last && ($now - $last) < $cooldown) {
            return; // dentro de ventana, no reintentar
        }

        $ref = $this->chooseFallbackAssetForPost($postId, $preferredAlias);
        if (!is_string($ref) || $ref === '' || !AssetsUtility::assetExists($ref)) {
            update_post_meta($postId, self::META_FALLBACK_LAST_ATTEMPT, $now);
            update_post_meta($postId, self::META_FALLBACK_STATUS, 'fail');
            return;
        }

        // Importar explícitamente el fallback (permitido en admin)
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

    private function chooseFallbackGalleryForPost(int $postId, int $count): array
    {
        $colorList = \Glory\Utility\AssetsUtility::listImagesForAlias('colors');
        if (!is_array($colorList) || empty($colorList)) { return []; }
        $result = [];
        $total = count($colorList);
        $seed = abs(crc32('gallery|' . (string) $postId));
        for ($i = 0; $i < $count; $i++) {
            $idx = ($seed + $i * 13) % $total; // step pseudo-primo
            $result[] = 'colors::' . $colorList[$idx];
        }
        return array_values(array_unique($result));
    }

    private function getFallbackUrlForPost(int $postId): ?string
    {
        $ref = $this->chooseFallbackAssetForPost($postId);
        $aid = AssetsUtility::get_attachment_id_from_asset($ref);
        if ($aid) {
            $url = wp_get_attachment_url($aid);
            if (is_string($url) && $url !== '') { return $url; }
        }
        return null;
    }

    private function sanitizeContentAndMetaUploads(int $postId): void
    {
        $uploads = wp_get_upload_dir();
        $baseUrl = isset($uploads['baseurl']) ? (string) $uploads['baseurl'] : '';
        $baseDir = isset($uploads['basedir']) ? (string) $uploads['basedir'] : '';
        if ($baseUrl === '' || $baseDir === '') { return; }

        // 1) Sanitizar metadatos que contengan URLs a uploads rotas
        $allMeta = get_post_meta($postId);
        if (is_array($allMeta)) {
            foreach ($allMeta as $key => $values) {
                // No tocar claves técnicas
                if (is_string($key) && strpos($key, '_edit') === 0) { continue; }
                if (!is_array($values)) { $values = [$values]; }
                $changed = false;
                $newValues = [];
                foreach ($values as $val) {
                    if (is_string($val) && strpos($val, $baseUrl . '/') === 0) {
                        $rel = ltrim(str_replace($baseUrl, '', $val), '/\\');
                        $path = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
                        if (!file_exists($path)) {
                            $fallback = $this->getFallbackUrlForPost($postId);
                            if ($fallback) {
                                $newValues[] = $fallback;
                                $changed = true;
                                continue;
                            }
                        }
                    }
                    $newValues[] = $val;
                }
                if ($changed) {
                    delete_post_meta($postId, $key);
                    foreach ($newValues as $nv) { add_post_meta($postId, $key, $nv); }
                }
            }
        }

        // 2) Sanitizar post_content reemplazando URLs a uploads que no existan
        $post = get_post($postId);
        if ($post && isset($post->post_content) && is_string($post->post_content) && $post->post_content !== '') {
            $content = $post->post_content;
            $pattern = '#'.preg_quote($baseUrl, '#').'/[^"\s\)]+#i';
            $replaced = $content;
            if (preg_match_all($pattern, $content, $m)) {
                $urls = array_unique($m[0]);
                $fallback = $this->getFallbackUrlForPost($postId);
                foreach ($urls as $u) {
                    $rel = ltrim(str_replace($baseUrl, '', $u), '/\\');
                    $path = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
                    if (!file_exists($path) && $fallback) {
                        $replaced = str_replace($u, $fallback, $replaced);
                    }
                }
            }
            if ($replaced !== $content) {
                wp_update_post(['ID' => $postId, 'post_content' => $replaced]);
            }
        }
    }
}

 
