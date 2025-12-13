<?php

namespace Glory\Plugins\AmazonProduct\Controller;

use Glory\Plugins\AmazonProduct\Admin\Tabs\TabInterface;
use Glory\Plugins\AmazonProduct\Admin\Tabs\ImportTab;
use Glory\Plugins\AmazonProduct\Admin\Tabs\DealsTab;
use Glory\Plugins\AmazonProduct\Admin\Tabs\ConfigTab;
use Glory\Plugins\AmazonProduct\Admin\Tabs\DesignTab;
use Glory\Plugins\AmazonProduct\Admin\Tabs\UpdatesTab;
use Glory\Plugins\AmazonProduct\Admin\Tabs\HelpTab;
use Glory\Plugins\AmazonProduct\Admin\Tabs\ManualImportTab;
use Glory\Plugins\AmazonProduct\Admin\Tabs\ApiSetupWizardTab;

/**
 * Admin Controller for Amazon Product Plugin.
 * 
 * Refactored to use separate Tab classes following SRP.
 * This class now only orchestrates tab registration and rendering.
 */
class AdminController
{
    /** @var TabInterface[] */
    private array $tabs = [];

    public function init(): void
    {
        $this->registerTabs();
        add_action('admin_menu', [$this, 'registerAdminMenu']);
    }

    /**
     * Register all available tabs.
     * New tabs can be added here easily.
     */
    private function registerTabs(): void
    {
        $this->tabs = [
            new ApiSetupWizardTab(),
            new ImportTab(),
            new DealsTab(),
            new ConfigTab(),
            new DesignTab(),
            new UpdatesTab(),
            new HelpTab(),
            new ManualImportTab(),
        ];
    }

    public function registerAdminMenu(): void
    {
        add_submenu_page(
            'edit.php?post_type=amazon_product',
            'Amazon Settings',
            'Settings',
            'manage_options',
            'amazon-product-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function renderSettingsPage(): void
    {
        // Determinar tab por defecto: si no hay API Key, mostrar wizard
        $apiKey = get_option('amazon_api_key', '');
        $defaultTab = empty($apiKey) ? 'api-setup-wizard' : 'import';

        // Sanitizar y validar el tab activo
        $activeTabSlug = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $defaultTab;
        $activeTab = $this->findTab($activeTabSlug);

        // Si no se encuentra el tab, usar el primero
        if (!$activeTab && !empty($this->tabs)) {
            $activeTab = $this->tabs[0];
            $activeTabSlug = $activeTab->getSlug();
        }
?>
        <div class="wrap">
            <h1>Amazon Product Integration</h1>
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
     * Render the tab navigation header.
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

    /**
     * Find a tab by its slug.
     */
    private function findTab(string $slug): ?TabInterface
    {
        foreach ($this->tabs as $tab) {
            if ($tab->getSlug() === $slug) {
                return $tab;
            }
        }
        return null;
    }

    /**
     * Get all registered tabs.
     * Useful for external access or testing.
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    /**
     * Add a custom tab dynamically.
     * Allows plugins/themes to extend the admin interface.
     */
    public function addTab(TabInterface $tab): void
    {
        $this->tabs[] = $tab;
    }
}
