<?php

namespace Glory\Manager;

use Glory\Core\GloryLogger;

/*
 * Responsabilidad: definir y registrar páginas gestionadas.
 * Almacena el estado estático de páginas definidas, React full-page, etc.
 * Extraído de PageManager para cumplir SRP (max 300 líneas).
 */
class PageDefinition
{
    private const CLAVE_MODO_CONTENIDO = '_glory_content_mode';

    private static array $paginasDefinidas = [];
    private static string $modoPorDefecto = 'code';
    private static array $paginasReactFullpage = [];
    private static array $reactPageConfigs = [];

    /*
     * Slugs de páginas padre que aceptan segmentos dinámicos.
     * Ej: 'perfil' permite /perfil/{username}.
     */
    private static array $rutasDinamicas = [];

    /**
     * Registra slugs como páginas React Fullpage.
     * Las páginas React Fullpage renderizan su propio layout (sin header/footer de WP).
     *
     * @param array $slugs Array de slugs a registrar como React Fullpage
     */
    public static function registerReactFullPages(array $slugs): void
    {
        self::$paginasReactFullpage = array_unique(
            array_merge(self::$paginasReactFullpage, $slugs)
        );
    }

    public static function isReactFullPage(string $slug): bool
    {
        return in_array($slug, self::$paginasReactFullpage, true);
    }

    /**
     * Define una página gestionada.
     *
     * @param string $slug El slug de la página.
     * @param string|null $handler Título, función de renderizado, o nombre del archivo de plantilla.
     * @param string|null $plantilla Opcional. Nombre del archivo de plantilla si se provee un título en $handler.
     * @param array $roles Roles requeridos para ver la página.
     */
    public static function define(string $slug, ?string $handler = null, ?string $plantilla = null, array $roles = []): void
    {
        if (empty($slug) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            GloryLogger::error("PageDefinition: Slug inválido '{$slug}'.");
            return;
        }

        $titulo = ucwords(str_replace(['-', '_'], ' ', $slug));
        $nombreFuncion = null;
        $nombrePlantilla = "Template" . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug))) . ".php";

        if ($handler) {
            if (str_ends_with($handler, '.php')) {
                $nombrePlantilla = $handler;
            } elseif ($plantilla) {
                $titulo = $handler;
                $nombrePlantilla = $plantilla;
            } else {
                $titulo = ucwords(str_replace(['-', '_'], ' ', $slug));
                $nombrePlantilla = 'TemplateReact.php';
                $nombreFuncion = $handler;
            }
        }

        self::$paginasDefinidas[$slug] = [
            'titulo'      => $titulo,
            'plantilla'   => $nombrePlantilla,
            'funcion'     => $nombreFuncion,
            'slug'        => $slug,
            'roles'       => $roles,
            'parentSlug'  => null,
        ];
    }

    /**
     * Define una página gestionada como hija de otra.
     * La URL resultante será /padre/hijo/.
     */
    public static function defineWithParent(string $parentSlug, string $slug, ?string $handler = null, array $roles = []): void
    {
        self::define($slug, $handler, null, $roles);
        if (isset(self::$paginasDefinidas[$slug])) {
            self::$paginasDefinidas[$slug]['parentSlug'] = $parentSlug;
        }
    }

    /**
     * Define una página React de forma simplificada.
     *
     * Combina: registrar ReactFullPage + definir página + handler auto-generado.
     *
     * @param string $slug Slug de la URL (ej: 'about', 'home-static')
     * @param string $islandName Nombre del Island React (ej: 'HomeStaticIsland')
     * @param array|callable|null $props Props estáticos o callback que retorna props
     * @param array $roles Roles requeridos para ver la página
     */
    public static function reactPage(
        string $slug,
        string $islandName,
        array|callable|null $props = null,
        array $roles = []
    ): void {
        /* Soporte para slugs anidados (ej: 'soluciones/hosting') */
        $parentSlug = null;
        $childSlug = $slug;

        if (str_contains($slug, '/')) {
            $parts = explode('/', $slug);
            $childSlug = array_pop($parts);
            $parentSlug = implode('/', $parts);

            foreach (array_merge($parts, [$childSlug]) as $segmento) {
                if (empty($segmento) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $segmento)) {
                    GloryLogger::error("PageDefinition::reactPage: Segmento invalido '{$segmento}' en slug '{$slug}'.");
                    return;
                }
            }
        } else {
            if (empty($slug) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                GloryLogger::error("PageDefinition::reactPage: Slug invalido '{$slug}'.");
                return;
            }
        }

        /*
         * Auto-registrar paginas padre stub cuando no estan definidas.
         * Necesario para que PageProcessor las cree en WP ANTES que las hijas,
         * garantizando la jerarquia correcta (ej: /admin/ existe antes de /admin/panel/).
         * Soporta multiples niveles: 'a/b/c' crea stubs para 'a' y 'a/b'.
         */
        if ($parentSlug) {
            $niveles = explode('/', $parentSlug);
            $acumulado = '';
            foreach ($niveles as $i => $nivel) {
                $acumulado = $i === 0 ? $nivel : $acumulado . '/' . $nivel;
                if (!isset(self::$paginasDefinidas[$acumulado])) {
                    $parentDeNivel = $i > 0 ? implode('/', array_slice($niveles, 0, $i)) : null;
                    self::$paginasDefinidas[$acumulado] = [
                        'titulo'      => ucwords(str_replace(['-', '_'], ' ', $nivel)),
                        'plantilla'   => '',
                        'funcion'     => null,
                        'slug'        => $nivel,
                        'roles'       => [],
                        'parentSlug'  => $parentDeNivel,
                    ];
                }
            }
        }

        /* Registrar como ReactFullPage */
        if (!in_array($slug, self::$paginasReactFullpage, true)) {
            self::$paginasReactFullpage[] = $slug;
        }
        if ($parentSlug && !in_array($childSlug, self::$paginasReactFullpage, true)) {
            self::$paginasReactFullpage[] = $childSlug;
        }

        /* Guardar configuración */
        self::$reactPageConfigs[$slug] = [
            'islandName' => $islandName,
            'props' => $props,
        ];

        $nombreFuncion = '_glory_react_page_' . str_replace(['-', '/'], '_', $slug);
        if (!function_exists($nombreFuncion)) {
            $GLOBALS['_glory_react_configs'][$slug] = [
                'islandName' => $islandName,
                'props' => $props,
            ];
        }

        $titulo = ucwords(str_replace(['-', '_'], ' ', $childSlug));

        self::$paginasDefinidas[$slug] = [
            'titulo'      => $titulo,
            'plantilla'   => 'TemplateReact.php',
            'funcion'     => [self::class, 'renderReactIsland'],
            'slug'        => $childSlug,
            'roles'       => $roles,
            'parentSlug'  => $parentSlug,
            'isReactPage' => true,
            'islandName'  => $islandName,
            'islandProps' => $props,
        ];
    }

    /**
     * Renderiza un React Island para páginas definidas con reactPage().
     */
    public static function renderReactIsland(): void
    {
        $queriedId = get_queried_object_id();
        $slug = get_post_field('post_name', $queriedId);
        $config = self::$paginasDefinidas[$slug] ?? null;

        /* Fallback: páginas hijas, buscar por path completo */
        if (!$config || empty($config['isReactPage'])) {
            $fullPath = get_page_uri($queriedId);
            if ($fullPath) {
                $config = self::$paginasDefinidas[$fullPath] ?? null;
            }
        }

        /*
         * Fallback robusto: reconstruir ruta desde parent WP,
         * o buscar por slug en todas las definiciones.
         */
        if (!$config || empty($config['isReactPage'])) {
            $parentId = wp_get_post_parent_id($queriedId);
            if ($parentId) {
                $parentSlug = get_post_field('post_name', $parentId);
                $candidato = $parentSlug . '/' . $slug;
                $config = self::$paginasDefinidas[$candidato] ?? null;
            }

            if (!$config || empty($config['isReactPage'])) {
                foreach (self::$paginasDefinidas as $def) {
                    if (($def['slug'] ?? '') === $slug && !empty($def['isReactPage'])) {
                        $config = $def;
                        break;
                    }
                }
            }
        }

        if (!$config || empty($config['isReactPage'])) {
            echo '<!-- PageDefinition: No se encontro configuracion para ' . esc_html($slug) . ' -->';
            return;
        }

        $islandName = $config['islandName'];
        $propsConfig = $config['islandProps'] ?? null;
        $pageId = get_the_ID() ?: 0;

        $props = [];
        if (is_callable($propsConfig)) {
            $props = call_user_func($propsConfig, $pageId);
            if (!is_array($props)) {
                $props = [];
            }
        } elseif (is_array($propsConfig)) {
            $props = $propsConfig;
        }

        if (class_exists('Glory\\Services\\ReactIslands')) {
            echo \Glory\Services\ReactIslands::render($islandName, $props);
        } else {
            echo '<!-- PageDefinition: ReactIslands no disponible -->';
        }
    }

    /* ── Rutas dinámicas: /perfil/{username}, /sample/{slug}, etc. ── */

    /**
     * Registra un slug como ruta dinámica.
     * Permite que /slug/{segmento} resuelva a la página padre.
     */
    public static function registrarRutaDinamica(string $padreSlug): void
    {
        if (!in_array($padreSlug, self::$rutasDinamicas, true)) {
            self::$rutasDinamicas[] = $padreSlug;
        }
    }

    public static function getRutasDinamicas(): array
    {
        return self::$rutasDinamicas;
    }

    /* Accesores de estado (usados por PageTemplateInterceptor, PageProcessor, etc.) */

    public static function getPaginasDefinidas(): array
    {
        return self::$paginasDefinidas;
    }

    public static function getDefinicionPorSlug(string $slug): ?array
    {
        return self::$paginasDefinidas[$slug] ?? null;
    }

    public static function getHandlerPorSlug(string $slug): ?string
    {
        return isset(self::$paginasDefinidas[$slug]) ? (self::$paginasDefinidas[$slug]['funcion'] ?? null) : null;
    }

    public static function setDefaultContentMode(string $mode): void
    {
        if (in_array($mode, ['code', 'editor'], true)) {
            self::$modoPorDefecto = $mode;
        }
    }

    public static function getDefaultContentMode(): string
    {
        return self::$modoPorDefecto;
    }

    public static function getModoContenidoParaPagina(int $postId): string
    {
        $modo = get_post_meta($postId, self::CLAVE_MODO_CONTENIDO, true);
        return in_array($modo, ['code', 'editor'], true) ? $modo : 'code';
    }

    /**
     * Devuelve el mapa de rutas React para navegacion SPA client-side.
     * Formato: { '/slug/': { island: 'IslandName', props: {...} } }
     */
    public static function getReactPageRoutes(): array
    {
        $routes = [];

        foreach (self::$paginasDefinidas as $slug => $config) {
            if (empty($config['isReactPage'])) {
                continue;
            }

            $path = '/' . trim($slug, '/') . '/';
            if ($slug === 'home') {
                $path = '/';
            }

            $props = [];
            $propsConfig = $config['islandProps'] ?? null;

            if (is_array($propsConfig)) {
                $props = $propsConfig;
            }
            /* Los callable props no se serializan, se omiten del mapa estatico */

            $routes[$path] = [
                'island' => $config['islandName'],
                'props' => $props,
                'title' => $config['titulo'] ?? '',
            ];
        }

        return $routes;
    }
}
