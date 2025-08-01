jQuery(document).ready(function ($) {
    'use strict';

    function gestionarPestanas() {
        var navContainer = $('.glory-panel-nav');
        var contentContainer = $('.glory-panel-content');

        navContainer.on('click', '.nav-tab', function (e) {
            e.preventDefault();

            var targetId = $(this).attr('href');

            navContainer.find('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            contentContainer.find('.glory-panel-tab').removeClass('active');
            $(targetId).addClass('active');

            if (window.localStorage) {
                localStorage.setItem('gloryOpcionesPestanaActiva', targetId);
            }
        });

        if (window.localStorage) {
            var pestanaActiva = localStorage.getItem('gloryOpcionesPestanaActiva');
            if (pestanaActiva && $(pestanaActiva).length) {
                navContainer.find('a[href="' + pestanaActiva + '"]').click();
            } else {
                navContainer.find('.nav-tab').first().click();
            }
        } else {
            navContainer.find('.nav-tab').first().click();
        }
    }

    function inicializarColorPicker() {
        $('.glory-color-picker').wpColorPicker();
    }

    function inicializarImageUploader() {
        var mediaUploader;

        $('body').on('click', '.glory-upload-image-button', function (e) {
            e.preventDefault();
            var button = $(this);
            var uploaderContainer = button.closest('.glory-image-uploader');

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Seleccionar una Imagen',
                button: {
                    text: 'Usar esta imagen'
                },
                multiple: false
            });

            mediaUploader.on('select', function () {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                var previewUrl = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

                uploaderContainer.find('.glory-image-id').val(attachment.id);
                uploaderContainer.find('.image-preview').html('<img src="' + previewUrl + '" alt="Previsualización">');
                uploaderContainer.find('.glory-remove-image-button').show();
            });

            mediaUploader.open();
        });

        $('body').on('click', '.glory-remove-image-button', function (e) {
            e.preventDefault();
            var button = $(this);
            var uploaderContainer = button.closest('.glory-image-uploader');

            uploaderContainer.find('.glory-image-id').val('');
            var placeholderText = uploaderContainer.find('.image-preview').data('placeholder');
            uploaderContainer.find('.image-preview').html('<span class="image-preview-placeholder">' + placeholderText + '</span>');
            button.hide();
        });
    }

    // --- INICIO: CÓDIGO AÑADIDO ---
    function gestionarCamposCondicionales() {
        const gloryPanel = $('.glory-options-panel');

        function actualizarVisibilidad() {
            gloryPanel.find('.glory-conditional-field').each(function () {
                const campo = $(this);
                const campoControladorNombre = campo.data('condition-field');
                const valorRequerido = campo.data('condition-value');

                const controlador = gloryPanel.find(`[name="${campoControladorNombre}"]`);
                let valorActual;

                if (controlador.is(':radio')) {
                    valorActual = gloryPanel.find(`[name="${campoControladorNombre}"]:checked`).val();
                } else { // Handles select, text, etc.
                    valorActual = controlador.val();
                }

                if (valorActual === valorRequerido) {
                    campo.show();
                } else {
                    campo.hide();
                }
            });
        }

        actualizarVisibilidad();

        gloryPanel.on('change', 'select, input[type="radio"]', function() {
            const nombreControlador = $(this).attr('name');
            if (gloryPanel.find(`.glory-conditional-field[data-condition-field="${nombreControlador}"]`).length > 0) {
                 actualizarVisibilidad();
            }
        });
    }
    // --- FIN: CÓDIGO AÑADIDO ---

    gestionarPestanas();
    inicializarColorPicker();
    inicializarImageUploader();
    gestionarCamposCondicionales(); // Llamada a la nueva función
});