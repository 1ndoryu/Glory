/**
 * Sections Tab - Interactividad para gestion de secciones dinamicas
 * Plugin Amazon Products - Glory Theme
 */
(function ($) {
    'use strict';

    const SectionsTab = {
        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            $(document).on('click', '[data-toggle-section]', this.toggleSection);
            $(document).on('submit', '.seccionFormulario', this.saveSection);
            $(document).on('click', '.seccionBotonRestaurar', this.restoreSection);
            $(document).on('click', '.seccionBotonIncluir', this.includeProduct);
            $(document).on('click', '.seccionBotonPreview', this.previewSection);
            $(document).on('click', '.seccionPreviewCerrar', this.closePreview);
            $(document).on('click', '.seccionPreviewModal', this.closePreviewOnBackdrop);
        },

        toggleSection: function () {
            const slug = $(this).data('toggle-section');
            const $content = $('#seccion-' + slug);
            const $card = $(this).closest('.seccionCard');

            if ($content.is(':visible')) {
                $content.slideUp(200);
                $card.removeClass('seccionAbierta');
            } else {
                $content.slideDown(200);
                $card.addClass('seccionAbierta');
            }
        },

        saveSection: function (e) {
            e.preventDefault();

            const $form = $(this);
            const $button = $form.find('.seccionBotonGuardar');
            const $message = $form.find('.seccionMensaje');
            const slug = $form.data('section');

            $button.prop('disabled', true).text(glorySections.strings.saving);

            const formData = new FormData(this);
            formData.append('action', 'glory_save_section');
            formData.append('nonce', glorySections.nonce);

            $.ajax({
                url: glorySections.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        SectionsTab.showMessage($message, response.data.message, 'exito');

                        const $card = $form.closest('.seccionCard');
                        const $counter = $card.find('.seccionConteoProductos');
                        $counter.text(response.data.productCount + ' productos');

                        if (response.data.hasModifications) {
                            $card.addClass('seccionModificada');
                        } else {
                            $card.removeClass('seccionModificada');
                        }
                    } else {
                        SectionsTab.showMessage($message, response.data.message, 'error');
                    }
                },
                error: function () {
                    SectionsTab.showMessage($message, glorySections.strings.error, 'error');
                },
                complete: function () {
                    $button.prop('disabled', false).text('Guardar cambios');
                }
            });
        },

        restoreSection: function () {
            const slug = $(this).data('section');

            if (!confirm(glorySections.strings.confirmRestore)) {
                return;
            }

            const $button = $(this);
            $button.prop('disabled', true);

            $.ajax({
                url: glorySections.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'glory_restore_section',
                    nonce: glorySections.nonce,
                    section_slug: slug
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false);
                    }
                },
                error: function () {
                    alert(glorySections.strings.error);
                    $button.prop('disabled', false);
                }
            });
        },

        includeProduct: function () {
            const $button = $(this);
            const slug = $button.data('section');
            const productId = $button.data('product-id');

            $button.prop('disabled', true);

            $.ajax({
                url: glorySections.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'glory_include_product',
                    nonce: glorySections.nonce,
                    section_slug: slug,
                    product_id: productId
                },
                success: function (response) {
                    if (response.success) {
                        $button.closest('.seccionExcluidoItem').fadeOut(200, function () {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false);
                    }
                },
                error: function () {
                    alert(glorySections.strings.error);
                    $button.prop('disabled', false);
                }
            });
        },

        previewSection: function () {
            const slug = $(this).data('section');
            const $button = $(this);

            $button.prop('disabled', true).text(glorySections.strings.loading);

            $.ajax({
                url: glorySections.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'glory_preview_section',
                    nonce: glorySections.nonce,
                    section_slug: slug
                },
                success: function (response) {
                    if (response.success) {
                        SectionsTab.showPreviewModal(slug, response.data);
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert(glorySections.strings.error);
                },
                complete: function () {
                    $button.prop('disabled', false).text('Previsualizar');
                }
            });
        },

        showPreviewModal: function (slug, data) {
            let productsHtml = '';

            data.products.forEach(function (product) {
                productsHtml += `
                    <div class="seccionPreviewProducto">
                        <img src="${product.image || ''}" alt="">
                        <div class="titulo">${product.title}</div>
                        <div class="precio">${product.price ? product.price + ' €' : ''}</div>
                    </div>
                `;
            });

            const showing = data.showing || data.products.length;
            const total = data.total || showing;

            const modalHtml = `
                <div class="seccionPreviewModal">
                    <div class="seccionPreviewContenido">
                        <div class="seccionPreviewCabecera">
                            <h4>Preview: ${slug}</h4>
                            <span class="seccionPreviewConteo">Mostrando ${showing} de ${total} productos</span>
                            <button type="button" class="seccionPreviewCerrar">&times;</button>
                        </div>
                        <div class="seccionPreviewGrid">
                            ${productsHtml || '<p>No hay productos que mostrar</p>'}
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
        },

        closePreview: function () {
            $('.seccionPreviewModal').remove();
        },

        closePreviewOnBackdrop: function (e) {
            if ($(e.target).hasClass('seccionPreviewModal')) {
                SectionsTab.closePreview();
            }
        },

        showMessage: function ($element, message, type) {
            $element.removeClass('exito error').addClass(type).text(message).show();

            setTimeout(function () {
                $element.fadeOut(300);
            }, 3000);
        },

        scanSections: function () {
            const $button = $(this);
            const originalText = $button.text();

            $button.prop('disabled', true).text('Escaneando...');

            $.ajax({
                url: glorySections.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'glory_scan_sections',
                    nonce: glorySections.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert(glorySections.strings.error);
                },
                complete: function () {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
    };

    $(document).ready(function () {
        SectionsTab.init();
    });
})(jQuery);
