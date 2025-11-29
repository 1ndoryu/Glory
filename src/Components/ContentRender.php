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
                'plantilla'              => null,
                'argumentosConsulta'     => [],
                'forzarSinCache'         => false,
                'paginacion'             => false,
            ],
            'schema' => [
                [
                    'id'       => 'publicacionesPorPagina',
                    'tipo'     => 'slider',
                    'etiqueta' => 'Entradas por página',
                    'min'      => 1,
                    'max'      => 20,
                    'paso'     => 1,
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
                    'id'       => 'paginacion',
                    'tipo'     => 'toggle',
                    'etiqueta' => 'Activar paginación AJAX',
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

        // Usar la constante global de WordPress para el modo desarrollo.
        // Si WP_DEBUG es true, no se usará la caché.
        $isDevMode = (defined('WP_DEBUG') && WP_DEBUG);

        if ($isDevMode || $config['forzarSinCache']) {
            // Si es modo desarrollo o se fuerza, renderiza directamente.
            echo self::renderizarContenido($postType, $config);
            return;
        }

        // 1. Crear una clave única para esta consulta específica.
        // Incluir la página actual para evitar reutilizar HTML entre páginas distintas.
        // Se ignoran los callbacks para la generación de la clave.
        $pagedForCache         = isset($config['argumentosConsulta']['paged']) ? (int) $config['argumentosConsulta']['paged'] : (get_query_var('paged') ? (int) get_query_var('paged') : 1);
        $opcionesParaCache     = $config;
        unset($opcionesParaCache['plantillaCallback']);
        $opcionesParaCache['__paged'] = $pagedForCache;
        $cacheKey              = 'glory_content_' . md5($postType . serialize($opcionesParaCache));

        // 2. Intentar obtener el contenido desde la caché.
        $cachedHtml = get_transient($cacheKey);

        if ($cachedHtml !== false) {
            // 3. Si se encuentra en caché, imprimirlo y terminar.
            echo $cachedHtml;
            return;
        }

        // 4. Si no está en caché, generarlo.
        $htmlGenerado = self::renderizarContenido($postType, $config);

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

            $contenedorClass  = trim($config['claseContenedor'] . ' ' . sanitize_html_class($postType));
            $itemClass        = trim($config['claseItem'] . ' ' . sanitize_html_class($postType) . '-item');
            $isAjaxPagination = $config['paginacion'];

            $instanceClass = isset( $config['instanceClass'] ) ? (string) $config['instanceClass'] : '';
            $categoryFilterConfig  = is_array( $config['categoryFilter'] ?? [] ) ? $config['categoryFilter'] : [];
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
                $configAttr = esc_attr(wp_json_encode($gbnRole['config'] ?? []));
                $schemaAttr = esc_attr(wp_json_encode($gbnRole['schema'] ?? []));
                $gbnAttrs   = ' data-gbn-content="1" data-gbn-role="content"'
                    . ' data-gbn-config="' . $configAttr . '"'
                    . ' data-gbn-schema="' . $schemaAttr . '"';
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
                    <h2 class="glory-cr__title"><?php echo esc_html(get_the_title($post)); ?></h2>
                    <?php
                    if (has_post_thumbnail($post)) :
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
        $js .= 'var items=target?Array.prototype.slice.call(document.querySelectorAll(target)):[];
';
        $js .= 'function applyFilter(value){var slug="*"===value?"*":value;items.forEach(function(item){var raw=(item.getAttribute("data-glory-categories")||"").trim();var cats=raw?raw.split(/\s+/):[];var match="*"===slug||cats.indexOf(slug)!==-1;item.classList.toggle(' . $hiddenClassJson . ', !match);});}';
        $js .= 'applyFilter("*");';
        $js .= 'root.addEventListener("click",function(evt){var btn=evt.target.closest(".glory-cr__filter");if(!btn){return;}evt.preventDefault();var value=btn.getAttribute("data-filter-value")||"*";root.querySelectorAll(".glory-cr__filter").forEach(function(el){el.classList.remove("is-active");el.setAttribute("aria-pressed","false");});btn.classList.add("is-active");btn.setAttribute("aria-pressed","true");applyFilter(value);});';
        $js .= '})();';
        return '<script id="' . esc_attr($filterId) . '-script">' . $js . '</script>';
    }
}
