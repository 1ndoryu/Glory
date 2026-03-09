<?php

namespace Glory\Seo;

/**
 * RuntimeSeoData
 *
 * Almacen estatico request-scoped para datos SEO dinamicos.
 * Permite que DynamicSeoResolver inyecte datos SEO especificos por request
 * (ej: titulo del sample, descripcion del perfil) que los renderers
 * consultan ANTES de los fallbacks habituales (post_meta, defaultSeoMap).
 *
 * Ciclo de vida: se puebla en hook `wp` (antes de wp_head) y se consume
 * durante wp_head por MetaTagRenderer, OpenGraphRenderer y JsonLdRenderer.
 */
class RuntimeSeoData
{
    private static ?array $data = null;

    /**
     * Establece los datos SEO para la request actual.
     * Solo debe llamarse una vez por request desde DynamicSeoResolver.
     */
    public static function set(array $seoData): void
    {
        self::$data = $seoData;
    }

    /**
     * Verifica si hay datos SEO dinamicos disponibles.
     */
    public static function has(): bool
    {
        return self::$data !== null;
    }

    /**
     * Obtiene un campo especifico o null si no existe.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$data === null) {
            return $default;
        }
        return self::$data[$key] ?? $default;
    }

    /**
     * Obtiene todos los datos SEO o null si no hay override.
     */
    public static function getAll(): ?array
    {
        return self::$data;
    }

    /**
     * Limpia los datos (para testing o reset).
     */
    public static function clear(): void
    {
        self::$data = null;
    }
}
