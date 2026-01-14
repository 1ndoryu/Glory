<?php

namespace Glory\Plugins\AmazonProduct\Renderer;

use Glory\Plugins\AmazonProduct\i18n\Labels;
use Glory\Plugins\AmazonProduct\Service\DiscountCalculator;

/**
 * Deals Renderer - Renderiza el shortcode [amazon_deals].
 * 
 * Responsabilidad unica: Mostrar productos guardados que tienen descuento.
 * NO llama a la API - solo lee productos ya importados con original_price.
 */
class DealsRenderer
{
    /**
     * Renderiza el shortcode [amazon_deals].
     * 
     * @param array $atts Atributos del shortcode
     * @return string HTML del shortcode
     */
    public function render(array $atts): string
    {
        $atts = shortcode_atts([
            'limit' => 12,
            'orderby' => 'discount',
            'order' => 'DESC',
            'category' => '',
            'show_sort' => '1',
        ], $atts);

        $query = $this->buildDealsQuery($atts);

        if (!$query->have_posts()) {
            return '<p class="amazon-no-deals">' . esc_html(Labels::get('no_deals')) . '</p>';
        }

        $deals = $this->collectAndSortDeals($query, $atts);
        $limitedDeals = array_slice($deals, 0, (int) $atts['limit']);
        $visibleCount = count($limitedDeals);
        $showSort = ($atts['show_sort'] === '1');
        $currentSort = $atts['orderby'] . '-' . $atts['order'];

        ob_start();
?>
        <div class="amazon-deals-wrapper"
            data-orderby="<?php echo esc_attr($atts['orderby']); ?>"
            data-order="<?php echo esc_attr($atts['order']); ?>">
            <div class="amazon-results-header">
                <div class="amazonResultadosInfo">
                    <h2><?php echo esc_html(Labels::get('daily_deals')); ?></h2>
                    <span class="amazon-count-badge"><?php echo $visibleCount; ?> <?php echo esc_html(Labels::get('results')); ?></span>
                </div>
                <?php if ($showSort): ?>
                    <div class="amazonOrdenamientoRapido">
                        <label for="amazon-deals-sort"><?php echo esc_html(Labels::get('sort_by')); ?>:</label>
                        <select id="amazon-deals-sort" class="amazonSelectorOrden" data-deals="1">
                            <option value="discount-DESC" <?php selected($currentSort, 'discount-DESC'); ?>><?php echo esc_html(Labels::get('best_discount')); ?></option>
                            <option value="price-ASC" <?php selected($currentSort, 'price-ASC'); ?>><?php echo esc_html(Labels::get('price_low')); ?></option>
                            <option value="price-DESC" <?php selected($currentSort, 'price-DESC'); ?>><?php echo esc_html(Labels::get('price_high')); ?></option>
                            <option value="rating-DESC" <?php selected($currentSort, 'rating-DESC'); ?>><?php echo esc_html(Labels::get('top_rated')); ?></option>
                            <option value="date-DESC" <?php selected($currentSort, 'date-DESC'); ?>><?php echo esc_html(Labels::get('newest')); ?></option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <div class="amazon-product-grid">
                <?php foreach ($limitedDeals as $item): ?>
                    <?php CardRenderer::renderProduct($item['post']); ?>
                <?php endforeach; ?>
            </div>
        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Construye la query para productos con descuento.
     */
    private function buildDealsQuery(array $atts): \WP_Query
    {
        $args = [
            'post_type' => 'amazon_product',
            'posts_per_page' => -1, // Traer todos para ordenar por descuento
            'meta_query' => [
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
            ]
        ];

        // Filtro por categoria opcional
        if (!empty($atts['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'amazon_category',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                ]
            ];
        }

        return new \WP_Query($args);
    }

    /**
     * Recolecta productos y los ordena segun el criterio especificado.
     * Solo incluye productos donde original_price > price (descuento real).
     */
    private function collectAndSortDeals(\WP_Query $query, array $atts): array
    {
        $dealsWithDiscount = [];

        while ($query->have_posts()) {
            $query->the_post();
            $post = get_post();
            $price = (float) get_post_meta($post->ID, 'price', true);
            $originalPrice = (float) get_post_meta($post->ID, 'original_price', true);

            /* 
             * Solo incluir productos con descuento real.
             * El precio original debe ser mayor que el precio actual.
             */
            if ($originalPrice <= $price || $originalPrice <= 0 || $price <= 0) {
                continue;
            }

            $discount = DiscountCalculator::calculate($originalPrice, $price);

            /* Solo incluir si hay un descuento significativo (al menos 1%) */
            if ($discount < 1) {
                continue;
            }

            $dealsWithDiscount[] = [
                'post' => $post,
                'discount' => $discount,
                'price' => $price,
                'rating' => (float) get_post_meta($post->ID, 'rating', true),
                'date' => $post->post_date,
            ];
        }
        wp_reset_postdata();

        // Ordenar segun atributo
        usort($dealsWithDiscount, function ($a, $b) use ($atts) {
            $field = $atts['orderby'];
            /* 
             * FIX: El orden estaba invertido.
             * DESC debe mostrar mayor primero (orden natural de $b - $a)
             * ASC debe mostrar menor primero (invertimos con -1)
             */
            $isDesc = strtoupper($atts['order']) === 'DESC';

            switch ($field) {
                case 'discount':
                    $diff = $b['discount'] - $a['discount'];
                    return $isDesc ? $diff : -$diff;
                case 'price':
                    $diff = $a['price'] - $b['price'];
                    return $isDesc ? -$diff : $diff;
                case 'rating':
                    $diff = $b['rating'] - $a['rating'];
                    return $isDesc ? $diff : -$diff;
                default:
                    $diff = strcmp($b['date'], $a['date']);
                    return $isDesc ? $diff : -$diff;
            }
        });

        return $dealsWithDiscount;
    }
}
