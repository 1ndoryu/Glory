<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

/**
 * Tab de configuracion para modo cliente.
 * 
 * Permite configurar opciones esenciales del cliente:
 * - Region de Amazon (para busquedas)
 * - Affiliate Tag (para comisiones)
 * - Idioma del plugin
 */
class ClientSettingsTab implements TabInterface
{
    private const REGIONES_AMAZON = [
        'es' => 'España (amazon.es)',
        'us' => 'Estados Unidos (amazon.com)',
        'mx' => 'México (amazon.com.mx)',
        'uk' => 'Reino Unido (amazon.co.uk)',
        'de' => 'Alemania (amazon.de)',
        'fr' => 'Francia (amazon.fr)',
        'it' => 'Italia (amazon.it)',
        'ca' => 'Canadá (amazon.ca)',
    ];

    public function getSlug(): string
    {
        return 'settings';
    }

    public function getLabel(): string
    {
        return 'Configuración';
    }

    public function render(): void
    {
        if (isset($_POST['guardar_configuracion']) && check_admin_referer('glory_client_settings', 'glory_settings_nonce')) {
            $this->guardarConfiguracion();
            echo '<div class="notice notice-success inline"><p>Configuración guardada correctamente.</p></div>';
        }

        $this->renderFormulario();
    }

    private function guardarConfiguracion(): void
    {
        $region = sanitize_text_field($_POST['amazon_api_region'] ?? 'es');
        $affiliateTag = sanitize_text_field($_POST['amazon_affiliate_tag'] ?? '');
        $idioma = sanitize_text_field($_POST['amazon_plugin_lang'] ?? 'default');

        update_option('amazon_api_region', $region);
        update_option('amazon_affiliate_tag', $affiliateTag);
        update_option('amazon_plugin_lang', $idioma);
    }

    private function renderFormulario(): void
    {
        $region = get_option('amazon_api_region', 'es');
        $affiliateTag = get_option('amazon_affiliate_tag', '');
        $idioma = get_option('amazon_plugin_lang', 'default');
?>
        <div id="contenedor-configuracion-cliente" class="wrap">
            <h2>Configuración del Plugin</h2>
            <p style="color: #666;">
                Configura las opciones de tu integracion con Amazon.
            </p>

            <form method="post">
                <?php wp_nonce_field('glory_client_settings', 'glory_settings_nonce'); ?>

                <!-- Seccion: Amazon -->
                <div id="seccion-amazon-config" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px; max-width: 600px;">
                    <h3 style="margin-top: 0;">
                        <span class="dashicons dashicons-amazon" style="color: #ff9900;"></span>
                        Configuración de Amazon
                    </h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="amazon_api_region">Región de Amazon</label>
                            </th>
                            <td>
                                <select name="amazon_api_region" id="amazon_api_region" style="min-width: 250px;">
                                    <?php foreach (self::REGIONES_AMAZON as $codigo => $nombre): ?>
                                        <option value="<?php echo esc_attr($codigo); ?>" <?php selected($region, $codigo); ?>>
                                            <?php echo esc_html($nombre); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    Selecciona la tienda de Amazon donde quieres buscar productos.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="amazon_affiliate_tag">Tag de Afiliado</label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    name="amazon_affiliate_tag"
                                    id="amazon_affiliate_tag"
                                    value="<?php echo esc_attr($affiliateTag); ?>"
                                    class="regular-text"
                                    placeholder="mi-tag-20">
                                <p class="description">
                                    Tu ID de afiliado de Amazon Associates (ej: <code>mitienda-21</code>).
                                    <br>Se añadirá a todos los enlaces de productos para ganar comisiones.
                                </p>

                                <?php if (empty($affiliateTag)): ?>
                                    <div class="notice notice-warning inline" style="margin-top: 10px;">
                                        <p>
                                            <strong>Importante:</strong> Sin un tag de afiliado no ganaras comisiones por las ventas.
                                            <a href="https://affiliate-program.amazon.es/" target="_blank">Registrate en Amazon Associates</a>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Seccion: Plugin -->
                <div id="seccion-plugin-config" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px; max-width: 600px;">
                    <h3 style="margin-top: 0;">
                        <span class="dashicons dashicons-admin-settings"></span>
                        Opciones del Plugin
                    </h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="amazon_plugin_lang">Idioma del Plugin</label>
                            </th>
                            <td>
                                <select name="amazon_plugin_lang" id="amazon_plugin_lang">
                                    <option value="default" <?php selected($idioma, 'default'); ?>>Automático (idioma del sitio)</option>
                                    <option value="es" <?php selected($idioma, 'es'); ?>>Español</option>
                                    <option value="en" <?php selected($idioma, 'en'); ?>>English</option>
                                </select>
                                <p class="description">
                                    Idioma de las etiquetas del plugin (precio, valoracion, etc).
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button('Guardar Configuración', 'primary', 'guardar_configuracion'); ?>
            </form>
        </div>
<?php
    }
}
