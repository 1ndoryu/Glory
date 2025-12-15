<?php

namespace Glory\Plugins\AmazonProduct\Service;

use Glory\Plugins\AmazonProduct\Model\License;
use Glory\Core\GloryLogger;

/**
 * Servicio de gestion de licencias.
 * 
 * Solo se usa en modo SERVIDOR.
 * Gestiona CRUD de licencias, validacion de API Keys,
 * y control de uso de GB.
 */
class LicenseService
{
    private const TABLE_NAME = 'glory_amazon_licenses';

    /**
     * Crea la tabla de licencias en la base de datos.
     * Llamar en activacion del plugin.
     */
    public static function createTable(): void
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::TABLE_NAME;
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            api_key VARCHAR(64) NOT NULL,
            email VARCHAR(255) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'trial',
            gb_used DECIMAL(10,6) NOT NULL DEFAULT 0,
            gb_limit DECIMAL(10,2) NOT NULL DEFAULT 4,
            stripe_customer_id VARCHAR(255) DEFAULT '',
            stripe_subscription_id VARCHAR(255) DEFAULT '',
            created_at BIGINT(20) NOT NULL,
            expires_at BIGINT(20) DEFAULT 0,
            last_request_at BIGINT(20) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY api_key (api_key),
            KEY email (email),
            KEY status (status),
            KEY stripe_subscription_id (stripe_subscription_id)
        ) {$charsetCollate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        GloryLogger::info('LicenseService: Tabla de licencias creada/verificada');
    }

    /**
     * Genera una API Key unica.
     */
    public static function generateApiKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Crea una nueva licencia.
     */
    public static function create(string $email, string $stripeCustomerId = '', string $stripeSubscriptionId = ''): ?License
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::TABLE_NAME;
        $apiKey = self::generateApiKey();

        $data = [
            'api_key' => $apiKey,
            'email' => sanitize_email($email),
            'status' => License::STATUS_TRIAL,
            'gb_used' => 0,
            'gb_limit' => License::DEFAULT_GB_LIMIT,
            'stripe_customer_id' => $stripeCustomerId,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'created_at' => time(),
            'expires_at' => strtotime('+30 days'),
            'last_request_at' => 0,
        ];

        $result = $wpdb->insert($tableName, $data);

        if ($result === false) {
            GloryLogger::error('LicenseService: Error al crear licencia - ' . $wpdb->last_error);
            return null;
        }

        $data['id'] = $wpdb->insert_id;
        GloryLogger::info("LicenseService: Licencia creada para {$email}");

        return new License($data);
    }

    /**
     * Busca una licencia por API Key.
     */
    public static function findByApiKey(string $apiKey): ?License
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::TABLE_NAME;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$tableName} WHERE api_key = %s", $apiKey),
            ARRAY_A
        );

        return $row ? new License($row) : null;
    }

    /**
     * Busca una licencia por email.
     */
    public static function findByEmail(string $email): ?License
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::TABLE_NAME;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$tableName} WHERE email = %s", $email),
            ARRAY_A
        );

        return $row ? new License($row) : null;
    }

    /**
     * Busca una licencia por ID de suscripcion de Stripe.
     */
    public static function findByStripeSubscription(string $subscriptionId): ?License
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::TABLE_NAME;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$tableName} WHERE stripe_subscription_id = %s", $subscriptionId),
            ARRAY_A
        );

        return $row ? new License($row) : null;
    }

    /**
     * Actualiza una licencia existente.
     */
    public static function update(License $license): bool
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::TABLE_NAME;
        $data = $license->toArray();
        $id = $data['id'];
        unset($data['id']);

        $result = $wpdb->update($tableName, $data, ['id' => $id]);

        if ($result === false) {
            GloryLogger::error('LicenseService: Error al actualizar licencia - ' . $wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Registra uso de bytes para una licencia.
     */
    public static function recordUsage(License $license, int $bytes): bool
    {
        $license->addUsage($bytes);
        return self::update($license);
    }

    /**
     * Activa una licencia (despues de pago exitoso).
     */
    public static function activate(License $license, int $daysValid = 30): bool
    {
        $license->setStatus(License::STATUS_ACTIVE);
        $license->setExpiresAt(strtotime("+{$daysValid} days"));

        GloryLogger::info("LicenseService: Licencia activada para {$license->getEmail()}");

        return self::update($license);
    }

    /**
     * Suspende una licencia (pago fallido, cancelacion, etc).
     */
    public static function suspend(License $license): bool
    {
        $license->setStatus(License::STATUS_SUSPENDED);

        GloryLogger::info("LicenseService: Licencia suspendida para {$license->getEmail()}");

        return self::update($license);
    }

    /**
     * Marca una licencia como expirada.
     */
    public static function expire(License $license): bool
    {
        $license->setStatus(License::STATUS_EXPIRED);

        GloryLogger::info("LicenseService: Licencia expirada para {$license->getEmail()}");

        return self::update($license);
    }

    /**
     * Reinicia el uso de GB (nuevo ciclo de facturacion).
     */
    public static function resetUsage(License $license): bool
    {
        $license->resetUsage();
        return self::update($license);
    }

    /**
     * Obtiene todas las licencias con filtros opcionales.
     */
    public static function getAll(array $filters = []): array
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::TABLE_NAME;
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = $filters['status'];
        }

        $sql = "SELECT * FROM {$tableName}";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY created_at DESC';

        if (!empty($filters['limit'])) {
            $sql .= ' LIMIT ' . intval($filters['limit']);
        }

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, ...$params);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);

        return array_map(fn($row) => new License($row), $rows ?: []);
    }

    /**
     * Cuenta licencias por estado.
     */
    public static function countByStatus(): array
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::TABLE_NAME;

        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$tableName} GROUP BY status",
            ARRAY_A
        );

        $counts = [
            'active' => 0,
            'trial' => 0,
            'expired' => 0,
            'suspended' => 0,
            'total' => 0,
        ];

        foreach ($results ?: [] as $row) {
            $counts[$row['status']] = (int) $row['count'];
            $counts['total'] += (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Obtiene estadisticas globales de uso.
     */
    public static function getGlobalStats(): array
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::TABLE_NAME;

        $row = $wpdb->get_row(
            "SELECT 
                SUM(gb_used) as total_gb_used,
                COUNT(*) as total_licenses,
                SUM(CASE WHEN status IN ('active', 'trial') THEN 1 ELSE 0 END) as active_licenses
            FROM {$tableName}",
            ARRAY_A
        );

        return [
            'total_gb_used' => round((float) ($row['total_gb_used'] ?? 0), 3),
            'total_licenses' => (int) ($row['total_licenses'] ?? 0),
            'active_licenses' => (int) ($row['active_licenses'] ?? 0),
        ];
    }

    /**
     * Verifica licencias expiradas y las marca.
     * Ejecutar periodicamente via cron.
     */
    public static function checkExpiredLicenses(): int
    {
        global $wpdb;

        $tableName = $wpdb->prefix . self::TABLE_NAME;
        $now = time();

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$tableName} 
                SET status = %s 
                WHERE expires_at > 0 
                AND expires_at < %d 
                AND status IN (%s, %s)",
                License::STATUS_EXPIRED,
                $now,
                License::STATUS_ACTIVE,
                License::STATUS_TRIAL
            )
        );

        if ($result > 0) {
            GloryLogger::info("LicenseService: {$result} licencias marcadas como expiradas");
        }

        return (int) $result;
    }
}
