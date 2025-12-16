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

    private ?int $lastCacheTime = null;

    public function searchProducts(string $keyword, int $page = 1, bool $forceRefresh = false): array
    {
        $cacheKey = 'amazon_scraper_search_' . md5($keyword . $page . $this->region);

        if ($forceRefresh) {
            delete_transient($cacheKey);
        }

        $cached = get_transient($cacheKey);

        if ($cached !== false) {
            // No podemos saber el timestamp exacto de creacion del transient nativo de WP facilmente
            // Asumimos "hace poco" o podriamos guardar un transient paralelo con el time.
            // Para simplicidad, si viene de cache, marcamos que existe.
            // Una mejora seria guardar array('data' => ..., 'time' => time()) en el transient.
            if (isset($cached['data']) && isset($cached['time'])) {
                $this->lastCacheTime = $cached['time'];
                return $cached['data'];
            }
            // Fallback para caches viejas que no tenian estructura
            $this->lastCacheTime = time() - (HOUR_IN_SECONDS / 2); // Fake time
            return $cached;
        }

        $this->lastCacheTime = null; // Live

        $domain = $this->getDomain();
        $url = "https://www.{$domain}/s?k=" . urlencode($keyword) . "&page={$page}";

        $html = $this->fetchUrl($url);
        if (empty($html)) {
            return [];
        }

        $results = $this->parseSearchResults($html);

        if (!empty($results)) {
            // Guardamos wrapper con timestamp
            set_transient($cacheKey, ['data' => $results, 'time' => time()], HOUR_IN_SECONDS);
        }

        return $results;
    }

    public function getLastCacheTime(): ?int
    {
        return $this->lastCacheTime;
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

    /**
     * Obtiene el codigo de pais ISO para el proxy basado en la region de Amazon.
     * 
     * Es importante que la IP del proxy sea del mismo pais que el dominio de Amazon
     * para evitar deteccion y bloqueos.
     */
    private function getProxyCountryCode(): string
    {
        $countryCodes = [
            'us' => 'us',
            'es' => 'es',
            'uk' => 'gb',
            'de' => 'de',
            'fr' => 'fr',
            'it' => 'it',
        ];

        return $countryCodes[$this->region] ?? 'es';
    }

    /**
     * Numero maximo de reintentos para requests fallidos
     */
    private const MAX_RETRIES = 5;

    /**
     * Delay base en milisegundos entre requests
     */
    private const BASE_DELAY_MS = 2000;

    /**
     * Realiza una peticion HTTP con reintentos y delays para evitar bloqueos.
     * 
     * Caracteristicas de resiliencia:
     * - Delay aleatorio antes de cada request (2-5 segundos)
     * - Retry automatico con backoff exponencial (hasta 5 intentos)
     * - Deteccion de CAPTCHA y bloqueos
     * - Rotacion de User-Agents
     * - Logging detallado del proxy para diagnostico
     * 
     * @param string $url URL a obtener
     * @param int $attempt Numero de intento actual (para recursion)
     * @return string HTML de la pagina o vacio si falla
     */
    private function fetchUrl(string $url, int $attempt = 1): string
    {
        $requestStartTime = microtime(true);

        /*
         * Delay aleatorio antes del request para simular comportamiento humano.
         * Rango: 2 a 5 segundos
         */
        $delayMs = self::BASE_DELAY_MS + random_int(0, 3000);
        usleep($delayMs * 1000);

        $ch = curl_init();
        $ua = $this->userAgents[array_rand($this->userAgents)];

        /*
         * Proxy Configuration
         * Prioridad: constantes wp-config.php > get_option
         */
        $proxy = defined('GLORY_PROXY_HOST')
            ? GLORY_PROXY_HOST
            : get_option('amazon_scraper_proxy', '');

        $proxyAuth = defined('GLORY_PROXY_AUTH')
            ? GLORY_PROXY_AUTH
            : get_option('amazon_scraper_proxy_auth', '');

        /*
         * Configuracion de parámetros del proxy DataImpulse.
         * 
         * SOLUCION para forzar rotacion de IP:
         * Usar un sessid UNICO por cada request. DataImpulse asigna una nueva IP
         * a cada sessid diferente. Al generar un UUID aleatorio por request,
         * garantizamos que obtendremos una IP diferente cada vez.
         * 
         * Esto funciona incluso si el dashboard tiene configurado un rotation interval.
         * 
         * Ref: https://docs.dataimpulse.com/proxies/types-of-connections
         */
        $countryCode = $this->getProxyCountryCode();
        $sessionId = bin2hex(random_bytes(8)); // Unico por request

        if (!empty($proxyAuth) && strpos($proxyAuth, ':') !== false) {
            [$proxyUser, $proxyPass] = explode(':', $proxyAuth, 2);

            /*
             * Formato: usuario__cr.XX;sessid.UNIQUE:password
             * - cr.XX: Geo-targeting (IP del pais)
             * - sessid.UNIQUE: Fuerza una nueva IP porque es un ID nuevo
             */
            $proxyAuth = "{$proxyUser}__cr.{$countryCode};sessid.{$sessionId}:{$proxyPass}";
        }

        /* Solo advertir si el proxy no está configurado */
        if (empty($proxy) && $attempt === 1) {
            GloryLogger::warning("Scraper: Proxy NO configurado - Usando IP directa");
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => $ua,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
                'Upgrade-Insecure-Requests: 1',
                'Cache-Control: max-age=0',
                'Referer: https://www.google.es/',
                'sec-ch-ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'sec-fetch-dest: document',
                'sec-fetch-mode: navigate',
                'sec-fetch-site: none',
                'sec-fetch-user: ?1'
            ],
            CURLOPT_COOKIEFILE => '',
            CURLOPT_COOKIEJAR => '',
        ];

        /*
         * Configurar proxy si esta disponible.
         * 
         * IMPORTANTE: Para garantizar rotacion de IP con el puerto 823:
         * - CURLOPT_FRESH_CONNECT: Fuerza una nueva conexion TCP
         * - CURLOPT_FORBID_REUSE: Impide que CURL reutilice la conexion
         * 
         * Sin estas opciones, CURL puede reusar conexiones persistentes
         * y el proxy mantendria la misma IP.
         */
        if (!empty($proxy)) {
            $options[CURLOPT_PROXY] = $proxy;
            $options[CURLOPT_FRESH_CONNECT] = true;
            $options[CURLOPT_FORBID_REUSE] = true;

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
        $curlError = curl_error($ch);
        $primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);

        $elapsedMs = round((microtime(true) - $requestStartTime) * 1000);
        $responseSize = strlen($response);
        $responseSizeKb = round($responseSize / 1024, 1);

        /* Log solo si hay problemas (error o bloqueo) - removido log normal para produccion */

        /*
         * Verificar si fue bloqueado o hay CAPTCHA
         */
        $blockStatus = $this->detectBlockOrCaptcha($response, $httpCode);

        if ($blockStatus !== false) {
            GloryLogger::warning("Scraper: Bloqueo detectado - {$blockStatus} (intento {$attempt}/" . self::MAX_RETRIES . ")");

            /*
             * Retry rapido (2-4 segundos)
             * El proxy rota IP automaticamente, no necesitamos backoff exponencial
             */
            if ($attempt < self::MAX_RETRIES) {
                $waitSeconds = random_int(2, 4);
                sleep($waitSeconds);
                return $this->fetchUrl($url, $attempt + 1);
            }

            GloryLogger::error("Scraper: FALLO - Maximo de reintentos ({self::MAX_RETRIES}) alcanzado para: {$url}");
            return '';
        }

        /*
         * Verificar errores de CURL o HTTP
         */
        if (!empty($curlError) || $httpCode !== 200) {
            GloryLogger::error("Scraper Error (HTTP {$httpCode}): {$curlError} | IP: {$primaryIp} | URL: {$url}");

            /*
             * Retry para errores de conexion o servidor
             */
            if ($attempt < self::MAX_RETRIES && ($httpCode >= 500 || $httpCode === 0)) {
                $waitSeconds = random_int(2, 4);
                sleep($waitSeconds);
                return $this->fetchUrl($url, $attempt + 1);
            }

            return '';
        }

        /*
         * Request exitoso
         */
        GloryLogger::info("Scraper: OK - {$responseSizeKb}KB en {$elapsedMs}ms" . (!empty($proxy) ? " (via proxy)" : " (IP directa)"));

        return $response;
    }

    /**
     * Detecta si Amazon ha bloqueado la peticion o mostrado CAPTCHA.
     * 
     * @param string $html Respuesta HTML
     * @param int $httpCode Codigo HTTP
     * @return string|false Tipo de bloqueo detectado o false si no hay bloqueo
     */
    private function detectBlockOrCaptcha(string $html, int $httpCode): string|false
    {
        // Codigos HTTP de bloqueo
        if ($httpCode === 503 || $httpCode === 429) {
            return "HTTP {$httpCode} - Rate Limited";
        }

        // CAPTCHA de Amazon
        if (stripos($html, 'captcha') !== false || stripos($html, 'robot') !== false) {
            if (preg_match('/captcha|robot check|automated access|unusual traffic/i', $html)) {
                return 'CAPTCHA detectado';
            }
        }

        // Pagina vacia o muy corta (menos de 5KB probablemente no es una pagina real)
        if (strlen($html) < 5000 && $httpCode === 200) {
            // Verificar si es una pagina de error de Amazon
            if (stripos($html, 'something went wrong') !== false) {
                return 'Pagina de error de Amazon';
            }
        }

        return false;
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
                // Prioridad: H2 dentro de un link (evita marcas como headings)
                $titleNode = $xpath->query('.//a//h2//span', $node)->item(0);
                if (!$titleNode) {
                    // Fallback
                    $titleNode = $xpath->query('.//h2//span', $node)->item(0);
                }
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
                $rating = 0;
                // Intento 1: aria-label en span (Estandar antiguo)
                $ratingNode = $xpath->query('.//span[contains(@aria-label, "estrellas") or contains(@aria-label, "stars")]', $node)->item(0);

                // Intento 2: a-icon-alt text content (Nuevo layout)
                if (!$ratingNode) {
                    $ratingNode = $xpath->query('.//span[contains(@class, "a-icon-alt")]', $node)->item(0);
                }

                if ($ratingNode) {
                    // Si tiene aria-label, usalo
                    $ratingText = $ratingNode->getAttribute('aria-label');
                    // Si no, usa el contenido de texto (a-icon-alt)
                    if (empty($ratingText)) {
                        $ratingText = $ratingNode->textContent;
                    }

                    // Extraer numero (ej: "4,6 de 5 estrellas" -> 4.6)
                    if (preg_match('/([0-9.,]+)/', $ratingText, $matches)) {
                        $rating = floatval(str_replace(',', '.', $matches[1]));
                    }
                }

                // Reviews
                // Intentamos buscar el numero de reviews (suele estar en un spam con clase s-underline-text)
                // Relaxed selector: buscar cualquier span con s-underline-text, removiendo constraint de size
                $reviewsNode = $xpath->query('.//span[contains(@class, "s-underline-text")]', $node)->item(0);
                $totalReview = 0;
                if ($reviewsNode) {
                    // Limpiamos todo lo que no sea numeros
                    $totalReview = (int) preg_replace('/[^0-9]/', '', $reviewsNode->textContent);
                }

                if (!empty($title)) {
                    $products[] = [
                        'asin' => $asin,
                        'asin_name' => $title,
                        'asin_price' => $price,
                        'asin_currency' => 'EUR', // Asumimos EUR para ES
                        'image_url' => $image,
                        'rating' => $rating,
                        'total_review' => $totalReview,
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

        // Precio actual - Usar metodo mejorado
        $price = $this->extractPriceFromHtml($html);

        // Precio original (tachado) - Para ofertas
        $originalPrice = $this->extractOriginalPriceFromHtml($html);

        // Calcular descuento si hay precio original
        $discountPercent = 0;
        if ($originalPrice > $price && $price > 0) {
            $discountPercent = round((($originalPrice - $price) / $originalPrice) * 100);
        }

        // Imagen (LandingImage)
        $imgNode = $xpath->query('//img[@id="landingImage"]')->item(0);
        $image = $imgNode ? $imgNode->getAttribute('src') : '';

        // Fallback: data-a-dynamic-image
        if (empty($image) && preg_match('/data-a-dynamic-image="([^"]+)"/', $html, $imgMatches)) {
            $jsonStr = html_entity_decode($imgMatches[1], ENT_QUOTES, 'UTF-8');
            $imgData = json_decode($jsonStr, true);
            if (is_array($imgData)) {
                $image = array_key_first($imgData) ?? '';
            }
        }

        // Descripcion
        $descNode = $xpath->query('//div[@id="feature-bullets"]')->item(0);
        $description = $descNode ? trim($descNode->textContent) : '';

        // Rating mejorado
        $rating = $this->extractRatingFromHtml($html, $xpath);

        // Reviews mejorado
        $totalReview = $this->extractReviewsFromHtml($html, $xpath);

        // Prime detection
        $isPrime = $this->extractPrimeFromHtml($html);

        // Categoria
        $category = $this->extractCategoryFromHtml($html, $xpath);

        return [
            'asin' => $asin,
            'asin_name' => $title,
            'asin_price' => $price,
            'asin_original_price' => $originalPrice,
            'asin_list_price' => $originalPrice,
            'discount_percent' => $discountPercent,
            'asin_currency' => 'EUR',
            'image_url' => $image,
            'asin_images' => [$image],
            'asin_informations' => $description,
            'rating' => $rating,
            'total_review' => $totalReview,
            'reviews' => $totalReview,
            'total_start' => $rating,
            'is_prime' => $isPrime,
            'category_path' => $category,
            'in_stock' => true
        ];
    }

    /**
     * Extrae el precio actual del HTML usando multiples estrategias.
     * Compatible con formato europeo (1.234,56) y americano (1,234.56)
     */
    private function extractPriceFromHtml(string $html): float
    {
        /*
         * Estrategia 1: CorePrice (precio principal de Amazon)
         * Buscar dentro del contenedor de precio principal
         */
        if (preg_match('/id="corePrice[^"]*"[^>]*>.*?<span class="a-price-whole">([^<]+)/s', $html, $matches)) {
            return $this->parseEuropeanPrice($matches[1], $html);
        }

        /*
         * Estrategia 2: a-price-whole + a-price-fraction (estructura estandar)
         */
        if (preg_match('/<span class="a-price-whole">([^<]+)/', $html, $whole)) {
            $priceWhole = preg_replace('/[^0-9]/', '', $whole[1]);
            $priceFraction = '00';
            if (preg_match('/<span class="a-price-fraction">([0-9]+)/', $html, $fraction)) {
                $priceFraction = $fraction[1];
            }
            return floatval($priceWhole . '.' . $priceFraction);
        }

        /*
         * Estrategia 3: Precio en euros con formato europeo (64,99 o 1.234,56)
         */
        if (preg_match('/([0-9.]+,[0-9]{2})\s*\\x{20AC}/u', $html, $matches)) {
            $price = str_replace('.', '', $matches[1]);
            $price = str_replace(',', '.', $price);
            return floatval($price);
        }

        /*
         * Estrategia 4: Formato americano ($99.95)
         */
        if (preg_match('/(?:US)?\$\s*([0-9,]+(?:\.[0-9]{2})?)/', $html, $matches)) {
            return floatval(str_replace(',', '', $matches[1]));
        }

        return 0.00;
    }

    /**
     * Extrae precio original (tachado) para detectar ofertas.
     */
    private function extractOriginalPriceFromHtml(string $html): float
    {
        /*
         * Estrategia 1: "Precio recomendado:" (Amazon.es)
         */
        if (preg_match('/Precio recomendado[:\s]*([0-9.]+,[0-9]{2})\s*\\x{20AC}/u', $html, $matches)) {
            $price = str_replace('.', '', $matches[1]);
            $price = str_replace(',', '.', $price);
            return floatval($price);
        }

        /*
         * Estrategia 2: basisPrice con a-offscreen (precio base)
         */
        if (preg_match('/basisPrice.*?<span[^>]*class="a-offscreen"[^>]*>([0-9.]+,[0-9]{2})\s*\\x{20AC}/su', $html, $matches)) {
            $price = str_replace('.', '', $matches[1]);
            $price = str_replace(',', '.', $price);
            return floatval($price);
        }

        /*
         * Estrategia 3: Precio tachado con data-a-strike
         */
        if (preg_match('/<span[^>]*data-a-strike="true"[^>]*>.*?([0-9.]+,[0-9]{2})\s*\\x{20AC}/su', $html, $matches)) {
            $price = str_replace('.', '', $matches[1]);
            $price = str_replace(',', '.', $price);
            return floatval($price);
        }

        /*
         * Estrategia 4: a-text-strike class (precio tachado generico)
         */
        if (preg_match('/<span[^>]*class="[^"]*a-text-strike[^"]*"[^>]*>\s*\$?\s*([0-9,]+(?:\.[0-9]{2})?)/s', $html, $matches)) {
            return floatval(str_replace(',', '', $matches[1]));
        }

        /*
         * Estrategia 5: "Was:" o "List Price:" (Amazon.com)
         */
        if (preg_match('/(?:Was|List Price)[:\s]*\$?\s*([0-9,]+\.[0-9]{2})/i', $html, $matches)) {
            return floatval(str_replace(',', '', $matches[1]));
        }

        return 0.00;
    }

    /**
     * Parsea un precio en formato europeo
     */
    private function parseEuropeanPrice(string $priceWhole, string $html): float
    {
        $priceWhole = preg_replace('/[^0-9]/', '', $priceWhole);
        $priceFraction = '00';
        if (preg_match('/<span class="a-price-fraction">([0-9]+)/', $html, $fraction)) {
            $priceFraction = $fraction[1];
        }
        return floatval($priceWhole . '.' . $priceFraction);
    }

    /**
     * Extrae rating mejorado
     */
    private function extractRatingFromHtml(string $html, \DOMXPath $xpath): float
    {
        // Intento 1: acrPopover title
        $ratingNode = $xpath->query('//span[@id="acrPopover"]')->item(0);
        if ($ratingNode) {
            $ratingText = $ratingNode->getAttribute('title');
            if (preg_match('/([0-9.,]+)/', $ratingText, $matches)) {
                return floatval(str_replace(',', '.', $matches[1]));
            }
        }

        // Intento 2: Patron espanol "4,6 de 5 estrellas"
        if (preg_match('/([0-9]+[.,][0-9]+)\s*de\s*5\s*estrellas/i', $html, $matches)) {
            return floatval(str_replace(',', '.', $matches[1]));
        }

        // Intento 3: Patron ingles
        if (preg_match('/([0-9]+[.,][0-9]+)\s*out of\s*5\s*stars/i', $html, $matches)) {
            return floatval(str_replace(',', '.', $matches[1]));
        }

        // Intento 4: a-icon-alt
        if (preg_match('/<span class="a-icon-alt">([0-9]+[.,][0-9]+)/', $html, $matches)) {
            return floatval(str_replace(',', '.', $matches[1]));
        }

        return 0.0;
    }

    /**
     * Extrae numero de reviews mejorado
     */
    private function extractReviewsFromHtml(string $html, \DOMXPath $xpath): int
    {
        // Intento 1: acrCustomerReviewText
        $reviewNode = $xpath->query('//span[@id="acrCustomerReviewText"]')->item(0);
        if ($reviewNode) {
            return (int) preg_replace('/[^0-9]/', '', $reviewNode->textContent);
        }

        // Intento 2: aria-label con numero de reviews
        if (preg_match('/aria-label="([0-9.,]+)\s*(?:Resenas|Reviews|valoraciones|ratings)"/i', $html, $matches)) {
            return (int) preg_replace('/[^0-9]/', '', $matches[1]);
        }

        // Intento 3: Texto tipo "115 valoraciones"
        if (preg_match('/([0-9,.]+)\s*(?:calificaciones|ratings|valoraciones|reviews|Resenas)/i', $html, $matches)) {
            return (int) preg_replace('/[^0-9]/', '', $matches[1]);
        }

        return 0;
    }

    /**
     * Detecta si el producto es Prime
     */
    private function extractPrimeFromHtml(string $html): bool
    {
        if (preg_match('/i-prime|a-icon-prime|prime-icon|FREE.*delivery|Envio GRATIS/i', $html)) {
            return true;
        }
        if (preg_match('/data-[^=]*prime[^=]*="true"/i', $html)) {
            return true;
        }
        if (preg_match('/alt="[^"]*Prime[^"]*"/', $html)) {
            return true;
        }
        return false;
    }

    /**
     * Extrae la categoria del producto
     */
    private function extractCategoryFromHtml(string $html, \DOMXPath $xpath): string
    {
        // Intento 1: wayfinding-breadcrumbs (layout clasico)
        $breadcrumb = $xpath->query('//div[@id="wayfinding-breadcrumbs_feature_div"]//a')->item(0);
        if ($breadcrumb) {
            $categories = [];
            $links = $xpath->query('//div[@id="wayfinding-breadcrumbs_feature_div"]//a');
            foreach ($links as $link) {
                $text = trim($link->textContent);
                if (!empty($text) && strlen($text) > 1) {
                    $categories[] = $text;
                }
            }
            if (!empty($categories)) {
                $result = implode(' > ', $categories);
                GloryLogger::info("Scraper Category: Encontrada via wayfinding-breadcrumbs: {$result}");
                return $result;
            }
        }

        // Intento 2: nav-subnav (barra de navegacion superior)
        $navSubnav = $xpath->query('//div[@id="nav-subnav"]/@data-category')->item(0);
        if ($navSubnav) {
            $category = trim($navSubnav->nodeValue);
            if (!empty($category)) {
                GloryLogger::info("Scraper Category: Encontrada via nav-subnav: {$category}");
                return $category;
            }
        }

        // Intento 3: Buscar en dp-container o product-detail
        $dpCategory = $xpath->query('//a[contains(@class, "a-link-normal") and contains(@href, "/b/")]')->item(0);
        if ($dpCategory) {
            $category = trim($dpCategory->textContent);
            if (!empty($category) && strlen($category) > 2) {
                GloryLogger::info("Scraper Category: Encontrada via link /b/: {$category}");
                return $category;
            }
        }

        // Intento 4: Meta tag o schema.org
        if (preg_match('/"category"\s*:\s*"([^"]+)"/', $html, $matches)) {
            $category = $matches[1];
            GloryLogger::info("Scraper Category: Encontrada via JSON schema: {$category}");
            return $category;
        }

        GloryLogger::warning("Scraper Category: No se pudo extraer categoria del HTML");
        return '';
    }
}
