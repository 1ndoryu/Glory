<?php

namespace Glory\Services;

use Glory\Core\GloryFeatures;

/**
 * Perfilador de consultas SQL para WordPress.
 * - Muestra un widget flotante con el listado de consultas y sus tiempos.
 * - Puede escribir un resumen (Top 10 más lentas) al log por petición si está activado.
 * - Agnóstico del tema. Activo por defecto sólo en modo desarrollo, salvo que el tema lo desactive explícitamente.
 */
class QueryProfiler
{
    /**
     * Evita doble inyección de datos en un mismo request.
     */
    private static bool $dataInjected = false;

    /**
     * Log interno controlado por la opción/feature 'queryProfilerLogs'.
     */
    private static function debugLog(string $message): void
    {
        // Logs desactivados por defecto. Para reactivar, eliminar este return
        return;
    }
    /**
     * Hook de arranque. Debe llamarse temprano durante la carga del framework.
     */
    public static function init(): void
    {
        // Activo sólo si la feature está activa por código o por opción del panel.
        // Por defecto: apagado en prod; en dev, el código puede habilitarlo sin tocar la opción.
        $activo = GloryFeatures::isActive('queryProfiler', 'glory_query_profiler_activado', false);
        self::debugLog('init: featureActive=' . ($activo ? '1' : '0') . ' area=' . (is_admin() ? 'admin' : 'front'));
        if (!$activo) {
            return;
        }

        // Asegurar que WordPress acumule las consultas en $wpdb->queries
        if (!defined('SAVEQUERIES')) {
            define('SAVEQUERIES', true);
        }

        // Encolar assets (fallback directo por si el AssetManager no los encola a tiempo)
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets'], 50);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets'], 50);

        // Inyectar datos lo más tarde posible (footer) para capturar consultas tardías
        add_action('wp_print_footer_scripts', [self::class, 'injectData'], 1);
        add_action('admin_print_footer_scripts', [self::class, 'injectData'], 1);

        // Logging opcional: Top 10 más lentas, una entrada por petición
        add_action('shutdown', [self::class, 'logSummaryIfEnabled'], 1000);
    }

    /**
     * Construye el arreglo de consultas ordenado por duración descendente.
     * Incluye toda la lista para UI; el log resumirá a Top 10.
     *
     * @return array
     */
    private static function buildQueriesData(): array
    {
        global $wpdb;
        $queries = isset($wpdb->queries) && is_array($wpdb->queries) ? $wpdb->queries : [];

        $result = [];
        foreach ($queries as $q) {
            // Formato estándar de SAVEQUERIES: [sql, time, caller]
            $sql    = $q[0] ?? '';
            $time   = (float) ($q[1] ?? 0);
            $caller = $q[2] ?? '';
            $result[] = [
                'sql'    => $sql,
                'time'   => $time,
                'caller' => $caller,
            ];
        }

        usort($result, static function ($a, $b) {
            if ($a['time'] === $b['time']) return 0;
            return ($a['time'] > $b['time']) ? -1 : 1;
        });

        return $result;
    }

    /**
     * Inyecta datos en el handle del script del perfilador, disponible tanto en front como en admin.
     * Se usa inline script para datos dinámicos por petición.
     */
    public static function injectData(): void
    {
        if (self::$dataInjected) {
            return;
        }
        self::debugLog('injectData: hook=' . current_action() . ' area=' . (is_admin() ? 'admin' : 'front'));
        // El asset se registra en Glory/Config/scriptSetup.php con handle 'glory-query-profiler'
        if (!wp_script_is('glory-query-profiler', 'enqueued')) {
            // Intentar encolarlo si está registrado (para cubrir orden de hooks)
            if (wp_script_is('glory-query-profiler', 'registered')) {
                wp_enqueue_script('glory-query-profiler');
            }
        }
        if (!wp_style_is('glory-query-profiler', 'enqueued')) {
            if (wp_style_is('glory-query-profiler', 'registered')) {
                wp_enqueue_style('glory-query-profiler');
            }
        }
        self::debugLog('injectData: script_enqueued=' . (wp_script_is('glory-query-profiler', 'enqueued') ? '1' : '0') . ' style_enqueued=' . (wp_style_is('glory-query-profiler', 'enqueued') ? '1' : '0'));
        if (!wp_script_is('glory-query-profiler', 'enqueued')) {
            return;
        }

        $queries = self::buildQueriesData();
        $sum = 0.0;
        foreach ($queries as $row) {
            $sum += (float) ($row['time'] ?? 0);
        }
        $data = [
            'enabled'     => true,
            'queries'     => $queries,
            'total'       => get_num_queries(),
            // Suma de tiempos de consultas (preferido para UI)
            'total_time'  => round($sum, 6),
            // Tiempo total de WordPress como respaldo
            'wp_time'     => (float) timer_stop(0),
            'environment' => (defined('WP_DEBUG') && WP_DEBUG) ? 'dev' : 'prod',
        ];

        $json = wp_json_encode($data);
        if ($json === false) {
            return;
        }

        // Poner los datos antes del script para que estén disponibles al cargarlo
        wp_add_inline_script('glory-query-profiler', 'window.GloryQueryProfilerData = ' . $json . ';', 'before');
        self::$dataInjected = true;
        self::debugLog('injectData: inlineData added with ' . count($data['queries']) . ' queries.');
    }

    /**
     * Fallback de encolado directo que no depende del AssetManager.
     * Se ejecuta temprano para asegurar que los handles existan en este ciclo.
     */
    public static function enqueueAssets(): void
    {
        self::debugLog('enqueueAssets: hook=' . current_action() . ' area=' . (is_admin() ? 'admin' : 'front'));
        $styleHandle = 'glory-query-profiler';
        $scriptHandle = 'glory-query-profiler';

        if (!wp_style_is($styleHandle, 'registered') && !wp_style_is($styleHandle, 'enqueued')) {
            $cssPath = get_template_directory() . '/Glory/assets/css/query-profiler.css';
            $cssUrl  = get_template_directory_uri() . '/Glory/assets/css/query-profiler.css';
            if (file_exists($cssPath)) {
                wp_register_style($styleHandle, $cssUrl, [], filemtime($cssPath));
            }
        }
        if (wp_style_is($styleHandle, 'registered') && !wp_style_is($styleHandle, 'enqueued')) {
            wp_enqueue_style($styleHandle);
        }

        if (!wp_script_is($scriptHandle, 'registered') && !wp_script_is($scriptHandle, 'enqueued')) {
            $jsPath = get_template_directory() . '/Glory/assets/js/query-profiler.js';
            $jsUrl  = get_template_directory_uri() . '/Glory/assets/js/query-profiler.js';
            if (file_exists($jsPath)) {
                wp_register_script($scriptHandle, $jsUrl, ['jquery'], filemtime($jsPath), true);
            }
        }
        if (wp_script_is($scriptHandle, 'registered') && !wp_script_is($scriptHandle, 'enqueued')) {
            wp_enqueue_script($scriptHandle);
        }
        self::debugLog('enqueueAssets: script_registered=' . (wp_script_is($scriptHandle, 'registered') ? '1' : '0') . ' style_registered=' . (wp_style_is($styleHandle, 'registered') ? '1' : '0'));
        self::debugLog('enqueueAssets: enqueued script=' . (wp_script_is($scriptHandle, 'enqueued') ? '1' : '0') . ' style=' . (wp_style_is($styleHandle, 'enqueued') ? '1' : '0'));
    }

    /**
     * Si el logging del perfilador está habilitado, escribe un resumen Top 10 al log.
     */
    public static function logSummaryIfEnabled(): void
    {
        if (!GloryFeatures::isActive('queryProfilerLogs', null, false)) {
            return;
        }

        $entries = self::buildQueriesData();
        if (empty($entries)) {
            return;
        }

        $top = array_slice($entries, 0, 10);
        $lines = [];
        $lines[] = '=== Glory QueryProfiler (Top 10 por duración) ===';
        $lines[] = sprintf('Total consultas: %d | Tiempo total WP: %ss', (int) get_num_queries(), (string) timer_stop(0));
        foreach ($top as $idx => $row) {
            $lines[] = sprintf(
                '#%02d | %0.6fs | %s',
                $idx + 1,
                $row['time'],
                self::sanitizeOneLineSql($row['sql'])
            );
        }
        $lines[] = '===============================================';

        error_log(implode("\n", $lines));
    }

    /**
     * Normaliza SQL a una sola línea para el log.
     */
    private static function sanitizeOneLineSql(string $sql): string
    {
        $sql = preg_replace('/\s+/', ' ', $sql);
        return trim((string) $sql);
    }
}


