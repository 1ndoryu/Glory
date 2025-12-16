<?php

namespace Glory\Plugins\AmazonProduct\Controller;

use Glory\Plugins\AmazonProduct\Admin\Tabs\TabInterface;
use Glory\Plugins\AmazonProduct\Admin\Tabs\LicensesTab;
use Glory\Plugins\AmazonProduct\Admin\Tabs\ServerStatsTab;
use Glory\Plugins\AmazonProduct\Admin\Tabs\ServerLogsTab;
use Glory\Plugins\AmazonProduct\Admin\Tabs\ServerSettingsTab;
use Glory\Plugins\AmazonProduct\Admin\Tabs\ImportTab;
use Glory\Plugins\AmazonProduct\Admin\Tabs\ManualImportTab;

/**
 * Admin Controller para el modo SERVIDOR.
 * 
 * Muestra tabs de administracion de licencias, estadisticas y configuracion.
 */
class ServerAdminController
{
    /** @var TabInterface[] */
    private array $tabs = [];

    public function init(): void
    {
        $this->registerTabs();
        add_action('admin_menu', [$this, 'registerAdminMenu']);

        /* Inicializar controladores AJAX */
        new ImportAjaxController();
        new ManualImportAjaxController();
    }

    /**
     * Registra los tabs del modo servidor.
     */
    private function registerTabs(): void
    {
        $this->tabs = [
            new LicensesTab(),
            new ServerStatsTab(),
            new ServerLogsTab(),
            new ServerSettingsTab(),
            new ImportTab(),
            new ManualImportTab(),
        ];
    }

    public function registerAdminMenu(): void
    {
        add_submenu_page(
            'edit.php?post_type=amazon_product',
            'Server Dashboard',
            'Dashboard',
            'manage_options',
            'amazon-product-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function renderSettingsPage(): void
    {
        $defaultTab = 'licenses';

        $activeTabSlug = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $defaultTab;
        $activeTab = $this->findTab($activeTabSlug);

        if (!$activeTab && !empty($this->tabs)) {
            $activeTab = $this->tabs[0];
            $activeTabSlug = $activeTab->getSlug();
        }
?>
        <div class="wrap">
            <h1>Amazon Product API Server</h1>
            <p style="color: #666;">Modo: <strong style="color: #0073aa;">SERVIDOR</strong> - Este WordPress actua como API central.</p>
            <?php $this->renderTabNavigation($activeTabSlug); ?>
            <div class="tab-content" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-top: none;">
                <?php
                if ($activeTab) {
                    $activeTab->render();
                }
                ?>
            </div>
        </div>
    <?php
    }

    /**
     * Renderiza la navegacion de tabs.
     */
    private function renderTabNavigation(string $activeSlug): void
    {
    ?>
        <h2 class="nav-tab-wrapper">
            <?php foreach ($this->tabs as $tab): ?>
                <?php
                $isActive = ($tab->getSlug() === $activeSlug) ? 'nav-tab-active' : '';
                $url = add_query_arg([
                    'post_type' => 'amazon_product',
                    'page' => 'amazon-product-settings',
                    'tab' => $tab->getSlug()
                ], admin_url('edit.php'));
                ?>
                <a href="<?php echo esc_url($url); ?>" class="nav-tab <?php echo $isActive; ?>">
                    <?php echo esc_html($tab->getLabel()); ?>
                </a>
            <?php endforeach; ?>
        </h2>
<?php
    }

    private function findTab(string $slug): ?TabInterface
    {
        foreach ($this->tabs as $tab) {
            if ($tab->getSlug() === $slug) {
                return $tab;
            }
        }
        return null;
    }
}
