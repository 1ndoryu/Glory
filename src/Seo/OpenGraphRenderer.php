<?php

namespace Glory\Seo;

/**
 * OpenGraphRenderer
 *
 * Renderiza meta tags Open Graph y Twitter Cards en wp_head.
 * Delega la obtencion de datos SEO e imagen a MetaTagRenderer.
 * Consulta RuntimeSeoData primero para paginas dinamicas (samples, perfiles, etc.).
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
        $image = MetaTagRenderer::getOgImage($postId);
        $siteName = get_bloginfo('name');

        /* URL: RuntimeSeoData canonical > permalink */
        $url = '';
        if (RuntimeSeoData::has()) {
            $url = RuntimeSeoData::get('canonical', '');
        }
        if ($url === '') {
            $url = MetaTagRenderer::ensureTrailingSlash((string) get_permalink($postId));
        }

        /* Determinar tipo OG: RuntimeSeoData > tipo de post */
        $ogType = 'website';
        if (RuntimeSeoData::has()) {
            $runtimeType = RuntimeSeoData::get('ogType', '');
            if ($runtimeType !== '') {
                $ogType = $runtimeType;
            }
        } elseif (get_post_type($postId) === 'post') {
            $ogType = 'article';
        }

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

        /* Audio preview para samples (og:audio) */
        if (RuntimeSeoData::has()) {
            $ogAudio = RuntimeSeoData::get('ogAudio', '');
            if ($ogAudio !== '') {
                $tags['og:audio'] = esc_url($ogAudio);
                $tags['og:audio:type'] = 'audio/mpeg';
            }
        }

        /* Para articulos: fecha de publicacion y modificacion */
        if ($ogType === 'article') {
            $extra = RuntimeSeoData::has() ? RuntimeSeoData::get('extra', []) : [];
            $published = $extra['publishedAt'] ?? '';
            $modified = $extra['modifiedAt'] ?? '';

            if ($published !== '') {
                $tags['article:published_time'] = $published;
            } else {
                $tags['article:published_time'] = get_the_date('c', $postId);
            }
            if ($modified !== '') {
                $tags['article:modified_time'] = $modified;
            } else {
                $tags['article:modified_time'] = get_the_modified_date('c', $postId);
            }
        }

        /* Para music.song: fechas si estan disponibles */
        if ($ogType === 'music.song' && RuntimeSeoData::has()) {
            $extra = RuntimeSeoData::get('extra', []);
            if (!empty($extra['publishedAt'])) {
                $tags['music:release_date'] = date('Y-m-d', strtotime($extra['publishedAt']));
            }
        }

        foreach ($tags as $property => $content) {
            if ($content !== '' && $content !== false) {
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

        /* Twitter player card para samples con audio */
        $cardType = $image !== '' ? 'summary_large_image' : 'summary';
        if (RuntimeSeoData::has() && RuntimeSeoData::get('ogAudio', '') !== '') {
            $cardType = 'player';
        }

        $tags = [
            'twitter:card' => $cardType,
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
