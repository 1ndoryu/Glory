<?php

namespace Glory\Plugins\AmazonProduct;

use Glory\Manager\PostTypeManager;
use Glory\Core\GloryFeatures;
use Glory\Plugins\AmazonProduct\Controller\AdminController;
use Glory\Plugins\AmazonProduct\Renderer\ProductRenderer;
use Glory\Plugins\AmazonProduct\Service\ProductSyncService;
use Glory\Plugins\AmazonProduct\Service\LicenseService;
use Glory\Plugins\AmazonProduct\Admin\ApiWizardAjaxHandler;
use Glory\Plugins\AmazonProduct\Mode\PluginMode;
use Glory\Plugins\AmazonProduct\Api\ApiEndpoints;
use Glory\Plugins\AmazonProduct\Service\SmtpConfig;
use Glory\Plugins\AmazonProduct\Model\TransactionLog;

/**
 * Amazon Product Plugin for Glory Framework.
 * 
 * Soporta dos modos:
 * - SERVER: Corre en VPS central, tiene scraper y API
 * - CLIENT: Corre en WordPress de clientes, se conecta a API
 * 
 * El modo se define en wp-config.php:
 * define('GLORY_AMAZON_MODE', 'server'); // o 'client'
 */
class AmazonProductPlugin
{
    public function init(): void
    {
        /*
         * Inicializar segun modo
         */
        if (PluginMode::isServer()) {
            $this->initServerMode();
        } else {
            $this->initClientMode();
        }

        /*
         * Componentes comunes a ambos modos
         */
        $this->registerPostType();

        $renderer = new ProductRenderer();
        $renderer->init();

        add_action('wp_ajax_amazon_filter_products', [$renderer, 'handleAjaxRequest']);
        add_action('wp_ajax_nopriv_amazon_filter_products', [$renderer, 'handleAjaxRequest']);
    }

    /**
     * Inicializa modo SERVIDOR (VPS central).
     * - Registra API REST
     * - Crea tablas de licencias
     * - Dashboard de administracion de licencias
     */
    private function initServerMode(): void
    {
        /*
         * Configurar SMTP para envio de emails (Brevo)
         */
        SmtpConfig::init();

        /*
         * Crear tablas en activacion
         */
        add_action('after_switch_theme', [LicenseService::class, 'createTable']);

        /*
         * Verificar tablas existen
         */
        add_action('init', function () {
            if (get_option('glory_amazon_tables_created') !== '1') {
                LicenseService::createTable();
                update_option('glory_amazon_tables_created', '1');
            }
        });

        /*
         * Registrar API REST endpoints
         */
        add_action('rest_api_init', [ApiEndpoints::class, 'register']);

        /*
         * Registrar PostType de Transacciones (historial de compras)
         */
        add_action('init', [TransactionLog::class, 'register']);

        /*
         * Admin con tabs de servidor (licencias, estadisticas, logs)
         */
        if (is_admin()) {
            $adminController = new \Glory\Plugins\AmazonProduct\Controller\ServerAdminController();
            $adminController->init();
        }

        /*
         * Cron para verificar licencias expiradas
         */
        add_action('init', [$this, 'handleCronSchedule']);
        add_action('glory_check_expired_licenses', [LicenseService::class, 'checkExpiredLicenses']);

        if (!wp_next_scheduled('glory_check_expired_licenses')) {
            wp_schedule_event(time(), 'daily', 'glory_check_expired_licenses');
        }

        /*
         * Sync local (el servidor tambien puede tener productos)
         */
        ProductSyncService::init();
    }

    /**
     * Inicializa modo CLIENTE (WordPress de usuarios).
     * - Se conecta a API externa en lugar de scraper local
     * - UI de importacion
     */
    private function initClientMode(): void
    {
        if (is_admin()) {
            $adminController = new AdminController();
            $adminController->init();
        }

        /* 
         * Servicio de sincronizacion automatica para modo cliente
         * Usa el ApiClient para actualizar productos via el servidor SaaS
         */
        \Glory\Plugins\AmazonProduct\Service\ClientSyncService::init();
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
