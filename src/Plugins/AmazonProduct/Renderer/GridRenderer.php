<?php

namespace Glory\Plugins\AmazonProduct\Renderer;

use Glory\Plugins\AmazonProduct\i18n\Labels;
use Glory\Plugins\AmazonProduct\Service\DiscountCalculator;

/**
 * Grid Renderer - Renderiza el grid de productos y paginacion.
 * 
 * Responsabilidad unica: Presentacion visual del grid de productos.
 * Maneja ordenamiento por descuento (requiere procesamiento PHP).
 */
class GridRenderer
{
    private QueryBuilder $queryBuilder;

    public function __construct()
    {
        $this->queryBuilder = new QueryBuilder();
    }

    /**
     * Renderiza el grid de productos.
     * 
     * @param array $params Parametros de filtrado y paginacion
     * @return int Total de productos encontrados (found_posts)
     */
    public function render(array $params): int
    {
        $query = $this->queryBuilder->build($params);
        $totalPosts = $query->found_posts;
        $showPagination = $params['show_pagination'] ?? true;

        // Ordenamiento por descuento requiere procesamiento PHP
        if ($this->queryBuilder->isDiscountSorting($params) && $query->have_posts()) {
            return $this->renderDiscountSortedGrid($query, $params);
        }

        if (!$query->have_posts()) {
            $this->renderEmptyState();
            return 0;
        }

        echo '<div class="amazon-product-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            CardRenderer::renderProduct(get_post());
        }
        echo '</div>';

        if ($showPagination) {
            $this->renderPagination($query->max_num_pages, $params['paged'] ?? 1);
        }

        wp_reset_postdata();
        return $totalPosts;
    }

    /**
     * Renderiza grid ordenado por descuento.
     * Requiere traer todos los productos y ordenar en PHP porque
     * el descuento es un valor calculado (no esta en meta directamente).
     */
    private function renderDiscountSortedGrid(\WP_Query $query, array $params): int
    {
        $postsWithDiscount = [];
        $showPagination = $params['show_pagination'] ?? true;

        // Recolectar productos con su descuento calculado
        while ($query->have_posts()) {
            $query->the_post();
            $post = get_post();
            $price = (float) get_post_meta($post->ID, 'price', true);
            $originalPrice = (float) get_post_meta($post->ID, 'original_price', true);
            $discount = DiscountCalculator::calculate($originalPrice, $price);

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
        $limit = (int) ($params['limit'] ?? 12);
        $paged = (int) ($params['paged'] ?? 1);
        $offset = ($paged - 1) * $limit;
        $pagedPosts = array_slice($postsWithDiscount, $offset, $limit);
        $totalPosts = count($postsWithDiscount);
        $totalPages = ceil($totalPosts / $limit);

        if (empty($pagedPosts)) {
            $this->renderEmptyState();
            return 0;
        }

        echo '<div class="amazon-product-grid">';
        foreach ($pagedPosts as $item) {
            CardRenderer::renderProduct($item['post']);
        }
        echo '</div>';

        if ($showPagination) {
            $this->renderPagination($totalPages, $paged);
        }

        wp_reset_postdata();
        return $totalPosts;
    }

    /**
     * Renderiza el estado vacio cuando no hay resultados.
     */
    private function renderEmptyState(): void
    {
?>
        <div class="amazon-empty-state">
            <div class="amazon-empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
            </div>
            <h3><?php echo esc_html(Labels::get('no_results')); ?></h3>
            <button id="amazon-clear-search"><?php echo esc_html(Labels::get('clear_search')); ?></button>
        </div>
    <?php
    }

    /**
     * Renderiza los enlaces de paginacion.
     */
    private function renderPagination(int $totalPages, int $currentPage): void
    {
        if ($totalPages <= 1) {
            return;
        }
    ?>
        <div class="amazon-pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php $class = ($i == $currentPage) ? 'page-numbers current noAjax' : 'page-numbers noAjax'; ?>
                <a href="#" class="<?php echo $class; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
<?php
    }
}
