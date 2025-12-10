<?php

namespace Glory\Plugins\AmazonProduct\Controller;

use Glory\Plugins\AmazonProduct\Service\ProductImporter;

/**
 * Demo Controller - Generates demo products for testing.
 * Used for development and showcasing the plugin without real API data.
 */
class DemoController
{
    private const DEMO_PRODUCTS = [
        ['Echo Dot', 'Electronics', 49.99, 'https://m.media-amazon.com/images/I/714Rq4k05UL._AC_SL1000_.jpg'],
        ['Kindle Paperwhite', 'Electronics', 139.99, 'https://m.media-amazon.com/images/I/51p4-eX4g3L._AC_SL1000_.jpg'],
        ['Fire TV Stick', 'Electronics', 39.99, 'https://m.media-amazon.com/images/I/51CgKGfMelL._AC_SL1000_.jpg'],
        ['Sony Headphones', 'Audio', 348.00, 'https://m.media-amazon.com/images/I/71o8Q5XJS5L._AC_SL1500_.jpg'],
        ['AirPods Pro', 'Audio', 249.00, 'https://m.media-amazon.com/images/I/71bhWgQK-cL._AC_SL1500_.jpg'],
        ['Samsung Galaxy', 'Mobile', 799.99, 'https://m.media-amazon.com/images/I/61jY-m8kGPL._AC_SL1000_.jpg'],
        ['iPad Air', 'Tablet', 599.00, 'https://m.media-amazon.com/images/I/61XZQXFQeVL._AC_SL1500_.jpg'],
        ['MacBook Air', 'Laptop', 999.00, 'https://m.media-amazon.com/images/I/71TPda7cwUL._AC_SL1500_.jpg'],
        ['Gaming Mouse', 'Accessories', 59.99, 'https://m.media-amazon.com/images/I/61mpMH5TzkL._AC_SL1500_.jpg'],
        ['Mechanical Keyboard', 'Accessories', 129.99, 'https://m.media-amazon.com/images/I/71fthf1WJ9L._AC_SL1500_.jpg'],
    ];

    public static function render(): void
    {
        // Handle Demo Data Generation
        if (isset($_POST['generate_demo_data']) && current_user_can('manage_options')) {
            self::generateDemoData();
        }

        // Handle Update Demo Prices
        if (isset($_POST['update_demo_prices']) && current_user_can('manage_options')) {
            self::updateDemoPrices();
        }

        self::renderPage();
    }

    /**
     * Render the demo page with products grid.
     */
    private static function renderPage(): void
    {
?>
        <div class="glory-container" style="padding: 40px 20px;">
            <header class="entry-header" style="margin-bottom: 40px; text-align: center;">
                <?php if (current_user_can('manage_options')): ?>
                    <form method="post" style="margin-top: 20px; display: inline-flex; gap: 10px;">
                        <button type="submit" name="generate_demo_data" class="button button-primary">
                            Generate Demo Products
                        </button>
                        <button type="submit" name="update_demo_prices" class="button button-secondary">
                            Add Discounts to Existing
                        </button>
                    </form>
                <?php endif; ?>
            </header>

            <div class="entry-content">
                <?php echo do_shortcode('[amazon_products limit="12"]'); ?>
            </div>
        </div>
<?php
    }

    /**
     * Generate demo products for testing.
     */
    private static function generateDemoData(): void
    {
        $count = 0;

        for ($i = 0; $i < 50; $i++) {
            $asin = 'DEMO' . str_pad((string)$i, 6, '0', STR_PAD_LEFT);

            // Check if already exists
            if (ProductImporter::findByAsin($asin)) {
                self::backfillCategory($asin, $i);
                continue;
            }

            // Create new product
            $base = self::DEMO_PRODUCTS[array_rand(self::DEMO_PRODUCTS)];
            $postId = self::createDemoProduct($asin, $base, $i);

            if ($postId) {
                ProductImporter::assignCategory($postId, $base[1]);
                $count++;
            }
        }

        echo '<div class="notice notice-success inline"><p>Demo products processed. New: ' . $count . '</p></div>';
    }

    /**
     * Create a single demo product.
     */
    private static function createDemoProduct(string $asin, array $base, int $index): int|false
    {
        $title = $base[0] . ' ' . ($index + 1);
        $price = $base[2] + rand(-20, 50);
        $rating = rand(30, 50) / 10;
        $reviews = rand(10, 5000);
        $prime = rand(0, 1);

        // 60% of products will have a discount
        $hasDiscount = rand(1, 100) <= 60;
        $originalPrice = $hasDiscount ? round($price * (1 + rand(10, 40) / 100), 2) : '';

        $postId = wp_insert_post([
            'post_title' => $title,
            'post_content' => 'Demo product description for ' . $title,
            'post_status' => 'publish',
            'post_type' => 'amazon_product',
        ]);

        if ($postId && !is_wp_error($postId)) {
            update_post_meta($postId, 'asin', $asin);
            update_post_meta($postId, 'price', number_format($price, 2));
            update_post_meta($postId, 'original_price', $originalPrice ? number_format($originalPrice, 2) : '');
            update_post_meta($postId, 'rating', $rating);
            update_post_meta($postId, 'reviews', $reviews);
            update_post_meta($postId, 'prime', (string)$prime);
            update_post_meta($postId, 'image_url', $base[3]);
            return $postId;
        }

        return false;
    }

    /**
     * Backfill category for existing demo product.
     */
    private static function backfillCategory(string $asin, int $index): void
    {
        $posts = get_posts([
            'post_type' => 'amazon_product',
            'meta_key' => 'asin',
            'meta_value' => $asin,
            'post_status' => 'any',
            'posts_per_page' => 1,
        ]);

        if (empty($posts)) {
            return;
        }

        $post = $posts[0];
        foreach (self::DEMO_PRODUCTS as $bp) {
            if (strpos($post->post_title, $bp[0]) !== false) {
                ProductImporter::assignCategory($post->ID, $bp[1]);
                break;
            }
        }
    }

    /**
     * Update existing products with discount prices.
     */
    private static function updateDemoPrices(): void
    {
        $query = new \WP_Query([
            'post_type' => 'amazon_product',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);

        $updated = 0;
        foreach ($query->posts as $postId) {
            $price = get_post_meta($postId, 'price', true);
            $originalPrice = get_post_meta($postId, 'original_price', true);

            // Only update if no original_price and has price
            if (empty($originalPrice) && !empty($price)) {
                // 70% chance to add discount
                if (rand(1, 100) <= 70) {
                    $priceFloat = (float) str_replace(',', '', $price);
                    $discount = rand(10, 35) / 100;
                    $newOriginalPrice = round($priceFloat / (1 - $discount), 2);
                    update_post_meta($postId, 'original_price', number_format($newOriginalPrice, 2, '.', ''));
                    $updated++;
                }
            }
        }

        echo '<div class="notice notice-success inline"><p>Updated ' . $updated . ' products with discount prices.</p></div>';
    }
}
