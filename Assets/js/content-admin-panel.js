jQuery(document).ready(function ($) {
    // From get_image_uploader_js()
    console.log('Glory Admin Panel JS loaded');
    $(document).on('click', '.glory-upload-image-button', function (e) {
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
            button: {text: gloryAdminPanelSettings.i18n.useThisImage}, // PHP esc_js(__('Use this image', 'glory'))
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            inputField.val(attachment.url);

            var newImg = $('<img>');
            newImg.attr('src', attachment.url);
            imagePreviewContainer.empty().append(newImg);
        });
        frame.open();
    });

    $(document).on('click', '.glory-remove-image-button', function (e) {
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

    gloryTabs.on('click', function (e) {
        e.preventDefault();
        activateTab(this);
        window.location.hash = $(this).attr('href').substring(1);
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
    // This was commented out in PHP, keeping it commented.
    // $('.glory-schedule-editor select').trigger('change');
});

// ====== MENU STRUCTURE EDITOR ======
jQuery(document).ready(function ($) {
    if ($('.glory-menu-structure-admin').length) {
        // Solo ejecutar si el editor de menú está presente

        // --- Gestión de Items de Menú (Secciones Standard) ---

        // Añadir Item a una Sección Standard
        $(document).on('click', '.glory-add-menu-item', function () {
            var $button = $(this);
            var $sectionAdminDiv = $button.closest('.inside'); // Contenedor de la sección
            var $itemsList = $sectionAdminDiv.find('.glory-menu-items-list');
            var sectionId = $button.data('section-id'); // Necesitamos el ID de la sección para construir el nombre del input

            // Determinar el siguiente índice para el nuevo ítem
            var newItemIndex = $itemsList.find('.glory-menu-item').length;

            // Nombre base para los inputs del nuevo item.
            // Ej: glory_content[restaurant_main_menu][sections][46580][items]
            // Obtenemos el 'name' del primer input de título de sección y lo adaptamos
            var baseInputNameForSection = $sectionAdminDiv.find('input[name*="[title]"]').attr('name');
            if (!baseInputNameForSection) {
                console.error('Could not determine base input name for section items.');
                return;
            }
            // Reemplazar '[title]' por '[items]'
            var baseInputNameForItems = baseInputNameForSection.replace(/\[title\]$/, '[items]');

            // Plantilla HTML para un nuevo ítem (simplificada)
            // En una implementación más robusta, esto podría ser una plantilla <template> o generada con más cuidado
            var newItemHtml = `
            <div class="glory-menu-item" data-item-index="${newItemIndex}">
                <button type="button" class="button button-small glory-remove-menu-item">X</button>
                <p>
                    <label><?php _e('Item Name:', 'glory'); ?></label><br>
                    <input type="text" name="${baseInputNameForItems}[${newItemIndex}][name]" value="" class="large-text">
                </p>
                <p>
                    <label><?php _e('Item Price:', 'glory'); ?></label><br>
                    <input type="text" name="${baseInputNameForItems}[${newItemIndex}][price]" value="" class="regular-text">
                </p>
                <p>
                    <label><?php _e('Item Description (optional):', 'glory'); ?></label><br>
                    <textarea name="${baseInputNameForItems}[${newItemIndex}][description]" rows="2" class="large-text"></textarea>
                </p>
                <hr>
            </div>
        `;
            // NOTA: Los `<?php _e(...) ?>` no funcionarán directamente en JS.
            // Deberías pasar estas traducciones vía wp_localize_script si quieres que sean dinámicas.
            // Por ahora, para que funcione, reemplaza `<?php _e('Item Name:', 'glory'); ?>` por "Item Name:" directamente.
            // O mejor aún, usa los i18n que ya tienes en gloryAdminPanelSettings
            // Ejemplo: gloryAdminPanelSettings.i18n.itemNameLabel (tendrías que añadirlo en PHP)

            // ---- REEMPLAZO TEMPORAL PARA TRADUCCIONES EN LA PLANTILLA JS ----
            newItemHtml = newItemHtml.replace("<?php _e('Item Name:', 'glory'); ?>", gloryAdminPanelSettings.i18n.itemNameLabel || 'Item Name:');
            newItemHtml = newItemHtml.replace("<?php _e('Item Price:', 'glory'); ?>", gloryAdminPanelSettings.i18n.itemPriceLabel || 'Item Price:');
            newItemHtml = newItemHtml.replace("<?php _e('Item Description (optional):', 'glory'); ?>", gloryAdminPanelSettings.i18n.itemDescriptionLabel || 'Item Description (optional):');
            // ---- FIN REEMPLAZO TEMPORAL ----

            $itemsList.append(newItemHtml);
        });

        // Eliminar Item de una Sección Standard
        $(document).on('click', '.glory-remove-menu-item', function () {
            var $button = $(this);
            var $itemDiv = $button.closest('.glory-menu-item');
            var $itemsList = $itemDiv.parent();

            $itemDiv.remove();

            // Opcional: Re-indexar los items restantes para evitar huecos en los índices del array POST
            // Esto es importante si tu PHP de guardado espera índices consecutivos (0, 1, 2...).
            $itemsList.find('.glory-menu-item').each(function (newIndex, itemElement) {
                var $currentItem = $(itemElement);
                $currentItem.attr('data-item-index', newIndex); // Actualizar el data attribute
                $currentItem.find('input, textarea').each(function () {
                    var currentName = $(this).attr('name');
                    if (currentName) {
                        // Reemplazar el viejo índice (ej: ...[items][2][name]) por el nuevo (ej: ...[items][0][name])
                        var newName = currentName.replace(/\[items\]\[\d+\]/, '[items][' + newIndex + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        });

        // --- Gestión de Secciones del Menú ---
        $(document).on('click', '.glory-add-menu-section', function () {
            var $sectionsEditor = $('.glory-menu-sections-editor');
            var newSectionIndex = $sectionsEditor.find('.glory-menu-section').length; // Para un ID temporal
            var newSectionId = 'new_section_' + Date.now(); // ID único temporal para la nueva sección

            // Nombre base para los inputs de la nueva sección.
            // Ej: glory_content[restaurant_main_menu][sections][ID_SECCION_NUEVA]
            // Tomamos el nombre de un input de la primera pestaña como referencia para la clave principal del menú.
            var anyTabInput = $('.glory-menu-tabs-editor').find('input[name*="[tabs]"]').first();
            if (!anyTabInput.length) {
                console.error('Cannot determine base name for new section. No tabs found.');
                // Fallback si no hay pestañas: intentar obtener el nombre base del contenedor.
                // Esto es menos fiable, idealmente el nombre del campo principal del menú está disponible.
                var menuKeyFromForm = $('form[action*="page=glory-content-manager"]').find('input[name^="glory_content["][name*="[_json_fallback]"]').attr('name');
                if (menuKeyFromForm) {
                    baseInputNameForMenu = menuKeyFromForm.match(/^(glory_content\[[^\]]+\])/)[0];
                } else {
                    console.error('Could not determine base input name for menu for new section.');
                    return;
                }
            } else {
                var baseInputNameForMenu = anyTabInput.attr('name').match(/^(glory_content\[[^\]]+\])/)[0];
            }

            var baseNameForNewSection = `${baseInputNameForMenu}[sections][${newSectionId}]`;

            // Plantilla HTML para una nueva sección
            // De nuevo, las traducciones necesitarían wp_localize_script
            var newSectionHtml = `
            <div class="glory-menu-section postbox">
                <h3 class="hndle"><span>${gloryAdminPanelSettings.i18n.newSectionLabel || 'New Section'} (ID: ${newSectionId})</span></h3>
                <div class="inside">
                    <input type="hidden" name="${baseNameForNewSection}[id_placeholder]" value="${newSectionId}">
                    <p>
                        <label>${gloryAdminPanelSettings.i18n.sectionTitleLabel || 'Section Title:'}</label><br>
                        <input type="text" name="${baseNameForNewSection}[title]" value="" class="large-text">
                    </p>
                    <p>
                        <label>${gloryAdminPanelSettings.i18n.sectionDescriptionLabel || 'Section Description (optional):'}</label><br>
                        <textarea name="${baseNameForNewSection}[description]" rows="3" class="large-text"></textarea>
                    </p>
                    <p>
                        <label>${gloryAdminPanelSettings.i18n.sectionTypeLabel || 'Section Type:'}</label><br>
                        <select name="${baseNameForNewSection}[type]">
                            <option value="standard" selected>${gloryAdminPanelSettings.i18n.sectionTypeStandard || 'Standard Items'}</option>
                            <option value="multi_price">${gloryAdminPanelSettings.i18n.sectionTypeMultiPrice || 'Multi-Price Items'}</option>
                            <option value="menu_pack">${gloryAdminPanelSettings.i18n.sectionTypeMenuPack || 'Menu Packs'}</option>
                        </select>
                    </p>
                    
                    <!-- Contenedor para items (inicialmente vacío para 'standard') -->
                    <div class="glory-section-type-content">
                        <div class="glory-menu-items-list"></div>
                        <button type="button" class="button glory-add-menu-item" data-section-id="${newSectionId}">${gloryAdminPanelSettings.i18n.addItemToSectionLabel || 'Add Item to this Section'}</button>
                    </div>

                    <p><button type="button" class="button button-link-delete glory-remove-menu-section">${gloryAdminPanelSettings.i18n.removeSectionLabel || 'Remove this Section'}</button></p>
                </div>
            </div>
        `;
            $sectionsEditor.append(newSectionHtml);
            // Disparar el 'change' en el nuevo select de tipo por si queremos lógica asociada
            $sectionsEditor.find('.glory-menu-section').last().find('select[name*="[type]"]').trigger('change');
        });

        // Eliminar Sección
        $(document).on('click', '.glory-remove-menu-section', function () {
            if (confirm(gloryAdminPanelSettings.i18n.confirmRemoveSection || 'Are you sure you want to remove this entire section?')) {
                $(this).closest('.glory-menu-section').remove();
                // No es estrictamente necesario re-indexar secciones porque el ID de la sección es la clave en el array PHP.
            }
        });

        // --- Gestión de Pestañas (Tabs) ---
        $(document).on('click', '.glory-add-menu-tab', function () {
            var $tabsEditor = $('.glory-menu-tabs-editor');
            var newTabIndex = $tabsEditor.find('.glory-menu-tab-item').length;

            // Obtenemos el nombre base del primer input de pestaña existente
            var baseTabNameRef = $tabsEditor.find('input[name*="[tabs]"]').first().attr('name');
            if (!baseTabNameRef) {
                // Si no hay pestañas, necesitamos construirlo desde cero
                var menuKeyFromForm = $('form[action*="page=glory-content-manager"]').find('input[name^="glory_content["][name*="[_json_fallback]"]').attr('name');
                if (menuKeyFromForm) {
                    baseInputNameForMenu = menuKeyFromForm.match(/^(glory_content\[[^\]]+\])/)[0];
                } else {
                    console.error('Could not determine base input name for menu for new tab.');
                    return;
                }
                baseTabNameRef = `${baseInputNameForMenu}[tabs][0][id]`; // Placeholder para el regex
            }

            var baseNameForNewTab = baseTabNameRef.replace(/\[tabs\]\[\d+\]/, `[tabs][${newTabIndex}]`);
            // Quitar el [id], [text] final si existe para tener el nombre base del tab
            baseNameForNewTab = baseNameForNewTab.replace(/\[(id|text|visible_in_tabs)\]$/, '');

            var newTabHtml = `
            <div class="glory-menu-tab-item">
                <p>
                    ID: <input type="text" name="${baseNameForNewTab}[id]" value="" placeholder="Ej: 46580_new">
                    Texto: <input type="text" name="${baseNameForNewTab}[text]" value="" placeholder="Ej: Nueva Pestaña">
                    <label>
                        <input type="checkbox" name="${baseNameForNewTab}[visible_in_tabs]" value="1" checked>
                        ${gloryAdminPanelSettings.i18n.visibleInTabBarLabel || 'Visible in main tab bar'}
                    </label>
                    <button type="button" class="button button-small glory-remove-menu-tab">X</button>
                </p>
            </div>
        `;
            $tabsEditor.find('.glory-add-menu-tab').before(newTabHtml); // Añadir antes del botón "Add Tab"
        });

        // Eliminar Pestaña
        $(document).on('click', '.glory-remove-menu-tab', function () {
            $(this).closest('.glory-menu-tab-item').remove();
            // Re-indexar pestañas
            $('.glory-menu-tabs-editor .glory-menu-tab-item').each(function (newIndex, tabElement) {
                $(tabElement)
                    .find('input')
                    .each(function () {
                        var currentName = $(this).attr('name');
                        if (currentName) {
                            var newName = currentName.replace(/\[tabs\]\[\d+\]/, `[tabs][${newIndex}]`);
                            $(this).attr('name', newName);
                        }
                    });
            });
        });

        // Cambiar contenido visible según el tipo de sección seleccionado
        $(document).on('change', '.glory-menu-section select[name*="[type]"]', function () {
            var $select = $(this);
            var selectedType = $select.val();
            var $sectionInside = $select.closest('.inside');
            var $typeSpecificContentContainer = $sectionInside.find('.glory-section-type-content'); // Asumimos que tienes este div

            // Ocultar todos los contenidos específicos primero (si tuvieras más)
            $typeSpecificContentContainer.find('> div, > button, > p').hide(); // Oculta items list, add item button, y placeholders

            if (selectedType === 'standard') {
                $typeSpecificContentContainer.find('.glory-menu-items-list').show();
                $typeSpecificContentContainer.find('.glory-add-menu-item').show();
            } else if (selectedType === 'multi_price') {
                // Mostrar placeholder o futura UI para multi_price
                // Por ahora, crearemos un placeholder si no existe
                if (!$typeSpecificContentContainer.find('.multi-price-placeholder').length) {
                    $typeSpecificContentContainer.append('<p class="multi-price-placeholder"><em>' + (gloryAdminPanelSettings.i18n.multiPriceUIPlaceholder || 'Multi-price item editor UI will be implemented later.') + '</em></p>');
                }
                $typeSpecificContentContainer.find('.multi-price-placeholder').show();
            } else if (selectedType === 'menu_pack') {
                // Mostrar placeholder o futura UI para menu_pack
                if (!$typeSpecificContentContainer.find('.menu-pack-placeholder').length) {
                    $typeSpecificContentContainer.append('<p class="menu-pack-placeholder"><em>' + (gloryAdminPanelSettings.i18n.menuPackUIPlaceholder || 'Menu pack editor UI will be implemented later.') + '</em></p>');
                }
                $typeSpecificContentContainer.find('.menu-pack-placeholder').show();
            }
        });
        // Disparar el evento change en todos los select de tipo de sección al cargar la página
        // para asegurar que la UI correcta se muestre inicialmente.
        $('.glory-menu-structure-admin .glory-menu-section select[name*="[type]"]').trigger('change');
    } // Fin de if ($('.glory-menu-structure-admin').length)
    // ====== FIN MENU STRUCTURE EDITOR ======
});
