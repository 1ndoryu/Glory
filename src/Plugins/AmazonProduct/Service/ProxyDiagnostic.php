<?php

namespace Glory\Plugins\AmazonProduct\Service;

/**
 * Diagnostico de Proxy - Verifica la IP de salida real
 * 
 * Este script hace requests a servicios externos que reportan la IP
 * desde la cual se conecta el cliente (la IP de salida del proxy).
 */
class ProxyDiagnostic
{
    /**
     * Ejecuta diagnostico completo del proxy
     * 
     * @return array Resultados del diagnostico
     */
    public static function run(): array
    {
        $results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tests' => []
        ];

        /* 
         * Obtener configuracion del proxy
         */
        $proxyHost = defined('GLORY_PROXY_HOST')
            ? GLORY_PROXY_HOST
            : get_option('amazon_scraper_proxy', '');

        $proxyAuth = defined('GLORY_PROXY_AUTH')
            ? GLORY_PROXY_AUTH
            : get_option('amazon_scraper_proxy_auth', '');

        $results['proxy_configured'] = !empty($proxyHost);
        $results['proxy_host'] = $proxyHost;

        if (empty($proxyHost)) {
            $results['error'] = 'Proxy no configurado';
            return $results;
        }

        /* 
         * Test 1: Sin proxy (IP real del servidor)
         */
        $results['tests']['sin_proxy'] = self::checkIp(null, null);

        /* 
         * Test 2: Con proxy (deberia mostrar IP residencial)
         */
        $results['tests']['con_proxy'] = self::checkIp($proxyHost, $proxyAuth);

        /* 
         * Test 3: Con proxy + country code ES
         */
        if (!empty($proxyAuth) && strpos($proxyAuth, ':') !== false) {
            [$user, $pass] = explode(':', $proxyAuth, 2);
            $sessionId = bin2hex(random_bytes(8));
            $authWithParams = "{$user}__cr.es;sessid.{$sessionId}:{$pass}";
            $results['tests']['con_proxy_es'] = self::checkIp($proxyHost, $authWithParams);
        }

        /* 
         * Comparar IPs
         */
        $ipSinProxy = $results['tests']['sin_proxy']['ip'] ?? 'error';
        $ipConProxy = $results['tests']['con_proxy']['ip'] ?? 'error';

        $results['proxy_working'] = ($ipSinProxy !== $ipConProxy && $ipConProxy !== 'error');

        if ($results['proxy_working']) {
            $results['conclusion'] = 'PROXY FUNCIONANDO - La IP de salida es diferente a la IP del servidor';
        } else {
            $results['conclusion'] = 'PROBLEMA - La IP no cambio o hubo error';
        }

        return $results;
    }

    /**
     * Verifica la IP de salida usando un servicio externo
     */
    private static function checkIp(?string $proxy, ?string $proxyAuth): array
    {
        $services = [
            'ipify' => 'https://api.ipify.org?format=json',
            'httpbin' => 'https://httpbin.org/ip',
        ];

        $result = [
            'ip' => null,
            'country' => null,
            'details' => []
        ];

        foreach ($services as $name => $url) {
            $ch = curl_init();

            $options = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FRESH_CONNECT => true,
                CURLOPT_FORBID_REUSE => true,
            ];

            if (!empty($proxy)) {
                $options[CURLOPT_PROXY] = $proxy;
                if (!empty($proxyAuth)) {
                    $options[CURLOPT_PROXYUSERPWD] = $proxyAuth;
                }
            }

            curl_setopt_array($ch, $options);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP);

            curl_close($ch);

            $result['details'][$name] = [
                'url' => $url,
                'http_code' => $httpCode,
                'curl_primary_ip' => $primaryIp,
                'error' => $curlError ?: null,
            ];

            if ($httpCode === 200 && !empty($response)) {
                $data = json_decode($response, true);

                if ($name === 'ipify' && isset($data['ip'])) {
                    $result['ip'] = $data['ip'];
                } elseif ($name === 'httpbin' && isset($data['origin'])) {
                    $result['ip'] = $data['origin'];
                }

                $result['details'][$name]['response'] = $data;
            }
        }

        /* 
         * Obtener info del pais si tenemos IP
         */
        if (!empty($result['ip'])) {
            $geoUrl = "http://ip-api.com/json/{$result['ip']}";
            $geoResponse = @file_get_contents($geoUrl);
            if ($geoResponse) {
                $geoData = json_decode($geoResponse, true);
                $result['country'] = $geoData['country'] ?? null;
                $result['country_code'] = $geoData['countryCode'] ?? null;
                $result['city'] = $geoData['city'] ?? null;
                $result['isp'] = $geoData['isp'] ?? null;
                $result['org'] = $geoData['org'] ?? null;
            }
        }

        return $result;
    }

    /**
     * Ejecuta y muestra resultados formateados
     */
    public static function runAndPrint(): void
    {
        $results = self::run();

        echo "\n=== DIAGNOSTICO DE PROXY ===\n";
        echo "Timestamp: {$results['timestamp']}\n";
        echo "Proxy Host: {$results['proxy_host']}\n";
        echo "Proxy Configurado: " . ($results['proxy_configured'] ? 'SI' : 'NO') . "\n\n";

        foreach ($results['tests'] as $testName => $test) {
            echo "--- Test: {$testName} ---\n";
            echo "IP de Salida: " . ($test['ip'] ?? 'ERROR') . "\n";
            echo "Pais: " . ($test['country'] ?? 'N/A') . " ({$test['country_code']})\n";
            echo "ISP: " . ($test['isp'] ?? 'N/A') . "\n";
            echo "Org: " . ($test['org'] ?? 'N/A') . "\n\n";
        }

        echo "=== CONCLUSION ===\n";
        echo $results['conclusion'] . "\n";
        echo "Proxy Funcionando: " . ($results['proxy_working'] ? 'SI' : 'NO') . "\n";
    }
}
