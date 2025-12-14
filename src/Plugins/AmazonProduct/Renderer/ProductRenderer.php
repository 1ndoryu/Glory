<?php

namespace Glory\Plugins\AmazonProduct\Renderer;

/**
 * Product Renderer - Coordinador principal del frontend.
 * 
 * Responsabilidad: Registrar shortcodes y delegar a clases especializadas.
 * 
 * Clases delegadas:
 * - AssetLoader: Carga de CSS/JS
 * - FilterRenderer: Panel de filtros UI
 * - GridRenderer: Grid de productos y paginacion
 * - DealsRenderer: Shortcode [amazon_deals]
 * - QueryBuilder: Construccion de WP_Query
 * 
 * Refactorizado siguiendo SOLID (Diciembre 2024):
 * - De 709 lineas a ~130 lineas
 * - Cada clase tiene responsabilidad unica
 */
class ProductRenderer
{
    private AssetLoader $assetLoader;
    private FilterRenderer $filterRenderer;
    private GridRenderer $gridRenderer;
    private DealsRenderer $dealsRenderer;
    private QueryBuilder $queryBuilder;

    public function __construct()
    {
        $this->assetLoader = new AssetLoader();
        $this->filterRenderer = new FilterRenderer();
        $this->gridRenderer = new GridRenderer();
        $this->dealsRenderer = new DealsRenderer();
        $this->queryBuilder = new QueryBuilder();
    }

    /**
     * Registra shortcodes y hooks del frontend.
     */
    public function init(): void
    {
        add_shortcode('amazon_products', [$this, 'renderShortcode']);
        add_shortcode('amazon_deals', [$this, 'renderDealsShortcode']);
        add_action('wp_enqueue_scripts', [$this->assetLoader, 'enqueue']);
    }

    /**
     * Main shortcode: [amazon_products]
     * 
     * Atributos soportados:
     * - limit: Numero de productos (default: 12)
     * - min_price / max_price: Filtro de precio
     * - category: Slug de categoria
     * - only_prime: "1" para solo productos Prime
     * - only_deals: "1" para solo productos con descuento
     * - orderby: "date", "price", "rating", "discount", "random"
     * - order: "ASC" o "DESC"
     * - hide_filters: "1" para ocultar panel de filtros
     * - ids: IDs de WordPress separados por coma (ej: "123,456,789")
     * - search: Palabra clave para filtrar productos guardados
     * - pagination: "0" para desactivar paginacion
     * - min_rating: Rating minimo (1-5)
     * - exclude: Palabras a excluir separadas por coma (ej: "paletero,bolsa,funda")
     */
    public function renderShortcode($atts): string
    {
        $atts = shortcode_atts([
            'limit' => 12,
            'min_price' => '',
            'max_price' => '',
            'category' => '',
            'only_prime' => '',
            'only_deals' => '',
            'orderby' => 'date',
            'order' => 'DESC',
            'hide_filters' => '1',
            'ids' => '',
            'search' => '',
            'pagination' => '1',
            'min_rating' => '',
            'exclude' => '',
        ], $atts);

        $hideFilters = ($atts['hide_filters'] === '1');
        $showPagination = ($atts['pagination'] !== '0');

        // Obtener el total de productos antes de renderizar para mostrar contador correcto
        // BUG-03 fix: Usamos found_posts en lugar de contar cards visibles
        $totalCount = $this->getTotalProductCount($atts);

        ob_start();
?>
        <div class="amazon-product-wrapper"
            data-limit="<?php echo esc_attr($atts['limit']); ?>"
            data-min-price="<?php echo esc_attr($atts['min_price']); ?>"
            data-max-price="<?php echo esc_attr($atts['max_price']); ?>"
            data-category="<?php echo esc_attr($atts['category']); ?>"
            data-only-prime="<?php echo esc_attr($atts['only_prime']); ?>"
            data-only-deals="<?php echo esc_attr($atts['only_deals']); ?>"
            data-orderby="<?php echo esc_attr($atts['orderby']); ?>"
            data-order="<?php echo esc_attr($atts['order']); ?>"
            data-ids="<?php echo esc_attr($atts['ids']); ?>"
            data-search="<?php echo esc_attr($atts['search']); ?>"
            data-pagination="<?php echo esc_attr($atts['pagination']); ?>"
            data-min-rating="<?php echo esc_attr($atts['min_rating']); ?>"
            data-exclude="<?php echo esc_attr($atts['exclude']); ?>"
            data-total-count="<?php echo esc_attr($totalCount); ?>">

            <?php if (!$hideFilters): ?>
                <?php $this->filterRenderer->renderHeaderControls($atts); ?>
                <?php $this->filterRenderer->renderPanel($atts); ?>
            <?php endif; ?>

            <?php $this->filterRenderer->renderResultsHeader($totalCount); ?>

            <div class="amazon-product-grid-container">
                <?php $this->gridRenderer->render(array_merge($atts, [
                    'paged' => 1,
                    'show_pagination' => $showPagination
                ])); ?>
            </div>

            <div class="amazon-loader" style="display: none;">
                <div class="spinner"></div>
            </div>

        </div>
<?php
        return ob_get_clean();
    }

    /**
     * Obtiene el total de productos que coinciden con los filtros.
     * Se usa para mostrar el contador correcto en la carga inicial.
     * 
     * Corregido: 
     * - Aplica el filtro de exclusion de palabras para obtener el conteo real.
     * - Cuando pagination=0, muestra cuantos productos son visibles (respetando limit).
     * 
     * @param array $atts Atributos del shortcode
     * @return int Total de productos encontrados o visibles
     */
    private function getTotalProductCount(array $atts): int
    {
        $params = array_merge($atts, ['paged' => 1]);
        $showPagination = ($atts['pagination'] ?? '1') !== '0';
        $limit = (int) ($atts['limit'] ?? 12);

        // Obtener palabras de exclusion
        $excludeWords = $this->queryBuilder->getExcludeWords($params);

        // Si hay palabras de exclusion, necesitamos traer todos los posts y filtrar
        if (!empty($excludeWords)) {
            $countParams = array_merge($params, ['limit' => -1]);
            $query = $this->queryBuilder->build($countParams);

            $posts = [];
            while ($query->have_posts()) {
                $query->the_post();
                $posts[] = get_post();
            }
            wp_reset_postdata();

            // Aplicar filtro de exclusion
            $filteredPosts = QueryBuilder::filterExcludedPosts($posts, $excludeWords);
            $totalFiltered = count($filteredPosts);

            // Si no hay paginacion, mostrar solo los visibles (limitados)
            if (!$showPagination) {
                return min($totalFiltered, $limit);
            }

            return $totalFiltered;
        }

        // Sin exclusiones, usar el conteo nativo de WordPress
        $query = $this->queryBuilder->build($params);
        $foundPosts = $query->found_posts;
        wp_reset_postdata();

        // Si no hay paginacion, mostrar solo los visibles (limitados)
        if (!$showPagination) {
            return min($foundPosts, $limit);
        }

        return $foundPosts;
    }

    /**
     * Deals shortcode: [amazon_deals]
     * Delega al DealsRenderer.
     */
    public function renderDealsShortcode($atts): string
    {
        return $this->dealsRenderer->render($atts ?? []);
    }

    /**
     * Handle AJAX filter requests.
     * Llamado desde AmazonProductPlugin via AJAX hooks.
     */
    public function handleAjaxRequest(): void
    {
        check_ajax_referer('amazon_product_nonce', 'nonce');

        $params = [
            'limit' => intval($_POST['limit'] ?? 12),
            'paged' => intval($_POST['paged'] ?? 1),
            'search' => sanitize_text_field($_POST['search'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'min_price' => sanitize_text_field($_POST['min_price'] ?? ''),
            'max_price' => sanitize_text_field($_POST['max_price'] ?? ''),
            'min_rating' => sanitize_text_field($_POST['min_rating'] ?? ''),
            'only_prime' => sanitize_text_field($_POST['only_prime'] ?? ''),
            'only_deals' => sanitize_text_field($_POST['only_deals'] ?? ''),
            'orderby' => sanitize_text_field($_POST['orderby'] ?? 'date'),
            'order' => sanitize_text_field($_POST['order'] ?? 'DESC'),
            'exclude' => sanitize_text_field($_POST['exclude'] ?? ''),
            'show_pagination' => true,
        ];

        ob_start();
        $count = $this->gridRenderer->render($params);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html, 'count' => $count]);
    }
}
