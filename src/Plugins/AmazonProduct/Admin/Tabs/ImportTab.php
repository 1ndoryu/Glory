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

        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';

        // Handle Pagination clicks
        if (isset($_POST['next_page'])) {
            $page++;
        } elseif (isset($_POST['prev_page'])) {
            $page = max(1, $page - 1);
        } elseif (isset($_POST['amazon_search'])) {
            $page = 1; // Reset to 1 on new search
        }

        // Verificar si la API está configurada
        if (!$service->isConfigured()) {
            echo '<div class="notice notice-warning inline"><p><strong>API no configurada.</strong> Ve a la pestaña "Configuracion Guiada" para configurar tu API Key de RapidAPI.</p></div>';
        }

        // Handle Search and Refresh
        if ((!empty($keyword) || isset($_POST['refresh_search'])) && check_admin_referer('amazon_search_action', 'amazon_search_nonce')) {
            if (!$service->isConfigured()) {
                $message = '<div class="notice notice-error inline"><p>No puedes buscar productos sin configurar la API primero. Ve a la pestaña "Configuracion Guiada".</p></div>';
            } else {
                $forceRefresh = isset($_POST['refresh_search']);
                $apiResults = $service->searchProducts($keyword, $page, $forceRefresh);

                $lastCache = $service->getLastCacheTime();
                if ($lastCache && !$forceRefresh) {
                    $timeAgo = human_time_diff($lastCache);
                    $message = '<div class="notice notice-info inline" style="display:flex; align-items:center; justify-content:space-between;">';
                    $message .= '<p>Resultados cacheados hace ' . $timeAgo . '.</p>';
                    $message .= '<form method="post" style="margin:5px 0 0 10px;">';
                    $message .= wp_nonce_field('amazon_search_action', 'amazon_search_nonce', true, false);
                    $message .= '<input type="hidden" name="keyword" value="' . esc_attr($keyword) . '">';
                    $message .= '<input type="hidden" name="page" value="' . $page . '">';
                    $message .= '<input type="submit" name="refresh_search" class="button button-small" value="Forzar actualizacion">';
                    $message .= '</form>';
                    $message .= '</div>';
                } elseif ($forceRefresh) {
                    $message = '<div class="notice notice-success inline"><p>Cache limpiada. Resultados actualizados.</p></div>';
                }

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
                    $message = '<div class="notice notice-info inline"><p>No se encontraron productos para: <strong>' . esc_html($keyword) . '</strong> en la pagina ' . $page . '</p></div>';
                }
            }
        }

        // Handle import
        if (isset($_POST['amazon_import']) && check_admin_referer('amazon_import_action', 'amazon_import_nonce')) {
            $asin = sanitize_text_field($_POST['asin']);
            $existingId = ProductImporter::findByAsin($asin);

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
        $this->renderSearchForm($keyword, $page);
        $this->renderResultsTable($results, $page, $keyword);
    }

    private function renderSearchForm(string $keyword, int $page): void
    {
?>
        <h3>Buscar e Importar</h3>
        <p style="color: #666;">Los productos ya importados se mostraran marcados. Puedes actualizarlos sin duplicar.</p>
        <form method="post" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
            <?php wp_nonce_field('amazon_search_action', 'amazon_search_nonce'); ?>
            <input type="text" name="keyword" value="<?php echo esc_attr($keyword); ?>" placeholder="Buscar producto..." required class="regular-text">
            <input type="hidden" name="page" value="<?php echo $page; ?>">
            <input type="submit" name="amazon_search" class="button button-primary" value="Buscar en Amazon">
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
            <div class="tablenav-pages">
                <span class="displaying-num">Pagina <?php echo $page; ?></span>
                <span class="pagination-links">
                    <form method="post" style="display:inline-block;">
                        <?php wp_nonce_field('amazon_search_action', 'amazon_search_nonce'); ?>
                        <input type="hidden" name="keyword" value="<?php echo esc_attr($keyword); ?>">
                        <input type="hidden" name="page" value="<?php echo $page; ?>">
                        <button type="submit" name="prev_page" class="button" <?php disabled($page, 1); ?>>&laquo; Anterior</button>
                    </form>

                    <form method="post" style="display:inline-block;">
                        <?php wp_nonce_field('amazon_search_action', 'amazon_search_nonce'); ?>
                        <input type="hidden" name="keyword" value="<?php echo esc_attr($keyword); ?>">
                        <input type="hidden" name="page" value="<?php echo $page; ?>">
                        <button type="submit" name="next_page" class="button">Siguiente &raquo;</button>
                    </form>
                </span>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 80px;">Imagen</th>
                    <th>Titulo</th>
                    <th style="width: 120px;">ASIN</th>
                    <th style="width: 100px;">Reseñas</th>
                    <th style="width: 150px;">Precio</th>
                    <th style="width: 100px;">Estado</th>
                    <th style="width: 120px;">Accion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $item):
                    $asin = $item['asin'] ?? '';
                    $isImported = isset($importedAsins[$asin]);
                    $existingId = $isImported ? $importedAsins[$asin] : null;

                    $fetchedPrice = $item['asin_price'] ?? 0;
                    $savedPrice = $isImported ? get_post_meta($existingId, 'price', true) : null;

                    $reviews = $item['total_review'] ?? 0;
                    $rating = $item['rating'] ?? 0;
                ?>
                    <tr style="<?php echo $isImported ? 'background: #f0f8e8;' : ''; ?>">
                        <td>
                            <img src="<?php echo esc_url($item['asin_images'][0] ?? $item['image_url'] ?? ''); ?>" width="50" style="border-radius: 4px;">
                        </td>
                        <td>
                            <strong><?php echo esc_html($item['asin_name']); ?></strong>
                            <div class="row-actions">
                                <span class="view"><a href="<?php echo 'https://www.amazon.es/dp/' . $asin; ?>" target="_blank">Ver en Amazon</a></span>
                            </div>
                        </td>
                        <td><code><?php echo esc_html($asin); ?></code></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span style="color: #f39c12;">★</span> <strong><?php echo $rating; ?></strong>
                                <span style="color: #999; font-size: 11px;">(<?php echo number_format((int)$reviews); ?>)</span>
                            </div>
                        </td>
                        <td>
                            <div>
                                <span style="color: #2271b1; font-weight: bold;">
                                    <?php echo $fetchedPrice . ' €'; ?>
                                </span>
                                <br>
                                <small style="color: #666;">Detectado</small>
                            </div>
                            <?php if ($isImported && $savedPrice !== null): ?>
                                <div style="margin-top: 5px; border-top: 1px dotted #ccc; padding-top: 2px;">
                                    <span style="color: #46b450; font-weight: bold;">
                                        <?php echo $savedPrice . ' €'; ?>
                                    </span>
                                    <br>
                                    <small style="color: #666;">Guardado</small>
                                </div>
                            <?php endif; ?>
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
                                <!-- Preservation of state (page and keyword) for when the page reloads after action -->
                                <input type="hidden" name="page" value="<?php echo $page; ?>">
                                <input type="hidden" name="keyword" value="<?php echo esc_attr($keyword); ?>">

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
