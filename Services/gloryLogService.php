<?php

# Glory/Services/gloryLogService.php

// Asegúrate de que la clase GloryLogger esté disponible, ya sea por autoloader o include/require.
// Si usas un autoloader de Composer, generalmente no necesitas hacer nada aquí.
// Si no, podrías necesitar algo como:
// require_once __DIR__ . '/../Class/GloryLogger.php'; // Ajusta la ruta según tu estructura

add_action('wp_ajax_glory_get_logs', 'glory_ajax_get_logs_handler');

function glory_ajax_get_logs_handler()
{
    // Asumiendo que GloryLogger está en el namespace Glory\Class
    // y has definido las constantes de metadatos allí.
    // Si no, reemplaza \Glory\Class\GloryLogger::META_KEY_CLASS_NAME con la cadena '_glory_log_class_name', etc.
    // Es mejor usar las constantes de la clase para evitar errores tipográficos.

    $log_prefix = "[GloryLog AJAX:" . wp_generate_password(4, false) . "]";

    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');

    error_log("{$log_prefix} ----- Request Start -----");

    if (!check_ajax_referer('glory_logs_nonce', 'nonce', false)) {
        error_log("{$log_prefix} Nonce check FAILED.");
        wp_send_json_error(['message' => 'Nonce verification failed.'], 403);
        return;
    }
    error_log("{$log_prefix} Nonce check PASSED.");

    global $wpdb; // $wpdb no se usa directamente en esta versión adaptada, pero es bueno tenerlo si se necesitara.
    $page               = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $posts_per_page     = 20;
    $last_timestamp     = isset($_POST['last_timestamp']) ? intval($_POST['last_timestamp']) : null;
    $raw_filters_json   = isset($_POST['filters']) ? stripslashes_deep($_POST['filters']) : '[]';
    $filters            = json_decode($raw_filters_json, true);

    error_log("{$log_prefix} Inputs: Page={$page}, LastTimestamp={$last_timestamp}");
    error_log("{$log_prefix} Raw Filters JSON: {$raw_filters_json}");
    error_log("{$log_prefix} Decoded Filters: " . json_encode($filters));

    $args = [
        'post_type'      => \Glory\Class\GloryLogger::CPT_SLUG,
        'posts_per_page' => $posts_per_page,
        'paged'          => $page,
        'orderby'        => 'date', // Ordenar por fecha de creación del post de log
        'order'          => 'DESC',
        'meta_query'     => ['relation' => 'AND'], // Iniciar meta_query con AND
        'date_query'     => [],
    ];

    if ($last_timestamp) {
        $args['date_query'][] = [
            'column'    => 'post_date_gmt',
            'after'     => gmdate('Y-m-d H:i:s', $last_timestamp),
            'inclusive' => false
        ];
        $args['posts_per_page'] = -1;
        $args['order']          = 'ASC'; // Para polling, obtener en orden cronológico
        error_log("{$log_prefix} Date Query (for new logs since {$last_timestamp}) added.");
    }

    // --- INICIO: Lógica de Filtros Adaptada ---

    // Filtrar por Clase(s)
    if (!empty($filters['classes']) && is_array($filters['classes'])) {
        $class_conditions = ['relation' => 'OR']; // Si hay múltiples clases, es un OR entre ellas
        foreach ($filters['classes'] as $class_name) {
            $sanitized_class_name = sanitize_text_field(trim($class_name));
            if (empty($sanitized_class_name)) continue;

            $class_conditions[] = [
                'key'     => \Glory\Class\GloryLogger::META_KEY_CLASS_NAME,
                'value'   => $sanitized_class_name,
                'compare' => '=',
            ];
        }
        // Solo añadir el bloque de clases si contiene condiciones válidas
        if (count($class_conditions) > 1) { // > 1 porque el primero es 'relation' => 'OR'
            $args['meta_query'][] = $class_conditions;
            error_log("{$log_prefix} Added Class filter block: " . json_encode($class_conditions));
        }
    }

    // Filtrar por Método(s)/Función(es)
    if (!empty($filters['methods']) && is_array($filters['methods'])) {
        $method_conditions = ['relation' => 'OR']; // Si hay múltiples métodos, es un OR entre ellos
        foreach ($filters['methods'] as $method_name) {
            $sanitized_method_name = sanitize_text_field(trim($method_name));
            if (empty($sanitized_method_name)) continue;

            $method_conditions[] = [
                'key'     => \Glory\Class\GloryLogger::META_KEY_METHOD_NAME,
                'value'   => $sanitized_method_name,
                'compare' => '=',
            ];
        }
        // Solo añadir el bloque de métodos si contiene condiciones válidas
        if (count($method_conditions) > 1) { // > 1 porque el primero es 'relation' => 'OR'
            $args['meta_query'][] = $method_conditions;
            error_log("{$log_prefix} Added Method filter block: " . json_encode($method_conditions));
        }
    }

    // Filtrar por Nivel (status)
    if (!empty($filters['level']) && $filters['level'] !== 'all') {
        $level_value = sanitize_text_field(strtoupper($filters['level']));
        $args['meta_query'][] = [
            'key'   => \Glory\Class\GloryLogger::META_KEY_STATUS,
            'value' => $level_value,
            'compare' => '=',
        ];
        error_log("{$log_prefix} Added Level filter: {$level_value}");
    }

    // Si meta_query solo tiene 'relation' => 'AND' y no otras condiciones, quitarla.
    if (isset($args['meta_query']['relation']) && count($args['meta_query']) === 1) {
        unset($args['meta_query']);
        error_log("{$log_prefix} meta_query was empty (only relation), so it was UNSET.");
    }
    // --- FIN: Lógica de Filtros Adaptada ---


    error_log("{$log_prefix} Final WP_Query Args: " . json_encode($args));

    $log_sql_query_callback = function ($sql, $query_instance) use ($log_prefix) {
        // Solo loguear si es la query de nuestros logs
        if (isset($query_instance->query_vars['post_type']) && $query_instance->query_vars['post_type'] === \Glory\Class\GloryLogger::CPT_SLUG) {
            $cleaned_sql = preg_replace('/\s+/', ' ', $sql);
            error_log("{$log_prefix} Generated SQL: " . $cleaned_sql);
        }
        return $sql;
    };
    add_filter('posts_request', $log_sql_query_callback, 10, 2);

    $query = new WP_Query($args);

    remove_filter('posts_request', $log_sql_query_callback, 10);

    $logs  = [];
    $newest_log_timestamp_gmt = $last_timestamp ?: 0;

    error_log("{$log_prefix} WP_Query: Found posts = {$query->found_posts}, Post count in current page = {$query->post_count}");

    if ($query->have_posts()) {
        error_log("{$log_prefix} Processing {$query->post_count} posts from query...");
        while ($query->have_posts()) {
            $query->the_post();
            $id    = get_the_ID();
            $ts_gmt = get_post_time('U', true, $id);
            $newest_log_timestamp_gmt = max($newest_log_timestamp_gmt, $ts_gmt);

            // Para la respuesta, seguimos devolviendo 'function' como el nombre completo,
            // pero internamente ya estamos usando los metadatos separados para filtrar.
            // También podríamos añadir class_name y method_name a la respuesta si el frontend los necesita.
            $logs[] = [
                'id'               => $id,
                'title'            => get_the_title(),
                'content'          => nl2br(esc_html(get_the_content())),
                'raw_content'      => get_the_content(),
                'function'         => get_post_meta($id, \Glory\Class\GloryLogger::META_KEY_FUNCTION_FULL, true),
                'class_name'       => get_post_meta($id, \Glory\Class\GloryLogger::META_KEY_CLASS_NAME, true), // Opcional para el frontend
                'method_name'      => get_post_meta($id, \Glory\Class\GloryLogger::META_KEY_METHOD_NAME, true), // Opcional para el frontend
                'status'           => get_post_meta($id, \Glory\Class\GloryLogger::META_KEY_STATUS, true),
                'level_int'        => get_post_meta($id, \Glory\Class\GloryLogger::META_KEY_LEVEL, true), // 'level' es una palabra reservada en WP_Query, usando 'level_int'
                'timestamp'        => get_the_date('Y-m-d H:i:s'),
                'timestamp_gmt'    => $ts_gmt,
            ];
        }
        wp_reset_postdata();
    } else {
        error_log("{$log_prefix} WP_Query: No posts found matching criteria.");
    }

    if ($last_timestamp && !empty($logs)) {
        $logs = array_reverse($logs); // Revertir si es polling para mostrar nuevos primero
        error_log("{$log_prefix} Logs reversed (due to last_timestamp usage).");
    }

    $response_data = [
        'logs'                    => $logs,
        'has_more'                => ($page * $posts_per_page) < $query->found_posts && !$last_timestamp,
        'total'                   => $query->found_posts,
        'newest_log_timestamp_gmt' => $newest_log_timestamp_gmt,
        // 'debug_args' => $args // Descomentar para depuración en frontend
    ];
    error_log("{$log_prefix} Sending Response: " . count($logs) . " logs. HasMore: " . ($response_data['has_more'] ? 'Yes' : 'No') . ". TotalFound: {$query->found_posts}. NewestTimestamp: {$newest_log_timestamp_gmt}");
    error_log("{$log_prefix} ----- Request End -----");

    wp_send_json_success($response_data);
}

/**
 * Obtiene listas únicas de nombres de clases y nombres de métodos/funciones
 * que han sido registrados en los logs, usando los nuevos metadatos.
 */
function get_unique_glory_log_classes_and_methods()
{
    $log_prefix_unique = "[GloryLog Unique]";
    global $wpdb;

    $post_type = \Glory\Class\GloryLogger::CPT_SLUG;

    // Obtener clases únicas
    $class_meta_key = \Glory\Class\GloryLogger::META_KEY_CLASS_NAME;
    error_log("{$log_prefix_unique} Fetching unique CLASS names. Meta Key: '{$class_meta_key}', Post Type: '{$post_type}'");
    $query_classes_sql = $wpdb->prepare("
        SELECT DISTINCT pm.meta_value
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = %s
        AND p.post_type = %s
        AND pm.meta_value IS NOT NULL
        AND pm.meta_value != '' /* Excluir valores vacíos (funciones globales no tienen clase aquí) */
        ORDER BY pm.meta_value ASC
    ", $class_meta_key, $post_type);
    $unique_classes = $wpdb->get_col($query_classes_sql);
    $sample_classes = 'None';
    if ($unique_classes) {
        $sample_classes = implode(', ', array_slice($unique_classes, 0, 5)) . (count($unique_classes) > 5 ? '...' : '');
    }
    error_log("{$log_prefix_unique} DB returned " . count($unique_classes) . " unique CLASS names. Sample: [{$sample_classes}]");


    // Obtener métodos/funciones únicas
    $method_meta_key = \Glory\Class\GloryLogger::META_KEY_METHOD_NAME;
    error_log("{$log_prefix_unique} Fetching unique METHOD names. Meta Key: '{$method_meta_key}', Post Type: '{$post_type}'");
    $query_methods_sql = $wpdb->prepare("
        SELECT DISTINCT pm.meta_value
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key = %s
        AND p.post_type = %s
        AND pm.meta_value IS NOT NULL
        AND pm.meta_value != ''
        ORDER BY pm.meta_value ASC
    ", $method_meta_key, $post_type);
    $unique_methods = $wpdb->get_col($query_methods_sql);
    $sample_methods = 'None';
    if ($unique_methods) {
        $sample_methods = implode(', ', array_slice($unique_methods, 0, 5)) . (count($unique_methods) > 5 ? '...' : '');
    }
    error_log("{$log_prefix_unique} DB returned " . count($unique_methods) . " unique METHOD names. Sample: [{$sample_methods}]");

    // No se necesita más parseo aquí, los metadatos ya están separados.
    $final_classes = $unique_classes ?: [];
    sort($final_classes);
    $final_methods = $unique_methods ?: [];
    sort($final_methods);

    error_log("{$log_prefix_unique} Final Unique Classes (" . count($final_classes) . "): " . (!empty($final_classes) ? implode(', ', $final_classes) : 'None'));
    error_log("{$log_prefix_unique} Final Unique Methods (" . count($final_methods) . "): " . (!empty($final_methods) ? implode(', ', $final_methods) : 'None'));

    return [$final_classes, $final_methods];
}