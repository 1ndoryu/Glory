<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\Service\ApiClient;
use Glory\Plugins\AmazonProduct\Mode\PluginMode;

/**
 * Import Products Tab - UI para buscar e importar productos de Amazon.
 * 
 * Responsabilidad unica: Renderizar la interfaz de usuario.
 * La logica AJAX esta en ImportAjaxController.
 */
class ImportTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'import';
    }

    public function getLabel(): string
    {
        return 'Import Products';
    }

    public function render(): void
    {
        $affiliateTag = get_option('amazon_affiliate_tag', '');
        $region = get_option('amazon_api_region', 'es');
        $isClientMode = PluginMode::isClient();

        if ($isClientMode && !PluginMode::getApiKey()) {
            $this->renderApiKeyError();
            return;
        }

        if (empty($affiliateTag)) {
            $this->renderAffiliateWarning();
        }

        $this->renderUsageWidget();
        $this->renderSearchForm($region);
        $this->enqueueScripts();
    }

    /**
     * Renderiza el formulario de busqueda.
     */
    private function renderSearchForm(string $region): void
    {
?>
        <div class="wrap amazon-import-tab">
            <h3>Buscar e Importar</h3>
            <p style="color: #666;">
                Busca productos en Amazon y importalos a tu tienda.
                <strong>Region actual:</strong> Amazon.<?php echo esc_html($region === 'es' ? 'es' : $region); ?>
            </p>

            <div class="search-box" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
                <input type="text" id="amazon-search-keyword" placeholder="Buscar producto..." class="regular-text" style="min-width: 300px;">
                <button type="button" id="amazon-search-btn" class="button button-primary">Buscar en Amazon</button>
            </div>

            <div id="amazon-search-results"></div>
        </div>
    <?php
    }

    /**
     * Encola los scripts necesarios.
     */
    private function enqueueScripts(): void
    {
        $jsPath = get_template_directory_uri() . '/Glory/src/Plugins/AmazonProduct/assets/js/import-tab.js';

        wp_enqueue_script('amazon-import-tab', $jsPath, ['jquery'], '1.0.0', true);

        wp_localize_script('amazon-import-tab', 'amazonImportConfig', [
            'searchNonce' => wp_create_nonce('amazon_search_ajax'),
            'importNonce' => wp_create_nonce('amazon_import_ajax')
        ]);
    }

    /**
     * Renderiza error de API Key no configurada.
     */
    private function renderApiKeyError(): void
    {
        echo '<div class="notice notice-error inline"><p><strong>API Key no configurada.</strong> Ve a la pestana "Licencia" para activar tu suscripcion.</p></div>';
    }

    /**
     * Renderiza advertencia de affiliate tag.
     */
    private function renderAffiliateWarning(): void
    {
        echo '<div class="notice notice-warning inline"><p><strong>Tag de Afiliado no configurado.</strong> Ve a "Settings" para configurar tu Amazon Affiliate Tag y empezar a ganar comisiones.</p></div>';
    }

    /**
     * Renderiza el widget de uso de datos (solo en modo cliente).
     */
    private function renderUsageWidget(): void
    {
        if (!PluginMode::isClient()) {
            return;
        }

        $client = new ApiClient();
        $result = $client->getLicenseStatus();

        if (!$result['success'] || empty($result['status'])) {
            return;
        }

        $status = $result['status'];
        $gbUsed = $status['gb_used'] ?? 0;
        $gbLimit = $status['gb_limit'] ?? 4;
        $gbRemaining = max(0, $gbLimit - $gbUsed);
        $percentUsed = $gbLimit > 0 ? ($gbUsed / $gbLimit) * 100 : 0;
        $barColor = $percentUsed > 80 ? '#dc3232' : ($percentUsed > 60 ? '#f39c12' : '#46b450');
    ?>
        <div id="widget-uso-datos" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px; margin-bottom: 20px; max-width: 400px;">
            <h4 style="margin: 0 0 10px 0; font-size: 14px;">Uso de Datos</h4>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="flex: 1; background: #e0e0e0; border-radius: 10px; overflow: hidden;">
                    <div style="width: <?php echo min(100, $percentUsed); ?>%; height: 12px; background: <?php echo $barColor; ?>; transition: width 0.3s;"></div>
                </div>
                <span style="font-weight: bold; font-size: 13px;">
                    <?php echo number_format($gbUsed, 2); ?> / <?php echo $gbLimit; ?> GB
                </span>
            </div>
            <p style="margin: 8px 0 0 0; font-size: 12px; color: #666;">
                Te quedan <strong><?php echo number_format($gbRemaining, 2); ?> GB</strong> este mes.
            </p>
        </div>
<?php
    }
}
