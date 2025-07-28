<?php

namespace Glory\Components;

use WP_Query;

/**
 * Componente para imprimir listas de contenido de cualquier post type.
 *
 * Permite renderizar colecciones de posts con una plantilla personalizada
 * y opciones para paginación, clases CSS y número de resultados.
 */
class ContentRender
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
     * - 'orden' (string): Orden de los resultados. Acepta 'fecha' (por defecto) o 'random'.
     * - 'metaKey' (string|null): Clave meta para ordenar (string|null).
     * - 'grupoEncabezado' (bool): Mostrar encabezado cuando cambia el valor de la meta.
     * - 'grupoOrdenCantidad' (bool): Ordenar grupos por número de posts desc.
     * - 'minPaginas' (int): Forzar un número mínimo de páginas para la paginación.
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
            'orden'                  => 'fecha',
            'metaKey'                => null,
            'grupoEncabezado'        => false,
            'grupoOrdenCantidad'     => false,
            'minPaginas'             => 1,
        ];
        $config = wp_parse_args($opciones, $defaults);

        // 2. Preparar la consulta a la base de datos
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

        // Construir argumentos de consulta dinámicamente
        $args = [
            'post_type'      => $postType,
            'posts_per_page' => $config['publicacionesPorPagina'],
            'paged'          => $paged,
        ];

        // 2.a Ordenar por meta si se especifica
        if (!empty($config['metaKey'])) {
            $args['meta_key'] = $config['metaKey'];
            $args['orderby']  = 'meta_value';
            $args['order']    = (strtoupper($config['orden']) === 'DESC') ? 'DESC' : 'ASC';
        } else {
            // Mantener el sistema anterior: fecha o random
            $orderby          = ($config['orden'] === 'random') ? 'rand' : 'date';
            $args['orderby']  = $orderby;
        }

        // Combinar con argumentos extra del usuario
        $args = array_merge($args, $config['argumentosConsulta']);

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            echo "<p>No se encontraron contenidos para '{$postType}'.</p>";
            return;
        }
        // Depuración: Forzar un número mínimo de páginas duplicando los resultados.
        if ($config['minPaginas'] > 1) {
            $paginasDeseadas = (int) $config['minPaginas'];
            if ($query->max_num_pages < $paginasDeseadas) {
                // Ajustar propiedades internas de la consulta
                $query->max_num_pages = $paginasDeseadas;
                $query->found_posts   = $paginasDeseadas * $config['publicacionesPorPagina'];

                // Duplicar posts para rellenar la página actual
                $originalPosts = $query->posts;
                if (!empty($originalPosts)) {
                    while (count($query->posts) < $config['publicacionesPorPagina']) {
                        $query->posts = array_merge($query->posts, $originalPosts);
                    }
                    $query->posts = array_slice($query->posts, 0, $config['publicacionesPorPagina']);
                    $query->rewind_posts();
                }
            }
        }

        // 3. Preparar clases dinámicas que incluyan el post type
        $contenedorClass = trim($config['claseContenedor'] . ' ' . sanitize_html_class($postType));
        $itemClass       = trim($config['claseItem'] . ' ' . sanitize_html_class($postType) . '-item');

        // 4. Renderizar la lista con soporte de encabezados por grupo
        echo '<div class="' . esc_attr($contenedorClass) . '">';

        // Si no se requiere orden por cantidad, usamos flujo directo (más eficiente)

        // Contador de posición global para los elementos renderizados
        $indiceGlobal = 0;

        if (empty($config['metaKey']) || !$config['grupoEncabezado'] || !$config['grupoOrdenCantidad']) {

            $currentMetaValue = null;
            $groupOpen        = false;

            while ($query->have_posts()) {
                $query->the_post();

                // Agrupación y encabezados dinámicos por meta
                if (!empty($config['metaKey']) && $config['grupoEncabezado']) {
                    $metaValue = get_post_meta(get_the_ID(), $config['metaKey'], true);

                    // Cuando cambia el valor de la meta, cerramos el contenedor anterior (si existe) y abrimos uno nuevo
                    if ($metaValue !== $currentMetaValue) {
                        if ($groupOpen) {
                            echo '</div>'; // cerrar contenedor anterior
                        }

                        $currentMetaValue = $metaValue;

                        // Cabecera del grupo fuera del contenedor de posts
                        echo '<h3 class="grupoHead">' . esc_html($metaValue) . '</h3>';
                        // Abrir nuevo contenedor de posts del grupo
                        echo '<div class="gloryGrupo ' . esc_attr(sanitize_title($metaValue)) . '">';

                        $groupOpen = true;
                    }
                }

                // Incrementamos el índice y preparamos clases adicionales por ítem
                $indiceGlobal++;

                $clasesExtras  = ' post-id-' . get_the_ID();
                $clasesExtras .= ' posicion-' . $indiceGlobal;
                $clasesExtras .= ($indiceGlobal % 2 === 0) ? ' par' : ' impar';

                $currentItemClass = trim($itemClass . $clasesExtras);

                // Llama a la función de plantilla proporcionada con las clases extendidas
                call_user_func($config['plantillaCallback'], get_post(), $currentItemClass);
            }

            // Cerrar último contenedor de grupo si se abrió alguno
            if ($groupOpen) {
                echo '</div>'; // cierre grupo
            }
        } else {
            /*
             * Modo de agrupación con orden por número de posts (desc).
             * Agrupa todos los posts primero y luego los imprime según tamaño.
             */

            $groups = [];
            foreach ($query->posts as $postObj) {
                $metaValue = get_post_meta($postObj->ID, $config['metaKey'], true);
                if (!isset($groups[$metaValue])) {
                    $groups[$metaValue] = [];
                }
                $groups[$metaValue][] = $postObj;
            }

            // Ordenar grupos por cantidad descendente
            uasort($groups, function ($a, $b) {
                return count($b) <=> count($a);
            });

            // Imprimir grupos
            foreach ($groups as $metaValue => $posts) {
                echo '<h3 class="grupoHead">' . esc_html($metaValue) . '</h3>';
                echo '<div class="gloryGrupo ' . esc_attr(sanitize_title($metaValue)) . '">';

                foreach ($posts as $postObj) {
                    setup_postdata($postObj);

                    // Incrementamos el índice global
                    $indiceGlobal++;

                    $clasesExtras  = ' post-id-' . $postObj->ID;
                    $clasesExtras .= ' posicion-' . $indiceGlobal;
                    $clasesExtras .= ($indiceGlobal % 2 === 0) ? ' par' : ' impar';

                    $currentItemClass = trim($itemClass . $clasesExtras);

                    call_user_func($config['plantillaCallback'], $postObj, $currentItemClass);
                }

                echo '</div>'; // cierre grupo
            }
            wp_reset_postdata();
        }

        echo '</div>'; // cierre contenedor principal

        // 5. Renderizar la paginación si está habilitada
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
            echo '<div class="gloryPaginacion">' . $pagination_html . '</div>';
        }
    }
}
