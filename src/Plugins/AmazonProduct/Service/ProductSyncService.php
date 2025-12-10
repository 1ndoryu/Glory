<?php

namespace Glory\Plugins\AmazonProduct\Service;

use Glory\Core\GloryLogger;

/**
 * Product Sync Service - Sincronizacion programada de productos.
 * 
 * FEAT-07: Panel de control de actualizacion programada
 * FEAT-08: Actualizacion inteligente de productos guardados
 * 
 * Responsabilidades:
 * - Actualizar precios/disponibilidad de productos YA guardados
 * - Programar tareas con WP Cron
 * - Registrar log de actualizaciones
 * - Procesar por lotes para evitar timeout
 */
class ProductSyncService
{
    private const POST_TYPE = 'amazon_product';
    private const OPTION_LAST_SYNC = 'amazon_last_sync';
    private const OPTION_SYNC_LOG = 'amazon_sync_log';
    private const OPTION_SYNC_STATUS = 'amazon_sync_status';
    private const CRON_HOOK = 'amazon_product_sync_event';
    private const BATCH_SIZE = 10;
    private const MAX_LOG_ENTRIES = 50;

    /**
     * Registra los hooks de WP Cron.
     */
    public static function init(): void
    {
        add_action(self::CRON_HOOK, [self::class, 'runScheduledSync']);
    }

    /**
     * Programa la sincronizacion segun la frecuencia configurada.
     * Llamar despues de guardar configuracion.
     */
    public static function scheduleSync(): void
    {
        $frequency = get_option('amazon_sync_frequency', 'off');

        // Limpiar cualquier programacion existente
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }

        // Programar segun frecuencia
        if ($frequency !== 'off') {
            $schedule = self::getScheduleInterval($frequency);
            if ($schedule) {
                wp_schedule_event(time(), $schedule, self::CRON_HOOK);
            }
        }
    }

    /**
     * Convierte la frecuencia configurada a intervalo de WP Cron.
     */
    private static function getScheduleInterval(string $frequency): ?string
    {
        $map = [
            'hourly6' => 'twicedaily', // Cada 12h es lo mas cercano a 6h en WP
            'daily' => 'daily',
            'weekly' => 'weekly'
        ];
        return $map[$frequency] ?? null;
    }

    /**
     * Ejecuta la sincronizacion programada.
     * Actualiza productos por lotes para evitar timeout.
     */
    public static function runScheduledSync(): void
    {
        GloryLogger::info('ProductSyncService: Iniciando sincronizacion programada');
        self::updateSyncStatus('running', 'Sincronizacion en progreso...');

        $result = self::syncAllProducts();

        self::updateSyncStatus('completed', sprintf(
            'Sincronizacion completada: %d actualizados, %d fallidos',
            $result['updated'],
            $result['failed']
        ));

        // Guardar timestamp de ultima sincronizacion
        update_option(self::OPTION_LAST_SYNC, current_time('timestamp'));

        GloryLogger::info('ProductSyncService: Sincronizacion completada', $result);
    }

    /**
     * Sincroniza todos los productos guardados.
     * FEAT-08: Solo actualiza productos YA guardados, no trae nuevos.
     * 
     * @return array [updated => int, failed => int, skipped => int]
     */
    public static function syncAllProducts(): array
    {
        $result = [
            'updated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => []
        ];

        // Obtener todos los ASINs guardados
        $asins = self::getAllSavedAsins();

        if (empty($asins)) {
            self::addToLog('sync', 'No hay productos para sincronizar', 'info');
            return $result;
        }

        $apiService = new AmazonApiService();
        $batches = array_chunk($asins, self::BATCH_SIZE);

        foreach ($batches as $batchIndex => $batch) {
            foreach ($batch as $asinData) {
                $asin = $asinData['asin'];
                $postId = $asinData['post_id'];

                // Verificar limite de API antes de cada llamada
                if (!ApiUsageTracker::canMakeCall()) {
                    self::addToLog('sync', 'Sincronizacion detenida: limite de API alcanzado', 'warning');
                    $result['skipped'] += count($asins) - ($batchIndex * self::BATCH_SIZE + array_search($asinData, $batch));
                    return $result;
                }

                $productData = $apiService->getProductByAsin($asin);

                if (empty($productData)) {
                    $result['failed']++;
                    $result['details'][] = ['asin' => $asin, 'status' => 'failed', 'reason' => 'API no devolvio datos'];
                    continue;
                }

                // Actualizar solo precio y disponibilidad
                $updated = self::updateProductData($postId, $productData);

                if ($updated) {
                    $result['updated']++;
                    $result['details'][] = ['asin' => $asin, 'status' => 'updated'];
                } else {
                    $result['failed']++;
                    $result['details'][] = ['asin' => $asin, 'status' => 'failed', 'reason' => 'Error al actualizar'];
                }
            }

            // Pausa entre lotes para evitar saturar la API
            if ($batchIndex < count($batches) - 1) {
                usleep(500000); // 0.5 segundos
            }
        }

        // Agregar al log
        self::addToLog('sync', sprintf(
            'Sincronizados %d de %d productos',
            $result['updated'],
            count($asins)
        ), $result['failed'] > 0 ? 'warning' : 'success');

        return $result;
    }

    /**
     * Obtiene lista de todos los ASINs guardados en la DB.
     * 
     * @return array [['asin' => string, 'post_id' => int], ...]
     */
    public static function getAllSavedAsins(): array
    {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID as post_id, pm.meta_value as asin
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = %s
             AND p.post_status = 'publish'
             AND pm.meta_key = 'asin'
             AND pm.meta_value != ''
             ORDER BY p.post_date DESC",
            self::POST_TYPE
        ), ARRAY_A);

        return $results ?: [];
    }

    /**
     * Actualiza datos de un producto existente.
     * Solo actualiza precio, rating y reviews (no titulo ni imagen).
     * 
     * @param int $postId ID del post
     * @param array $data Datos de la API
     * @return bool True si se actualizo correctamente
     */
    private static function updateProductData(int $postId, array $data): bool
    {
        // Extraer datos segun el formato de respuesta de la API
        $price = $data['asin_price'] ?? $data['price'] ?? null;
        $rating = $data['total_start'] ?? $data['asin_rating_star'] ?? null;
        $reviews = $data['total_review'] ?? $data['asin_total_review'] ?? null;
        $isPrime = isset($data['is_prime']) ? (!empty($data['is_prime']) ? '1' : '0') : null;

        $updated = false;

        if ($price !== null) {
            update_post_meta($postId, 'price', $price);
            $updated = true;
        }

        if ($rating !== null) {
            update_post_meta($postId, 'rating', $rating);
            $updated = true;
        }

        if ($reviews !== null) {
            update_post_meta($postId, 'reviews', $reviews);
            $updated = true;
        }

        if ($isPrime !== null) {
            update_post_meta($postId, 'prime', $isPrime);
            $updated = true;
        }

        // Registrar fecha de ultima actualizacion
        if ($updated) {
            update_post_meta($postId, 'last_synced', current_time('timestamp'));
        }

        return $updated;
    }

    /**
     * Obtiene informacion del estado de sincronizacion.
     * 
     * @return array [status, message, lastSync, nextSync, productCount]
     */
    public static function getSyncInfo(): array
    {
        $status = get_option(self::OPTION_SYNC_STATUS, ['status' => 'idle', 'message' => '']);
        $lastSync = get_option(self::OPTION_LAST_SYNC, 0);
        $nextScheduled = wp_next_scheduled(self::CRON_HOOK);
        $frequency = get_option('amazon_sync_frequency', 'off');

        return [
            'status' => $status['status'] ?? 'idle',
            'message' => $status['message'] ?? '',
            'lastSync' => $lastSync ? date('Y-m-d H:i:s', $lastSync) : 'Nunca',
            'lastSyncTimestamp' => $lastSync,
            'nextSync' => $nextScheduled ? date('Y-m-d H:i:s', $nextScheduled) : 'No programado',
            'nextSyncTimestamp' => $nextScheduled ?: 0,
            'frequency' => $frequency,
            'productCount' => self::getProductCount()
        ];
    }

    /**
     * Cuenta total de productos guardados.
     */
    public static function getProductCount(): int
    {
        $count = wp_count_posts(self::POST_TYPE);
        return (int) ($count->publish ?? 0);
    }

    /**
     * Actualiza el estado de sincronizacion.
     */
    private static function updateSyncStatus(string $status, string $message): void
    {
        update_option(self::OPTION_SYNC_STATUS, [
            'status' => $status,
            'message' => $message,
            'timestamp' => current_time('timestamp')
        ]);
    }

    /**
     * Agrega entrada al log de sincronizacion.
     */
    private static function addToLog(string $type, string $message, string $level = 'info'): void
    {
        $log = get_option(self::OPTION_SYNC_LOG, []);

        array_unshift($log, [
            'timestamp' => current_time('timestamp'),
            'date' => current_time('Y-m-d H:i:s'),
            'type' => $type,
            'message' => $message,
            'level' => $level
        ]);

        // Limitar tamanho del log
        if (count($log) > self::MAX_LOG_ENTRIES) {
            $log = array_slice($log, 0, self::MAX_LOG_ENTRIES);
        }

        update_option(self::OPTION_SYNC_LOG, $log);
    }

    /**
     * Obtiene el log de sincronizacion.
     * 
     * @param int $limit Numero de entradas a devolver
     * @return array
     */
    public static function getSyncLog(int $limit = 20): array
    {
        $log = get_option(self::OPTION_SYNC_LOG, []);
        return array_slice($log, 0, $limit);
    }

    /**
     * Limpia el log de sincronizacion.
     */
    public static function clearLog(): void
    {
        update_option(self::OPTION_SYNC_LOG, []);
    }
}
