<?php

namespace Glory\Plugins\AmazonProduct\Service;

use Glory\Core\GloryLogger;

/**
 * Amazon API Service - Fachada para acceder a la API de Amazon.
 * 
 * ARCH-01: Esta clase ahora actua como Factory/Fachada.
 * Selecciona automaticamente el provider correcto segun la configuracion.
 * 
 * Providers disponibles:
 * - RapidApiProvider: amazon-data.p.rapidapi.com (default)
 * - AmazonPaApiProvider: Amazon Product Advertising API 5.0
 * 
 * El provider se selecciona desde ConfigTab con la opcion 'amazon_api_provider'.
 * 
 * Mantiene compatibilidad hacia atras: el resto del plugin usa esta clase
 * sin saber que provider esta activo internamente.
 */
class AmazonApiService
{
    private const PROVIDER_RAPIDAPI = 'rapidapi';
    private const PROVIDER_PAAPI = 'paapi';
    private const PROVIDER_SCRAPER = 'scraper';

    private ApiProviderInterface $provider;
    private string $providerType;

    public function __construct()
    {
        $this->providerType = get_option('amazon_api_provider', self::PROVIDER_RAPIDAPI);

        // AUTO-FIX: Si el usuario tiene RapidAPI seleccionado (o default) pero no ha puesto API Key,
        // forzamos el uso del Web Scraper automáticamente para que no vea errores.
        if ($this->providerType === self::PROVIDER_RAPIDAPI && empty(get_option('amazon_api_key'))) {
            GloryLogger::info('AmazonApiService: RapidAPI sin key detectada. Forzando Web Scraper.');
            $this->providerType = self::PROVIDER_SCRAPER;
        }

        $this->provider = $this->createProvider();
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
        return $this->provider->isConfigured();
    }

    /**
     * Busca productos por palabra clave.
     * Delega al provider activo.
     * 
     * @param string $keyword Palabra clave
     * @param int $page Numero de pagina
     * @return array Lista de productos
     */
    public function searchProducts(string $keyword, int $page = 1, bool $forceRefresh = false): array
    {
        if (method_exists($this->provider, 'searchProducts')) {
            // Check if method accepts 3rd argument (reflection or try catch?)
            // PHP allows passing extra args, but we should update interface optimally.
            // For now, let's assume implementation.
            return $this->provider->searchProducts($keyword, $page, $forceRefresh);
        }
        return $this->provider->searchProducts($keyword, $page);
    }

    public function getLastCacheTime(): ?int
    {
        if (method_exists($this->provider, 'getLastCacheTime')) {
            return $this->provider->getLastCacheTime();
        }
        return null;
    }

    /**
     * Obtiene un producto por ASIN.
     * Delega al provider activo.
     * 
     * @param string $asin ASIN del producto
     * @return array Datos del producto
     */
    public function getProductByAsin(string $asin): array
    {
        return $this->provider->getProductByAsin($asin);
    }

    /**
     * Obtiene ofertas actuales.
     * Delega al provider activo.
     * 
     * @param int $page Numero de pagina
     * @return array Lista de ofertas
     */
    public function getDeals(int $page = 1): array
    {
        return $this->provider->getDeals($page);
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
