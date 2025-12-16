<?php

namespace Glory\Plugins\AmazonProduct\Api;

use Glory\Plugins\AmazonProduct\Service\LicenseService;
use Glory\Plugins\AmazonProduct\Model\License;
use Glory\Plugins\AmazonProduct\Model\TransactionLog;
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

        GloryLogger::info("=== NUEVA SUSCRIPCION ===");
        GloryLogger::info("Customer ID: {$customerId}");
        GloryLogger::info("Subscription ID: {$subscriptionId}");
        GloryLogger::info("Status: {$status}");

        /*
         * Obtener email del cliente desde metadata o customer
         */
        $customerEmail = $this->getCustomerEmail($customerId);
        GloryLogger::info("Email obtenido: " . ($customerEmail ?: 'NULL'));

        /*
         * Si no hay email (cliente de prueba CLI), generar uno temporal
         * En produccion los clientes de Stripe Checkout siempre tienen email
         */
        if (empty($customerEmail)) {
            $customerEmail = 'test_' . substr($customerId, 4, 8) . '@stripe-test.local';
            GloryLogger::warning("Stripe Webhook: Cliente sin email, usando temporal: {$customerEmail}");
        }

        /*
         * Buscar licencia existente o crear nueva
         */
        $license = LicenseService::findByEmail($customerEmail);

        if (!$license) {
            $license = LicenseService::create($customerEmail, $customerId, $subscriptionId);

            if (!$license) {
                GloryLogger::error("ERROR: No se pudo crear la licencia");
                return new WP_REST_Response(['error' => 'Could not create license'], 500);
            }
            GloryLogger::info("Licencia NUEVA creada");
        } else {
            $license->setStripeCustomerId($customerId);
            $license->setStripeSubscriptionId($subscriptionId);
            GloryLogger::info("Licencia EXISTENTE actualizada");
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
            GloryLogger::info("Estado: TRIAL hasta " . date('Y-m-d', $trialEnd));
        } elseif ($status === 'active') {
            $license->setStatus(License::STATUS_ACTIVE);
            $periodEnd = $subscription['current_period_end'] ?? 0;
            if ($periodEnd) {
                $license->setExpiresAt($periodEnd);
            }
            GloryLogger::info("Estado: ACTIVE hasta " . date('Y-m-d', $periodEnd));
        }

        LicenseService::update($license);

        /*
         * Registrar transaccion en historial
         */
        $amount = $subscription['items']['data'][0]['price']['unit_amount'] ?? 0;
        $currency = $subscription['currency'] ?? 'usd';

        TransactionLog::create([
            'type' => $status === 'trialing'
                ? TransactionLog::TYPE_TRIAL_STARTED
                : TransactionLog::TYPE_SUBSCRIPTION_CREATED,
            'email' => $customerEmail,
            'subscription_id' => $subscriptionId,
            'customer_id' => $customerId,
            'amount' => $amount,
            'currency' => $currency,
            'api_key' => $license->getApiKey(),
            'event_id' => $subscription['id'] ?? '',
            'details' => [
                'status' => $status,
                'plan' => $subscription['items']['data'][0]['plan']['id'] ?? 'unknown',
                'gb_limit' => $license->getGbLimit(),
            ],
        ]);
        GloryLogger::info("Transaccion registrada en historial");

        /*
         * Enviar email de bienvenida con API Key
         */
        $this->sendWelcomeEmail($license);

        GloryLogger::info("=== FIN NUEVA SUSCRIPCION ===");

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
        $customerId = $subscription['customer'] ?? '';

        GloryLogger::info("=== SUSCRIPCION CANCELADA ===");
        GloryLogger::info("Subscription ID: {$subscriptionId}");

        $license = LicenseService::findByStripeSubscription($subscriptionId);

        if ($license) {
            $email = $license->getEmail();

            LicenseService::expire($license);
            GloryLogger::info("Licencia expirada para: {$email}");

            /* Registrar cancelacion en historial */
            TransactionLog::create([
                'type' => TransactionLog::TYPE_SUBSCRIPTION_CANCELED,
                'email' => $email,
                'subscription_id' => $subscriptionId,
                'customer_id' => $customerId,
                'amount' => 0,
                'currency' => 'usd',
                'details' => [
                    'reason' => 'Suscripcion cancelada por el cliente o expirada',
                    'canceled_at' => date('Y-m-d H:i:s'),
                ],
            ]);
            GloryLogger::info("Cancelacion registrada en historial");
        } else {
            GloryLogger::warning("Licencia no encontrada para sub: {$subscriptionId}");
        }

        GloryLogger::info("=== FIN CANCELACION ===");
        return new WP_REST_Response(['received' => true], 200);
    }

    /**
     * Factura pagada (renovacion exitosa).
     */
    private function handleInvoicePaid(array $invoice): WP_REST_Response
    {
        $subscriptionId = $invoice['subscription'] ?? '';
        $customerId = $invoice['customer'] ?? '';
        $amount = $invoice['amount_paid'] ?? 0;
        $currency = $invoice['currency'] ?? 'usd';

        if (empty($subscriptionId)) {
            return new WP_REST_Response(['received' => true], 200);
        }

        GloryLogger::info("=== PAGO RECIBIDO (RENOVACION) ===");
        GloryLogger::info("Subscription ID: {$subscriptionId}");
        GloryLogger::info("Monto: {$amount} {$currency}");

        $license = LicenseService::findByStripeSubscription($subscriptionId);

        if ($license) {
            $email = $license->getEmail();
            $gbAnterior = $license->getGbUsed();

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

            GloryLogger::info("Licencia renovada para: {$email}");
            GloryLogger::info("GB reseteados: {$gbAnterior} -> 0");
            GloryLogger::info("Nueva expiracion: " . date('Y-m-d', time() + (30 * 24 * 3600)));

            /* Registrar renovacion en historial */
            TransactionLog::create([
                'type' => TransactionLog::TYPE_SUBSCRIPTION_RENEWED,
                'email' => $email,
                'subscription_id' => $subscriptionId,
                'customer_id' => $customerId,
                'amount' => $amount,
                'currency' => $currency,
                'details' => [
                    'gb_reset_from' => $gbAnterior,
                    'new_expiration' => date('Y-m-d', time() + (30 * 24 * 3600)),
                    'invoice_id' => $invoice['id'] ?? '',
                ],
            ]);
            GloryLogger::info("Renovacion registrada en historial");
        } else {
            GloryLogger::warning("Licencia no encontrada para sub: {$subscriptionId}");
        }

        GloryLogger::info("=== FIN RENOVACION ===");
        return new WP_REST_Response(['received' => true], 200);
    }

    /**
     * Pago fallido.
     */
    private function handlePaymentFailed(array $invoice): WP_REST_Response
    {
        $subscriptionId = $invoice['subscription'] ?? '';
        $customerId = $invoice['customer'] ?? '';
        $amount = $invoice['amount_due'] ?? 0;
        $currency = $invoice['currency'] ?? 'usd';

        if (empty($subscriptionId)) {
            return new WP_REST_Response(['received' => true], 200);
        }

        GloryLogger::info("=== PAGO FALLIDO ===");
        GloryLogger::info("Subscription ID: {$subscriptionId}");
        GloryLogger::info("Monto intentado: {$amount} {$currency}");

        $license = LicenseService::findByStripeSubscription($subscriptionId);

        if ($license) {
            $email = $license->getEmail();

            /*
             * No suspender inmediatamente, Stripe reintentara
             * Solo logear para monitoreo
             */
            GloryLogger::warning("Pago fallido para: {$email}");

            /* Registrar pago fallido en historial */
            TransactionLog::create([
                'type' => TransactionLog::TYPE_PAYMENT_FAILED,
                'email' => $email,
                'subscription_id' => $subscriptionId,
                'customer_id' => $customerId,
                'amount' => $amount,
                'currency' => $currency,
                'details' => [
                    'invoice_id' => $invoice['id'] ?? '',
                    'attempt_count' => $invoice['attempt_count'] ?? 1,
                    'next_payment_attempt' => $invoice['next_payment_attempt'] ?? null,
                ],
            ]);
            GloryLogger::info("Pago fallido registrado en historial");

            /*
             * TODO: Enviar email de aviso al cliente
             */
        }

        GloryLogger::info("=== FIN PAGO FALLIDO ===");
        return new WP_REST_Response(['received' => true], 200);
    }

    /**
     * Obtiene email del cliente desde Stripe API.
     */
    private function getCustomerEmail(string $customerId): ?string
    {
        GloryLogger::info("getCustomerEmail: customerId = {$customerId}");

        if (empty($customerId)) {
            GloryLogger::error("getCustomerEmail: customerId vacío");
            return null;
        }

        $secretKey = defined('GLORY_STRIPE_SECRET_KEY')
            ? GLORY_STRIPE_SECRET_KEY
            : get_option('glory_stripe_secret_key', '');

        GloryLogger::info("getCustomerEmail: GLORY_STRIPE_SECRET_KEY defined = " . (defined('GLORY_STRIPE_SECRET_KEY') ? 'true' : 'false'));
        GloryLogger::info("getCustomerEmail: secretKey length = " . strlen($secretKey));

        if (empty($secretKey)) {
            GloryLogger::error("getCustomerEmail: secretKey vacía");
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
            GloryLogger::error("getCustomerEmail: wp_error = " . $response->get_error_message());
            return null;
        }

        $responseCode = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        GloryLogger::info("getCustomerEmail: Stripe response code = {$responseCode}");
        GloryLogger::info("getCustomerEmail: email = " . ($body['email'] ?? 'NULL'));

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
