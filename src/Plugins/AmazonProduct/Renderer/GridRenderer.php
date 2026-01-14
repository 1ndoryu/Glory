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
        $showPagination = $params['show_pagination'] ?? true;
        $excludeWords = $this->queryBuilder->getExcludeWords($params);
        $searchTerms = $this->queryBuilder->getSearchTerms($params);
        $limit = (int) ($params['limit'] ?? 12);
        $paged = (int) ($params['paged'] ?? 1);

        /*
         * Si hay palabras de exclusion O terminos de busqueda multiples,
         * necesitamos traer TODOS los productos primero, aplicar filtros
         * en PHP, y luego paginar manualmente.
         */
        if (!empty($excludeWords) || !empty($searchTerms)) {
            return $this->renderWithFilters($params, $searchTerms, $excludeWords, $limit, $paged, $showPagination);
        }

        // Sin exclusiones: usar query normal con paginacion nativa
        $query = $this->queryBuilder->build($params);

        // Ordenamiento por descuento requiere procesamiento PHP
        if ($this->queryBuilder->isDiscountSorting($params) && $query->have_posts()) {
            return $this->renderDiscountSortedGrid($query, $params);
        }

        if (!$query->have_posts()) {
            $this->renderEmptyState();
            return 0;
        }

        // Renderizar productos de la pagina actual
        echo '<div class="amazon-product-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            CardRenderer::renderProduct(get_post());
        }
        echo '</div>';

        if ($showPagination) {
            $this->renderPagination($query->max_num_pages, $paged);
        }

        $totalPosts = $query->found_posts;
        wp_reset_postdata();

        /*
         * Si no hay paginación, devolver cuántos productos se mostraron realmente
         * (el menor entre found_posts y limit).
         */
        return $showPagination ? $totalPosts : min($totalPosts, $limit);
    }

    /**
     * Renderiza el grid cuando hay filtros PHP necesarios.
     * Aplica filtro de busqueda multiples (OR) y/o exclusion de palabras.
     * Trae todos los productos, aplica filtros, y pagina manualmente.
     */
    private function renderWithFilters(
        array $params,
        array $searchTerms,
        array $excludeWords,
        int $limit,
        int $paged,
        bool $showPagination
    ): int {
        // Traer TODOS los productos (sin paginacion)
        $allParams = array_merge($params, ['limit' => -1, 'paged' => 1]);
        $query = $this->queryBuilder->build($allParams);

        if (!$query->have_posts()) {
            $this->renderEmptyState();
            return 0;
        }

        // Recolectar todos los posts
        $allPosts = [];
        while ($query->have_posts()) {
            $query->the_post();
            $allPosts[] = get_post();
        }

        $filteredPosts = $allPosts;

        /* 
         * Aplicar filtro de busqueda OR (si hay terminos multiples).
         * Incluye solo productos que contengan AL MENOS UNO de los terminos.
         */
        if (!empty($searchTerms)) {
            $filteredPosts = QueryBuilder::filterBySearchTerms($filteredPosts, $searchTerms);
        }

        /* 
         * Aplicar filtro de exclusion.
         * Excluye productos que contengan CUALQUIERA de las palabras de exclusion.
         */
        if (!empty($excludeWords)) {
            $filteredPosts = QueryBuilder::filterExcludedPosts($filteredPosts, $excludeWords);
        }

        $totalPosts = count($filteredPosts);

        if (empty($filteredPosts)) {
            $this->renderEmptyState();
            wp_reset_postdata();
            return 0;
        }

        /* 
         * Aplicar ordenamiento por descuento si corresponde.
         * Calcula el descuento para cada producto y ordena de mayor a menor.
         */
        if ($this->queryBuilder->isDiscountSorting($params)) {
            $filteredPosts = $this->sortByDiscount($filteredPosts, $params['order'] ?? 'DESC');
        }

        // Aplicar paginacion manual
        $limit = max(1, $limit); // Proteccion contra division por cero
        $offset = ($paged - 1) * $limit;
        $pagedPosts = array_slice($filteredPosts, $offset, $limit);
        $totalPages = (int) ceil($totalPosts / $limit);

        echo '<div class="amazon-product-grid">';
        foreach ($pagedPosts as $post) {
            CardRenderer::renderProduct($post);
        }
        echo '</div>';

        if ($showPagination) {
            $this->renderPagination($totalPages, $paged);
        }

        wp_reset_postdata();

        /* 
         * Si no hay paginación, el contador debe mostrar cuántos productos son visibles,
         * no el total de productos filtrados. Esto corrige el bug donde el contador
         * mostraba 467 productos cuando solo se mostraban 4.
         */
        return $showPagination ? $totalPosts : count($pagedPosts);
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
        $limit = max(1, (int) ($params['limit'] ?? 12));
        $paged = (int) ($params['paged'] ?? 1);
        $offset = ($paged - 1) * $limit;
        $pagedPosts = array_slice($postsWithDiscount, $offset, $limit);
        /* 
         * Si no hay paginación, el total es lo que mostramos (respetando limit).
         * Esto corrige el bug donde el contador mostraba TODOS los productos  
         * cuando se usaba orderby="discount" en shortcodes con pagination="0".
         */
        $totalPosts = $showPagination ? count($postsWithDiscount) : count($pagedPosts);
        $totalPages = (int) ceil(count($postsWithDiscount) / $limit);

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
     * Ordena un array de posts por descuento.
     * 
     * @param array $posts Array de WP_Post objects
     * @param string $order 'DESC' para mayor descuento primero, 'ASC' para menor
     * @return array Posts ordenados
     */
    private function sortByDiscount(array $posts, string $order = 'DESC'): array
    {
        $postsWithDiscount = [];

        foreach ($posts as $post) {
            $price = (float) get_post_meta($post->ID, 'price', true);
            $originalPrice = (float) get_post_meta($post->ID, 'original_price', true);
            $discount = DiscountCalculator::calculate($originalPrice, $price);

            $postsWithDiscount[] = [
                'post' => $post,
                'discount' => $discount
            ];
        }

        usort($postsWithDiscount, function ($a, $b) use ($order) {
            $diff = $b['discount'] - $a['discount'];
            return $order === 'ASC' ? -$diff : $diff;
        });

        return array_column($postsWithDiscount, 'post');
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
     * Renderiza los botones de paginacion.
     * 
     * Nota: Usamos <button> en lugar de <a href="#"> para evitar conflictos
     * con scripts de navegacion AJAX que interceptan enlaces.
     */
    private function renderPagination(int $totalPages, int $currentPage): void
    {
        if ($totalPages <= 1) {
            return;
        }
    ?>
        <div class="amazon-pagination" role="navigation" aria-label="Paginacion de productos">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php
                $isCurrent = ($i == $currentPage);
                $class = $isCurrent ? 'page-numbers current' : 'page-numbers';
                ?>
                <button
                    type="button"
                    class="<?php echo $class; ?>"
                    data-page="<?php echo $i; ?>"
                    <?php echo $isCurrent ? 'aria-current="page" disabled' : ''; ?>><?php echo $i; ?></button>
            <?php endfor; ?>
        </div>
<?php
    }
}
