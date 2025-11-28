<?php

namespace Glory\Plugins\AmazonProduct\Service;

use Glory\Core\GloryLogger;

class AmazonApiService
{
    private const API_HOST = 'amazon-data.p.rapidapi.com';
    private const API_URL = 'https://amazon-data.p.rapidapi.com';
    private string $apiKey;
    private string $region;

    public function __construct()
    {
        $this->apiKey = get_option('amazon_api_key', '');
        $this->region = get_option('amazon_api_region', 'us');
    }

    public static function getDomain(string $region): string
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

        return $domains[$region] ?? 'amazon.com';
    }

    public function getProductByAsin(string $asin): array
    {
        $cacheKey = 'amazon_product_' . $asin;
        $cached = get_transient($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->makeRequest('asin.php', ['asin' => $asin, 'region' => $this->region]);

        if (empty($response)) {
            return [];
        }

        set_transient($cacheKey, $response, DAY_IN_SECONDS);

        return $response;
    }

    public function searchProducts(string $keyword, int $page = 1): array
    {
        $cacheKey = 'amazon_search_' . md5($keyword . $page);
        $cached = get_transient($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->makeRequest('search.php', ['keyword' => $keyword, 'region' => $this->region, 'page' => $page]);

        if (empty($response)) {
            return [];
        }

        set_transient($cacheKey, $response, HOUR_IN_SECONDS);

        return $response;
    }

    public function getDeals(int $page = 1): array
    {
        $cacheKey = 'amazon_deals_' . $this->region . '_' . $page;
        $cached = get_transient($cacheKey);

        if ($cached !== false) {
            return $cached;
        }

        $response = $this->makeRequest('deal.php', ['region' => $this->region, 'page' => $page]);

        if (empty($response)) {
            return [];
        }

        // Cache deals for 2 hours to save API calls
        set_transient($cacheKey, $response, 2 * HOUR_IN_SECONDS);

        return $response;
    }

    private function makeRequest(string $endpoint, array $params): array
    {
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
                "x-rapidapi-host: " . get_option('amazon_api_host', self::API_HOST),
                "x-rapidapi-key: " . $this->apiKey
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            GloryLogger::error("AmazonApiService Error: " . $err);
            return [];
        }

        return json_decode($response, true) ?: [];
    }
}
