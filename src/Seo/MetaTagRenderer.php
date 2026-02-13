<?php

namespace Glory\Seo;

use Glory\Manager\PageManager;

/**
 * MetaTagRenderer
 *
 * Renderiza meta tags basicos de SEO: titulo, descripcion, canonical.
 * Expone helpers reutilizados por OpenGraphRenderer y JsonLdRenderer.
 */
class MetaTagRenderer
{
    public const META_TITLE = '_glory_seo_title';
    public const META_DESC = '_glory_seo_desc';
    public const META_CANONICAL = '_glory_seo_canonical';
    public const META_FAQ = '_glory_seo_faq';
    public const META_BREADCRUMB = '_glory_seo_breadcrumb';

    /**
     * Asegura trailing slash en URLs.
     */
    public static function ensureTrailingSlash(string $url): string
    {
        if ($url === '') {
            return $url;
        }
        return substr($url, -1) === '/' ? $url : ($url . '/');
    }

    /**
     * Obtiene titulo y descripcion SEO para el post actual.
     * Reutilizado por OG, Twitter Cards y JSON-LD para evitar duplicar logica.
     */
    public static function getSeoData(int $postId): array
    {
        $title = (string) get_post_meta($postId, self::META_TITLE, true);
        $desc = (string) get_post_meta($postId, self::META_DESC, true);
        $slug = get_post_field('post_name', $postId);

        if ($title === '' || $desc === '') {
            $defaults = PageManager::getDefaultSeoForSlug(is_string($slug) ? $slug : '');
            if ($title === '' && !empty($defaults['title'])) {
                $title = (string) $defaults['title'];
            }
            if ($desc === '' && !empty($defaults['desc'])) {
                $desc = (string) $defaults['desc'];
            }
        }

        /* Fallback al titulo del post si no hay SEO title */
        if ($title === '') {
            $title = get_the_title($postId);
        }

        /* Fallback al extracto si no hay meta description */
        if ($desc === '') {
            $excerpt = get_the_excerpt($postId);
            if (is_string($excerpt) && $excerpt !== '') {
                $desc = wp_trim_words($excerpt, 30, '...');
            }
        }

        return ['title' => $title, 'desc' => $desc];
    }

    /**
     * Obtiene la imagen destacada del post o un fallback del sitio.
     */
    public static function getOgImage(int $postId): string
    {
        $thumbnail = get_the_post_thumbnail_url($postId, 'large');
        if ($thumbnail && is_string($thumbnail)) {
            return $thumbnail;
        }

        /* Fallback: logo del sitio si existe */
        $customLogoId = get_theme_mod('custom_logo');
        if ($customLogoId) {
            $logoUrl = wp_get_attachment_image_url($customLogoId, 'full');
            if ($logoUrl) {
                return $logoUrl;
            }
        }

        return '';
    }

    /**
     * Filtro para document_title_parts.
     * Retorna el titulo SEO personalizado si existe.
     */
    public static function filterDocumentTitle(array $parts): array
    {
        if (!is_page() && !is_singular()) {
            return $parts;
        }

        $postId = get_queried_object_id();
        $seoTitle = (string) get_post_meta($postId, self::META_TITLE, true);

        /* Fallback a defaultSeoMap */
        if ($seoTitle === '') {
            $slug = get_post_field('post_name', $postId);
            if (is_string($slug) && $slug !== '') {
                $defaults = PageManager::getDefaultSeoForSlug($slug);
                if (!empty($defaults['title'])) {
                    $seoTitle = (string) $defaults['title'];
                }
            }
        }

        if ($seoTitle !== '') {
            $parts['title'] = $seoTitle;
            unset($parts['site'], $parts['tagline']);
        }

        return $parts;
    }

    /**
     * Imprime link canonical en wp_head.
     */
    public static function printCanonical(): void
    {
        if (!is_page() && !is_singular()) {
            return;
        }

        $postId = get_queried_object_id();
        $metaCanonical = (string) get_post_meta($postId, self::META_CANONICAL, true);

        if ($metaCanonical !== '') {
            $href = esc_url(self::ensureTrailingSlash($metaCanonical));
            echo "<link rel=\"canonical\" href=\"{$href}\" />\n";
            return;
        }

        /* Fallback a defaultSeoMap */
        $slug = get_post_field('post_name', $postId);
        if (is_string($slug) && $slug !== '') {
            $defaults = PageManager::getDefaultSeoForSlug($slug);
            if (!empty($defaults['canonical'])) {
                $href = esc_url(self::ensureTrailingSlash((string) $defaults['canonical']));
                echo "<link rel=\"canonical\" href=\"{$href}\" />\n";
            }
        }
    }

    /**
     * Imprime meta description en wp_head.
     */
    public static function printMetaDescription(): void
    {
        if (!is_page() && !is_singular()) {
            return;
        }

        $postId = get_queried_object_id();
        $metaDesc = (string) get_post_meta($postId, self::META_DESC, true);

        if ($metaDesc !== '') {
            $content = esc_attr($metaDesc);
            echo "<meta name=\"description\" content=\"{$content}\" />\n";
            return;
        }

        /* Fallback a defaultSeoMap */
        $slug = get_post_field('post_name', $postId);
        if (is_string($slug) && $slug !== '') {
            $defaults = PageManager::getDefaultSeoForSlug($slug);
            if (!empty($defaults['desc'])) {
                $content = esc_attr((string) $defaults['desc']);
                echo "<meta name=\"description\" content=\"{$content}\" />\n";
            }
        }
    }
}
