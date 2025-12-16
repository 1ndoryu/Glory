<?php

namespace Glory\Plugins\AmazonProduct\Controller;

use Glory\Plugins\AmazonProduct\Service\HtmlParserService;
use Glory\Plugins\AmazonProduct\Service\ProductImporter;
use Glory\Plugins\AmazonProduct\Service\ImageDownloaderService;

/**
 * Controlador AJAX para importacion manual de productos desde HTML.
 * 
 * Responsabilidad unica: Manejar las peticiones AJAX de parseo e importacion
 * de archivos HTML de Amazon.
 */
class ManualImportAjaxController
{
    private HtmlParserService $parserService;
    private ImageDownloaderService $imageService;

    public function __construct()
    {
        $this->parserService = new HtmlParserService();
        $this->imageService = new ImageDownloaderService();

        add_action('wp_ajax_amazon_parse_html', [$this, 'handleParseHtml']);
        add_action('wp_ajax_amazon_import_product', [$this, 'handleImportProduct']);
    }

    /**
     * Parsea HTML de Amazon y extrae datos del producto.
     */
    public function handleParseHtml(): void
    {
        check_ajax_referer('amazon_manual_import_ajax', 'nonce');

        $html = wp_unslash($_POST['html'] ?? '');

        if (empty($html)) {
            wp_send_json_error('HTML vacio');
        }

        $data = $this->parserService->parseHtml($html);

        if (empty($data['asin'])) {
            wp_send_json_error('No se pudo extraer el ASIN');
        }

        $data['exists'] = ProductImporter::findByAsin($data['asin']) !== null;

        wp_send_json_success($data);
    }

    /**
     * Importa un producto desde datos parseados.
     */
    public function handleImportProduct(): void
    {
        check_ajax_referer('amazon_manual_import_ajax', 'nonce');

        $productJson = $_POST['product'] ?? '';
        $downloadImage = !empty($_POST['download_image']);

        $product = json_decode(stripslashes($productJson), true);

        if (empty($product) || empty($product['asin'])) {
            wp_send_json_error('Datos de producto invalidos');
        }

        $result = $this->saveProduct($product, $downloadImage);

        if ($result['success']) {
            wp_send_json_success($result['post_id']);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * Guarda el producto en la base de datos.
     */
    private function saveProduct(array $data, bool $downloadImage = true): array
    {
        $asin = sanitize_text_field($data['asin']);
        $title = sanitize_text_field($data['title'] ?? '');
        $price = floatval($data['price'] ?? 0);
        $originalPrice = floatval($data['original_price'] ?? 0);
        $rating = floatval($data['rating'] ?? 0);
        $reviews = intval($data['reviews'] ?? 0);
        $prime = !empty($data['prime']) ? '1' : '0';
        $category = sanitize_text_field($data['category'] ?? '');
        $currency = sanitize_text_field($data['currency'] ?? 'USD');
        $imageUrl = esc_url_raw($data['image'] ?? '');
        $productUrl = esc_url_raw($data['url'] ?? '');

        $existingId = ProductImporter::findByAsin($asin);

        if ($existingId) {
            return $this->updateExistingProduct(
                $existingId,
                $title,
                $price,
                $originalPrice,
                $rating,
                $reviews,
                $prime,
                $currency,
                $productUrl,
                $imageUrl,
                $category,
                $downloadImage
            );
        }

        return $this->createNewProduct(
            $asin,
            $title,
            $price,
            $originalPrice,
            $rating,
            $reviews,
            $prime,
            $currency,
            $productUrl,
            $imageUrl,
            $category,
            $downloadImage
        );
    }

    /**
     * Actualiza un producto existente.
     */
    private function updateExistingProduct(
        int $existingId,
        string $title,
        float $price,
        float $originalPrice,
        float $rating,
        int $reviews,
        string $prime,
        string $currency,
        string $productUrl,
        string $imageUrl,
        string $category,
        bool $downloadImage
    ): array {
        wp_update_post(['ID' => $existingId, 'post_title' => $title]);

        update_post_meta($existingId, 'price', $price);
        update_post_meta($existingId, 'original_price', $originalPrice);
        update_post_meta($existingId, 'rating', $rating);
        update_post_meta($existingId, 'reviews', $reviews);
        update_post_meta($existingId, 'prime', $prime);
        update_post_meta($existingId, 'currency', $currency);
        update_post_meta($existingId, 'product_url', $productUrl);
        update_post_meta($existingId, 'image_url', $imageUrl);

        if ($downloadImage && !empty($imageUrl) && !$this->imageService->hasLocalImage($existingId)) {
            $this->imageService->downloadAndSetAsThumbnail($imageUrl, $existingId, $title);
        }

        if (!empty($category)) {
            ProductImporter::syncCategories($existingId, $category);
        }

        return ['success' => true, 'post_id' => $existingId, 'updated' => true];
    }

    /**
     * Crea un nuevo producto.
     */
    private function createNewProduct(
        string $asin,
        string $title,
        float $price,
        float $originalPrice,
        float $rating,
        int $reviews,
        string $prime,
        string $currency,
        string $productUrl,
        string $imageUrl,
        string $category,
        bool $downloadImage
    ): array {
        $postId = wp_insert_post([
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'amazon_product',
            'meta_input' => [
                'asin' => $asin,
                'price' => $price,
                'original_price' => $originalPrice,
                'rating' => $rating,
                'reviews' => $reviews,
                'prime' => $prime,
                'currency' => $currency,
                'image_url' => $imageUrl,
                'product_url' => $productUrl,
            ]
        ]);

        if (is_wp_error($postId)) {
            return ['success' => false, 'message' => $postId->get_error_message()];
        }

        if ($downloadImage && !empty($imageUrl)) {
            $this->imageService->downloadAndSetAsThumbnail($imageUrl, $postId, $title);
        }

        if (!empty($category)) {
            ProductImporter::syncCategories($postId, $category);
        }

        return ['success' => true, 'post_id' => $postId, 'updated' => false];
    }
}
