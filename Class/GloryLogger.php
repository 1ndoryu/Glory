<?php
# App/Glory/Class/GloryLogger.php // Asegúrate que la ruta y namespace coincidan

namespace Glory\Class; // Asegúrate que este namespace es correcto para tu estructura

class GloryLogger
{
    const CPT_SLUG = 'glory_log';
    const LEVEL_INFO = 10;
    const LEVEL_WARNING = 20; 
    const LEVEL_ERROR = 30;
    const LEVEL_CRITICAL = 50; 
    private static $minSaveLevel = self::LEVEL_ERROR;
    private static $logBuffer = [];
    private static $saveLogsHookRegistered = false;

    // NUEVAS CONSTANTES PARA METADATOS
    const META_KEY_FUNCTION_FULL = '_glory_log_function'; // Mantener el original por compatibilidad o si se quiere
    const META_KEY_CLASS_NAME = '_glory_log_class_name';
    const META_KEY_METHOD_NAME = '_glory_log_method_name';
    const META_KEY_STATUS = '_glory_log_status';
    const META_KEY_LEVEL = '_glory_log_level';
    const META_KEY_TIMESTAMP = '_glory_log_timestamp';

    // CONSTANTES PARA LÍMITES DE LOGS
    const MAX_INFO_LOGS = 300;
    const MAX_ERROR_LOGS = 100;


    public static function init(?int $level = null): void
    {
        if (!is_null($level) && in_array($level, [self::LEVEL_INFO, self::LEVEL_ERROR], true)) {
            self::$minSaveLevel = $level;
        }
        add_action('init', [self::class, 'registerCpt'], 0);
        // Hook para la limpieza periódica de logs
        if (!wp_next_scheduled('glory_log_cleanup_hook')) {
            wp_schedule_event(time(), 'daily', 'glory_log_cleanup_hook'); // Puedes usar 'hourly' o 'twicedaily'
        }
        add_action('glory_log_cleanup_hook', [self::class, 'cleanupOldLogs']);
    }

    public static function registerCpt(): void
    {
        if (post_type_exists(self::CPT_SLUG)) {
           return;
        }

        $labels = [ /* ... (labels como antes) ... */
            'name'               => _x('Glory Logs', 'post type general name', 'glory'),
            'singular_name'      => _x('Glory Log', 'post type singular name', 'glory'),
            'menu_name'          => _x('Glory Logs', 'admin menu', 'glory'),
            'name_admin_bar'     => _x('Glory Log', 'add new on admin bar', 'glory'),
            'add_new'            => _x('Add New', 'log', 'glory'),
            'add_new_item'       => __('Add New Log', 'glory'),
            'new_item'           => __('New Log', 'glory'),
            'edit_item'          => __('Edit Log', 'glory'),
            'view_item'          => __('View Log', 'glory'),
            'all_items'          => __('All Glory Logs', 'glory'),
            'search_items'       => __('Search Logs', 'glory'),
            'parent_item_colon'  => __('Parent Logs:', 'glory'),
            'not_found'          => __('No logs found.', 'glory'),
            'not_found_in_trash' => __('No logs found in Trash.', 'glory')
        ];
        $args = [ /* ... (args como antes, asegurando show_ui => true) ... */
            'labels'             => $labels,
            'description'        => __('Stores execution logs from the Glory Framework.', 'glory'),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'tools.php', // O donde prefieras mostrarlo
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'map_meta_cap'       => true, // Importante para permisos
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 80,
            'supports'           => ['title', 'editor', 'custom-fields'], // Editor para el contenido, custom-fields para metadatos
            'show_in_rest'       => false, // Generalmente no necesario para logs internos
        ];

        $result = register_post_type(self::CPT_SLUG, $args);

        if (is_wp_error($result)) {
            error_log('[GloryLogger] registerCpt() - FAILED to register CPT! WP_Error: ' . $result->get_error_message());
        }
    }

    public static function setMinSaveLevel(int $level): void
    {
        if (in_array($level, [self::LEVEL_INFO, self::LEVEL_ERROR], true)) {
            self::$minSaveLevel = $level;
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::log(self::LEVEL_INFO, $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log(self::LEVEL_ERROR, $message, $context);
    }

    private static function log(int $level, string $message, array $context = []): void
    {
        $caller = self::getCallerFunction();
        if (!$caller) {
            return;
        }
        $logFingerprint = md5($level . '|' . $message . '|' . serialize($context));
        if (!isset(self::$logBuffer[$caller])) {
            self::$logBuffer[$caller] = [
                'messages'      => [],
                'max_level'     => self::LEVEL_INFO,
                'unique_hashes' => [],
            ];
        }
        if (isset(self::$logBuffer[$caller]['unique_hashes'][$logFingerprint])) {
            return;
        }
        self::$logBuffer[$caller]['unique_hashes'][$logFingerprint] = true;
        $timestamp = time(); // Podrías usar microtime(true) para más precisión si es necesario
        self::$logBuffer[$caller]['messages'][] = [
            'timestamp' => $timestamp,
            'level'     => $level,
            'message'   => $message,
            'context'   => $context
        ];
        if ($level > self::$logBuffer[$caller]['max_level']) {
            self::$logBuffer[$caller]['max_level'] = $level;
        }
        self::registerSaveLogsHook();
    }

    private static function getCallerFunction(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        for ($i = 2; $i < count($trace); $i++) {
            if (isset($trace[$i]['class']) && $trace[$i]['class'] === self::class) {
                continue;
            }
            if (isset($trace[$i])) {
                 $caller_info = $trace[$i];
                 $function = $caller_info['function'] ?? '[unknown_function]';
                 if (isset($caller_info['class'])) {
                     $function = $caller_info['class'] . '::' . $function;
                 }
                 return $function;
            }
        }
        return '[unknown_caller]';
    }

    private static function registerSaveLogsHook(): void
    {
        if (!self::$saveLogsHookRegistered) {
            add_action('shutdown', [self::class, 'saveBufferedLogs'], PHP_INT_MAX);
            self::$saveLogsHookRegistered = true;
        }
    }

    public static function saveBufferedLogs(): void
    {
        if (empty(self::$logBuffer)) {
            return;
        }
        foreach (self::$logBuffer as $functionName => $logData) {
            $maxLevelEncountered = $logData['max_level'];
            if (empty($logData['messages'])) {
                continue;
            }
            if ($maxLevelEncountered >= self::$minSaveLevel) {
                self::createLogPost($functionName, $logData);
            }
        }
        self::$logBuffer = [];
        self::$saveLogsHookRegistered = false;
    }

    private static function createLogPost(string $functionName, array $logData): void
    {
        if (empty($logData['messages'])) {
            error_log('[GloryLogger Internal] createLogPost called for ' . $functionName . ' with empty messages.');
            return;
        }

        $startTime = $logData['messages'][0]['timestamp'] ?? time();
        $maxLevel = $logData['max_level'];
        $status = ($maxLevel === self::LEVEL_ERROR) ? 'ERROR' : 'INFO';

        $post_title = sprintf(
            '[%s] %s - %s',
            $status,
            $functionName,
            wp_date('Y-m-d H:i:s', $startTime)
        );

        $post_content = "Log entries for function/method: {$functionName}\n";
        $post_content .= "Overall Execution Status based on logs: {$status}\n";
        $post_content .= "--------------------------------------------------\n\n";

        foreach ($logData['messages'] as $entry) {
            $levelStr = ($entry['level'] === self::LEVEL_ERROR) ? 'ERROR' : 'INFO';
            $post_content .= sprintf(
                "[%s] [%s] %s\n",
                wp_date('H:i:s', $entry['timestamp']) . '.' . substr(sprintf('%03d', ($entry['timestamp'] - floor($entry['timestamp'])) * 1000), 0, 3), // Intento de milisegundos
                $levelStr,
                esc_html($entry['message'])
            );
            if (!empty($entry['context'])) {
                $contextStr = print_r($entry['context'], true);
                $post_content .= "  Context: " . esc_html($contextStr) . "\n";
            }
            $post_content .= "\n";
        }

        $post_data = [
            'post_title'   => $post_title,
            'post_content' => $post_content,
            'post_status'  => 'publish',
            'post_type'    => self::CPT_SLUG,
            'post_author'  => 0,
        ];

        // Desactivar filtros/acciones para evitar interferencias
        $current_filter_level_data = did_action('wp_insert_post_data');
        $current_filter_level_post = did_action('wp_insert_post');

        if ($current_filter_level_data < 10) remove_all_filters('wp_insert_post_data');
        if ($current_filter_level_post < 10) remove_all_filters('wp_insert_post');


        $postId = wp_insert_post($post_data, true);

        if (!is_wp_error($postId) && $postId > 0) {
            // --- INICIO: Nuevos metadatos para clase y método ---
            $className = '';
            $methodName = $functionName; // Por defecto, es una función global

            if (strpos($functionName, '::') !== false) {
                list($className, $methodName) = explode('::', $functionName, 2);
            }

            update_post_meta($postId, self::META_KEY_FUNCTION_FULL, $functionName);
            update_post_meta($postId, self::META_KEY_CLASS_NAME, $className); // Puede estar vacío si es función global
            update_post_meta($postId, self::META_KEY_METHOD_NAME, $methodName);
            // --- FIN: Nuevos metadatos ---

            update_post_meta($postId, self::META_KEY_STATUS, $status);
            update_post_meta($postId, self::META_KEY_LEVEL, $maxLevel);
            update_post_meta($postId, self::META_KEY_TIMESTAMP, $startTime);
        } else {
            $error_message = is_wp_error($postId) ? $postId->get_error_message() : 'Unknown error (Invalid Post ID returned: ' . print_r($postId, true) . ')';
            error_log('[GloryLogger] createLogPost() - FAILED to create log post for ' . $functionName . '. Error: ' . $error_message);
        }
    }

    /**
     * Limpia logs antiguos para mantener solo un número específico de entradas INFO y ERROR.
     * Se ejecuta a través de un WP Cron job.
     */
    public static function cleanupOldLogs(): void
    {
        error_log('[GloryLogger Cron] cleanupOldLogs() - Iniciando limpieza de logs antiguos.');

        // Limpiar logs de INFO
        self::trimLogsByStatus('INFO', self::MAX_INFO_LOGS);

        // Limpiar logs de ERROR
        self::trimLogsByStatus('ERROR', self::MAX_ERROR_LOGS);

        error_log('[GloryLogger Cron] cleanupOldLogs() - Limpieza completada.');
    }

    /**
     * Helper para eliminar logs que exceden el límite para un estado específico.
     *
     * @param string $status ('INFO' o 'ERROR')
     * @param int $limit El número máximo de logs a retener para este estado.
     */
    private static function trimLogsByStatus(string $status_to_trim, int $limit): void
    {
        global $wpdb;

        // Contar cuántos logs de este estado existen
        $query_count = new \WP_Query([
            'post_type'      => self::CPT_SLUG,
            'post_status'    => 'publish',
            'posts_per_page' => 1, // Solo necesitamos el conteo
            'meta_query'     => [
                [
                    'key'   => self::META_KEY_STATUS,
                    'value' => $status_to_trim,
                ]
            ],
            'fields'         => 'ids', // Solo obtener IDs es más eficiente para contar
        ]);

        $total_logs_for_status = $query_count->found_posts;
        error_log("[GloryLogger Cron] cleanupOldLogs() - Estado: {$status_to_trim}. Total actual: {$total_logs_for_status}. Límite: {$limit}.");


        if ($total_logs_for_status > $limit) {
            $posts_to_delete_count = $total_logs_for_status - $limit;
            error_log("[GloryLogger Cron] cleanupOldLogs() - Estado: {$status_to_trim}. Necesita eliminar {$posts_to_delete_count} logs.");

            // Obtener los IDs de los posts más antiguos de este estado para eliminar
            $query_oldest = new \WP_Query([
                'post_type'      => self::CPT_SLUG,
                'post_status'    => 'publish',
                'posts_per_page' => $posts_to_delete_count,
                'orderby'        => 'date', // Ordenar por fecha de post
                'order'          => 'ASC',  // Los más antiguos primero
                'meta_query'     => [
                    [
                        'key'   => self::META_KEY_STATUS,
                        'value' => $status_to_trim,
                    ]
                ],
                'fields'         => 'ids', // Solo necesitamos los IDs para eliminar
            ]);

            if (!empty($query_oldest->posts)) {
                $deleted_count = 0;
                foreach ($query_oldest->posts as $post_id_to_delete) {
                    $deleted = wp_delete_post($post_id_to_delete, true); // true para forzar la eliminación (saltar papelera)
                    if ($deleted) {
                        $deleted_count++;
                    } else {
                        error_log("[GloryLogger Cron] cleanupOldLogs() - Estado: {$status_to_trim}. No se pudo eliminar el post ID: {$post_id_to_delete}.");
                    }
                }
                error_log("[GloryLogger Cron] cleanupOldLogs() - Estado: {$status_to_trim}. Se eliminaron {$deleted_count} de {$posts_to_delete_count} logs.");
            } else {
                 error_log("[GloryLogger Cron] cleanupOldLogs() - Estado: {$status_to_trim}. No se encontraron posts para eliminar (query_oldest vacía), aunque el conteo inicial era mayor al límite. Esto podría ser una condición de carrera o un problema de query.");
            }
        } else {
             error_log("[GloryLogger Cron] cleanupOldLogs() - Estado: {$status_to_trim}. No se necesita eliminar logs.");
        }
    }

} // Fin de la clase GloryLogger

// Para activar el cron la primera vez si el plugin se activa
// register_activation_hook(__FILE__, function() { // Reemplaza __FILE__ con la ruta real a tu archivo de plugin principal
// if (!wp_next_scheduled('glory_log_cleanup_hook')) {
// wp_schedule_event(time(), 'daily', 'glory_log_cleanup_hook');
// }
// });

// Para desactivar el cron si el plugin se desactiva
// register_deactivation_hook(__FILE__, function() { // Reemplaza __FILE__ con la ruta real
// wp_clear_scheduled_hook('glory_log_cleanup_hook');
// });