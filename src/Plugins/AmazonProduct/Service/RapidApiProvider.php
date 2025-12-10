<?php

namespace Glory\Plugins\AmazonProduct\Service;

use Glory\Core\GloryLogger;

/**
 * RapidAPI Provider - Implementacion para amazon-data.p.rapidapi.com.
 * 
 * ARCH-01: Este es el proveedor actual del plugin.
 * Implementa ApiProviderInterface para permitir intercambio futuro.
 * 
 * Endpoints disponibles:
 * - search.php: Busqueda por keyword
 * - asin.php: Producto por ASIN
 * - deal.php: Ofertas actuales
 */
class RapidApiProvider implements ApiProviderInterface
{
    private const API_HOST = 'amazon-data.p.rapidapi.com';
    private const API_URL = 'https://amazon-data.p.rapidapi.com';

    private string $apiKey;
    private string $apiHost;
    private string $region;

    public function __construct()
    {
        $this->apiKey = get_option('amazon_api_key', '');
        $this->apiHost = get_option('amazon_api_host', self::API_HOST);
        $this->region = get_option('amazon_api_region', 'us');
    }

    /**
     * {@inheritdoc}
     */
    public function searchProducts(string $keyword, int $page = 1): array
    {
        $cacheKey = 'amazon_search_' . md5($keyword . $page . $this->region);
        $cached = get_transient($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->makeRequest('search.php', [
            'keyword' => $keyword,
            'region' => $this->region,
            'page' => $page
        ]);

        if (empty($response)) {
            return [];
        }

        set_transient($cacheKey, $response, HOUR_IN_SECONDS);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductByAsin(string $asin): array
    {
        $cacheKey = 'amazon_product_' . $asin . '_' . $this->region;
        $cached = get_transient($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->makeRequest('asin.php', [
            'asin' => $asin,
            'region' => $this->region
        ]);

        if (empty($response)) {
            return [];
        }

        set_transient($cacheKey, $response, DAY_IN_SECONDS);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeals(int $page = 1): array
    {
        $cacheKey = 'amazon_deals_' . $this->region . '_' . $page;
        $cached = get_transient($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->makeRequest('deal.php', [
            'region' => $this->region,
            'page' => $page
        ]);

        if (empty($response)) {
            return [];
        }

        // Cache deals for 2 hours to save API calls
        set_transient($cacheKey, $response, 2 * HOUR_IN_SECONDS);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderName(): string
    {
        return 'RapidAPI (amazon-data)';
    }

    /**
     * {@inheritdoc}
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getDomain(): string
    {
        $domains = [
            'us' => 'amazon.com',
            'es' => 'amazon.es',
            'uk' => 'amazon.co.uk',
            'de' => 'amazon.de',
            'fr' => 'amazon.fr',
            'it' => 'amazon.it',
            'ca' => 'amazon.ca',
            'jp' => 'amazon.co.jp',
            'au' => 'amazon.com.au',
            'br' => 'amazon.com.br',
            'mx' => 'amazon.com.mx',
        ];

        return $domains[$this->region] ?? 'amazon.com';
    }

    /**
     * Realiza una peticion HTTP a la API.
     * 
     * @param string $endpoint Endpoint a llamar
     * @param array $params Parametros de la peticion
     * @return array Respuesta decodificada o array vacio
     */
    private function makeRequest(string $endpoint, array $params): array
    {
        // Verificar limite de API antes de llamar
        if (!ApiUsageTracker::canMakeCall()) {
            GloryLogger::warning("RapidApiProvider: Limite mensual de API alcanzado");
            return [];
        }

        $url = self::API_URL . '/' . $endpoint . '?' . http_build_query($params);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "x-rapidapi-host: " . $this->apiHost,
                "x-rapidapi-key: " . $this->apiKey
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            GloryLogger::error("RapidApiProvider Error: " . $err);
            ApiUsageTracker::recordCall($endpoint, $params, false);
            return [];
        }

        $result = json_decode($response, true) ?: [];
        $success = !empty($result);

        // Registrar llamada en el tracker
        ApiUsageTracker::recordCall($endpoint, $params, $success);

        return $result;
    }
}
