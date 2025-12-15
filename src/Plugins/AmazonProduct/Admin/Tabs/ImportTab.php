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

        // Verificar si la API está configurada
        if (!$service->isConfigured()) {
            echo '<div class="notice notice-warning inline"><p><strong>API no configurada.</strong> Ve a la pestaña "Configuracion Guiada" para configurar tu API Key de RapidAPI.</p></div>';
        }

        // Handle search
        if (isset($_POST['amazon_search']) && check_admin_referer('amazon_search_action', 'amazon_search_nonce')) {
            if (!$service->isConfigured()) {
                $message = '<div class="notice notice-error inline"><p>No puedes buscar productos sin configurar la API primero. Ve a la pestaña "Configuracion Guiada".</p></div>';
            } else {
                $keyword = sanitize_text_field($_POST['keyword']);

                // Paginacion
                $page = isset($_POST['page_num']) ? (int) $_POST['page_num'] : 1;
                if (isset($_POST['search_action'])) {
                    if ($_POST['search_action'] === 'next') $page++;
                    if ($_POST['search_action'] === 'prev' && $page > 1) $page--;
                    // Si es una nueva busqueda (submit normal), reset a 1
                    if ($_POST['search_action'] === 'search') $page = 1;
                }

                $apiResults = $service->searchProducts($keyword, $page);

                // Validar que los resultados sean un array de productos válido
                if (is_array($apiResults) && !empty($apiResults)) {
                    // Verificar que el primer elemento sea un array (producto válido)
                    $firstItem = reset($apiResults);
                    if (is_array($firstItem) && isset($firstItem['asin'])) {
                        $results = $apiResults;
                    } else {
                        // La API devolvió algo pero no es la estructura esperada
                        $message = '<div class="notice notice-warning inline"><p>La API devolvio una respuesta inesperada. Verifica tu configuracion de API.</p></div>';
                    }
                } else {
                    $message = '<div class="notice notice-info inline"><p>No se encontraron productos para: <strong>' . esc_html($keyword) . '</strong></p></div>';
                }
            }
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

            if (!empty($productData) && is_array($productData)) {
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
        $currentPage = $page ?? 1;
        $currentKeyword = $keyword ?? '';

        $this->renderSearchForm($currentKeyword);
        $this->renderResultsTable($results, $currentPage, $currentKeyword);
    }

    private function renderSearchForm(string $currentKeyword): void
    {
?>
        <h3>Buscar e Importar</h3>
        <p style="color: #666;">Los productos ya importados se mostraran marcados. Puedes actualizarlos sin duplicar.</p>
        <form method="post" style="margin-bottom: 20px;">
            <?php wp_nonce_field('amazon_search_action', 'amazon_search_nonce'); ?>
            <input type="text" name="keyword" placeholder="Buscar producto..." value="<?php echo esc_attr($currentKeyword); ?>" required class="regular-text">
            <button type="submit" name="search_action" value="search" class="button button-primary">Buscar en Amazon</button>
        </form>
    <?php
    }

    private function renderResultsTable(array $results, int $page, string $keyword): void
    {
        if (empty($results)) {
            return;
        }

        // Pre-cargar ASINs ya importados para evitar multiples queries
        $importedAsins = $this->getImportedAsins($results);
    ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <span style="line-height: 30px; margin-right: 10px;">Pagina <?php echo $page; ?></span>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 80px;">Imagen</th>
                    <th>Titulo</th>
                    <th style="width: 120px;">ASIN</th>
                    <th style="width: 90px;">Precio</th>
                    <th style="width: 100px;">Valoracion</th>
                    <th style="width: 100px;">Estado</th>
                    <th style="width: 120px;">Accion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $item):
                    $asin = $item['asin'] ?? '';
                    $isImported = isset($importedAsins[$asin]);
                    $existingId = $isImported ? $importedAsins[$asin] : null;
                    $rating = $item['rating'] ?? 0;
                    $reviews = $item['total_review'] ?? 0;
                ?>
                    <tr style="<?php echo $isImported ? 'background: #f0f8e8;' : ''; ?>">
                        <td>
                            <img src="<?php echo esc_url($item['image_url'] ?? ($item['asin_images'][0] ?? '')); ?>" width="50" style="border-radius: 4px;">
                        </td>
                        <td><strong><?php echo esc_html($item['asin_name']); ?></strong></td>
                        <td><code><?php echo esc_html($asin); ?></code></td>
                        <td>
                            <strong><?php echo esc_html($item['asin_price'] ?? 'N/A'); ?></strong>
                            <small><?php echo esc_html($item['asin_currency'] ?? ''); ?></small>
                        </td>
                        <td>
                            <div style="color: #f0c14b;">
                                <?php echo $rating > 0 ? str_repeat('★', floor($rating)) : '-'; ?>
                                <span style="color: #666; font-size: 10px;">(<?php echo $rating; ?>)</span>
                            </div>
                        </td>
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

        <!-- Paginacion Footer -->
        <form method="post" style="margin-top: 15px; text-align: center;">
            <?php wp_nonce_field('amazon_search_action', 'amazon_search_nonce'); ?>
            <input type="hidden" name="keyword" value="<?php echo esc_attr($keyword); ?>">
            <input type="hidden" name="page_num" value="<?php echo $page; ?>">

            <?php if ($page > 1): ?>
                <button type="submit" name="search_action" value="prev" class="button">« Anterior</button>
            <?php endif; ?>

            <span style="margin: 0 10px;">Pagina <?php echo $page; ?></span>

            <button type="submit" name="search_action" value="next" class="button">Siguiente »</button>
        </form>

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
