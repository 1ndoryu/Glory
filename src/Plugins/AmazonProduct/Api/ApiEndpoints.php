<?php

namespace Glory\Plugins\AmazonProduct\Api;

use Glory\Plugins\AmazonProduct\Mode\PluginMode;
use Glory\Plugins\AmazonProduct\Service\LicenseService;
use Glory\Plugins\AmazonProduct\Service\UsageController;
use Glory\Plugins\AmazonProduct\Service\WebScraperProvider;
use Glory\Plugins\AmazonProduct\Service\ProxyDiagnostic;
use Glory\Plugins\AmazonProduct\Model\License;
use Glory\Core\GloryLogger;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Endpoints de la API REST para el servidor SaaS.
 * 
 * Solo se registran en modo SERVIDOR.
 * 
 * Endpoints:
 * - POST /glory/v1/amazon/search - Buscar productos
 * - POST /glory/v1/amazon/product/{asin} - Obtener producto por ASIN
 * - GET /glory/v1/amazon/license/status - Estado de licencia
 * - POST /glory/v1/amazon/stripe-webhook - Webhook de Stripe
 */
class ApiEndpoints
{
    private const NAMESPACE = 'glory/v1';
    private const ROUTE_PREFIX = '/amazon';

    /**
     * Registra los endpoints. Llamar en rest_api_init.
     */
    public static function register(): void
    {
        if (!PluginMode::isServer()) {
            return;
        }

        /*
         * Buscar productos
         */
        register_rest_route(self::NAMESPACE, self::ROUTE_PREFIX . '/search', [
            'methods' => 'POST',
            'callback' => [self::class, 'handleSearch'],
            'permission_callback' => [self::class, 'validateApiKey'],
        ]);

        /*
         * Obtener producto por ASIN
         */
        register_rest_route(self::NAMESPACE, self::ROUTE_PREFIX . '/product/(?P<asin>[A-Z0-9]+)', [
            'methods' => 'POST',
            'callback' => [self::class, 'handleProduct'],
            'permission_callback' => [self::class, 'validateApiKey'],
        ]);

        /*
         * Estado de licencia
         */
        register_rest_route(self::NAMESPACE, self::ROUTE_PREFIX . '/license/status', [
            'methods' => 'GET',
            'callback' => [self::class, 'handleLicenseStatus'],
            'permission_callback' => [self::class, 'validateApiKey'],
        ]);

        /*
         * Webhook de Stripe (no requiere API Key, usa firma)
         */
        register_rest_route(self::NAMESPACE, self::ROUTE_PREFIX . '/stripe-webhook', [
            'methods' => 'POST',
            'callback' => [self::class, 'handleStripeWebhook'],
            'permission_callback' => '__return_true',
        ]);

        /*
         * Diagnostico de Proxy (solo admin, para verificar IPs de salida)
         */
        register_rest_route(self::NAMESPACE, self::ROUTE_PREFIX . '/proxy-diagnostic', [
            'methods' => 'GET',
            'callback' => [self::class, 'handleProxyDiagnostic'],
            'permission_callback' => function () {
                /* 
                 * Solo permitir si viene de localhost o tiene parametro secreto
                 */
                $secret = $_GET['secret'] ?? '';
                $expectedSecret = defined('GLORY_DIAGNOSTIC_SECRET')
                    ? GLORY_DIAGNOSTIC_SECRET
                    : 'glory-diag-2024';
                return $secret === $expectedSecret;
            },
        ]);

        /*
         * Diagnostico de Email (para probar que wp_mail funciona)
         * GET /glory/v1/amazon/email-test?secret=xxx&to=email@ejemplo.com
         */
        register_rest_route(self::NAMESPACE, self::ROUTE_PREFIX . '/email-test', [
            'methods' => 'GET',
            'callback' => [self::class, 'handleEmailTest'],
            'permission_callback' => function () {
                $secret = $_GET['secret'] ?? '';
                $expectedSecret = defined('GLORY_DIAGNOSTIC_SECRET')
                    ? GLORY_DIAGNOSTIC_SECRET
                    : 'glory-diag-2024';
                return $secret === $expectedSecret;
            },
        ]);
    }

    /**
     * Valida la API Key del header.
     * 
     * @return bool|WP_Error
     */
    public static function validateApiKey(WP_REST_Request $request)
    {
        $apiKey = $request->get_header('X-API-Key');

        if (empty($apiKey)) {
            return new WP_Error(
                'missing_api_key',
                'API Key requerida',
                ['status' => 401]
            );
        }

        $license = LicenseService::findByApiKey($apiKey);

        if (!$license) {
            return new WP_Error(
                'invalid_api_key',
                'API Key no valida',
                ['status' => 401]
            );
        }

        /*
         * Verificar si puede hacer request (licencia valida + GB + rate limit)
         */
        $check = UsageController::checkRequest($license);

        if (!$check['allowed']) {
            return new WP_Error(
                $check['reason'],
                $check['message'],
                ['status' => 403]
            );
        }

        /*
         * Guardar licencia en request para usar despues
         */
        $request->set_param('_license', $license);

        return true;
    }

    /**
     * Buscar productos.
     * 
     * POST /glory/v1/amazon/search
     * Body: { keyword, page?, region? }
     */
    public static function handleSearch(WP_REST_Request $request): WP_REST_Response
    {
        $license = $request->get_param('_license');
        $keyword = sanitize_text_field($request->get_param('keyword') ?? '');
        $page = (int) ($request->get_param('page') ?? 1);
        $region = sanitize_text_field($request->get_param('region') ?? 'es');

        if (empty($keyword)) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Palabra clave requerida'
            ], 400);
        }

        $startTime = microtime(true);

        try {
            /*
             * Verificar cache global primero
             */
            $cacheKey = 'glory_api_search_' . md5($keyword . $page . $region);
            $cached = get_transient($cacheKey);

            if ($cached !== false) {
                /*
                 * Usar cache (consumo minimo: solo overhead de response)
                 */
                $bytesUsed = strlen(json_encode($cached));
                UsageController::recordRequest($license, $bytesUsed, 'search_cached', [
                    'keyword' => $keyword,
                    'page' => $page,
                    'cached' => true
                ]);

                return self::buildSuccessResponse($cached, $license, $bytesUsed);
            }

            /*
             * Hacer scraping real
             */
            $scraper = new WebScraperProvider();
            $products = $scraper->searchProducts($keyword, $page);

            /*
             * Calcular bytes (aproximado: tamaño del HTML scrapeado)
             * Usamos tamaño de resultado * 1.5 como estimacion
             */
            $resultJson = json_encode($products);
            $bytesUsed = (int) (strlen($resultJson) * 1.5);

            /*
             * Guardar en cache global (1 hora)
             */
            set_transient($cacheKey, $products, HOUR_IN_SECONDS);

            /*
             * Registrar uso
             */
            UsageController::recordRequest($license, $bytesUsed, 'search', [
                'keyword' => $keyword,
                'page' => $page,
                'results' => count($products),
                'time_ms' => round((microtime(true) - $startTime) * 1000)
            ]);

            return self::buildSuccessResponse($products, $license, $bytesUsed);
        } catch (\Throwable $e) {
            GloryLogger::error("API Search Error: " . $e->getMessage());

            return new WP_REST_Response([
                'success' => false,
                'error' => 'Error al buscar productos'
            ], 500);
        }
    }

    /**
     * Obtener producto por ASIN.
     * 
     * POST /glory/v1/amazon/product/{asin}
     */
    public static function handleProduct(WP_REST_Request $request): WP_REST_Response
    {
        $license = $request->get_param('_license');
        $asin = strtoupper(sanitize_text_field($request->get_param('asin') ?? ''));
        $region = sanitize_text_field($request->get_param('region') ?? 'es');

        if (empty($asin) || strlen($asin) !== 10) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'ASIN invalido'
            ], 400);
        }

        $startTime = microtime(true);

        try {
            /*
             * Verificar cache global
             */
            $cacheKey = 'glory_api_product_' . $asin . '_' . $region;
            $cached = get_transient($cacheKey);

            if ($cached !== false) {
                $bytesUsed = strlen(json_encode($cached));
                UsageController::recordRequest($license, $bytesUsed, 'product_cached', [
                    'asin' => $asin,
                    'cached' => true
                ]);

                return self::buildSuccessResponse($cached, $license, $bytesUsed);
            }

            /*
             * Hacer scraping real
             */
            $scraper = new WebScraperProvider();
            $product = $scraper->getProductByAsin($asin);

            if (empty($product)) {
                return new WP_REST_Response([
                    'success' => false,
                    'error' => 'Producto no encontrado'
                ], 404);
            }

            $resultJson = json_encode($product);
            $bytesUsed = (int) (strlen($resultJson) * 1.5);

            /*
             * Cache por producto (2 horas)
             */
            set_transient($cacheKey, $product, 2 * HOUR_IN_SECONDS);

            UsageController::recordRequest($license, $bytesUsed, 'product', [
                'asin' => $asin,
                'time_ms' => round((microtime(true) - $startTime) * 1000)
            ]);

            return self::buildSuccessResponse($product, $license, $bytesUsed);
        } catch (\Throwable $e) {
            GloryLogger::error("API Product Error: " . $e->getMessage());

            return new WP_REST_Response([
                'success' => false,
                'error' => 'Error al obtener producto'
            ], 500);
        }
    }

    /**
     * Estado de licencia.
     * 
     * GET /glory/v1/amazon/license/status
     */
    public static function handleLicenseStatus(WP_REST_Request $request): WP_REST_Response
    {
        $license = $request->get_param('_license');

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'status' => $license->getStatus(),
                'email' => $license->getEmail(),
                'gb_used' => round($license->getGbUsed(), 3),
                'gb_limit' => $license->getGbLimit(),
                'gb_remaining' => round($license->getGbRemaining(), 3),
                'usage_percent' => round($license->getUsagePercentage(), 1),
                'expires_at' => $license->getExpiresAt(),
                'expires_at_formatted' => $license->getExpiresAt() > 0
                    ? date('Y-m-d', $license->getExpiresAt())
                    : null,
                'is_near_limit' => $license->isNearLimit(),
            ]
        ], 200);
    }

    /**
     * Webhook de Stripe.
     * 
     * POST /glory/v1/amazon/stripe-webhook
     */
    public static function handleStripeWebhook(WP_REST_Request $request): WP_REST_Response
    {
        /*
         * Delegamos al handler de Stripe
         */
        $handler = new StripeWebhookHandler();
        return $handler->handle($request);
    }

    /**
     * Construye response exitosa con info de uso.
     */
    private static function buildSuccessResponse($data, License $license, int $bytesUsed): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
            'usage' => [
                'bytes_this_request' => $bytesUsed,
                'gb_used' => round($license->getGbUsed(), 3),
                'gb_remaining' => round($license->getGbRemaining(), 3),
                'gb_limit' => $license->getGbLimit(),
            ]
        ], 200);
    }

    /**
     * Diagnostico de Proxy.
     * 
     * GET /glory/v1/amazon/proxy-diagnostic?secret=xxx
     * 
     * Verifica la IP de salida real del proxy usando servicios externos.
     */
    public static function handleProxyDiagnostic(WP_REST_Request $request): WP_REST_Response
    {
        $results = ProxyDiagnostic::run();

        return new WP_REST_Response([
            'success' => true,
            'diagnostic' => $results
        ], 200);
    }

    /**
     * Diagnostico de Email.
     * 
     * GET /glory/v1/amazon/email-test?secret=xxx&to=email@ejemplo.com
     * 
     * Prueba que wp_mail() funciona correctamente en el servidor.
     */
    public static function handleEmailTest(WP_REST_Request $request): WP_REST_Response
    {
        $toEmail = sanitize_email($_GET['to'] ?? '');

        if (empty($toEmail) || !is_email($toEmail)) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Parametro "to" requerido con email valido. Ejemplo: ?secret=xxx&to=tu@email.com'
            ], 400);
        }

        $subject = 'Prueba de Email - Glory Amazon Plugin';
        $timestamp = date('Y-m-d H:i:s');
        
        $message = "
Hola!

Este es un email de prueba enviado desde el servidor Glory Amazon Plugin.

Fecha y hora: {$timestamp}
Servidor: " . home_url() . "

Si recibes este mensaje, el sistema de emails esta funcionando correctamente.

---
Glory Amazon Plugin - Test automatico
        ";

        /* 
         * Headers opcionales para mejor entrega
         */
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'From: Glory Plugin <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        ];

        /*
         * Intentar enviar
         */
        $result = wp_mail($toEmail, $subject, $message, $headers);

        /*
         * Obtener ultimo error de phpmailer si fallo
         */
        $error = '';
        if (!$result) {
            global $phpmailer;
            if (isset($phpmailer) && $phpmailer instanceof \PHPMailer\PHPMailer\PHPMailer) {
                $error = $phpmailer->ErrorInfo;
            }
        }

        /*
         * Info adicional del servidor
         */
        $serverInfo = [
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'mail_function_exists' => function_exists('mail'),
            'home_url' => home_url(),
            'admin_email' => get_option('admin_email'),
        ];

        GloryLogger::info("Email test enviado a {$toEmail}: " . ($result ? 'OK' : 'FAILED - ' . $error));

        return new WP_REST_Response([
            'success' => $result,
            'message' => $result 
                ? "Email enviado correctamente a {$toEmail}. Revisa tu bandeja de entrada (y spam)."
                : "Error al enviar email: " . ($error ?: 'Error desconocido'),
            'details' => [
                'to' => $toEmail,
                'subject' => $subject,
                'timestamp' => $timestamp,
                'wp_mail_result' => $result,
                'error' => $error ?: null,
            ],
            'server_info' => $serverInfo
        ], $result ? 200 : 500);
    }
}
