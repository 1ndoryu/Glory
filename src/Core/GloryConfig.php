<?php

namespace Glory\Core;

/*
 * Configuración de rutas del proyecto.
 * Lee glory.config.php de la raíz del tema una sola vez.
 * Si no existe, usa defaults convencionales (App/).
 *
 * Esto permite que Glory sea agnóstico: el proyecto decide su estructura
 * sin que Glory hardcodee rutas internas de App/.
 */
final class GloryConfig
{
    private static ?array $config = null;

    private const DEFAULTS = [
        'config_dir'    => 'App/Config',
        'content_dir'   => 'App/Content',
        'assets_dir'    => 'App/Assets',
        'react_dir'     => 'App/React',
        'templates_dir' => 'App/Templates',
    ];

    public static function load(): void
    {
        if (self::$config !== null) {
            return;
        }

        $configFile = get_template_directory() . '/glory.config.php';

        if (is_readable($configFile)) {
            $loaded = include $configFile;
            self::$config = is_array($loaded) ? array_merge(self::DEFAULTS, $loaded) : self::DEFAULTS;
        } else {
            self::$config = self::DEFAULTS;
        }
    }

    /**
     * Obtiene una ruta de configuración del proyecto.
     * Siempre relativa a la raíz del tema (sin leading slash).
     */
    public static function get(string $key, ?string $fallback = null): string
    {
        self::load();
        return self::$config[$key] ?? $fallback ?? '';
    }

    /**
     * Ruta absoluta: get_template_directory() + config key.
     */
    public static function path(string $key): string
    {
        return get_template_directory() . '/' . self::get($key);
    }

    /**
     * Registra un hook para que proyectos puedan agregar asset paths propios
     * después de que el framework se inicialice.
     */
    public static function triggerAssetPathRegistration(): void
    {
        do_action('glory/register_asset_paths');
    }
}
