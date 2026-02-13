<?php

namespace Glory\Seo;

use Glory\Manager\PageManager;

/**
 * JsonLdRenderer
 *
 * Renderiza bloques JSON-LD estructurados en wp_head:
 * - FAQPage y BreadcrumbList (desde post_meta o defaultSeoMap)
 * - Organization, WebSite, Service, BlogPosting
 */
class JsonLdRenderer
{
    /**
     * Punto de entrada: imprime JSON-LD de FAQ y Breadcrumb para la pagina actual.
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
     * Genera y imprime JSON-LD desde metadatos del post (FAQ + Breadcrumb).
     */
    public static function printJsonLdFromMeta(int $postId): void
    {
        $faqMeta = (string) get_post_meta($postId, MetaTagRenderer::META_FAQ, true);
        $bcMeta = (string) get_post_meta($postId, MetaTagRenderer::META_BREADCRUMB, true);
        $canonicalMeta = (string) get_post_meta($postId, MetaTagRenderer::META_CANONICAL, true);
        $lang = str_replace('_', '-', get_locale());

        /* Normalizar posibles literales unicode sin barra invertida (uXXXX -> \uXXXX) */
        $tmpFaq = preg_replace('/(?<!\\\\)u([0-9a-fA-F]{4})/', '\\\\u$1', $faqMeta);
        $faqMeta = is_string($tmpFaq) ? $tmpFaq : '';
        $tmpBc = preg_replace('/(?<!\\\\)u([0-9a-fA-F]{4})/', '\\\\u$1', $bcMeta);
        $bcMeta = is_string($tmpBc) ? $tmpBc : '';

        $graph = [];

        /* Obtener SEO por defecto del mapa */
        $slug = get_post_field('post_name', $postId);
        $defaultSeo = PageManager::getDefaultSeoForSlug(is_string($slug) ? $slug : '');

        /* Base ID: canonica > defaultSeo canonical > permalink local */
        $idBase = '';
        if ($canonicalMeta !== '') {
            $idBase = MetaTagRenderer::ensureTrailingSlash((string) esc_url_raw($canonicalMeta));
        } elseif (!empty($defaultSeo['canonical'])) {
            $idBase = MetaTagRenderer::ensureTrailingSlash((string) $defaultSeo['canonical']);
        } else {
            $idBase = MetaTagRenderer::ensureTrailingSlash((string) get_permalink($postId));
        }

        /* Procesar Breadcrumb */
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

        /* Fallback breadcrumb desde defaultSeoMap */
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

        /* Fallback breadcrumb generico si no hay nada */
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

        /* Procesar FAQ */
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

        /* Fallback FAQ desde defaultSeoMap */
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

        /* Imprimir JSON-LD si hay contenido */
        if (!empty($graph)) {
            $json = [
                '@context' => 'https://schema.org',
                '@graph' => $graph,
            ];
            echo '<script type="application/ld+json" data-glory-seo="1">' . wp_json_encode($json, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }

    /**
     * Imprime JSON-LD Organization + WebSite + Service/BlogPosting.
     * Solo una vez por pagina.
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
            $seo = MetaTagRenderer::getSeoData($postId);
            $graph[] = [
                '@type' => 'Service',
                '@id' => MetaTagRenderer::ensureTrailingSlash((string) get_permalink($postId)) . '#service',
                'name' => get_the_title($postId),
                'description' => $seo['desc'],
                'provider' => ['@id' => $siteUrl . '#organization'],
                'url' => MetaTagRenderer::ensureTrailingSlash((string) get_permalink($postId)),
            ];
        }

        /* Schema Article/BlogPosting para posts del blog */
        if (is_singular('post')) {
            $postId = get_queried_object_id();
            $seo = MetaTagRenderer::getSeoData($postId);
            $image = MetaTagRenderer::getOgImage($postId);

            $article = [
                '@type' => 'BlogPosting',
                '@id' => MetaTagRenderer::ensureTrailingSlash((string) get_permalink($postId)) . '#article',
                'headline' => get_the_title($postId),
                'description' => $seo['desc'],
                'url' => MetaTagRenderer::ensureTrailingSlash((string) get_permalink($postId)),
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
