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

        function abrirMedia(uploaderContainer) {
            if (typeof wp === 'undefined' || !wp.media) {
                console.error('wp.media no está disponible en este contexto');
                return;
            }

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Seleccionar una Imagen',
                button: { text: 'Usar esta imagen' },
                multiple: false
            });

            mediaUploader.on('select', function () {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                var previewUrl = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;

                // Guardar ID
                uploaderContainer.find('.glory-image-id').val(attachment.id);

                // Actualizar preview (compatibilidad con ambas estructuras)
                var $previewModern = uploaderContainer.find('.previewImagen');
                if ($previewModern.length) {
                    $previewModern.html('<img src="' + previewUrl + '" alt="Previsualización">');
                    uploaderContainer.find('.previewRemover').removeClass('oculto').show();
                    $previewModern.find('.image-preview-placeholder').addClass('oculto');
                }

                var $previewLegacy = uploaderContainer.find('.image-preview');
                if ($previewLegacy.length) {
                    $previewLegacy.html('<img src="' + previewUrl + '" alt="Previsualización">');
                    uploaderContainer.find('.glory-remove-image-button').show();
                }
            });

            mediaUploader.open();
        }

        // Disparadores para abrir el selector: botón legacy y clic en la preview moderna
        $('body')
            .on('click', '.glory-upload-image-button', function (e) {
                e.preventDefault();
                abrirMedia($(this).closest('.glory-image-uploader'));
            })
            .on('click', '.glory-image-uploader .previewImagen', function (e) {
                e.preventDefault();
                abrirMedia($(this).closest('.glory-image-uploader'));
            })
            .on('click', '.glory-image-uploader .image-preview', function (e) {
                e.preventDefault();
                abrirMedia($(this).closest('.glory-image-uploader'));
            });

        // Eliminar imagen: compatibilidad moderna y legacy
        $('body')
            .on('click', '.glory-remove-image-button', function (e) {
                e.preventDefault();
                var uploaderContainer = $(this).closest('.glory-image-uploader');
                uploaderContainer.find('.glory-image-id').val('');
                var $preview = uploaderContainer.find('.image-preview');
                var placeholderText = $preview.data('placeholder') || 'Haz clic para subir una imagen';
                $preview.html('<span class="image-preview-placeholder">' + placeholderText + '</span>');
                $(this).hide();
            })
            .on('click', '.glory-image-uploader .previewRemover', function (e) {
                e.preventDefault();
                var uploaderContainer = $(this).closest('.glory-image-uploader');
                uploaderContainer.find('.glory-image-id').val('');
                var $preview = uploaderContainer.find('.previewImagen');
                var placeholderText = $preview.data('placeholder') || 'Haz clic para subir una imagen';
                $preview.html('<span class="image-preview-placeholder">' + placeholderText + '</span>');
                $(this).addClass('oculto').hide();
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