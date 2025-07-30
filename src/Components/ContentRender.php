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
                $callback_str = is_array($config['plantillaCallback']) ? implode('::', $config['plantillaCallback']) : $config['plantillaCallback'];

                echo '<div class="glory-pagination-container"
                         data-nonce="' . esc_attr(wp_create_nonce('glory_pagination_nonce')) . '"
                         data-post-type="' . esc_attr($postType) . '"
                         data-posts-per-page="' . esc_attr($config['publicacionesPorPagina']) . '"
                         data-template-callback="' . esc_attr($callback_str) . '"
                         data-container-class="' . esc_attr($config['claseContenedor']) . '"
                         data-item-class="' . esc_attr($config['claseItem']) . '"
                         data-content-target=".' . esc_attr($content_target_class) . '"
                         data-pagination-target=".' . esc_attr($pagination_target_class) . '">';

                echo '<div class="' . esc_attr($content_target_class) . '">';
            }
            
            echo '<div class="' . esc_attr($contenedorClass) . '">';
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