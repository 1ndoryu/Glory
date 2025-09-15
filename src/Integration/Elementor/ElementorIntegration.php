<?php

namespace Glory\Integration\Elementor;

class ElementorIntegration
{
    public static function register(): void
    {
        // Registrar categoría personalizada "Glory" en Elementor
        add_action('elementor/elements/categories_registered', [self::class, 'registerCategory']);

        // Registrar widget cuando Elementor esté listo
        add_action('elementor/widgets/register', [self::class, 'registerWidgets']);
    }

    public static function registerCategory($elements_manager): void
    {
        if (!method_exists($elements_manager, 'add_category')) {
            return;
        }
        $elements_manager->add_category('glory', [
            'title' => 'Glory',
            'icon'  => 'fa fa-plug',
        ]);
    }

    public static function registerWidgets($widgets_manager): void
    {
        if (!class_exists('Elementor\\Widget_Base')) {
            return; // Elementor no activo
        }

        // Registrar el widget principal de contenido
        if (class_exists(ContentRenderWidget::class)) {
            $widgets_manager->register(new ContentRenderWidget());
        }
    }
}


