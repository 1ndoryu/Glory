<?php

namespace Glory\Gbn\Ajax;

class Registrar
{
    public static function register(): void
    {
        add_action('wp_ajax_gbn_save_order', [OrderHandler::class, 'saveOrder']);
        add_action('wp_ajax_gbn_save_options', [ContentHandler::class, 'saveOptions']);
        add_action('wp_ajax_gbn_save_config', [ContentHandler::class, 'saveConfig']);
        add_action('wp_ajax_gbn_preview_block', [ContentHandler::class, 'previewBlock']);
        add_action('wp_ajax_gbn_restore_page', [ContentHandler::class, 'restorePage']);
        add_action('wp_ajax_gbn_get_page_settings', [PageSettingsHandler::class, 'getPageSettings']);
        add_action('wp_ajax_gbn_save_page_settings', [PageSettingsHandler::class, 'savePageSettings']);
        add_action('wp_ajax_create_glory_link', [LibraryHandler::class, 'createGloryLink']);
        add_action('wp_ajax_update_glory_link', [LibraryHandler::class, 'updateGloryLink']);
        add_action('wp_ajax_create_glory_header', [LibraryHandler::class, 'createGloryHeader']);
        add_action('wp_ajax_update_glory_header', [LibraryHandler::class, 'updateGloryHeader']);
        add_action('wp_ajax_gbn_delete_item', [DeleteHandler::class, 'deleteItem']);
        add_action('wp_ajax_gbn_get_theme_settings', [ThemeSettingsHandler::class, 'getSettings']);
        add_action('wp_ajax_gbn_save_theme_settings', [ThemeSettingsHandler::class, 'saveSettings']);
        add_action('wp_ajax_gbn_log_client_event', [Handlers\LoggerHandler::class, 'handle']);
    }
}


