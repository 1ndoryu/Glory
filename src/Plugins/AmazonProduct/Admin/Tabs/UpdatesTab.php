<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\Service\ProductSyncService;
use Glory\Plugins\AmazonProduct\Service\ApiUsageTracker;

/**
 * Updates Tab - Panel de control de sincronizacion de productos.
 * 
 * FEAT-07: Panel de control de actualizacion programada
 * 
 * Funcionalidades:
 * - Visualizacion de estado de sincronizacion
 * - Ultima y proxima sincronizacion
 * - Boton de sincronizacion manual
 * - Log de actualizaciones recientes
 */
class UpdatesTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'updates';
    }

    public function getLabel(): string
    {
        return 'Updates';
    }

    public function render(): void
    {
        // Manejar accion de sincronizacion manual
        if (isset($_POST['sync_now']) && check_admin_referer('amazon_sync_action', 'amazon_sync_nonce')) {
            $this->runManualSync();
        }

        // Manejar accion de limpiar log
        if (isset($_POST['clear_log']) && check_admin_referer('amazon_sync_action', 'amazon_sync_nonce')) {
            ProductSyncService::clearLog();
            echo '<div class="notice notice-success inline"><p>Log limpiado correctamente.</p></div>';
        }

        $this->renderSyncPanel();
    }

    /**
     * Ejecuta sincronizacion manual.
     */
    private function runManualSync(): void
    {
        // Verificar limite de API antes de iniciar
        if (!ApiUsageTracker::canMakeCall()) {
            echo '<div class="notice notice-error inline"><p><strong>Error:</strong> Limite de API alcanzado. No se puede sincronizar hasta el proximo ciclo.</p></div>';
            return;
        }

        $result = ProductSyncService::syncAllProducts();

        if ($result['updated'] > 0 || $result['failed'] === 0) {
            echo '<div class="notice notice-success inline"><p>';
            echo '<strong>Sincronizacion completada:</strong> ';
            echo $result['updated'] . ' productos actualizados';
            if ($result['failed'] > 0) {
                echo ', ' . $result['failed'] . ' fallidos';
            }
            if ($result['skipped'] > 0) {
                echo ', ' . $result['skipped'] . ' omitidos (limite API)';
            }
            echo '</p></div>';
        } else {
            echo '<div class="notice notice-warning inline"><p>';
            echo '<strong>Sincronizacion con errores:</strong> ' . $result['failed'] . ' productos fallaron.';
            echo '</p></div>';
        }
    }

    /**
     * Renderiza el panel completo de sincronizacion.
     */
    private function renderSyncPanel(): void
    {
        $syncInfo = ProductSyncService::getSyncInfo();
        $apiStats = ApiUsageTracker::getStats();
        $syncLog = ProductSyncService::getSyncLog(15);
?>
        <h3>Sincronizacion de Productos</h3>
        <p>Actualiza los precios y disponibilidad de todos los productos guardados consultando la API de Amazon.</p>

        <!-- Estado actual -->
        <div class="amazon-sync-status" style="background:#f9f9f9;border:1px solid #ddd;padding:20px;margin-bottom:20px;border-radius:4px;">
            <h4 style="margin-top:0;">Estado de Sincronizacion</h4>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:20px;">
                <div>
                    <strong>Productos guardados:</strong><br>
                    <span style="font-size:1.5em;color:#2271b1;"><?php echo esc_html($syncInfo['productCount']); ?></span>
                </div>
                <div>
                    <strong>Ultima sincronizacion:</strong><br>
                    <span><?php echo esc_html($syncInfo['lastSync']); ?></span>
                </div>
                <div>
                    <strong>Proxima sincronizacion:</strong><br>
                    <span><?php echo esc_html($syncInfo['nextSync']); ?></span>
                </div>
                <div>
                    <strong>Frecuencia:</strong><br>
                    <span><?php echo esc_html($this->getFrequencyLabel($syncInfo['frequency'])); ?></span>
                </div>
            </div>

            <?php if ($syncInfo['status'] === 'running'): ?>
                <p style="color:#2271b1;margin-top:15px;">
                    <span class="spinner is-active" style="float:none;margin:0 5px 0 0;"></span>
                    <?php echo esc_html($syncInfo['message']); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- API Usage Warning -->
        <?php if ($apiStats['isWarning'] || $apiStats['isCritical']): ?>
            <div class="notice notice-<?php echo $apiStats['isCritical'] ? 'error' : 'warning'; ?> inline" style="margin-bottom:20px;">
                <p>
                    <strong>Uso de API:</strong>
                    <?php echo esc_html($apiStats['count']); ?> / <?php echo esc_html($apiStats['limit']); ?> llamadas
                    (<?php echo esc_html($apiStats['percentage']); ?>%)
                    <?php if ($apiStats['isCritical']): ?>
                        - <strong>Limite alcanzado</strong>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Boton de sincronizacion manual -->
        <form method="post" style="margin-bottom:30px;">
            <?php wp_nonce_field('amazon_sync_action', 'amazon_sync_nonce'); ?>
            <p>
                <strong>Sincronizacion manual:</strong>
                Actualiza todos los productos ahora. Cada producto consume 1 llamada de API.
            </p>
            <p>
                <?php
                $buttonDisabled = $apiStats['isCritical'] || $syncInfo['productCount'] === 0;
                $buttonClass = $buttonDisabled ? 'button button-secondary' : 'button button-primary';
                ?>
                <button type="submit" name="sync_now" class="<?php echo $buttonClass; ?>"
                    <?php echo $buttonDisabled ? 'disabled' : ''; ?>>
                    Sincronizar Ahora (<?php echo esc_html($syncInfo['productCount']); ?> productos)
                </button>
                <?php if ($apiStats['isCritical']): ?>
                    <span style="color:#dc3232;margin-left:10px;">Limite de API alcanzado</span>
                <?php elseif ($syncInfo['productCount'] === 0): ?>
                    <span style="color:#666;margin-left:10px;">No hay productos para sincronizar</span>
                <?php endif; ?>
            </p>
        </form>

        <!-- Log de sincronizaciones -->
        <div class="amazon-sync-log">
            <h4>Log de Sincronizaciones</h4>

            <?php if (empty($syncLog)): ?>
                <p style="color:#666;">No hay registros de sincronizacion.</p>
            <?php else: ?>
                <table class="widefat striped" style="margin-bottom:15px;">
                    <thead>
                        <tr>
                            <th style="width:150px;">Fecha</th>
                            <th style="width:80px;">Tipo</th>
                            <th>Mensaje</th>
                            <th style="width:80px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($syncLog as $entry): ?>
                            <tr>
                                <td><?php echo esc_html($entry['date']); ?></td>
                                <td><?php echo esc_html(ucfirst($entry['type'])); ?></td>
                                <td><?php echo esc_html($entry['message']); ?></td>
                                <td>
                                    <?php
                                    $levelColors = [
                                        'success' => '#00a32a',
                                        'warning' => '#dba617',
                                        'error' => '#dc3232',
                                        'info' => '#72aee6'
                                    ];
                                    $color = $levelColors[$entry['level']] ?? '#666';
                                    ?>
                                    <span style="color:<?php echo $color; ?>;">
                                        <?php echo esc_html(ucfirst($entry['level'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <form method="post" style="display:inline;">
                    <?php wp_nonce_field('amazon_sync_action', 'amazon_sync_nonce'); ?>
                    <button type="submit" name="clear_log" class="button button-link-delete"
                        onclick="return confirm('Limpiar todo el log de sincronizaciones?');">
                        Limpiar Log
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Nota sobre configuracion -->
        <div style="margin-top:30px;padding:15px;background:#fff8e5;border-left:4px solid #dba617;border-radius:4px;">
            <p style="margin:0;">
                <strong>Nota:</strong> La frecuencia de sincronizacion automatica se configura en la pestana
                <a href="<?php echo admin_url('admin.php?page=amazon-product&tab=settings'); ?>">API Settings</a>.
                Opciones disponibles: Manual, Diario, Semanal.
            </p>
        </div>
<?php
    }

    /**
     * Obtiene la etiqueta legible para la frecuencia.
     */
    private function getFrequencyLabel(string $frequency): string
    {
        $labels = [
            'off' => 'Manual',
            'daily' => 'Diario',
            'weekly' => 'Semanal',
            'hourly6' => 'Cada 6 horas'
        ];
        return $labels[$frequency] ?? $frequency;
    }
}
