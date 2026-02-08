<?php

namespace Glory\Seo;

use Glory\Manager\PageManager;

/**
 * SeoFrontendRenderer
 * 
 * Renderiza las meta tags SEO en el frontend de WordPress de forma agnostica.
 * Lee datos de post_meta (_glory_seo_*) con fallback a PageManager::getDefaultSeoForSlug().
 * 
 * Genera:
 * - Titulo del documento (filtro document_title_parts)
 * - Meta description
 * - Link canonical
 * - Open Graph (og:title, og:description, og:image, og:url, og:type)
 * - Twitter Cards (twitter:card, twitter:title, twitter:description, twitter:image)
 * - JSON-LD FAQPage
 * - JSON-LD BreadcrumbList
 * - JSON-LD Organization / WebSite
 * - JSON-LD Service (para paginas de servicio)
 * - JSON-LD Article / BlogPosting (para posts del blog)
 * 
 * Esta es la version agnostica que vive en Glory/ y puede usarse en cualquier proyecto.
 */
class SeoFrontendRenderer
{
    private const META_TITLE = '_glory_seo_title';
    private const META_DESC = '_glory_seo_desc';
    private const META_CANONICAL = '_glory_seo_canonical';
    private const META_FAQ = '_glory_seo_faq';
    private const META_BREADCRUMB = '_glory_seo_breadcrumb';

    private static bool $registered = false;

    /**
     * Registra los hooks de WordPress para renderizar SEO.
     * Debe llamarse una sola vez, idealmente desde Glory\Core\Setup.
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        // Soporte para title-tag
        add_action('after_setup_theme', function () {
            add_theme_support('title-tag');
        });

        // Filtro para el titulo del documento
        add_filter('document_title_parts', [self::class, 'filterDocumentTitle'], 20);

        // Remover canonical del core para evitar duplicados
        add_action('init', function () {
            remove_action('wp_head', 'rel_canonical');
        });

        // Hook para imprimir meta tags en wp_head
        add_action('wp_head', [self::class, 'printCanonical'], 1);
        add_action('wp_head', [self::class, 'printMetaDescription'], 1);
        add_action('wp_head', [self::class, 'printOpenGraph'], 1);
        add_action('wp_head', [self::class, 'printTwitterCards'], 1);

        // Hook para imprimir JSON-LD en wp_head
        add_action('wp_head', [self::class, 'printJsonLd'], 2);
        add_action('wp_head', [self::class, 'printJsonLdOrganization'], 2);
    }

    /**
     * Asegura trailing slash en URLs.
     */
    private static function ensureTrailingSlash(string $url): string
    {
        if ($url === '') {
            return $url;
        }
        return substr($url, -1) === '/' ? $url : ($url . '/');
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

        // Fallback a defaultSeoMap
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
            // Eliminar nombre del sitio y tagline para que quede exactamente el titulo SEO
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

        // Fallback a defaultSeoMap
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

        // Fallback a defaultSeoMap
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
     * Imprime JSON-LD para FAQPage y BreadcrumbList.
     */
    public static function printJsonLd(): void
    {
        if (!is_page() && !is_singular()) {
            return;
        }

        $postId = get_queried_object_id();
        self::printJsonLdFromMeta($postId);
    }

    /**
     * Genera y imprime JSON-LD desde metadatos del post.
     */
    public static function printJsonLdFromMeta(int $postId): void
    {
        $faqMeta = (string) get_post_meta($postId, self::META_FAQ, true);
        $bcMeta = (string) get_post_meta($postId, self::META_BREADCRUMB, true);
        $canonicalMeta = (string) get_post_meta($postId, self::META_CANONICAL, true);
        $lang = str_replace('_', '-', get_locale());

        // Normalizar posibles literales unicode sin barra invertida (uXXXX -> \uXXXX)
        $tmpFaq = preg_replace('/(?<!\\\\)u([0-9a-fA-F]{4})/', '\\\\u$1', $faqMeta);
        $faqMeta = is_string($tmpFaq) ? $tmpFaq : '';
        $tmpBc = preg_replace('/(?<!\\\\)u([0-9a-fA-F]{4})/', '\\\\u$1', $bcMeta);
        $bcMeta = is_string($tmpBc) ? $tmpBc : '';

        $graph = [];

        // Obtener SEO por defecto del mapa
        $slug = get_post_field('post_name', $postId);
        $defaultSeo = PageManager::getDefaultSeoForSlug(is_string($slug) ? $slug : '');

        // Base ID (usar canonica si existe, luego defaultSeo canonical, luego permalink local)
        $idBase = '';
        if ($canonicalMeta !== '') {
            $idBase = self::ensureTrailingSlash((string) esc_url_raw($canonicalMeta));
        } elseif (!empty($defaultSeo['canonical'])) {
            $idBase = self::ensureTrailingSlash((string) $defaultSeo['canonical']);
        } else {
            $idBase = self::ensureTrailingSlash((string) get_permalink($postId));
        }

        // Procesar Breadcrumb
        $bcHandled = false;
        if ($bcMeta !== '') {
            $bcArr = json_decode($bcMeta, true);
            if (is_array($bcArr) && !empty($bcArr)) {
                $items = self::buildBreadcrumbItems($bcArr);
                if (!empty($items)) {
                    $graph[] = [
                        '@type' => 'BreadcrumbList',
                        '@id' => $idBase . '#breadcrumb',
                        'itemListElement' => $items,
                    ];
                    $bcHandled = true;
                }
            }
        }

        // Fallback breadcrumb desde defaultSeoMap
        if (!$bcHandled && !empty($defaultSeo['breadcrumb']) && is_array($defaultSeo['breadcrumb'])) {
            $items = self::buildBreadcrumbItems($defaultSeo['breadcrumb']);
            if (!empty($items)) {
                $graph[] = [
                    '@type' => 'BreadcrumbList',
                    '@id' => $idBase . '#breadcrumb',
                    'itemListElement' => $items,
                ];
                $bcHandled = true;
            }
        }

        // Fallback breadcrumb generico si no hay nada
        if (!$bcHandled) {
            $items = [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Inicio',
                    'item' => home_url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => get_the_title($postId),
                    'item' => get_permalink($postId),
                ],
            ];
            $graph[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $idBase . '#breadcrumb',
                'itemListElement' => $items,
            ];
        }

        // Procesar FAQ
        $faqHandled = false;
        if ($faqMeta !== '') {
            $faqArr = json_decode($faqMeta, true);
            if (is_array($faqArr) && !empty($faqArr)) {
                $main = self::buildFaqMainEntity($faqArr);
                if (!empty($main)) {
                    $graph[] = [
                        '@type' => 'FAQPage',
                        '@id' => $idBase . '#faq',
                        'inLanguage' => $lang,
                        'mainEntity' => $main,
                    ];
                    $faqHandled = true;
                }
            }
        }

        // Fallback FAQ desde defaultSeoMap
        if (!$faqHandled && !empty($defaultSeo['faq']) && is_array($defaultSeo['faq'])) {
            $main = self::buildFaqMainEntity($defaultSeo['faq']);
            if (!empty($main)) {
                $graph[] = [
                    '@type' => 'FAQPage',
                    '@id' => $idBase . '#faq',
                    'inLanguage' => $lang,
                    'mainEntity' => $main,
                ];
            }
        }

        // Imprimir JSON-LD si hay contenido
        if (!empty($graph)) {
            $json = [
                '@context' => 'https://schema.org',
                '@graph' => $graph,
            ];
            echo '<script type="application/ld+json" data-glory-seo="1">' . wp_json_encode($json, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }

    /**
     * Obtiene titulo y descripcion SEO para el post actual.
     * Reutilizado por OG y Twitter Cards para evitar duplicar logica.
     */
    private static function getSeoData(int $postId): array
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
    private static function getOgImage(int $postId): string
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
     * Imprime meta tags Open Graph en wp_head.
     */
    public static function printOpenGraph(): void
    {
        if (!is_page() && !is_singular()) {
            return;
        }

        $postId = get_queried_object_id();
        $seo = self::getSeoData($postId);
        $url = self::ensureTrailingSlash((string) get_permalink($postId));
        $image = self::getOgImage($postId);
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

        /* Para artículos: fecha de publicacion y modificacion */
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
        $seo = self::getSeoData($postId);
        $image = self::getOgImage($postId);

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

    /**
     * Imprime JSON-LD Organization + WebSite (una vez por pagina).
     * Solo en la home o en todas las paginas (Google recomienda home).
     */
    public static function printJsonLdOrganization(): void
    {
        $graph = [];
        $siteUrl = home_url('/');
        $siteName = get_bloginfo('name');
        $siteDesc = get_bloginfo('description');

        /* Schema Organization */
        $org = [
            '@type' => 'Organization',
            '@id' => $siteUrl . '#organization',
            'name' => $siteName,
            'url' => $siteUrl,
            'description' => $siteDesc,
        ];

        /* Logo del sitio */
        $customLogoId = get_theme_mod('custom_logo');
        if ($customLogoId) {
            $logoUrl = wp_get_attachment_image_url($customLogoId, 'full');
            if ($logoUrl) {
                $org['logo'] = [
                    '@type' => 'ImageObject',
                    'url' => $logoUrl,
                ];
            }
        }

        $graph[] = $org;

        /* Schema WebSite con SearchAction */
        $graph[] = [
            '@type' => 'WebSite',
            '@id' => $siteUrl . '#website',
            'name' => $siteName,
            'url' => $siteUrl,
            'publisher' => ['@id' => $siteUrl . '#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $siteUrl . '?s={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        /* Schema Service para CPT servicio */
        if (is_singular('servicio')) {
            $postId = get_queried_object_id();
            $seo = self::getSeoData($postId);
            $graph[] = [
                '@type' => 'Service',
                '@id' => self::ensureTrailingSlash((string) get_permalink($postId)) . '#service',
                'name' => get_the_title($postId),
                'description' => $seo['desc'],
                'provider' => ['@id' => $siteUrl . '#organization'],
                'url' => self::ensureTrailingSlash((string) get_permalink($postId)),
            ];
        }

        /* Schema Article/BlogPosting para posts del blog */
        if (is_singular('post')) {
            $postId = get_queried_object_id();
            $seo = self::getSeoData($postId);
            $image = self::getOgImage($postId);

            $article = [
                '@type' => 'BlogPosting',
                '@id' => self::ensureTrailingSlash((string) get_permalink($postId)) . '#article',
                'headline' => get_the_title($postId),
                'description' => $seo['desc'],
                'url' => self::ensureTrailingSlash((string) get_permalink($postId)),
                'datePublished' => get_the_date('c', $postId),
                'dateModified' => get_the_modified_date('c', $postId),
                'author' => [
                    '@type' => 'Person',
                    'name' => get_the_author_meta('display_name', (int) get_post_field('post_author', $postId)),
                ],
                'publisher' => ['@id' => $siteUrl . '#organization'],
                'isPartOf' => ['@id' => $siteUrl . '#website'],
            ];

            if ($image !== '') {
                $article['image'] = $image;
            }

            $graph[] = $article;
        }

        if (!empty($graph)) {
            $json = [
                '@context' => 'https://schema.org',
                '@graph' => $graph,
            ];
            echo '<script type="application/ld+json" data-glory-seo="org">' . wp_json_encode($json, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }

    /**
     * Construye array de items para BreadcrumbList.
     */
    private static function buildBreadcrumbItems(array $breadcrumbArr): array
    {
        $items = [];
        $pos = 1;
        foreach ($breadcrumbArr as $item) {
            $name = isset($item['name']) ? trim((string) $item['name']) : '';
            $url = isset($item['url']) ? trim((string) $item['url']) : '';
            if ($name === '') {
                continue;
            }
            $entry = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'name' => $name,
            ];
            if ($url !== '') {
                $entry['item'] = $url;
            }
            $items[] = $entry;
        }
        return $items;
    }

    /**
     * Construye array mainEntity para FAQPage.
     */
    private static function buildFaqMainEntity(array $faqArr): array
    {
        $main = [];
        foreach ($faqArr as $qa) {
            $q = isset($qa['q']) ? trim((string) $qa['q']) : '';
            $a = isset($qa['a']) ? trim((string) $qa['a']) : '';
            if ($q === '' || $a === '') {
                continue;
            }
            $main[] = [
                '@type' => 'Question',
                'name' => $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $a,
                ],
            ];
        }
        return $main;
    }
}
