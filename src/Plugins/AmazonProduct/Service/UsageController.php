<?php

namespace Glory\Plugins\AmazonProduct\Service;

use Glory\Plugins\AmazonProduct\Model\License;
use Glory\Core\GloryLogger;

/**
 * Controlador de uso y rate limiting.
 * 
 * Protege contra:
 * - Exceso de requests por minuto
 * - Consumo anomalo de GB
 * - Abuso del servicio
 * 
 * Solo se usa en modo SERVIDOR.
 */
class UsageController
{
    /*
     * Limites por defecto
     */
    private const MAX_REQUESTS_PER_MINUTE = 30;
    private const MAX_BYTES_PER_REQUEST = 2 * 1024 * 1024;
    private const ANOMALY_THRESHOLD_GB_PER_HOUR = 0.5;

    private const CACHE_PREFIX = 'glory_rate_limit_';

    /**
     * Verifica si una licencia puede hacer un request.
     * 
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public static function checkRequest(License $license): array
    {
        /*
         * Verificar estado de licencia
         */
        if (!$license->isValid()) {
            return [
                'allowed' => false,
                'reason' => 'license_invalid',
                'message' => 'Licencia no valida o expirada'
            ];
        }

        /*
         * Verificar GB disponibles
         */
        if (!$license->hasGbAvailable()) {
            return [
                'allowed' => false,
                'reason' => 'gb_exceeded',
                'message' => 'Limite de GB alcanzado'
            ];
        }

        /*
         * Verificar rate limit (requests por minuto)
         */
        $rateCheck = self::checkRateLimit($license);
        if (!$rateCheck['allowed']) {
            return $rateCheck;
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Verifica rate limit por API Key.
     */
    private static function checkRateLimit(License $license): array
    {
        $cacheKey = self::CACHE_PREFIX . 'rpm_' . md5($license->getApiKey());
        $currentCount = (int) get_transient($cacheKey);

        if ($currentCount >= self::MAX_REQUESTS_PER_MINUTE) {
            GloryLogger::warning("RateLimit: Limite alcanzado para {$license->getEmail()}");

            return [
                'allowed' => false,
                'reason' => 'rate_limit',
                'message' => 'Demasiadas solicitudes. Espera un momento.'
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Incrementa el contador de requests por minuto.
     */
    public static function incrementRateLimit(License $license): void
    {
        $cacheKey = self::CACHE_PREFIX . 'rpm_' . md5($license->getApiKey());
        $currentCount = (int) get_transient($cacheKey);

        set_transient($cacheKey, $currentCount + 1, 60);
    }

    /**
     * Registra el uso de una request completada.
     * 
     * @param License $license
     * @param int $bytes Bytes consumidos
     * @param string $endpoint Endpoint usado
     * @param array $metadata Datos adicionales
     */
    public static function recordRequest(License $license, int $bytes, string $endpoint = '', array $metadata = []): void
    {
        /*
         * Limitar bytes maximos por request (proteccion contra datos falsos)
         */
        $bytes = min($bytes, self::MAX_BYTES_PER_REQUEST);

        /*
         * Actualizar uso en licencia
         */
        LicenseService::recordUsage($license, $bytes);

        /*
         * Incrementar rate limit
         */
        self::incrementRateLimit($license);

        /*
         * Registrar en log de uso
         */
        self::logRequest($license, $bytes, $endpoint, $metadata);

        /*
         * Verificar anomalias
         */
        self::checkForAnomalies($license);
    }

    /**
     * Registra un request en el log.
     */
    private static function logRequest(License $license, int $bytes, string $endpoint, array $metadata): void
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'glory_amazon_usage_log';

        /*
         * Crear tabla si no existe
         */
        self::ensureLogTableExists();

        $wpdb->insert($tableName, [
            'license_id' => $license->getId(),
            'api_key' => substr($license->getApiKey(), 0, 16) . '...',
            'endpoint' => $endpoint,
            'bytes_used' => $bytes,
            'metadata' => json_encode($metadata),
            'ip_address' => self::getClientIp(),
            'created_at' => time(),
        ]);
    }

    /**
     * Verifica patrones anomalos de uso.
     */
    private static function checkForAnomalies(License $license): void
    {
        $cacheKey = self::CACHE_PREFIX . 'hourly_' . md5($license->getApiKey());
        $hourlyBytes = (int) get_transient($cacheKey);

        $gbPerHour = $hourlyBytes / (1024 * 1024 * 1024);

        if ($gbPerHour > self::ANOMALY_THRESHOLD_GB_PER_HOUR) {
            GloryLogger::warning(
                "Anomalia detectada: {$license->getEmail()} consumiendo {$gbPerHour} GB/hora"
            );

            /*
             * Podriamos suspender automaticamente, pero por ahora solo alertamos
             */
        }
    }

    /**
     * Obtiene IP del cliente.
     */
    private static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }

        return 'unknown';
    }

    /**
     * Crea la tabla de log de uso.
     */
    private static function ensureLogTableExists(): void
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'glory_amazon_usage_log';
        $charsetCollate = $wpdb->get_charset_collate();

        /*
         * Verificar si ya existe para no recrear constantemente
         */
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$tableName}'") === $tableName;

        if ($exists) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id BIGINT(20) UNSIGNED NOT NULL,
            api_key VARCHAR(32) NOT NULL,
            endpoint VARCHAR(100) DEFAULT '',
            bytes_used INT(11) NOT NULL DEFAULT 0,
            metadata TEXT,
            ip_address VARCHAR(45) DEFAULT '',
            created_at BIGINT(20) NOT NULL,
            PRIMARY KEY (id),
            KEY license_id (license_id),
            KEY created_at (created_at)
        ) {$charsetCollate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Obtiene estadisticas de uso reciente.
     */
    public static function getRecentStats(int $hours = 24): array
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'glory_amazon_usage_log';
        $since = time() - ($hours * 3600);

        self::ensureLogTableExists();

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_requests,
                    SUM(bytes_used) as total_bytes,
                    COUNT(DISTINCT license_id) as unique_users
                FROM {$tableName}
                WHERE created_at >= %d",
                $since
            ),
            ARRAY_A
        );

        return [
            'requests' => (int) ($row['total_requests'] ?? 0),
            'bytes' => (int) ($row['total_bytes'] ?? 0),
            'gb' => round((int) ($row['total_bytes'] ?? 0) / (1024 * 1024 * 1024), 3),
            'unique_users' => (int) ($row['unique_users'] ?? 0),
            'period_hours' => $hours,
        ];
    }

    /**
     * Obtiene log de uso reciente.
     */
    public static function getRecentLog(int $limit = 50): array
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'glory_amazon_usage_log';

        self::ensureLogTableExists();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$tableName} ORDER BY created_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Limpia logs antiguos (mas de 30 dias).
     */
    public static function cleanOldLogs(): int
    {
        global $wpdb;

        $tableName = $wpdb->prefix . 'glory_amazon_usage_log';
        $threshold = time() - (30 * 24 * 3600);

        return (int) $wpdb->query(
            $wpdb->prepare("DELETE FROM {$tableName} WHERE created_at < %d", $threshold)
        );
    }
}
