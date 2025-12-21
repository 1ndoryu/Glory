<?php

/**
 * AbstractStripeWebhookHandler
 *
 * Clase base abstracta para manejar webhooks de Stripe.
 * Las implementaciones concretas deben definir que hacer con cada evento.
 *
 * Ejemplo de uso:
 * ```php
 * class MiWebhookHandler extends AbstractStripeWebhookHandler
 * {
 *     protected function onSubscriptionCreated(array $subscription): void { ... }
 *     protected function onSubscriptionUpdated(array $subscription): void { ... }
 *     // etc.
 * }
 * ```
 *
 * @package Glory\Services\Stripe
 */

namespace Glory\Services\Stripe;

use Glory\Core\GloryLogger;
use WP_REST_Request;
use WP_REST_Response;

abstract class AbstractStripeWebhookHandler
{
    protected StripeApiClient $stripeClient;

    public function __construct()
    {
        $this->stripeClient = new StripeApiClient();
    }

    /**
     * Procesa el webhook entrante
     */
    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_body();
        $sigHeader = $request->get_header('stripe-signature');

        try {
            $event = StripeWebhookVerifier::verify($payload, $sigHeader);
        } catch (StripeWebhookException $e) {
            GloryLogger::error('Stripe Webhook: ' . $e->getMessage());
            return new WP_REST_Response(
                ['error' => $e->getErrorCode()],
                $e->getHttpStatus()
            );
        }

        $eventType = StripeWebhookVerifier::getEventType($event);
        $eventData = StripeWebhookVerifier::getEventObject($event);
        $eventId = StripeWebhookVerifier::getEventId($event);

        GloryLogger::info("Stripe Webhook: Recibido {$eventType} (ID: {$eventId})");

        try {
            $this->dispatchEvent($eventType, $eventData, $event);
            return new WP_REST_Response(['received' => true], 200);
        } catch (\Throwable $e) {
            GloryLogger::error("Stripe Webhook Error: " . $e->getMessage());
            return new WP_REST_Response(['error' => 'Processing error'], 500);
        }
    }

    /**
     * Despacha el evento al metodo correspondiente
     */
    protected function dispatchEvent(string $eventType, array $eventData, array $fullEvent): void
    {
        switch ($eventType) {
            /* Suscripciones */
            case 'customer.subscription.created':
                $this->onSubscriptionCreated($eventData, $fullEvent);
                break;

            case 'customer.subscription.updated':
                $this->onSubscriptionUpdated($eventData, $fullEvent);
                break;

            case 'customer.subscription.deleted':
                $this->onSubscriptionDeleted($eventData, $fullEvent);
                break;

            /* Checkout */
            case 'checkout.session.completed':
                $this->onCheckoutCompleted($eventData, $fullEvent);
                break;

            /* Facturas */
            case 'invoice.paid':
                $this->onInvoicePaid($eventData, $fullEvent);
                break;

            case 'invoice.payment_failed':
                $this->onPaymentFailed($eventData, $fullEvent);
                break;

            /* Pagos */
            case 'payment_intent.succeeded':
                $this->onPaymentSucceeded($eventData, $fullEvent);
                break;

            case 'payment_intent.payment_failed':
                $this->onPaymentIntentFailed($eventData, $fullEvent);
                break;

            default:
                $this->onUnhandledEvent($eventType, $eventData, $fullEvent);
                break;
        }
    }

    /**
     * Obtiene el email del cliente desde Stripe
     */
    protected function getCustomerEmail(string $customerId): ?string
    {
        return $this->stripeClient->getCustomerEmail($customerId);
    }

    /**
     * Calcula los dias de duracion de una suscripcion o trial
     */
    protected function calculateDays(int $startTimestamp, int $endTimestamp): int
    {
        return max(0, (int) ceil(($endTimestamp - $startTimestamp) / 86400));
    }

    /* 
     * Metodos a implementar por las clases concretas
     * Por defecto no hacen nada
     */

    protected function onSubscriptionCreated(array $subscription, array $fullEvent): void {}

    protected function onSubscriptionUpdated(array $subscription, array $fullEvent): void {}

    protected function onSubscriptionDeleted(array $subscription, array $fullEvent): void {}

    protected function onCheckoutCompleted(array $session, array $fullEvent): void {}

    protected function onInvoicePaid(array $invoice, array $fullEvent): void {}

    protected function onPaymentFailed(array $invoice, array $fullEvent): void {}

    protected function onPaymentSucceeded(array $paymentIntent, array $fullEvent): void {}

    protected function onPaymentIntentFailed(array $paymentIntent, array $fullEvent): void {}

    protected function onUnhandledEvent(string $eventType, array $eventData, array $fullEvent): void
    {
        GloryLogger::info("Stripe Webhook: Evento no manejado - {$eventType}");
    }
}
