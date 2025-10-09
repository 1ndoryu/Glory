<?php

namespace Glory\Helpers;

/**
 * Helper estático para configurar la navegación AJAX desde el tema de forma clara.
 */
class AjaxNav
{
    private static array $config = [
        'enabled' => true,
        'contentSelector' => '#main',
        'mainScrollSelector' => '#main',
    ];

    private static bool $registered = false;

    /**
     * Habilita o deshabilita la navegación AJAX.
     */
    public static function set(bool $enabled): void
    {
        self::$config['enabled'] = $enabled;
        self::ensureFilterRegistered();
    }

    /**
     * Establece el selector del contenedor principal de contenido.
     */
    public static function contentSelector(string $selector): void
    {
        self::$config['contentSelector'] = $selector;
        self::ensureFilterRegistered();
    }

    /**
     * Establece el selector usado para hacer scroll al cambio de página.
     */
    public static function mainScrollSelector(string $selector): void
    {
        self::$config['mainScrollSelector'] = $selector;
        self::ensureFilterRegistered();
    }

    /**
     * Registra el filtro que mezcla la configuración del tema con la configuración por defecto.
     */
    public static function registerFilter(): void
    {
        if (self::$registered) {
            return;
        }

        if (function_exists('add_filter')) {
            add_filter('glory/nav_config', [self::class, 'applyFilter']);
            self::$registered = true;
        }
    }

    public static function applyFilter(array $baseConfig): array
    {
        return array_merge($baseConfig, self::$config);
    }

    private static function ensureFilterRegistered(): void
    {
        if (!self::$registered && function_exists('add_filter')) {
            self::registerFilter();
        }
    }
}


