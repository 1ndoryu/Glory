<?php

namespace Glory\Plugins\AmazonProduct;

use Glory\Manager\PostTypeManager;
use Glory\Core\GloryFeatures;
use Glory\Plugins\AmazonProduct\Controller\AdminController;
use Glory\Plugins\AmazonProduct\Renderer\ProductRenderer;
use Glory\Plugins\AmazonProduct\Service\ProductSyncService;

/**
 * Amazon Product Plugin for Glory Framework.
 * 
 * Handles Amazon product integration, CPT registration, and rendering.
 */
class AmazonProductPlugin
{
    public function init(): void
    {
        // 1. Register Post Type
        $this->registerPostType();

        // 2. Initialize Admin Controller (if in admin)
        if (is_admin()) {
            $adminController = new AdminController();
            $adminController->init();
        }

        // 3. Initialize Frontend Renderer
        $renderer = new ProductRenderer();
        $renderer->init();

        // AJAX Hooks
        add_action('wp_ajax_amazon_filter_products', [$renderer, 'handleAjaxRequest']);
        add_action('wp_ajax_nopriv_amazon_filter_products', [$renderer, 'handleAjaxRequest']);

        // AJAX Hooks para importacion manual
        if (is_admin()) {
            $this->registerManualImportAjax();
        }

        // Cron Hooks - FEAT-07: Usar ProductSyncService
        add_action('init', [$this, 'handleCronSchedule']);
        ProductSyncService::init();
    }

    /**
     * Registra los handlers AJAX para importacion manual de productos.
     */
    private function registerManualImportAjax(): void
    {
        $manualImportTab = new \Glory\Plugins\AmazonProduct\Admin\Tabs\ManualImportTab();
        add_action('wp_ajax_amazon_parse_html', [$manualImportTab, 'ajaxParseHtml']);
        add_action('wp_ajax_amazon_import_product', [$manualImportTab, 'ajaxImportProduct']);
    }

    private function registerPostType(): void
    {
        // DATA-01: Meta fields documentados en API_DATA_STRUCTURE.md
        PostTypeManager::define(
            'amazon_product',
            [
                'public'       => true,
                'show_in_rest' => true,
                'menu_icon'    => 'dashicons-cart',
                'supports'     => ['title', 'editor', 'thumbnail', 'custom-fields', 'excerpt'],
                'rewrite'      => ['slug' => 'producto'],
            ],
            'Producto Amazon',
            'Productos Amazon',
            [
                // Campos basicos (todos los endpoints)
                'asin'             => '',
                'price'            => '',
                'original_price'   => '',
                'rating'           => '',
                'reviews'          => '',
                'prime'            => '0',
                'image_url'        => '',
                'product_url'      => '',
                // Campos adicionales (deal.php)
                'discount_percent' => '',
                'currency'         => '',
                'deal_ends_at'     => '',
                // Campos de sincronizacion
                'last_synced'      => '',
            ]
        );

        $this->registerTaxonomy();
    }

    private function registerTaxonomy(): void
    {
        $labels = [
            'name'              => 'Categorías Amazon',
            'singular_name'     => 'Categoría Amazon',
            'search_items'      => 'Buscar Categorías',
            'all_items'         => 'Todas las Categorías',
            'parent_item'       => 'Categoría Padre',
            'parent_item_colon' => 'Categoría Padre:',
            'edit_item'         => 'Editar Categoría',
            'update_item'       => 'Actualizar Categoría',
            'add_new_item'      => 'Añadir Nueva Categoría',
            'new_item_name'     => 'Nombre de Nueva Categoría',
            'menu_name'         => 'Categorías',
        ];

        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'categoria-amazon'],
            'show_in_rest'      => true,
        ];

        register_taxonomy('amazon_category', ['amazon_product'], $args);
    }

    public function handleCronSchedule(): void
    {
        $freq = get_option('amazon_sync_frequency', 'off');
        $hook = 'amazon_product_sync_event';

        if ($freq === 'off') {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        } else {
            if (!wp_next_scheduled($hook)) {
                wp_schedule_event(time(), $freq, $hook);
            }
        }
    }

    /**
     * Sincroniza productos usando ProductSyncService.
     * FEAT-07/FEAT-08: Delegamos la logica al servicio especializado.
     */
    public function syncProducts(): void
    {
        ProductSyncService::runScheduledSync();
    }
}
