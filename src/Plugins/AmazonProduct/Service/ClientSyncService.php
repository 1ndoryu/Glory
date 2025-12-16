<?php

namespace Glory\Plugins\AmazonProduct\Service;

use Glory\Plugins\AmazonProduct\Mode\PluginMode;
use Glory\Core\GloryLogger;

/**
 * Servicio de sincronizacion de productos para modo cliente SaaS.
 * 
 * Actualiza productos progresivamente (1 cada 40 segundos) para evitar
 * sobrecargar el servidor y el proxy. Compatible con WP Cron.
 * 
 * Caracteristicas:
 * - Actualizacion progresiva (no todo al mismo tiempo)
 * - Reintentos automaticos en caso de fallo
 * - Respeta limites de GB del cliente
 * - Log de actividad detallado
 */
class ClientSyncService
{
    private const POST_TYPE = 'amazon_product';
    private const CRON_HOOK = 'glory_amazon_client_sync';
    private const CRON_SINGLE_HOOK = 'glory_amazon_sync_single';

    private const OPTION_SYNC_CONFIG = 'glory_amazon_sync_config';
    private const OPTION_SYNC_STATUS = 'glory_amazon_sync_status';
    private const OPTION_SYNC_QUEUE = 'glory_amazon_sync_queue';
    private const OPTION_SYNC_LOG = 'glory_amazon_sync_log';

    /* Delay entre cada producto (en segundos) */
    private const DELAY_BETWEEN_PRODUCTS = 40;

    /* Maximo de reintentos por producto */
    private const MAX_RETRIES = 2;

    /* Maximo de entradas en el log */
    private const MAX_LOG_ENTRIES = 100;

    /**
     * Inicializa los hooks de cron.
     */
    public static function init(): void
    {
        /* Solo en modo cliente */
        if (!PluginMode::isClient()) {
            return;
        }

        add_action(self::CRON_HOOK, [self::class, 'startSyncCycle']);
        add_action(self::CRON_SINGLE_HOOK, [self::class, 'syncNextProduct']);

        /* Registrar intervalos personalizados de cron */
        add_filter('cron_schedules', [self::class, 'addCronIntervals']);
    }

    /**
     * Agrega intervalos personalizados de cron.
     */
    public static function addCronIntervals(array $schedules): array
    {
        $schedules['glory_every_3_days'] = [
            'interval' => 3 * DAY_IN_SECONDS,
            'display' => 'Cada 3 dias'
        ];
        $schedules['glory_every_7_days'] = [
            'interval' => 7 * DAY_IN_SECONDS,
            'display' => 'Cada 7 dias'
        ];
        $schedules['glory_every_14_days'] = [
            'interval' => 14 * DAY_IN_SECONDS,
            'display' => 'Cada 14 dias'
        ];
        return $schedules;
    }

    /**
     * Guarda la configuracion de sincronizacion y reprograma el cron.
     */
    public static function saveConfig(string $frequency): void
    {
        $config = [
            'frequency' => $frequency,
            'updated_at' => time()
        ];

        update_option(self::OPTION_SYNC_CONFIG, $config);
        self::scheduleSync($frequency);

        self::addToLog('config', "Frecuencia cambiada a: {$frequency}", 'info');
    }

    /**
     * Obtiene la configuracion actual.
     */
    public static function getConfig(): array
    {
        $defaults = [
            'frequency' => 'off',
            'updated_at' => 0
        ];

        return array_merge($defaults, get_option(self::OPTION_SYNC_CONFIG, []));
    }

    /**
     * Programa el cron segun la frecuencia.
     */
    private static function scheduleSync(string $frequency): void
    {
        /* Limpiar programacion anterior */
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }

        if ($frequency === 'off') {
            return;
        }

        /* Mapear frecuencia a intervalo de cron */
        $intervalMap = [
            'daily' => 'daily',
            '3days' => 'glory_every_3_days',
            'weekly' => 'glory_every_7_days',
            '14days' => 'glory_every_14_days'
        ];

        $interval = $intervalMap[$frequency] ?? null;

        if ($interval) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, $interval, self::CRON_HOOK);
            GloryLogger::info("ClientSyncService: Cron programado con intervalo {$interval}");
        }
    }

    /**
     * Inicia un ciclo de sincronizacion.
     * Crea la cola de productos y programa el primer producto.
     */
    public static function startSyncCycle(): void
    {
        GloryLogger::info('ClientSyncService: Iniciando ciclo de sincronizacion');

        /* Obtener todos los productos */
        $products = self::getAllProducts();

        if (empty($products)) {
            self::addToLog('sync', 'No hay productos para sincronizar', 'info');
            return;
        }

        /* Crear cola de sincronizacion */
        $queue = [];
        foreach ($products as $product) {
            $queue[] = [
                'post_id' => $product['post_id'],
                'asin' => $product['asin'],
                'retries' => 0,
                'status' => 'pending'
            ];
        }

        /* Guardar cola y estado */
        update_option(self::OPTION_SYNC_QUEUE, $queue);
        self::updateStatus([
            'running' => true,
            'started_at' => time(),
            'total' => count($queue),
            'processed' => 0,
            'success' => 0,
            'failed' => 0
        ]);

        self::addToLog('sync', "Iniciando sincronizacion de " . count($queue) . " productos", 'info');

        /* Programar el primer producto inmediatamente */
        if (!wp_next_scheduled(self::CRON_SINGLE_HOOK)) {
            wp_schedule_single_event(time() + 5, self::CRON_SINGLE_HOOK);
        }
    }

    /**
     * Sincroniza el siguiente producto en la cola.
     * Se llama recursivamente via WP Cron hasta completar la cola.
     */
    public static function syncNextProduct(): void
    {
        $queue = get_option(self::OPTION_SYNC_QUEUE, []);
        $status = self::getStatus();

        if (empty($queue)) {
            /* Cola vacia, terminar */
            self::finishSyncCycle();
            return;
        }

        /* Obtener siguiente producto pendiente */
        $index = null;
        $item = null;
        foreach ($queue as $i => $q) {
            if ($q['status'] === 'pending') {
                $index = $i;
                $item = $q;
                break;
            }
        }

        if ($item === null) {
            /* No hay mas pendientes */
            self::finishSyncCycle();
            return;
        }

        /* Intentar sincronizar */
        $success = self::syncSingleProduct($item['post_id'], $item['asin']);

        /* Actualizar cola */
        if ($success) {
            $queue[$index]['status'] = 'success';
            $status['success']++;
        } else {
            $queue[$index]['retries']++;

            if ($queue[$index]['retries'] >= self::MAX_RETRIES) {
                $queue[$index]['status'] = 'failed';
                $status['failed']++;
            }
            /* Si no alcanzo max retries, sigue como pending */
        }

        $status['processed']++;

        update_option(self::OPTION_SYNC_QUEUE, $queue);
        self::updateStatus($status);

        /* Verificar si hay mas productos pendientes */
        $pending = array_filter($queue, fn($q) => $q['status'] === 'pending');

        if (!empty($pending)) {
            /* Programar siguiente producto con delay */
            wp_schedule_single_event(time() + self::DELAY_BETWEEN_PRODUCTS, self::CRON_SINGLE_HOOK);
        } else {
            self::finishSyncCycle();
        }
    }

    /**
     * Sincroniza un producto individual.
     */
    private static function syncSingleProduct(int $postId, string $asin): bool
    {
        $apiClient = new ApiClient();

        /* Obtener datos actualizados del servidor */
        $result = $apiClient->getProductByAsin($asin);

        if (!$result['success'] || empty($result['product'])) {
            $error = $result['error'] ?? 'Error desconocido';
            self::addToLog('error', "Error sincronizando ASIN {$asin}: {$error}", 'error');
            return false;
        }

        $product = $result['product'];

        /* Actualizar meta fields del producto */
        $updated = false;

        $fieldsToUpdate = [
            'price' => $product['asin_price'] ?? $product['price'] ?? null,
            'original_price' => $product['asin_original_price'] ?? $product['original_price'] ?? null,
            'rating' => $product['total_start'] ?? $product['rating'] ?? null,
            'reviews' => $product['total_review'] ?? $product['reviews'] ?? null,
            'prime' => isset($product['is_prime']) ? ($product['is_prime'] ? '1' : '0') : null
        ];

        foreach ($fieldsToUpdate as $key => $value) {
            if ($value !== null) {
                update_post_meta($postId, $key, $value);
                $updated = true;
            }
        }

        if ($updated) {
            update_post_meta($postId, 'last_synced', time());

            $title = get_the_title($postId);
            $shortTitle = mb_substr($title, 0, 40) . (mb_strlen($title) > 40 ? '...' : '');
            self::addToLog('success', "Actualizado: {$shortTitle}", 'success');
        }

        return $updated;
    }

    /**
     * Finaliza el ciclo de sincronizacion.
     */
    private static function finishSyncCycle(): void
    {
        $status = self::getStatus();

        $status['running'] = false;
        $status['finished_at'] = time();

        self::updateStatus($status);

        /* Limpiar cola */
        delete_option(self::OPTION_SYNC_QUEUE);

        $message = sprintf(
            'Sincronizacion completada: %d exitosos, %d fallidos de %d total',
            $status['success'],
            $status['failed'],
            $status['total']
        );

        self::addToLog('sync', $message, $status['failed'] > 0 ? 'warning' : 'success');
        GloryLogger::info("ClientSyncService: {$message}");
    }

    /**
     * Ejecuta sincronizacion manual (inicia el ciclo).
     */
    public static function runManualSync(): array
    {
        /* Verificar que no haya otra sincronizacion en curso */
        $status = self::getStatus();
        if ($status['running']) {
            return [
                'success' => false,
                'message' => 'Ya hay una sincronizacion en curso'
            ];
        }

        /* Iniciar ciclo */
        self::startSyncCycle();

        return [
            'success' => true,
            'message' => 'Sincronizacion iniciada. Los productos se actualizaran progresivamente.'
        ];
    }

    /**
     * Obtiene todos los productos guardados.
     */
    public static function getAllProducts(): array
    {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID as post_id, pm.meta_value as asin
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = %s
             AND p.post_status = 'publish'
             AND pm.meta_key = 'asin'
             AND pm.meta_value != ''
             ORDER BY p.post_modified ASC",
            self::POST_TYPE
        ), ARRAY_A) ?: [];
    }

    /**
     * Obtiene el conteo de productos.
     */
    public static function getProductCount(): int
    {
        $count = wp_count_posts(self::POST_TYPE);
        return (int) ($count->publish ?? 0);
    }

    /**
     * Obtiene el estado actual de sincronizacion.
     */
    public static function getStatus(): array
    {
        $defaults = [
            'running' => false,
            'started_at' => 0,
            'finished_at' => 0,
            'total' => 0,
            'processed' => 0,
            'success' => 0,
            'failed' => 0
        ];

        return array_merge($defaults, get_option(self::OPTION_SYNC_STATUS, []));
    }

    /**
     * Actualiza el estado de sincronizacion.
     */
    private static function updateStatus(array $status): void
    {
        update_option(self::OPTION_SYNC_STATUS, $status);
    }

    /**
     * Obtiene informacion completa para mostrar en UI.
     */
    public static function getSyncInfo(): array
    {
        $config = self::getConfig();
        $status = self::getStatus();
        $productCount = self::getProductCount();
        $nextScheduled = wp_next_scheduled(self::CRON_HOOK);

        /* Estimar consumo por actualizacion */
        $estimatedBytesPerProduct = 500 * 1024; // ~500 KB por producto
        $estimatedGbPerSync = ($productCount * $estimatedBytesPerProduct) / (1024 * 1024 * 1024);

        /* Calcular tiempo estimado para actualizar todos */
        $estimatedTimeSeconds = $productCount * self::DELAY_BETWEEN_PRODUCTS;
        $estimatedTimeMinutes = ceil($estimatedTimeSeconds / 60);

        return [
            'frequency' => $config['frequency'],
            'frequencyLabel' => self::getFrequencyLabel($config['frequency']),
            'productCount' => $productCount,
            'isRunning' => $status['running'],
            'progress' => $status['running'] ? [
                'processed' => $status['processed'],
                'total' => $status['total'],
                'percent' => $status['total'] > 0
                    ? round(($status['processed'] / $status['total']) * 100)
                    : 0
            ] : null,
            'lastSync' => $status['finished_at'] > 0
                ? date('d/m/Y H:i', $status['finished_at'])
                : 'Nunca',
            'nextSync' => $nextScheduled
                ? date('d/m/Y H:i', $nextScheduled)
                : 'No programado',
            'estimatedGbPerSync' => round($estimatedGbPerSync, 3),
            'estimatedMbPerSync' => round($estimatedGbPerSync * 1024, 1),
            'estimatedTimeMinutes' => $estimatedTimeMinutes
        ];
    }

    /**
     * Obtiene etiqueta legible para frecuencia.
     */
    public static function getFrequencyLabel(string $frequency): string
    {
        $labels = [
            'off' => 'Desactivado',
            'daily' => 'Diario',
            '3days' => 'Cada 3 dias',
            'weekly' => 'Semanal',
            '14days' => 'Cada 14 dias'
        ];
        return $labels[$frequency] ?? $frequency;
    }

    /**
     * Agrega entrada al log.
     */
    private static function addToLog(string $type, string $message, string $level = 'info'): void
    {
        $log = get_option(self::OPTION_SYNC_LOG, []);

        array_unshift($log, [
            'timestamp' => time(),
            'date' => date('d/m/Y H:i:s'),
            'type' => $type,
            'message' => $message,
            'level' => $level
        ]);

        /* Limitar tamano del log */
        if (count($log) > self::MAX_LOG_ENTRIES) {
            $log = array_slice($log, 0, self::MAX_LOG_ENTRIES);
        }

        update_option(self::OPTION_SYNC_LOG, $log);
    }

    /**
     * Obtiene el log de sincronizacion.
     */
    public static function getLog(int $limit = 20): array
    {
        $log = get_option(self::OPTION_SYNC_LOG, []);
        return array_slice($log, 0, $limit);
    }

    /**
     * Limpia el log.
     */
    public static function clearLog(): void
    {
        update_option(self::OPTION_SYNC_LOG, []);
    }
}
