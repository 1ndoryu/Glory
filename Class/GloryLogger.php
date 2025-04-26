<?php
# App/Glory/Class/GloryLogger.php // Asegúrate que la ruta y namespace coincidan

namespace Glory\Class; // Asegúrate que este namespace es correcto para tu estructura

class GloryLogger
{
    const CPT_SLUG = 'glory_log';
    const LEVEL_INFO = 10;
    const LEVEL_ERROR = 20;
    private static $minSaveLevel = self::LEVEL_ERROR;
    private static $logBuffer = [];
    private static $saveLogsHookRegistered = false;

    public static function init(?int $level = null): void
    {
        if (!is_null($level) && in_array($level, [self::LEVEL_INFO, self::LEVEL_ERROR], true)) {
            self::$minSaveLevel = $level;
        }
        add_action('init', [self::class, 'registerCpt'], 0);
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
            // Usar error_log() aquí es seguro, ya que el CPT podría no estar listo
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

    /**
     * Logs a message if it's not an exact duplicate within the same request for the same caller.
     *
     * @param int    $level   The log level (e.g., self::LEVEL_INFO, self::LEVEL_ERROR).
     * @param string $message The log message.
     * @param array  $context Optional context data associated with the log entry.
     */
    private static function log(int $level, string $message, array $context = []): void
    {
        $caller = self::getCallerFunction();
        if (!$caller) {
            // Podríamos loguear un error interno aquí si falla la obtención del caller
            // error_log('[GloryLogger Internal] Could not determine caller function.');
            return; // Salir si no podemos identificar quién llama
        }

        // 1. Generar una "huella digital" única para esta entrada de log específica.
        // Usamos serialize para el contexto, ya que maneja estructuras PHP más complejas que json_encode.
        // Concatenamos nivel, mensaje y contexto serializado para crear la huella.
        $logFingerprint = md5($level . '|' . $message . '|' . serialize($context));

        // 2. Inicializar las estructuras del buffer si no existen para este caller.
        if (!isset(self::$logBuffer[$caller])) {
            self::$logBuffer[$caller] = [
                'messages'      => [],
                'max_level'     => self::LEVEL_INFO, // Nivel inicial por defecto
                'unique_hashes' => [],
            ];
        }

        // 3. Comprobar si esta entrada exacta ya ha sido bufferizada *para este caller* en *esta petición*.
        if (isset(self::$logBuffer[$caller]['unique_hashes'][$logFingerprint])) {
            // Duplicado detectado dentro de la misma petición para la misma función. Ignorar.
            // Opcional: Añadir un log de depuración si es necesario durante el desarrollo
            // error_log("[GloryLogger Debug] Duplicate log skipped for $caller: Level $level - $message");
            return; // <-- Evita añadir el duplicado al buffer
        }

        // 4. Si no es un duplicado, añadir el hash para prevenir futuros duplicados (dentro de esta petición).
        self::$logBuffer[$caller]['unique_hashes'][$logFingerprint] = true; // Marcar este hash como visto

        // 5. Añadir los detalles del mensaje de log al buffer.
        $timestamp = time();
        self::$logBuffer[$caller]['messages'][] = [
            'timestamp' => $timestamp,
            'level'     => $level,
            'message'   => $message,
            'context'   => $context
        ];

        // 6. Actualizar el nivel máximo encontrado para este caller.
        if ($level > self::$logBuffer[$caller]['max_level']) {
            self::$logBuffer[$caller]['max_level'] = $level;
        }

        // 7. Asegurar que el hook 'shutdown' está registrado para guardar los logs al final.
        self::registerSaveLogsHook();
    }


    private static function getCallerFunction(): ?string
    {
        // Aumentamos ligeramente el límite para asegurarnos de saltar las llamadas internas de GloryLogger
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5); // Ajustado a 5 por si acaso

        // Buscamos la primera llamada que NO sea desde dentro de esta clase
        for ($i = 2; $i < count($trace); $i++) { // Empezar desde índice 2 (saltando log() y info()/error())
            if (isset($trace[$i]['class']) && $trace[$i]['class'] === self::class) {
                continue; // Saltar llamadas internas como log() -> info() o error()
            }

            // Encontrada la función/método externo que llamó a GloryLogger
            if (isset($trace[$i])) {
                 $caller_info = $trace[$i];
                 $function = $caller_info['function'] ?? '[unknown_function]';
                 if (isset($caller_info['class'])) {
                     $function = $caller_info['class'] . '::' . $function;
                 }
                 // Opcional: añadir línea y fichero si es útil
                 // $file = $caller_info['file'] ?? '[unknown_file]';
                 // $line = $caller_info['line'] ?? '[unknown_line]';
                 // return "{$function} ({$file}:{$line})";
                 return $function;
            }
        }

        // Si no se encuentra un llamador externo adecuado (difícil, pero posible)
        return '[unknown_caller]';
    }


    /**
     * Registra el hook 'shutdown' de WordPress si no se ha hecho ya.
     */
    private static function registerSaveLogsHook(): void
    {
        if (!self::$saveLogsHookRegistered) {
            add_action('shutdown', [self::class, 'saveBufferedLogs'], PHP_INT_MAX); // Prioridad alta para ejecutar tarde
            self::$saveLogsHookRegistered = true;
        }
    }

    /**
     * Procesa los logs almacenados en el búfer al final de la ejecución de la petición.
     * Guarda los logs como CPTs basándose en el nivel mínimo configurado.
     * @internal Llamado vía el hook 'shutdown' de WordPress. No llamar directamente.
     */
    public static function saveBufferedLogs(): void
    {
        if (empty(self::$logBuffer)) {
            return;
        }

        foreach (self::$logBuffer as $functionName => $logData) {
            $maxLevelEncountered = $logData['max_level'];

            // Comprobar si hay mensajes que guardar para este llamador
            if (empty($logData['messages'])) {
                continue; // No hay mensajes (podría pasar si solo se loguearon duplicados)
            }

            // Comprobar si el nivel máximo alcanzado justifica guardar el log
            if ($maxLevelEncountered >= self::$minSaveLevel) {
                // Ahora llamamos a createLogPost solo si se cumple el nivel mínimo
                self::createLogPost($functionName, $logData);
            } else {
                // Opcional: Log de depuración si el nivel no se alcanzó
                // if ($maxLevelEncountered === self::LEVEL_ERROR) { // Ejemplo: loguear si era error pero no se guardó
                //    error_log('[GloryLogger Debug] saveBufferedLogs() - Level threshold NOT met for ' . $functionName . '. Max level was ' . $maxLevelEncountered . ', min save level is ' . self::$minSaveLevel . '. Skipping post creation.');
                // }
            }
        }

        // Limpiar el búfer y resetear la bandera para la siguiente petición
        self::$logBuffer = [];
        self::$saveLogsHookRegistered = false;
    }

    /**
     * Crea una entrada CPT para los mensajes de log de una función.
     * (Sin cambios necesarios en esta función interna)
     *
     * @param string $functionName El nombre de la función/método.
     * @param array $logData Los datos bufferizados para esta función (incluye 'messages' y 'max_level').
     */
    private static function createLogPost(string $functionName, array $logData): void
    {
        // Asegurarse que hay mensajes antes de proceder (aunque saveBufferedLogs ya debería filtrarlo)
        if (empty($logData['messages'])) {
            error_log('[GloryLogger Internal] createLogPost called for ' . $functionName . ' with empty messages.');
            return;
        }

        $startTime = $logData['messages'][0]['timestamp'] ?? time(); // Usar el timestamp del primer mensaje
        $maxLevel = $logData['max_level'];
        $status = ($maxLevel === self::LEVEL_ERROR) ? 'ERROR' : 'INFO';

        // Título más informativo: incluye estado, función y fecha/hora del primer evento
        $post_title = sprintf(
            '[%s] %s - %s',
            $status,
            $functionName,
            wp_date('Y-m-d H:i:s', $startTime) // Usar wp_date para localización/zona horaria de WP
        );

        // Construcción del contenido del post
        $post_content = "Log entries for function/method: {$functionName}\n";
        $post_content .= "Overall Execution Status based on logs: {$status}\n";
        $post_content .= "--------------------------------------------------\n\n";

        foreach ($logData['messages'] as $entry) {
            $levelStr = ($entry['level'] === self::LEVEL_ERROR) ? 'ERROR' : 'INFO';
            $post_content .= sprintf(
                "[%s] [%s] %s\n",
                wp_date('H:i:s.v', $entry['timestamp']), // Añadir milisegundos si PHP/WP lo soporta y es útil
                $levelStr,
                esc_html($entry['message']) // Escapar HTML en el mensaje
            );
            if (!empty($entry['context'])) {
                // Usar print_r con retorno true y escapar HTML para seguridad
                $contextStr = print_r($entry['context'], true);
                $post_content .= "  Context: " . esc_html($contextStr) . "\n";
            }
            $post_content .= "\n"; // Espacio entre entradas
        }

        $post_data = [
            'post_title'   => $post_title,
            'post_content' => $post_content,
            'post_status'  => 'publish', // Publicar directamente para que sea visible en el admin
            'post_type'    => self::CPT_SLUG,
            'post_author'  => 0, // O asignar un usuario específico si se desea (p.ej., get_current_user_id() si aplica)
        ];

        // Desactivar filtros/acciones que podrían interferir es una buena práctica defensiva
        $current_filter_level = did_action('wp_insert_post_data'); // O alguna heurística si es necesario
        if ($current_filter_level < 10) { // Evitar recursión infinita si un filtro llama al logger
            remove_all_filters('wp_insert_post_data');
            remove_all_filters('wp_insert_post');
            // Podrías querer quitar acciones específicas también si causan problemas
        }

        $postId = wp_insert_post($post_data, true); // El segundo argumento true devuelve WP_Error en caso de fallo

        if (!is_wp_error($postId) && $postId > 0) {
            // Guardar metadatos útiles para filtrado/ordenación si es necesario
            update_post_meta($postId, '_glory_log_function', $functionName);
            update_post_meta($postId, '_glory_log_status', $status);
            update_post_meta($postId, '_glory_log_level', $maxLevel);
            update_post_meta($postId, '_glory_log_timestamp', $startTime); // Guardar timestamp del inicio
            // Podrías añadir más metadatos si fueran útiles (ej. URL, usuario, etc.)
        } else {
            // Falló la creación del post, registrar en el log de errores de PHP
            $error_message = is_wp_error($postId) ? $postId->get_error_message() : 'Unknown error (Invalid Post ID returned: ' . print_r($postId, true) . ')';
            error_log('[GloryLogger] createLogPost() - FAILED to create log post for ' . $functionName . '. Error: ' . $error_message);
        }
    }

} // Fin de la clase GloryLogger