<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\Service\UsageController;

/**
 * Tab de logs del servidor.
 * Solo visible en modo SERVIDOR.
 */
class ServerLogsTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'logs';
    }

    public function getLabel(): string
    {
        return 'Logs';
    }

    public function render(): void
    {
        $this->handleActions();
        $logs = UsageController::getRecentLog(100);
?>
        <div id="logs-tab">
            <h3>Logs de Actividad</h3>

            <div style="margin-bottom: 20px;">
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('logs_action', 'logs_nonce'); ?>
                    <button type="submit" name="action" value="clean_old" class="button">
                        Limpiar logs antiguos (>30 dias)
                    </button>
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 120px;">Fecha/Hora</th>
                        <th style="width: 150px;">API Key</th>
                        <th style="width: 100px;">Endpoint</th>
                        <th style="width: 80px;">Bytes</th>
                        <th style="width: 100px;">IP</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px;">
                                No hay logs registrados aun.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <?php echo date('d/m H:i:s', $log['created_at']); ?>
                                </td>
                                <td>
                                    <code style="font-size: 11px;"><?php echo esc_html($log['api_key']); ?></code>
                                </td>
                                <td>
                                    <?php echo $this->renderEndpointBadge($log['endpoint']); ?>
                                </td>
                                <td>
                                    <?php echo $this->formatBytes((int)$log['bytes_used']); ?>
                                </td>
                                <td>
                                    <span style="font-size: 11px; color: #666;"><?php echo esc_html($log['ip_address']); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $metadata = json_decode($log['metadata'] ?? '{}', true);
                                    if (!empty($metadata)) {
                                        $details = [];
                                        if (!empty($metadata['keyword'])) {
                                            $details[] = 'Busqueda: "' . esc_html($metadata['keyword']) . '"';
                                        }
                                        if (!empty($metadata['asin'])) {
                                            $details[] = 'ASIN: ' . esc_html($metadata['asin']);
                                        }
                                        if (!empty($metadata['cached'])) {
                                            $details[] = '<span style="color: #2196f3;">Cache</span>';
                                        }
                                        if (!empty($metadata['results'])) {
                                            $details[] = $metadata['results'] . ' resultados';
                                        }
                                        if (!empty($metadata['time_ms'])) {
                                            $details[] = $metadata['time_ms'] . 'ms';
                                        }
                                        echo implode(' | ', $details);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p style="color: #666; margin-top: 10px;">
                Mostrando los ultimos 100 registros. Los logs se limpian automaticamente despues de 30 dias.
            </p>
        </div>
<?php
    }

    /**
     * Procesa acciones.
     */
    private function handleActions(): void
    {
        if (!isset($_POST['logs_nonce']) || !wp_verify_nonce($_POST['logs_nonce'], 'logs_action')) {
            return;
        }

        $action = sanitize_text_field($_POST['action'] ?? '');

        if ($action === 'clean_old') {
            $deleted = UsageController::cleanOldLogs();
            echo '<div class="notice notice-success"><p>' . $deleted . ' registros eliminados.</p></div>';
        }
    }

    /**
     * Formatea bytes a formato legible.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Renderiza badge de endpoint.
     */
    private function renderEndpointBadge(string $endpoint): string
    {
        $colors = [
            'search' => '#4caf50',
            'search_cached' => '#2196f3',
            'product' => '#ff9800',
            'product_cached' => '#03a9f4',
        ];

        $color = $colors[$endpoint] ?? '#9e9e9e';

        return sprintf(
            '<span style="background: %s; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px;">%s</span>',
            $color,
            esc_html($endpoint)
        );
    }
}
