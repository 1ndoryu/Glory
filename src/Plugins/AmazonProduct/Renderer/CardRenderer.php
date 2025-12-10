<?php

namespace Glory\Plugins\AmazonProduct\Renderer;

use Glory\Plugins\AmazonProduct\Service\AmazonApiService;
use Glory\Plugins\AmazonProduct\Service\DiscountCalculator;

/**
 * Renders product cards for the frontend.
 * Single Responsibility: Card HTML generation only.
 */
class CardRenderer
{
    /**
     * Render a product card from a WP_Post object.
     */
    public static function renderProduct(\WP_Post $post): void
    {
        $meta = self::getProductMeta($post->ID);
        $discount = DiscountCalculator::calculate((float) $meta['original_price'], (float) $meta['price']);
        $productUrl = self::buildProductUrl($meta['asin'], $meta['product_url']);

        self::renderCardHtml([
            'title'          => $post->post_title,
            'image'          => $meta['image_url'],
            'price'          => $meta['price'],
            'original_price' => $meta['original_price'],
            'rating'         => $meta['rating'],
            'reviews'        => $meta['reviews'],
            'is_prime'       => $meta['prime'] === '1',
            'discount'       => $discount,
            'product_url'    => $productUrl,
            'category_label' => 'Amazon',
        ]);
    }

    /**
     * Render a deal card from API data array.
     */
    public static function renderDeal(array $deal): void
    {
        $asin = $deal['asin'] ?? '';
        $productUrl = self::buildProductUrl($asin, '');

        self::renderCardHtml([
            'title'          => $deal['deal_title'] ?? '',
            'image'          => $deal['asin_image'] ?? '',
            'price'          => $deal['deal_min_price'] ?? 0,
            'original_price' => $deal['deal_min_list_price'] ?? 0,
            'rating'         => $deal['asin_rating_star'] ?? 0,
            'reviews'        => $deal['asin_total_review'] ?? 0,
            'is_prime'       => true,
            'discount'       => $deal['deal_min_percent_off'] ?? 0,
            'product_url'    => $productUrl,
            'category_label' => 'Oferta Flash',
            'show_currency'  => true,
        ]);
    }

    /**
     * Get all product meta fields.
     */
    private static function getProductMeta(int $postId): array
    {
        return [
            'asin'           => get_post_meta($postId, 'asin', true),
            'price'          => get_post_meta($postId, 'price', true),
            'original_price' => get_post_meta($postId, 'original_price', true),
            'rating'         => get_post_meta($postId, 'rating', true),
            'reviews'        => get_post_meta($postId, 'reviews', true),
            'prime'          => get_post_meta($postId, 'prime', true),
            'image_url'      => get_post_meta($postId, 'image_url', true),
            'product_url'    => get_post_meta($postId, 'product_url', true),
        ];
    }

    /**
     * Build the affiliate product URL.
     */
    private static function buildProductUrl(string $asin, string $existingUrl): string
    {
        if (empty($existingUrl)) {
            $region = get_option('amazon_api_region', 'us');
            $domain = AmazonApiService::getDomain($region);
            $existingUrl = 'https://www.' . $domain . '/dp/' . $asin;
        }

        $affiliateTag = get_option('amazon_affiliate_tag', '');
        if (!empty($affiliateTag)) {
            $separator = (strpos($existingUrl, '?') !== false) ? '&' : '?';
            $existingUrl .= $separator . 'tag=' . esc_attr($affiliateTag);
        }

        return $existingUrl;
    }

    /**
     * Render the card HTML structure.
     * Unified template for both products and deals.
     */
    private static function renderCardHtml(array $data): void
    {
        $hasDiscount = $data['discount'] > 0;
        $showCurrency = $data['show_currency'] ?? false;
        $currencySymbol = $showCurrency ? '&euro;' : '';
?>
        <div class="amazon-product-card group">
            <div class="amazon-card-image-wrapper">
                <?php if ($hasDiscount): ?>
                    <span class="amazon-discount-badge">-<?php echo (int) $data['discount']; ?>%</span>
                <?php elseif ($data['is_prime']): ?>
                    <span class="amazon-prime-badge">PRIME</span>
                <?php endif; ?>
                <img
                    src="<?php echo esc_url($data['image']); ?>"
                    alt="<?php echo esc_attr($data['title']); ?>"
                    class="amazon-product-image"
                    loading="lazy">
                <div class="amazon-card-overlay"></div>
            </div>

            <div class="amazon-card-content">
                <div class="amazon-card-cat"><?php echo esc_html($data['category_label']); ?></div>
                <h3 class="amazon-card-title"><?php echo esc_html($data['title']); ?></h3>

                <?php self::renderStars($data['rating'], $data['reviews'] ?? null); ?>

                <div class="amazon-card-footer">
                    <div class="amazon-price-block">
                        <?php if ($hasDiscount && !empty($data['original_price'])): ?>
                            <span class="price-original"><?php echo esc_html($data['original_price']); ?><?php echo $currencySymbol; ?></span>
                        <?php endif; ?>
                        <span class="price-value"><?php echo esc_html($data['price']); ?><?php echo $currencySymbol; ?></span>
                    </div>
                    <a href="<?php echo esc_url($data['product_url']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="amazon-buy-btn-icon">
                        <?php echo self::getExternalLinkIcon(); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Render star rating with optional review count.
     */
    private static function renderStars(float $rating, ?int $reviews = null): void
    {
    ?>
        <div class="amazon-card-rating">
            <div class="amazon-stars">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                        fill="<?php echo $i < $rating ? 'currentColor' : 'none'; ?>"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="<?php echo $i < $rating ? 'star-filled' : 'star-empty'; ?>">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                    </svg>
                <?php endfor; ?>
            </div>
            <span class="rating-count">(<?php echo esc_html($reviews ?? $rating); ?>)</span>
        </div>
<?php
    }

    /**
     * Get external link SVG icon.
     */
    private static function getExternalLinkIcon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
            <polyline points="15 3 21 3 21 9" />
            <line x1="10" x2="21" y1="14" y2="3" />
        </svg>';
    }

    /**
     * Get search icon SVG.
     */
    public static function getSearchIcon(): string
    {
        return '<svg class="amazon-icon-search" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.3-4.3" />
        </svg>';
    }

    /**
     * Get filter icon SVG.
     * Icono de sliders/ajustes para representar filtros
     */
    public static function getFilterIcon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="4" x2="4" y1="21" y2="14" />
            <line x1="4" x2="4" y1="10" y2="3" />
            <line x1="12" x2="12" y1="21" y2="12" />
            <line x1="12" x2="12" y1="8" y2="3" />
            <line x1="20" x2="20" y1="21" y2="16" />
            <line x1="20" x2="20" y1="12" y2="3" />
            <line x1="1" x2="7" y1="14" y2="14" />
            <line x1="9" x2="15" y1="8" y2="8" />
            <line x1="17" x2="23" y1="16" y2="16" />
        </svg>';
    }

    /**
     * Get chevron down icon SVG.
     */
    public static function getChevronIcon(): string
    {
        return '<svg class="amazon-icon-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6" />
        </svg>';
    }

    /**
     * Get star icon SVG.
     */
    public static function getStarIcon(bool $filled = true): string
    {
        $fill = $filled ? 'currentColor' : 'none';
        $class = $filled ? 'star-filled' : 'star-empty';
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="' . $fill . '" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . $class . '">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
        </svg>';
    }
}
