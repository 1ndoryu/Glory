<?php

/**
 * StripeWebhookVerifier
 *
 * Verifica la firma de webhooks de Stripe.
 * Implementacion sin dependencia de la libreria oficial de Stripe.
 *
 * @package Glory\Services\Stripe
 */

namespace Glory\Services\Stripe;

use Glory\Core\GloryLogger;

class StripeWebhookVerifier
{
    /**
     * Tolerancia de tiempo para firmas (5 minutos)
     */
    private const TIMESTAMP_TOLERANCE = 300;

    /**
     * Verifica y parsea el payload del webhook
     *
     * @param string $payload Cuerpo crudo del request
     * @param string|null $signatureHeader Header Stripe-Signature
     * @param string|null $webhookSecret Secreto del webhook (si no se pasa, usa config)
     * @return array Evento parseado
     * @throws StripeWebhookException Si la firma es invalida
     */
    public static function verify(string $payload, ?string $signatureHeader, ?string $webhookSecret = null): array
    {
        $secret = $webhookSecret ?? StripeConfig::getWebhookSecret();

        if (empty($secret)) {
            throw new StripeWebhookException('Webhook secret not configured', 'config_error');
        }

        if (empty($signatureHeader)) {
            throw new StripeWebhookException('Missing Stripe-Signature header', 'missing_signature');
        }

        /* Parsear header Stripe-Signature: t=timestamp,v1=signature */
        $parts = self::parseSignatureHeader($signatureHeader);

        if (!$parts['timestamp'] || !$parts['signature']) {
            throw new StripeWebhookException('Invalid signature format', 'invalid_format');
        }

        /* Verificar que no sea muy viejo */
        if (abs(time() - (int) $parts['timestamp']) > self::TIMESTAMP_TOLERANCE) {
            throw new StripeWebhookException('Timestamp too old', 'timestamp_expired');
        }

        /* Calcular firma esperada */
        $signedPayload = $parts['timestamp'] . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        if (!hash_equals($expectedSignature, $parts['signature'])) {
            throw new StripeWebhookException('Signature mismatch', 'signature_mismatch');
        }

        $event = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new StripeWebhookException('Invalid JSON payload', 'invalid_json');
        }

        return $event;
    }

    /**
     * Parsea el header Stripe-Signature
     */
    private static function parseSignatureHeader(string $header): array
    {
        $result = [
            'timestamp' => null,
            'signature' => null,
        ];

        $parts = explode(',', $header);

        foreach ($parts as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) === 2) {
                if ($kv[0] === 't') {
                    $result['timestamp'] = $kv[1];
                } elseif ($kv[0] === 'v1') {
                    $result['signature'] = $kv[1];
                }
            }
        }

        return $result;
    }

    /**
     * Extrae el tipo de evento
     */
    public static function getEventType(array $event): string
    {
        return $event['type'] ?? '';
    }

    /**
     * Extrae el objeto principal del evento
     */
    public static function getEventObject(array $event): array
    {
        return $event['data']['object'] ?? [];
    }

    /**
     * Extrae el ID del evento
     */
    public static function getEventId(array $event): string
    {
        return $event['id'] ?? '';
    }
}
