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

        /* Interceptar rutas dinámicas antes de que WP resuelva 404 */
        add_action('parse_request', [self::class, 'resolverRutaDinamica'], 20);
        
        /* Forzar resolución de 404 para rutas dinámicas que WP no resolvió */
        add_action('wp', [self::class, 'forzarResolucionDinamica'], 1);
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

        /*
         * Fallback robusto: si el lookup directo falla, reconstruir la ruta
         * desde el padre WP o buscar por slug en todas las definiciones.
         * Cubre el caso donde la página hija existe pero su parent_id
         * no se ha sincronizado aún con WP (ej: página recién registrada).
         */
        if (!isset($paginas[$lookupKey])) {
            $parentId = wp_get_post_parent_id($queriedId);
            if ($parentId) {
                $parentSlug = get_post_field('post_name', $parentId);
                $candidato = $parentSlug . '/' . $slug;
                if (isset($paginas[$candidato])) {
                    $lookupKey = $candidato;
                }
            }

            if (!isset($paginas[$lookupKey])) {
                foreach ($paginas as $key => $def) {
                    if (($def['slug'] ?? '') === $slug && !empty($def['isReactPage'])) {
                        $lookupKey = $key;
                        break;
                    }
                }
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

    /**
     * Resuelve rutas dinámicas tipo /perfil/{username} o /sample/{slug}.
     * Cuando WP no encuentra una página hija real, redirige la query
     * a la página padre registrada como ruta dinámica.
     */
    public static function resolverRutaDinamica(\WP $wp): void
    {
        $rutasDinamicas = PageDefinition::getRutasDinamicas();
        if (empty($rutasDinamicas)) {
            return;
        }

        $requestPath = trim($wp->request ?? '', '/');
        if (empty($requestPath)) {
            return;
        }

        foreach ($rutasDinamicas as $padreSlug) {
            if (strpos($requestPath, $padreSlug . '/') !== 0) {
                continue;
            }

            $segmento = substr($requestPath, strlen($padreSlug) + 1);
            $segmento = trim($segmento, '/');

            /* Ignorar si está vacío o tiene sub-segmentos (ej: /perfil/a/b) */
            if (empty($segmento) || str_contains($segmento, '/')) {
                continue;
            }

            /* Verificar que NO sea una página hija conocida (ej: /perfil/editar) */
            $paginasDefinidas = PageDefinition::getPaginasDefinidas();
            $esHijoConocido = false;
            foreach ($paginasDefinidas as $key => $def) {
                if (($def['parentSlug'] ?? '') === $padreSlug && $def['slug'] === $segmento) {
                    $esHijoConocido = true;
                    break;
                }
            }

            if ($esHijoConocido) {
                continue;
            }

            /* Redirigir query a la página padre */
            $wp->query_vars['pagename'] = $padreSlug;
            unset($wp->query_vars['name'], $wp->query_vars['error']);
            GloryLogger::info("PageTemplateInterceptor: Ruta dinámica resuelta — /{$requestPath}/ → página '{$padreSlug}'");
            break;
        }
    }

    /**
     * Fuerza la resolución de rutas dinámicas que WordPress resolvió como 404.
     * Esto cubre los casos donde parse_request redirigió pagename pero WP
     * aún marcó la query como 404 (típico en acceso directo por URL).
     */
    public static function forzarResolucionDinamica(): void
    {
        if (!is_404()) {
            return;
        }

        $rutasDinamicas = PageDefinition::getRutasDinamicas();
        if (empty($rutasDinamicas)) {
            return;
        }

        $requestPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        if (empty($requestPath)) {
            return;
        }

        foreach ($rutasDinamicas as $padreSlug) {
            if (strpos($requestPath, $padreSlug . '/') !== 0) {
                continue;
            }

            $segmento = substr($requestPath, strlen($padreSlug) + 1);
            $segmento = trim($segmento, '/');

            if (empty($segmento) || str_contains($segmento, '/')) {
                continue;
            }

            /* Buscar la página padre en WP */
            $paginaPadre = get_page_by_path($padreSlug);
            if (!$paginaPadre) {
                continue;
            }

            /* Forzar la main query para que resuelva a la página padre */
            global $wp_query;
            $wp_query->is_404 = false;
            $wp_query->is_page = true;
            $wp_query->is_singular = true;
            $wp_query->post = $paginaPadre;
            $wp_query->posts = [$paginaPadre];
            $wp_query->found_posts = 1;
            $wp_query->post_count = 1;
            $wp_query->queried_object = $paginaPadre;
            $wp_query->queried_object_id = $paginaPadre->ID;

            status_header(200);
            GloryLogger::info("PageTemplateInterceptor: Forzado 404 → página '{$padreSlug}' para /{$requestPath}/");
            break;
        }
    }
}
