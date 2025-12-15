<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

/**
 * Tab de configuracion del servidor.
 * Solo visible en modo SERVIDOR.
 */
class ServerSettingsTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'server-settings';
    }

    public function getLabel(): string
    {
        return 'Configuracion';
    }

    public function render(): void
    {
        $this->handleSave();
?>
        <div id="server-settings-tab">
            <h3>Configuracion del Servidor API</h3>

            <form method="post">
                <?php wp_nonce_field('server_settings', 'settings_nonce'); ?>

                <!-- Stripe Settings -->
                <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                    <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <span class="dashicons dashicons-money-alt" style="color: #635bff;"></span>
                        Stripe
                    </h4>

                    <table class="form-table">
                        <tr>
                            <th><label for="stripe_secret_key">Secret Key</label></th>
                            <td>
                                <input type="password"
                                    name="stripe_secret_key"
                                    id="stripe_secret_key"
                                    class="regular-text"
                                    value="<?php echo esc_attr(get_option('glory_stripe_secret_key', '')); ?>"
                                    placeholder="sk_live_... o sk_test_...">
                                <p class="description">Tu Secret Key de Stripe (nunca se expone publicamente)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="stripe_webhook_secret">Webhook Secret</label></th>
                            <td>
                                <input type="password"
                                    name="stripe_webhook_secret"
                                    id="stripe_webhook_secret"
                                    class="regular-text"
                                    value="<?php echo esc_attr(get_option('glory_stripe_webhook_secret', '')); ?>"
                                    placeholder="whsec_...">
                                <p class="description">
                                    El secret del webhook. <a href="https://dashboard.stripe.com/webhooks" target="_blank">Configurar en Stripe</a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>URL del Webhook</th>
                            <td>
                                <code style="background: #f5f5f5; padding: 8px 12px; display: inline-block; border-radius: 4px;">
                                    <?php echo esc_html(get_rest_url(null, 'glory/v1/amazon/stripe-webhook')); ?>
                                </code>
                                <p class="description">Copia esta URL en tu configuracion de webhooks de Stripe</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Link de Checkout</th>
                            <td>
                                <input type="url"
                                    name="stripe_checkout_url"
                                    id="stripe_checkout_url"
                                    class="large-text"
                                    value="<?php echo esc_attr(get_option('glory_stripe_checkout_url', '')); ?>"
                                    placeholder="https://buy.stripe.com/...">
                                <p class="description">Link de Stripe Checkout para nuevos clientes</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Proxy Settings -->
                <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                    <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <span class="dashicons dashicons-shield" style="color: #4caf50;"></span>
                        Proxy (Scraping)
                    </h4>

                    <table class="form-table">
                        <tr>
                            <th><label for="scraper_proxy">Proxy Host:Port</label></th>
                            <td>
                                <input type="text"
                                    name="scraper_proxy"
                                    id="scraper_proxy"
                                    class="regular-text"
                                    value="<?php echo esc_attr(get_option('amazon_scraper_proxy', '')); ?>"
                                    placeholder="gw.dataimpulse.com:823">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="scraper_proxy_auth">Proxy Auth</label></th>
                            <td>
                                <input type="text"
                                    name="scraper_proxy_auth"
                                    id="scraper_proxy_auth"
                                    class="regular-text"
                                    value="<?php echo esc_attr(get_option('amazon_scraper_proxy_auth', '')); ?>"
                                    placeholder="usuario:password">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="api_region">Region Amazon</label></th>
                            <td>
                                <select name="api_region" id="api_region">
                                    <?php
                                    $regions = [
                                        'es' => 'Amazon.es (Espana)',
                                        'us' => 'Amazon.com (USA)',
                                        'uk' => 'Amazon.co.uk (UK)',
                                        'de' => 'Amazon.de (Alemania)',
                                        'fr' => 'Amazon.fr (Francia)',
                                        'it' => 'Amazon.it (Italia)',
                                    ];
                                    $current = get_option('amazon_api_region', 'es');
                                    foreach ($regions as $code => $name) {
                                        $selected = $code === $current ? 'selected' : '';
                                        echo "<option value=\"{$code}\" {$selected}>{$name}</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Limites -->
                <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                    <h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <span class="dashicons dashicons-performance" style="color: #ff9800;"></span>
                        Limites por Defecto
                    </h4>

                    <table class="form-table">
                        <tr>
                            <th><label for="default_gb_limit">GB por licencia</label></th>
                            <td>
                                <input type="number"
                                    name="default_gb_limit"
                                    id="default_gb_limit"
                                    class="small-text"
                                    step="0.5"
                                    min="0.5"
                                    value="<?php echo esc_attr(get_option('glory_default_gb_limit', '4')); ?>">
                                <span>GB</span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="trial_days">Dias de trial</label></th>
                            <td>
                                <input type="number"
                                    name="trial_days"
                                    id="trial_days"
                                    class="small-text"
                                    min="1"
                                    value="<?php echo esc_attr(get_option('glory_trial_days', '30')); ?>">
                                <span>dias</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <button type="submit" name="save_settings" class="button button-primary button-large">
                        Guardar Configuracion
                    </button>
                </p>
            </form>
        </div>
<?php
    }

    /**
     * Guarda la configuracion.
     */
    private function handleSave(): void
    {
        if (!isset($_POST['settings_nonce']) || !wp_verify_nonce($_POST['settings_nonce'], 'server_settings')) {
            return;
        }

        if (!isset($_POST['save_settings'])) {
            return;
        }

        // Stripe
        update_option('glory_stripe_secret_key', sanitize_text_field($_POST['stripe_secret_key'] ?? ''));
        update_option('glory_stripe_webhook_secret', sanitize_text_field($_POST['stripe_webhook_secret'] ?? ''));
        update_option('glory_stripe_checkout_url', esc_url_raw($_POST['stripe_checkout_url'] ?? ''));

        // Proxy
        update_option('amazon_scraper_proxy', sanitize_text_field($_POST['scraper_proxy'] ?? ''));
        update_option('amazon_scraper_proxy_auth', sanitize_text_field($_POST['scraper_proxy_auth'] ?? ''));
        update_option('amazon_api_region', sanitize_text_field($_POST['api_region'] ?? 'es'));

        // Limites
        update_option('glory_default_gb_limit', (float) ($_POST['default_gb_limit'] ?? 4));
        update_option('glory_trial_days', (int) ($_POST['trial_days'] ?? 30));

        echo '<div class="notice notice-success"><p>Configuracion guardada.</p></div>';
    }
}
