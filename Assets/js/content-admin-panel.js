jQuery(document).ready(function ($) {
    // From get_image_uploader_js()
    console.log('Glory Admin Panel JS loaded');

    // Inicializar CodeMirror para campos JSON
    if (typeof wp !== 'undefined' && wp.codeEditor && typeof gloryAdminPanelSettings !== 'undefined' && gloryAdminPanelSettings.codeEditorSettings) {
        $('.glory-json-editor-area').each(function () {
            var $textarea = $(this);
            var editorInstance = wp.codeEditor.initialize($textarea.attr('id'), gloryAdminPanelSettings.codeEditorSettings);
            
            // Guardar la instancia de CodeMirror en el elemento textarea
            // para poder acceder a ella más tarde y refrescarla si es necesario.
            if (editorInstance && editorInstance.codemirror) {
                $textarea.data('CodeMirrorInstance', editorInstance.codemirror);
            }
        });
    } else {
        console.warn('Glory Admin Panel: wp.codeEditor or codeEditorSettings not available for JSON fields.');
    }

    $(document).on('click', '.glory-upload-image-button', function (e) {
        e.preventDefault();
        var button = $(this);
        // Modificado para ser más general, no solo para .glory-gallery-item
        var parentContainer = button.closest('td, .glory-gallery-item'); 
        var inputField = parentContainer.find('.glory-image-url-field');
        var imagePreviewContainer = parentContainer.find('.glory-image-preview');

        if (!inputField.length) {
             console.error('Glory Uploader: Could not find inputField.');
             return;
        }
        if (!imagePreviewContainer.length) {
             console.error('Glory Uploader: Could not find imagePreviewContainer.');
             // return; // Podría no ser crítico si solo queremos setear el input
        }

        var frame = wp.media({
            title: gloryAdminPanelSettings.i18n.selectOrUploadImage,
            button: {text: gloryAdminPanelSettings.i18n.useThisImage},
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            inputField.val(attachment.url);

            var newImg = $('<img>');
            newImg.attr('src', attachment.url);
            if (imagePreviewContainer.length) {
                imagePreviewContainer.empty().append(newImg);
            }
        });
        frame.open();
    });

    $(document).on('click', '.glory-remove-image-button', function (e) {
        e.preventDefault();
        var button = $(this);
        // Modificado para ser más general
        var parentContainer = button.closest('td, .glory-gallery-item');
        var inputField = parentContainer.find('.glory-image-url-field');
        var imagePreviewContainer = parentContainer.find('.glory-image-preview');

        if (!inputField.length) {
            console.error('Glory Remover: Could not find inputField.');
            return;
        }

        inputField.val('');
        if (imagePreviewContainer.length) {
            imagePreviewContainer.html('');
        }
    });

    // From get_tabs_js()
    var gloryTabs = $('.glory-tabs-nav-container .nav-tab');
    var gloryTabContents = $('.glory-tab-content');

    function activateTab(tabLink) {
        var tabId = $(tabLink).attr('href'); // e.g., "#tab-general"
        var $tabContent = $(tabId);

        gloryTabs.removeClass('nav-tab-active');
        $(tabLink).addClass('nav-tab-active');
        
        gloryTabContents.removeClass('active').hide();
        $tabContent.addClass('active').show();

        // Refrescar instancias de CodeMirror dentro de la pestaña recién activada
        $tabContent.find('.glory-json-editor-area').each(function() {
            var cmInstance = $(this).data('CodeMirrorInstance');
            if (cmInstance) {
                cmInstance.refresh();
            }
        });
        
        // Refrescar editores TinyMCE si es necesario (a veces tienen problemas al mostrarse desde un display:none)
        // $tabContent.find('.wp-editor-area').each(function(){
        //    var editorId = $(this).attr('id');
        //    if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
        //        tinymce.get(editorId).show(); // O alguna otra forma de forzar un repaint/refresh si es necesario
        //    }
        // });


        if (history.pushState) {
            var newUrl = window.location.protocol + '//' + window.location.host + window.location.pathname + '?page=' + gloryAdminPanelSettings.menuSlug + '&tab=' + tabId.substring(5);
            history.pushState({path: newUrl}, '', newUrl);
        }
    }

    var initialTab = window.location.hash;
    if (initialTab && $(initialTab).length) {
        var correspondingLink = $('.glory-tabs-nav-container .nav-tab[href="' + initialTab + '"]');
        if (correspondingLink.length) activateTab(correspondingLink);
    } else if (gloryTabs.length > 0) {
        const urlParams = new URLSearchParams(window.location.search);
        const queryTab = urlParams.get('tab');
        let activatedFromQuery = false;
        if (queryTab) {
            var targetTabLink = $('.glory-tabs-nav-container .nav-tab[data-tab-id="' + queryTab + '"]');
            if (targetTabLink.length) {
                activateTab(targetTabLink);
                activatedFromQuery = true;
            }
        }
        if (!activatedFromQuery && gloryTabs.first().length) {
            activateTab(gloryTabs.first());
        }
    }

    gloryTabs.on('click', function (e) {
        e.preventDefault();
        activateTab(this);
        // La URL hash se actualiza visualmente por el navegador debido al href,
        // pero si se quiere forzar o asegurar:
        // window.location.hash = $(this).attr('href');
    });

    // From inline script in render_schedule_input_control()
    $(document).on('change', '.glory-schedule-editor select', function () {
        var row = $(this).closest('.glory-schedule-day-row');
        var isOpen = $(this).val() === 'open';
        row.find('input[type="time"]').prop('disabled', !isOpen);
        if (!isOpen) {
            // row.find('input[type="time"]').val(''); // Optional: clear times if closed
        }
    });
    // Trigger change on load to apply initial state for schedule editor
    // $('.glory-schedule-editor select').trigger('change'); // Descomentar si se quiere ejecutar al cargar la página
});