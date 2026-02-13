<?php

namespace Glory\Services\Sync;

use Glory\Utility\AssetsUtility;

/*
 * Sanitización del contenido y metadatos de un post.
 * Reemplaza URLs de uploads rotas por un fallback válido.
 */
class ContentSanitizer
{
    private FeaturedImageRepair $featuredImageRepair;

    public function __construct(FeaturedImageRepair $featuredImageRepair)
    {
        $this->featuredImageRepair = $featuredImageRepair;
    }

    /*
     * Recorre metadatos y post_content buscando URLs a uploads
     * cuyos archivos físicos no existen, reemplazándolas por un fallback.
     */
    public function sanitizeContentAndMetaUploads(int $postId): void
    {
        $uploads = wp_get_upload_dir();
        $baseUrl = isset($uploads['baseurl']) ? (string) $uploads['baseurl'] : '';
        $baseDir = isset($uploads['basedir']) ? (string) $uploads['basedir'] : '';
        if ($baseUrl === '' || $baseDir === '') {
            return;
        }

        /* Sanitizar metadatos que contengan URLs a uploads rotas */
        $allMeta = get_post_meta($postId);
        if (is_array($allMeta)) {
            foreach ($allMeta as $key => $values) {
                if (is_string($key) && strpos($key, '_edit') === 0) {
                    continue;
                }
                if (!is_array($values)) {
                    $values = [$values];
                }
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
                    foreach ($newValues as $nv) {
                        add_post_meta($postId, $key, $nv);
                    }
                }
            }
        }

        /* Sanitizar post_content reemplazando URLs a uploads inexistentes */
        $post = get_post($postId);
        if ($post && isset($post->post_content) && is_string($post->post_content) && $post->post_content !== '') {
            $content = $post->post_content;
            $pattern = '#' . preg_quote($baseUrl, '#') . '/[^"\s\)]+#i';
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

    /*
     * Obtiene la URL de un asset de fallback para el post dado.
     */
    private function getFallbackUrlForPost(int $postId): ?string
    {
        $ref = $this->featuredImageRepair->chooseFallbackAssetForPost($postId);
        $aid = AssetsUtility::get_attachment_id_from_asset($ref);
        if ($aid) {
            $url = wp_get_attachment_url($aid);
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }
        return null;
    }
}
