<?php
# App/Glory/Components/PostDisplay.php
namespace Glory\Components;

use WP_Query;
use WP_Post;
use Glory\Class\GloryLogger;

/**
 * Class PostDisplay
 * Handles fetching and displaying posts using a configuration array and a template file.
 */
class PostDisplay
{
    /** Default WP_Query arguments. */
    private static $defaultQueryArgs = [
        'posts_per_page' => -1, // Fetch all matching posts initially
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => 'publish',
        'no_found_rows'  => true, // Optimized for when WP pagination isn't needed directly
    ];

    /** Default display options. */
    private static $defaultOptions = [
        'wrapper_base_class' => 'post-display-wrapper',
        'item_class'         => 'post-item',
        'show_excerpt'       => true,
        'no_posts_message'   => 'No posts found.',
        'template_path'      => '', // Default path is empty, calculated later

        // --- NUEVAS OPCIONES PARA SECTORES ---
        'sector_enable'      => false,
        'sector_size'        => 4,
        'sector_class'       => 'post-sector',

        // --- NUEVA OPCIÓN PARA POSTS PRIORITARIOS ---
        'priority_ids'       => [], // Array of post IDs to display first, in the given order.
    ];

    /**
     * Generates the HTML for displaying posts based on a configuration array.
     *
     * @param string $postType The slug of the post type.
     * @param array $config Optional. Configuration array.
     *                      - 'priority_ids' (int[]): Array of post IDs to feature at the beginning.
     *                      - ... other options and query args.
     * @return string The generated HTML string.
     */
    public static function getHtml(string $postType, array $config = []): string
    {
        if (empty($postType) || !post_type_exists($postType)) {
            GloryLogger::error("PostDisplay::getHtml() - Invalid or non-existent post type: '{$postType}'.");
            return '';
        }

        $inputQueryArgs = [];
        $inputOptions = [];
        foreach ($config as $key => $value) {
            if (array_key_exists($key, self::$defaultOptions)) {
                $inputOptions[$key] = $value;
            } else {
                $inputQueryArgs[$key] = $value;
            }
        }

        $finalOptions = array_merge(self::$defaultOptions, $inputOptions);
        $finalQueryArgs = array_merge(self::$defaultQueryArgs, $inputQueryArgs);
        $finalQueryArgs['post_type'] = $postType;

        // --- Validación y preparación de priority_ids ---
        $priorityIds = [];
        if (!empty($finalOptions['priority_ids']) && is_array($finalOptions['priority_ids'])) {
            // Asegurarse de que son enteros positivos
            $priorityIds = array_filter(array_map('intval', $finalOptions['priority_ids']), function ($id) {
                return $id > 0;
            });
            $priorityIds = array_values(array_unique($priorityIds)); // Eliminar duplicados y reindexar
        }
        // --- Fin validación priority_ids ---

        // Asegurarse de que sector_size sea un entero positivo si está habilitado
        $finalOptions['sector_size'] = max(1, intval($finalOptions['sector_size']));

        if (empty($finalOptions['template_path'])) {
            $finalOptions['template_path'] = __DIR__ . '/templates/post-display-item.php';
        }
        if (!is_readable($finalOptions['template_path'])) {
            GloryLogger::error("PostDisplay::getHtml() - Template file not found or not readable: {$finalOptions['template_path']}");
            return '<p class="post-display-error">Error: Could not load display template.</p>';
        }

        $postTypeClass = sanitize_html_class('post-type-' . $postType);
        $wrapperBaseClass = sanitize_html_class($finalOptions['wrapper_base_class']);
        $itemClass = sanitize_html_class($finalOptions['item_class']);
        $sectorBaseClass = sanitize_html_class($finalOptions['sector_class']);

        GloryLogger::info("PostDisplay::getHtml() - Rendering posts for '{$postType}' using template: {$finalOptions['template_path']}", [
            'input_config' => $config,
            'final_query_args' => $finalQueryArgs,
            'final_options' => $finalOptions,
            'priority_ids_used' => $priorityIds,
        ]);

        // Ejecutar la consulta principal
        // Nota: Si hay priority_ids, podríamos excluirlos aquí con 'post__not_in',
        // pero es más flexible obtener todos y reordenar después en PHP,
        // especialmente si la lista de posts es grande y los priority_ids podrían cambiar.
        // Por simplicidad y robustez (asegurando que los priority posts realmente existen
        // y cumplen otros criterios si los hubiera), los obtendremos todos primero.
        $query = new WP_Query($finalQueryArgs);

        $orderedPosts = []; // Array para almacenar los posts en el orden final

        if ($query->have_posts()) {
            $allPosts = $query->posts; // Obtener todos los objetos WP_Post de la consulta

            if (!empty($priorityIds)) {
                $priorityPostsFound = [];
                $otherPosts = [];
                $priorityIdsLookup = array_flip($priorityIds); // Para búsqueda rápida O(1)

                // 1. Separar los posts prioritarios de los demás
                foreach ($allPosts as $post) {
                    if (isset($priorityIdsLookup[$post->ID])) {
                        // Guardar los posts prioritarios encontrados, indexados por ID
                        $priorityPostsFound[$post->ID] = $post;
                    } else {
                        // Guardar los posts no prioritarios
                        $otherPosts[] = $post;
                    }
                }

                // 2. Construir la lista ordenada: primero los prioritarios (en el orden de $priorityIds), luego los demás
                foreach ($priorityIds as $pId) {
                    if (isset($priorityPostsFound[$pId])) {
                        $orderedPosts[] = $priorityPostsFound[$pId];
                    }
                    // Si un ID prioritario no se encontró en los resultados de la query (p.ej., no publicado, tipo incorrecto), simplemente se omite.
                }
                $orderedPosts = array_merge($orderedPosts, $otherPosts);

            } else {
                // Si no hay IDs prioritarios, el orden es el de la consulta original
                $orderedPosts = $allPosts;
            }
        }

        // --- Preparación del Bucle de Visualización ---
        ob_start();
        ?>
        <div class="<?php echo esc_attr($wrapperBaseClass); ?> <?php echo esc_attr($postTypeClass); ?>">
            <?php if (!empty($orderedPosts)) : ?>
                <?php
                $position = 0; // Contador de posición general
                $sector_index = 0; // Contador de sectores
                $total_posts = count($orderedPosts); // Total de posts a mostrar

                // Determinar si se usa la lógica de sectores
                $use_sectors = $finalOptions['sector_enable'] === true && $finalOptions['sector_size'] > 0;
                $sector_size = $finalOptions['sector_size'];

                // Iterar sobre la lista de posts REORDENADA
                foreach ($orderedPosts as $post) :
                    setup_postdata($post); // Configurar datos globales del post para el template
                    $position++; // Incrementar posición

                    // --- LÓGICA DE SECTORES ---
                    if ($use_sectors) {
                        if (($position - 1) % $sector_size === 0) {
                            $sector_index++;
                            $current_sector_class = esc_attr($sectorBaseClass . ' ' . $sectorBaseClass . '-' . $sector_index);
                            echo '<div class="' . $current_sector_class . '">';
                        }
                    }
                    // --- FIN LÓGICA DE SECTORES ---

                    // Prepara los datos para el template
                    $templateData = [
                        'options'   => $finalOptions,
                        'itemClass' => $itemClass,
                        'position'  => $position,
                        'post_object' => $post, // Pasar el objeto $post directamente
                    ];

                    // Extrae las variables para que estén disponibles en el scope del include
                    extract($templateData);

                    // Incluye el template del item
                    include $finalOptions['template_path'];

                     // --- LÓGICA DE SECTORES (CIERRE) ---
                    if ($use_sectors) {
                        if ($position % $sector_size === 0 || $position === $total_posts) {
                            echo '</div><!-- .' . esc_attr($sectorBaseClass) . ' -->';
                        }
                    }
                    // --- FIN LÓGICA DE SECTORES (CIERRE) ---

                endforeach; // Fin del bucle foreach sobre $orderedPosts

                wp_reset_postdata(); // Restaurar datos globales del post original
                ?>
            <?php else : ?>
                <p class="no-posts-found"><?php echo esc_html($finalOptions['no_posts_message']); ?></p>
            <?php endif; ?>
        </div><!-- .<?php echo esc_attr($wrapperBaseClass); ?> -->
        <?php
        return ob_get_clean();
    }

    /**
     * Renders the HTML for displaying posts directly.
     *
     * @param string $postType The slug of the post type.
     * @param array $config Optional. Configuration array (see getHtml method).
     */
     public static function render(string $postType, array $config = []): void
    {
        echo self::getHtml($postType, $config);
    }
}