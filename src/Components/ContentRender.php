<?php

namespace Glory\Components;

use WP_Query;
use Glory\Components\PaginationRenderer;
use Glory\Utility\ImageUtility;

class ContentRender
{
    /** @var array<string,mixed> */
    private static $currentConfig = [];
    /**
     * Imprime una lista de contenidos con opción de caché.
     *
     * @param string $postType El tipo de post a consultar.
     * @param array $opciones Opciones de configuración.
     */
    public static function print(string $postType, array $opciones = []): void
    {
        $defaults = [
            'publicacionesPorPagina' => 10,
            'claseContenedor'      => 'glory-content-list',
            'claseItem'            => 'glory-content-item',
            'paginacion'           => false,
            'plantillaCallback'    => [self::class, 'defaultTemplate'],
            'argumentosConsulta'   => [],
            'orden'                => 'fecha',
            'metaKey'              => null,
            'minPaginas'           => 1,
            'tiempoCache'          => HOUR_IN_SECONDS, // Cache por 1 hora por defecto
            'forzarSinCache'       => false, // Permite forzar la no-cache para un llamado específico
            // Opciones UI agnósticas
            'acciones'             => [],     // ej: ['eliminar']
            'submenu'              => false,  // habilita data-submenu-enabled
            'eventoAccion'         => 'dblclick', // click | dblclick | longpress
            'selectorItem'         => '[id^="post-"]', // selector CSS para identificar el item clicado
        ];
        $config = wp_parse_args($opciones, $defaults);

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
        $pagedForCache = isset($config['argumentosConsulta']['paged']) ? (int) $config['argumentosConsulta']['paged'] : ( get_query_var('paged') ? (int) get_query_var('paged') : 1 );
        $opcionesParaCache = $config;
        unset($opcionesParaCache['plantillaCallback']);
        $opcionesParaCache['__paged'] = $pagedForCache;
        $cacheKey = 'glory_content_' . md5($postType . serialize($opcionesParaCache));

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
     * @param array $config La configuración completa.
     * @return string El HTML renderizado.
     */
    private static function renderizarContenido(string $postType, array $config): string
    {
        ob_start(); // Iniciar el buffer de salida para capturar todo el HTML.

        $paged = isset($config['argumentosConsulta']['paged']) ? (int) $config['argumentosConsulta']['paged'] : ( get_query_var('paged') ? (int) get_query_var('paged') : 1 );

        $args = [
            'post_type'      => $postType,
            'posts_per_page' => $config['publicacionesPorPagina'],
            'paged'          => $paged,
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
                $seed = (int) (crc32($seedSource . '|' . $postType) & 0x7fffffff);
            }
            $args['glory_rand_seed'] = $seed;
            $orderbyFilter = function ($orderby, $q) use ($seed) {
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
        $gloryFilterThePosts = function ($posts, $q) {
            if (!is_array($posts)) {
                return $posts;
            }
            $seen = [];
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

            $contenedorClass = trim($config['claseContenedor'] . ' ' . sanitize_html_class($postType));
            $itemClass       = trim($config['claseItem'] . ' ' . sanitize_html_class($postType) . '-item');
            $is_ajax_pagination = $config['paginacion'];

            if ($is_ajax_pagination) {
                $content_target_class = 'glory-content-target';
                $pagination_target_class = 'glory-pagination-target';
                // Representación segura del callback para atributos data-* (omite closures)
                $callback_str = null;
                if (is_array($config['plantillaCallback'])) {
                    $parts = [];
                    foreach ($config['plantillaCallback'] as $p) {
                        $parts[] = is_object($p) ? get_class($p) : (string) $p;
                    }
                    $callback_str = implode('::', $parts);
                } elseif (is_string($config['plantillaCallback'])) {
                    $callback_str = $config['plantillaCallback'];
                } elseif (is_object($config['plantillaCallback']) && !($config['plantillaCallback'] instanceof \Closure)) {
                    $callback_str = get_class($config['plantillaCallback']);
                }

                echo '<div class="glory-pagination-container"
                         data-nonce="' . esc_attr(wp_create_nonce('glory_pagination_nonce')) . '"
                         data-post-type="' . esc_attr($postType) . '"
                         data-posts-per-page="' . esc_attr($config['publicacionesPorPagina']) . '"
                         ' . (!empty($callback_str) ? 'data-template-callback="' . esc_attr($callback_str) . '"' : '') . '
                         data-container-class="' . esc_attr($config['claseContenedor']) . '"
                         data-item-class="' . esc_attr($config['claseItem']) . '"
                         data-content-target=".' . esc_attr($content_target_class) . '"
                         data-pagination-target=".' . esc_attr($pagination_target_class) . '">';

                echo '<div class="' . esc_attr($content_target_class) . '">';
            }
            
            // Data attributes para JS/UI agnóstico
            $acciones = is_array($config['acciones']) ? implode(',', array_map('sanitize_key', $config['acciones'])) : sanitize_text_field(strval($config['acciones']));
            $submenuEnabled = !empty($config['submenu']);
            $eventoAccion = sanitize_key($config['eventoAccion']);
            // Representación segura del callback (omite closures)
            $callback_str = null;
            if (is_array($config['plantillaCallback'])) {
                $parts = [];
                foreach ($config['plantillaCallback'] as $p) {
                    $parts[] = is_object($p) ? get_class($p) : (string) $p;
                }
                $callback_str = implode('::', $parts);
            } elseif (is_string($config['plantillaCallback'])) {
                $callback_str = $config['plantillaCallback'];
            } elseif (is_object($config['plantillaCallback']) && !($config['plantillaCallback'] instanceof \Closure)) {
                $callback_str = get_class($config['plantillaCallback']);
            }
            // Exponer config actual a las plantillas
            self::$currentConfig = $config;

            echo '<div class="' . esc_attr($contenedorClass) . '"'
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
                . (!empty($callback_str) ? ' data-template-callback="' . esc_attr($callback_str) . '"' : '')
                . '>';
            $indiceGlobal = 0;
            while ($query->have_posts()) {
                $query->the_post();
                $indiceGlobal++;
                $clasesExtras  = ' post-id-' . get_the_ID();
                $clasesExtras .= ' posicion-' . $indiceGlobal;
                $clasesExtras .= ($indiceGlobal % 2 === 0) ? ' par' : ' impar';
                $currentItemClass = trim($itemClass . $clasesExtras);
                self::setCurrentOption('indiceItem', $indiceGlobal);
                call_user_func($config['plantillaCallback'], get_post(), $currentItemClass);
            }
            self::setCurrentOption('indiceItem', null);
            echo '</div>';

            if ($is_ajax_pagination) {
                echo '</div>'; 
                echo '<div class="' . esc_attr($pagination_target_class) . '">';
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
     * @param \WP_Post $post El objeto del post.
     * @param string $itemClass Las clases CSS para el contenedor.
     */
    public static function defaultTemplate(\WP_Post $post, string $itemClass): void
    {
        ?>
        <div id="post-<?php echo $post->ID; ?>" class="<?php echo esc_attr($itemClass); ?>">
            <a class="glory-cr__link" href="<?php echo esc_url(get_permalink($post)); ?>">
                <div class="glory-cr__stack">
                    <h2 class="glory-cr__title"><?php echo esc_html(get_the_title($post)); ?></h2>
                    <?php
                    if ( has_post_thumbnail($post) ) :
                        $optimize = (bool) (self::$currentConfig['imgOptimize'] ?? true);
                        $quality  = (int)  (self::$currentConfig['imgQuality']  ?? 60);
                        $size = (string) (self::$currentConfig['imgSize'] ?? 'medium');
                        if ( $optimize ) {
                            $imgHtml = ImageUtility::optimizar($post, $size, $quality);
                            if ( is_string($imgHtml) && $imgHtml !== '' ) {
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
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getCurrentOption(string $key, $default = null)
    {
        return array_key_exists($key, self::$currentConfig) ? self::$currentConfig[$key] : $default;
    }

    public static function setCurrentOption(string $key, $value): void
    {
        if ($value === null) {
            unset(self::$currentConfig[$key]);
            return;
        }

        self::$currentConfig[$key] = $value;
    }
}