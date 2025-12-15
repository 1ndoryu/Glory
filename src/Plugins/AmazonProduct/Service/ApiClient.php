<?php

namespace Glory\Plugins\AmazonProduct\Service;

use Glory\Plugins\AmazonProduct\Mode\PluginMode;

/**
 * Cliente HTTP para conectarse a la API del servidor Glory.
 * 
 * Este servicio solo se usa en MODO CLIENTE.
 * Se conecta a la API REST del servidor central para:
 * - Buscar productos
 * - Obtener productos por ASIN  
 * - Verificar estado de licencia
 */
class ApiClient
{
    private string $apiKey;
    private string $serverUrl;
    private ?array $lastUsageInfo = null;

    public function __construct()
    {
        $this->apiKey = PluginMode::getApiKey();
        $this->serverUrl = PluginMode::getApiServerUrl();
    }

    /**
     * Busca productos en Amazon via la API del servidor.
     */
    public function searchProducts(string $keyword, int $page = 1): array
    {
        $response = $this->request('POST', '/wp-json/glory/v1/amazon/search', [
            'keyword' => $keyword,
            'page' => $page,
            'region' => get_option('amazon_api_region', 'es')
        ]);

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => $response['error'] ?? 'Error desconocido',
                'products' => []
            ];
        }

        $this->lastUsageInfo = $response['usage'] ?? null;

        return [
            'success' => true,
            'products' => $response['data'] ?? [],
            'usage' => $this->lastUsageInfo
        ];
    }

    /**
     * Obtiene un producto por ASIN via la API del servidor.
     */
    public function getProductByAsin(string $asin): array
    {
        $response = $this->request('POST', '/wp-json/glory/v1/amazon/product/' . $asin, [
            'region' => get_option('amazon_api_region', 'es')
        ]);

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => $response['error'] ?? 'Error desconocido',
                'product' => null
            ];
        }

        $this->lastUsageInfo = $response['usage'] ?? null;

        return [
            'success' => true,
            'product' => $response['data'] ?? null,
            'usage' => $this->lastUsageInfo
        ];
    }

    /**
     * Verifica el estado de la licencia y GB disponibles.
     */
    public function getLicenseStatus(): array
    {
        $response = $this->request('GET', '/wp-json/glory/v1/amazon/license/status');

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => $response['error'] ?? 'Error desconocido',
                'status' => null
            ];
        }

        return [
            'success' => true,
            'status' => $response['data'] ?? null
        ];
    }

    /**
     * Obtiene la ultima informacion de uso recibida.
     */
    public function getLastUsageInfo(): ?array
    {
        return $this->lastUsageInfo;
    }

    /**
     * Verifica si hay una API Key configurada.
     */
    public function hasApiKey(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Verifica si la conexion con el servidor es valida.
     */
    public function testConnection(): array
    {
        if (!$this->hasApiKey()) {
            return [
                'success' => false,
                'error' => 'No hay API Key configurada'
            ];
        }

        return $this->getLicenseStatus();
    }

    /**
     * Realiza una peticion HTTP al servidor.
     * 
     * Timeout alto (120s) porque el servidor puede tardar en:
     * - Reintentar scraping si hay CAPTCHA
     * - Procesar requests pesados
     */
    private function request(string $method, string $endpoint, array $body = []): array
    {
        if (!$this->hasApiKey()) {
            return [
                'success' => false,
                'error' => 'API Key no configurada. Ve a Configuracion para activar tu licencia.'
            ];
        }

        $url = $this->serverUrl . $endpoint;
        $startTime = microtime(true);

        $args = [
            'method' => $method,
            'timeout' => 120,
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ];

        if ($method === 'POST' && !empty($body)) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);
        $elapsedMs = round((microtime(true) - $startTime) * 1000);

        if (is_wp_error($response)) {
            $errorCode = $response->get_error_code();
            $errorMessage = $response->get_error_message();

            error_log("ApiClient Error: {$errorCode} - {$errorMessage} | URL: {$url} | Tiempo: {$elapsedMs}ms");

            return [
                'success' => false,
                'error' => "Error de conexion: {$errorMessage} ({$errorCode})"
            ];
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);
        $data = json_decode($responseBody, true);

        if ($statusCode === 401) {
            return [
                'success' => false,
                'error' => 'API Key invalida o expirada'
            ];
        }

        if ($statusCode === 402) {
            return [
                'success' => false,
                'error' => 'Limite de GB alcanzado. Renueva tu suscripcion.'
            ];
        }

        if ($statusCode === 429) {
            return [
                'success' => false,
                'error' => 'Demasiadas peticiones. Espera un momento.'
            ];
        }

        if ($statusCode >= 400) {
            return [
                'success' => false,
                'error' => $data['message'] ?? "Error del servidor (HTTP $statusCode)"
            ];
        }

        return [
            'success' => true,
            'data' => $data['data'] ?? $data,
            'usage' => $data['usage'] ?? null
        ];
    }
}
