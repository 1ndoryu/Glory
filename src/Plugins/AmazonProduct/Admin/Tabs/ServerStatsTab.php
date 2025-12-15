<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\Service\LicenseService;
use Glory\Plugins\AmazonProduct\Service\UsageController;

/**
 * Tab de estadisticas del servidor.
 * Solo visible en modo SERVIDOR.
 */
class ServerStatsTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'stats';
    }

    public function getLabel(): string
    {
        return 'Estadisticas';
    }

    public function render(): void
    {
        $licenseStats = LicenseService::getGlobalStats();
        $usageStats24h = UsageController::getRecentStats(24);
        $usageStats7d = UsageController::getRecentStats(24 * 7);
?>
        <div id="estadisticas-tab">
            <h3>Estadisticas del Servidor</h3>

            <!-- Stats generales -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px;">
                    <div style="font-size: 32px; font-weight: bold;"><?php echo $licenseStats['active_licenses']; ?></div>
                    <div style="opacity: 0.9;">Licencias Activas</div>
                </div>
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 10px;">
                    <div style="font-size: 32px; font-weight: bold;"><?php echo number_format($licenseStats['total_gb_used'], 2); ?> GB</div>
                    <div style="opacity: 0.9;">Total GB Usados</div>
                </div>
                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 10px;">
                    <div style="font-size: 32px; font-weight: bold;"><?php echo number_format($usageStats24h['requests']); ?></div>
                    <div style="opacity: 0.9;">Requests (24h)</div>
                </div>
                <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 20px; border-radius: 10px;">
                    <div style="font-size: 32px; font-weight: bold;"><?php echo $usageStats24h['unique_users']; ?></div>
                    <div style="opacity: 0.9;">Usuarios Activos (24h)</div>
                </div>
            </div>

            <!-- Detalle de uso -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px;">
                    <h4 style="margin-top: 0;">Ultimas 24 horas</h4>
                    <table class="widefat">
                        <tr>
                            <td>Requests</td>
                            <td style="text-align: right;"><strong><?php echo number_format($usageStats24h['requests']); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Datos transferidos</td>
                            <td style="text-align: right;"><strong><?php echo number_format($usageStats24h['gb'], 3); ?> GB</strong></td>
                        </tr>
                        <tr>
                            <td>Usuarios unicos</td>
                            <td style="text-align: right;"><strong><?php echo $usageStats24h['unique_users']; ?></strong></td>
                        </tr>
                        <tr>
                            <td>Promedio por request</td>
                            <td style="text-align: right;">
                                <strong>
                                    <?php
                                    $avgKb = $usageStats24h['requests'] > 0
                                        ? ($usageStats24h['bytes'] / $usageStats24h['requests']) / 1024
                                        : 0;
                                    echo number_format($avgKb, 1) . ' KB';
                                    ?>
                                </strong>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px;">
                    <h4 style="margin-top: 0;">Ultimos 7 dias</h4>
                    <table class="widefat">
                        <tr>
                            <td>Requests</td>
                            <td style="text-align: right;"><strong><?php echo number_format($usageStats7d['requests']); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Datos transferidos</td>
                            <td style="text-align: right;"><strong><?php echo number_format($usageStats7d['gb'], 3); ?> GB</strong></td>
                        </tr>
                        <tr>
                            <td>Usuarios unicos</td>
                            <td style="text-align: right;"><strong><?php echo $usageStats7d['unique_users']; ?></strong></td>
                        </tr>
                        <tr>
                            <td>Promedio diario</td>
                            <td style="text-align: right;">
                                <strong><?php echo number_format($usageStats7d['requests'] / 7, 0); ?> req/dia</strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Estimacion de costos -->
            <div style="background: #fff8e1; border: 1px solid #ffcc02; border-radius: 5px; padding: 20px; margin-top: 20px;">
                <h4 style="margin-top: 0; color: #f57c00;">Estimacion de Costos (Proxy)</h4>
                <p>
                    Basado en el uso de 7 dias (<?php echo number_format($usageStats7d['gb'], 3); ?> GB):
                </p>
                <ul>
                    <li>Proyeccion mensual: ~<?php echo number_format(($usageStats7d['gb'] / 7) * 30, 2); ?> GB</li>
                    <li>Costo estimado proxy: ~$<?php echo number_format(($usageStats7d['gb'] / 7) * 30 * 3.5, 2); ?> (a $3.50/GB)</li>
                </ul>
            </div>

            <!-- Estado del sistema -->
            <div style="background: #e8f5e9; border: 1px solid #4caf50; border-radius: 5px; padding: 20px; margin-top: 20px;">
                <h4 style="margin-top: 0; color: #2e7d32;">Estado del Sistema</h4>
                <table class="widefat">
                    <tr>
                        <td>Modo</td>
                        <td><strong style="color: #0073aa;">SERVIDOR</strong></td>
                    </tr>
                    <tr>
                        <td>API Endpoint</td>
                        <td><code><?php echo esc_html(get_rest_url(null, 'glory/v1/amazon/')); ?></code></td>
                    </tr>
                    <tr>
                        <td>Proxy Configurado</td>
                        <td>
                            <?php
                            $proxy = get_option('amazon_scraper_proxy', '');
                            echo $proxy ? '<span style="color: green;">Si</span>' : '<span style="color: red;">No</span>';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Stripe Webhook</td>
                        <td>
                            <?php
                            $webhookSecret = defined('GLORY_STRIPE_WEBHOOK_SECRET') ? GLORY_STRIPE_WEBHOOK_SECRET : get_option('glory_stripe_webhook_secret', '');
                            echo $webhookSecret ? '<span style="color: green;">Configurado</span>' : '<span style="color: orange;">Pendiente</span>';
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
<?php
    }
}
