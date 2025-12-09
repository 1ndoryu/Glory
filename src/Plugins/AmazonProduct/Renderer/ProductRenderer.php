<?php

namespace Glory\Plugins\AmazonProduct\Renderer;

class ProductRenderer
{
    public function init(): void
    {
        add_shortcode('amazon_products', [$this, 'renderShortcode']);
        add_shortcode('amazon_deals', [$this, 'renderDealsShortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        // Usar AssetManager para cache busting automatico (usa filemtime en dev mode)
        \Glory\Manager\AssetManager::define(
            'style',
            'amazon-product-css',
            '/Glory/src/Plugins/AmazonProduct/assets/css/amazon-product.css',
            ['dev_mode' => true] // Forzar cache busting basado en filemtime
        );

        \Glory\Manager\AssetManager::define(
            'script',
            'amazon-product-js',
            '/Glory/src/Plugins/AmazonProduct/assets/js/amazon-product.js',
            [
                'dev_mode' => true,
                'in_footer' => true,
                'localize' => [
                    'nombreObjeto' => 'amazonProductAjax',
                    'datos' => [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonce'    => wp_create_nonce('amazon_product_nonce')
                    ]
                ]
            ]
        );

        // Dynamic CSS from Design Settings (inline)
        $btnBg = get_option('amazon_btn_bg', '#FFD814');
        $btnColor = get_option('amazon_btn_color', '#111111');
        $priceColor = get_option('amazon_price_color', '#B12704');
        $btnBgHover = $this->adjustBrightness($btnBg, -10);

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

        // Agregar inline style despues de que se encole el CSS principal
        add_action('wp_enqueue_scripts', function () use ($customCss) {
            wp_add_inline_style('amazon-product-css', $customCss);
        }, 21);
    }

    private function adjustBrightness($hex, $steps)
    {
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
                'deals_only' => 'Deals Only',
                'newest' => 'Newest First',
                'best_discount' => 'Best Discount',
                'price_low' => 'Price: Low to High',
                'price_high' => 'Price: High to Low',
                'top_rated' => 'Top Rated',
                'no_results' => 'No products found.',
                'view_amazon' => 'View on Amazon',
                'categories' => 'Categories',
            ],
            'es' => [
                'search_placeholder' => 'Buscar productos...',
                'min_price' => 'Precio Min',
                'max_price' => 'Precio Max',
                'prime_only' => 'Solo Prime',
                'deals_only' => 'Solo Ofertas',
                'newest' => 'Mas Recientes',
                'best_discount' => 'Mayor Descuento',
                'price_low' => 'Precio: Bajo a Alto',
                'price_high' => 'Precio: Alto a Bajo',
                'top_rated' => 'Mejor Valorados',
                'no_results' => 'No se encontraron productos.',
                'view_amazon' => 'Ver en Amazon',
                'categories' => 'Categorias',
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
            'category'  => '',
            'only_prime' => '',
            'only_deals' => '',
            'orderby' => 'date',
            'order' => 'DESC'
        ], $atts);

        ob_start();
?>
        <div class="amazon-product-wrapper"
            data-limit="<?php echo esc_attr($atts['limit']); ?>"
            data-min-price="<?php echo esc_attr($atts['min_price']); ?>"
            data-min-price="<?php echo esc_attr($atts['min_price']); ?>"
            data-max-price="<?php echo esc_attr($atts['max_price']); ?>"
            data-category="<?php echo esc_attr($atts['category']); ?>"
            data-only-prime="<?php echo esc_attr($atts['only_prime']); ?>"
            data-orderby="<?php echo esc_attr($atts['orderby']); ?>"
            data-order="<?php echo esc_attr($atts['order']); ?>">

            <!-- Header & Search -->
            <div class="amazon-header-controls">
                <div class="amazon-search-container">
                    <input type="text" id="amazon-search" placeholder="<?php echo esc_attr($this->getLabel('search_placeholder')); ?>">
                    <svg class="amazon-icon-search" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </div>

                <button id="amazon-toggle-filters" class="amazon-btn-filters">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" x2="20" y1="21" y2="21" />
                        <line x1="4" x2="20" y1="3" y2="3" />
                        <line x1="4" x2="20" y1="12" y2="12" />
                        <line x1="4" x2="8" y1="8" y2="16" />
                        <line x1="16" x2="20" y1="8" y2="16" />
                    </svg>
                    <span>Filtros</span>
                    <svg class="amazon-icon-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </button>
            </div>

            <!-- Filter Panel (Hidden by default) -->
            <div id="amazon-filter-panel" class="amazon-filter-panel">
                <div class="amazon-filter-grid">

                    <!-- Category Filter -->
                    <div class="amazon-filter-col">
                        <h3><?php echo esc_html($this->getLabel('categories')); ?></h3>
                        <div class="amazon-category-list">
                            <?php
                            $terms = get_terms([
                                'taxonomy' => 'amazon_category',
                                'hide_empty' => true,
                            ]);
                            if (!empty($terms) && !is_wp_error($terms)) {
                                foreach ($terms as $term) {
                                    $isActive = ($atts['category'] == $term->slug) ? 'active' : '';
                                    echo '<button class="amazon-category-btn ' . $isActive . '" data-slug="' . esc_attr($term->slug) . '">';
                                    echo esc_html($term->name) . ' <span class="count">(' . $term->count . ')</span>';
                                    echo '</button>';
                                }
                            } else {
                                echo '<p class="amazon-no-cats">No hay categorías.</p>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Price Filter -->
                    <div class="amazon-filter-col">
                        <h3><?php echo esc_html($this->getLabel('max_price')); ?>: <span id="price-display">2000</span>€</h3>
                        <div class="amazon-range-wrapper">
                            <input type="range" id="amazon-max-price-range" min="0" max="2000" step="50" value="2000">
                            <div class="amazon-range-labels">
                                <span>0€</span>
                                <span>1000€</span>
                                <span>2000€+</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rating Filter -->
                    <div class="amazon-filter-col">
                        <h3><?php echo esc_html($this->getLabel('top_rated')); ?></h3>
                        <div class="amazon-rating-list">
                            <?php foreach ([4, 3, 2, 1] as $star): ?>
                                <button class="amazon-rating-btn" data-rating="<?php echo $star; ?>">
                                    <div class="amazon-radio-circle"></div>
                                    <div class="amazon-stars">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="<?php echo $i < $star ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="<?php echo $i < $star ? 'star-filled' : 'star-empty'; ?>">
                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <span>& más</span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Sort / Prime / Deals -->
                    <div class="amazon-filter-col">
                        <h3>Opciones</h3>
                        <label class="amazon-checkbox-label">
                            <input type="checkbox" id="amazon-prime" value="1" <?php checked('1', $atts['only_prime']); ?>>
                            <span class="checkbox-custom"></span>
                            <?php echo esc_html($this->getLabel('prime_only')); ?>
                        </label>
                        <label class="amazon-checkbox-label">
                            <input type="checkbox" id="amazon-deals" value="1" <?php checked('1', $atts['only_deals']); ?>>
                            <span class="checkbox-custom"></span>
                            <?php echo esc_html($this->getLabel('deals_only')); ?>
                        </label>

                        <div class="amazon-sort-wrapper">
                            <select id="amazon-sort">
                                <option value="date-DESC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'date-DESC'); ?>><?php echo esc_html($this->getLabel('newest')); ?></option>
                                <option value="discount-DESC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'discount-DESC'); ?>><?php echo esc_html($this->getLabel('best_discount')); ?></option>
                                <option value="price-ASC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'price-ASC'); ?>><?php echo esc_html($this->getLabel('price_low')); ?></option>
                                <option value="price-DESC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'price-DESC'); ?>><?php echo esc_html($this->getLabel('price_high')); ?></option>
                                <option value="rating-DESC" <?php selected($atts['orderby'] . '-' . $atts['order'], 'rating-DESC'); ?>><?php echo esc_html($this->getLabel('top_rated')); ?></option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="amazon-filter-footer">
                    <button id="amazon-reset-filters">Restablecer todos los filtros</button>
                </div>
            </div>

            <!-- Results Header -->
            <div class="amazon-results-header">
                <h2>Productos</h2>
                <span class="amazon-count-badge"><span id="amazon-total-count">...</span> resultados</span>
            </div>

            <div class="amazon-product-grid-container">
                <?php $this->renderGrid($atts); ?>
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
            'category'   => sanitize_text_field($_POST['category'] ?? ''),
            'min_price'  => sanitize_text_field($_POST['min_price'] ?? ''),
            'max_price'  => sanitize_text_field($_POST['max_price'] ?? ''),
            'min_rating' => sanitize_text_field($_POST['min_rating'] ?? ''),
            'only_prime' => sanitize_text_field($_POST['only_prime'] ?? ''),
            'only_deals' => sanitize_text_field($_POST['only_deals'] ?? ''),
            'orderby'    => sanitize_text_field($_POST['orderby'] ?? 'date'),
            'order'      => sanitize_text_field($_POST['order'] ?? 'DESC'),
        ];

        ob_start();
        $count = $this->renderGrid($params);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'count' => $count]);
    }

    private function renderGrid(array $params): int
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

        // Category Filter
        if (!empty($params['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'amazon_category',
                    'field'    => 'slug',
                    'terms'    => $params['category'],
                ]
            ];
        }

        // Price Filter
        if (!empty($params['max_price'])) {
            $args['meta_query'][] = [
                'key' => 'price',
                'value' => (float)$params['max_price'],
                'compare' => '<=',
                'type' => 'NUMERIC'
            ];
        }

        // Rating Filter
        if (!empty($params['min_rating'])) {
            $args['meta_query'][] = [
                'key' => 'rating',
                'value' => (float)$params['min_rating'],
                'compare' => '>=',
                'type' => 'NUMERIC'
            ];
        }

        // Prime Filter
        if (!empty($params['only_prime'])) {
            $args['meta_query'][] = [
                'key' => 'prime',
                'value' => '1',
                'compare' => '='
            ];
        }

        // Deals Filter - Solo productos con precio original (descuento)
        // Verifica que original_price exista, no este vacio y sea mayor a 0
        if (!empty($params['only_deals'])) {
            $args['meta_query'][] = [
                'relation' => 'AND',
                [
                    'key' => 'original_price',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => 'original_price',
                    'value' => ['', '0', '0.00'],
                    'compare' => 'NOT IN'
                ]
            ];
        }

        // Sorting
        // El ordenamiento por descuento se hace en PHP despues del query
        // porque requiere calculo dinamico entre price y original_price
        $sortByDiscount = ($params['orderby'] === 'discount');

        if ($params['orderby'] === 'price') {
            $args['meta_key'] = 'price';
            $args['orderby'] = 'meta_value_num';
        } elseif ($params['orderby'] === 'rating') {
            $args['meta_key'] = 'rating';
            $args['orderby'] = 'meta_value_num';
        } elseif ($sortByDiscount) {
            // Traemos todos para ordenar por descuento en PHP
            $args['posts_per_page'] = -1;
            $args['orderby'] = 'date';
        } else {
            $args['orderby'] = 'date';
        }
        $args['order'] = $params['order'];

        $query = new \WP_Query($args);
        $total_posts = $query->found_posts;

        // Si ordenamos por descuento, procesamos manualmente
        if ($sortByDiscount && $query->have_posts()) {
            $postsWithDiscount = [];

            while ($query->have_posts()) {
                $query->the_post();
                $post = get_post();
                $price = (float) get_post_meta($post->ID, 'price', true);
                $originalPrice = (float) get_post_meta($post->ID, 'original_price', true);
                $discount = $this->calculateDiscount($originalPrice, $price);

                $postsWithDiscount[] = [
                    'post' => $post,
                    'discount' => $discount
                ];
            }

            // Ordenar por descuento (mayor primero)
            usort($postsWithDiscount, function ($a, $b) {
                return $b['discount'] - $a['discount'];
            });

            // Aplicar paginacion manual
            $limit = (int) $params['limit'];
            $paged = (int) ($params['paged'] ?? 1);
            $offset = ($paged - 1) * $limit;
            $pagedPosts = array_slice($postsWithDiscount, $offset, $limit);
            $total_posts = count($postsWithDiscount);
            $total_pages = ceil($total_posts / $limit);

            if (empty($pagedPosts)) {
                echo '<div class="amazon-empty-state">';
                echo '<div class="amazon-empty-icon"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg></div>';
                echo '<h3>' . esc_html($this->getLabel('no_results')) . '</h3>';
                echo '<button id="amazon-clear-search">Limpiar busqueda</button>';
                echo '</div>';
            } else {
                echo '<div class="amazon-product-grid">';
                foreach ($pagedPosts as $item) {
                    $this->renderCard($item['post']);
                }
                echo '</div>';

                // Pagination
                if ($total_pages > 1) {
                    echo '<div class="amazon-pagination">';
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $class = ($i == $paged) ? 'page-numbers current noAjax' : 'page-numbers noAjax';
                        echo '<a href="#" class="' . $class . '" data-page="' . $i . '">' . $i . '</a>';
                    }
                    echo '</div>';
                }
            }
        } elseif (!$query->have_posts()) {
            echo '<div class="amazon-empty-state">';
            echo '<div class="amazon-empty-icon"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg></div>';
            echo '<h3>' . esc_html($this->getLabel('no_results')) . '</h3>';
            echo '<button id="amazon-clear-search">Limpiar busqueda</button>';
            echo '</div>';
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
        return $total_posts;
    }

    /**
     * Calcula el porcentaje de descuento entre precio original y actual
     */
    private function calculateDiscount(float $originalPrice, float $currentPrice): int
    {
        if ($originalPrice <= 0 || $originalPrice <= $currentPrice) {
            return 0;
        }
        return (int) round((($originalPrice - $currentPrice) / $originalPrice) * 100);
    }

    private function renderCard($post): void
    {
        $asin = get_post_meta($post->ID, 'asin', true);
        $price = get_post_meta($post->ID, 'price', true);
        $originalPrice = get_post_meta($post->ID, 'original_price', true);
        $rating = get_post_meta($post->ID, 'rating', true);
        $image = get_post_meta($post->ID, 'image_url', true);
        $isPrime = get_post_meta($post->ID, 'prime', true);

        // Calcular descuento
        $discount = $this->calculateDiscount((float) $originalPrice, (float) $price);
        $hasDiscount = $discount > 0;

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
    ?>
        <div class="amazon-product-card group">
            <div class="amazon-card-image-wrapper">
                <?php if ($hasDiscount): ?>
                    <span class="amazon-discount-badge">-<?php echo $discount; ?>%</span>
                <?php elseif ($isPrime): ?>
                    <span class="amazon-prime-badge">PRIME</span>
                <?php endif; ?>
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($post->post_title); ?>" class="amazon-product-image" loading="lazy">
                <div class="amazon-card-overlay"></div>
            </div>

            <div class="amazon-card-content">
                <div class="amazon-card-cat">Amazon</div>
                <h3 class="amazon-card-title"><?php echo esc_html($post->post_title); ?></h3>

                <div class="amazon-card-rating">
                    <div class="amazon-stars">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="<?php echo $i < $rating ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="<?php echo $i < $rating ? 'star-filled' : 'star-empty'; ?>">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-count">(<?php echo esc_html($rating); ?>)</span>
                </div>

                <div class="amazon-card-footer">
                    <div class="amazon-price-block">
                        <?php if ($hasDiscount && !empty($originalPrice)): ?>
                            <span class="price-original"><?php echo esc_html($originalPrice); ?></span>
                        <?php endif; ?>
                        <span class="price-value"><?php echo esc_html($price); ?></span>
                    </div>
                    <a href="<?php echo esc_url($productUrl); ?>" target="_blank" rel="noopener noreferrer" class="amazon-buy-btn-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                            <polyline points="15 3 21 3 21 9" />
                            <line x1="10" x2="21" y1="14" y2="3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    <?php
    }
    public function renderDealsShortcode($atts): string
    {
        $atts = shortcode_atts([
            'limit' => 12,
            'page'  => 1,
        ], $atts);

        $service = new \Glory\Plugins\AmazonProduct\Service\AmazonApiService();
        $deals = $service->getDeals((int)$atts['page']);

        if (empty($deals)) {
            return '<p class="amazon-no-deals">No hay ofertas disponibles en este momento.</p>';
        }

        // Limit results if needed (API returns paginated results, so limit is per page usually)
        $deals = array_slice($deals, 0, $atts['limit']);

        ob_start();
    ?>
        <div class="amazon-deals-wrapper">
            <div class="amazon-results-header">
                <h2>Ofertas del Día</h2>
                <span class="amazon-count-badge">Amazon Deals</span>
            </div>

            <div class="amazon-product-grid">
                <?php foreach ($deals as $deal): ?>
                    <?php $this->renderDealCard($deal); ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    private function renderDealCard(array $deal): void
    {
        $asin = $deal['asin'];
        $image = $deal['asin_image'];
        $title = $deal['deal_title'];
        $price = $deal['deal_min_price'];
        $originalPrice = $deal['deal_min_list_price'];
        $discount = $deal['deal_min_percent_off'];
        $rating = $deal['asin_rating_star'];
        $reviews = $deal['asin_total_review'];

        $region = get_option('amazon_api_region', 'us');
        $domain = \Glory\Plugins\AmazonProduct\Service\AmazonApiService::getDomain($region);
        $productUrl = 'https://www.' . $domain . '/dp/' . $asin;

        $affiliateTag = get_option('amazon_affiliate_tag', '');
        if (!empty($affiliateTag)) {
            $productUrl .= '?tag=' . esc_attr($affiliateTag);
        }
    ?>
        <div class="amazon-product-card group">
            <div class="amazon-card-image-wrapper">
                <span class="amazon-prime-badge" style="background: #cc0c39;">-<?php echo esc_html($discount); ?>%</span>
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" class="amazon-product-image">
                <div class="amazon-card-overlay"></div>
            </div>

            <div class="amazon-card-content">
                <div class="amazon-card-cat">Oferta Flash</div>
                <h3 class="amazon-card-title"><?php echo esc_html($title); ?></h3>

                <div class="amazon-card-rating">
                    <div class="amazon-stars">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="<?php echo $i < $rating ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="<?php echo $i < $rating ? 'star-filled' : 'star-empty'; ?>">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-count">(<?php echo esc_html($reviews); ?>)</span>
                </div>

                <div class="amazon-card-footer">
                    <div class="amazon-price-block">
                        <span class="price-label" style="text-decoration: line-through; color: #737373; font-size: 10px;"><?php echo esc_html($originalPrice); ?>€</span>
                        <span class="price-value" style="color: #B12704;"><?php echo esc_html($price); ?>€</span>
                    </div>
                    <a href="<?php echo esc_url($productUrl); ?>" target="_blank" class="amazon-buy-btn-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                            <polyline points="15 3 21 3 21 9" />
                            <line x1="10" x2="21" y1="14" y2="3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
<?php
    }
}
