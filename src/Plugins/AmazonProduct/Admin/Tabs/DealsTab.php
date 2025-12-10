<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\Service\AmazonApiService;
use Glory\Plugins\AmazonProduct\Service\ProductImporter;

/**
 * Deals Tab - Import deals with original prices and discounts.
 */
class DealsTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'deals';
    }

    public function getLabel(): string
    {
        return 'Import Deals';
    }

    public function render(): void
    {
        $service = new AmazonApiService();
        $message = '';
        $page = isset($_GET['deals_page']) ? max(1, intval($_GET['deals_page'])) : 1;

        // Load deals
        if (isset($_POST['load_deals']) && check_admin_referer('amazon_deals_action', 'amazon_deals_nonce')) {
            $page = 1;
        }

        $deals = $service->getDeals($page);

        // Import single deal
        if (isset($_POST['import_deal']) && check_admin_referer('amazon_import_deal_action', 'amazon_import_deal_nonce')) {
            $dealData = json_decode(stripslashes($_POST['deal_data']), true);
            if (!empty($dealData)) {
                $postId = ProductImporter::importDeal($dealData);
                if ($postId) {
                    $message = '<div class="notice notice-success inline"><p>Deal imported successfully with original price!</p></div>';
                }
            }
        }

        // Import all visible deals
        if (isset($_POST['import_all_deals']) && check_admin_referer('amazon_import_all_deals_action', 'amazon_import_all_deals_nonce')) {
            $count = 0;
            foreach ($deals as $deal) {
                if (ProductImporter::importDeal($deal)) {
                    $count++;
                }
            }
            $message = '<div class="notice notice-success inline"><p>' . $count . ' deals imported successfully!</p></div>';
        }

        echo $message;
        $this->renderHeader();
        $this->renderLoadForm();
        $this->renderDealsTable($deals, $page);
    }

    private function renderHeader(): void
    {
?>
        <h3>Import Deals (Offers with Discounts)</h3>
        <p>These products include original price and discount percentage from Amazon's deals endpoint.</p>
    <?php
    }

    private function renderLoadForm(): void
    {
    ?>
        <form method="post" style="margin-bottom: 20px;">
            <?php wp_nonce_field('amazon_deals_action', 'amazon_deals_nonce'); ?>
            <input type="submit" name="load_deals" class="button button-primary" value="Load Current Deals">
        </form>
    <?php
    }

    private function renderDealsTable(array $deals, int $page): void
    {
        if (empty($deals)) {
            echo '<p>No deals found. Click "Load Current Deals" to fetch offers from Amazon.</p>';
            return;
        }
    ?>
        <form method="post" style="margin-bottom: 20px;">
            <?php wp_nonce_field('amazon_import_all_deals_action', 'amazon_import_all_deals_nonce'); ?>
            <input type="submit" name="import_all_deals" class="button button-secondary" value="Import All Deals (<?php echo count($deals); ?>)">
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 80px;">Image</th>
                    <th>Title</th>
                    <th>ASIN</th>
                    <th>Original</th>
                    <th>Price</th>
                    <th>Discount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deals as $deal): ?>
                    <tr>
                        <td>
                            <img src="<?php echo esc_url($deal['asin_image'] ?? ''); ?>" width="50" style="border-radius: 4px;">
                        </td>
                        <td><strong><?php echo esc_html($deal['deal_title'] ?? 'N/A'); ?></strong></td>
                        <td><?php echo esc_html($deal['asin'] ?? 'N/A'); ?></td>
                        <td style="text-decoration: line-through; color: #999;">
                            <?php echo esc_html($deal['deal_min_list_price'] ?? 'N/A'); ?> <?php echo esc_html($deal['deal_currency'] ?? ''); ?>
                        </td>
                        <td style="color: #B12704; font-weight: bold;">
                            <?php echo esc_html($deal['deal_min_price'] ?? 'N/A'); ?> <?php echo esc_html($deal['deal_currency'] ?? ''); ?>
                        </td>
                        <td>
                            <span style="background: #cc0c39; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 12px;">
                                -<?php echo esc_html($deal['deal_min_percent_off'] ?? 0); ?>%
                            </span>
                        </td>
                        <td>
                            <form method="post">
                                <?php wp_nonce_field('amazon_import_deal_action', 'amazon_import_deal_nonce'); ?>
                                <input type="hidden" name="deal_data" value="<?php echo esc_attr(json_encode($deal)); ?>">
                                <input type="submit" name="import_deal" class="button button-secondary" value="Import">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php $this->renderPagination($page); ?>
    <?php
    }

    private function renderPagination(int $page): void
    {
    ?>
        <div style="margin-top: 20px;">
            <?php if ($page > 1): ?>
                <a href="<?php echo add_query_arg('deals_page', $page - 1); ?>" class="button">Previous Page</a>
            <?php endif; ?>
            <a href="<?php echo add_query_arg('deals_page', $page + 1); ?>" class="button">Next Page</a>
            <span style="margin-left: 10px;">Page <?php echo $page; ?></span>
        </div>
<?php
    }
}
