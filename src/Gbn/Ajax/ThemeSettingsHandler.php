<?php
namespace Glory\Gbn\Ajax;

class ThemeSettingsHandler {
    public static function register() {
        add_action('wp_ajax_gbn_get_theme_settings', [__CLASS__, 'getSettings']);
        add_action('wp_ajax_gbn_save_theme_settings', [__CLASS__, 'saveSettings']);
    }

    public static function getSettings() {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        
        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $settings = get_option('gbn_theme_settings', []);
        wp_send_json_success($settings);
    }

    public static function saveSettings() {
        check_ajax_referer('glory_gbn_nonce', 'nonce');
        
        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $settingsJson = isset($_POST['settings']) ? stripslashes($_POST['settings']) : '{}';
        $settings = json_decode($settingsJson, true);

        if (!is_array($settings)) {
            wp_send_json_error(['message' => 'Formato inválido']);
        }

        update_option('gbn_theme_settings', $settings);
        wp_send_json_success(['message' => 'Configuración guardada']);
    }
}
