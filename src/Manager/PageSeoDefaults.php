<?php

namespace Glory\Manager;

/*
 * Responsabilidad: gestionar valores SEO por defecto para páginas gestionadas.
 * Separado de PageManager para cumplir SRP.
 */
class PageSeoDefaults
{
    private static array $defaultSeoMap = [];

    /**
     * Define valores SEO por defecto por slug.
     * Estructura: ['slug' => ['title' => '', 'desc' => '', 'canonical' => '', 'faq' => [], 'breadcrumb' => []]]
     */
    public static function setDefaultSeoMap(array $map): void
    {
        self::$defaultSeoMap = $map;
    }

    /**
     * Devuelve la configuración SEO por defecto para un slug si existe.
     */
    public static function getDefaultSeoForSlug(string $slug): array
    {
        return isset(self::$defaultSeoMap[$slug]) && is_array(self::$defaultSeoMap[$slug])
            ? self::$defaultSeoMap[$slug]
            : [];
    }

    /**
     * Aplica SEO por defecto a una página (sin sobreescribir valores manuales).
     */
    public static function aplicarSeoPorDefecto(int $postId, string $slug): void
    {
        if ($slug === '' || empty(self::$defaultSeoMap[$slug]) || !is_array(self::$defaultSeoMap[$slug])) {
            return;
        }

        $def = self::$defaultSeoMap[$slug];
        $title = isset($def['title']) ? (string) $def['title'] : '';
        $desc = isset($def['desc']) ? (string) $def['desc'] : '';
        $canonical = isset($def['canonical']) ? (string) $def['canonical'] : '';
        $faq = isset($def['faq']) && is_array($def['faq']) ? $def['faq'] : [];
        $bc = isset($def['breadcrumb']) && is_array($def['breadcrumb']) ? $def['breadcrumb'] : [];

        if ($title !== '' && get_post_meta($postId, '_glory_seo_title', true) === '') {
            update_post_meta($postId, '_glory_seo_title', $title);
        }
        if ($desc !== '' && get_post_meta($postId, '_glory_seo_desc', true) === '') {
            update_post_meta($postId, '_glory_seo_desc', $desc);
        }
        if ($canonical !== '' && get_post_meta($postId, '_glory_seo_canonical', true) === '') {
            if (substr($canonical, -1) !== '/') {
                $canonical .= '/';
            }
            update_post_meta($postId, '_glory_seo_canonical', $canonical);
        }
        if (!empty($faq) && get_post_meta($postId, '_glory_seo_faq', true) === '') {
            update_post_meta($postId, '_glory_seo_faq', wp_json_encode($faq, JSON_UNESCAPED_UNICODE));
        }
        if (!empty($bc) && get_post_meta($postId, '_glory_seo_breadcrumb', true) === '') {
            update_post_meta($postId, '_glory_seo_breadcrumb', wp_json_encode($bc, JSON_UNESCAPED_UNICODE));
        }
    }
}
