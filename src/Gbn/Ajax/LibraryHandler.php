<?php

namespace Glory\Gbn\Ajax;

class LibraryHandler
{
    public static function createGloryLink(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (empty($title) || empty($url)) {
            wp_send_json_error(['message' => 'Título y URL son requeridos']);
        }
        if (!current_user_can('publish_posts')) {
            wp_send_json_error(['message' => 'Sin permisos para crear posts']);
        }
        $postData = [
            'post_title' => $title,
            'post_type' => 'glory_link',
            'post_status' => 'publish',
            'meta_input' => [
                '_glory_url' => $url,
            ],
        ];
        $postId = wp_insert_post($postData);
        if (is_wp_error($postId)) {
            wp_send_json_error(['message' => 'Error al crear el link: ' . $postId->get_error_message()]);
        }
        wp_send_json_success([
            'post_id' => $postId,
            'message' => 'Link creado exitosamente'
        ]);
    }

    public static function updateGloryLink(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $postId = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (!$postId || empty($title) || empty($url)) {
            wp_send_json_error(['message' => 'ID de post, título y URL son requeridos']);
        }
        if (!current_user_can('edit_post', $postId)) {
            wp_send_json_error(['message' => 'Sin permisos para editar este post']);
        }
        $post = get_post($postId);
        if (!$post || $post->post_type !== 'glory_link') {
            wp_send_json_error(['message' => 'Post no encontrado o no es un link']);
        }
        $postData = [
            'ID' => $postId,
            'post_title' => $title,
        ];
        $updated = wp_update_post($postData);
        if (is_wp_error($updated)) {
            wp_send_json_error(['message' => 'Error al actualizar el título: ' . $updated->get_error_message()]);
        }
        update_post_meta($postId, '_glory_url', $url);
        wp_send_json_success([
            'post_id' => $postId,
            'message' => 'Link actualizado exitosamente'
        ]);
    }

    public static function createGloryHeader(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $paddingTop = isset($_POST['padding_top']) ? sanitize_text_field($_POST['padding_top']) : '';
        $paddingBottom = isset($_POST['padding_bottom']) ? sanitize_text_field($_POST['padding_bottom']) : '';
        if (empty($title)) {
            wp_send_json_error(['message' => 'El título es requerido']);
        }
        if (!current_user_can('publish_posts')) {
            wp_send_json_error(['message' => 'Sin permisos para crear posts']);
        }
        $postData = [
            'post_title' => $title,
            'post_type' => 'glory_header',
            'post_status' => 'publish',
            'meta_input' => [
                '_glory_header_padding_top' => $paddingTop,
                '_glory_header_padding_bottom' => $paddingBottom,
            ],
        ];
        $postId = wp_insert_post($postData);
        if (is_wp_error($postId)) {
            wp_send_json_error(['message' => 'Error al crear el header: ' . $postId->get_error_message()]);
        }
        wp_send_json_success([
            'post_id' => $postId,
            'message' => 'Header creado exitosamente'
        ]);
    }

    public static function updateGloryHeader(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $postId = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $paddingTop = isset($_POST['padding_top']) ? sanitize_text_field($_POST['padding_top']) : '';
        $paddingBottom = isset($_POST['padding_bottom']) ? sanitize_text_field($_POST['padding_bottom']) : '';
        if (!$postId || empty($title)) {
            wp_send_json_error(['message' => 'ID de post y título son requeridos']);
        }
        if (!current_user_can('edit_post', $postId)) {
            wp_send_json_error(['message' => 'Sin permisos para editar este post']);
        }
        $post = get_post($postId);
        if (!$post || $post->post_type !== 'glory_header') {
            wp_send_json_error(['message' => 'Post no encontrado o no es un header']);
        }
        $postData = [
            'ID' => $postId,
            'post_title' => $title,
        ];
        $updated = wp_update_post($postData);
        if (is_wp_error($updated)) {
            wp_send_json_error(['message' => 'Error al actualizar el título: ' . $updated->get_error_message()]);
        }
        update_post_meta($postId, '_glory_header_padding_top', $paddingTop);
        update_post_meta($postId, '_glory_header_padding_bottom', $paddingBottom);
        wp_send_json_success([
            'post_id' => $postId,
            'message' => 'Header actualizado exitosamente'
        ]);
    }
}


