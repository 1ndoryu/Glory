<?php
namespace Glory\Gbn\Ajax;

class ThemeSettingsHandler {
    public static function register() {
        add_action('wp_ajax_gbn_get_theme_settings', [__CLASS__, 'getSettings']);
        add_action('wp_ajax_gbn_save_theme_settings', [__CLASS__, 'saveSettings']);
    }

    public static function getSettings() {
        // error_log('[GBN] ThemeSettingsHandler::getSettings called');
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        
        if (!current_user_can('edit_theme_options')) {
            error_log('[GBN] ThemeSettingsHandler::getSettings - Permisos insuficientes');
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $settings = get_option('gbn_theme_settings', []);
        // error_log('[GBN] ThemeSettingsHandler::getSettings - Settings: ' . json_encode($settings));
        wp_send_json_success($settings);
    }

    public static function saveSettings() {
        error_log('[GBN] ThemeSettingsHandler::saveSettings called');
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        
        if (!current_user_can('edit_theme_options')) {
            error_log('[GBN] ThemeSettingsHandler::saveSettings - Permisos insuficientes');
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $settingsJson = isset($_POST['settings']) ? stripslashes($_POST['settings']) : '{}';
        error_log('[GBN] ThemeSettingsHandler::saveSettings - Payload: ' . $settingsJson);
        $settings = json_decode($settingsJson, true);

        if (!is_array($settings)) {
            error_log('[GBN] ThemeSettingsHandler::saveSettings - Formato inválido');
            wp_send_json_error(['message' => 'Formato inválido']);
        }

        update_option('gbn_theme_settings', $settings);
        error_log('[GBN] ThemeSettingsHandler::saveSettings - Guardado exitoso');
        wp_send_json_success(['message' => 'Configuración guardada']);
    }
}
