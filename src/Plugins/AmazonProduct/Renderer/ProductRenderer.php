<?php

namespace Glory\Plugins\AmazonProduct\Renderer;

class ProductRenderer
{
    public function init(): void
    {
        add_shortcode('amazon_products', [$this, 'renderShortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        wp_enqueue_style(
            'amazon-product-css', 
            get_template_directory_uri() . '/Glory/src/Plugins/AmazonProduct/assets/css/amazon-product.css',
            [],
            '1.1.0'
        );

        // Dynamic CSS from Design Settings
        $btnBg = get_option('amazon_btn_bg', '#FFD814');
        $btnColor = get_option('amazon_btn_color', '#111111');
        $priceColor = get_option('amazon_price_color', '#B12704');
        $btnBgHover = $this->adjustBrightness($btnBg, -10); // Darken by 10%

        $customCss = "
            :root {
                --amazon-accent: {$btnBg};
                --amazon-accent-hover: {$btnBgHover};
                --amazon-price: {$priceColor};
            }
            .amazon-buy-button {
                color: {$btnColor};
            }
        ";
        wp_add_inline_style('amazon-product-css', $customCss);

        wp_enqueue_script(
            'amazon-product-js',
            get_template_directory_uri() . '/Glory/src/Plugins/AmazonProduct/assets/js/amazon-product.js',
            ['jquery'],
            '1.1.0',
            true
        );

        wp_localize_script('amazon-product-js', 'amazonProductAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('amazon_product_nonce')
        ]);
    }

    private function adjustBrightness($hex, $steps) {
        // Steps should be between -255 and 255. Negative = darker, Positive = lighter
        $steps = max(-255, min(255, $steps));
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        $color_parts = str_split($hex, 2);
        $return = '#';
        foreach ($color_parts as $color) {
            $color   = hexdec($color);
            $color   = max(0, min(255, $color + $steps));
            $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT);
        }
        return $return;
    }

    private function getLabel(string $key): string
    {
        $lang = get_option('amazon_plugin_lang', 'default');
        if ($lang === 'default') {
            $lang = substr(get_locale(), 0, 2);
        }

        $strings = [
            'en' => [
                'search_placeholder' => 'Search products...',
                'min_price' => 'Min Price',
                'max_price' => 'Max Price',
                'prime_only' => 'Prime Only',
                'newest' => 'Newest First',
                'price_low' => 'Price: Low to High',
                'price_high' => 'Price: High to Low',
                'top_rated' => 'Top Rated',
                'no_results' => 'No products found.',
                'view_amazon' => 'View on Amazon',
            ],
            'es' => [
                'search_placeholder' => 'Buscar productos...',
                'min_price' => 'Precio Min',
                'max_price' => 'Precio Max',
                'prime_only' => 'Solo Prime',
                'newest' => 'Más Recientes',
                'price_low' => 'Precio: Bajo a Alto',
                'price_high' => 'Precio: Alto a Bajo',
                'top_rated' => 'Mejor Valorados',
                'no_results' => 'No se encontraron productos.',
                'view_amazon' => 'Ver en Amazon',
            ]
        ];

        // Fallback to English if lang not found
        $dict = $strings[$lang] ?? $strings['en'];
        return $dict[$key] ?? $key;
    }

    public function renderShortcode($atts): string
    {
        $atts = shortcode_atts([
            'limit' => 12,
            'min_price' => '',
            'max_price' => '',
            'only_prime' => '',
            'orderby' => 'date',
            'order' => 'DESC'
        ], $atts);

        // Initial Render
        ob_start();
        ?>
        <div class="amazon-product-wrapper" 
             data-limit="<?php echo esc_attr($atts['limit']); ?>"
             data-min-price="<?php echo esc_attr($atts['min_price']); ?>"
             data-max-price="<?php echo esc_attr($atts['max_price']); ?>"
             data-only-prime="<?php echo esc_attr($atts['only_prime']); ?>"
             data-orderby="<?php echo esc_attr($atts['orderby']); ?>"
             data-order="<?php echo esc_attr($atts['order']); ?>">
            
            <div class="amazon-product-filters">
                <div class="amazon-filter-group">
                    <input type="text" id="amazon-search" placeholder="<?php echo esc_attr($this->getLabel('search_placeholder')); ?>">
                </div>
                <div class="amazon-filter-group">
                    <input type="number" id="amazon-min-price" placeholder="<?php echo esc_attr($this->getLabel('min_price')); ?>" value="<?php echo esc_attr($atts['min_price']); ?>">
                    <input type="number" id="amazon-max-price" placeholder="<?php echo esc_attr($this->getLabel('max_price')); ?>" value="<?php echo esc_attr($atts['max_price']); ?>">
                </div>
                <div class="amazon-filter-group">
                    <label class="amazon-checkbox-label">
                        <input type="checkbox" id="amazon-prime" value="1" <?php checked('1', $atts['only_prime']); ?>> 
                        <span class="checkbox-custom"></span>
                        <?php echo esc_html($this->getLabel('prime_only')); ?>
                    </label>
                </div>
                <div class="amazon-filter-group">
                    <select id="amazon-sort">
                        <option value="date-DESC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'date-DESC'); ?>><?php echo esc_html($this->getLabel('newest')); ?></option>
                        <option value="price-ASC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'price-ASC'); ?>><?php echo esc_html($this->getLabel('price_low')); ?></option>
                        <option value="price-DESC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'price-DESC'); ?>><?php echo esc_html($this->getLabel('price_high')); ?></option>
                        <option value="rating-DESC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'rating-DESC'); ?>><?php echo esc_html($this->getLabel('top_rated')); ?></option>
                    </select>
                </div>
            </div>

            <div class="amazon-product-grid-container">
                <?php 
                // Initial load via PHP for SEO/No-JS
                $this->renderGrid($atts); 
                ?>
            </div>
            
            <div class="amazon-loader" style="display: none;">
                <div class="spinner"></div>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    public function handleAjaxRequest(): void
    {
        check_ajax_referer('amazon_product_nonce', 'nonce');

        $params = [
            'limit'      => intval($_POST['limit'] ?? 12),
            'paged'      => intval($_POST['paged'] ?? 1),
            'search'     => sanitize_text_field($_POST['search'] ?? ''),
            'min_price'  => sanitize_text_field($_POST['min_price'] ?? ''),
            'max_price'  => sanitize_text_field($_POST['max_price'] ?? ''),
            'only_prime' => sanitize_text_field($_POST['only_prime'] ?? ''),
            'orderby'    => sanitize_text_field($_POST['orderby'] ?? 'date'),
            'order'      => sanitize_text_field($_POST['order'] ?? 'DESC'),
        ];

        ob_start();
        $this->renderGrid($params);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    private function renderGrid(array $params): void
    {
        $args = [
            'post_type' => 'amazon_product',
            'posts_per_page' => $params['limit'],
            'paged' => $params['paged'] ?? 1,
            'meta_query' => [],
        ];

        // Search
        if (!empty($params['search'])) {
            $args['s'] = $params['search'];
        }

        // Price Filter
        if (!empty($params['min_price']) || !empty($params['max_price'])) {
            $price_query = ['key' => 'price', 'type' => 'NUMERIC', 'compare' => 'BETWEEN'];
            $min = !empty($params['min_price']) ? (float)$params['min_price'] : 0;
            $max = !empty($params['max_price']) ? (float)$params['max_price'] : 999999;
            $price_query['value'] = [$min, $max];
            $args['meta_query'][] = $price_query;
        }

        // Prime Filter
        if (!empty($params['only_prime'])) {
            $args['meta_query'][] = [
                'key' => 'prime',
                'value' => '1',
                'compare' => '='
            ];
        }

        // Sorting
        if ($params['orderby'] === 'price') {
            $args['meta_key'] = 'price';
            $args['orderby'] = 'meta_value_num';
        } elseif ($params['orderby'] === 'rating') {
            $args['meta_key'] = 'rating';
            $args['orderby'] = 'meta_value_num';
        } else {
            $args['orderby'] = 'date';
        }
        $args['order'] = $params['order'];

        $query = new \WP_Query($args);

        if (!$query->have_posts()) {
            echo '<p class="amazon-no-results">' . esc_html($this->getLabel('no_results')) . '</p>';
        } else {
            echo '<div class="amazon-product-grid">';
            while ($query->have_posts()) {
                $query->the_post();
                $this->renderCard(get_post());
            }
            echo '</div>';
            
            // Pagination
            $total_pages = $query->max_num_pages;
            if ($total_pages > 1) {
                echo '<div class="amazon-pagination">';
                for ($i = 1; $i <= $total_pages; $i++) {
                    $current = ($params['paged'] ?? 1);
                    $class = ($i == $current) ? 'page-numbers current noAjax' : 'page-numbers noAjax';
                    echo '<a href="#" class="' . $class . '" data-page="' . $i . '">' . $i . '</a>';
                }
                echo '</div>';
            }
        }
        
        wp_reset_postdata();
    }

    private function renderCard($post): void
    {
        $asin = get_post_meta($post->ID, 'asin', true);
        $price = get_post_meta($post->ID, 'price', true);
        $rating = get_post_meta($post->ID, 'rating', true);
        $image = get_post_meta($post->ID, 'image_url', true);
        $isPrime = get_post_meta($post->ID, 'prime', true);
        
        // Get URL and Affiliate Tag
        $productUrl = get_post_meta($post->ID, 'product_url', true);
        if (empty($productUrl)) {
            $region = get_option('amazon_api_region', 'us');
            $domain = \Glory\Plugins\AmazonProduct\Service\AmazonApiService::getDomain($region);
            $productUrl = 'https://www.' . $domain . '/dp/' . $asin;
        }

        $affiliateTag = get_option('amazon_affiliate_tag', '');
        if (!empty($affiliateTag)) {
            $separator = (strpos($productUrl, '?') !== false) ? '&' : '?';
            $productUrl .= $separator . 'tag=' . esc_attr($affiliateTag);
        }

        $btnText = get_option('amazon_btn_text', $this->getLabel('view_amazon'));
        ?>
        <div class="amazon-product-card">
            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($post->post_title); ?>" class="amazon-product-image">
            <h3 class="amazon-product-title"><?php echo esc_html($post->post_title); ?></h3>
            <div class="amazon-product-price">$<?php echo esc_html($price); ?></div>
            <div class="amazon-product-meta">
                <span>⭐ <?php echo esc_html($rating); ?></span>
                <?php if ($isPrime): ?>
                    <span class="amazon-prime-badge">Prime</span>
                <?php endif; ?>
            </div>
            <a href="<?php echo esc_url($productUrl); ?>" target="_blank" class="amazon-buy-button"><?php echo esc_html($btnText); ?></a>
        </div>
        <?php
    }
}
