<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\Mode\PluginMode;
use Glory\Plugins\AmazonProduct\Service\ApiClient;

/**
 * Tab para configurar la licencia del plugin en modo cliente.
 * 
 * Permite:
 * - Ingresar API Key
 * - Ver estado de la licencia
 * - Ver GB usados/disponibles
 * - Link para renovar suscripcion
 */
class ClientLicenseTab implements TabInterface
{
    public function __construct()
    {
        add_action('wp_ajax_glory_save_api_key', [$this, 'ajaxSaveApiKey']);
        add_action('wp_ajax_glory_test_connection', [$this, 'ajaxTestConnection']);
    }

    public function getSlug(): string
    {
        return 'license';
    }

    public function getLabel(): string
    {
        return 'Licencia';
    }

    public function render(): void
    {
        $apiKey = PluginMode::getApiKey();
        $hasKey = !empty($apiKey);
        $maskedKey = $hasKey ? substr($apiKey, 0, 8) . '...' . substr($apiKey, -4) : '';

        $licenseStatus = null;
        if ($hasKey) {
            $client = new ApiClient();
            $result = $client->getLicenseStatus();
            if ($result['success']) {
                $licenseStatus = $result['status'];
            }
        }
?>
        <div id="contenedor-licencia-cliente" class="wrap">
            <h2>Configuracion de Licencia</h2>
            <p style="color: #666;">
                Ingresa tu API Key para conectarte al servicio de Glory Amazon Products.
            </p>

            <!-- Formulario de API Key -->
            <div id="seccion-formulario-apikey" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px; max-width: 600px;">
                <h3 style="margin-top: 0;">API Key</h3>

                <?php if ($hasKey): ?>
                    <div style="margin-bottom: 15px; padding: 10px; background: #f0f8e8; border-left: 4px solid #46b450; border-radius: 2px;">
                        <strong>API Key configurada:</strong> <code><?php echo esc_html($maskedKey); ?></code>
                    </div>
                <?php endif; ?>

                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <input
                        type="text"
                        id="glory-api-key-input"
                        class="regular-text"
                        placeholder="Ingresa tu API Key..."
                        value=""
                        style="flex: 1; min-width: 300px;">
                    <button type="button" id="glory-save-api-key" class="button button-primary">
                        <?php echo $hasKey ? 'Actualizar' : 'Guardar'; ?>
                    </button>
                    <?php if ($hasKey): ?>
                        <button type="button" id="glory-test-connection" class="button">
                            Probar Conexion
                        </button>
                    <?php endif; ?>
                </div>

                <div id="glory-api-key-message" style="margin-top: 10px;"></div>
            </div>

            <!-- Estado de la Licencia -->
            <?php if ($hasKey && $licenseStatus): ?>
                <div id="seccion-estado-licencia" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px; max-width: 600px;">
                    <h3 style="margin-top: 0;">Estado de tu Suscripcion</h3>

                    <?php $this->renderLicenseStatus($licenseStatus); ?>
                </div>
            <?php elseif ($hasKey): ?>
                <div class="notice notice-warning inline" style="max-width: 600px;">
                    <p>No se pudo obtener el estado de la licencia. Verifica tu API Key o prueba la conexion.</p>
                </div>
            <?php endif; ?>

            <!-- Instrucciones -->
            <div id="seccion-instrucciones" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 8px; max-width: 600px; color: #fff;">
                <h3 style="margin-top: 0; color: #fff; font-size: 20px;">¿No tienes una API Key?</h3>
                <p style="margin-bottom: 20px; opacity: 0.95;">
                    Para obtener tu API Key necesitas una suscripcion activa al servicio Glory Amazon Products.
                </p>
                <a href="https://buy.stripe.com/8x26oG58XchA56va31cAo0c"
                    target="_blank"
                    style="display: inline-block; background: #fff; color: #667eea; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.2s, box-shadow 0.2s;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.3)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)';">
                    Suscribirse Ahora
                </a>
                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.3);">
                    <p style="margin: 0; font-size: 14px; opacity: 0.9;">
                        <strong>Plan Basico:</strong> $20/mes - 4GB de datos
                    </p>
                    <p style="margin: 5px 0 0; font-size: 13px; opacity: 0.85;">
                        Incluye <strong>30 dias de prueba GRATIS</strong>
                    </p>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                /* Guardar API Key */
                $('#glory-save-api-key').on('click', function() {
                    const btn = $(this);
                    const apiKey = $('#glory-api-key-input').val().trim();

                    if (!apiKey) {
                        $('#glory-api-key-message').html(
                            '<div class="notice notice-error inline"><p>Ingresa una API Key valida</p></div>'
                        );
                        return;
                    }

                    btn.prop('disabled', true).text('Guardando...');

                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'glory_save_api_key',
                            api_key: apiKey,
                            nonce: '<?php echo wp_create_nonce('glory_save_api_key'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#glory-api-key-message').html(
                                    '<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>'
                                );
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                $('#glory-api-key-message').html(
                                    '<div class="notice notice-error inline"><p>' + response.data + '</p></div>'
                                );
                                btn.prop('disabled', false).text('Guardar');
                            }
                        },
                        error: function() {
                            $('#glory-api-key-message').html(
                                '<div class="notice notice-error inline"><p>Error de conexion</p></div>'
                            );
                            btn.prop('disabled', false).text('Guardar');
                        }
                    });
                });

                /* Probar Conexion */
                $('#glory-test-connection').on('click', function() {
                    const btn = $(this);
                    btn.prop('disabled', true).text('Probando...');

                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'glory_test_connection',
                            nonce: '<?php echo wp_create_nonce('glory_test_connection'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#glory-api-key-message').html(
                                    '<div class="notice notice-success inline"><p>Conexion exitosa!</p></div>'
                                );
                            } else {
                                $('#glory-api-key-message').html(
                                    '<div class="notice notice-error inline"><p>' + response.data + '</p></div>'
                                );
                            }
                            btn.prop('disabled', false).text('Probar Conexion');
                        },
                        error: function() {
                            $('#glory-api-key-message').html(
                                '<div class="notice notice-error inline"><p>Error de conexion</p></div>'
                            );
                            btn.prop('disabled', false).text('Probar Conexion');
                        }
                    });
                });
            });
        </script>
    <?php
    }

    /**
     * Renderiza el estado de la licencia con indicadores visuales.
     */
    private function renderLicenseStatus(array $status): void
    {
        $gbUsed = $status['gb_used'] ?? 0;
        $gbLimit = $status['gb_limit'] ?? 4;
        $gbRemaining = max(0, $gbLimit - $gbUsed);
        $percentUsed = $gbLimit > 0 ? ($gbUsed / $gbLimit) * 100 : 0;

        $licenseStatus = $status['status'] ?? 'unknown';
        $expiresAt = $status['expires_at'] ?? null;

        $statusColor = match ($licenseStatus) {
            'active' => '#46b450',
            'trial' => '#0073aa',
            'expired' => '#dc3232',
            default => '#999'
        };

        $statusLabel = match ($licenseStatus) {
            'active' => 'Activa',
            'trial' => 'Periodo de Prueba',
            'expired' => 'Expirada',
            default => 'Desconocido'
        };

        $barColor = $percentUsed > 80 ? '#dc3232' : ($percentUsed > 60 ? '#f39c12' : '#46b450');
    ?>
        <table class="form-table">
            <tr>
                <th>Estado</th>
                <td>
                    <span style="background: <?php echo $statusColor; ?>; color: #fff; padding: 4px 12px; border-radius: 3px; font-weight: bold;">
                        <?php echo esc_html($statusLabel); ?>
                    </span>
                </td>
            </tr>
            <?php if ($expiresAt): ?>
                <tr>
                    <th>Expira</th>
                    <td><?php echo esc_html(date('d/m/Y', strtotime($expiresAt))); ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th>Uso de Datos</th>
                <td>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="flex: 1; max-width: 200px; background: #e0e0e0; border-radius: 10px; overflow: hidden;">
                            <div style="width: <?php echo min(100, $percentUsed); ?>%; height: 20px; background: <?php echo $barColor; ?>; transition: width 0.3s;"></div>
                        </div>
                        <span style="font-weight: bold;">
                            <?php echo number_format($gbUsed, 2); ?> / <?php echo $gbLimit; ?> GB
                        </span>
                    </div>
                    <p style="margin-top: 5px; color: #666; font-size: 12px;">
                        Te quedan <strong><?php echo number_format($gbRemaining, 2); ?> GB</strong> disponibles este mes.
                    </p>
                </td>
            </tr>
        </table>

        <?php if ($licenseStatus === 'expired'): ?>
            <div class="notice notice-error inline" style="margin-top: 15px;">
                <p>
                    <strong>Tu suscripcion ha expirado.</strong>
                    <a href="https://buy.stripe.com/8x26oG58XchA56va31cAo0c" target="_blank" style="font-weight: bold;">Renueva ahora</a> para seguir usando el servicio.
                </p>
            </div>
        <?php elseif ($percentUsed > 80): ?>
            <div class="notice notice-warning inline" style="margin-top: 15px;">
                <p>
                    <strong>Estas cerca del limite.</strong>
                    Has usado el <?php echo number_format($percentUsed, 0); ?>% de tus datos mensuales.
                </p>
            </div>
        <?php endif; ?>
<?php
    }

    /**
     * AJAX: Guarda la API Key.
     */
    public function ajaxSaveApiKey(): void
    {
        check_ajax_referer('glory_save_api_key', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $apiKey = sanitize_text_field($_POST['api_key'] ?? '');

        if (empty($apiKey)) {
            wp_send_json_error('API Key vacia');
        }

        if (strlen($apiKey) < 20) {
            wp_send_json_error('API Key invalida (muy corta)');
        }

        PluginMode::setApiKey($apiKey);

        $client = new ApiClient();
        $result = $client->testConnection();

        if ($result['success']) {
            wp_send_json_success([
                'message' => 'API Key guardada y verificada correctamente!'
            ]);
        } else {
            PluginMode::setApiKey('');
            wp_send_json_error('API Key invalida: ' . ($result['error'] ?? 'Error desconocido'));
        }
    }

    /**
     * AJAX: Prueba la conexion con el servidor.
     */
    public function ajaxTestConnection(): void
    {
        check_ajax_referer('glory_test_connection', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $client = new ApiClient();
        $result = $client->testConnection();

        if ($result['success']) {
            wp_send_json_success(['message' => 'Conexion exitosa']);
        } else {
            wp_send_json_error($result['error'] ?? 'Error de conexion');
        }
    }
}
