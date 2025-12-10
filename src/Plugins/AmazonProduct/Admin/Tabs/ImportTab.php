<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\Service\AmazonApiService;
use Glory\Plugins\AmazonProduct\Service\ProductImporter;

/**
 * Import Products Tab - Search and import products from Amazon API.
 * Detecta productos ya importados para evitar duplicados y ahorrar llamadas API.
 */
class ImportTab implements TabInterface
{
    public function getSlug(): string
    {
        return 'import';
    }

    public function getLabel(): string
    {
        return 'Import Products';
    }

    public function render(): void
    {
        $service = new AmazonApiService();
        $results = [];
        $message = '';

        // Handle search
        if (isset($_POST['amazon_search']) && check_admin_referer('amazon_search_action', 'amazon_search_nonce')) {
            $keyword = sanitize_text_field($_POST['keyword']);
            $results = $service->searchProducts($keyword);
        }

        // Handle import
        if (isset($_POST['amazon_import']) && check_admin_referer('amazon_import_action', 'amazon_import_nonce')) {
            $asin = sanitize_text_field($_POST['asin']);
            $existingId = ProductImporter::findByAsin($asin);

            // Si ya existe, usar datos cacheados en lugar de hacer otra llamada API
            if ($existingId) {
                $message = '<div class="notice notice-info inline"><p>El producto ya existe (ID: ' . $existingId . '). Se actualizo con los datos mas recientes.</p></div>';
                // Aun asi actualizamos con datos frescos de la API
            }

            $productData = $service->getProductByAsin($asin);

            if (!empty($productData)) {
                $postId = ProductImporter::importProduct($productData);
                if ($postId) {
                    $action = $existingId ? 'actualizado' : 'importado';
                    $message = '<div class="notice notice-success inline"><p>Producto ' . $action . ' exitosamente! (ID: ' . $postId . ')</p></div>';
                } else {
                    $message = '<div class="notice notice-error inline"><p>Error al importar el producto.</p></div>';
                }
            } else {
                $message = '<div class="notice notice-error inline"><p>Error al obtener datos del producto.</p></div>';
            }
        }

        echo $message;
        $this->renderSearchForm();
        $this->renderResultsTable($results);
    }

    private function renderSearchForm(): void
    {
?>
        <h3>Buscar e Importar</h3>
        <p style="color: #666;">Los productos ya importados se mostraran marcados. Puedes actualizarlos sin duplicar.</p>
        <form method="post" style="margin-bottom: 20px;">
            <?php wp_nonce_field('amazon_search_action', 'amazon_search_nonce'); ?>
            <input type="text" name="keyword" placeholder="Buscar producto..." required class="regular-text">
            <input type="submit" name="amazon_search" class="button button-primary" value="Buscar en Amazon">
        </form>
    <?php
    }

    private function renderResultsTable(array $results): void
    {
        if (empty($results)) {
            return;
        }

        // Pre-cargar ASINs ya importados para evitar multiples queries
        $importedAsins = $this->getImportedAsins($results);
    ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 80px;">Imagen</th>
                    <th>Titulo</th>
                    <th style="width: 120px;">ASIN</th>
                    <th style="width: 80px;">Precio</th>
                    <th style="width: 100px;">Estado</th>
                    <th style="width: 120px;">Accion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $item):
                    $asin = $item['asin'] ?? '';
                    $isImported = isset($importedAsins[$asin]);
                    $existingId = $isImported ? $importedAsins[$asin] : null;
                ?>
                    <tr style="<?php echo $isImported ? 'background: #f0f8e8;' : ''; ?>">
                        <td>
                            <img src="<?php echo esc_url($item['asin_images'][0] ?? ''); ?>" width="50" style="border-radius: 4px;">
                        </td>
                        <td><strong><?php echo esc_html($item['asin_name']); ?></strong></td>
                        <td><code><?php echo esc_html($asin); ?></code></td>
                        <td><?php echo esc_html($item['asin_price'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($isImported): ?>
                                <span style="background: #46b450; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                    Ya importado
                                </span>
                                <br><small style="color: #666;">ID: <?php echo $existingId; ?></small>
                            <?php else: ?>
                                <span style="color: #999;">No importado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post">
                                <?php wp_nonce_field('amazon_import_action', 'amazon_import_nonce'); ?>
                                <input type="hidden" name="asin" value="<?php echo esc_attr($asin); ?>">
                                <?php if ($isImported): ?>
                                    <input type="submit" name="amazon_import" class="button" value="Actualizar">
                                <?php else: ?>
                                    <input type="submit" name="amazon_import" class="button button-primary" value="Importar">
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin-top: 15px; color: #666;">
            <strong>Nota:</strong> Los productos marcados en verde ya estan en tu base de datos.
            Puedes actualizarlos para refrescar precio y datos sin crear duplicados.
        </p>
<?php
    }

    /**
     * Obtiene un array de ASINs ya importados de los resultados de busqueda.
     * Optimizado para hacer una sola query en lugar de N queries.
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
}
