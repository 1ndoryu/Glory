<?php

/**
 * Vista: Tabla de resultados de busqueda de productos Amazon.
 * 
 * Variables disponibles:
 * - $results: array de productos de Amazon
 * - $page: numero de pagina actual
 * - $importedAsins: array [asin => postId] de productos ya importados
 */

if (!defined('ABSPATH')) exit;
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
            <th style="width: 100px;">Resenas</th>
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
                    <div class="amazon-action-btns" style="display: flex; flex-direction: column; gap: 10px;">
                        <?php if ($isImported): ?>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $existingId . '&action=edit')); ?>"
                                target="_blank"
                                class="button button-secondary"
                                style="width: 100%; text-align: center;">
                                Ver Producto
                            </a>
                        <?php endif; ?>
                        <button type="button"
                            class="button <?php echo $isImported ? '' : 'button-primary'; ?> amazon-quick-import-btn"
                            data-product='<?php echo esc_attr($productJson); ?>'
                            title="Importacion rapida: usa datos de busqueda, sin peticion extra. Ahorra datos.">
                            <?php echo $isImported ? 'Reimp. Rapida' : 'Rapida'; ?>
                        </button>
                        <button type="button"
                            class="button amazon-detailed-import-btn"
                            data-asin="<?php echo esc_attr($asin); ?>"
                            title="Importacion detallada: obtiene categoria, descripcion, precio original, etc.">
                            <?php echo $isImported ? 'Reimp. Detallada' : 'Detallada'; ?>
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