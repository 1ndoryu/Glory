<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

use Glory\Plugins\AmazonProduct\Service\HtmlParserService;
use Glory\Plugins\AmazonProduct\Service\ProductImporter;
use Glory\Plugins\AmazonProduct\Service\ImageDownloaderService;
use Glory\Plugins\AmazonProduct\i18n\Labels;

/**
 * Tab para importar productos manualmente desde archivos HTML de Amazon.
 * Soporta drag & drop de multiples archivos para importacion en lote.
 */
class ManualImportTab implements TabInterface
{
    private HtmlParserService $parserService;
    private ImageDownloaderService $imageService;

    public function __construct()
    {
        $this->parserService = new HtmlParserService();
        $this->imageService = new ImageDownloaderService();
    }

    public function getSlug(): string
    {
        return 'manual-import';
    }

    public function getLabel(): string
    {
        return Labels::get('manual_import_tab');
    }

    public function render(): void
    {
        // Registrar AJAX handlers
        add_action('wp_ajax_amazon_parse_html', [$this, 'ajaxParseHtml']);
        add_action('wp_ajax_amazon_import_product', [$this, 'ajaxImportProduct']);

        $this->renderInterface();
        $this->renderStyles();
        $this->renderScripts();
    }

    /**
     * Renderiza la interfaz principal
     */
    private function renderInterface(): void
    {
?>
        <div class="amazon-manual-import-wrapper">
            <h2><?php echo esc_html(Labels::get('manual_import_title')); ?></h2>
            <p>Arrastra archivos HTML de productos de Amazon o haz clic para seleccionarlos. Puedes importar multiples productos a la vez.</p>

            <!-- Zona de Drag & Drop -->
            <div id="drop-zone" class="drop-zone">
                <div class="drop-zone-content">
                    <span class="dashicons dashicons-upload" style="font-size: 48px; color: #2271b1;"></span>
                    <p><strong>Arrastra archivos HTML aqui</strong></p>
                    <p style="color: #666;">o haz clic para seleccionar</p>
                    <input type="file" id="file-input" multiple accept=".html,.htm" style="display: none;">
                </div>
            </div>

            <!-- Barra de progreso -->
            <div id="progress-container" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <p id="progress-text">Procesando archivos...</p>
            </div>

            <!-- Tabla de productos extraidos -->
            <div id="products-container" style="display: none;">
                <h3>Productos Detectados</h3>
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <label>
                            <input type="checkbox" id="download-images-global" checked>
                            Descargar imagenes localmente
                        </label>
                    </div>
                    <div class="alignright">
                        <button type="button" id="import-selected" class="button button-primary" disabled>
                            Importar Seleccionados (0)
                        </button>
                        <button type="button" id="import-all" class="button button-secondary">
                            Importar Todos
                        </button>
                        <button type="button" id="clear-all" class="button">
                            Limpiar
                        </button>
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped" id="products-table">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="select-all">
                            </td>
                            <th class="column-image" style="width: 80px;">Imagen</th>
                            <th class="column-title">Producto</th>
                            <th class="column-price" style="width: 100px;">Precio</th>
                            <th class="column-rating" style="width: 80px;">Rating</th>
                            <th class="column-status" style="width: 120px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody">
                    </tbody>
                </table>
            </div>

            <!-- Log de importacion -->
            <div id="import-log" style="display: none;">
                <h3>Resultado de Importacion</h3>
                <div id="log-content"></div>
            </div>
        </div>

        <!-- Nonce para AJAX -->
        <?php wp_nonce_field('amazon_manual_import_ajax', 'amazon_import_nonce'); ?>
    <?php
    }

    /**
     * Renderiza los estilos CSS
     */
    private function renderStyles(): void
    {
    ?>
        <style>
            .amazon-manual-import-wrapper {
                max-width: 1200px;
            }

            .drop-zone {
                border: 3px dashed #c3c4c7;
                border-radius: 8px;
                padding: 60px 20px;
                text-align: center;
                background: #f6f7f7;
                cursor: pointer;
                transition: all 0.3s ease;
                margin-bottom: 20px;
            }

            .drop-zone:hover,
            .drop-zone.dragover {
                border-color: #2271b1;
                background: #f0f6fc;
            }

            .drop-zone.dragover {
                transform: scale(1.02);
            }

            .progress-bar {
                height: 20px;
                background: #ddd;
                border-radius: 10px;
                overflow: hidden;
                margin: 20px 0;
            }

            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #2271b1, #135e96);
                width: 0%;
                transition: width 0.3s ease;
            }

            #products-table .column-image img {
                width: 60px;
                height: 60px;
                object-fit: contain;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: #fff;
            }

            #products-table .product-info {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            #products-table .product-title {
                font-weight: 600;
            }

            #products-table .product-asin {
                color: #666;
                font-size: 12px;
            }

            #products-table .product-category {
                color: #999;
                font-size: 11px;
            }

            #products-table .price-current {
                font-weight: 600;
                color: #0f9d58;
            }

            #products-table .price-original {
                text-decoration: line-through;
                color: #999;
                font-size: 12px;
            }

            #products-table .price-discount {
                color: #d93025;
                font-size: 11px;
                font-weight: 600;
            }

            #products-table .badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: 600;
            }

            #products-table .badge-pending {
                background: #fff3cd;
                color: #856404;
            }

            #products-table .badge-success {
                background: #d4edda;
                color: #155724;
            }

            #products-table .badge-error {
                background: #f8d7da;
                color: #721c24;
            }

            #products-table .badge-exists {
                background: #cce5ff;
                color: #004085;
            }

            #products-table .badge-prime {
                background: #232f3e;
                color: #fff;
                margin-left: 5px;
            }

            .tablenav {
                padding: 10px 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .tablenav .alignright button {
                margin-left: 5px;
            }

            #import-log .log-item {
                padding: 8px 12px;
                margin: 5px 0;
                border-radius: 4px;
            }

            #import-log .log-success {
                background: #d4edda;
            }

            #import-log .log-error {
                background: #f8d7da;
            }
        </style>
    <?php
    }

    /**
     * Renderiza el JavaScript
     */
    private function renderScripts(): void
    {
    ?>
        <script>
            jQuery(document).ready(function($) {
                        const dropZone = $('#drop-zone');
                        const fileInput = $('#file-input');
                        const productsContainer = $('#products-container');
                        const productsTbody = $('#products-tbody');
                        const progressContainer = $('#progress-container');
                        const progressFill = $('#progress-fill');
                        const progressText = $('#progress-text');
                        const nonce = $('#amazon_import_nonce').val();

                        let products = [];

                        // Click en zona para abrir selector de archivos
                        dropZone.on('click', function() {
                            fileInput.click();
                        });

                        // Eventos de drag & drop
                        dropZone.on('dragover dragenter', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            $(this).addClass('dragover');
                        });

                        dropZone.on('dragleave dragend drop', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            $(this).removeClass('dragover');
                        });

                        dropZone.on('drop', function(e) {
                            const files = e.originalEvent.dataTransfer.files;
                            processFiles(files);
                        });

                        fileInput.on('change', function() {
                            processFiles(this.files);
                        });

                        // Procesar archivos HTML
                        function processFiles(files) {
                            const htmlFiles = Array.from(files).filter(f => f.name.match(/\.html?$/i));

                            if (htmlFiles.length === 0) {
                                alert('Por favor selecciona archivos HTML validos.');
                                return;
                            }

                            progressContainer.show();
                            productsContainer.hide();

                            let processed = 0;
                            const total = htmlFiles.length;

                            htmlFiles.forEach((file, index) => {
                                const reader = new FileReader();

                                reader.onload = function(e) {
                                    const html = e.target.result;

                                    // Enviar HTML al servidor para parsear
                                    $.ajax({
                                        url: ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'amazon_parse_html',
                                            nonce: nonce,
                                            html: html,
                                            filename: file.name
                                        },
                                        success: function(response) {
                                            if (response.success && response.data) {
                                                response.data.filename = file.name;
                                                products.push(response.data);
                                            }
                                            processed++;
                                            updateProgress(processed, total);

                                            if (processed === total) {
                                                finishProcessing();
                                            }
                                        },
                                        error: function() {
                                            processed++;
                                            updateProgress(processed, total);
                                            if (processed === total) {
                                                finishProcessing();
                                            }
                                        }
                                    });
                                };

                                reader.readAsText(file);
                            });
                        }

                        function updateProgress(current, total) {
                            const percent = Math.round((current / total) * 100);
                            progressFill.css('width', percent + '%');
                            progressText.text(`Procesando ${current} de ${total} archivos...`);
                        }

                        function finishProcessing() {
                            progressContainer.hide();

                            if (products.length === 0) {
                                alert('No se encontraron productos validos en los archivos.');
                                return;
                            }

                            renderProductsTable();
                            productsContainer.show();
                        }

                        function renderProductsTable() {
                            productsTbody.empty();

                            products.forEach((product, index) => {
                                const discount = product.original_price > product.price ?
                                    Math.round(((product.original_price - product.price) / product.original_price) * 100) :
                                    0;

                                const statusBadge = product.exists ?
                                    '<span class="badge badge-exists">Existente</span>' :
                                    '<span class="badge badge-pending">Pendiente</span>';

                                const primeBadge = product.prime ?
                                    '<span class="badge badge-prime">Prime</span>' :
                                    '';

                                const row = `
                        <tr data-index="${index}">
                            <th class="check-column">
                                <input type="checkbox" class="product-checkbox" data-index="${index}">
                            </th>
                            <td class="column-image">
                                ${product.image ? `<img src="${product.image}" alt="">` : '<div style="width:60px;height:60px;background:#f0f0f1;"></div>'}
                            </td>
                            <td class="column-title">
                                <div class="product-info">
                                    <span class="product-title">${escapeHtml(product.title || 'Sin titulo')}</span>
                                    <span class="product-asin">ASIN: ${product.asin || 'N/A'} ${primeBadge}</span>
                                    <span class="product-category">${escapeHtml(truncate(product.category || '', 50))}</span>
                                </div>
                            </td>
                            <td class="column-price">
                                <div class="price-current">$${(product.price || 0).toFixed(2)}</div>
                                ${discount > 0 ? `
                                    <div class="price-original">$${product.original_price.toFixed(2)}</div>
                                    <div class="price-discount">-${discount}%</div>
                                ` : ''}
                            </td>
                            <td class="column-rating">
                                ${product.rating ? `${product.rating}/5` : '-'}<br>
                                <small style="color:#666;">${product.reviews || 0} reviews</small>
                            </td>
                            <td class="column-status">
                                <span class="status-badge" data-index="${index}">${statusBadge}</span>
                            </td>
                        </tr>
                    `;
                                productsTbody.append(row);
                            });

                            updateSelectedCount();
                        }

                        function escapeHtml(text) {
                            const div = document.createElement('div');
                            div.textContent = text;
                            return div.innerHTML;
                        }

                        function truncate(str, max) {
                            return str.length > max ? str.substr(0, max) + '...' : str;
                        }

                        // Seleccionar todos
                        $('#select-all').on('change', function() {
                            $('.product-checkbox').prop('checked', $(this).is(':checked'));
                            updateSelectedCount();
                        });

                        $(document).on('change', '.product-checkbox', function() {
                            updateSelectedCount();
                        });

                        function updateSelectedCount() {
                            const count = $('.product-checkbox:checked').length;
                            $('#import-selected').text(`Importar Seleccionados (${count})`);
                            $('#import-selected').prop('disabled', count === 0);
                        }

                        // Importar seleccionados
                        $('#import-selected').on('click', function() {
                            const indices = [];
                            $('.product-checkbox:checked').each(function() {
                                indices.push($(this).data('index'));
                            });
                            importProducts(indices);
                        });

                        // Importar todos
                        $('#import-all').on('click', function() {
                            const indices = products.map((_, i) => i);
                            importProducts(indices);
                        });

                        // Limpiar
                        $('#clear-all').on('click', function() {
                            products = [];
                            productsTbody.empty();
                            productsContainer.hide();
                            $('#import-log').hide();
                        });

                        function importProducts(indices) {
                            const downloadImages = $('#download-images-global').is(':checked');
                            const logContainer = $('#import-log');
                            const logContent = $('#log-content');

                            logContainer.show();
                            logContent.empty();

                            let imported = 0;
                            const total = indices.length;

                            indices.forEach((index, i) => {
                                const product = products[index];

                                setTimeout(() => {
                                    $.ajax({
                                        url: ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'amazon_import_product',
                                            nonce: nonce,
                                            product: JSON.stringify(product),
                                            download_image: downloadImages ? 1 : 0
                                        },
                                        success: function(response) {
                                            imported++;
                                            const statusSpan = $(`.status-badge[data-index="${index}"]`);

                                            if (response.success) {
                                                statusSpan.html('<span class="badge badge-success">Importado</span>');
                                                logContent.append(`<div class="log-item log-success">OK ${product.title}</div>`);
                                            } else {
                                                statusSpan.html('<span class="badge badge-error">Error</span>');
                                                logContent.append(`<div class="log-item log-error">X ${product.title}: ${response.data || 'Error desconocido'}</div>`);
                                            }

                                            if (imported === total) {
                                                logContent.append(`<div class="log-item" style="font-weight:bold;margin-top:10px;">Completado: ${imported} productos procesados</div>`);
                                            }
                                        },
                                        error: function() {
                                            imported++;
                                            $(`.status-badge[data-index="${index}"]`).html('<span class="badge badge-error">Error</span>');
                                        }
                                    });
                                }, i * 500);
                            });
                        }
        </script>
<?php
    }

    /**
     * AJAX: Parsear HTML
     */
    public function ajaxParseHtml(): void
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

        // Verificar si ya existe
        $data['exists'] = ProductImporter::findByAsin($data['asin']) !== null;

        wp_send_json_success($data);
    }

    /**
     * AJAX: Importar producto
     */
    public function ajaxImportProduct(): void
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
     * Guarda el producto en la base de datos
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
            // Actualizar existente
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
        } else {
            // Crear nuevo
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
}
