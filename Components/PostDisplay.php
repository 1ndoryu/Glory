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
        'posts_per_page' => 5,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => 'publish',
        'no_found_rows'  => true, // Set to false if pagination might be needed later
    ];

    /** Default display options. */
    private static $defaultOptions = [
        'wrapper_base_class' => 'post-display-wrapper',
        'item_class'         => 'post-item',
        'show_excerpt'       => true,
        'no_posts_message'   => 'No posts found.',
        'template_path'      => '', // Default path is empty, calculated later
    ];

    /**
     * Generates the HTML for displaying posts based on a configuration array.
     *
     * @param string $postType The slug of the post type.
     * @param array $config Optional. A single array containing both WP_Query arguments
     *                      and display options (keys matching $defaultOptions are treated
     *                      as display options, others as query args).
     * @return string The generated HTML string.
     */
    public static function getHtml(string $postType, array $config = []): string
    {
        // ... (Validación y separación de config - sin cambios) ...
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

        GloryLogger::info("PostDisplay::getHtml() - Rendering posts for '{$postType}' using template: {$finalOptions['template_path']}", [
            'input_config' => $config,
            'final_query_args' => $finalQueryArgs,
            'final_options' => $finalOptions,
        ]);

        $query = new WP_Query($finalQueryArgs);

        ob_start();
        ?>
        <div class="<?php echo esc_attr($wrapperBaseClass); ?> <?php echo esc_attr($postTypeClass); ?>">
            <?php if ($query->have_posts()) : ?>
                <?php
                $position = 0; // <-- Inicializa el contador de posición

                while ($query->have_posts()) : $query->the_post();
                    global $post;
                    $position++; // <-- Incrementa la posición para cada post

                    // Prepara los datos para el template, incluyendo la posición
                    $templateData = [
                        'options'   => $finalOptions,
                        'itemClass' => $itemClass,
                        'position'  => $position, // <-- Pasa la posición al template
                    ];

                    // Extrae las variables para que estén disponibles en el scope del include
                    extract($templateData);

                    // Incluye el template del item
                    include $finalOptions['template_path'];

                endwhile;

                wp_reset_postdata();
                ?>
            <?php else : ?>
                <p class="no-posts-found"><?php echo esc_html($finalOptions['no_posts_message']); ?></p>
            <?php endif; ?>
        </div><!-- .<?php echo esc_attr($wrapperBaseClass); ?> -->
        <?php
        return ob_get_clean();
    }

    // ... (método render sin cambios) ...
     public static function render(string $postType, array $config = []): void
    {
        echo self::getHtml($postType, $config);
    }
}