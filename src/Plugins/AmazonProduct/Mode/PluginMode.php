<?php

namespace Glory\Plugins\AmazonProduct\Mode;

/**
 * Detecta y gestiona el modo de operacion del plugin.
 * 
 * Modos disponibles:
 * - 'server': Corre en el VPS central, tiene el scraper y API
 * - 'client': Corre en WordPress de clientes, se conecta a la API
 * 
 * El modo se define en wp-config.php:
 * define('GLORY_AMAZON_MODE', 'server');
 * 
 * Si no esta definido, por defecto es 'client'.
 */
class PluginMode
{
    public const MODE_SERVER = 'server';
    public const MODE_CLIENT = 'client';

    private static ?string $currentMode = null;

    /**
     * Obtiene el modo actual del plugin.
     */
    public static function getMode(): string
    {
        if (self::$currentMode === null) {
            self::$currentMode = defined('GLORY_AMAZON_MODE')
                ? GLORY_AMAZON_MODE
                : self::MODE_CLIENT;
        }
        return self::$currentMode;
    }

    /**
     * Verifica si esta en modo servidor.
     */
    public static function isServer(): bool
    {
        return self::getMode() === self::MODE_SERVER;
    }

    /**
     * Verifica si esta en modo cliente.
     */
    public static function isClient(): bool
    {
        return self::getMode() === self::MODE_CLIENT;
    }

    /**
     * Obtiene la URL del servidor API.
     * Solo relevante en modo cliente.
     */
    public static function getApiServerUrl(): string
    {
        if (defined('GLORY_API_SERVER')) {
            return rtrim(GLORY_API_SERVER, '/');
        }
        return 'https://api.wandori.us';
    }

    /**
     * Obtiene la API Key del cliente.
     * Solo relevante en modo cliente.
     */
    public static function getApiKey(): string
    {
        return get_option('glory_amazon_api_key', '');
    }

    /**
     * Guarda la API Key del cliente.
     */
    public static function setApiKey(string $apiKey): void
    {
        update_option('glory_amazon_api_key', sanitize_text_field($apiKey));
    }
}
