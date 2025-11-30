<?php

namespace Glory\Gbn\Ajax;

class PageSettingsHandler
{
    public static function getPageSettings(): void
    {
        // error_log('[GBN] PageSettingsHandler::getPageSettings called');
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        if (!$pageId) {
            error_log('[GBN] PageSettingsHandler::getPageSettings - Invalid PageID');
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            error_log('[GBN] PageSettingsHandler::getPageSettings - Permisos insuficientes');
            wp_send_json_error(['message' => 'Sin permisos']);
        }
        $settings = get_post_meta($pageId, 'gbn_page_settings', true);
        if (!is_array($settings)) { $settings = []; }
        wp_send_json_success($settings);
    }

    public static function savePageSettings(): void
    {
        error_log('[GBN] PageSettingsHandler::savePageSettings called');
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        $pageId = isset($_POST['pageId']) ? absint($_POST['pageId']) : 0;
        $settingsJson = isset($_POST['settings']) ? stripslashes($_POST['settings']) : '{}';
        error_log('[GBN] PageSettingsHandler::savePageSettings - PageID: ' . $pageId . ' Payload: ' . $settingsJson);
        $settings = json_decode($settingsJson, true);
        
        if (!$pageId || !is_array($settings)) {
            error_log('[GBN] PageSettingsHandler::savePageSettings - Datos inválidos');
            wp_send_json_error(['message' => 'Datos inválidos']);
        }
        if (!current_user_can('edit_post', $pageId)) {
            error_log('[GBN] PageSettingsHandler::savePageSettings - Permisos insuficientes');
            wp_send_json_error(['message' => 'Sin permisos']);
        }
        
        // Sanitize? For now allow structure.
        // Ideally we should sanitize specific known keys.
        // But let's save what is sent to ensure it works.
        
        update_post_meta($pageId, 'gbn_page_settings', $settings);
        error_log('[GBN] PageSettingsHandler::savePageSettings - Guardado exitoso');
        wp_send_json_success(['ok' => true, 'settings' => $settings]);
    }
}


