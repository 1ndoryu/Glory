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

            /*
             * Rutas multi-segmento: /sampleo/168/seo-slug → segmento='168/seo-slug'
             * Solo permitir si la ruta dinámica declara multi-segmento.
             * Rutas simples (single segment) rechazan sub-segmentos como antes.
             */
            if (empty($segmento)) {
                continue;
            }

            $tieneSubSegmentos = str_contains($segmento, '/');
            if ($tieneSubSegmentos && !PageDefinition::rutaDinamicaPermiteMultiSegmento($padreSlug)) {
                continue;
            }

            /* Verificar que NO sea una página hija conocida (ej: /perfil/editar).
             * Para rutas multi-segmento, comprobar solo el primer sub-segmento. */
            $primerSegmento = str_contains($segmento, '/')
                ? strstr($segmento, '/', true)
                : $segmento;
            $paginasDefinidas = PageDefinition::getPaginasDefinidas();
            $esHijoConocido = false;
            foreach ($paginasDefinidas as $key => $def) {
                if (($def['parentSlug'] ?? '') === $padreSlug && $def['slug'] === $primerSegmento) {
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
     * Fuerza la resolución de rutas que WordPress resolvió como 404.
     * Cubre dos casos:
     * 1. Rutas dinámicas (/perfil/{username}) donde parse_request redirigió
     *    pero WP aún marcó 404.
     * 2. Páginas estáticas definidas (/admin/panel) que WP no resolvió
     *    por problemas de rewrite rules, caché o jerarquía padre/hijo.
     */
    public static function forzarResolucionDinamica(): void
    {
        if (!is_404()) {
            return;
        }

        $requestPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        if (empty($requestPath)) {
            return;
        }

        /* 1. Intentar resolver como ruta dinámica */
        $rutasDinamicas = PageDefinition::getRutasDinamicas();
        foreach ($rutasDinamicas as $padreSlug) {
            if (strpos($requestPath, $padreSlug . '/') !== 0) {
                continue;
            }

            $segmento = substr($requestPath, strlen($padreSlug) + 1);
            $segmento = trim($segmento, '/');

            if (empty($segmento)) {
                continue;
            }

            $tieneSubSegmentos = str_contains($segmento, '/');
            if ($tieneSubSegmentos && !PageDefinition::rutaDinamicaPermiteMultiSegmento($padreSlug)) {
                continue;
            }

            $paginaPadre = get_page_by_path($padreSlug);
            if (!$paginaPadre) {
                /*
                 * QQ28: Auto-reparar si la página está definida en PageDefinition
                 * pero no existe en WordPress (nunca sincronizada o eliminada).
                 * Crea la página on-the-fly para que el routing funcione sin
                 * requerir sync manual. Usa transient para no repetir cada request.
                 */
                $paginasDefinidas = PageDefinition::getPaginasDefinidas();
                if (isset($paginasDefinidas[$padreSlug])) {
                    $transientKey = 'glory_autofix_' . $padreSlug;
                    if (false === get_transient($transientKey)) {
                        $nuevoId = PageProcessor::crearPaginaDefinida($paginasDefinidas[$padreSlug]);
                        set_transient($transientKey, true, 300);
                        if ($nuevoId) {
                            $paginaPadre = get_post($nuevoId);
                            GloryLogger::info("PageTemplateInterceptor: Auto-creada página faltante '{$padreSlug}' (ID={$nuevoId})");
                        }
                    }
                }
                if (!$paginaPadre) {
                    continue;
                }
            }

            self::forzarQueryAPagina($paginaPadre);
            GloryLogger::info("PageTemplateInterceptor: Forzado 404 → ruta dinámica '{$padreSlug}' para /{$requestPath}/");
            return;
        }

        /* 2. Intentar resolver como página estática definida en PageDefinition.
         * Cubre páginas que WP no resuelve por rewrite rules, caché, o jerarquía
         * padre/hijo no sincronizada. Ej: /admin/panel accedido directamente. */
        $paginasDefinidas = PageDefinition::getPaginasDefinidas();
        if (!isset($paginasDefinidas[$requestPath])) {
            return;
        }

        $pagina = get_page_by_path($requestPath);
        if (!$pagina) {
            /* QQ28: Auto-crear página estática definida pero ausente en WP */
            $transientKey = 'glory_autofix_' . str_replace('/', '_', $requestPath);
            if (false === get_transient($transientKey)) {
                $nuevoId = PageProcessor::crearPaginaDefinida($paginasDefinidas[$requestPath]);
                set_transient($transientKey, true, 300);
                if ($nuevoId) {
                    $pagina = get_post($nuevoId);
                    GloryLogger::info("PageTemplateInterceptor: Auto-creada página estática faltante '{$requestPath}' (ID={$nuevoId})");
                }
            }
            if (!$pagina) {
                return;
            }
        }

        self::forzarQueryAPagina($pagina);
        GloryLogger::info("PageTemplateInterceptor: Forzado 404 → página estática '{$requestPath}' (acceso directo)");
    }

    /**
     * Fuerza la main query de WordPress para que resuelva a una página específica.
     * Cambia el estado 404 a 200 y configura todos los campos necesarios
     * para que template_include y el resto del pipeline funcionen correctamente.
     */
    private static function forzarQueryAPagina(\WP_Post $pagina): void
    {
        global $wp_query;
        $wp_query->is_404 = false;
        $wp_query->is_page = true;
        $wp_query->is_singular = true;
        $wp_query->post = $pagina;
        $wp_query->posts = [$pagina];
        $wp_query->found_posts = 1;
        $wp_query->post_count = 1;
        $wp_query->queried_object = $pagina;
        $wp_query->queried_object_id = $pagina->ID;

        status_header(200);
    }
}
