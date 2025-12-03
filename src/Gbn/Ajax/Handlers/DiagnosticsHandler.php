<?php

namespace Glory\Gbn\Ajax\Handlers;

use Glory\Gbn\Config\ContainerRegistry;

class DiagnosticsHandler
{
    public static function dump()
    {
        // Verificar nonce y permisos
        check_ajax_referer('glory_gbn_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $componentFilter = isset($_REQUEST['component']) ? sanitize_text_field($_REQUEST['component']) : null;

        // Recopilar datos del sistema
        $registry = ContainerRegistry::all();
        $schemas = ContainerRegistry::rolePayload();
        $traits = \Glory\Gbn\Diagnostics\SystemMapper::getTraitMap();

        // Filtrar si se solicita un componente específico
        if ($componentFilter) {
            if (isset($registry[$componentFilter])) {
                $registry = [$componentFilter => $registry[$componentFilter]];
                // Filtrar schemas (la estructura es ['role' => ...])
                $schemas = isset($schemas[$componentFilter]) ? [$componentFilter => $schemas[$componentFilter]] : [];
                // Filtrar traits
                $traits = isset($traits[$componentFilter]) ? [$componentFilter => $traits[$componentFilter]] : [];
            } else {
                wp_send_json_error(['message' => "Componente '{$componentFilter}' no encontrado"]);
            }
        }

        $data = [
            'components' => $registry,
            'themeSettings' => get_option('gbn_theme_settings', []),
            'traits' => $traits,
            'payload' => [
                'schemas' => $schemas
            ],
            'timestamp' => current_time('mysql'),
            'version' => wp_get_theme()->get('Version'),
            'php_version' => phpversion(),
            'memory_limit' => ini_get('memory_limit'),
        ];

        wp_send_json_success($data);
    }

    public static function validate()
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $issues = [];
        $score = 100;

        // 1. Load Data
        $registry = ContainerRegistry::all(); // ['role' => [...]]
        $schemas = ContainerRegistry::rolePayload(); // ['role' => ['schema' => [...], 'config' => ...]]
        $themeSettings = get_option('gbn_theme_settings', []);

        // 2. Validate Theme Settings (Orphans)
        if (isset($themeSettings['components']) && is_array($themeSettings['components'])) {
            foreach ($themeSettings['components'] as $role => $settings) {
                // Check if Component exists
                if (!isset($registry[$role])) {
                    $issues[] = [
                        'type' => 'orphan_component',
                        'severity' => 'medium',
                        'message' => "Componente '{$role}' existe en DB pero no está registrado en el sistema.",
                        'context' => $role
                    ];
                    $score -= 10;
                    continue;
                }

                // Check Fields
                if (is_array($settings)) {
                    $roleSchema = $schemas[$role]['schema'] ?? [];
                    // Create map of valid field IDs
                    $validFields = [];
                    foreach ($roleSchema as $field) {
                        if (isset($field['id'])) $validFields[$field['id']] = true;
                    }

                    foreach ($settings as $key => $val) {
                        if (!isset($validFields[$key])) {
                            $issues[] = [
                                'type' => 'orphan_field',
                                'severity' => 'low',
                                'message' => "Campo '{$key}' en componente '{$role}' es huérfano (no existe en schema).",
                                'context' => "{$role}.{$key}"
                            ];
                            $score -= 2;
                        }
                    }
                }
            }
        }

        // 3. Validate Schema Integrity
        foreach ($registry as $role => $data) {
            if (!isset($schemas[$role])) {
                $issues[] = [
                    'type' => 'missing_schema',
                    'severity' => 'high',
                    'message' => "Componente '{$role}' registrado pero sin payload de schema generado.",
                    'context' => $role
                ];
                $score -= 15;
            }

            // 4. Validate DOM Observability (Sync Rule)
            // Regla: Todo componente debe tener un selector para poder leer estilos inline/computados
            if (empty($data['selector']) || (!isset($data['selector']['attribute']) && !isset($data['selector']['class']))) {
                $issues[] = [
                    'type' => 'missing_selector',
                    'severity' => 'critical',
                    'message' => "Componente '{$role}' no tiene selector DOM válido. Imposible leer estilos inline/computados (Violación de Regla de Sincronización).",
                    'context' => $role
                ];
                $score -= 20;
            }
        }

        $score = max(0, $score);

        $report = [
            'score' => $score,
            'issues' => $issues,
            'timestamp' => current_time('mysql')
        ];

        wp_send_json_success($report);
    }

    public static function getLogs()
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
        }

        $logDir = get_template_directory() . '/Glory/src/Gbn/logs';
        $logs = [];

        if (is_dir($logDir)) {
            $files = glob($logDir . '/*.log');
            if ($files) {
                // Ordenar por fecha de modificación (más reciente primero)
                usort($files, function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });

                // Leer los últimos 5 archivos
                $recentFiles = array_slice($files, 0, 5);

                foreach ($recentFiles as $file) {
                    $content = file_get_contents($file);
                    // Parsear líneas (asumiendo formato JSON o texto simple)
                    // Por ahora enviamos el contenido crudo pero limitado
                    $logs[basename($file)] = mb_substr($content, -10000); // Últimos 10KB
                }
            }
        }

        wp_send_json_success(['logs' => $logs]);
    }
}
