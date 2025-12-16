(function ($) {
    'use strict';

    /**
     * Amazon Import Tab - JavaScript Handler
     *
     * Maneja las interacciones AJAX para busqueda e importacion de productos.
     */

    /* Estado de busqueda */
    let currentKeyword = '';
    let currentPage = 1;

    /* Inicializacion */
    $(document).ready(function () {
        bindSearchEvents();
        bindImportEvents();
        bindPaginationEvents();
    });

    /*
     * Eventos de busqueda
     */
    function bindSearchEvents() {
        $('#amazon-search-btn').on('click', function () {
            currentKeyword = $('#amazon-search-keyword').val();
            currentPage = 1;
            performSearch(false);
        });

        $('#amazon-search-keyword').on('keypress', function (e) {
            if (e.which === 13) {
                currentKeyword = $(this).val();
                currentPage = 1;
                performSearch(false);
            }
        });

        $(document).on('click', '#amazon-force-refresh', function (e) {
            e.preventDefault();
            performSearch(true);
        });
    }

    /*
     * Eventos de paginacion
     */
    function bindPaginationEvents() {
        $(document).on('click', '.amazon-page-link', function (e) {
            e.preventDefault();
            if ($(this).attr('disabled')) return;

            if ($(this).data('page')) {
                currentPage = $(this).data('page');
                performSearch(false);
            }
        });
    }

    /*
     * Eventos de importacion
     */
    function bindImportEvents() {
        /* Importacion Rapida */
        $(document).on('click', '.amazon-quick-import-btn', function (e) {
            e.preventDefault();
            const btn = $(this);
            const container = btn.closest('.amazon-action-btns');
            const productData = btn.data('product');
            const asin = productData.asin;

            /* Guardar datos en el contenedor para reimportacion */
            container.data('productData', productData);

            container.find('button').prop('disabled', true);
            btn.text('Importando...');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'amazon_quick_import',
                    product_data: JSON.stringify(productData),
                    nonce: amazonImportConfig.importNonce
                },
                success: function (response) {
                    handleImportSuccess(response, asin, container);
                },
                error: function () {
                    handleImportError(container, btn, 'Rapida');
                }
            });
        });

        /* Importacion Detallada */
        $(document).on('click', '.amazon-detailed-import-btn', function (e) {
            e.preventDefault();
            const btn = $(this);
            const container = btn.closest('.amazon-action-btns');
            const asin = btn.data('asin');

            /* Guardar asin y obtener datos del producto del boton rapido */
            const quickBtn = container.find('.amazon-quick-import-btn');
            if (quickBtn.length && quickBtn.data('product')) {
                container.data('productData', quickBtn.data('product'));
            }

            container.find('button').prop('disabled', true);
            btn.text('Obteniendo...');

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'amazon_import_single',
                    asin: asin,
                    nonce: amazonImportConfig.importNonce
                },
                success: function (response) {
                    handleImportSuccess(response, asin, container);
                },
                error: function () {
                    handleImportError(container, btn, 'Detallada');
                }
            });
        });
    }

    /*
     * Funcion de busqueda
     */
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
                nonce: amazonImportConfig.searchNonce
            },
            success: function (response) {
                if (response.success) {
                    $('#amazon-search-results').html(response.data);
                } else {
                    $('#amazon-search-results').html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>');
                }
            },
            error: function () {
                $('#amazon-search-results').html('<div class="notice notice-error inline"><p>Error de conexion</p></div>');
            }
        });
    }

    /*
     * Maneja el exito de importacion
     */
    function handleImportSuccess(response, asin, container) {
        if (response.success) {
            const data = response.data;
            const importType = data.import_type === 'quick' ? 'Rapida' : 'Detallada';

            /* Reconstruir botones con opcion de reimportar */
            const viewBtn = '<a href="' + data.edit_link + '" target="_blank" class="button button-secondary" style="margin-bottom: 4px; width: 100%; text-align: center;">Ver Producto</a>';

            /* Obtener datos del producto guardados en el contenedor */
            const savedProductData = container.data('productData');
            const productDataJson = savedProductData ? JSON.stringify(savedProductData).replace(/'/g, '&#39;') : '';

            /* Botones de reimportacion */
            let quickBtn = '';
            if (productDataJson) {
                quickBtn = '<button type="button" class="button amazon-quick-import-btn" data-product=\'' + productDataJson + '\' title="Reimportar con datos de busqueda" style="width: 100%;">Reimp. Rapida</button>';
            }
            const detailedBtn = '<button type="button" class="button amazon-detailed-import-btn" data-asin="' + asin + '" title="Reimportar obteniendo datos completos" style="width: 100%;">Reimp. Detallada</button>';

            container.html(viewBtn + '<div style="display: flex; gap: 10px; margin-top: 4px; flex-direction: column;">' + quickBtn + detailedBtn + '</div>');

            $('#row-status-' + asin).html('<span style="background: #46b450; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px;">' + (data.action === 'updated' ? 'Actualizado' : 'Importado') + '</span><br><small style="color: #666;">ID: ' + data.id + ' (' + importType + ')</small>');

            container.closest('tr').css('background', '#f0f8e8');

            if (data.price_html) {
                $('#row-price-' + asin).html(data.price_html);
            }
        } else {
            alert('Error: ' + (response.data || 'Unknown error'));
            container.find('button').prop('disabled', false);
        }
    }

    /*
     * Maneja el error de importacion
     */
    function handleImportError(container, btn, type) {
        alert('Error de conexion');
        container.find('button').prop('disabled', false);
        btn.text(type);
    }
})(jQuery);
