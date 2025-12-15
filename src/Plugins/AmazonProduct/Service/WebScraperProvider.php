<?php

namespace Glory\Plugins\AmazonProduct\Service;

use Glory\Core\GloryLogger;

/**
 * Web Scraper Provider - Extracción directa desde Amazon (Sin API).
 * 
 * Este proveedor simula un navegador web para obtener datos directamente
 * de la web de Amazon cuando las APIs fallan.
 */
class WebScraperProvider implements ApiProviderInterface
{
    private string $region;

    // User-Agents rotativos para evitar bloqueos
    private array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0'
    ];

    public function __construct()
    {
        $this->region = get_option('amazon_api_region', 'es'); // Default a ES
    }

    public function searchProducts(string $keyword, int $page = 1): array
    {
        $domain = $this->getDomain();
        $url = "https://www.{$domain}/s?k=" . urlencode($keyword) . "&page={$page}";

        $html = $this->fetchUrl($url);
        if (empty($html)) {
            return [];
        }

        return $this->parseSearchResults($html);
    }

    public function getProductByAsin(string $asin): array
    {
        $domain = $this->getDomain();
        $url = "https://www.{$domain}/dp/{$asin}";

        $html = $this->fetchUrl($url);
        if (empty($html)) {
            return [];
        }

        return $this->parseProductPage($html, $asin);
    }

    public function getDeals(int $page = 1): array
    {
        // El scraping de deals es muy complejo y variante, retornamos vacío por seguridad inicial
        return [];
    }

    public function getProviderName(): string
    {
        return 'Web Scraper (Directo)';
    }

    public function isConfigured(): bool
    {
        return true; // No requiere API Key
    }

    public function getDomain(): string
    {
        $domains = [
            'us' => 'amazon.com',
            'es' => 'amazon.es',
            'uk' => 'amazon.co.uk',
            'de' => 'amazon.de',
            'fr' => 'amazon.fr',
            'it' => 'amazon.it',
        ];

        return $domains[$this->region] ?? 'amazon.es';
    }

    private function fetchUrl(string $url): string
    {
        $ch = curl_init();
        $ua = $this->userAgents[array_rand($this->userAgents)];

        // Proxy Configuration
        $proxy = get_option('amazon_scraper_proxy', '');
        $proxyAuth = get_option('amazon_scraper_proxy_auth', '');

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_ENCODING => '', // Soporta gzip, deflate automaticamente
            CURLOPT_USERAGENT => $ua,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
                'Upgrade-Insecure-Requests: 1',
                'Cache-Control: max-age=0',
                'Referer: https://www.google.com/'
            ],
            CURLOPT_COOKIEFILE => '',
        ];

        // Añadir Proxy si esta configurado
        if (!empty($proxy)) {
            $options[CURLOPT_PROXY] = $proxy;
            // Detectar si es SOCKS5 (ej: socks5://...)
            if (strpos($proxy, 'socks5') !== false) {
                $options[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
            }

            if (!empty($proxyAuth)) {
                $options[CURLOPT_PROXYUSERPWD] = $proxyAuth;
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch) || $httpCode != 200) {
            GloryLogger::error("Scraper Error ({$httpCode}): " . curl_error($ch));
            return '';
        }

        curl_close($ch);
        return $response;
    }

    private function parseSearchResults(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $products = [];
        // Selectores comunes de Amazon Search (pueden cambiar, Amazon los rota)
        // Buscamos contenedores de resultados de búsqueda estándar
        $nodes = $xpath->query('//div[@data-component-type="s-search-result"]');

        foreach ($nodes as $node) {
            try {
                $asin = $node->getAttribute('data-asin');
                if (empty($asin)) continue;

                // Titulo
                $titleNode = $xpath->query('.//h2//span', $node)->item(0);
                $title = $titleNode ? $titleNode->textContent : '';

                // Precio (parte entera + fracción)
                $priceWhole = $xpath->query('.//span[@class="a-price-whole"]', $node)->item(0);
                $priceFraction = $xpath->query('.//span[@class="a-price-fraction"]', $node)->item(0);

                $price = 0;
                if ($priceWhole) {
                    $pString = str_replace([',', '.'], '', $priceWhole->textContent); // Limpiar miles
                    $frac = $priceFraction ? $priceFraction->textContent : '00';
                    $price = floatval($pString . '.' . $frac);
                }

                // Imagen
                $imgNode = $xpath->query('.//img[@class="s-image"]', $node)->item(0);
                $image = $imgNode ? $imgNode->getAttribute('src') : '';

                // Rating
                $ratingNode = $xpath->query('.//span[contains(@aria-label, "estrellas")]', $node)->item(0); // ES especifico
                // Fallback para US
                if (!$ratingNode) $ratingNode = $xpath->query('.//span[contains(@aria-label, "stars")]', $node)->item(0);

                $rating = 0;
                if ($ratingNode) {
                    $ratingHeader = $ratingNode->getAttribute('aria-label');
                    $rating = floatval(substr($ratingHeader, 0, 3));
                }

                if (!empty($title)) {
                    $products[] = [
                        'asin' => $asin,
                        'asin_name' => $title,
                        'asin_price' => $price,
                        'asin_currency' => 'EUR', // Asumimos EUR para ES
                        'image_url' => $image,
                        'rating' => $rating,
                        'total_review' => 0, // Dificil de parsear consistentemente
                        'in_stock' => true
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $products;
    }

    private function parseProductPage(string $html, string $asin): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        // Titulo
        $titleNode = $xpath->query('//span[@id="productTitle"]')->item(0);
        $title = $titleNode ? trim($titleNode->textContent) : '';

        // Precio
        $price = 0;
        $priceNode = $xpath->query('//span[@class="a-price-whole"]')->item(0);
        if ($priceNode) {
            $pString = str_replace([',', '.'], '', $priceNode->textContent);
            $fracNode = $xpath->query('//span[@class="a-price-fraction"]')->item(0);
            $frac = $fracNode ? $fracNode->textContent : '00';
            $price = floatval($pString . '.' . $frac);
        }

        // Imagen (LandingImage)
        $imgNode = $xpath->query('//img[@id="landingImage"]')->item(0);
        $image = $imgNode ? $imgNode->getAttribute('src') : '';

        // Descripcion
        $descNode = $xpath->query('//div[@id="feature-bullets"]')->item(0);
        $description = $descNode ? trim($descNode->textContent) : '';

        return [
            'asin' => $asin,
            'asin_name' => $title,
            'asin_price' => $price,
            'asin_currency' => 'EUR',
            'image_url' => $image,
            'asin_images' => [$image],
            'asin_informations' => $description,
            'in_stock' => true
        ];
    }
}
