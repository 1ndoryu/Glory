<?php
/**
 * Renderizador de Contenido
 *
 * Esta clase se encarga de renderizar listados de posts y contenidos de forma
 * eficiente, soportando caché, paginación AJAX, y opciones de configuración
 * flexibles para el tema.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

use WP_Query;
use Glory\Components\PaginationRenderer;
use Glory\Utility\ImageUtility;
use Glory\Core\GloryFeatures;
use Glory\Support\CSS\ContentRenderCss;
use Glory\Utility\TemplateRegistry;

/**
 * Clase ContentRender.
 *
 * Gestiona la visualización de cuadrículas y listas de posts con soporte
 * para caché de fragmentos HTML y paginación asíncrona.
 */
class ContentRender
{
    /** @var array<string,mixed> Configuración actual del renderizado. */
    private static $currentConfig = [];

    /** @var bool Indica si los hooks de limpieza de caché ya fueron registrados. */
    private static $hooksRegistered = false;

    /**
     * Devuelve la configuración predeterminada y el esquema para GBN (Glory Builder Native).
     *
     * @return array{config:array<string,mixed>,schema:array<int,array<string,mixed>>}
     */
    public static function gbnDefaults(): array
    {
        return [
            'config' => [
                'publicacionesPorPagina' => 6,
                'claseContenedor'        => 'gbn-content-grid',
                'claseItem'              => 'gbn-content-card',
                'argumentosConsulta'     => [],
                'forzarSinCache'         => false,
                'paginacion'             => false,
                'gap'                    => '20px',
                'display_mode'           => 'flex',
                'img_show'               => true,
                'title_show'             => true,
                'title_position'         => 'top',
                'title_show_on_hover'    => false,
                'content_opacity'        => 0.9,
            ],
            'schema' => [
                [
                    'id'       => 'postType',
                    'tipo'     => 'select',
                    'etiqueta' => 'Tipo de contenido',
                    'opciones' => [
                        ['valor' => 'post', 'etiqueta' => 'Entradas'],
                        ['valor' => 'page', 'etiqueta' => 'Páginas'],
                        ['valor' => 'libro', 'etiqueta' => 'Libros'],
                        ['valor' => 'servicio', 'etiqueta' => 'Servicios'],
                        ['valor' => 'portfolio', 'etiqueta' => 'Portafolio'],
                    ],
                ],
                [
                    'id'       => 'plantilla',
                    'tipo'     => 'select',
                    'etiqueta' => 'Plantilla (ID)',
                    'opciones' => self::getTemplateOptions(),
                    'descripcion' => 'Selecciona la plantilla de renderizado.',
                ],
                [
                    'id'       => 'publicacionesPorPagina',
                    'tipo'     => 'slider',
                    'etiqueta' => 'Publicaciones por página',
                    'min'      => 1,
                    'max'      => 50,
                    'paso'     => 1,
                ],
                [
                    'id'       => 'paginacion',
                    'tipo'     => 'toggle',
                    'etiqueta' => 'Activar paginación AJAX',
                ],
                // --- Layout Options ---
                [
                    'id'       => 'display_mode',
                    'tipo'     => 'icon_group',
                    'etiqueta' => 'Modo de Visualización',
                    'opciones' => [
                        ['valor' => 'block', 'etiqueta' => 'Bloque', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>'],
                        ['valor' => 'flex', 'etiqueta' => 'Flexbox', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 3v18"/></svg>'],
                        ['valor' => 'grid', 'etiqueta' => 'Grid', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>'],
                    ],
                    'default'  => 'flex',
                ],
                [
                    'id'       => 'gap',
                    'tipo'     => 'dimension',
                    'etiqueta' => 'Espaciado (Gap)',
                    'default'  => '20px',
                    'condicion' => ['display_mode', 'in', ['flex', 'grid']],
                ],
                // Flex Options
                [
                    'id'       => 'flex_direction',
                    'tipo'     => 'icon_group',
                    'etiqueta' => 'Dirección Flex',
                    'opciones' => [
                        ['valor' => 'row', 'etiqueta' => 'Horizontal', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><path d="M16 8l4 4-4 4"/></svg>'],
                        ['valor' => 'column', 'etiqueta' => 'Vertical', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16"/><path d="M8 16l4 4 4-4"/></svg>'],
                    ],
                    'condicion' => ['display_mode', '==', 'flex'],
                ],
                [
                    'id'       => 'flex_wrap',
                    'tipo'     => 'icon_group',
                    'etiqueta' => 'Envolver (Wrap)',
                    'opciones' => [
                        ['valor' => 'nowrap', 'etiqueta' => 'No envolver', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/></svg>'],
                        ['valor' => 'wrap', 'etiqueta' => 'Envolver', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8h10a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4"/><path d="M8 12l-4 4 4 4"/></svg>'],
                    ],
                    'condicion' => ['display_mode', '==', 'flex'],
                ],
                [
                    'id'       => 'justify_content',
                    'tipo'     => 'icon_group',
                    'etiqueta' => 'Justificar Contenido',
                    'opciones' => [
                        ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="6" height="18" rx="1"/></svg>'],
                        ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="3" width="6" height="18" rx="1"/></svg>'],
                        ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="15" y="3" width="6" height="18" rx="1"/></svg>'],
                        ['valor' => 'space-between', 'etiqueta' => 'Espacio entre', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="4" height="18" rx="1"/><rect x="17" y="3" width="4" height="18" rx="1"/></svg>'],
                        ['valor' => 'space-around', 'etiqueta' => 'Espacio alrededor', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="3" width="4" height="18" rx="1"/><rect x="15" y="3" width="4" height="18" rx="1"/></svg>'],
                    ],
                    'condicion' => ['display_mode', '==', 'flex'],
                ],
                [
                    'id'       => 'align_items',
                    'tipo'     => 'icon_group',
                    'etiqueta' => 'Alinear Items',
                    'opciones' => [
                        ['valor' => 'stretch', 'etiqueta' => 'Estirar', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3v18"/><path d="M20 3v18"/><rect x="8" y="6" width="8" height="12" rx="1"/></svg>'],
                        ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3h16"/><rect x="8" y="7" width="8" height="8" rx="1"/></svg>'],
                        ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>'],
                        ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21h16"/><rect x="8" y="9" width="8" height="8" rx="1"/></svg>'],
                    ],
                    'condicion' => ['display_mode', '==', 'flex'],
                ],
                // Grid Options
                [
                    'id'       => 'grid_columns',
                    'tipo'     => 'slider',
                    'etiqueta' => 'Columnas (Grid)',
                    'min'      => 1,
                    'max'      => 12,
                    'condicion' => ['display_mode', '==', 'grid'],
                ],
                [
                    'id'       => 'grid_min_width',
                    'tipo'     => 'dimension',
                    'etiqueta' => 'Ancho Mínimo (Grid)',
                    'default'  => '250px',
                    'condicion' => ['display_mode', '==', 'grid'],
                ],
                // --- Image Options ---
                [
                    'id'       => 'img_show',
                    'tipo'     => 'toggle',
                    'etiqueta' => 'Mostrar Imagen',
                    'default'  => true,
                ],
                [
                    'id'       => 'img_size',
                    'tipo'     => 'select',
                    'etiqueta' => 'Tamaño de Imagen',
                    'opciones' => [
                        ['valor' => 'thumbnail', 'etiqueta' => 'Miniatura'],
                        ['valor' => 'medium', 'etiqueta' => 'Medio'],
                        ['valor' => 'large', 'etiqueta' => 'Grande'],
                        ['valor' => 'full', 'etiqueta' => 'Completo'],
                    ],
                    'condicion' => ['img_show', '==', true],
                ],
                [
                    'id'       => 'img_aspect_ratio',
                    'tipo'     => 'text',
                    'etiqueta' => 'Aspect Ratio',
                    'descripcion' => 'Ej: 16/9, 4/3, 1/1',
                    'condicion' => ['img_show', '==', true],
                ],
                [
                    'id'       => 'img_object_fit',
                    'tipo'     => 'select',
                    'etiqueta' => 'Ajuste de Imagen',
                    'opciones' => [
                        ['valor' => 'cover', 'etiqueta' => 'Cover'],
                        ['valor' => 'contain', 'etiqueta' => 'Contain'],
                    ],
                    'condicion' => ['img_show', '==', true],
                ],
                // --- Typography Options ---
                [
                    'id'       => 'title_show',
                    'tipo'     => 'toggle',
                    'etiqueta' => 'Mostrar Título',
                    'default'  => true,
                ],
                [
                    'id'       => 'title_font_size',
                    'tipo'     => 'dimension',
                    'etiqueta' => 'Tamaño Fuente Título',
                    'condicion' => ['title_show', '==', true],
                ],
                [
                    'id'       => 'title_color',
                    'tipo'     => 'color',
                    'etiqueta' => 'Color Título',
                    'condicion' => ['title_show', '==', true],
                ],
                [
                    'id'       => 'title_position',
                    'tipo'     => 'select',
                    'etiqueta' => 'Posición Título',
                    'opciones' => [
                        ['valor' => 'top', 'etiqueta' => 'Arriba'],
                        ['valor' => 'bottom', 'etiqueta' => 'Abajo'],
                    ],
                    'condicion' => ['title_show', '==', true],
                ],
                [
                    'id'       => 'title_show_on_hover',
                    'tipo'     => 'toggle',
                    'etiqueta' => 'Mostrar al pasar el mouse',
                    'condicion' => ['title_show', '==', true],
                ],
                // --- Interaction Options ---
                [
                    'id'       => 'interaccion_modo',
                    'tipo'     => 'select',
                    'etiqueta' => 'Modo Interacción',
                    'opciones' => [
                        ['valor' => 'normal', 'etiqueta' => 'Normal'],
                        ['valor' => 'carousel', 'etiqueta' => 'Carrusel'],
                        ['valor' => 'toggle', 'etiqueta' => 'Acordeón (Toggle)'],
                    ],
                    'default'  => 'normal',
                ],
                [
                    'id'       => 'claseContenedor',
                    'tipo'     => 'text',
                    'etiqueta' => 'Clase del contenedor',
                ],
                [
                    'id'       => 'claseItem',
                    'tipo'     => 'text',
                    'etiqueta' => 'Clase de cada item',
                ],
                [
                    'id'       => 'forzarSinCache',
                    'tipo'     => 'toggle',
                    'etiqueta' => 'Ignorar caché',
                ],
            ],
        ];
    }

    /**
     * Helper to get template options for the select field.
     */
    private static function getTemplateOptions(): array
    {
        $options = [];
        if (class_exists(TemplateRegistry::class)) {
            $templates = TemplateRegistry::options();
            foreach ($templates as $id => $label) {
                $options[] = ['valor' => $id, 'etiqueta' => $label . ' (' . $id . ')'];
            }
        }
        return $options;
    }

    /**
     * Inicializa los hooks para limpieza de caché.
     */
    public static function initHooks(): void
    {
        if (self::$hooksRegistered) {
            return;
        }

        add_action('save_post', [self::class, 'clearCacheOnPostChange'], 10, 1);
        add_action('delete_post', [self::class, 'clearCacheOnPostChange'], 10, 1);
        add_action('wp_trash_post', [self::class, 'clearCacheOnPostChange'], 10, 1);
        add_action('untrash_post', [self::class, 'clearCacheOnPostChange'], 10, 1);

        self::$hooksRegistered = true;
    }

    /**
     * Limpia la caché de ContentRender cuando se modifica un post.
     *
     * @param int $postId ID del post modificado.
     */
    public static function clearCacheOnPostChange(int $postId): void
    {
        if (wp_is_post_revision($postId)) {
            return;
        }

        $post = get_post($postId);
        if (!$post) {
            return;
        }

        $postType = $post->post_type;

        // Limpiar todas las caches relacionadas con este tipo de post
        self::clearCacheForPostType($postType);

        // También limpiar caches generales que podrían contener este post
        self::clearAllContentCaches();
    }

    /**
     * Limpia todas las caches de ContentRender para un tipo de post específico.
     *
     * @param string $postType El tipo de post.
     */
    public static function clearCacheForPostType(string $postType): void
    {
        global $wpdb;

        $prefix        = '_transient_glory_content_';
        $timeoutPrefix = '_transient_timeout_glory_content_';

        // Escapar el prefijo para evitar SQL injection
        $escapedPrefix        = $wpdb->esc_like($prefix . '%');
        $escapedTimeoutPrefix = $wpdb->esc_like($timeoutPrefix . '%');

        // Eliminar transients relacionados con ContentRender
        $sql = $wpdb->prepare(
            "DELETE FROM {$wpdb->options}
             WHERE (option_name LIKE %s OR option_name LIKE %s)
             AND option_name LIKE %s",
            $escapedPrefix,
            $escapedTimeoutPrefix,
            '%' . $wpdb->esc_like($postType) . '%'
        );

        $wpdb->query($sql);
    }

    /**
     * Limpia todas las caches de ContentRender.
     */
    public static function clearAllContentCaches(): void
    {
        global $wpdb;

        $prefix        = '_transient_glory_content_';
        $timeoutPrefix = '_transient_timeout_glory_content_';

        // Escapar los prefijos
        $escapedPrefix        = $wpdb->esc_like($prefix . '%');
        $escapedTimeoutPrefix = $wpdb->esc_like($timeoutPrefix . '%');

        // Eliminar todos los transients de ContentRender
        $sql = $wpdb->prepare(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE %s OR option_name LIKE %s",
            $escapedPrefix,
            $escapedTimeoutPrefix
        );

        $wpdb->query($sql);
    }

    /**
     * Imprime una lista de contenidos con opción de caché.
     *
     * @param string $postType El tipo de post a consultar.
     * @param array  $opciones Opciones de configuración.
     */
    public static function print(string $postType, array $opciones = []): void
    {
        $defaults = [
            'publicacionesPorPagina' => 10,
            'claseContenedor'        => 'glory-content-list',
            'claseItem'              => 'glory-content-item',
            'paginacion'             => false,
            'plantillaCallback'      => [self::class, 'defaultTemplate'],
            'argumentosConsulta'     => [],
            'orden'                  => 'fecha',
            'metaKey'                => null,
            'minPaginas'             => 1,
            'tiempoCache'            => HOUR_IN_SECONDS, // Cache por 1 hora por defecto
            'forzarSinCache'         => false, // Permite forzar la no-cache para un llamado específico
            // Opciones UI agnósticas
            'acciones'               => [],     // ej: ['eliminar']
            'submenu'                => false,  // habilita data-submenu-enabled
            'eventoAccion'           => 'dblclick', // click | dblclick | longpress
            'selectorItem'           => '[id^="post-"]', // selector CSS para identificar el item clicado
            // Orden preferido (IDs que deben aparecer primero, en este orden)
            'idsPreferidos'          => [],
        ];
        $config   = wp_parse_args($opciones, $defaults);

        // Resolve template ID to callback if provided
        if (!empty($config['plantilla'])) {
            $callback = null;
            
            // 1. Try TemplateRegistry (registered via code)
            if (class_exists(TemplateRegistry::class)) {
                $callback = TemplateRegistry::get($config['plantilla']);
            }
            
            // 2. Try TemplateManager (scans files)
            if (!$callback && class_exists(\Glory\Manager\TemplateManager::class)) {
                $callback = \Glory\Manager\TemplateManager::getTemplateCallback($config['plantilla']);
            }

            // 3. Fallback: check if the ID itself is a callable function (e.g. 'plantillaPosts')
            if (!$callback && is_callable($config['plantilla'])) {
                $callback = $config['plantilla'];
            }
            
            if ($callback) {
                $config['plantillaCallback'] = $callback;
            }
        }

        // Usar la constante global de WordPress para el modo desarrollo.
        // Si WP_DEBUG es true, no se usará la caché.
        $isDevMode = (defined('WP_DEBUG') && WP_DEBUG);

        if ($isDevMode || $config['forzarSinCache']) {
            // Si es modo desarrollo o se fuerza, renderiza directamente.
            echo self::renderizarContenido($config['postType'] ?? $postType, $config);
            return;
        }

        // 1. Crear una clave única para esta consulta específica.
        // Incluir la página actual para evitar reutilizar HTML entre páginas distintas.
        // Se ignoran los callbacks para la generación de la clave.
        $pagedForCache         = isset($config['argumentosConsulta']['paged']) ? (int) $config['argumentosConsulta']['paged'] : (get_query_var('paged') ? (int) get_query_var('paged') : 1);
        $opcionesParaCache     = $config;
        unset($opcionesParaCache['plantillaCallback']);
        $opcionesParaCache['__paged'] = $pagedForCache;
        $effectivePostType     = $config['postType'] ?? $postType;
        $cacheKey              = 'glory_content_' . md5($effectivePostType . serialize($opcionesParaCache));

        // 2. Intentar obtener el contenido desde la caché.
        $cachedHtml = get_transient($cacheKey);

        if ($cachedHtml !== false) {
            // 3. Si se encuentra en caché, imprimirlo y terminar.
            echo $cachedHtml;
            return;
        }

        // 4. Si no está en caché, generarlo.
        $htmlGenerado = self::renderizarContenido($effectivePostType, $config);

        // 5. Guardar el HTML generado en la caché.
        set_transient($cacheKey, $htmlGenerado, $config['tiempoCache']);

        // 6. Imprimir el HTML.
        echo $htmlGenerado;
    }

    /**
     * Lógica de renderizado separada para poder cachear su salida.
     *
     * @param string $postType El tipo de post.
     * @param array  $config   La configuración completa.
     * @return string El HTML renderizado.
     */
    private static function renderizarContenido(string $postType, array $config): string
    {
        ob_start(); // Iniciar el buffer de salida para capturar todo el HTML.

        $paged = isset($config['argumentosConsulta']['paged']) ? (int) $config['argumentosConsulta']['paged'] : (get_query_var('paged') ? (int) get_query_var('paged') : 1);

        $args = [
            'post_type'           => $postType,
            'posts_per_page'      => $config['publicacionesPorPagina'],
            'paged'               => $paged,
            'ignore_sticky_posts' => true,
        ];

        if (!empty($config['metaKey'])) {
            $args['meta_key'] = $config['metaKey'];
            $args['orderby']  = 'meta_value';
            $args['order']    = (strtoupper($config['orden']) === 'DESC') ? 'DESC' : 'ASC';
        } else {
            $args['orderby'] = ($config['orden'] === 'random') ? 'rand' : 'date';
        }

        // Orden aleatorio con semilla estable (preferir seed explícita o HTTP_REFERER, fallback REQUEST_URI)
        $orderbyFilter = null;
        if (empty($config['metaKey']) && ($config['orden'] === 'random')) {
            if (!empty($config['argumentosConsulta']['glory_rand_seed'])) {
                $seed = (int) $config['argumentosConsulta']['glory_rand_seed'];
            } else {
                $seedSource = (string) ($_SERVER['HTTP_REFERER'] ?? ($_SERVER['REQUEST_URI'] ?? ''));
                $seed       = (int) (crc32($seedSource . '|' . $postType) & 0x7fffffff);
            }
            $args['glory_rand_seed'] = $seed;
            $orderbyFilter           = function ($orderby, $q) use ($seed) {
                if ((string) $q->get('glory_rand_seed') !== '') {
                    if (stripos((string) $orderby, 'rand()') !== false) {
                        $orderby = (string) preg_replace('/RAND\s*\(\s*\)/i', 'RAND(' . $seed . ')', (string) $orderby);
                    } elseif (stripos((string) $orderby, 'rand') !== false) {
                        $orderby = (string) preg_replace('/RAND(?!\s*\()/i', 'RAND(' . $seed . ')', (string) $orderby);
                    }
                }
                return $orderby;
            };
            add_filter('posts_orderby', $orderbyFilter, 10, 2);
        }

        $args = array_merge($args, $config['argumentosConsulta']);
        // Si la consulta trae un post__in sin especificar orderby, respetar el orden explícito.
        if (!empty($args['post__in']) && empty($args['orderby'])) {
            $args['orderby'] = 'post__in';
        }
        // Forzar DISTINCT y deduplicación por ID alrededor de esta consulta
        $gloryFilterPostsDistinct = function ($distinct, $q) {
            return 'DISTINCT';
        };
        $gloryFilterThePosts      = function ($posts, $q) {
            if (!is_array($posts)) {
                return $posts;
            }
            $seen    = [];
            $deduped = [];
            foreach ($posts as $post) {
                $id = is_object($post) && isset($post->ID) ? (int) $post->ID : (int) $post;
                if ($id && !isset($seen[$id])) {
                    $seen[$id] = true;
                    $deduped[] = $post;
                }
            }
            return $deduped;
        };
        add_filter('posts_distinct', $gloryFilterPostsDistinct, 10, 2);
        add_filter('the_posts', $gloryFilterThePosts, 10, 2);

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            echo "<p>No se encontraron contenidos para '{$postType}'.</p>";
        } else {
            if ($config['minPaginas'] > 1 && $query->max_num_pages < (int) $config['minPaginas']) {
                $query->max_num_pages = (int) $config['minPaginas'];
            }

            // Generate instance class for CSS scoping
            $instanceClass = $config['instanceClass'] ?? 'glory-cr-' . substr(md5(uniqid('', true)), 0, 8);
            
            $contenedorClass  = trim($config['claseContenedor'] . ' ' . sanitize_html_class($postType) . ' ' . $instanceClass);
            $itemClass        = trim($config['claseItem'] . ' ' . sanitize_html_class($postType) . '-item' . ' ' . $instanceClass . '__item');
            $isAjaxPagination = $config['paginacion'];

            // Generate CSS
            if (class_exists(ContentRenderCss::class)) {
                $css = ContentRenderCss::build(
                    $instanceClass, 
                    $config, 
                    $config, 
                    $config['interaccion_modo'] ?? 'normal', 
                    false 
                );
                if (!empty($css)) {
                    echo '<style>' . $css . '</style>';
                }
            }

            if ( ! isset( $config['categoryFilter'] ) || ! is_array( $config['categoryFilter'] ) ) {
                $config['categoryFilter'] = [
                    'enabled'  => false,
                    'allLabel' => __( 'All', 'glory-ab' ),
                ];
            }
            $categoryFilterConfig  = $config['categoryFilter'];
            $categoryFilterRuntime = self::prepareCategoryFilterRuntime( $postType, $categoryFilterConfig, $instanceClass, $query->posts );
            if ( $categoryFilterRuntime['enabled'] && '' !== $categoryFilterRuntime['markup'] ) {
                echo $categoryFilterRuntime['markup']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

            if ($isAjaxPagination) {
                $contentTargetClass    = 'glory-content-target';
                $paginationTargetClass = 'glory-pagination-target';
                // Representación segura del callback para atributos data-* (omite closures)
                $callbackStr = null;
                if (is_array($config['plantillaCallback'])) {
                    $parts = [];
                    foreach ($config['plantillaCallback'] as $p) {
                        $parts[] = is_object($p) ? get_class($p) : (string) $p;
                    }
                    $callbackStr = implode('::', $parts);
                } elseif (is_string($config['plantillaCallback'])) {
                    $callbackStr = $config['plantillaCallback'];
                } elseif (is_object($config['plantillaCallback']) && !($config['plantillaCallback'] instanceof \Closure)) {
                    $callbackStr = get_class($config['plantillaCallback']);
                }

                echo '<div class="glory-pagination-container"
                         data-nonce="' . esc_attr(wp_create_nonce('glory_pagination_nonce')) . '"
                         data-post-type="' . esc_attr($postType) . '"
                         data-posts-per-page="' . esc_attr($config['publicacionesPorPagina']) . '"
                         ' . (!empty($callbackStr) ? 'data-template-callback="' . esc_attr($callbackStr) . '"' : '') . '
                         data-container-class="' . esc_attr($config['claseContenedor']) . '"
                         data-item-class="' . esc_attr($config['claseItem']) . '"
                         data-content-target=".' . esc_attr($contentTargetClass) . '"
                         data-pagination-target=".' . esc_attr($paginationTargetClass) . '">';

                echo '<div class="' . esc_attr($contentTargetClass) . '">';
            }

            // Data attributes para JS/UI agnóstico
            $acciones       = is_array($config['acciones']) ? implode(',', array_map('sanitize_key', $config['acciones'])) : sanitize_text_field(strval($config['acciones']));
            $submenuEnabled = !empty($config['submenu']);
            $eventoAccion   = sanitize_key($config['eventoAccion']);
            // Representación segura del callback (omite closures)
            $callbackStr = null;
            if (is_array($config['plantillaCallback'])) {
                $parts = [];
                foreach ($config['plantillaCallback'] as $p) {
                    $parts[] = is_object($p) ? get_class($p) : (string) $p;
                }
                $callbackStr = implode('::', $parts);
            } elseif (is_string($config['plantillaCallback'])) {
                $callbackStr = $config['plantillaCallback'];
            } elseif (is_object($config['plantillaCallback']) && !($config['plantillaCallback'] instanceof \Closure)) {
                $callbackStr = get_class($config['plantillaCallback']);
            }
            // Exponer config actual a las plantillas
            self::$currentConfig = $config;

            // Reordenar resultados priorizando idsPreferidos sin excluir nuevos posts
            $preferredIds = array_values(array_filter(array_map('absint', (array) ($config['idsPreferidos'] ?? []))));
            if (!empty($preferredIds) && is_array($query->posts) && !empty($query->posts)) {
                $byId = [];
                foreach ($query->posts as $p) {
                    if (is_object($p) && isset($p->ID)) {
                        $byId[(int) $p->ID] = $p;
                    }
                }
                $ordered = [];
                $added   = [];
                foreach ($preferredIds as $pid) {
                    if (isset($byId[$pid])) {
                        $ordered[]   = $byId[$pid];
                        $added[$pid] = true;
                    }
                }
                foreach ($query->posts as $p) {
                    $pid = (is_object($p) && isset($p->ID)) ? (int) $p->ID : 0;
                    if ($pid && empty($added[$pid])) {
                        $ordered[]   = $p;
                        $added[$pid] = true;
                    }
                }
                if (!empty($ordered)) {
                    $query->posts      = $ordered;
                    $query->post_count = count($ordered);
                    $query->rewind_posts();
                }
            }

            $gbnAttrs = '';
            if (class_exists(GloryFeatures::class) && GloryFeatures::isActive('gbn', 'glory_gbn_activado') !== false) {
                $gbnRole    = self::gbnDefaults();
                // Merge defaults with current config to ensure all keys exist
                $finalConfig = wp_parse_args($config, $gbnRole['config'] ?? []);
                
                // Ensure post_type is set in finalConfig so frontend picks it up
                if (empty($finalConfig['post_type'])) {
                    $finalConfig['post_type'] = $effectivePostType;
                }
                // Maintain backward compatibility for config key 'postType' if needed by PHP logic
                if (empty($finalConfig['postType'])) {
                    $finalConfig['postType'] = $effectivePostType;
                }

                $configAttr = esc_attr(wp_json_encode($finalConfig));
                $schemaAttr = esc_attr(wp_json_encode($gbnRole['schema'] ?? []));
                $gbnAttrs   = ' data-gbn-content="1" data-gbn-role="content"'
                    . ' data-gbn-config="' . $configAttr . '"'
                    . ' data-gbn-schema="' . $schemaAttr . '"';
            }

            $layoutPatternAttrs = '';
            if (!empty($config['layoutPattern']) && is_array($config['layoutPattern'])) {
                $patternLg = (string) ($config['layoutPattern']['large'] ?? 'none');
                $patternMd = (string) ($config['layoutPattern']['medium'] ?? $patternLg);
                $patternSm = (string) ($config['layoutPattern']['small'] ?? $patternMd);
                $layoutPatternAttrs .= ' data-layout-pattern-lg="' . esc_attr($patternLg) . '"';
                $layoutPatternAttrs .= ' data-layout-pattern-md="' . esc_attr($patternMd) . '"';
                $layoutPatternAttrs .= ' data-layout-pattern-sm="' . esc_attr($patternSm) . '"';
            }

            echo '<div class="' . esc_attr($contenedorClass) . '"'
                . $gbnAttrs
                . ' data-post-type="' . esc_attr($postType) . '"'
                . (!empty($acciones) ? ' data-content-actions="' . esc_attr($acciones) . '"' : '')
                . ' data-submenu-enabled="' . ($submenuEnabled ? '1' : '0') . '"'
                . ' data-accion-evento="' . esc_attr($eventoAccion) . '"'
                . ' data-item-selector="' . esc_attr($config['selectorItem']) . '"'
                . ' data-publicaciones-por-pagina="' . esc_attr($config['publicacionesPorPagina']) . '"'
                . ' data-clase-contenedor="' . esc_attr($config['claseContenedor']) . '"'
                . ' data-clase-item="' . esc_attr($config['claseItem']) . '"'
                . ' data-img-optimize="' . (!empty($config['imgOptimize']) ? '1' : '0') . '"'
                . ' data-img-quality="' . esc_attr((string) ($config['imgQuality'] ?? '')) . '"'
                . (!empty($callbackStr) ? ' data-template-callback="' . esc_attr($callbackStr) . '"' : '')
                . $layoutPatternAttrs
                . '>';
            $indiceGlobal = 0;
            while ($query->have_posts()) {
                $query->the_post();
                $indiceGlobal++;
                $clasesExtras     = ' post-id-' . get_the_ID();
                $clasesExtras    .= ' posicion-' . $indiceGlobal;
                $clasesExtras    .= ($indiceGlobal % 2 === 0) ? ' par' : ' impar';
                $currentItemClass = trim($itemClass . $clasesExtras);
                self::setCurrentOption('indiceItem', $indiceGlobal);
                if ( $categoryFilterRuntime['enabled'] ) {
                    $currentCategories = $categoryFilterRuntime['map'][ get_the_ID() ] ?? [];
                    self::setCurrentOption( 'currentCategories', $currentCategories );
                } else {
                    self::setCurrentOption( 'currentCategories', null );
                }
                call_user_func($config['plantillaCallback'], get_post(), $currentItemClass);
            }
            self::setCurrentOption('indiceItem', null);
            if ( $categoryFilterRuntime['enabled'] ) {
                self::setCurrentOption( 'currentCategories', null );
            }
            echo '</div>';

            if ($isAjaxPagination) {
                echo '</div>';
                echo '<div class="' . esc_attr($paginationTargetClass) . '">';
                PaginationRenderer::render($query);
                echo '</div>';
                echo '</div>';
            } elseif ($config['paginacion']) {
                PaginationRenderer::render($query);
            }
        }

        // Limpiar filtros locales
        remove_filter('posts_distinct', $gloryFilterPostsDistinct, 10);
        remove_filter('the_posts', $gloryFilterThePosts, 10);

        // Limpiar filtro de orden por RAND con semilla, si fue aplicado.
        if (null !== $orderbyFilter) {
            remove_filter('posts_orderby', $orderbyFilter, 10);
        }

        wp_reset_postdata();

        $out = ob_get_clean();
        // Limpiar config expuesta
        self::$currentConfig = [];
        return $out; // Devolver el contenido del buffer y limpiarlo.
    }

    /**
     * Plantilla por defecto para un item.
     *
     * @param \WP_Post $post      El objeto del post.
     * @param string   $itemClass Las clases CSS para el contenedor.
     */
    public static function defaultTemplate(\WP_Post $post, string $itemClass): void
    {
        ?>
        <div id="post-<?php echo $post->ID; ?>" class="<?php echo esc_attr($itemClass); ?>">
            <a class="glory-cr__link" href="<?php echo esc_url(get_permalink($post)); ?>">
                <div class="glory-cr__stack">
                    <h2 class="glory-cr__title"
                        <?php if (isset(self::$currentConfig['title_show']) && !self::$currentConfig['title_show']) echo ' style="display:none;"'; ?>
                    ><?php echo esc_html(get_the_title($post)); ?></h2>
                    <?php
                    $showImg = isset(self::$currentConfig['img_show']) ? (bool) self::$currentConfig['img_show'] : true;
                    if ($showImg && has_post_thumbnail($post)) :
                        $optimize = (bool) (self::$currentConfig['imgOptimize'] ?? true);
                        $quality  = (int) (self::$currentConfig['imgQuality'] ?? 60);
                        $size     = (string) (self::$currentConfig['imgSize'] ?? 'medium');
                        if ($optimize) {
                            $imgHtml = ImageUtility::optimizar($post, $size, $quality);
                            if (is_string($imgHtml) && $imgHtml !== '') {
                                // Inyectar clase para que el CSS por instancia aplique
                                $imgHtml = preg_replace('/^<img\s+/i', '<img class="glory-cr__image" ', $imgHtml);
                                echo $imgHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }
                        } else {
                            ?>
                            <img class="glory-cr__image" src="<?php echo esc_url(get_the_post_thumbnail_url($post, $size)); ?>" alt="<?php echo esc_attr(get_the_title($post)); ?>">
                            <?php
                        }
                    endif;
                    ?>
                </div>
            </a>
            <div class="entry-content"><?php the_excerpt(); ?></div>
        </div>
        <?php
    }

    /**
     * Devuelve la opción actual del render (para usar en plantillas externas).
     *
     * @param string $key     Clave de la opción.
     * @param mixed  $default Valor por defecto si no existe.
     * @return mixed Valor de la opción.
     */
    public static function getCurrentOption(string $key, $default = null)
    {
        return array_key_exists($key, self::$currentConfig) ? self::$currentConfig[$key] : $default;
    }

    /**
     * Establece una opción de configuración actual.
     *
     * @param string $key   Clave de la opción.
     * @param mixed  $value Valor. Si es null, se elimina la clave.
     */
    public static function setCurrentOption(string $key, $value): void
    {
        if ($value === null) {
            unset(self::$currentConfig[$key]);
            return;
        }

        self::$currentConfig[$key] = $value;
    }

    /**
     * Plantilla mínima que imprime el contenido completo del post.
     * No imprime el título ni la imagen para usos tipo "detalle".
     *
     * @param \WP_Post $post      El objeto del post.
     * @param string   $itemClass Clases CSS.
     */
    public static function fullContentTemplate(\WP_Post $post, string $itemClass): void
    {
        ?>
        <div id="post-<?php echo $post->ID; ?>" class="<?php echo esc_attr($itemClass); ?>">
            <div class="entry-content">
                <?php echo apply_filters('the_content', get_post_field('post_content', $post)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>
        <?php
    }

    /**
     * Prepara la data necesaria para el filtro por categorías.
     *
     * @param string   $postType     Tipo de post.
     * @param array    $config       Configuración del filtro.
     * @param string   $instanceClass Clase única de la instancia.
     * @param \WP_Post[] $posts      Lista de posts consultados.
     * @return array{enabled:bool,markup:string,map:array<int,array<int,array{slug:string,label:string}>>}
     */
    private static function prepareCategoryFilterRuntime(string $postType, array $config, string $instanceClass, array $posts): array
    {
        $response = [
            'enabled' => false,
            'markup'  => '',
            'map'     => [],
        ];
        if (empty($config['enabled']) || empty($instanceClass) || empty($posts)) {
            return $response;
        }
        $labels = [];
        $map    = [];
        foreach ($posts as $post) {
            if (!($post instanceof \WP_Post)) {
                continue;
            }
            $detected = self::extractCategoriesForPost($post, $postType);
            if (empty($detected)) {
                continue;
            }
            foreach ($detected as $labelRaw) {
                $label = trim(wp_strip_all_tags((string) $labelRaw));
                if ('' === $label) {
                    continue;
                }
                $slug = sanitize_title($label);
                if ('' === $slug) {
                    continue;
                }
                $labels[$slug] = $label;
                $map[$post->ID][] = [
                    'slug'  => $slug,
                    'label' => $label,
                ];
            }
        }
        if (empty($labels)) {
            return $response;
        }
        $filterId = 'glory-cr-filter-' . wp_generate_password(8, false, false);
        $allLabel = isset($config['allLabel']) && '' !== trim((string) $config['allLabel'])
            ? (string) $config['allLabel']
            : \__('All', 'glory-ab');
        $markup = self::buildCategoryFilterMarkup($filterId, $labels, $allLabel, $instanceClass);
        $response['enabled'] = true;
        $response['markup']  = $markup;
        $response['map']     = $map;
        return $response;
    }

    /**
     * Obtiene categorías asociadas a un post.
     *
     * @param \WP_Post $post     Post actual.
     * @param string   $postType Tipo de post.
     * @return array<int,string>
     */
    private static function extractCategoriesForPost(\WP_Post $post, string $postType): array
    {
        $detected = [];
        $metaCategories = get_post_meta($post->ID, 'category', true);
        if (is_array($metaCategories)) {
            foreach ($metaCategories as $metaCat) {
                $label = trim((string) $metaCat);
                if ('' !== $label) {
                    $detected[] = $label;
                }
            }
        }
        $taxonomy = 'category';
        if (in_array($postType, ['portfolio', 'portafolio'], true) && taxonomy_exists('portfolio_category')) {
            $taxonomy = 'portfolio_category';
        }
        $terms = get_the_terms($post->ID, $taxonomy);
        if (!is_wp_error($terms) && is_array($terms)) {
            foreach ($terms as $term) {
                $label = trim((string) $term->name);
                if ('' !== $label) {
                    $detected[] = $label;
                }
            }
        }
        return $detected;
    }

    /**
     * Construye el HTML del filtro por categorías y su script asociado.
     *
     * @param string $filterId      ID único del filtro.
     * @param array  $labels        Lista de categorías slug => label.
     * @param string $allLabel      Etiqueta para la pestaña "All".
     * @param string $instanceClass Clase única de la instancia.
     * @return string
     */
    private static function buildCategoryFilterMarkup(string $filterId, array $labels, string $allLabel, string $instanceClass): string
    {
        $wrapClasses = trim('glory-cr__filters ' . $instanceClass . '__filters');
        $targetSelector = '.' . $instanceClass . '__item';
        $html  = '<div class="' . esc_attr($wrapClasses) . '" id="' . esc_attr($filterId) . '" data-target="' . esc_attr($targetSelector) . '">';
        $html .= '<button type="button" class="glory-cr__filter is-active" data-filter-value="*" aria-pressed="true">' . esc_html($allLabel) . '</button>';
        foreach ($labels as $slug => $label) {
            $html .= '<button type="button" class="glory-cr__filter" data-filter-value="' . esc_attr($slug) . '" aria-pressed="false">' . esc_html($label) . '</button>';
        }
        $html .= '</div>';
        $html .= self::buildCategoryFilterScript($filterId);
        return $html;
    }

    /**
     * Genera el script del filtro por categorías.
     *
     * @param string $filterId ID del contenedor del filtro.
     * @return string
     */
    private static function buildCategoryFilterScript(string $filterId): string
    {
        $filterIdJson = function_exists('wp_json_encode') ? wp_json_encode($filterId) : json_encode($filterId);
        $hiddenClass  = 'glory-cr__filter-hidden';
        $hiddenClassJson = function_exists('wp_json_encode') ? wp_json_encode($hiddenClass) : json_encode($hiddenClass);
        $js  = '(function(){var root=document.getElementById(' . $filterIdJson . ');if(!root){return;}';
        $js .= 'var target=root.getAttribute("data-target")||"";';
        $js .= 'var items=[];';
        $js .= 'var hiddenClass=' . $hiddenClassJson . ';';
        $js .= 'var lrLeftClass="glory-cr__item--lr-left";var lrRightClass="glory-cr__item--lr-right";';
        $js .= 'var instanceClass="";if(target.charAt(0)==="."){instanceClass=target.slice(1);}';
        $js .= 'if(instanceClass.indexOf("__item")>-1){instanceClass=instanceClass.replace(/__item$/,"");}';
        $js .= 'var container=null;';
        $js .= 'var dynamicClass="glory-cr--lr-dynamic";';
        $js .= 'function getContainer(){if(container&&container.parentNode){return container;}if(!instanceClass){return null;}container=document.querySelector("."+instanceClass)||null;return container;}';
        $js .= 'function ensureDynamicFlag(){var rootContainer=getContainer();if(rootContainer&&!rootContainer.classList.contains(dynamicClass)){rootContainer.classList.add(dynamicClass);}}';
        $js .= 'function collectItems(){ensureDynamicFlag();items=target?Array.prototype.slice.call(document.querySelectorAll(target)):[];}';
        $js .= 'function ensureItems(){if(!items.length){collectItems();}}';
        $js .= 'function patternForViewport(){var rootContainer=getContainer();if(!rootContainer){return"";}var w=window.innerWidth||document.documentElement.clientWidth||0;var lg=rootContainer.getAttribute("data-layout-pattern-lg")||"";var md=rootContainer.getAttribute("data-layout-pattern-md")||"";var sm=rootContainer.getAttribute("data-layout-pattern-sm")||"";if(w>=980){return lg||"";}if(w>=768){return md||lg||"";}return sm||md||lg||"";}';
        $js .= 'function resetLrClasses(){items.forEach(function(item){item.classList.remove(lrLeftClass);item.classList.remove(lrRightClass);});}';
        $js .= 'function reflowAlternado(){ensureItems();if(!items.length){return;}if("alternado_lr"!==patternForViewport()){resetLrClasses();return;}var visibles=[];items.forEach(function(item){if(!item.classList.contains(hiddenClass)){visibles.push(item);}});visibles.forEach(function(item,idx){item.classList.remove(lrLeftClass);item.classList.remove(lrRightClass);if(idx%2===0){item.classList.add(lrLeftClass);}else{item.classList.add(lrRightClass);}});}';
        $js .= 'var currentFilter="*";';
        $js .= 'function applyFilter(value){var slug=value||"*";currentFilter=slug;ensureItems();items.forEach(function(item){var raw=(item.getAttribute("data-glory-categories")||"").trim();var cats=raw?raw.split(/\\s+/):[];var match="*"===slug||cats.indexOf(slug)!==-1;item.classList.toggle(hiddenClass,!match);});reflowAlternado();}';
        $js .= 'applyFilter("*");';
        $js .= 'var raf=window.requestAnimationFrame||function(cb){return setTimeout(cb,16);};';
        $js .= 'raf(function(){collectItems();applyFilter(currentFilter);});';
        $js .= 'document.addEventListener("DOMContentLoaded",function(){collectItems();applyFilter(currentFilter);});';
        $js .= 'root.addEventListener("click",function(evt){var btn=evt.target.closest(".glory-cr__filter");if(!btn){return;}evt.preventDefault();var value=btn.getAttribute("data-filter-value")||"*";root.querySelectorAll(".glory-cr__filter").forEach(function(el){el.classList.remove("is-active");el.setAttribute("aria-pressed","false");});btn.classList.add("is-active");btn.setAttribute("aria-pressed","true");applyFilter(value);});';
        $js .= 'var resizeTimer=null;window.addEventListener("resize",function(){if(!getContainer()){return;}if(resizeTimer){clearTimeout(resizeTimer);}resizeTimer=setTimeout(function(){collectItems();reflowAlternado();},150);});';
        $js .= '})();';
        return '<script id="' . esc_attr($filterId) . '-script">' . $js . '</script>';
    }
}
