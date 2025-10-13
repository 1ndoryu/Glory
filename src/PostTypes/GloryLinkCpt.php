<?php

namespace Glory\PostTypes;

use Glory\Core\GloryFeatures;

class GloryLinkCpt
{
    public static function register(): void
    {
        add_action('init', [self::class, 'registerPostType']);
        add_action('add_meta_boxes', [self::class, 'registerMetabox']);
        add_action('save_post_glory_link', [self::class, 'saveMeta'], 10, 2);
        add_filter('manage_glory_link_posts_columns', [self::class, 'columns']);
        add_action('manage_glory_link_posts_custom_column', [self::class, 'renderColumn'], 10, 2);
    }

    public static function registerPostType(): void
    {
        $labels = [
            'name'               => 'Links',
            'singular_name'      => 'Link',
            'menu_name'          => 'Links',
            'name_admin_bar'     => 'Link',
            'add_new'            => 'Añadir nuevo',
            'add_new_item'       => 'Añadir nuevo Link',
            'new_item'           => 'Nuevo Link',
            'edit_item'          => 'Editar Link',
            'view_item'          => 'Ver Link',
            'all_items'          => 'Todos los Links',
            'search_items'       => 'Buscar Links',
            'not_found'          => 'No se encontraron Links',
            'not_found_in_trash' => 'No hay Links en la papelera',
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
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-admin-links',
            'supports'           => [ 'title', 'custom-fields', 'page-attributes' ], // menu_order
            'show_in_rest'       => false,
        ];

        register_post_type('glory_link', $args);
    }

    public static function registerMetabox(): void
    {
        add_meta_box('glory_link_url', 'URL del Link', [self::class, 'metaboxHtml'], 'glory_link', 'normal', 'default');
    }

    public static function metaboxHtml(\WP_Post $post): void
    {
        $url = (string) get_post_meta($post->ID, '_glory_url', true);
        wp_nonce_field('glory_link_save', 'glory_link_nonce');
        echo '<label for="glory_link_url_field">URL</label>';
        echo '<input type="url" id="glory_link_url_field" name="glory_link_url_field" value="' . esc_attr($url) . '" style="width:100%" placeholder="https://..." />';
        echo '<p style="margin-top:6px;color:#666">Se abrirá en una nueva pestaña.</p>';
    }

    public static function saveMeta(int $postId, \WP_Post $post): void
    {
        if (!isset($_POST['glory_link_nonce']) || !wp_verify_nonce($_POST['glory_link_nonce'], 'glory_link_save')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $postId)) {
            return;
        }
        $urlRaw = isset($_POST['glory_link_url_field']) ? (string) $_POST['glory_link_url_field'] : '';
        $url = esc_url_raw(trim($urlRaw));
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            delete_post_meta($postId, '_glory_url');
            return;
        }
        update_post_meta($postId, '_glory_url', $url);
    }

    public static function columns(array $cols): array
    {
        $cols['glory_url'] = 'URL';
        return $cols;
    }

    public static function renderColumn(string $col, int $postId): void
    {
        if ($col === 'glory_url') {
            $url = (string) get_post_meta($postId, '_glory_url', true);
            if ($url !== '') {
                echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($url) . '</a>';
            } else {
                echo '<em>—</em>';
            }
        }
    }
}

// Registro global (tema controla la visibilidad vía supports/consultas)
\add_action('plugins_loaded', function () {
    GloryLinkCpt::register();
});


