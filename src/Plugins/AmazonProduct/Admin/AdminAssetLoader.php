<?php

namespace Glory\Plugins\AmazonProduct\Admin;

use Glory\Manager\AssetManager;

/**
 * AdminAssetLoader - Gestiona la carga centralizada de assets del admin.
 * 
 * Responsabilidad unica: Registrar y cargar todos los CSS/JS del panel admin
 * de Amazon Product usando el AssetManager de Glory.
 * 
 * Beneficios:
 * - Cache busting automatico en dev_mode (usa filemtime)
 * - Centralizacion de configuraciones
 * - Evita scripts viejos cacheados
 */
class AdminAssetLoader
{
    private const ASSET_BASE_PATH = '/Glory/src/Plugins/AmazonProduct/assets';

    private static bool $registered = false;

    /**
     * Assets disponibles para el admin.
     * Cada entrada tiene:
     *  - type: 'style' o 'script'
     *  - file: nombre del archivo sin la carpeta css/js
     *  - localize: (opcional) datos para wp_localize_script
     */
    private static array $adminAssets = [
        /* 
         * CSS 
         */
        'amazon-manual-import-css' => [
            'type' => 'style',
            'file' => 'css/manual-import.css',
        ],
        'amazon-api-wizard-css' => [
            'type' => 'style',
            'file' => 'css/api-wizard.css',
        ],
        'amazon-sections-tab-css' => [
            'type' => 'style',
            'file' => 'css/sections-tab.css',
        ],

        /* 
         * JavaScript 
         */
        'amazon-manual-import-js' => [
            'type' => 'script',
            'file' => 'js/manual-import.js',
        ],
        'amazon-api-wizard-js' => [
            'type' => 'script',
            'file' => 'js/api-wizard.js',
        ],
        'amazon-sections-tab-js' => [
            'type' => 'script',
            'file' => 'js/sections-tab.js',
        ],
        'amazon-import-tab-js' => [
            'type' => 'script',
            'file' => 'js/import-tab.js',
        ],
    ];

    /**
     * Registra todos los assets del admin en AssetManager.
     * Se debe llamar una vez al inicializar el plugin en admin.
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        foreach (self::$adminAssets as $handle => $config) {
            $fullPath = self::ASSET_BASE_PATH . '/' . $config['file'];

            AssetManager::define(
                $config['type'],
                $handle,
                $fullPath,
                [
                    'dev_mode' => true,
                    'area' => 'admin',
                    'deps' => $config['type'] === 'script' ? ['jquery'] : [],
                    'in_footer' => true,
                ]
            );
        }

        self::$registered = true;
    }

    /**
     * Encola assets especificos para la tab de Manual Import.
     */
    public static function enqueueManualImport(): void
    {
        self::ensureRegistered();

        wp_enqueue_style('amazon-manual-import-css');
        wp_enqueue_script('amazon-manual-import-js');

        wp_localize_script('amazon-manual-import-js', 'manualImportConfig', [
            'nonce' => wp_create_nonce('amazon_manual_import_ajax'),
        ]);
    }

    /**
     * Encola assets especificos para la tab de API Wizard.
     */
    public static function enqueueApiWizard(): void
    {
        self::ensureRegistered();

        wp_enqueue_style('amazon-api-wizard-css');
        wp_enqueue_script('amazon-api-wizard-js');

        wp_localize_script('amazon-api-wizard-js', 'apiWizardData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('api_wizard_nonce'),
            'currentApiKey' => get_option('amazon_api_key', ''),
        ]);
    }

    /**
     * Encola assets especificos para la tab de Sections.
     */
    public static function enqueueSections(): void
    {
        self::ensureRegistered();

        wp_enqueue_style('amazon-sections-tab-css');
        wp_enqueue_script('amazon-sections-tab-js');

        wp_localize_script('amazon-sections-tab-js', 'glorySections', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('glory_sections_nonce'),
            'strings' => [
                'confirmRestore' => 'Restaurar esta seccion a sus valores por defecto?',
                'saving' => 'Guardando...',
                'saved' => 'Guardado',
                'error' => 'Error al guardar',
                'loading' => 'Cargando...',
            ],
        ]);
    }

    /**
     * Encola assets especificos para la tab de Import Products.
     */
    public static function enqueueImportTab(): void
    {
        self::ensureRegistered();

        wp_enqueue_script('amazon-import-tab-js');

        wp_localize_script('amazon-import-tab-js', 'amazonImportConfig', [
            'searchNonce' => wp_create_nonce('amazon_search_ajax'),
            'importNonce' => wp_create_nonce('amazon_import_ajax'),
        ]);
    }

    /**
     * Asegura que los assets estan registrados antes de encolar.
     */
    private static function ensureRegistered(): void
    {
        if (!self::$registered) {
            self::register();
        }
    }
}
