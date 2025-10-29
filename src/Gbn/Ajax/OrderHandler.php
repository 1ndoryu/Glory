<?php

namespace Glory\Gbn\Ajax;

class OrderHandler
{
    public static function saveOrder(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        $gbnId  = isset($_POST['gbnId']) ? sanitize_text_field($_POST['gbnId']) : '';
        $idsRaw = isset($_POST['postIds']) ? wp_unslash($_POST['postIds']) : '[]';
        $ids = json_decode((string) $idsRaw, true);
        if (!$pageId || $gbnId === '' || !is_array($ids)) {
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }
        $ids = array_values(array_filter(array_map('absint', $ids)));
        update_post_meta($pageId, 'gbn_order_' . $gbnId, $ids);
        wp_send_json_success(['ok' => true]);
    }
}


