<?php

namespace Glory\Plugins\AmazonProduct\Renderer;

use Glory\Plugins\AmazonProduct\Service\DiscountCalculator;

/**
 * Asset Loader - Gestiona la carga de CSS y JavaScript del plugin.
 * 
 * Responsabilidad unica: Enqueue de assets y estilos dinamicos.
 * Usa AssetManager de Glory para cache busting automatico.
 */
class AssetLoader
{
    /**
     * Registra los assets del plugin.
     */
    public function enqueue(): void
    {
        $this->enqueueStyles();
        $this->enqueueScripts();
        $this->enqueueInlineStyles();
    }

    /**
     * Registra los estilos CSS del plugin.
     */
    private function enqueueStyles(): void
    {
        \Glory\Manager\AssetManager::define(
            'style',
            'amazon-product-css',
            '/Glory/src/Plugins/AmazonProduct/assets/css/amazon-product.css',
            ['dev_mode' => true]
        );
    }

    /**
     * Registra los scripts JS del plugin.
     * Incluye localizacion con datos AJAX.
     */
    private function enqueueScripts(): void
    {
        \Glory\Manager\AssetManager::define(
            'script',
            'amazon-product-js',
            '/Glory/src/Plugins/AmazonProduct/assets/js/amazon-product.js',
            [
                'dev_mode' => true,
                'in_footer' => true,
                'localize' => [
                    'nombreObjeto' => 'amazonProductAjax',
                    'datos' => [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('amazon_product_nonce')
                    ]
                ]
            ]
        );
    }

    /**
     * Genera estilos inline dinamicos basados en configuracion del admin.
     * Colores personalizables: boton, hover, precio.
     */
    private function enqueueInlineStyles(): void
    {
        $btnBg = get_option('amazon_btn_bg', '#FFD814');
        $btnColor = get_option('amazon_btn_color', '#111111');
        $priceColor = get_option('amazon_price_color', '#B12704');
        $btnBgHover = DiscountCalculator::adjustBrightness($btnBg, -10);

        $customCss = "
            :root {
                --amazon-accent: {$btnBg};
                --amazon-accent-hover: {$btnBgHover};
                --amazon-price: {$priceColor};
            }
            .amazon-buy-button {
                color: {$btnColor};
            }
        ";

        // Prioridad 21 para que se agregue despues del CSS principal
        add_action('wp_enqueue_scripts', function () use ($customCss) {
            wp_add_inline_style('amazon-product-css', $customCss);
        }, 21);
    }
}
