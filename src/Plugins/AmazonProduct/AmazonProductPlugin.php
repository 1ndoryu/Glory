<?php

namespace Glory\Plugins\AmazonProduct;

use Glory\Manager\PostTypeManager;
use Glory\Core\GloryFeatures;
use Glory\Plugins\AmazonProduct\Controller\AdminController;
use Glory\Plugins\AmazonProduct\Renderer\ProductRenderer;

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
    }

    private function registerPostType(): void
    {
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
                'asin'  => '',
                'price' => '',
                'rating' => '',
                'reviews' => '',
                'prime' => '0',
            ]
        );
    }
}
