<?php

/**
 * StripeCheckoutService
 *
 * Servicio para crear sesiones de Stripe Checkout.
 * Permite generar URLs de pago para suscripciones o pagos unicos.
 *
 * @package Glory\Services\Stripe
 */

namespace Glory\Services\Stripe;

use Glory\Core\GloryLogger;

class StripeCheckoutService
{
    private StripeApiClient $client;

    public function __construct(?StripeApiClient $client = null)
    {
        $this->client = $client ?? new StripeApiClient();
    }

    /**
     * Crea una sesion de checkout para suscripcion
     *
     * @param array $options Opciones de la sesion:
     *   - priceId: ID del precio en Stripe (requerido)
     *   - successUrl: URL de exito (requerido)
     *   - cancelUrl: URL de cancelacion (requerido)
     *   - customerEmail: Email del cliente (opcional, prellenado)
     *   - customerId: ID de cliente existente (opcional)
     *   - trialDays: Dias de trial (opcional)
     *   - metadata: Metadatos adicionales (opcional)
     *   - allowPromotionCodes: Permitir codigos promocionales (opcional)
     */
    public function createSubscriptionSession(array $options): array
    {
        $required = ['priceId', 'successUrl', 'cancelUrl'];
        foreach ($required as $field) {
            if (empty($options[$field])) {
                return [
                    'success' => false,
                    'error' => "Missing required field: {$field}",
                ];
            }
        }

        $params = [
            'mode' => 'subscription',
            'line_items[0][price]' => $options['priceId'],
            'line_items[0][quantity]' => 1,
            'success_url' => $options['successUrl'],
            'cancel_url' => $options['cancelUrl'],
        ];

        /* Cliente existente o nuevo */
        if (!empty($options['customerId'])) {
            $params['customer'] = $options['customerId'];
        } elseif (!empty($options['customerEmail'])) {
            $params['customer_email'] = $options['customerEmail'];
        }

        /* Trial */
        if (!empty($options['trialDays']) && $options['trialDays'] > 0) {
            $params['subscription_data[trial_period_days]'] = $options['trialDays'];
        }

        /* Metadata */
        if (!empty($options['metadata']) && is_array($options['metadata'])) {
            foreach ($options['metadata'] as $key => $value) {
                $params["subscription_data[metadata][{$key}]"] = $value;
            }
        }

        /* Codigos promocionales */
        if (!empty($options['allowPromotionCodes'])) {
            $params['allow_promotion_codes'] = 'true';
        }

        $result = $this->client->post('/checkout/sessions', $params);

        if ($result['success']) {
            return [
                'success' => true,
                'sessionId' => $result['data']['id'],
                'url' => $result['data']['url'],
            ];
        }

        return $result;
    }

    /**
     * Crea una sesion de checkout para pago unico
     *
     * @param array $options Opciones de la sesion:
     *   - priceId: ID del precio en Stripe (requerido)
     *   - successUrl: URL de exito (requerido)
     *   - cancelUrl: URL de cancelacion (requerido)
     *   - customerEmail: Email del cliente (opcional)
     *   - quantity: Cantidad (opcional, default 1)
     *   - metadata: Metadatos adicionales (opcional)
     */
    public function createPaymentSession(array $options): array
    {
        $required = ['priceId', 'successUrl', 'cancelUrl'];
        foreach ($required as $field) {
            if (empty($options[$field])) {
                return [
                    'success' => false,
                    'error' => "Missing required field: {$field}",
                ];
            }
        }

        $params = [
            'mode' => 'payment',
            'line_items[0][price]' => $options['priceId'],
            'line_items[0][quantity]' => $options['quantity'] ?? 1,
            'success_url' => $options['successUrl'],
            'cancel_url' => $options['cancelUrl'],
        ];

        if (!empty($options['customerEmail'])) {
            $params['customer_email'] = $options['customerEmail'];
        }

        if (!empty($options['metadata']) && is_array($options['metadata'])) {
            foreach ($options['metadata'] as $key => $value) {
                $params["payment_intent_data[metadata][{$key}]"] = $value;
            }
        }

        $result = $this->client->post('/checkout/sessions', $params);

        if ($result['success']) {
            return [
                'success' => true,
                'sessionId' => $result['data']['id'],
                'url' => $result['data']['url'],
            ];
        }

        return $result;
    }

    /**
     * Obtiene una sesion de checkout existente
     */
    public function getSession(string $sessionId): array
    {
        return $this->client->get("/checkout/sessions/{$sessionId}");
    }

    /**
     * Genera URLs de checkout predefinidas (usando Payment Links de Stripe)
     * Los Payment Links se crean en el dashboard de Stripe
     */
    public static function getPaymentLinkUrl(string $linkId, array $prefill = []): string
    {
        $url = "https://buy.stripe.com/{$linkId}";

        if (!empty($prefill)) {
            $params = [];
            if (!empty($prefill['email'])) {
                $params['prefilled_email'] = $prefill['email'];
            }
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        }

        return $url;
    }
}
