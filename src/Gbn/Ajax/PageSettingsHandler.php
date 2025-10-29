<?php

namespace Glory\Gbn\Ajax;

class PageSettingsHandler
{
    public static function getPageSettings(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        if (!$pageId) {
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }
        $settings = get_post_meta($pageId, 'gbn_page_settings', true);
        if (!is_array($settings)) { $settings = []; }
        $bg = isset($settings['background_color']) ? (string) $settings['background_color'] : '';
        wp_send_json_success(['background_color' => $bg]);
    }

    public static function savePageSettings(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        $valuesRaw = isset($_POST['values']) ? wp_unslash($_POST['values']) : '{}';
        $values = json_decode((string) $valuesRaw, true);
        if (!$pageId || !is_array($values)) {
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }
        $settings = get_post_meta($pageId, 'gbn_page_settings', true);
        if (!is_array($settings)) { $settings = []; }
        if (array_key_exists('background_color', $values)) {
            $v = (string) $values['background_color'];
            if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $v) || preg_match('/^rgba?\([\d\s.,]+\)$/', $v)) {
                $settings['background_color'] = $v;
            } else {
                $settings['background_color'] = '';
            }
        }
        update_post_meta($pageId, 'gbn_page_settings', $settings);
        wp_send_json_success(['ok' => true, 'settings' => $settings]);
    }
}


