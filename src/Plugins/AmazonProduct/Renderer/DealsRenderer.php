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
        ], $atts);

        $query = $this->buildDealsQuery($atts);

        if (!$query->have_posts()) {
            return '<p class="amazon-no-deals">' . esc_html(Labels::get('no_deals')) . '</p>';
        }

        $deals = $this->collectAndSortDeals($query, $atts);
        $limitedDeals = array_slice($deals, 0, (int) $atts['limit']);
        $totalDeals = count($deals);

        ob_start();
?>
        <div class="amazon-deals-wrapper">
            <div class="amazon-results-header">
                <h2><?php echo esc_html(Labels::get('daily_deals')); ?></h2>
                <span class="amazon-count-badge"><?php echo $totalDeals; ?> <?php echo esc_html(Labels::get('results')); ?></span>
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
     */
    private function collectAndSortDeals(\WP_Query $query, array $atts): array
    {
        $dealsWithDiscount = [];

        while ($query->have_posts()) {
            $query->the_post();
            $post = get_post();
            $price = (float) get_post_meta($post->ID, 'price', true);
            $originalPrice = (float) get_post_meta($post->ID, 'original_price', true);
            $discount = DiscountCalculator::calculate($originalPrice, $price);

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
            $order = strtoupper($atts['order']) === 'ASC' ? 1 : -1;

            switch ($field) {
                case 'discount':
                    return ($b['discount'] - $a['discount']) * $order;
                case 'price':
                    return ($a['price'] - $b['price']) * $order;
                case 'rating':
                    return ($b['rating'] - $a['rating']) * $order;
                default:
                    return strcmp($b['date'], $a['date']) * $order;
            }
        });

        return $dealsWithDiscount;
    }
}
