<?php

/**
 * StripeApiClient
 *
 * Cliente HTTP para la API de Stripe.
 * Implementacion sin libreria, usando wp_remote_*.
 *
 * @package Glory\Services\Stripe
 */

namespace Glory\Services\Stripe;

use Glory\Core\GloryLogger;

class StripeApiClient
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct(?string $secretKey = null)
    {
        $this->secretKey = $secretKey ?? StripeConfig::getSecretKey();
        $this->baseUrl = StripeConfig::getApiBaseUrl();
    }

    /**
     * Realiza una peticion GET a la API de Stripe
     */
    public function get(string $endpoint): array
    {
        return $this->request('GET', $endpoint);
    }

    /**
     * Realiza una peticion POST a la API de Stripe
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Realiza una peticion DELETE a la API de Stripe
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Ejecuta la peticion HTTP
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        if (empty($this->secretKey)) {
            return [
                'success' => false,
                'error' => 'Stripe secret key not configured',
            ];
        }

        $url = $this->baseUrl . $endpoint;

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'timeout' => 30,
        ];

        if (!empty($data) && $method === 'POST') {
            $args['body'] = http_build_query($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            GloryLogger::error('Stripe API Error: ' . $response->get_error_message());
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($statusCode >= 400) {
            $errorMessage = $body['error']['message'] ?? 'Unknown error';
            GloryLogger::error("Stripe API Error ({$statusCode}): {$errorMessage}");
            return [
                'success' => false,
                'error' => $errorMessage,
                'status_code' => $statusCode,
            ];
        }

        return [
            'success' => true,
            'data' => $body,
        ];
    }

    /* 
     * Metodos de conveniencia para recursos comunes 
     */

    /**
     * Obtiene un cliente de Stripe
     */
    public function getCustomer(string $customerId): array
    {
        return $this->get("/customers/{$customerId}");
    }

    /**
     * Obtiene el email de un cliente
     */
    public function getCustomerEmail(string $customerId): ?string
    {
        $result = $this->getCustomer($customerId);

        if ($result['success']) {
            return $result['data']['email'] ?? null;
        }

        return null;
    }

    /**
     * Obtiene una suscripcion de Stripe
     */
    public function getSubscription(string $subscriptionId): array
    {
        return $this->get("/subscriptions/{$subscriptionId}");
    }

    /**
     * Cancela una suscripcion
     */
    public function cancelSubscription(string $subscriptionId, bool $immediately = false): array
    {
        if ($immediately) {
            return $this->delete("/subscriptions/{$subscriptionId}");
        }

        return $this->post("/subscriptions/{$subscriptionId}", [
            'cancel_at_period_end' => 'true',
        ]);
    }

    /**
     * Obtiene una factura
     */
    public function getInvoice(string $invoiceId): array
    {
        return $this->get("/invoices/{$invoiceId}");
    }

    /**
     * Crea un Portal de Cliente para gestionar suscripcion
     *
     * @param string $customerId ID del cliente en Stripe
     * @param string $returnUrl URL a donde redirigir despues
     */
    public function createBillingPortalSession(string $customerId, string $returnUrl): array
    {
        return $this->post('/billing_portal/sessions', [
            'customer' => $customerId,
            'return_url' => $returnUrl,
        ]);
    }
}
