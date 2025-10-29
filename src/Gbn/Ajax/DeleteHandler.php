<?php

namespace Glory\Gbn\Ajax;

class DeleteHandler
{
    public static function deleteItem(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $itemType = isset($_POST['item_type']) ? sanitize_text_field($_POST['item_type']) : '';
        $postId = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $postUrl = isset($_POST['post_url']) ? esc_url_raw($_POST['post_url']) : '';
        if (!$itemType) {
            wp_send_json_error(['message' => 'Tipo de elemento requerido']);
        }
        if ($itemType === 'link') {
            if (empty($postUrl)) {
                wp_send_json_error(['message' => 'URL del link requerida']);
            }
            $posts = get_posts([
                'post_type' => 'glory_link',
                'meta_key' => '_glory_url',
                'meta_value' => $postUrl,
                'posts_per_page' => 1,
            ]);
            if (empty($posts)) {
                wp_send_json_error(['message' => 'Link no encontrado']);
            }
            $postId = $posts[0]->ID;
        } elseif (in_array($itemType, ['post', 'header'])) {
            if (!$postId) {
                wp_send_json_error(['message' => 'ID del post requerido']);
            }
        } else {
            wp_send_json_error(['message' => 'Tipo de elemento no válido']);
        }
        if (!current_user_can('delete_post', $postId)) {
            wp_send_json_error(['message' => 'Sin permisos para eliminar este elemento']);
        }
        $post = get_post($postId);
        if (!$post) {
            wp_send_json_error(['message' => 'Elemento no encontrado']);
        }
        if ($itemType === 'post' && !in_array($post->post_type, ['post', 'page'])) {
            wp_send_json_error(['message' => 'El elemento no es un post válido']);
        } elseif ($itemType === 'header' && $post->post_type !== 'glory_header') {
            wp_send_json_error(['message' => 'El elemento no es un header válido']);
        } elseif ($itemType === 'link' && $post->post_type !== 'glory_link') {
            wp_send_json_error(['message' => 'El elemento no es un link válido']);
        }
        $result = wp_trash_post($postId);
        if (!$result) {
            wp_send_json_error(['message' => 'Error al mover el elemento a la papelera']);
        }
        wp_send_json_success([
            'post_id' => $postId,
            'message' => 'Elemento movido a la papelera exitosamente'
        ]);
    }
}


