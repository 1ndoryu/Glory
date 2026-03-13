<?php

namespace Glory\Seo;

use Glory\Manager\PageManager;

/**
 * MetaTagRenderer
 *
 * Renderiza meta tags basicos de SEO: titulo, descripcion, canonical.
 * Expone helpers reutilizados por OpenGraphRenderer y JsonLdRenderer.
 *
 * Prioridad de datos: RuntimeSeoData (dinamico) > post_meta > defaultSeoMap > fallback post
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
     * Consulta RuntimeSeoData primero para soportar SEO dinamico.
     */
    public static function getSeoData(int $postId): array
    {
        /* Override de SEO dinamico (sample, perfil, coleccion) */
        if (RuntimeSeoData::has()) {
            $runtimeTitle = RuntimeSeoData::get('title', '');
            $runtimeDesc = RuntimeSeoData::get('description', '');
            if ($runtimeTitle !== '' || $runtimeDesc !== '') {
                return [
                    'title' => $runtimeTitle !== '' ? $runtimeTitle : get_the_title($postId),
                    'desc'  => $runtimeDesc,
                ];
            }
        }

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
     * Obtiene la imagen OG. Prioriza RuntimeSeoData para paginas dinamicas.
     */
    public static function getOgImage(int $postId): string
    {
        /* Override de SEO dinamico */
        if (RuntimeSeoData::has()) {
            $runtimeImage = RuntimeSeoData::get('ogImage', '');
            if ($runtimeImage !== '') {
                return $runtimeImage;
            }
        }

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
        /* Manejo explícito de la portada (front page) */
        if (is_front_page()) {
            /* RuntimeSeoData para override dinámico */
            if (RuntimeSeoData::has()) {
                $runtimeTitle = RuntimeSeoData::get('title', '');
                if ($runtimeTitle !== '') {
                    $parts['title'] = $runtimeTitle;
                    unset($parts['site'], $parts['tagline']);
                    return $parts;
                }
            }

            /* Buscar SEO del front page: primero por page_on_front, luego por slug 'home' */
            $frontPageId = (int) get_option('page_on_front');
            $seoTitle = '';

            if ($frontPageId > 0) {
                $seoTitle = (string) get_post_meta($frontPageId, self::META_TITLE, true);
                if ($seoTitle === '') {
                    $slug = get_post_field('post_name', $frontPageId);
                    if (is_string($slug) && $slug !== '') {
                        $defaults = PageManager::getDefaultSeoForSlug($slug);
                        if (!empty($defaults['title'])) {
                            $seoTitle = (string) $defaults['title'];
                        }
                    }
                }
            }

            /* Fallback para homepage sin static page (show_on_front=posts) */
            if ($seoTitle === '') {
                $defaults = PageManager::getDefaultSeoForSlug('home');
                if (!empty($defaults['title'])) {
                    $seoTitle = (string) $defaults['title'];
                }
            }

            if ($seoTitle !== '') {
                $parts['title'] = $seoTitle;
                unset($parts['site'], $parts['tagline']);
            }
            return $parts;
        }

        if (!is_page() && !is_singular()) {
            return $parts;
        }

        /* Override de SEO dinamico */
        if (RuntimeSeoData::has()) {
            $runtimeTitle = RuntimeSeoData::get('title', '');
            if ($runtimeTitle !== '') {
                $parts['title'] = $runtimeTitle;
                unset($parts['site'], $parts['tagline']);
                return $parts;
            }
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
        if (!is_page() && !is_singular() && !is_front_page()) {
            return;
        }

        /* Override de SEO dinamico */
        if (RuntimeSeoData::has()) {
            $runtimeCanonical = RuntimeSeoData::get('canonical', '');
            if ($runtimeCanonical !== '') {
                $href = esc_url(self::ensureTrailingSlash($runtimeCanonical));
                echo "<link rel=\"canonical\" href=\"{$href}\" />\n";
                return;
            }
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
                return;
            }
        }

        /* Fallback final: permalink del post (wp core canonical fue removido) */
        $permalink = get_permalink($postId);
        if (is_string($permalink) && $permalink !== '') {
            $href = esc_url(self::ensureTrailingSlash($permalink));
            echo "<link rel=\"canonical\" href=\"{$href}\" />\n";
        }
    }

    /**
     * Imprime meta description en wp_head.
     */
    public static function printMetaDescription(): void
    {
        if (!is_page() && !is_singular() && !is_front_page()) {
            return;
        }

        /* Override de SEO dinamico */
        if (RuntimeSeoData::has()) {
            $runtimeDesc = RuntimeSeoData::get('description', '');
            if ($runtimeDesc !== '') {
                $content = esc_attr($runtimeDesc);
                echo "<meta name=\"description\" content=\"{$content}\" />\n";
                return;
            }
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

    /**
     * Imprime meta robots en wp_head.
     * Solo emite si RuntimeSeoData o defaultSeoMap especifican robots.
     */
    public static function printRobots(): void
    {
        if (!is_page() && !is_singular()) {
            return;
        }

        /* Override de SEO dinamico */
        if (RuntimeSeoData::has()) {
            $robots = RuntimeSeoData::get('robots', '');
            if ($robots !== '') {
                echo '<meta name="robots" content="' . esc_attr($robots) . '" />' . "\n";
                return;
            }
        }

        /* Fallback a defaultSeoMap */
        $postId = get_queried_object_id();
        $slug = get_post_field('post_name', $postId);
        if (is_string($slug) && $slug !== '') {
            $defaults = PageManager::getDefaultSeoForSlug($slug);
            if (!empty($defaults['robots'])) {
                echo '<meta name="robots" content="' . esc_attr((string) $defaults['robots']) . '" />' . "\n";
            }
        }
    }

    /**
     * Imprime meta keywords en wp_head (solo si hay datos dinamicos).
     */
    public static function printKeywords(): void
    {
        if (!RuntimeSeoData::has()) {
            return;
        }

        $extra = RuntimeSeoData::get('extra', []);
        $keywords = $extra['keywords'] ?? '';
        if ($keywords !== '') {
            echo '<meta name="keywords" content="' . esc_attr($keywords) . '" />' . "\n";
        }
    }
}
