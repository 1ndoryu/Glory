<?php

namespace Glory\Plugins\AmazonProduct\Service;

/**
 * Handles product import logic for Amazon products.
 * Single Responsibility: Import and update products in WordPress.
 */
class ProductImporter
{
    private const POST_TYPE = 'amazon_product';
    private const TAXONOMY = 'amazon_category';

    /**
     * Import or update a product from API data.
     * 
     * @param array $data Product data from Amazon API
     * @return int|false Post ID on success, false on failure
     */
    public static function importProduct(array $data): int|false
    {
        $asin = $data['asin'] ?? '';
        if (empty($asin)) {
            return false;
        }

        $region = get_option('amazon_api_region', 'us');
        $domain = AmazonApiService::getDomain($region);

        $existingId = self::findByAsin($asin);

        $postData = [
            'post_title'   => $data['asin_name'] ?? 'Amazon Product ' . $asin,
            'post_content' => $data['asin_name'] ?? '',
            'post_status'  => 'publish',
            'post_type'    => self::POST_TYPE,
            'meta_input'   => [
                'asin'           => $asin,
                'price'          => $data['asin_price'] ?? 0,
                'original_price' => $data['asin_original_price'] ?? $data['asin_list_price'] ?? '',
                'rating'         => $data['rating'] ?? $data['total_start'] ?? 0,
                'reviews'        => $data['reviews'] ?? $data['total_review'] ?? 0,
                'currency'       => $data['asin_currency'] ?? 'EUR',
                'prime'          => !empty($data['is_prime']) ? '1' : '0',
                'image_url'      => $data['asin_images'][0] ?? '',
                'product_url'    => $data['product_url'] ?? 'https://www.' . $domain . '/dp/' . $asin,
            ]
        ];

        if ($existingId) {
            $postData['ID'] = $existingId;
            $postId = wp_update_post($postData);
        } else {
            $postId = wp_insert_post($postData);
        }

        if ($postId && !is_wp_error($postId)) {
            // Handle external thumbnail
            if (!empty($data['asin_images'][0])) {
                update_post_meta($postId, '_thumbnail_url_external', $data['asin_images'][0]);
            }

            // Sync categories
            if (!empty($data['category_path'])) {
                self::syncCategories($postId, $data['category_path']);
            }
        }

        return is_wp_error($postId) ? false : $postId;
    }

    /**
     * Import a deal with original price and discount.
     * 
     * Mapeo de campos de deal.php:
     * - asin -> meta: asin
     * - deal_title -> post_title
     * - deal_description -> post_content
     * - deal_min_price -> meta: price
     * - deal_min_list_price -> meta: original_price
     * - deal_min_percent_off -> meta: discount_percent (nuevo)
     * - deal_currency -> meta: currency (nuevo)
     * - deal_ends_at -> meta: deal_ends_at (nuevo)
     * - asin_image -> meta: image_url
     * - asin_rating_star -> meta: rating
     * - asin_total_review -> meta: reviews
     * 
     * @param array $deal Deal data from Amazon API
     * @return int|false Post ID on success, false on failure
     */
    public static function importDeal(array $deal): int|false
    {
        $asin = $deal['asin'] ?? '';
        if (empty($asin)) {
            return false;
        }

        $region = get_option('amazon_api_region', 'us');
        $domain = AmazonApiService::getDomain($region);

        $existingId = self::findByAsin($asin);

        $postData = [
            'post_title'   => $deal['deal_title'] ?? 'Amazon Deal ' . $asin,
            'post_content' => $deal['deal_description'] ?? '',
            'post_status'  => 'publish',
            'post_type'    => self::POST_TYPE,
            'meta_input'   => [
                'asin'             => $asin,
                'price'            => $deal['deal_min_price'] ?? 0,
                'original_price'   => $deal['deal_min_list_price'] ?? 0,
                'discount_percent' => $deal['deal_min_percent_off'] ?? 0,
                'currency'         => $deal['deal_currency'] ?? 'USD',
                'deal_ends_at'     => $deal['deal_ends_at'] ?? '',
                'rating'           => $deal['asin_rating_star'] ?? 0,
                'reviews'          => $deal['asin_total_review'] ?? 0,
                'prime'            => '1',
                'image_url'        => $deal['asin_image'] ?? '',
                'product_url'      => 'https://www.' . $domain . '/dp/' . $asin,
            ]
        ];

        if ($existingId) {
            $postData['ID'] = $existingId;
            $postId = wp_update_post($postData);
        } else {
            $postId = wp_insert_post($postData);

            // Assign "Ofertas" category for new deals
            if ($postId && !is_wp_error($postId)) {
                self::assignCategory($postId, 'Ofertas');
            }
        }

        return is_wp_error($postId) ? false : $postId;
    }

    /**
     * Find existing product by ASIN.
     */
    public static function findByAsin(string $asin): ?int
    {
        $query = new \WP_Query([
            'post_type'      => self::POST_TYPE,
            'meta_key'       => 'asin',
            'meta_value'     => $asin,
            'posts_per_page' => 1,
            'fields'         => 'ids'
        ]);

        return $query->have_posts() ? $query->posts[0] : null;
    }

    /**
     * Sync hierarchical categories from path string.
     * Example path: "Electronics > Computers > Laptops"
     */
    public static function syncCategories(int $postId, string $path): void
    {
        $parts = explode(' > ', $path);
        $parentId = 0;
        $termIds = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            $term = term_exists($part, self::TAXONOMY, $parentId);

            if (!$term) {
                $term = wp_insert_term($part, self::TAXONOMY, ['parent' => $parentId]);
            }

            if (!is_wp_error($term) && isset($term['term_id'])) {
                $parentId = $term['term_id'];
                $termIds[] = $parentId;
            }
        }

        if (!empty($termIds)) {
            wp_set_object_terms($postId, $termIds, self::TAXONOMY);
        }
    }

    /**
     * Assign a single category to a product.
     */
    public static function assignCategory(int $postId, string $categoryName): void
    {
        $term = term_exists($categoryName, self::TAXONOMY);
        if (!$term) {
            $term = wp_insert_term($categoryName, self::TAXONOMY);
        }
        if (!is_wp_error($term)) {
            wp_set_object_terms($postId, (int) $term['term_id'], self::TAXONOMY);
        }
    }
}
