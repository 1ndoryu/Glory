<?php

namespace Glory\Plugins\AmazonProduct\Controller;

class DemoController
{
    public static function render(): void
    {
        // Handle Demo Data Generation
        if (isset($_POST['generate_demo_data']) && current_user_can('manage_options')) {
            self::generateDemoData();
        }

        ?>
        <div class="glory-container" style="padding: 40px 20px;">
            <header class="entry-header" style="margin-bottom: 40px; text-align: center;">
                <h1 class="entry-title">Amazon Plugin Demo</h1>
                <p>This page demonstrates the functionality of the Amazon Product Plugin.</p>
                
                <?php if (current_user_can('manage_options')): ?>
                    <form method="post" style="margin-top: 20px;">
                        <button type="submit" name="generate_demo_data" class="button button-primary">
                            Generate Demo Products
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

    private static function generateDemoData(): void
    {
        $base_products = [
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

        $count = 0;
        for ($i = 0; $i < 50; $i++) {
            $base = $base_products[array_rand($base_products)];
            $title = $base[0] . ' ' . ($i + 1);
            $asin = 'DEMO' . str_pad((string)$i, 6, '0', STR_PAD_LEFT);
            
            // Randomize data
            $price = $base[2] + rand(-20, 50);
            $rating = rand(30, 50) / 10; // 3.0 to 5.0
            $reviews = rand(10, 5000);
            $prime = rand(0, 1);
            
            $exists = get_posts([
                'post_type' => 'amazon_product',
                'meta_key' => 'asin',
                'meta_value' => $asin,
                'post_status' => 'any'
            ]);

            if (!$exists) {
                $post_id = wp_insert_post([
                    'post_title' => $title,
                    'post_content' => 'Demo product description for ' . $title,
                    'post_status' => 'publish',
                    'post_type' => 'amazon_product',
                ]);

                if ($post_id) {
                    update_post_meta($post_id, 'asin', $asin);
                    update_post_meta($post_id, 'price', number_format($price, 2));
                    update_post_meta($post_id, 'rating', $rating);
                    update_post_meta($post_id, 'reviews', $reviews);
                    update_post_meta($post_id, 'prime', (string)$prime);
                    update_post_meta($post_id, 'image_url', $base[3]);
                    $count++;
                }
            }
        }
        echo '<div class="notice notice-success inline"><p>Generated ' . $count . ' new demo products!</p></div>';
    }
}
