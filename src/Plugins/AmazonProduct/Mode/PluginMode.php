<?php

namespace Glory\Plugins\AmazonProduct\Mode;

/**
 * Detecta y gestiona el modo de operacion del plugin.
 * 
 * Modos disponibles:
 * - 'server': Corre en el VPS central, tiene el scraper y API
 * - 'client': Corre en WordPress de clientes, se conecta a la API
 * 
 * El modo se puede definir de dos formas (en orden de prioridad):
 * 
 * 1. Archivo .env en la raiz del tema:
 *    GLORY_AMAZON_MODE=server
 *    GLORY_STRIPE_SECRET_KEY=sk_live_xxx
 *    GLORY_STRIPE_WEBHOOK_SECRET=whsec_xxx
 * 
 * 2. wp-config.php:
 *    define('GLORY_AMAZON_MODE', 'server');
 * 
 * Si no esta definido, por defecto es 'client'.
 */
class PluginMode
{
    public const MODE_SERVER = 'server';
    public const MODE_CLIENT = 'client';

    private static ?string $currentMode = null;

    /**
     * Obtiene una variable de entorno (primero .env, luego constante).
     */
    private static function getEnvVar(string $name, string $default = ''): string
    {
        if (isset($_ENV[$name]) && $_ENV[$name] !== '') {
            return $_ENV[$name];
        }

        $envValue = getenv($name);
        if ($envValue !== false && $envValue !== '') {
            return $envValue;
        }

        if (defined($name)) {
            return constant($name);
        }

        return $default;
    }

    /**
     * Obtiene el modo actual del plugin.
     */
    public static function getMode(): string
    {
        if (self::$currentMode === null) {
            self::$currentMode = self::getEnvVar('GLORY_AMAZON_MODE', self::MODE_CLIENT);
            error_log('[PluginMode] Modo detectado: ' . self::$currentMode);
            error_log('[PluginMode] ENV GLORY_AMAZON_MODE: ' . ($_ENV['GLORY_AMAZON_MODE'] ?? 'NOT SET'));
            error_log('[PluginMode] getenv GLORY_AMAZON_MODE: ' . (getenv('GLORY_AMAZON_MODE') ?: 'NOT SET'));
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
        return self::getEnvVar('GLORY_API_SERVER', 'https://api.wandori.us');
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

    /**
     * Obtiene la Stripe Secret Key (solo modo servidor).
     */
    public static function getStripeSecretKey(): string
    {
        return self::getEnvVar('GLORY_STRIPE_SECRET_KEY', '');
    }

    /**
     * Obtiene el Stripe Webhook Secret (solo modo servidor).
     */
    public static function getStripeWebhookSecret(): string
    {
        return self::getEnvVar('GLORY_STRIPE_WEBHOOK_SECRET', '');
    }
}
