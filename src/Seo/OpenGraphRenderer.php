<?php

namespace Glory\Seo;

/**
 * OpenGraphRenderer
 *
 * Renderiza meta tags Open Graph y Twitter Cards en wp_head.
 * Delega la obtencion de datos SEO e imagen a MetaTagRenderer.
 */
class OpenGraphRenderer
{
    /**
     * Imprime meta tags Open Graph en wp_head.
     */
    public static function printOpenGraph(): void
    {
        if (!is_page() && !is_singular()) {
            return;
        }

        $postId = get_queried_object_id();
        $seo = MetaTagRenderer::getSeoData($postId);
        $url = MetaTagRenderer::ensureTrailingSlash((string) get_permalink($postId));
        $image = MetaTagRenderer::getOgImage($postId);
        $siteName = get_bloginfo('name');

        /* Determinar tipo OG segun tipo de post */
        $postType = get_post_type($postId);
        $ogType = ($postType === 'post') ? 'article' : 'website';

        $tags = [
            'og:type' => $ogType,
            'og:title' => esc_attr($seo['title']),
            'og:description' => esc_attr($seo['desc']),
            'og:url' => esc_url($url),
            'og:site_name' => esc_attr($siteName),
            'og:locale' => get_locale(),
        ];

        if ($image !== '') {
            $tags['og:image'] = esc_url($image);
        }

        /* Para articulos: fecha de publicacion y modificacion */
        if ($ogType === 'article') {
            $tags['article:published_time'] = get_the_date('c', $postId);
            $tags['article:modified_time'] = get_the_modified_date('c', $postId);
        }

        foreach ($tags as $property => $content) {
            if ($content !== '') {
                echo "<meta property=\"{$property}\" content=\"{$content}\" />\n";
            }
        }
    }

    /**
     * Imprime meta tags Twitter Cards en wp_head.
     */
    public static function printTwitterCards(): void
    {
        if (!is_page() && !is_singular()) {
            return;
        }

        $postId = get_queried_object_id();
        $seo = MetaTagRenderer::getSeoData($postId);
        $image = MetaTagRenderer::getOgImage($postId);

        $tags = [
            'twitter:card' => $image !== '' ? 'summary_large_image' : 'summary',
            'twitter:title' => esc_attr($seo['title']),
            'twitter:description' => esc_attr($seo['desc']),
        ];

        if ($image !== '') {
            $tags['twitter:image'] = esc_url($image);
        }

        foreach ($tags as $name => $content) {
            if ($content !== '') {
                echo "<meta name=\"{$name}\" content=\"{$content}\" />\n";
            }
        }
    }
}
