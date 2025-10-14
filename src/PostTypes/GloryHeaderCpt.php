<?php

namespace Glory\PostTypes;

use Glory\Core\GloryFeatures;

class GloryHeaderCpt
{
    public static function register(): void
    {
        add_action('init', [self::class, 'registerPostType']);
        add_action('add_meta_boxes', [self::class, 'registerMetabox']);
        add_action('save_post_glory_header', [self::class, 'saveMeta'], 10, 2);
        add_filter('manage_glory_header_posts_columns', [self::class, 'columns']);
        add_action('manage_glory_header_posts_custom_column', [self::class, 'renderColumn'], 10, 2);
    }

    public static function registerPostType(): void
    {
        $labels = [
            'name'               => 'Headers',
            'singular_name'      => 'Header',
            'menu_name'          => 'Headers',
            'name_admin_bar'     => 'Header',
            'add_new'            => 'Añadir nuevo',
            'add_new_item'       => 'Añadir nuevo Header',
            'new_item'           => 'Nuevo Header',
            'edit_item'          => 'Editar Header',
            'view_item'          => 'Ver Header',
            'all_items'          => 'Todos los Headers',
            'search_items'       => 'Buscar Headers',
            'not_found'          => 'No se encontraron Headers',
            'not_found_in_trash' => 'No hay Headers en la papelera',
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => false, // No página individual
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 21,
            'menu_icon'          => 'dashicons-editor-bold',
            'supports'           => [ 'title', 'custom-fields', 'page-attributes' ], // menu_order
            'show_in_rest'       => false,
        ];

        register_post_type('glory_header', $args);
    }

    public static function registerMetabox(): void
    {
        add_meta_box('glory_header_settings', 'Configuración del Header', [self::class, 'metaboxHtml'], 'glory_header', 'normal', 'default');
    }

    public static function metaboxHtml(\WP_Post $post): void
    {
        $padding_top = (string) get_post_meta($post->ID, '_glory_header_padding_top', true);
        $padding_bottom = (string) get_post_meta($post->ID, '_glory_header_padding_bottom', true);

        wp_nonce_field('glory_header_save', 'glory_header_nonce');

        echo '<div style="margin-bottom: 10px;">';
        echo '<label for="glory_header_padding_top_field">Padding Top:</label>';
        echo '<input type="text" id="glory_header_padding_top_field" name="glory_header_padding_top_field" value="' . esc_attr($padding_top) . '" placeholder="0px" style="width:100px" />';
        echo '</div>';

        echo '<div style="margin-bottom: 10px;">';
        echo '<label for="glory_header_padding_bottom_field">Padding Bottom:</label>';
        echo '<input type="text" id="glory_header_padding_bottom_field" name="glory_header_padding_bottom_field" value="' . esc_attr($padding_bottom) . '" placeholder="0px" style="width:100px" />';
        echo '</div>';
    }

    public static function saveMeta(int $postId, \WP_Post $post): void
    {
        if (!isset($_POST['glory_header_nonce']) || !wp_verify_nonce($_POST['glory_header_nonce'], 'glory_header_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $padding_top = isset($_POST['glory_header_padding_top_field']) ? sanitize_text_field($_POST['glory_header_padding_top_field']) : '';
        $padding_bottom = isset($_POST['glory_header_padding_bottom_field']) ? sanitize_text_field($_POST['glory_header_padding_bottom_field']) : '';

        update_post_meta($postId, '_glory_header_padding_top', $padding_top);
        update_post_meta($postId, '_glory_header_padding_bottom', $padding_bottom);
    }

    public static function columns(array $cols): array
    {
        $cols['glory_padding'] = 'Padding';
        return $cols;
    }

    public static function renderColumn(string $col, int $postId): void
    {
        if ($col === 'glory_padding') {
            $top = (string) get_post_meta($postId, '_glory_header_padding_top', true);
            $bottom = (string) get_post_meta($postId, '_glory_header_padding_bottom', true);
            $text = '';
            if ($top) $text .= 'T:' . $top;
            if ($bottom) $text .= ($text ? ' ' : '') . 'B:' . $bottom;
            echo $text ?: '<em>—</em>';
        }
    }
}

// Registro global (tema controla la visibilidad vía supports/consultas)
\add_action('plugins_loaded', function () {
    GloryHeaderCpt::register();
});
