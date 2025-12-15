<?php

namespace Glory\Plugins\AmazonProduct\Service;

use Glory\Core\GloryLogger;
use Glory\Plugins\AmazonProduct\Mode\PluginMode;

/**
 * Amazon API Service - Fachada para acceder a la API de Amazon.
 * 
 * ARCH-01: Esta clase ahora actua como Factory/Fachada.
 * Selecciona automaticamente el provider correcto segun la configuracion.
 * 
 * MODO CLIENTE vs SERVIDOR:
 * - En modo SERVIDOR: Usa providers locales (scraper, RapidAPI, PA-API)
 * - En modo CLIENTE: Delega a ApiClient para usar la API remota
 * 
 * Providers disponibles (modo servidor):
 * - RapidApiProvider: amazon-data.p.rapidapi.com
 * - AmazonPaApiProvider: Amazon Product Advertising API 5.0
 * - WebScraperProvider: Scraper directo (default)
 * 
 * El provider se selecciona desde ConfigTab con la opcion 'amazon_api_provider'.
 * 
 * Mantiene compatibilidad hacia atras: el resto del plugin usa esta clase
 * sin saber que provider/modo esta activo internamente.
 */
class AmazonApiService
{
    private const PROVIDER_RAPIDAPI = 'rapidapi';
    private const PROVIDER_PAAPI = 'paapi';
    private const PROVIDER_SCRAPER = 'scraper';

    private ?ApiProviderInterface $provider = null;
    private ?ApiClient $apiClient = null;
    private string $providerType;
    private bool $isClientMode;

    public function __construct()
    {
        $this->isClientMode = PluginMode::isClient();

        if ($this->isClientMode) {
            $this->apiClient = new ApiClient();
            $this->providerType = 'remote_api';
        } else {
            $this->providerType = get_option('amazon_api_provider', self::PROVIDER_SCRAPER);
            $this->provider = $this->createProvider();
        }
    }

    /**
     * Factory method: crea el provider segun la configuracion.
     * 
     * @return ApiProviderInterface Provider configurado
     */
    private function createProvider(): ApiProviderInterface
    {
        switch ($this->providerType) {
            case self::PROVIDER_PAAPI:
                GloryLogger::info('AmazonApiService: Usando PA-API provider');
                return new AmazonPaApiProvider();

            case self::PROVIDER_RAPIDAPI:
                return new RapidApiProvider();

            case self::PROVIDER_SCRAPER:
            default:
                GloryLogger::info('AmazonApiService: Usando Web Scraper de Emergencia (Default)');
                return new WebScraperProvider();
        }
    }

    /**
     * Obtiene el provider activo.
     * 
     * @return ApiProviderInterface
     */
    public function getProvider(): ApiProviderInterface
    {
        return $this->provider;
    }

    /**
     * Obtiene el tipo de provider activo.
     * 
     * @return string 'rapidapi' o 'paapi'
     */
    public function getProviderType(): string
    {
        return $this->providerType;
    }

    /**
     * Verifica si el provider esta configurado correctamente.
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        if ($this->isClientMode) {
            return $this->apiClient->hasApiKey();
        }
        return $this->provider->isConfigured();
    }

    /**
     * Busca productos por palabra clave.
     * En modo cliente: delega a ApiClient.
     * En modo servidor: delega al provider local.
     * 
     * @param string $keyword Palabra clave
     * @param int $page Numero de pagina
     * @return array Lista de productos
     */
    public function searchProducts(string $keyword, int $page = 1, bool $forceRefresh = false): array
    {
        if ($this->isClientMode) {
            $result = $this->apiClient->searchProducts($keyword, $page);
            if (!$result['success']) {
                GloryLogger::error('ApiClient searchProducts error: ' . ($result['error'] ?? 'Unknown'));
                return [];
            }
            return $result['products'] ?? [];
        }

        if (method_exists($this->provider, 'searchProducts')) {
            return $this->provider->searchProducts($keyword, $page, $forceRefresh);
        }
        return $this->provider->searchProducts($keyword, $page);
    }

    public function getLastCacheTime(): ?int
    {
        if ($this->isClientMode) {
            return null;
        }
        if (method_exists($this->provider, 'getLastCacheTime')) {
            return $this->provider->getLastCacheTime();
        }
        return null;
    }

    /**
     * Obtiene un producto por ASIN.
     * En modo cliente: delega a ApiClient.
     * En modo servidor: delega al provider local.
     * 
     * @param string $asin ASIN del producto
     * @return array Datos del producto
     */
    public function getProductByAsin(string $asin): array
    {
        if ($this->isClientMode) {
            $result = $this->apiClient->getProductByAsin($asin);
            if (!$result['success']) {
                GloryLogger::error('ApiClient getProductByAsin error: ' . ($result['error'] ?? 'Unknown'));
                return [];
            }
            return $result['product'] ?? [];
        }

        return $this->provider->getProductByAsin($asin);
    }

    /**
     * Obtiene ofertas actuales.
     * Solo disponible en modo servidor.
     * 
     * @param int $page Numero de pagina
     * @return array Lista de ofertas
     */
    public function getDeals(int $page = 1): array
    {
        if ($this->isClientMode) {
            return [];
        }
        return $this->provider->getDeals($page);
    }

    /**
     * Obtiene la ultima informacion de uso (solo modo cliente).
     */
    public function getLastUsageInfo(): ?array
    {
        if ($this->isClientMode) {
            return $this->apiClient->getLastUsageInfo();
        }
        return null;
    }

    /**
     * Obtiene el dominio de Amazon para una region.
     * Metodo estatico para compatibilidad hacia atras.
     * 
     * @param string $region Codigo de region (us, es, uk, etc.)
     * @return string Dominio de Amazon
     */
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

    /**
     * Obtiene los providers disponibles para mostrar en admin.
     * 
     * @return array [slug => nombre]
     */
    public static function getAvailableProviders(): array
    {
        return [
            self::PROVIDER_RAPIDAPI => 'RapidAPI (amazon-data)',
            self::PROVIDER_PAAPI => 'Amazon PA-API 5.0 (oficial)',
            self::PROVIDER_SCRAPER => 'Web Scraper (Emergency Mode)',
        ];
    }

    /**
     * Verifica si un provider especifico esta disponible y configurado.
     * 
     * @param string $providerType Tipo de provider
     * @return array [available => bool, configured => bool, name => string]
     */
    public static function checkProviderStatus(string $providerType): array
    {
        $providers = self::getAvailableProviders();
        $name = $providers[$providerType] ?? 'Desconocido';

        switch ($providerType) {
            case self::PROVIDER_RAPIDAPI:
                $configured = !empty(get_option('amazon_api_key', ''));
                break;

            case self::PROVIDER_PAAPI:
                $configured = !empty(get_option('amazon_paapi_access_key', ''))
                    && !empty(get_option('amazon_paapi_secret_key', ''))
                    && !empty(get_option('amazon_affiliate_tag', ''));
                break;

            case self::PROVIDER_SCRAPER:
                $configured = true; // Siempre disponible, no requiere key
                break;

            default:
                $configured = false;
        }

        return [
            'available' => true,
            'configured' => $configured,
            'name' => $name
        ];
    }
}
