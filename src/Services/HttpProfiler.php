<?php

namespace Glory\Services;

/**
 * Profiling de peticiones HTTP salientes de WordPress.
 * Extraído de PerformanceProfiler para cumplir SRP.
 */
class HttpProfiler
{
    /**
     * Tiempos de inicio por clave URL|METHOD.
     * @var array<string, float[]>
     */
    private static array $httpStarts = [];

    /**
     * Registra los hooks de interceptación HTTP.
     * Se debe llamar solo si el profiler está activo.
     */
    public static function registerHooks(): void
    {
        add_filter('pre_http_request', [self::class, 'httpRequestStart'], 10, 3);
        add_filter('http_response', [self::class, 'httpRequestEnd'], 10, 3);
    }

    /**
     * Marca inicio de una petición HTTP (no intercepta la solicitud).
     */
    public static function httpRequestStart($pre, array $args, string $url)
    {
        $method = strtoupper($args['method'] ?? 'GET');
        $key = $url . '|' . $method;
        self::$httpStarts[$key] = self::$httpStarts[$key] ?? [];
        self::$httpStarts[$key][] = microtime(true);
        return $pre;
    }

    /**
     * Marca fin de una petición HTTP y registra su duración.
     */
    public static function httpRequestEnd($response, array $args, string $url)
    {
        $method = strtoupper($args['method'] ?? 'GET');
        $key = $url . '|' . $method;
        $start = null;
        if (isset(self::$httpStarts[$key]) && !empty(self::$httpStarts[$key])) {
            $start = array_pop(self::$httpStarts[$key]);
        }
        if ($start !== null) {
            $duracion = microtime(true) - $start;
            $code = is_array($response) && isset($response['response']['code']) ? (int) $response['response']['code'] : 0;
            $timeout = $args['timeout'] ?? '';
            error_log(sprintf('[Glory Profiler][HTTP] %s %s | %0.6fs | code=%s | timeout=%s', $method, $url, $duracion, $code, $timeout));
        }
        return $response;
    }
}
