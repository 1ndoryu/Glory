<?php
namespace Glory\Components;

use WP_Query;

/**
 * Componente para imprimir listas de contenido de cualquier post type.
 *
 * Permite renderizar colecciones de posts con una plantilla personalizada
 * y opciones para paginación, clases CSS y número de resultados.
 */
class ContentPrinter
{
    /**
     * Imprime una lista de posts basada en los parámetros especificados.
     *
     * @param string $postType El slug del tipo de post a consultar.
     * @param array $opciones Un array de opciones para personalizar la salida.
     * - 'publicacionesPorPagina' (int): Número de posts por página. Por defecto 10.
     * - 'claseContenedor' (string): Clase CSS para el contenedor principal.
     * - 'claseItem' (string): Clase CSS para cada elemento individual de la lista.
     * - 'paginacion' (bool): Si se debe mostrar la paginación. Por defecto false.
     * - 'plantillaCallback' (callable): Una función que define el HTML para cada post.
     * - 'argumentosConsulta' (array): Argumentos adicionales para WP_Query.
     */
    public static function print(string $postType, array $opciones = []): void
    {
        // 1. Establecer valores por defecto con claves en español y camelCase
        $defaults = [
            'publicacionesPorPagina' => 10,
            'claseContenedor'        => 'glory-content-list',
            'claseItem'              => 'glory-content-item',
            'paginacion'             => false,
            'plantillaCallback'      => [self::class, 'defaultTemplate'],
            'argumentosConsulta'     => [],
        ];
        $config = wp_parse_args($opciones, $defaults);

        // 2. Preparar la consulta a la base de datos
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array_merge([
            'post_type'      => $postType,
            'posts_per_page' => $config['publicacionesPorPagina'],
            'paged'          => $paged,
        ], $config['argumentosConsulta']);

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            echo "<p>No se encontraron contenidos para '{$postType}'.</p>";
            return;
        }

        // 3. Renderizar la lista
        echo '<div class="' . esc_attr($config['claseContenedor']) . '">';

        while ($query->have_posts()) {
            $query->the_post();
            // Llama a la función de plantilla proporcionada
            call_user_func($config['plantillaCallback'], get_post(), $config['claseItem']);
        }

        echo '</div>';

        // 4. Renderizar la paginación si está habilitada
        if ($config['paginacion']) {
            self::renderPagination($query);
        }

        wp_reset_postdata();
    }

    /**
     * Plantilla por defecto para renderizar un post.
     *
     * @param \WP_Post $post El objeto del post.
     * @param string $itemClass La clase CSS para el contenedor del item.
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

    /**
     * Renderiza la paginación para una consulta de WP_Query.
     *
     * @param WP_Query $query La consulta para la que se generará la paginación.
     */
    private static function renderPagination(WP_Query $query): void
    {
        $big = 999999999;
        $pagination_html = paginate_links([
            'base'    => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format'  => '?paged=%#%',
            'current' => max(1, get_query_var('paged')),
            'total'   => $query->max_num_pages,
        ]);

        if ($pagination_html) {
            echo '<div class="glory-pagination">' . $pagination_html . '</div>';
        }
    }
}