<?php

namespace Glory\Plugins\AmazonProduct\Api;

use Glory\Plugins\AmazonProduct\Service\LicenseService;
use Glory\Plugins\AmazonProduct\Model\License;
use Glory\Core\GloryLogger;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handler de Webhooks de Stripe.
 * 
 * Procesa eventos de suscripcion para activar/desactivar licencias.
 * 
 * Eventos manejados:
 * - customer.subscription.created: Crear/activar licencia
 * - customer.subscription.updated: Actualizar estado
 * - customer.subscription.deleted: Suspender licencia
 * - invoice.paid: Renovacion exitosa
 * - invoice.payment_failed: Pago fallido
 */
class StripeWebhookHandler
{
    /**
     * Procesa el webhook de Stripe.
     */
    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_body();
        $sigHeader = $request->get_header('stripe-signature');

        /*
         * Verificar firma del webhook
         */
        $webhookSecret = defined('GLORY_STRIPE_WEBHOOK_SECRET')
            ? GLORY_STRIPE_WEBHOOK_SECRET
            : get_option('glory_stripe_webhook_secret', '');

        if (empty($webhookSecret)) {
            GloryLogger::error('Stripe Webhook: Secret no configurado');
            return new WP_REST_Response(['error' => 'Webhook not configured'], 500);
        }

        try {
            $event = $this->verifyWebhookSignature($payload, $sigHeader, $webhookSecret);
        } catch (\Exception $e) {
            GloryLogger::error('Stripe Webhook: Firma invalida - ' . $e->getMessage());
            return new WP_REST_Response(['error' => 'Invalid signature'], 400);
        }

        /*
         * Procesar evento
         */
        $eventType = $event['type'] ?? '';
        $eventData = $event['data']['object'] ?? [];

        GloryLogger::info("Stripe Webhook: Recibido {$eventType}");

        switch ($eventType) {
            case 'customer.subscription.created':
                return $this->handleSubscriptionCreated($eventData);

            case 'customer.subscription.updated':
                return $this->handleSubscriptionUpdated($eventData);

            case 'customer.subscription.deleted':
                return $this->handleSubscriptionDeleted($eventData);

            case 'invoice.paid':
                return $this->handleInvoicePaid($eventData);

            case 'invoice.payment_failed':
                return $this->handlePaymentFailed($eventData);

            default:
                GloryLogger::info("Stripe Webhook: Evento no manejado - {$eventType}");
                return new WP_REST_Response(['received' => true], 200);
        }
    }

    /**
     * Verifica la firma del webhook.
     * Implementacion simplificada sin libreria de Stripe.
     */
    private function verifyWebhookSignature(string $payload, ?string $sigHeader, string $secret): array
    {
        if (empty($sigHeader)) {
            throw new \Exception('Missing signature header');
        }

        /*
         * Parsear header Stripe-Signature
         * Formato: t=timestamp,v1=signature
         */
        $parts = explode(',', $sigHeader);
        $timestamp = null;
        $signature = null;

        foreach ($parts as $part) {
            $kv = explode('=', $part, 2);
            if ($kv[0] === 't') {
                $timestamp = $kv[1];
            } elseif ($kv[0] === 'v1') {
                $signature = $kv[1];
            }
        }

        if (!$timestamp || !$signature) {
            throw new \Exception('Invalid signature format');
        }

        /*
         * Verificar que no sea muy viejo (5 min tolerance)
         */
        if (abs(time() - (int)$timestamp) > 300) {
            throw new \Exception('Timestamp too old');
        }

        /*
         * Calcular firma esperada
         */
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Signature mismatch');
        }

        return json_decode($payload, true);
    }

    /**
     * Nueva suscripcion creada.
     */
    private function handleSubscriptionCreated(array $subscription): WP_REST_Response
    {
        $customerId = $subscription['customer'] ?? '';
        $subscriptionId = $subscription['id'] ?? '';
        $status = $subscription['status'] ?? '';

        /*
         * Obtener email del cliente desde metadata o customer
         */
        $customerEmail = $this->getCustomerEmail($customerId);

        if (empty($customerEmail)) {
            GloryLogger::error('Stripe Webhook: No se pudo obtener email del cliente');
            return new WP_REST_Response(['error' => 'Customer email not found'], 400);
        }

        /*
         * Buscar licencia existente o crear nueva
         */
        $license = LicenseService::findByEmail($customerEmail);

        if (!$license) {
            $license = LicenseService::create($customerEmail, $customerId, $subscriptionId);

            if (!$license) {
                return new WP_REST_Response(['error' => 'Could not create license'], 500);
            }
        } else {
            $license->setStripeCustomerId($customerId);
            $license->setStripeSubscriptionId($subscriptionId);
        }

        /*
         * Activar segun estado de Stripe
         */
        if ($status === 'trialing') {
            $license->setStatus(License::STATUS_TRIAL);
            $trialEnd = $subscription['trial_end'] ?? 0;
            if ($trialEnd) {
                $license->setExpiresAt($trialEnd);
            }
        } elseif ($status === 'active') {
            $license->setStatus(License::STATUS_ACTIVE);
            $periodEnd = $subscription['current_period_end'] ?? 0;
            if ($periodEnd) {
                $license->setExpiresAt($periodEnd);
            }
        }

        LicenseService::update($license);

        /*
         * TODO: Enviar email de bienvenida con API Key
         */
        $this->sendWelcomeEmail($license);

        GloryLogger::info("Stripe Webhook: Licencia creada/activada para {$customerEmail}");

        return new WP_REST_Response(['received' => true, 'license_created' => true], 200);
    }

    /**
     * Suscripcion actualizada.
     */
    private function handleSubscriptionUpdated(array $subscription): WP_REST_Response
    {
        $subscriptionId = $subscription['id'] ?? '';
        $status = $subscription['status'] ?? '';

        $license = LicenseService::findByStripeSubscription($subscriptionId);

        if (!$license) {
            GloryLogger::warning("Stripe Webhook: Licencia no encontrada para sub {$subscriptionId}");
            return new WP_REST_Response(['received' => true], 200);
        }

        /*
         * Mapear estado de Stripe a estado de licencia
         */
        switch ($status) {
            case 'active':
                $license->setStatus(License::STATUS_ACTIVE);
                $periodEnd = $subscription['current_period_end'] ?? 0;
                if ($periodEnd) {
                    $license->setExpiresAt($periodEnd);
                }
                /*
                 * Reiniciar GB en nuevo ciclo
                 */
                $license->resetUsage();
                break;

            case 'trialing':
                $license->setStatus(License::STATUS_TRIAL);
                break;

            case 'past_due':
            case 'unpaid':
                /*
                 * Mantener activo pero marcar para revision
                 */
                GloryLogger::warning("Stripe: Suscripcion {$subscriptionId} con pago pendiente");
                break;

            case 'canceled':
            case 'incomplete_expired':
                $license->setStatus(License::STATUS_EXPIRED);
                break;
        }

        LicenseService::update($license);

        return new WP_REST_Response(['received' => true], 200);
    }

    /**
     * Suscripcion eliminada/cancelada.
     */
    private function handleSubscriptionDeleted(array $subscription): WP_REST_Response
    {
        $subscriptionId = $subscription['id'] ?? '';

        $license = LicenseService::findByStripeSubscription($subscriptionId);

        if ($license) {
            LicenseService::expire($license);
            GloryLogger::info("Stripe: Licencia expirada por cancelacion - {$license->getEmail()}");
        }

        return new WP_REST_Response(['received' => true], 200);
    }

    /**
     * Factura pagada (renovacion exitosa).
     */
    private function handleInvoicePaid(array $invoice): WP_REST_Response
    {
        $subscriptionId = $invoice['subscription'] ?? '';

        if (empty($subscriptionId)) {
            return new WP_REST_Response(['received' => true], 200);
        }

        $license = LicenseService::findByStripeSubscription($subscriptionId);

        if ($license) {
            /*
             * Renovar licencia
             */
            $license->setStatus(License::STATUS_ACTIVE);
            $license->resetUsage();

            /*
             * Extender expiracion 30 dias
             */
            $license->setExpiresAt(time() + (30 * 24 * 3600));

            LicenseService::update($license);

            GloryLogger::info("Stripe: Licencia renovada - {$license->getEmail()}");
        }

        return new WP_REST_Response(['received' => true], 200);
    }

    /**
     * Pago fallido.
     */
    private function handlePaymentFailed(array $invoice): WP_REST_Response
    {
        $subscriptionId = $invoice['subscription'] ?? '';

        if (empty($subscriptionId)) {
            return new WP_REST_Response(['received' => true], 200);
        }

        $license = LicenseService::findByStripeSubscription($subscriptionId);

        if ($license) {
            /*
             * No suspender inmediatamente, Stripe reintentara
             * Solo logear para monitoreo
             */
            GloryLogger::warning("Stripe: Pago fallido para {$license->getEmail()}");

            /*
             * TODO: Enviar email de aviso al cliente
             */
        }

        return new WP_REST_Response(['received' => true], 200);
    }

    /**
     * Obtiene email del cliente desde Stripe API.
     */
    private function getCustomerEmail(string $customerId): ?string
    {
        if (empty($customerId)) {
            return null;
        }

        $secretKey = defined('GLORY_STRIPE_SECRET_KEY')
            ? GLORY_STRIPE_SECRET_KEY
            : get_option('glory_stripe_secret_key', '');

        if (empty($secretKey)) {
            return null;
        }

        $response = wp_remote_get(
            "https://api.stripe.com/v1/customers/{$customerId}",
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                ],
            ]
        );

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return $body['email'] ?? null;
    }

    /**
     * Envia email de bienvenida con API Key.
     */
    private function sendWelcomeEmail(License $license): void
    {
        $email = $license->getEmail();
        $apiKey = $license->getApiKey();

        $subject = 'Tu acceso a Amazon Product Plugin';

        $message = "
Hola!

Tu suscripcion ha sido activada. Aqui estan tus datos de acceso:

API Key: {$apiKey}

Como usar:
1. Instala el plugin en tu WordPress
2. Ve a Productos Amazon > Settings
3. Introduce tu API Key
4. Empieza a importar productos!

Tu plan incluye 4 GB de datos por mes.
Estado actual: {$license->getStatus()}

Si tienes dudas, contactanos.

Saludos,
El equipo
        ";

        wp_mail($email, $subject, $message);

        GloryLogger::info("Email de bienvenida enviado a {$email}");
    }
}
