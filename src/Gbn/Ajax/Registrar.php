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
        
        // Diagnostics / Control Panel API
        add_action('wp_ajax_gbn_diagnostics_dump', [Handlers\DiagnosticsHandler::class, 'dump']);
        add_action('wp_ajax_gbn_diagnostics_validate', [Handlers\DiagnosticsHandler::class, 'validate']);
        add_action('wp_ajax_gbn_diagnostics_logs', [Handlers\DiagnosticsHandler::class, 'getLogs']);
        
        // Fase 13: PostRender - Contenido Dinámico
        add_action('wp_ajax_gbn_post_render_preview', [Handlers\PostRenderHandler::class, 'getPreview']);
        add_action('wp_ajax_gbn_get_post_types', [Handlers\PostRenderHandler::class, 'getPostTypes']);
        add_action('wp_ajax_gbn_get_taxonomies', [Handlers\PostRenderHandler::class, 'getTaxonomies']);
        
        // Paginación AJAX (disponible para todos los usuarios - frontend)
        add_action('wp_ajax_gbn_post_render_paginate', [Handlers\PostRenderHandler::class, 'paginate']);
        add_action('wp_ajax_nopriv_gbn_post_render_paginate', [Handlers\PostRenderHandler::class, 'paginate']);

        // Fase 14.5: Envío de Formularios GBN (disponible para todos los usuarios - frontend)
        add_action('wp_ajax_gbn_form_submit', [Handlers\FormSubmitHandler::class, 'handle']);
        add_action('wp_ajax_nopriv_gbn_form_submit', [Handlers\FormSubmitHandler::class, 'handle']);
    }
}


