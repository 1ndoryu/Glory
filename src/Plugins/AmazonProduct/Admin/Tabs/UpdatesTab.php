<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\Mode\PluginMode;
use Glory\Plugins\AmazonProduct\Service\ClientSyncService;
use Glory\Plugins\AmazonProduct\Service\ApiClient;

/**
 * Updates Tab - Panel de actualizacion automatica de productos.
 * 
 * Permite al cliente:
 * - Configurar frecuencia de actualizacion automatica
 * - Ver estimado de consumo de datos
 * - Ejecutar actualizacion manual
 * - Ver log de actualizaciones
 */
class UpdatesTab implements TabInterface
{
    public function __construct()
    {
        add_action('wp_ajax_glory_start_sync', [$this, 'ajaxStartSync']);
        add_action('wp_ajax_glory_get_sync_status', [$this, 'ajaxGetSyncStatus']);
    }

    public function getSlug(): string
    {
        return 'updates';
    }

    public function getLabel(): string
    {
        return 'Actualizaciones';
    }

    public function render(): void
    {
        /* Manejar guardado de configuracion */
        if (isset($_POST['guardar_config_sync']) && check_admin_referer('glory_sync_config', 'glory_sync_nonce')) {
            $frequency = sanitize_text_field($_POST['sync_frequency'] ?? 'off');
            ClientSyncService::saveConfig($frequency);
            echo '<div class="notice notice-success inline"><p>Configuracion guardada correctamente.</p></div>';
        }

        /* Manejar limpiar log */
        if (isset($_POST['limpiar_log']) && check_admin_referer('glory_sync_config', 'glory_sync_nonce')) {
            ClientSyncService::clearLog();
            echo '<div class="notice notice-success inline"><p>Log limpiado correctamente.</p></div>';
        }

        $this->renderPanel();
    }

    private function renderPanel(): void
    {
        $syncInfo = ClientSyncService::getSyncInfo();
        $syncLog = ClientSyncService::getLog(20);
        $config = ClientSyncService::getConfig();

        /* Obtener info de licencia para mostrar GB disponibles */
        $licenseInfo = null;
        if (PluginMode::isClient() && PluginMode::getApiKey()) {
            $client = new ApiClient();
            $result = $client->getLicenseStatus();
            if ($result['success']) {
                $licenseInfo = $result['status'];
            }
        }
?>
        <div id="contenedor-actualizaciones" class="wrap">
            <h2>Actualizacion Automatica de Productos</h2>
            <p style="color: #666;">
                Mantiene los precios, ratings y disponibilidad de tus productos actualizados automaticamente.
            </p>

            <!-- Aviso importante sobre consumo -->
            <div class="notice notice-warning inline" style="margin: 15px 0;">
                <p>
                    <strong><span class="dashicons dashicons-warning"></span> Importante:</strong>
                    Las actualizaciones consumen datos de tu saldo mensual.
                    Cada producto actualizado consume aproximadamente <strong>~500 KB</strong>.
                </p>
            </div>

            <!-- Seccion de Configuracion -->
            <div id="seccion-config-sync" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">
                <h3 style="margin-top: 0;">
                    <span class="dashicons dashicons-update"></span>
                    Configuracion de Frecuencia
                </h3>

                <form method="post">
                    <?php wp_nonce_field('glory_sync_config', 'glory_sync_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th><label for="sync_frequency">Frecuencia de actualizacion</label></th>
                            <td>
                                <select name="sync_frequency" id="sync_frequency" style="min-width: 200px;">
                                    <option value="off" <?php selected($config['frequency'], 'off'); ?>>
                                        Desactivado (solo manual)
                                    </option>
                                    <option value="daily" <?php selected($config['frequency'], 'daily'); ?>>
                                        Diario
                                    </option>
                                    <option value="3days" <?php selected($config['frequency'], '3days'); ?>>
                                        Cada 3 dias
                                    </option>
                                    <option value="weekly" <?php selected($config['frequency'], 'weekly'); ?>>
                                        Semanal (recomendado)
                                    </option>
                                    <option value="14days" <?php selected($config['frequency'], '14days'); ?>>
                                        Cada 14 dias
                                    </option>
                                </select>
                                <p class="description">
                                    Recomendamos <strong>semanal</strong> para un buen balance entre datos frescos y consumo.
                                </p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button('Guardar Configuracion', 'primary', 'guardar_config_sync'); ?>
                </form>
            </div>

            <!-- Estimado de consumo -->
            <?php if ($syncInfo['productCount'] > 0): ?>
                <div id="seccion-estimado" style="background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0;">
                        <span class="dashicons dashicons-chart-bar"></span>
                        Estimado de Consumo
                    </h3>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px;">
                        <div style="background: #fff; padding: 15px; border-radius: 4px; text-align: center;">
                            <div style="font-size: 28px; font-weight: bold; color: #2271b1;">
                                <?php echo $syncInfo['productCount']; ?>
                            </div>
                            <div style="font-size: 12px; color: #666;">Productos guardados</div>
                        </div>
                        <div style="background: #fff; padding: 15px; border-radius: 4px; text-align: center;">
                            <div style="font-size: 28px; font-weight: bold; color: #d63638;">
                                <?php
                                if ($syncInfo['estimatedMbPerSync'] < 1) {
                                    echo number_format($syncInfo['estimatedMbPerSync'] * 1024, 0) . ' KB';
                                } else {
                                    echo number_format($syncInfo['estimatedMbPerSync'], 1) . ' MB';
                                }
                                ?>
                            </div>
                            <div style="font-size: 12px; color: #666;">Consumo por actualizacion</div>
                        </div>
                        <div style="background: #fff; padding: 15px; border-radius: 4px; text-align: center;">
                            <div style="font-size: 28px; font-weight: bold; color: #00a32a;">
                                ~<?php echo $syncInfo['estimatedTimeMinutes']; ?> min
                            </div>
                            <div style="font-size: 12px; color: #666;">Tiempo estimado</div>
                        </div>
                    </div>

                    <?php
                    /* Calcular consumo mensual segun frecuencia */
                    $consumoMensual = 0;
                    $frecuenciasAlMes = [
                        'daily' => 30,
                        '3days' => 10,
                        'weekly' => 4,
                        '14days' => 2,
                        'off' => 0
                    ];
                    $vecesAlMes = $frecuenciasAlMes[$config['frequency']] ?? 0;
                    $consumoMensual = $syncInfo['estimatedMbPerSync'] * $vecesAlMes;

                    if ($config['frequency'] !== 'off'):
                    ?>
                        <div style="margin-top: 15px; padding: 10px; background: #fff; border-radius: 4px;">
                            <strong>Consumo mensual estimado:</strong>
                            <?php
                            if ($consumoMensual < 1) {
                                echo number_format($consumoMensual * 1024, 0) . ' KB';
                            } elseif ($consumoMensual < 1024) {
                                echo number_format($consumoMensual, 0) . ' MB';
                            } else {
                                echo number_format($consumoMensual / 1024, 2) . ' GB';
                            }
                            ?>
                            <span style="color: #666;">(<?php echo $vecesAlMes; ?> actualizaciones x <?php echo number_format($syncInfo['estimatedMbPerSync'], 1); ?> MB)</span>

                            <?php if ($licenseInfo): ?>
                                <br>
                                <small style="color: #666;">
                                    Tienes <strong><?php echo number_format($licenseInfo['gb_remaining'], 2); ?> GB</strong> disponibles este mes.
                                    <?php
                                    $consumoGbMensual = $consumoMensual / 1024;
                                    if ($consumoGbMensual > $licenseInfo['gb_remaining']):
                                    ?>
                                        <span style="color: #d63638;">
                                            <br><strong>Atencion:</strong> El consumo estimado supera tu saldo disponible.
                                        </span>
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Estado actual -->
            <div id="seccion-estado" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">
                <h3 style="margin-top: 0;">
                    <span class="dashicons dashicons-info-outline"></span>
                    Estado de Sincronizacion
                </h3>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div>
                        <strong>Frecuencia actual:</strong><br>
                        <span style="color: <?php echo $config['frequency'] === 'off' ? '#999' : '#2271b1'; ?>;">
                            <?php echo esc_html($syncInfo['frequencyLabel']); ?>
                        </span>
                    </div>
                    <div>
                        <strong>Ultima actualizacion:</strong><br>
                        <span><?php echo esc_html($syncInfo['lastSync']); ?></span>
                    </div>
                    <div>
                        <strong>Proxima programada:</strong><br>
                        <span><?php echo esc_html($syncInfo['nextSync']); ?></span>
                    </div>
                </div>

                <?php if ($syncInfo['isRunning'] && $syncInfo['progress']): ?>
                    <!-- Barra de progreso -->
                    <div id="sync-progress-container" style="margin-bottom: 20px;">
                        <div style="background: #e0e0e0; border-radius: 10px; overflow: hidden; margin-bottom: 10px;">
                            <div id="sync-progress-bar" style="width: <?php echo $syncInfo['progress']['percent']; ?>%; height: 20px; background: #2271b1; transition: width 0.3s;"></div>
                        </div>
                        <p id="sync-progress-text" style="color: #666;">
                            Actualizando: <?php echo $syncInfo['progress']['processed']; ?> de <?php echo $syncInfo['progress']['total']; ?> productos
                            (<?php echo $syncInfo['progress']['percent']; ?>%)
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Boton de actualizacion manual -->
                <div style="margin-top: 15px;">
                    <button
                        type="button"
                        id="btn-sync-ahora"
                        class="button button-primary"
                        <?php echo $syncInfo['isRunning'] || $syncInfo['productCount'] === 0 ? 'disabled' : ''; ?>>
                        <?php if ($syncInfo['isRunning']): ?>
                            <span class="spinner is-active" style="float: none; margin: -2px 5px 0 0;"></span>
                            Sincronizando...
                        <?php else: ?>
                            Actualizar Ahora (<?php echo $syncInfo['productCount']; ?> productos)
                        <?php endif; ?>
                    </button>

                    <?php if ($syncInfo['productCount'] === 0): ?>
                        <span style="color: #666; margin-left: 10px;">No hay productos para actualizar</span>
                    <?php elseif (!$syncInfo['isRunning']): ?>
                        <span style="color: #666; margin-left: 10px;">
                            Consumira ~<?php echo number_format($syncInfo['estimatedMbPerSync'], 1); ?> MB de tu saldo
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Log de actualizaciones -->
            <div id="seccion-log" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3 style="margin-top: 0;">
                    <span class="dashicons dashicons-list-view"></span>
                    Log de Actualizaciones
                </h3>

                <?php if (empty($syncLog)): ?>
                    <p style="color: #666;">No hay registros de actualizaciones.</p>
                <?php else: ?>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <table class="widefat striped" style="margin-bottom: 15px;">
                            <thead>
                                <tr>
                                    <th style="width: 140px;">Fecha</th>
                                    <th style="width: 80px;">Tipo</th>
                                    <th>Mensaje</th>
                                    <th style="width: 80px;">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($syncLog as $entry): ?>
                                    <tr>
                                        <td style="font-size: 12px;"><?php echo esc_html($entry['date']); ?></td>
                                        <td>
                                            <span style="font-size: 11px; text-transform: uppercase; color: #666;">
                                                <?php echo esc_html($entry['type']); ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 13px;"><?php echo esc_html($entry['message']); ?></td>
                                        <td>
                                            <?php
                                            $levelColors = [
                                                'success' => '#00a32a',
                                                'warning' => '#dba617',
                                                'error' => '#d63638',
                                                'info' => '#72aee6'
                                            ];
                                            $color = $levelColors[$entry['level']] ?? '#666';
                                            ?>
                                            <span style="color: <?php echo $color; ?>; font-weight: 500;">
                                                <?php echo esc_html(ucfirst($entry['level'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('glory_sync_config', 'glory_sync_nonce'); ?>
                        <button type="submit" name="limpiar_log" class="button button-link-delete"
                            onclick="return confirm('¿Limpiar todo el log de actualizaciones?');">
                            Limpiar Log
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var isRunning = <?php echo $syncInfo['isRunning'] ? 'true' : 'false'; ?>;
                var pollInterval = null;

                /* Iniciar sincronizacion */
                $('#btn-sync-ahora').on('click', function() {
                    var btn = $(this);
                    btn.prop('disabled', true).html(
                        '<span class="spinner is-active" style="float: none; margin: -2px 5px 0 0;"></span> Iniciando...'
                    );

                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'glory_start_sync',
                            nonce: '<?php echo wp_create_nonce('glory_start_sync'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                isRunning = true;
                                startPolling();
                                btn.html(
                                    '<span class="spinner is-active" style="float: none; margin: -2px 5px 0 0;"></span> Sincronizando...'
                                );
                            } else {
                                alert(response.data || 'Error al iniciar sincronizacion');
                                btn.prop('disabled', false).text('Actualizar Ahora');
                            }
                        },
                        error: function() {
                            alert('Error de conexion');
                            btn.prop('disabled', false).text('Actualizar Ahora');
                        }
                    });
                });

                /* Polling para actualizar progreso */
                function startPolling() {
                    if (pollInterval) return;

                    pollInterval = setInterval(function() {
                        $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'glory_get_sync_status',
                                nonce: '<?php echo wp_create_nonce('glory_get_sync_status'); ?>'
                            },
                            success: function(response) {
                                if (response.success && response.data) {
                                    updateProgressUI(response.data);
                                    if (!response.data.running) {
                                        stopPolling();
                                        location.reload();
                                    }
                                }
                            }
                        });
                    }, 5000); /* Cada 5 segundos */
                }

                function stopPolling() {
                    if (pollInterval) {
                        clearInterval(pollInterval);
                        pollInterval = null;
                    }
                }

                function updateProgressUI(status) {
                    if (status.progress) {
                        var percent = status.progress.percent;
                        $('#sync-progress-bar').css('width', percent + '%');
                        $('#sync-progress-text').text(
                            'Actualizando: ' + status.progress.processed + ' de ' + status.progress.total +
                            ' productos (' + percent + '%)'
                        );
                    }
                }

                /* Iniciar polling si ya esta corriendo */
                if (isRunning) {
                    startPolling();
                }
            });
        </script>
<?php
    }

    /**
     * AJAX: Iniciar sincronizacion.
     */
    public function ajaxStartSync(): void
    {
        check_ajax_referer('glory_start_sync', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $result = ClientSyncService::runManualSync();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX: Obtener estado de sincronizacion.
     */
    public function ajaxGetSyncStatus(): void
    {
        check_ajax_referer('glory_get_sync_status', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $status = ClientSyncService::getStatus();
        $info = ClientSyncService::getSyncInfo();

        wp_send_json_success([
            'running' => $status['running'],
            'progress' => $info['progress']
        ]);
    }
}
