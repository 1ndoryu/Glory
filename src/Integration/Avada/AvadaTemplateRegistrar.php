<?php

namespace Glory\Integration\Avada;

use Glory\Utility\TemplateRegistry;

class AvadaTemplateRegistrar
{
    public static function register(): void
    {
        add_action('init', [self::class, 'registerTemplates']);
    }

    public static function registerTemplates(): void
    {
        if (!class_exists(TemplateRegistry::class)) {
            return;
        }

        $view = get_stylesheet_directory() . '/App/view/portafolio-item.php';
        if (!file_exists($view)) {
            $view = get_template_directory() . '/App/view/portafolio-item.php';
        }
        if (!file_exists($view)) {
            return;
        }

        TemplateRegistry::register(
            'glory_portfolio_item',
            'Glory Portafolio Item',
            function(\WP_Post $post, array $config = []) use ($view) {
                require_once $view;
                if (function_exists('portfolioItem')) {
                    $itemClass = isset($config['claseItem']) ? (string) $config['claseItem'] : 'glory-content-item';
                    portfolioItem($post, $itemClass);
                } else {
                    echo esc_html(get_the_title($post));
                }
            },
            [ 'avada_portfolio' ]
        );
    }
}


