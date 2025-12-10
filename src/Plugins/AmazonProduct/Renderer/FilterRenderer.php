<?php

namespace Glory\Plugins\AmazonProduct\Renderer;

use Glory\Plugins\AmazonProduct\i18n\Labels;

/**
 * Filter Renderer - Renderiza el panel de filtros del frontend.
 * 
 * Responsabilidad unica: Generacion de HTML para controles de filtrado.
 * Incluye: busqueda, categorias, precio, rating, opciones Prime/Deals, ordenamiento.
 */
class FilterRenderer
{
    /**
     * Renderiza los controles del header (busqueda + toggle filtros).
     */
    public function renderHeaderControls(array $atts): void
    {
?>
        <div class="amazon-header-controls">
            <div class="amazon-search-container">
                <input type="text" id="amazon-search" placeholder="<?php echo esc_attr(Labels::get('search_placeholder')); ?>">
                <?php echo CardRenderer::getSearchIcon(); ?>
            </div>

            <button id="amazon-toggle-filters" class="amazon-btn-filters">
                <?php echo CardRenderer::getFilterIcon(); ?>
                <span><?php echo esc_html(Labels::get('filters')); ?></span>
                <?php echo CardRenderer::getChevronIcon(); ?>
            </button>
        </div>
    <?php
    }

    /**
     * Renderiza el panel completo de filtros.
     */
    public function renderPanel(array $atts): void
    {
    ?>
        <div id="amazon-filter-panel" class="amazon-filter-panel">
            <div class="amazon-filter-grid">
                <?php $this->renderCategoryFilter($atts); ?>
                <?php $this->renderPriceFilter(); ?>
                <?php $this->renderRatingFilter(); ?>
                <?php $this->renderOptionsFilter($atts); ?>
            </div>

            <div class="amazon-filter-footer">
                <button id="amazon-reset-filters"><?php echo esc_html(Labels::get('reset_filters')); ?></button>
            </div>
        </div>
    <?php
    }

    /**
     * Renderiza el filtro de categorias.
     */
    private function renderCategoryFilter(array $atts): void
    {
    ?>
        <div class="amazon-filter-col">
            <h3><?php echo esc_html(Labels::get('categories')); ?></h3>
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
                    echo '<p class="amazon-no-cats">' . esc_html(Labels::get('no_categories')) . '</p>';
                }
                ?>
            </div>
        </div>
    <?php
    }

    /**
     * Renderiza el filtro de precio con slider.
     */
    private function renderPriceFilter(): void
    {
    ?>
        <div class="amazon-filter-col">
            <h3><?php echo esc_html(Labels::get('max_price')); ?>: <span id="price-display">2000</span>&euro;</h3>
            <div class="amazon-range-wrapper">
                <input type="range" id="amazon-max-price-range" min="0" max="2000" step="50" value="2000">
                <div class="amazon-range-labels">
                    <span>0&euro;</span>
                    <span>1000&euro;</span>
                    <span>2000&euro;+</span>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Renderiza el filtro de rating con estrellas.
     */
    private function renderRatingFilter(): void
    {
    ?>
        <div class="amazon-filter-col">
            <h3><?php echo esc_html(Labels::get('top_rated')); ?></h3>
            <div class="amazon-rating-list">
                <?php foreach ([4, 3, 2, 1] as $star): ?>
                    <button class="amazon-rating-btn" data-rating="<?php echo $star; ?>">
                        <div class="amazon-radio-circle"></div>
                        <div class="amazon-stars">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <?php echo CardRenderer::getStarIcon($i < $star); ?>
                            <?php endfor; ?>
                        </div>
                        <span><?php echo esc_html(Labels::get('and_more')); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php
    }

    /**
     * Renderiza las opciones adicionales: Prime, Deals, Ordenamiento.
     */
    private function renderOptionsFilter(array $atts): void
    {
    ?>
        <div class="amazon-filter-col">
            <h3><?php echo esc_html(Labels::get('options')); ?></h3>
            <label class="amazon-checkbox-label">
                <input type="checkbox" id="amazon-prime" value="1" <?php checked('1', $atts['only_prime']); ?>>
                <span class="checkbox-custom"></span>
                <?php echo esc_html(Labels::get('prime_only')); ?>
            </label>
            <label class="amazon-checkbox-label">
                <input type="checkbox" id="amazon-deals" value="1" <?php checked('1', $atts['only_deals']); ?>>
                <span class="checkbox-custom"></span>
                <?php echo esc_html(Labels::get('deals_only')); ?>
            </label>

            <div class="amazon-sort-wrapper">
                <select id="amazon-sort">
                    <?php $currentSort = $atts['orderby'] . '-' . $atts['order']; ?>
                    <option value="date-DESC" <?php selected($currentSort, 'date-DESC'); ?>><?php echo esc_html(Labels::get('newest')); ?></option>
                    <option value="discount-DESC" <?php selected($currentSort, 'discount-DESC'); ?>><?php echo esc_html(Labels::get('best_discount')); ?></option>
                    <option value="price-ASC" <?php selected($currentSort, 'price-ASC'); ?>><?php echo esc_html(Labels::get('price_low')); ?></option>
                    <option value="price-DESC" <?php selected($currentSort, 'price-DESC'); ?>><?php echo esc_html(Labels::get('price_high')); ?></option>
                    <option value="rating-DESC" <?php selected($currentSort, 'rating-DESC'); ?>><?php echo esc_html(Labels::get('top_rated')); ?></option>
                </select>
            </div>
        </div>
    <?php
    }

    /**
     * Renderiza el header de resultados con contador.
     * 
     * @param int $totalCount Total de productos encontrados (found_posts)
     */
    public function renderResultsHeader(int $totalCount = 0): void
    {
    ?>
        <div class="amazon-results-header">
            <h2><?php echo esc_html(Labels::get('products')); ?></h2>
            <span class="amazon-count-badge"><span id="amazon-total-count"><?php echo esc_html($totalCount); ?></span> <?php echo esc_html(Labels::get('results')); ?></span>
        </div>
<?php
    }
}
