<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\i18n\Labels;
use Glory\Plugins\AmazonProduct\Service\ApiUsageTracker;
use Glory\Plugins\AmazonProduct\Service\AmazonApiService;

/**
 * Config Tab - API configuration settings.
 */
class ConfigTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'settings';
    }

    public function getLabel(): string
    {
        return 'API Settings';
    }

    public function render(): void
    {
        if (isset($_POST['save_settings']) && check_admin_referer('amazon_settings_action', 'amazon_settings_nonce')) {
            $this->saveSettings();
            echo '<div class="notice notice-success inline"><p>Settings saved successfully.</p></div>';
        }

        $this->renderForm();
    }

    private function saveSettings(): void
    {
        // ARCH-01: Guardar provider seleccionado y credenciales PA-API
        update_option('amazon_api_provider', sanitize_text_field($_POST['amazon_api_provider'] ?? 'rapidapi'));
        update_option('amazon_paapi_access_key', sanitize_text_field($_POST['amazon_paapi_access_key'] ?? ''));
        update_option('amazon_paapi_secret_key', sanitize_text_field($_POST['amazon_paapi_secret_key'] ?? ''));

        // Credenciales RapidAPI
        update_option('amazon_api_key', sanitize_text_field($_POST['amazon_api_key']));
        update_option('amazon_api_host', sanitize_text_field($_POST['amazon_api_host']));

        // Configuracion general
        update_option('amazon_api_region', sanitize_text_field($_POST['amazon_api_region']));
        update_option('amazon_affiliate_tag', sanitize_text_field($_POST['amazon_affiliate_tag']));
        update_option('amazon_sync_frequency', sanitize_text_field($_POST['amazon_sync_frequency']));
        update_option('amazon_plugin_lang', sanitize_text_field($_POST['amazon_plugin_lang']));

        // API-01: Guardar configuracion de limites de API
        $cycleDay = (int) ($_POST['amazon_api_cycle_day'] ?? 1);
        $monthlyLimit = (int) ($_POST['amazon_api_monthly_limit'] ?? 500);
        ApiUsageTracker::saveConfig($cycleDay, $monthlyLimit);

        // Limpiar cache de idioma para que el cambio surta efecto inmediato
        Labels::resetCache();
    }

    private function renderForm(): void
    {
        // ARCH-01: Provider seleccionado y credenciales
        $provider = get_option('amazon_api_provider', 'rapidapi');
        $paApiAccessKey = get_option('amazon_paapi_access_key', '');
        $paApiSecretKey = get_option('amazon_paapi_secret_key', '');

        // RapidAPI
        $apiKey = get_option('amazon_api_key', '');
        $apiHost = get_option('amazon_api_host', 'amazon-data.p.rapidapi.com');

        // General
        $region = get_option('amazon_api_region', 'us');
        $affiliateTag = get_option('amazon_affiliate_tag', '');
        $syncFreq = get_option('amazon_sync_frequency', 'off');
        $lang = get_option('amazon_plugin_lang', 'default');

        // API-01: Obtener estadisticas y configuracion de uso de API
        $apiStats = ApiUsageTracker::getStats();
        $apiConfig = ApiUsageTracker::getConfig();

        // ARCH-01: Estado de cada provider
        $rapidApiStatus = AmazonApiService::checkProviderStatus('rapidapi');
        $paApiStatus = AmazonApiService::checkProviderStatus('paapi');
?>
        <!-- ARCH-01: Selector de API Provider -->
        <h3>API Provider</h3>
        <p>Selecciona el proveedor de API de Amazon a utilizar.</p>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="amazon_api_provider">Proveedor de API</label></th>
                <td>
                    <select name="amazon_api_provider" id="amazon_api_provider">
                        <option value="rapidapi" <?php selected($provider, 'rapidapi'); ?>>
                            RapidAPI (amazon-data)
                            <?php echo $rapidApiStatus['configured'] ? '- Configurado' : '- No configurado'; ?>
                        </option>
                        <option value="paapi" <?php selected($provider, 'paapi'); ?>>
                            Amazon PA-API 5.0 (oficial)
                            <?php echo $paApiStatus['configured'] ? '- Configurado' : '- No configurado'; ?>
                        </option>
                    </select>
                    <p class="description">
                        <strong>RapidAPI:</strong> Facil de configurar, requiere suscripcion a RapidAPI.<br>
                        <strong>PA-API:</strong> API oficial de Amazon, requiere cuenta de Associates aprobada.
                    </p>
                </td>
            </tr>
        </table>

        <!-- RapidAPI Configuration -->
        <h3>RapidAPI Configuration</h3>
        <form method="post">
            <?php wp_nonce_field('amazon_settings_action', 'amazon_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="amazon_api_key">RapidAPI Key</label></th>
                    <td>
                        <input type="password" name="amazon_api_key" id="amazon_api_key" value="<?php echo esc_attr($apiKey); ?>" class="regular-text">
                        <p class="description">Enter your RapidAPI Key for Amazon Data API.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="amazon_api_host">RapidAPI Host</label></th>
                    <td>
                        <input type="text" name="amazon_api_host" id="amazon_api_host" value="<?php echo esc_attr($apiHost); ?>" class="regular-text">
                    </td>
                </tr>
            </table>

            <!-- PA-API Configuration (ARCH-01) -->
            <h3>Amazon PA-API Configuration</h3>
            <p>Credenciales para la API oficial de Amazon. Solo necesarias si seleccionas PA-API como proveedor.</p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="amazon_paapi_access_key">PA-API Access Key</label></th>
                    <td>
                        <input type="text" name="amazon_paapi_access_key" id="amazon_paapi_access_key" value="<?php echo esc_attr($paApiAccessKey); ?>" class="regular-text">
                        <p class="description">Tu Access Key de Amazon Product Advertising API.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="amazon_paapi_secret_key">PA-API Secret Key</label></th>
                    <td>
                        <input type="password" name="amazon_paapi_secret_key" id="amazon_paapi_secret_key" value="<?php echo esc_attr($paApiSecretKey); ?>" class="regular-text">
                        <p class="description">Tu Secret Key de Amazon Product Advertising API.</p>
                    </td>
                </tr>
            </table>

            <!-- General Configuration -->
            <h3>General Configuration</h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="amazon_api_region">Region</label></th>
                    <td>
                        <select name="amazon_api_region" id="amazon_api_region">
                            <option value="us" <?php selected($region, 'us'); ?>>United States (US)</option>
                            <option value="es" <?php selected($region, 'es'); ?>>Spain (ES)</option>
                            <option value="uk" <?php selected($region, 'uk'); ?>>United Kingdom (UK)</option>
                            <option value="de" <?php selected($region, 'de'); ?>>Germany (DE)</option>
                            <option value="fr" <?php selected($region, 'fr'); ?>>France (FR)</option>
                            <option value="it" <?php selected($region, 'it'); ?>>Italy (IT)</option>
                            <option value="ca" <?php selected($region, 'ca'); ?>>Canada (CA)</option>
                            <option value="mx" <?php selected($region, 'mx'); ?>>Mexico (MX)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="amazon_affiliate_tag">Amazon Affiliate Tag</label></th>
                    <td>
                        <input type="text" name="amazon_affiliate_tag" id="amazon_affiliate_tag" value="<?php echo esc_attr($affiliateTag); ?>" class="regular-text">
                        <p class="description">Your Amazon Associates Tag (e.g., <code>mytag-20</code>). It will be appended to all product links.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="amazon_sync_frequency">Sync Frequency</label></th>
                    <td>
                        <select name="amazon_sync_frequency" id="amazon_sync_frequency">
                            <option value="off" <?php selected($syncFreq, 'off'); ?>>Off (Manual Only)</option>
                            <option value="daily" <?php selected($syncFreq, 'daily'); ?>>Daily</option>
                            <option value="weekly" <?php selected($syncFreq, 'weekly'); ?>>Weekly</option>
                        </select>
                        <p class="description">How often to automatically update product prices and data.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="amazon_plugin_lang">Plugin Language</label></th>
                    <td>
                        <select name="amazon_plugin_lang" id="amazon_plugin_lang">
                            <option value="default" <?php selected($lang, 'default'); ?>>Default (Site Language)</option>
                            <option value="en" <?php selected($lang, 'en'); ?>>English</option>
                            <option value="es" <?php selected($lang, 'es'); ?>>Espanol</option>
                        </select>
                    </td>
                </tr>
            </table>

            <h3>API Usage Control</h3>
            <div class="amazon-api-stats" style="background:#f9f9f9;border:1px solid #ddd;padding:15px;margin-bottom:20px;border-radius:4px;">
                <div style="display:flex;gap:30px;flex-wrap:wrap;">
                    <div>
                        <strong>Llamadas este ciclo:</strong>
                        <span style="font-size:1.2em;color:<?php echo $apiStats['isCritical'] ? '#dc3232' : ($apiStats['isWarning'] ? '#ffb900' : '#00a32a'); ?>;">
                            <?php echo esc_html($apiStats['count']); ?>
                        </span>
                        / <?php echo esc_html($apiStats['limit']); ?>
                        (<?php echo esc_html($apiStats['percentage']); ?>%)
                    </div>
                    <div>
                        <strong>Restantes:</strong> <?php echo esc_html($apiStats['remaining']); ?>
                    </div>
                    <div>
                        <strong>Ciclo:</strong>
                        <?php echo esc_html($apiStats['cycleStart']); ?> - <?php echo esc_html($apiStats['cycleEnd']); ?>
                    </div>
                </div>
                <?php if ($apiStats['isWarning']): ?>
                    <p style="color:#ffb900;margin-top:10px;"><strong>Advertencia:</strong> Has usado mas del 80% de tus llamadas mensuales.</p>
                <?php endif; ?>
                <?php if ($apiStats['isCritical']): ?>
                    <p style="color:#dc3232;margin-top:10px;"><strong>Limite alcanzado:</strong> No se pueden hacer mas llamadas hasta el proximo ciclo.</p>
                <?php endif; ?>
            </div>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="amazon_api_cycle_day">Dia de inicio del ciclo</label></th>
                    <td>
                        <input type="number" name="amazon_api_cycle_day" id="amazon_api_cycle_day"
                            value="<?php echo esc_attr($apiConfig['cycleStartDay']); ?>"
                            min="1" max="28" class="small-text">
                        <p class="description">Dia del mes en que inicia tu ciclo de facturacion de RapidAPI (1-28).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="amazon_api_monthly_limit">Limite mensual de llamadas</label></th>
                    <td>
                        <input type="number" name="amazon_api_monthly_limit" id="amazon_api_monthly_limit"
                            value="<?php echo esc_attr($apiConfig['monthlyLimit']); ?>"
                            min="0" class="small-text">
                        <p class="description">Numero maximo de llamadas por ciclo. Por defecto: 500 (plan Basic de RapidAPI).</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings', 'primary', 'save_settings'); ?>
        </form>
<?php
    }
}
