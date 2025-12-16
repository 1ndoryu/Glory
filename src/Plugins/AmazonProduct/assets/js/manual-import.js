/**
 * --------------------------------------------------------------------
 * Manual Import Tab - JavaScript Handler
 * --------------------------------------------------------------------
 * Maneja las interacciones de drag & drop e importacion de archivos HTML.
 */
(function ($) {
    'use strict';

    let dropZone;
    let fileInput;
    let productsContainer;
    let productsTbody;
    let progressContainer;
    let progressFill;
    let progressText;

    let products = [];

    /*
     * Inicializacion
     */
    $(document).ready(function () {
        /* Inicializar selectores DESPUES de que el DOM este listo */
        dropZone = $('#zona-arrastre');
        fileInput = $('#entrada-archivo');
        productsContainer = $('#contenedor-productos');
        productsTbody = $('#cuerpo-tabla-productos');
        progressContainer = $('#contenedor-progreso');
        progressFill = $('#relleno-progreso');
        progressText = $('#texto-progreso');

        /* Verificar que los elementos existan */
        if (dropZone.length === 0) {
            console.error('ManualImport: No se encontro #zona-arrastre');
            return;
        }

        bindDropZoneEvents();
        bindSelectionEvents();
        bindImportEvents();
    });

    /*
     * Eventos de Drag & Drop
     */
    function bindDropZoneEvents() {
        /* Click en la zona para abrir selector de archivos */
        dropZone.on('click', function (e) {
            /* Prevenir que el click se propague si viene del input */
            if (e.target === fileInput[0]) {
                return;
            }
            fileInput.trigger('click');
        });

        /* Prevenir comportamiento por defecto del navegador para drag/drop */
        $(document).on('dragover dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
        });

        $(document).on('drop', function (e) {
            /* Solo prevenir si no es en la zona de drop */
            if (!$(e.target).closest('#zona-arrastre').length) {
                e.preventDefault();
                e.stopPropagation();
            }
        });

        /* Eventos de arrastre en la zona */
        dropZone.on('dragover dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('arrastrando');
        });

        dropZone.on('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            /* Solo quitar clase si salimos realmente de la zona */
            const rect = this.getBoundingClientRect();
            const x = e.originalEvent.clientX;
            const y = e.originalEvent.clientY;
            if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
                $(this).removeClass('arrastrando');
            }
        });

        dropZone.on('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('arrastrando');

            const dt = e.originalEvent.dataTransfer;
            if (dt && dt.files && dt.files.length > 0) {
                processFiles(dt.files);
            }
        });

        /* Evento de cambio en el input file */
        fileInput.on('change', function () {
            if (this.files && this.files.length > 0) {
                processFiles(this.files);
                /* Limpiar el input para permitir seleccionar el mismo archivo de nuevo */
                this.value = '';
            }
        });
    }

    /*
     * Eventos de Seleccion
     */
    function bindSelectionEvents() {
        $('#seleccionar-todos').on('change', function () {
            $('.checkbox-producto').prop('checked', $(this).is(':checked'));
            updateSelectedCount();
        });

        $(document).on('change', '.checkbox-producto', function () {
            updateSelectedCount();
        });
    }

    /*
     * Eventos de Importacion
     */
    function bindImportEvents() {
        $('#importar-seleccionados').on('click', function () {
            const indices = [];
            $('.checkbox-producto:checked').each(function () {
                indices.push($(this).data('index'));
            });
            importProducts(indices);
        });

        $('#importar-todos').on('click', function () {
            const indices = products.map((_, i) => i);
            importProducts(indices);
        });

        $('#limpiar-todo').on('click', function () {
            products = [];
            productsTbody.empty();
            productsContainer.hide();
            $('#registro-importacion').hide();
        });
    }

    /*
     * Procesar Archivos HTML
     */
    function processFiles(files) {
        const htmlFiles = Array.from(files).filter(f => f.name.match(/\.html?$/i));

        if (htmlFiles.length === 0) {
            alert('Por favor selecciona archivos HTML validos (.html o .htm)');
            return;
        }

        progressContainer.show();
        productsContainer.hide();

        let processed = 0;
        const total = htmlFiles.length;

        htmlFiles.forEach(file => {
            const reader = new FileReader();

            reader.onload = function (e) {
                const html = e.target.result;

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'amazon_parse_html',
                        nonce: manualImportConfig.nonce,
                        html: html,
                        filename: file.name
                    },
                    success: function (response) {
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
                    error: function () {
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

    /*
     * Actualizar Barra de Progreso
     */
    function updateProgress(current, total) {
        const percent = Math.round((current / total) * 100);
        progressFill.css('width', percent + '%');
        progressText.text('Procesando ' + current + ' de ' + total + ' archivos...');
    }

    /*
     * Finalizar Procesamiento
     */
    function finishProcessing() {
        progressContainer.hide();

        if (products.length === 0) {
            alert('No se encontraron productos validos en los archivos.');
            return;
        }

        renderProductsTable();
        productsContainer.show();
    }

    /*
     * Renderizar Tabla de Productos
     */
    function renderProductsTable() {
        productsTbody.empty();

        products.forEach((product, index) => {
            const discount = product.original_price > product.price ? Math.round(((product.original_price - product.price) / product.original_price) * 100) : 0;

            const statusBadge = product.exists ? '<span class="etiqueta etiquetaExistente">Existente</span>' : '<span class="etiqueta etiquetaPendiente">Pendiente</span>';

            const primeBadge = product.prime ? '<span class="etiqueta etiquetaPrime">Prime</span>' : '';

            const row = buildProductRow(product, index, discount, statusBadge, primeBadge);
            productsTbody.append(row);
        });

        updateSelectedCount();
    }

    /*
     * Construir Fila de Producto
     */
    function buildProductRow(product, index, discount, statusBadge, primeBadge) {
        const imageHtml = product.image ? '<img src="' + product.image + '" alt="">' : '<div style="width:60px;height:60px;background:#f0f0f1;"></div>';

        const discountHtml = discount > 0 ? '<div class="precioOriginal">$' + product.original_price.toFixed(2) + '</div>' + '<div class="precioDescuento">-' + discount + '%</div>' : '';

        return (
            '<tr data-index="' +
            index +
            '">' +
            '<th class="check-column">' +
            '<input type="checkbox" class="checkbox-producto" data-index="' +
            index +
            '">' +
            '</th>' +
            '<td class="columnaImagen">' +
            imageHtml +
            '</td>' +
            '<td class="column-title">' +
            '<div class="infoProducto">' +
            '<span class="tituloProducto">' +
            escapeHtml(product.title || 'Sin titulo') +
            '</span>' +
            '<span class="asinProducto">ASIN: ' +
            (product.asin || 'N/A') +
            ' ' +
            primeBadge +
            '</span>' +
            '<span class="categoriaProducto">' +
            escapeHtml(truncate(product.category || '', 50)) +
            '</span>' +
            '</div>' +
            '</td>' +
            '<td class="column-price">' +
            '<div class="precioActual">$' +
            (product.price || 0).toFixed(2) +
            '</div>' +
            discountHtml +
            '</td>' +
            '<td class="column-rating">' +
            (product.rating ? product.rating + '/5' : '-') +
            '<br>' +
            '<small style="color:#666;">' +
            (product.reviews || 0) +
            ' reviews</small>' +
            '</td>' +
            '<td class="column-status">' +
            '<span class="etiqueta-estado" data-index="' +
            index +
            '">' +
            statusBadge +
            '</span>' +
            '</td>' +
            '</tr>'
        );
    }

    /*
     * Escapar HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /*
     * Truncar Texto
     */
    function truncate(str, max) {
        return str.length > max ? str.substr(0, max) + '...' : str;
    }

    /*
     * Actualizar Contador de Seleccionados
     */
    function updateSelectedCount() {
        const count = $('.checkbox-producto:checked').length;
        $('#importar-seleccionados').text('Importar Seleccionados (' + count + ')');
        $('#importar-seleccionados').prop('disabled', count === 0);
    }

    /*
     * Importar Productos
     */
    function importProducts(indices) {
        const downloadImages = $('#descargar-imagenes-global').is(':checked');
        const logContainer = $('#registro-importacion');
        const logContent = $('#contenido-registro');

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
                        nonce: manualImportConfig.nonce,
                        product: JSON.stringify(product),
                        download_image: downloadImages ? 1 : 0
                    },
                    success: function (response) {
                        imported++;
                        const statusSpan = $('.etiqueta-estado[data-index="' + index + '"]');

                        if (response.success) {
                            statusSpan.html('<span class="etiqueta etiquetaExito">Importado</span>');
                            logContent.append('<div class="itemRegistro registroExito">OK ' + escapeHtml(product.title) + '</div>');
                        } else {
                            statusSpan.html('<span class="etiqueta etiquetaError">Error</span>');
                            logContent.append('<div class="itemRegistro registroError">X ' + escapeHtml(product.title) + ': ' + (response.data || 'Error desconocido') + '</div>');
                        }

                        if (imported === total) {
                            logContent.append('<div class="itemRegistro" style="font-weight:bold;margin-top:10px;">Completado: ' + imported + ' productos procesados</div>');
                        }
                    },
                    error: function () {
                        imported++;
                        $('.etiqueta-estado[data-index="' + index + '"]').html('<span class="etiqueta etiquetaError">Error</span>');
                    }
                });
            }, i * 500);
        });
    }
})(jQuery);
