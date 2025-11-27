<?php

namespace Glory\Plugins\AmazonProduct\Controller;

class AdminController
{
    public function init(): void
    {
        add_action('admin_menu', [$this, 'registerAdminMenu']);
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
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'import';
        ?>
        <div class="wrap">
            <h1>Amazon Product Integration</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?post_type=amazon_product&page=amazon-product-settings&tab=import" class="nav-tab <?php echo $active_tab == 'import' ? 'nav-tab-active' : ''; ?>">Import Products</a>
                <a href="?post_type=amazon_product&page=amazon-product-settings&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">API Settings</a>
                <a href="?post_type=amazon_product&page=amazon-product-settings&tab=design" class="nav-tab <?php echo $active_tab == 'design' ? 'nav-tab-active' : ''; ?>">Design</a>
                <a href="?post_type=amazon_product&page=amazon-product-settings&tab=updates" class="nav-tab <?php echo $active_tab == 'updates' ? 'nav-tab-active' : ''; ?>">Updates</a>
                <a href="?post_type=amazon_product&page=amazon-product-settings&tab=help" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>">Usage & Help</a>
            </h2>
            <div class="tab-content" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-top: none;">
                <?php
                switch ($active_tab) {
                    case 'import':
                        $this->renderImportTab();
                        break;
                    case 'settings':
                        $this->renderConfigTab();
                        break;
                    case 'design':
                        $this->renderDesignTab();
                        break;
                    case 'updates':
                        $this->renderUpdatesTab();
                        break;
                    case 'help':
                        $this->renderHelpTab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function renderImportTab(): void
    {
        $service = new \Glory\Plugins\AmazonProduct\Service\AmazonApiService();
        $results = [];
        $message = '';

        if (isset($_POST['amazon_search']) && check_admin_referer('amazon_search_action', 'amazon_search_nonce')) {
            $keyword = sanitize_text_field($_POST['keyword']);
            $results = $service->searchProducts($keyword);
        }

        if (isset($_POST['amazon_import']) && check_admin_referer('amazon_import_action', 'amazon_import_nonce')) {
            $asin = sanitize_text_field($_POST['asin']);
            $productData = $service->getProductByAsin($asin);
            
            if (!empty($productData)) {
                $this->importProduct($productData);
                $message = '<div class="notice notice-success inline"><p>Product imported successfully!</p></div>';
            } else {
                $message = '<div class="notice notice-error inline"><p>Failed to import product.</p></div>';
            }
        }

        echo $message;
        echo '<h3>Search & Import</h3>';
        echo '<form method="post" style="margin-bottom: 20px;">';
        wp_nonce_field('amazon_search_action', 'amazon_search_nonce');
        echo '<input type="text" name="keyword" placeholder="Search keyword..." required class="regular-text"> ';
        echo '<input type="submit" name="amazon_search" class="button button-primary" value="Search on Amazon">';
        echo '</form>';

        if (!empty($results)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th style="width: 80px;">Image</th><th>Title</th><th>ASIN</th><th>Price</th><th>Action</th></tr></thead>';
            echo '<tbody>';
            foreach ($results as $item) {
                echo '<tr>';
                echo '<td><img src="' . esc_url($item['asin_images'][0] ?? '') . '" width="50" style="border-radius: 4px;"></td>';
                echo '<td><strong>' . esc_html($item['asin_name']) . '</strong></td>';
                echo '<td>' . esc_html($item['asin']) . '</td>';
                echo '<td>' . esc_html($item['asin_price'] ?? 'N/A') . '</td>';
                echo '<td>';
                echo '<form method="post">';
                wp_nonce_field('amazon_import_action', 'amazon_import_nonce');
                echo '<input type="hidden" name="asin" value="' . esc_attr($item['asin']) . '">';
                echo '<input type="submit" name="amazon_import" class="button button-secondary" value="Import Product">';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
    }

    private function renderConfigTab(): void
    {
        if (isset($_POST['save_settings']) && check_admin_referer('amazon_settings_action', 'amazon_settings_nonce')) {
            update_option('amazon_api_key', sanitize_text_field($_POST['amazon_api_key']));
            update_option('amazon_api_host', sanitize_text_field($_POST['amazon_api_host']));
            update_option('amazon_api_region', sanitize_text_field($_POST['amazon_api_region']));
            update_option('amazon_affiliate_tag', sanitize_text_field($_POST['amazon_affiliate_tag']));
            update_option('amazon_sync_frequency', sanitize_text_field($_POST['amazon_sync_frequency']));
            update_option('amazon_plugin_lang', sanitize_text_field($_POST['amazon_plugin_lang']));
            echo '<div class="notice notice-success inline"><p>Settings saved successfully.</p></div>';
        }

        $apiKey = get_option('amazon_api_key', '');
        $apiHost = get_option('amazon_api_host', 'amazon-data.p.rapidapi.com');
        $region = get_option('amazon_api_region', 'us');
        $affiliateTag = get_option('amazon_affiliate_tag', '');
        $syncFreq = get_option('amazon_sync_frequency', 'off');
        $lang = get_option('amazon_plugin_lang', 'default');
        ?>
        <h3>API Configuration</h3>
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
                <tr>
                    <th scope="row"><label for="amazon_api_region">Region</label></th>
                    <td>
                        <select name="amazon_api_region" id="amazon_api_region">
                            <option value="us" <?php selected($region, 'us'); ?>>United States (US)</option>
                            <option value="es" <?php selected($region, 'es'); ?>>Spain (ES)</option>
                            <option value="uk" <?php selected($region, 'uk'); ?>>United Kingdom (UK)</option>
                            <option value="de" <?php selected($region, 'de'); ?>>Germany (DE)</option>
                            <option value="fr" <?php selected($region, 'fr'); ?>>France (FR)</option>
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
                            <option value="es" <?php selected($lang, 'es'); ?>>Español</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings', 'primary', 'save_settings'); ?>
        </form>
        <?php
    }

    private function renderDesignTab(): void
    {
        if (isset($_POST['save_design']) && check_admin_referer('amazon_design_action', 'amazon_design_nonce')) {
            update_option('amazon_btn_text', sanitize_text_field($_POST['amazon_btn_text']));
            update_option('amazon_btn_bg', sanitize_hex_color($_POST['amazon_btn_bg']));
            update_option('amazon_btn_color', sanitize_hex_color($_POST['amazon_btn_color']));
            update_option('amazon_price_color', sanitize_hex_color($_POST['amazon_price_color']));
            echo '<div class="notice notice-success inline"><p>Design settings saved.</p></div>';
        }

        $btnText = get_option('amazon_btn_text', 'View on Amazon');
        $btnBg = get_option('amazon_btn_bg', '#FFD814');
        $btnColor = get_option('amazon_btn_color', '#111111');
        $priceColor = get_option('amazon_price_color', '#B12704');
        ?>
        <h3>Design Customization</h3>
        <form method="post">
            <?php wp_nonce_field('amazon_design_action', 'amazon_design_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="amazon_btn_text">Button Text</label></th>
                    <td>
                        <input type="text" name="amazon_btn_text" id="amazon_btn_text" value="<?php echo esc_attr($btnText); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="amazon_btn_bg">Button Background</label></th>
                    <td>
                        <input type="color" name="amazon_btn_bg" id="amazon_btn_bg" value="<?php echo esc_attr($btnBg); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="amazon_btn_color">Button Text Color</label></th>
                    <td>
                        <input type="color" name="amazon_btn_color" id="amazon_btn_color" value="<?php echo esc_attr($btnColor); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="amazon_price_color">Price Color</label></th>
                    <td>
                        <input type="color" name="amazon_price_color" id="amazon_price_color" value="<?php echo esc_attr($priceColor); ?>">
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Design', 'primary', 'save_design'); ?>
        </form>
        <?php
    }

    private function renderUpdatesTab(): void
    {
        if (isset($_POST['sync_now']) && check_admin_referer('amazon_sync_action', 'amazon_sync_nonce')) {
            // Logic to sync all products would go here (using a background process or batching)
            // For now, we'll just show a message or sync a few items
            echo '<div class="notice notice-info inline"><p>Sync process started (simulated for now).</p></div>';
        }
        ?>
        <h3>Product Updates</h3>
        <p>Manually trigger an update for all Amazon products in your database.</p>
        <form method="post">
            <?php wp_nonce_field('amazon_sync_action', 'amazon_sync_nonce'); ?>
            <?php submit_button('Sync All Products Now', 'secondary', 'sync_now'); ?>
        </form>
        <?php
    }

    private function renderHelpTab(): void
    {
        ?>
        <h3>How to use</h3>
        <p>Use the shortcode <code>[amazon_products]</code> to display products on any page.</p>
        
        <h4>Available Attributes:</h4>
        <ul style="list-style: disc; margin-left: 20px;">
            <li><code>limit</code>: Number of products to show (default: 12).</li>
            <li><code>min_price</code>: Filter by minimum price.</li>
            <li><code>max_price</code>: Filter by maximum price.</li>
            <li><code>only_prime</code>: Set to "1" to show only Prime products.</li>
            <li><code>orderby</code>: Sort by "date", "price", "rating".</li>
            <li><code>order</code>: "ASC" or "DESC".</li>
        </ul>

        <h4>Examples:</h4>
        <p><code>[amazon_products limit="8" orderby="rating"]</code></p>
        <p><code>[amazon_products min_price="50" only_prime="1"]</code></p>
        <?php
    }

    private function importProduct(array $data): void
    {
        $region = get_option('amazon_api_region', 'us');
        $domain = \Glory\Plugins\AmazonProduct\Service\AmazonApiService::getDomain($region);
        
        // Check for existing product with same ASIN
        $existing = new \WP_Query([
            'post_type' => 'amazon_product',
            'meta_key' => 'asin',
            'meta_value' => $data['asin'],
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);

        $postData = [
            'post_title'   => $data['asin_name'],
            'post_content' => $data['asin_name'],
            'post_status'  => 'publish',
            'post_type'    => 'amazon_product',
            'meta_input'   => [
                'asin'    => $data['asin'],
                'price'   => $data['asin_price'],
                'rating'  => $data['total_start'] ?? 0,
                'reviews' => $data['total_review'] ?? 0,
                'prime'   => $data['is_prime'] ? '1' : '0',
                'image_url' => $data['asin_images'][0] ?? '',
                'product_url' => $data['product_url'] ?? 'https://www.' . $domain . '/dp/' . $data['asin'],
            ]
        ];

        if ($existing->have_posts()) {
            // Update existing
            $postData['ID'] = $existing->posts[0];
            $postId = wp_update_post($postData);
        } else {
            // Insert new
            $postId = wp_insert_post($postData);
        }

        // Handle Image Import (simplified)
        if ($postId && !empty($data['asin_images'][0])) {
            update_post_meta($postId, '_thumbnail_url_external', $data['asin_images'][0]);
        }
    }
}
