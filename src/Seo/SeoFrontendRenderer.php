<?php

namespace Glory\Seo;

/**
 * SeoFrontendRenderer
 *
 * Fachada delegadora que registra los hooks de WordPress para SEO
 * y redirige cada callback a su renderer especializado:
 *   - MetaTagRenderer: titulo, canonical, meta description
 *   - OpenGraphRenderer: Open Graph y Twitter Cards
 *   - JsonLdRenderer: JSON-LD (FAQ, Breadcrumb, Organization, BlogPosting, Service)
 */
class SeoFrontendRenderer
{
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

        /* Soporte para title-tag */
        add_action('after_setup_theme', function () {
            add_theme_support('title-tag');
        });

        /* Filtro para el titulo del documento */
        add_filter('document_title_parts', [MetaTagRenderer::class, 'filterDocumentTitle'], 20);

        /* Remover canonical del core para evitar duplicados */
        add_action('init', function () {
            remove_action('wp_head', 'rel_canonical');
        });

        /* Meta tags basicos */
        add_action('wp_head', [MetaTagRenderer::class, 'printCanonical'], 1);
        add_action('wp_head', [MetaTagRenderer::class, 'printMetaDescription'], 1);

        /* Open Graph y Twitter Cards */
        add_action('wp_head', [OpenGraphRenderer::class, 'printOpenGraph'], 1);
        add_action('wp_head', [OpenGraphRenderer::class, 'printTwitterCards'], 1);

        /* JSON-LD estructurado */
        add_action('wp_head', [JsonLdRenderer::class, 'printJsonLd'], 2);
        add_action('wp_head', [JsonLdRenderer::class, 'printJsonLdOrganization'], 2);
    }
}
