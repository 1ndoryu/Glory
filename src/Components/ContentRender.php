<?php

namespace Glory\Components;

use WP_Query;
use Glory\Components\PaginationRenderer;

class ContentRender
{
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
            'grupoEncabezado'        => false,
            'grupoOrdenCantidad'     => false,
            'minPaginas'             => 1,
        ];
        $config = wp_parse_args($opciones, $defaults);

        // Se prioriza el 'paged' de los argumentos de la consulta (para AJAX) o se obtiene de la URL.
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
            $orderby         = ($config['orden'] === 'random') ? 'rand' : 'date';
            $args['orderby'] = $orderby;
        }

        $args = array_merge($args, $config['argumentosConsulta']);
        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            echo "<p>No se encontraron contenidos para '{$postType}'.</p>";
            return;
        }
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
        echo '</div>'; // Fin de $contenedorClass

        if ($is_ajax_pagination) {
            echo '</div>'; // Fin de $content_target_class
            echo '<div class="' . esc_attr($pagination_target_class) . '">';
            PaginationRenderer::render($query);
            echo '</div>'; // Fin de $pagination_target_class
            echo '</div>'; // Fin de .glory-pagination-container
        } elseif ($config['paginacion']) {
            PaginationRenderer::render($query);
        }

        wp_reset_postdata();
    }

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