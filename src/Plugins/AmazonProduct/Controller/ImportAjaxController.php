<?php

namespace Glory\Plugins\AmazonProduct\Controller;

use Glory\Plugins\AmazonProduct\Service\AmazonApiService;
use Glory\Plugins\AmazonProduct\Service\ProductImporter;

/**
 * Controlador AJAX para importacion de productos Amazon.
 * 
 * Responsabilidad unica: Manejar las peticiones AJAX de busqueda e importacion.
 * Separado de ImportTab para mantener SRP.
 */
class ImportAjaxController
{
    public function __construct()
    {
        add_action('wp_ajax_amazon_search_products', [$this, 'handleSearch']);
        add_action('wp_ajax_amazon_import_single', [$this, 'handleDetailedImport']);
        add_action('wp_ajax_amazon_quick_import', [$this, 'handleQuickImport']);
    }

    /**
     * Busqueda de productos via AJAX.
     */
    public function handleSearch(): void
    {
        check_ajax_referer('amazon_search_ajax', 'nonce');

        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $forceRefresh = !empty($_POST['force_refresh']);

        if (empty($keyword)) {
            wp_send_json_error('Palabra clave vacia');
        }

        $service = new AmazonApiService();
        $results = $service->searchProducts($keyword, $page, $forceRefresh);

        $cacheInfoHtml = $this->buildCacheInfoHtml($service, $forceRefresh);

        if (empty($results)) {
            wp_send_json_error('No se encontraron productos');
        }

        ob_start();
        echo $cacheInfoHtml;
        $this->renderResultsTable($results, $page);
        $html = ob_get_clean();

        wp_send_json_success($html);
    }

    /**
     * Importacion Detallada - Hace peticion extra para obtener mas datos.
     */
    public function handleDetailedImport(): void
    {
        try {
            check_ajax_referer('amazon_import_ajax', 'nonce');

            $asin = sanitize_text_field($_POST['asin'] ?? '');
            if (empty($asin)) {
                wp_send_json_error('ASIN vacio');
            }

            $this->log("AjaxImport (Detallada): Start for ASIN $asin");

            $service = new AmazonApiService();
            $existingId = ProductImporter::findByAsin($asin);
            $productData = $service->getProductByAsin($asin);

            if (empty($productData) || !is_array($productData)) {
                wp_send_json_error('Error al obtener datos de Amazon');
            }

            $postId = ProductImporter::importProduct($productData);
            if (!$postId) {
                wp_send_json_error('Error al importar en BD');
            }

            $this->log("AjaxImport (Detallada): Success. Post ID: $postId");

            wp_send_json_success($this->buildImportResponse(
                $postId,
                $existingId,
                $productData['asin_price'] ?? 0,
                'detailed'
            ));
        } catch (\Throwable $e) {
            $this->log("AjaxImport Error: " . $e->getMessage(), 'error');
            wp_send_json_error('Error Fatal: ' . $e->getMessage());
        }
    }

    /**
     * Importacion Rapida - Usa datos de busqueda sin peticion extra.
     */
    public function handleQuickImport(): void
    {
        try {
            check_ajax_referer('amazon_import_ajax', 'nonce');

            $productJson = stripslashes($_POST['product_data'] ?? '');
            $productData = json_decode($productJson, true);

            if (empty($productData) || empty($productData['asin'])) {
                wp_send_json_error('Datos del producto invalidos');
            }

            $asin = sanitize_text_field($productData['asin']);
            $this->log("AjaxQuickImport: Start for ASIN $asin");

            $existingId = ProductImporter::findByAsin($asin);

            /* Normalizar image_url a asin_images para compatibilidad */
            if (!empty($productData['image_url']) && empty($productData['asin_images'])) {
                $productData['asin_images'] = [$productData['image_url']];
            }

            $postId = ProductImporter::importProduct($productData);
            if (!$postId) {
                wp_send_json_error('Error al importar en BD');
            }

            $this->log("AjaxQuickImport: Success. Post ID: $postId");

            wp_send_json_success($this->buildImportResponse(
                $postId,
                $existingId,
                $productData['asin_price'] ?? 0,
                'quick'
            ));
        } catch (\Throwable $e) {
            $this->log("AjaxQuickImport Error: " . $e->getMessage(), 'error');
            wp_send_json_error('Error Fatal: ' . $e->getMessage());
        }
    }

    /**
     * Construye la respuesta JSON de importacion exitosa.
     */
    private function buildImportResponse(int $postId, ?int $existingId, float $fetchedPrice, string $importType): array
    {
        $editLink = get_edit_post_link($postId, 'display');
        if (!$editLink) {
            $editLink = admin_url('post.php?post=' . $postId . '&action=edit');
        }

        $savedPrice = get_post_meta($postId, 'price', true);
        $priceHtml = $this->buildPriceHtml($fetchedPrice, $savedPrice);

        return [
            'id' => $postId,
            'action' => $existingId ? 'updated' : 'imported',
            'edit_link' => $editLink,
            'title' => get_the_title($postId),
            'price_html' => $priceHtml,
            'import_type' => $importType
        ];
    }

    /**
     * Construye el HTML de comparacion de precios.
     */
    private function buildPriceHtml(float $fetchedPrice, $savedPrice): string
    {
        $fetchedFormatted = function_exists('wc_price') ? wc_price($fetchedPrice) : $fetchedPrice . ' €';
        $savedFormatted = function_exists('wc_price') ? wc_price($savedPrice) : $savedPrice . ' €';

        $html = '<div><span style="color: #2271b1; font-weight: bold;">' . $fetchedFormatted . '</span><br><small style="color: #666;">Detectado</small></div>';
        $html .= '<div style="margin-top: 5px; border-top: 1px dotted #ccc; padding-top: 2px;"><span style="color: #46b450; font-weight: bold;">' . $savedFormatted . '</span><br><small style="color: #666;">Guardado</small></div>';

        return $html;
    }

    /**
     * Construye el HTML de informacion de cache.
     */
    private function buildCacheInfoHtml(AmazonApiService $service, bool $forceRefresh): string
    {
        $lastCache = $service->getLastCacheTime();

        if ($lastCache && !$forceRefresh) {
            $timeAgo = human_time_diff($lastCache);
            return '<div class="notice notice-info inline" style="margin-bottom:15px; display:flex; align-items:center; justify-content:space-between;">' .
                '<p>Resultados cacheados hace ' . $timeAgo . '.</p>' .
                '<button type="button" id="amazon-force-refresh" class="button button-small">Forzar actualizacion</button>' .
                '</div>';
        }

        if ($forceRefresh) {
            return '<div class="notice notice-success inline" style="margin-bottom:15px;"><p>Cache limpiada. Resultados actualizados.</p></div>';
        }

        return '';
    }

    /**
     * Renderiza la tabla de resultados.
     */
    private function renderResultsTable(array $results, int $page): void
    {
        $importedAsins = $this->getImportedAsins($results);

        require __DIR__ . '/../Admin/Views/import-results-table.php';
    }

    /**
     * Obtiene ASINs ya importados.
     */
    private function getImportedAsins(array $results): array
    {
        $asins = array_column($results, 'asin');
        if (empty($asins)) {
            return [];
        }

        $query = new \WP_Query([
            'post_type' => 'amazon_product',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'asin',
                    'value' => $asins,
                    'compare' => 'IN'
                ]
            ],
            'fields' => 'ids'
        ]);

        $importedAsins = [];
        foreach ($query->posts as $postId) {
            $asin = get_post_meta($postId, 'asin', true);
            $importedAsins[$asin] = $postId;
        }

        return $importedAsins;
    }

    /**
     * Helper para logging.
     */
    private function log(string $message, string $level = 'info'): void
    {
        if (class_exists('\Glory\Core\GloryLogger')) {
            if ($level === 'error') {
                \Glory\Core\GloryLogger::error($message);
            } else {
                \Glory\Core\GloryLogger::info($message);
            }
        }
    }
}
