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

            <!-- Seccion condicional: Suscripcion o Gestion -->
            <?php
            $isSubscribed = $hasKey && $licenseStatus && in_array($licenseStatus['status'] ?? '', ['active', 'trial']);

            if (!$isSubscribed):
            ?>
                <!-- Usuario NO suscrito: Mostrar CTA de suscripcion -->
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
            <?php else: ?>
                <!-- Usuario SUSCRITO: Mostrar gestion de suscripcion -->
                <div id="seccion-gestion" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px; max-width: 600px;">
                    <h3 style="margin-top: 0;">Gestionar Suscripcion</h3>

                    <?php
                    $expiresAt = $licenseStatus['expires_at'] ?? 0;
                    $expiresFormatted = $licenseStatus['expires_at_formatted'] ?? null;

                    if ($expiresAt > 0 || $expiresFormatted):
                        $displayDate = $expiresFormatted
                            ? date('d/m/Y', strtotime($expiresFormatted))
                            : date('d/m/Y', $expiresAt);
                    ?>
                        <p style="margin-bottom: 15px;">
                            <strong>Proximo cobro/renovacion:</strong> <?php echo esc_html($displayDate); ?>
                        </p>
                    <?php endif; ?>

                    <p style="color: #666; margin-bottom: 15px;">
                        Para cancelar tu suscripcion o actualizar tu metodo de pago, contacta con soporte.
                    </p>

                    <a href="https://wa.me/584120825234?text=Hola,%20necesito%20ayuda%20con%20mi%20suscripcion%20de%20Glory%20Amazon%20Plugin"
                        target="_blank"
                        style="display: inline-flex; align-items: center; gap: 8px; background: #25D366; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                        Contactar Soporte
                    </a>

                    <p style="margin-top: 15px; font-size: 12px; color: #666;">
                        WhatsApp: <a href="https://wa.me/584120825234" target="_blank">+58 412 082 52 34</a>
                    </p>
                </div>
            <?php endif; ?>
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
        $email = $status['email'] ?? null;

        /* 
         * Usar expires_at_formatted (string Y-m-d) en lugar de expires_at (timestamp)
         * para evitar el bug de 01/01/1970
         */
        $expiresAtFormatted = $status['expires_at_formatted'] ?? null;
        $expiresAt = $status['expires_at'] ?? 0;

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
            <?php if ($email): ?>
                <tr>
                    <th>Cuenta</th>
                    <td><code><?php echo esc_html($email); ?></code></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th>Estado</th>
                <td>
                    <span style="background: <?php echo $statusColor; ?>; color: #fff; padding: 4px 12px; border-radius: 3px; font-weight: bold;">
                        <?php echo esc_html($statusLabel); ?>
                    </span>
                </td>
            </tr>
            <?php
            /* Mostrar fecha solo si hay un valor valido */
            $displayDate = null;
            if (!empty($expiresAtFormatted) && $expiresAtFormatted !== '1970-01-01') {
                $displayDate = date('d/m/Y', strtotime($expiresAtFormatted));
            } elseif ($expiresAt > 86400) {
                $displayDate = date('d/m/Y', $expiresAt);
            }

            if ($displayDate):
            ?>
                <tr>
                    <th>Proximo Pago</th>
                    <td><?php echo esc_html($displayDate); ?></td>
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
