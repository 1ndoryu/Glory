<?php

/**
 * StripeConfig
 *
 * Configuracion centralizada para Stripe.
 * Busca las claves en constantes de wp-config.php o en opciones de WordPress.
 *
 * Uso en wp-config.php:
 *   define('GLORY_STRIPE_SECRET_KEY', 'sk_live_...');
 *   define('GLORY_STRIPE_PUBLISHABLE_KEY', 'pk_live_...');
 *   define('GLORY_STRIPE_WEBHOOK_SECRET', 'whsec_...');
 *
 * @package Glory\Services\Stripe
 */

namespace Glory\Services\Stripe;

class StripeConfig
{
    /**
     * Obtiene la clave secreta de Stripe
     */
    public static function getSecretKey(): string
    {
        if (defined('GLORY_STRIPE_SECRET_KEY')) {
            return GLORY_STRIPE_SECRET_KEY;
        }

        return get_option('glory_stripe_secret_key', '');
    }

    /**
     * Obtiene la clave publica de Stripe
     */
    public static function getPublishableKey(): string
    {
        if (defined('GLORY_STRIPE_PUBLISHABLE_KEY')) {
            return GLORY_STRIPE_PUBLISHABLE_KEY;
        }

        return get_option('glory_stripe_publishable_key', '');
    }

    /**
     * Obtiene el secreto del webhook
     */
    public static function getWebhookSecret(): string
    {
        if (defined('GLORY_STRIPE_WEBHOOK_SECRET')) {
            return GLORY_STRIPE_WEBHOOK_SECRET;
        }

        return get_option('glory_stripe_webhook_secret', '');
    }

    /**
     * Verifica si Stripe esta configurado
     */
    public static function isConfigured(): bool
    {
        return !empty(self::getSecretKey()) && !empty(self::getWebhookSecret());
    }

    /**
     * Verifica si estamos en modo test
     */
    public static function isTestMode(): bool
    {
        $secretKey = self::getSecretKey();
        return str_starts_with($secretKey, 'sk_test_');
    }

    /**
     * Obtiene la URL base de la API de Stripe
     */
    public static function getApiBaseUrl(): string
    {
        return 'https://api.stripe.com/v1';
    }

    /**
     * Obtiene informacion de configuracion (para diagnostico, sin exponer claves)
     */
    public static function getConfigInfo(): array
    {
        $secretKey = self::getSecretKey();
        $publishableKey = self::getPublishableKey();

        return [
            'configured' => self::isConfigured(),
            'test_mode' => self::isTestMode(),
            'secret_key_set' => !empty($secretKey),
            'secret_key_prefix' => $secretKey ? substr($secretKey, 0, 7) . '...' : null,
            'publishable_key_set' => !empty($publishableKey),
            'webhook_secret_set' => !empty(self::getWebhookSecret()),
        ];
    }
}
