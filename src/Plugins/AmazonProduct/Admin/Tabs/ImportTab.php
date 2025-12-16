<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\Service\AmazonApiService;
use Glory\Plugins\AmazonProduct\Service\ProductImporter;
use Glory\Plugins\AmazonProduct\Service\ApiClient;
use Glory\Plugins\AmazonProduct\Mode\PluginMode;

/**
 * Import Products Tab - AJAX powered search and import.
 * 
 * Funciona tanto en modo servidor como cliente.
 * En modo cliente muestra el uso de datos.
 */
class ImportTab implements TabInterface
{
    public function __construct()
    {
        add_action('wp_ajax_amazon_search_products', [$this, 'ajaxSearch']);
        add_action('wp_ajax_amazon_import_single', [$this, 'ajaxImport']);
        add_action('wp_ajax_amazon_quick_import', [$this, 'ajaxQuickImport']);
    }

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
        $affiliateTag = get_option('amazon_affiliate_tag', '');
        $region = get_option('amazon_api_region', 'es');
        $isClientMode = PluginMode::isClient();

        if ($isClientMode && !PluginMode::getApiKey()) {
            echo '<div class="notice notice-error inline"><p><strong>API Key no configurada.</strong> Ve a la pestaña "Licencia" para activar tu suscripcion.</p></div>';
            return;
        }

        if (empty($affiliateTag)) {
            echo '<div class="notice notice-warning inline"><p><strong>Tag de Afiliado no configurado.</strong> Ve a "Settings" para configurar tu Amazon Affiliate Tag y empezar a ganar comisiones.</p></div>';
        }

        $this->renderUsageWidget();
?>
        <div class="wrap amazon-import-tab">
            <h3>Buscar e Importar</h3>
            <p style="color: #666;">
                Busca productos en Amazon y importalos a tu tienda.
                <strong>Region actual:</strong> Amazon.<?php echo esc_html($region === 'es' ? 'es' : $region); ?>
            </p>

            <div class="search-box" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
                <input type="text" id="amazon-search-keyword" placeholder="Buscar producto..." class="regular-text" style="min-width: 300px;">
                <button type="button" id="amazon-search-btn" class="button button-primary">Buscar en Amazon</button>
            </div>

            <div id="amazon-search-results"></div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Search State
                let currentKeyword = '';
                let currentPage = 1;

                // Bind Search
                $('#amazon-search-btn').on('click', function() {
                    currentKeyword = $('#amazon-search-keyword').val();
                    currentPage = 1;
                    performSearch(false);
                });

                // Bind Enter Key
                $('#amazon-search-keyword').on('keypress', function(e) {
                    if (e.which == 13) {
                        currentKeyword = $(this).val();
                        currentPage = 1;
                        performSearch(false);
                    }
                });

                // Bind Pagination
                $(document).on('click', '.amazon-page-link', function(e) {
                    e.preventDefault();
                    if ($(this).attr('disabled')) return;

                    if ($(this).data('page')) {
                        currentPage = $(this).data('page');
                        performSearch(false);
                    }
                });

                // Bind Refresh Cache
                $(document).on('click', '#amazon-force-refresh', function(e) {
                    e.preventDefault();
                    performSearch(true);
                });

                /* 
                 * Handler para Importacion Rapida (Quick Import)
                 * Usa los datos de busqueda directamente, sin peticion extra al servidor.
                 */
                $(document).on('click', '.amazon-quick-import-btn', function(e) {
                    e.preventDefault();
                    const btn = $(this);
                    const container = btn.closest('.amazon-action-btns');
                    const productData = btn.data('product');
                    const asin = productData.asin;

                    container.find('button').prop('disabled', true);
                    btn.text('Importando...');

                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'amazon_quick_import',
                            product_data: JSON.stringify(productData),
                            nonce: '<?php echo wp_create_nonce('amazon_import_ajax'); ?>'
                        },
                        success: function(response) {
                            handleImportSuccess(response, asin, container);
                        },
                        error: function() {
                            handleImportError(container, btn, 'Rapida');
                        }
                    });
                });

                /* 
                 * Handler para Importacion Detallada (Detailed Import)
                 * Hace peticion extra al servidor para obtener mas datos.
                 */
                $(document).on('click', '.amazon-detailed-import-btn', function(e) {
                    e.preventDefault();
                    const btn = $(this);
                    const container = btn.closest('.amazon-action-btns');
                    const asin = btn.data('asin');

                    container.find('button').prop('disabled', true);
                    btn.text('Obteniendo...');

                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'amazon_import_single',
                            asin: asin,
                            nonce: '<?php echo wp_create_nonce('amazon_import_ajax'); ?>'
                        },
                        success: function(response) {
                            handleImportSuccess(response, asin, container);
                        },
                        error: function() {
                            handleImportError(container, btn, 'Detallada');
                        }
                    });
                });

                /* 
                 * Funcion comun para manejar exito de importacion 
                 */
                function handleImportSuccess(response, asin, container) {
                    if (response.success) {
                        const data = response.data;
                        const importType = data.import_type === 'quick' ? 'Rapida' : 'Detallada';
                        const viewBtn = `<a href="${data.edit_link}" target="_blank" class="button button-secondary">Ver Producto</a>`;
                        container.html(viewBtn);

                        $(`#row-status-${asin}`).html(`
                            <span style="background: #46b450; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                ${data.action === 'updated' ? 'Actualizado' : 'Importado'}
                            </span>
                            <br><small style="color: #666;">ID: ${data.id} (${importType})</small>
                        `);

                        container.closest('tr').css('background', '#f0f8e8');

                        if (data.price_html) {
                            $(`#row-price-${asin}`).html(data.price_html);
                        }
                    } else {
                        alert('Error: ' + (response.data || 'Unknown error'));
                        container.find('button').prop('disabled', false);
                    }
                }

                /* 
                 * Funcion comun para manejar error de importacion 
                 */
                function handleImportError(container, btn, type) {
                    alert('Error de conexion');
                    container.find('button').prop('disabled', false);
                    btn.text(type);
                }

                function performSearch(forceRefresh) {
                    if (!currentKeyword) return;

                    $('#amazon-search-results').html('<p class="spinner is-active" style="float:none; display:inline-block;"></p> Buscando...');

                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'amazon_search_products',
                            keyword: currentKeyword,
                            page: currentPage,
                            force_refresh: forceRefresh ? 1 : 0,
                            nonce: '<?php echo wp_create_nonce('amazon_search_ajax'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#amazon-search-results').html(response.data);
                            } else {
                                $('#amazon-search-results').html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>');
                            }
                        },
                        error: function() {
                            $('#amazon-search-results').html('<div class="notice notice-error inline"><p>Error de conexión</p></div>');
                        }
                    });
                }
            });
        </script>
    <?php
    }

    public function ajaxSearch(): void
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

        // Cache info
        $cacheInfoHtml = '';
        $lastCache = $service->getLastCacheTime();
        if ($lastCache && !$forceRefresh) {
            $timeAgo = human_time_diff($lastCache);
            $cacheInfoHtml = '<div class="notice notice-info inline" style="margin-bottom:15px; display:flex; align-items:center; justify-content:space-between;">' .
                '<p>Resultados cacheados hace ' . $timeAgo . '.</p>' .
                '<button type="button" id="amazon-force-refresh" class="button button-small">Forzar actualizacion</button>' .
                '</div>';
        } elseif ($forceRefresh) {
            $cacheInfoHtml = '<div class="notice notice-success inline" style="margin-bottom:15px;"><p>Cache limpiada. Resultados actualizados.</p></div>';
        }

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
     * Obtiene: categoria, descripcion, precio original, prime, multiples imagenes.
     */
    public function ajaxImport(): void
    {
        try {
            check_ajax_referer('amazon_import_ajax', 'nonce');

            $asin = sanitize_text_field($_POST['asin'] ?? '');
            if (empty($asin)) {
                wp_send_json_error('ASIN vacio');
            }

            if (class_exists('\Glory\Core\GloryLogger')) {
                \Glory\Core\GloryLogger::info("AjaxImport (Detallada): Start for ASIN $asin");
            }

            $service = new AmazonApiService();
            $existingId = ProductImporter::findByAsin($asin);

            $productData = $service->getProductByAsin($asin);

            if (!empty($productData) && is_array($productData)) {
                $postId = ProductImporter::importProduct($productData);
                if ($postId) {
                    $editLink = get_edit_post_link($postId, 'display');
                    if (!$editLink) $editLink = admin_url('post.php?post=' . $postId . '&action=edit');

                    $action = $existingId ? 'updated' : 'imported';

                    $savedPrice = get_post_meta($postId, 'price', true);
                    $fetchedPrice = $productData['asin_price'] ?? 0;

                    $fetchedPriceFormatted = function_exists('wc_price') ? wc_price($fetchedPrice) : $fetchedPrice . ' €';
                    $savedPriceFormatted = function_exists('wc_price') ? wc_price($savedPrice) : $savedPrice . ' €';

                    $priceHtml = '<div><span style="color: #2271b1; font-weight: bold;">' . $fetchedPriceFormatted . '</span><br><small style="color: #666;">Detectado</small></div>';
                    $priceHtml .= '<div style="margin-top: 5px; border-top: 1px dotted #ccc; padding-top: 2px;"><span style="color: #46b450; font-weight: bold;">' . $savedPriceFormatted . '</span><br><small style="color: #666;">Guardado</small></div>';

                    if (class_exists('\Glory\Core\GloryLogger')) {
                        \Glory\Core\GloryLogger::info("AjaxImport (Detallada): Success. Post ID: $postId");
                    }

                    wp_send_json_success([
                        'id' => $postId,
                        'action' => $action,
                        'edit_link' => $editLink,
                        'title' => get_the_title($postId),
                        'price_html' => $priceHtml,
                        'import_type' => 'detailed'
                    ]);
                } else {
                    wp_send_json_error('Error al importar en BD');
                }
            } else {
                wp_send_json_error('Error al obtener datos de Amazon');
            }
        } catch (\Throwable $e) {
            if (class_exists('\Glory\Core\GloryLogger')) {
                \Glory\Core\GloryLogger::error("AjaxImport Error: " . $e->getMessage());
            }
            wp_send_json_error('Error Fatal: ' . $e->getMessage());
        }
    }

    /**
     * Importacion Rapida - Usa datos de busqueda sin peticion extra.
     * Ahorra proxy y tiempo. No obtiene: categoria, descripcion, precio original.
     */
    public function ajaxQuickImport(): void
    {
        try {
            check_ajax_referer('amazon_import_ajax', 'nonce');

            $productJson = stripslashes($_POST['product_data'] ?? '');
            $productData = json_decode($productJson, true);

            if (empty($productData) || empty($productData['asin'])) {
                wp_send_json_error('Datos del producto invalidos');
            }

            $asin = sanitize_text_field($productData['asin']);

            if (class_exists('\Glory\Core\GloryLogger')) {
                \Glory\Core\GloryLogger::info("AjaxQuickImport: Start for ASIN $asin");
            }

            $existingId = ProductImporter::findByAsin($asin);

            /* 
             * Normalizar datos de busqueda al formato esperado por importProduct:
             * - image_url -> asin_images (array)
             */
            if (!empty($productData['image_url']) && empty($productData['asin_images'])) {
                $productData['asin_images'] = [$productData['image_url']];
            }

            $postId = ProductImporter::importProduct($productData);

            if ($postId) {
                $editLink = get_edit_post_link($postId, 'display');
                if (!$editLink) $editLink = admin_url('post.php?post=' . $postId . '&action=edit');

                $action = $existingId ? 'updated' : 'imported';

                $savedPrice = get_post_meta($postId, 'price', true);
                $fetchedPrice = $productData['asin_price'] ?? 0;

                $fetchedPriceFormatted = function_exists('wc_price') ? wc_price($fetchedPrice) : $fetchedPrice . ' €';
                $savedPriceFormatted = function_exists('wc_price') ? wc_price($savedPrice) : $savedPrice . ' €';

                $priceHtml = '<div><span style="color: #2271b1; font-weight: bold;">' . $fetchedPriceFormatted . '</span><br><small style="color: #666;">Detectado</small></div>';
                $priceHtml .= '<div style="margin-top: 5px; border-top: 1px dotted #ccc; padding-top: 2px;"><span style="color: #46b450; font-weight: bold;">' . $savedPriceFormatted . '</span><br><small style="color: #666;">Guardado</small></div>';

                if (class_exists('\Glory\Core\GloryLogger')) {
                    \Glory\Core\GloryLogger::info("AjaxQuickImport: Success. Post ID: $postId");
                }

                wp_send_json_success([
                    'id' => $postId,
                    'action' => $action,
                    'edit_link' => $editLink,
                    'title' => get_the_title($postId),
                    'price_html' => $priceHtml,
                    'import_type' => 'quick'
                ]);
            } else {
                wp_send_json_error('Error al importar en BD');
            }
        } catch (\Throwable $e) {
            if (class_exists('\Glory\Core\GloryLogger')) {
                \Glory\Core\GloryLogger::error("AjaxQuickImport Error: " . $e->getMessage());
            }
            wp_send_json_error('Error Fatal: ' . $e->getMessage());
        }
    }

    private function renderResultsTable(array $results, int $page): void
    {
        // Pre-cargar ASINs ya importados para evitar multiples queries
        $importedAsins = $this->getImportedAsins($results);
    ?>
        <div class="tablenav top">
            <div class="tablenav-pages">
                <span class="displaying-num">Pagina <?php echo $page; ?></span>
                <span class="pagination-links">
                    <button type="button" class="button amazon-page-link" data-page="<?php echo max(1, $page - 1); ?>" <?php echo $page <= 1 ? 'disabled' : ''; ?>>&laquo; Anterior</button>
                    <button type="button" class="button amazon-page-link" data-page="<?php echo $page + 1; ?>">Siguiente &raquo;</button>
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
                    <th style="width: 200px;">Accion</th>
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
                        <td id="row-price-<?php echo esc_attr($asin); ?>">
                            <div>
                                <span style="color: #2271b1; font-weight: bold;">
                                    <?php echo (function_exists('wc_price') ? wc_price($fetchedPrice) : $fetchedPrice . ' €'); ?>
                                </span>
                                <br>
                                <small style="color: #666;">Detectado</small>
                            </div>
                            <?php if ($isImported && $savedPrice !== null): ?>
                                <div style="margin-top: 5px; border-top: 1px dotted #ccc; padding-top: 2px;">
                                    <span style="color: #46b450; font-weight: bold;">
                                        <?php echo (function_exists('wc_price') ? wc_price($savedPrice) : $savedPrice . ' €'); ?>
                                    </span>
                                    <br>
                                    <small style="color: #666;">Guardado</small>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td id="row-status-<?php echo esc_attr($asin); ?>">
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
                            <?php 
                                /* Preparar datos del producto para importacion rapida */
                                $productJson = wp_json_encode([
                                    'asin' => $asin,
                                    'asin_name' => $item['asin_name'] ?? '',
                                    'asin_price' => $item['asin_price'] ?? 0,
                                    'asin_currency' => $item['asin_currency'] ?? 'EUR',
                                    'image_url' => $item['asin_images'][0] ?? $item['image_url'] ?? '',
                                    'rating' => $item['rating'] ?? 0,
                                    'total_review' => $item['total_review'] ?? 0,
                                    'in_stock' => $item['in_stock'] ?? true
                                ]);
                            ?>
                            <div class="amazon-action-btns" style="display: flex; flex-direction: column; gap: 4px;">
                                <button type="button" 
                                    class="button button-primary amazon-quick-import-btn" 
                                    data-product='<?php echo esc_attr($productJson); ?>'
                                    title="Importacion rapida: usa datos de busqueda, sin peticion extra. Ahorra datos.">
                                    <?php echo $isImported ? 'Rapida' : 'Rapida'; ?>
                                </button>
                                <button type="button" 
                                    class="button amazon-detailed-import-btn" 
                                    data-asin="<?php echo esc_attr($asin); ?>"
                                    title="Importacion detallada: obtiene categoria, descripcion, precio original, etc.">
                                    <?php echo $isImported ? 'Detallada' : 'Detallada'; ?>
                                </button>
                            </div>
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
     * Renderiza el widget de uso de datos (solo en modo cliente).
     */
    private function renderUsageWidget(): void
    {
        if (!PluginMode::isClient()) {
            return;
        }

        $client = new ApiClient();
        $result = $client->getLicenseStatus();

        if (!$result['success'] || empty($result['status'])) {
            return;
        }

        $status = $result['status'];
        $gbUsed = $status['gb_used'] ?? 0;
        $gbLimit = $status['gb_limit'] ?? 4;
        $gbRemaining = max(0, $gbLimit - $gbUsed);
        $percentUsed = $gbLimit > 0 ? ($gbUsed / $gbLimit) * 100 : 0;

        $barColor = $percentUsed > 80 ? '#dc3232' : ($percentUsed > 60 ? '#f39c12' : '#46b450');
    ?>
        <div id="widget-uso-datos" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px; margin-bottom: 20px; max-width: 400px;">
            <h4 style="margin: 0 0 10px 0; font-size: 14px;">Uso de Datos</h4>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="flex: 1; background: #e0e0e0; border-radius: 10px; overflow: hidden;">
                    <div style="width: <?php echo min(100, $percentUsed); ?>%; height: 12px; background: <?php echo $barColor; ?>; transition: width 0.3s;"></div>
                </div>
                <span style="font-weight: bold; font-size: 13px;">
                    <?php echo number_format($gbUsed, 2); ?> / <?php echo $gbLimit; ?> GB
                </span>
            </div>
            <p style="margin: 8px 0 0 0; font-size: 12px; color: #666;">
                Te quedan <strong><?php echo number_format($gbRemaining, 2); ?> GB</strong> este mes.
            </p>
        </div>
<?php
    }
}
