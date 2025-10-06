<?php

namespace Glory\Handler;

use Glory\Services\PostActionManager;
use Glory\Core\GloryLogger;

class ContentActionAjaxHandler
{
    public function __construct()
    {
        add_action('wp_ajax_glory_content_action', [$this, 'handle_request']);
        add_action('wp_ajax_nopriv_glory_content_action', [$this, 'handle_request']);
    }

    public function handle_request(): void
    {
        $accion   = sanitize_key($_POST['accion'] ?? '');
        $postId   = absint($_POST['postId'] ?? 0);
        $postType = sanitize_text_field($_POST['postType'] ?? '');
        $modo     = sanitize_key($_POST['modo'] ?? 'trash'); // 'trash' | 'delete'

        if (!$accion || $postId <= 0) {
            wp_send_json_error(['message' => 'Parámetros inválidos.']);
            return;
        }

        try {
            switch ($accion) {
                case 'eliminar':
                    $ok = ($modo === 'trash')
                        ? PostActionManager::trashPost($postId)
                        : PostActionManager::deletePost($postId, false);
                    if ($ok) {
                        wp_send_json_success(['message' => 'Post eliminado', 'postId' => $postId, 'postType' => $postType]);
                    } else {
                        wp_send_json_error(['message' => 'No se pudo eliminar el post.']);
                    }
                    return;
                default:
                    wp_send_json_error(['message' => 'Acción no soportada.']);
                    return;
            }
        } catch (\Throwable $e) {
            GloryLogger::error('ContentActionAjaxHandler error: ' . $e->getMessage(), ['accion' => $accion, 'postId' => $postId]);
            wp_send_json_error(['message' => 'Error del servidor.']);
        }
    }
}


