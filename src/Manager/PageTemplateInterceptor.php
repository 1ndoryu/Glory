<?php

namespace Glory\Manager;

use Glory\Core\GloryLogger;
use Glory\Utility\UserUtility;

/*
 * Responsabilidad: interceptar la resolución de templates de WordPress
 * para redirigir páginas gestionadas a TemplateReact.php.
 * Extraído de PageManager para cumplir SRP.
 */
class PageTemplateInterceptor
{
    private static mixed $funcionParaRenderizar = null;

    public static function register(): void
    {
        add_filter('template_include', [self::class, 'interceptarPlantilla'], 99);
        add_filter('the_content', [self::class, 'disableAutoPForManagedPages'], 1);
    }

    public static function disableAutoPForManagedPages($content)
    {
        global $post;
        if ($post && get_post_meta($post->ID, '_page_manager_managed', true)) {
            remove_filter('the_content', 'wpautop');
            remove_filter('the_content', 'wptexturize');
        }
        return $content;
    }

    /**
     * Intercepta la resolución de template de WordPress.
     * Redirige páginas gestionadas a TemplateReact.php con control de acceso.
     */
    public static function interceptarPlantilla(string $plantilla): string
    {
        if (!is_page() || is_admin()) {
            return $plantilla;
        }

        $queriedId = get_queried_object_id();
        $slug = get_post_field('post_name', $queriedId);

        /*
         * Resolver la key de la página definida.
         * Para páginas hijas (ej: /soluciones/hosting/), el post_name es 'hosting'
         * pero la key en paginasDefinidas es 'soluciones/hosting'.
         */
        $lookupKey = $slug;
        $paginas = PageDefinition::getPaginasDefinidas();
        if (!isset($paginas[$slug])) {
            $fullPath = get_page_uri($queriedId);
            if ($fullPath && isset($paginas[$fullPath])) {
                $lookupKey = $fullPath;
            }
        }

        if (!isset($paginas[$lookupKey])) {
            return $plantilla;
        }

        $defPagina = $paginas[$lookupKey];
        $rolesRequeridos = $defPagina['roles'] ?? [];

        if (!empty($rolesRequeridos)) {
            if (!is_user_logged_in()) {
                $login_url = wp_login_url(get_permalink());
                wp_redirect($login_url);
                exit;
            } elseif (!UserUtility::tieneRoles($rolesRequeridos)) {
                wp_die('No tienes permiso para ver esta página.', 'Acceso Denegado', ['response' => 403]);
            }
        }

        if (!empty($defPagina['funcion'])) {
            self::$funcionParaRenderizar = $defPagina['funcion'];

            /* Todas las páginas gestionadas usan TemplateReact.php */
            $plantillaCentral = get_template_directory() . '/TemplateReact.php';

            if (file_exists($plantillaCentral)) {
                return $plantillaCentral;
            }
            GloryLogger::error("PageTemplateInterceptor: No se encontro la plantilla central TemplateReact.php.");
        }

        return $plantilla;
    }

    public static function getFuncionParaRenderizar(): mixed
    {
        return self::$funcionParaRenderizar;
    }
}
