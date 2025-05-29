jQuery(document).ready(function($){
    // From get_image_uploader_js()
    console.log('Glory Admin Panel JS loaded');
    $(document).on('click', '.glory-upload-image-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var galleryItem = button.closest('.glory-gallery-item');
        var inputField = galleryItem.find('.glory-image-url-field');
        var imagePreviewContainer = galleryItem.find('.glory-image-preview');
        
        if (!inputField.length) {
            // console.error('Glory Uploader: Could not find inputField.');
            // return; 
        }
        if (!imagePreviewContainer.length) {
            // console.error('Glory Uploader: Could not find imagePreviewContainer.');
            // return; 
        }

        var frame = wp.media({
            title: gloryAdminPanelSettings.i18n.selectOrUploadImage, // PHP esc_js(__('Select or Upload Image', 'glory'))
            button: { text: gloryAdminPanelSettings.i18n.useThisImage }, // PHP esc_js(__('Use this image', 'glory'))
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            inputField.val(attachment.url);
            
            var newImg = $('<img>');
            newImg.attr('src', attachment.url);
            imagePreviewContainer.empty().append(newImg); 
        });
        frame.open();
    });

    $(document).on('click', '.glory-remove-image-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var galleryItem = button.closest('.glory-gallery-item');
        var inputField = galleryItem.find('.glory-image-url-field');
        var imagePreviewContainer = galleryItem.find('.glory-image-preview');

        if (!inputField.length || !imagePreviewContainer.length) {
            // console.error('Glory Remover: Could not find inputField or imagePreviewContainer.');
            // return; 
        }

        inputField.val('');
        imagePreviewContainer.html(''); 
    });

    // From get_tabs_js()
    var gloryTabs = $('.glory-tabs-nav-container .nav-tab');
    var gloryTabContents = $('.glory-tab-content');
    // var menuSlug = 'glory-content-manager'; // Removed, to be replaced by gloryAdminPanelSettings.menuSlug

    function activateTab(tabLink) {
        var tabId = $(tabLink).attr('href');
        gloryTabs.removeClass('nav-tab-active');
        $(tabLink).addClass('nav-tab-active');
        gloryTabContents.removeClass('active').hide();
        $(tabId).addClass('active').show();
        if (history.pushState) {
            var newUrl = window.location.protocol + '//' + window.location.host + window.location.pathname + '?page=' + gloryAdminPanelSettings.menuSlug + '&tab=' + tabId.substring(5); // PHP self::$menu_slug
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
        if (!activatedFromQuery && gloryTabs.first().length) activateTab(gloryTabs.first());
    }

    gloryTabs.on('click', function(e) {
        e.preventDefault();
        activateTab(this);
        window.location.hash = $(this).attr('href').substring(1);
    });

    // From inline script in render_schedule_input_control()
    $(document).on('change', '.glory-schedule-editor select', function() {
        var row = $(this).closest('.glory-schedule-day-row');
        var isOpen = $(this).val() === 'open';
        row.find('input[type="time"]').prop('disabled', !isOpen);
        if (!isOpen) {
           // row.find('input[type="time"]').val(''); // Optional: clear times if closed
        }
    });
    // Trigger change on load to apply initial state for schedule editor
    // This was commented out in PHP, keeping it commented.
    // $('.glory-schedule-editor select').trigger('change');
});
