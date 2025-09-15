<?php

namespace Glory\Components;

use WP_Query;
use Glory\Components\PaginationRenderer;

class ContentRender
{
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
        // Se ignoran los callbacks para la generación de la clave.
        $opcionesParaCache = $config;
        unset($opcionesParaCache['plantillaCallback']);
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

        $paged = $config['argumentosConsulta']['paged'] ?? (get_query_var('paged') ? get_query_var('paged') : 1);

        $args = [
            'post_type'      => $postType,
            'posts_per_page' => $config['publicacionesPorPagina'],
            'paged'          => $paged,
        ];

        if (!empty($config['metaKey'])) {
            $args['meta_key'] = $config['metaKey'];
            $args['orderby']  = 'meta_value';
            $args['order']    = (strtoupper($config['orden']) === 'DESC') ? 'DESC' : 'ASC';
        } else {
            $args['orderby'] = ($config['orden'] === 'random') ? 'rand' : 'date';
        }

        $args = array_merge($args, $config['argumentosConsulta']);
        // Si la consulta trae un post__in sin especificar orderby, respetar el orden explícito.
        if (!empty($args['post__in']) && empty($args['orderby'])) {
            $args['orderby'] = 'post__in';
        }
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
            echo '<div class="' . esc_attr($contenedorClass) . '"'
                . ' data-post-type="' . esc_attr($postType) . '"'
                . (!empty($acciones) ? ' data-content-actions="' . esc_attr($acciones) . '"' : '')
                . ' data-submenu-enabled="' . ($submenuEnabled ? '1' : '0') . '"'
                . ' data-accion-evento="' . esc_attr($eventoAccion) . '"'
                . ' data-item-selector="' . esc_attr($config['selectorItem']) . '"'
                . ' data-publicaciones-por-pagina="' . esc_attr($config['publicacionesPorPagina']) . '"'
                . ' data-clase-contenedor="' . esc_attr($config['claseContenedor']) . '"'
                . ' data-clase-item="' . esc_attr($config['claseItem']) . '"'
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
                call_user_func($config['plantillaCallback'], get_post(), $currentItemClass);
            }
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
        
        wp_reset_postdata();

        return ob_get_clean(); // Devolver el contenido del buffer y limpiarlo.
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
            <h2><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h2>
            <div class="entry-content">
                <?php the_excerpt(); ?>
            </div>
        </div>
        <?php
    }
}