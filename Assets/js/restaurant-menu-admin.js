jQuery(document).ready(function ($) {
    //console.log('Restaurant Menu Admin JS: Document ready. Checking conditions...');
    // Verificar si estamos en la página correcta y si hay editores de menú
    if (!$('.glory-menu-structure-admin').length) {
        //console.log('Restaurant Menu Admin JS: Conditions NOT met. Admin structure present:', $('.glory-menu-structure-admin').length > 0, '. Exiting.');
        return;
    }
    //console.log('Restaurant Menu Admin JS Loaded and conditions met.');

    // === UTILITIES ===
    /**
     * Genera un ID único simple.
     * @returns {string}
     */
    function generarIdUnico() {
        // //console.log('generarIdUnico called'); // Descomentar si se necesita un seguimiento muy detallado
        var id = 'gloryid_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        // //console.log('generarIdUnico generated:', id); // Descomentar si se necesita un seguimiento muy detallado
        return id;
    }

    /**
     * Reindexa los atributos 'name' y 'data-*-index' de los elementos dentro de un contenedor.
     * @param {jQuery} $container El contenedor de los elementos a reindexar.
     * @param {string} itemSelector Selector para los items individuales.
     * @param {string} namePatternRegex Regex para capturar el prefijo y el sufijo del atributo 'name'.
     * @param {string} dataAttributeForIndex (Opcional) El nombre del atributo data que almacena el índice. Ej: 'data-item-index'.
     */
    function reindexarElementos($container, itemSelector, namePatternRegex, dataAttributeForIndex) {
        //console.log('reindexarElementos called. Container:', $container.prop('tagName') + '.' + $container.attr('class'), 'ItemSelector:', itemSelector, 'Regex:', namePatternRegex, 'DataAttr:', dataAttributeForIndex);
        $container.find(itemSelector).each(function (newIndex, element) {
            var $element = $(element);
            // //console.log('  Re-indexing element, newIndex:', newIndex, 'Element:', $element.prop('tagName') + '.' + $element.attr('class'));
            if (dataAttributeForIndex) {
                $element.attr(dataAttributeForIndex, newIndex);
                // //console.log('    Set', dataAttributeForIndex, 'to', newIndex);
            }
            $element.find('input, textarea, select').each(function () {
                var currentName = $(this).attr('name');
                if (currentName) {
                    var newName = currentName.replace(namePatternRegex, '$1' + newIndex + '$2');
                    if (currentName !== newName) {
                        // //console.log('    Old name:', currentName, 'New name:', newName);
                        $(this).attr('name', newName);
                    }
                }
            });
        });
        //console.log('reindexarElementos finished for container:', $container.prop('tagName') + '.' + $container.attr('class'));
    }

    /**
     * Ajusta la altura de los textareas automáticamente.
     */
    function inicializarTextareaAutosize() {
        //console.log('inicializarTextareaAutosize called');
        $(document).on('input', 'textarea.glory-textarea-autosize', function () {
            // //console.log('Textarea autosize input event for:', $(this).attr('name')); // Puede ser muy verboso
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
        $('textarea.glory-textarea-autosize').each(function () {
            $(this).trigger('input');
        });
        //console.log('inicializarTextareaAutosize finished initial trigger.');
    }
    inicializarTextareaAutosize();

    // === INICIALIZACIÓN PARA CADA INSTANCIA DE EDITOR DE MENÚ ===
    $('.glory-menu-structure-admin').each(function (instanceIndex) {
        //console.log(`Initializing Menu Editor Instance #${instanceIndex + 1}`);
        var $menuEditorInstance = $(this);
        var menuKey = $menuEditorInstance.data('menu-key'); // glory_content[menu_key_X]
        var baseInputNameForMenu = 'glory_content[' + menuKey + ']';
        //console.log('  Menu Key:', menuKey, 'Base Input Name:', baseInputNameForMenu);
        var i18n = gloryRestaurantMenuSettings.i18n; // Alias para facilidad de uso
        if (!i18n) {
            console.error('  ERROR: gloryRestaurantMenuSettings.i18n is undefined!');
            i18n = {}; // Fallback to prevent further errors
        }


        // --- Sortables (jQuery UI) ---
        // Sortable para Pestañas
        $menuEditorInstance.find('.glory-menu-tabs-editor').sortable({
            items: '.glory-menu-tab-item',
            handle: '.glory-sortable-handle',
            axis: 'y',
            update: function (event, ui) {
                //console.log('Tabs sortable updated. Re-indexing tabs.');
                reindexarElementos($(this), '.glory-menu-tab-item', new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[tabs\\]\\[)\\d+(\\]\\[.*)$'), 'data-tab-index');
            }
        });
        //console.log('  Tabs sortable initialized.');

        // Sortable para Secciones
        $menuEditorInstance.find('.glory-menu-sections-editor').sortable({
            items: '.glory-menu-section.postbox',
            handle: '.glory-sortable-handle',
            axis: 'y',
            update: function (event, ui) {
                //console.log('Sections sortable updated. Item dragged:', ui.item.find('.glory-section-title-display').text());
                // No se necesita reindexar secciones porque usan IDs únicos como claves, no índices numéricos.
            }
        });
        //console.log('  Sections sortable initialized.');

        // Sortable para Items dentro de secciones (se activa dinámicamente)
        function activarSortableParaItems($itemsList) {
            //console.log('activarSortableParaItems called for list:', $itemsList.prop('tagName') + '.' + $itemsList.attr('class'));
            if (!$itemsList.length) {
                console.warn('  activarSortableParaItems: $itemsList is empty or not found.');
                return;
            }
            if (!$itemsList.hasClass('ui-sortable')) {
                //console.log('  Initializing sortable on:', $itemsList.prop('tagName') + '.' + $itemsList.attr('class'));
                var baseNamePattern = new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[sections\\]\\[[^\\s\\]]+\\]\\[(?:items|packs|price_headers)\\]\\[)\\d+(\\](?:\\[.*\\])?)$');
                $itemsList.sortable({
                    items: '.glory-menu-item, .glory-price-header-item',
                    handle: '.glory-sortable-handle',
                    axis: 'y',
                    update: function (event, ui) {
                        //console.log('  Items sortable updated in list:', $(this).prop('tagName') + '.' + $(this).attr('class'), 'Item class:', ui.item.attr('class'));
                        let itemSelector = '.glory-menu-item';
                        if (ui.item.hasClass('glory-price-header-item')) {
                            itemSelector = '.glory-price-header-item';
                        }
                        //console.log('    Re-indexing with selector:', itemSelector, 'and pattern:', baseNamePattern);
                        reindexarElementos($(this), itemSelector, baseNamePattern, 'data-item-index');
                    }
                });
            } else {
                //console.log('  Sortable already initialized on:', $itemsList.prop('tagName') + '.' + $itemsList.attr('class'));
            }
        }
        // Activar para los existentes al cargar
        $menuEditorInstance.find('.glory-menu-items-list, .glory-menu-packs-list, .glory-price-headers-editor').each(function () {
            //console.log('  Activating sortable for existing list on load:', $(this).prop('tagName') + '.' + $(this).attr('class'));
            activarSortableParaItems($(this));
        });

        // --- Gestión de Pestañas (Tabs) ---
        $menuEditorInstance.on('click', '.glory-add-menu-tab', function () {
            //console.log('Add Menu Tab clicked.');
            var $tabsEditor = $(this).closest('.menu-tabs-container').find('.glory-menu-tabs-editor');
            var newTabIndex = $tabsEditor.find('.glory-menu-tab-item').length;
            var newTabId = generarIdUnico();
            //console.log('  New Tab Index:', newTabIndex, 'New Tab ID:', newTabId);

            var html = `
            <div class="glory-menu-tab-item" data-tab-index="${newTabIndex}">
                <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                <input type="text" name="${baseInputNameForMenu}[tabs][${newTabIndex}][id]" value="${newTabId}" placeholder="${i18n.tabIdPlaceholder || 'Tab ID'}" class="regular-text glory-tab-id-input">
                <input type="text" name="${baseInputNameForMenu}[tabs][${newTabIndex}][text]" value="" placeholder="${i18n.tabTextPlaceholder || 'Tab Text'}" class="regular-text glory-tab-text-input">
                <label class="glory-tab-visibility">
                    <input type="checkbox" name="${baseInputNameForMenu}[tabs][${newTabIndex}][visible_in_tabs]" value="1" checked>
                    ${i18n.visibleInTabBarLabel || 'Visible in tab bar'}
                </label>
                <button type="button" class="button button-small button-link-delete glory-remove-menu-tab" title="${i18n.removeTab || 'Remove Tab'}">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>`;
            // //console.log('  New tab HTML:', html);
            $tabsEditor.append(html);
            //console.log('  New tab appended.');
        });

        $menuEditorInstance.on('click', '.glory-remove-menu-tab', function () {
            var $tabItem = $(this).closest('.glory-menu-tab-item');
            var tabId = $tabItem.find('.glory-tab-id-input').val();
            //console.log('Remove Menu Tab clicked for tab ID (approx):', tabId);
            if (confirm(i18n.confirmRemoveTab || 'Are you sure you want to remove this tab?')) {
                //console.log('  Confirmed removal of tab ID:', tabId);
                var $tabsEditor = $tabItem.parent();
                $tabItem.remove();
                reindexarElementos($tabsEditor, '.glory-menu-tab-item', new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[tabs\\]\\[)\\d+(\\]\\[.*)$'), 'data-tab-index');
            } else {
                //console.log('  Removal of tab ID cancelled:', tabId);
            }
        });

        // --- Gestión de Secciones del Menú ---
        $menuEditorInstance.on('click', '.glory-add-menu-section', function () {
            //console.log('Add Menu Section clicked.');
            var $sectionsEditor = $(this).closest('.menu-sections-container').find('.glory-menu-sections-editor');
            var newSectionId = generarIdUnico();
            var baseNameForNewSection = `${baseInputNameForMenu}[sections][${newSectionId}]`;
            //console.log('  New Section ID:', newSectionId, 'Base name for new section:', baseNameForNewSection);

            var html = `
            <div class="glory-menu-section postbox" data-section-id="${newSectionId}">
                <div class="postbox-header">
                    <h2 class="hndle">
                        <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                        <span class="glory-section-title-display">${i18n.newSectionLabel || 'New Section'}</span>
                        <span class="glory-section-id-display">(ID: ${newSectionId})</span>
                    </h2>
                    <div class="handle-actions hide-if-no-js">
                         <button type="button" class="button-link glory-remove-menu-section" title="${i18n.removeSection || 'Remove Section'}"><span class="dashicons dashicons-trash"></span></button>
                         <button type="button" class="handlediv" aria-expanded="true"><span class="toggle-indicator"></span></button>
                    </div>
                </div>
                <div class="inside">
                    <p>
                        <label>${i18n.sectionTitleLabel || 'Section Title:'}</label><br>
                        <input type="text" name="${baseNameForNewSection}[title]" value="" class="large-text glory-section-title-input">
                    </p>
                    <p>
                        <label>${i18n.sectionDescriptionLabel || 'Section Description (optional):'}</label><br>
                        <textarea name="${baseNameForNewSection}[description]" rows="2" class="large-text glory-textarea-autosize"></textarea>
                    </p>
                    <p>
                        <label>${i18n.sectionTypeLabel || 'Section Type:'}</label><br>
                        <select name="${baseNameForNewSection}[type]" class="glory-section-type-select">
                            <option value="standard" selected>${i18n.sectionTypeStandard || 'Standard Items'}</option>
                            <option value="multi_price">${i18n.sectionTypeMultiPrice || 'Multi-Price Items'}</option>
                            <option value="menu_pack">${i18n.sectionTypeMenuPack || 'Menu Packs'}</option>
                        </select>
                    </p>
                    <div class="glory-section-type-specific-content">
                        <!-- Contenido se cargará dinámicamente por JS al cambiar el tipo o por defecto -->
                    </div>
                </div>
            </div>`;
            var $newSection = $(html);
            // //console.log('  New section HTML:', html);
            $sectionsEditor.append($newSection);
            //console.log('  New section appended.');
            $newSection.find('.glory-section-type-select').trigger('change');
            //console.log('  Triggered change on new section type select.');
            $newSection.find('textarea.glory-textarea-autosize').trigger('input'); // Ajustar altura
            // Activar sortable para la lista de items si la nueva sección es de un tipo que los usa
            //console.log('  Activating sortable for new section content (if any).');
            activarSortableParaItems($newSection.find('.glory-menu-items-list, .glory-menu-packs-list, .glory-price-headers-editor'));
        });

        $menuEditorInstance.on('input', '.glory-section-title-input', function () {
            var $input = $(this);
            var newTitle = $input.val() || i18n.newSectionLabel || 'New Section';
            // //console.log('Section title input changed to:', newTitle, 'for section ID:', $input.closest('.glory-menu-section').data('section-id'));
            $input.closest('.glory-menu-section').find('.glory-section-title-display').text(newTitle);
        });

        $menuEditorInstance.on('click', '.glory-remove-menu-section', function () {
            var $section = $(this).closest('.glory-menu-section.postbox');
            var sectionId = $section.data('section-id');
            var sectionTitle = $section.find('.glory-section-title-display').text();
            //console.log('Remove Menu Section clicked for section ID:', sectionId, 'Title:', sectionTitle);
            if (confirm(i18n.confirmRemoveSection || 'Are you sure you want to remove this entire section?')) {
                //console.log('  Confirmed removal of section ID:', sectionId);
                $section.remove();
            } else {
                //console.log('  Removal of section ID cancelled:', sectionId);
            }
        });

        // --- Gestión de Tipos de Sección (Standard, Multi-Price, Menu Pack) ---
        $menuEditorInstance.on('change', '.glory-section-type-select', function () {
            var $select = $(this);
            var selectedType = $select.val();
            var $sectionPostbox = $select.closest('.glory-menu-section.postbox');
            var sectionId = $sectionPostbox.data('section-id');
            var $contentContainer = $sectionPostbox.find('.glory-section-type-specific-content');
            var baseNameForSectionContent = $select.attr('name').replace(/\[type\]$/, '');
            //console.log(`Section Type changed for section ID ${sectionId} to: ${selectedType}. Base name for content: ${baseNameForSectionContent}`);

            $contentContainer.html(''); // Limpiar contenido anterior
            //console.log('  Cleared previous section-specific content.');

            var html = '';
            if (selectedType === 'standard') {
                //console.log('  Loading UI for standard items.');
                html = `
                <div class="glory-menu-items-list-container">
                    <h4>${i18n.itemNameLabel || 'Items:'}</h4>
                    <div class="glory-menu-items-list"></div>
                    <button type="button" class="button glory-add-menu-item" data-item-type="standard">${i18n.addStandardItemLabel || 'Add Standard Item'}</button>
                </div>`;
            } else if (selectedType === 'multi_price') {
                //console.log('  Loading UI for multi-price items.');
                html = `
                <div class="glory-menu-section-multi-price-container">
                    <h4>${i18n.priceColumnsLabel || 'Price Columns:'}</h4>
                    <div class="glory-price-headers-editor glory-global-price-headers-editor"></div>
                    <button type="button" class="button glory-add-price-header">${i18n.addPriceColumnHeaderLabel || 'Add Price Column Header'}</button>
                    <hr>
                    <h4>${i18n.multiPriceItemsLabel || 'Multi-Price Items:'}</h4>
                    <div class="glory-menu-items-list glory-menu-items-multi-price"></div>
                    <button type="button" class="button glory-add-menu-item" data-item-type="multi_price">${i18n.addMultiPriceItemLabel || 'Add Multi-Price Item'}</button>
                </div>`;
            } else if (selectedType === 'menu_pack') {
                //console.log('  Loading UI for menu packs.');
                html = `
                <div class="glory-menu-section-menu-pack-container">
                    <h4>${i18n.menuPacksLabel || 'Menu Packs / Combos:'}</h4>
                    <div class="glory-menu-packs-list"></div>
                    <button type="button" class="button glory-add-menu-item" data-item-type="menu_pack">${i18n.addMenuPackLabel || 'Add Menu Pack'}</button>
                </div>`;
            }
            // //console.log('  Generated HTML for section type:', html);
            $contentContainer.html(html);
            //console.log('  Appended new section-specific content.');
            // Activar sortables para las nuevas listas que se hayan podido crear
            var $newListToSort = $contentContainer.find('.glory-menu-items-list, .glory-menu-packs-list, .glory-price-headers-editor');
            //console.log('  Activating sortable for newly created lists (if any). Found lists:', $newListToSort.length);
            activarSortableParaItems($newListToSort);
            $contentContainer.find('textarea.glory-textarea-autosize').trigger('input');
        });

        // --- Gestión de Items (Botón genérico ".glory-add-menu-item") ---
        $menuEditorInstance.on('click', '.glory-add-menu-item', function () {
            var $button = $(this);
            var itemType = $button.data('item-type');
            var $sectionPostbox = $button.closest('.glory-menu-section.postbox');
            var sectionId = $sectionPostbox.data('section-id');
            var baseNameForSection = $sectionPostbox
                .find('.glory-section-type-select') // This is the select for the section type
                .attr('name') // Name includes ...[sections][section_id][type]
                .replace(/\[type\]$/, ''); // Removes [type] to get ...[sections][section_id]
            
            //console.log(`Add Menu Item clicked. Type: ${itemType}, Section ID: ${sectionId}, Base name for section: ${baseNameForSection}`);

            var html = '';
            var $itemsList, newItemIndex, baseNameForItems, $packsList, baseNameForPacks;

            if (itemType === 'standard') {
                $itemsList = $sectionPostbox.find('.glory-menu-items-list');
                newItemIndex = $itemsList.find('.glory-menu-item-standard').length; // Only count standard items in this list
                baseNameForItems = `${baseNameForSection}[items]`;
                //console.log('  Adding standard item. Current count:', newItemIndex, 'Base name for items:', baseNameForItems);
                html = `
                <div class="glory-menu-item glory-menu-item-standard" data-item-index="${newItemIndex}">
                    <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                    <button type="button" class="button button-small button-link-delete glory-remove-menu-item" title="${i18n.removeItem || 'Remove Item'}"><span class="dashicons dashicons-no-alt"></span></button>
                    <p><label>${i18n.itemNameLabel || 'Item Name:'}</label><br><input type="text" name="${baseNameForItems}[${newItemIndex}][name]" value="" class="large-text"></p>
                    <p><label>${i18n.itemPriceLabel || 'Item Price:'}</label><br><input type="text" name="${baseNameForItems}[${newItemIndex}][price]" value="" class="regular-text"></p>
                    <p><label>${i18n.itemDescriptionLabel || 'Item Description:'}</label><br><textarea name="${baseNameForItems}[${newItemIndex}][description]" rows="1" class="large-text glory-textarea-autosize"></textarea></p>
                </div>`;
                $itemsList.append(html);
                //console.log('  Standard item appended.');
                activarSortableParaItems($itemsList);
                $itemsList.find('textarea.glory-textarea-autosize').last().trigger('input');
            } else if (itemType === 'multi_price') {
                $itemsList = $sectionPostbox.find('.glory-menu-items-multi-price');
                newItemIndex = $itemsList.find('.glory-menu-item-multi-price').length; // Count only multi-price items in this specific list
                baseNameForItems = `${baseNameForSection}[items]`;
                //console.log('  Adding multi-price item. Current count:', newItemIndex, 'Base name for items:', baseNameForItems);

                let activeHeaders = [];
                let lastItemHeaderRow = $itemsList.find('.glory-menu-item-row-header[data-is-header-row="true"]').last();
                //console.log('    Determining active headers for new multi-price item. LastItemHeaderRow in item list exists:', lastItemHeaderRow.length > 0);

                if (lastItemHeaderRow.length) {
                    //console.log('    Using headers from last item-defined header row.');
                    lastItemHeaderRow.find('.glory-header-row-prices input[type="text"]').each(function () {
                        activeHeaders.push($(this).val() || '');
                    });
                } else {
                    //console.log('    Using global headers from section.');
                    // If no 'is_header_row' in the list of items, use the global ones of the section
                    $sectionPostbox.find('.glory-global-price-headers-editor .glory-price-header-item input[type="text"]').each(function () {
                        activeHeaders.push($(this).val() || '');
                    });
                }
                //console.log('    Active headers for new item:', activeHeaders);
                
                var numPriceColumns = activeHeaders.length;
                var pricesHtml = '';

                if (numPriceColumns > 0) {
                    for (var i = 0; i < numPriceColumns; i++) {
                        let placeholder = activeHeaders[i] ? activeHeaders[i] : (i18n.priceLabelN || 'Price %d').replace('%d', i + 1);
                        pricesHtml += `<input type="text" name="${baseNameForItems}[${newItemIndex}][prices][${i}]" value="" placeholder="${placeholder}" class="regular-text"> `;
                    }
                } else {
                    pricesHtml = `<input type="text" name="${baseNameForItems}[${newItemIndex}][prices][0]" value="" placeholder="${i18n.priceLabelGeneral || 'Price'}" class="regular-text">`;
                }
                //console.log('    Generated prices HTML for new multi-price item:', pricesHtml.substring(0,150) + '...');

                html = `
                <div class="glory-menu-item glory-menu-item-multi-price" data-item-index="${newItemIndex}" data-is-header-row="false" data-is-single-price="false">
                    <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                    <button type="button" class="button button-small button-link-delete glory-remove-menu-item" title="${i18n.removeItem || 'Remove Item'}"><span class="dashicons dashicons-no-alt"></span></button>
                    
                    <p><label>${i18n.itemNameLabel || 'Item Name:'}</label><br>
                       <input type="text" name="${baseNameForItems}[${newItemIndex}][name]" value="" class="large-text glory-item-name">
                    </p>

                    <div class="glory-item-price-fields">
                        <label class="glory-item-prices-label">${i18n.multiPriceItemsLabel || 'Prices:'}</label>
                        <div class="glory-item-prices-row glory-standard-multi-prices-row">${pricesHtml}</div>
                    </div>

                    <p>
                        <label>
                            <input type="checkbox" class="glory-item-is-header-row-checkbox" name="${baseNameForItems}[${newItemIndex}][is_header_row]" value="1">
                            ${i18n.isHeaderRowLabel || 'Is Header Row'} 
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" class="glory-item-is-single-price-checkbox" name="${baseNameForItems}[${newItemIndex}][is_single_price]" value="1">
                            ${i18n.isSinglePriceLabel || 'Is Single Price Item'}
                        </label>
                    </p>

                    <p><label>${i18n.itemDescriptionLabel || 'Item Description:'}</label><br>
                       <textarea name="${baseNameForItems}[${newItemIndex}][description]" rows="1" class="large-text glory-textarea-autosize"></textarea>
                    </p>
                </div>`;
                var $newItem = $(html);
                $itemsList.append($newItem);
                //console.log('  Multi-price item appended.');
                activarSortableParaItems($itemsList);
                $newItem.find('textarea.glory-textarea-autosize').last().trigger('input');
                //console.log('    Triggering change on new multi-price item checkboxes to set initial UI.');
                $newItem.find('.glory-item-is-header-row-checkbox, .glory-item-is-single-price-checkbox').trigger('change');
            } else if (itemType === 'menu_pack') {
                $packsList = $sectionPostbox.find('.glory-menu-packs-list');
                newItemIndex = $packsList.find('.glory-menu-item-pack').length; // Count only pack items in this list
                baseNameForPacks = `${baseNameForSection}[packs]`;
                //console.log('  Adding menu pack item. Current count:', newItemIndex, 'Base name for packs:', baseNameForPacks);
                html = `
                <div class="glory-menu-item glory-menu-item-pack" data-item-index="${newItemIndex}">
                    <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                    <button type="button" class="button button-small button-link-delete glory-remove-menu-item" title="${i18n.removeItem || 'Remove Item'}"><span class="dashicons dashicons-no-alt"></span></button>
                    <p><label>${i18n.packNameLabel || 'Pack Name:'}</label><br><input type="text" name="${baseNameForPacks}[${newItemIndex}][name]" value="" class="large-text"></p>
                    <p><label>${i18n.packPriceLabel || 'Pack Price:'}</label><br><input type="text" name="${baseNameForPacks}[${newItemIndex}][price]" value="" class="regular-text"></p>
                    <p><label>${i18n.packItemsLabel || 'Pack Items:'}</label><br><textarea name="${baseNameForPacks}[${newItemIndex}][items]" rows="2" class="large-text glory-textarea-autosize"></textarea></p>
                    <p><label>${i18n.packDescriptionLabel || 'Pack Description:'}</label><br><textarea name="${baseNameForPacks}[${newItemIndex}][description]" rows="1" class="large-text glory-textarea-autosize"></textarea></p>
                </div>`;
                $packsList.append(html);
                //console.log('  Menu pack item appended.');
                activarSortableParaItems($packsList);
                $packsList.find('textarea.glory-textarea-autosize').last().trigger('input');
            } else {
                console.warn('  Unknown itemType:', itemType);
            }
        });

        // Eliminar Item (genérico para cualquier tipo de item dentro de una sección)
        $menuEditorInstance.on('click', '.glory-remove-menu-item', function () {
            var $itemDiv = $(this).closest('.glory-menu-item');
            var itemName = $itemDiv.find('input[name*="[name]"]').first().val() || 'Unnamed Item';
            var itemClass = $itemDiv.attr('class').split(' ').find(cls => cls.startsWith('glory-menu-item-'));
            //console.log('Remove Menu Item clicked for item:', itemName, 'Class:', itemClass);

            if (confirm(i18n.confirmRemoveItem || 'Are you sure you want to remove this item?')) {
                //console.log('  Confirmed removal of item:', itemName);
                var $itemsList = $itemDiv.parent(); // .glory-menu-items-list, .glory-menu-packs-list, etc.
                var itemSelectorForList = '.' + itemClass;

                $itemDiv.remove();

                var baseNamePatternForItems = new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[sections\\]\\[[^\\s\\]]+\\]\\[(?:items|packs)\\]\\[)\\d+(\\](?:\\[.*\\])?)$');
                //console.log('  Re-indexing remaining items in list:', $itemsList.attr('class'), 'using selector:', itemSelectorForList);
                reindexarElementos($itemsList, itemSelectorForList, baseNamePatternForItems, 'data-item-index');
            } else {
                //console.log('  Removal of item cancelled:', itemName);
            }
        });

        // --- Gestión de Headers de Precio (para tipo Multi-Price) ---
        $menuEditorInstance.on('click', '.glory-add-price-header', function () {
            //console.log('Add Price Header (global) clicked.');
            var $button = $(this);
            var $headersEditor = $button.siblings('.glory-price-headers-editor');
            var newHeaderIndex = $headersEditor.find('.glory-price-header-item').length;
            
            // Obtener baseNameForSection de forma más robusta
            var $sectionPostbox = $button.closest('.glory-menu-section.postbox');
            if (!$sectionPostbox.length) { // Si el botón no está dentro de un .postbox (ej. es global)
                console.error("  Could not find parent .glory-menu-section.postbox for .glory-add-price-header");
                return;
            }
             var baseNameForSection = $sectionPostbox
                .find('.glory-section-type-select')
                .attr('name')
                .replace(/\[type\]$/, '');

            var baseNameForHeaders = `${baseNameForSection}[price_headers]`;
            //console.log('  Adding global price header. Current count:', newHeaderIndex, 'Base name for headers:', baseNameForHeaders);

            var html = `
            <div class="glory-price-header-item" data-item-index="${newHeaderIndex}">
                <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                <input type="text" name="${baseNameForHeaders}[${newHeaderIndex}]" value="" placeholder="${i18n.priceHeaderPlaceholder || 'e.g., Small'}">
                <button type="button" class="button button-small button-link-delete glory-remove-price-header"><span class="dashicons dashicons-no-alt"></span></button>
            </div>`;
            $headersEditor.append(html);
            //console.log('  Global price header appended.');
            activarSortableParaItems($headersEditor);
            // Aquí podría ser necesario actualizar los items multi-precio existentes para reflejar el nuevo header
            //console.log('  TODO: Consider updating existing multi-price items to reflect new global header.');
        });


        $menuEditorInstance.on('click', '.glory-remove-price-header', function () {
            var $headerItem = $(this).closest('.glory-price-header-item');
            var headerVal = $headerItem.find('input[type="text"]').val();
            //console.log('Remove Price Header (global) clicked for header value:', headerVal);
            var $headersEditor = $headerItem.parent();
            // var removedIndex = $headerItem.index(); // No se usa, pero podría ser útil
            $headerItem.remove();

            var baseNamePatternForHeaders = new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[sections\\]\\[[^\\s\\]]+\\]\\[price_headers\\]\\[)\\d+(\\])$');
            //console.log('  Re-indexing remaining global price headers.');
            reindexarElementos($headersEditor, '.glory-price-header-item', baseNamePatternForHeaders, 'data-item-index');
            //console.log('  TODO: Consider updating existing multi-price items to reflect removed global header.');
        });

        // --- Disparar 'change' inicial para los selects de tipo de sección y checkboxes ---
        //console.log('  Performing initial setup for existing sections and items...');
        $menuEditorInstance.find('.glory-menu-section.postbox').each(function(idx) {
            var $section = $(this);
            var sectionTitle = $section.find('.glory-section-title-display').text();
            var sectionId = $section.data('section-id');
            //console.log(`    Initial setup for existing section #${idx + 1}: "${sectionTitle}" (ID: ${sectionId})`);

            // Activar sortables para las listas que ya existen al cargar la página
            var $listsToSort = $section.find('.glory-menu-items-list, .glory-menu-packs-list, .glory-price-headers-editor');
            if($listsToSort.length) {
                 //console.log(`      Activating sortables for ${$listsToSort.length} list(s) in section "${sectionTitle}"`);
                 activarSortableParaItems($listsToSort);
            }

            // Activar autosize para textareas existentes
            var $textareas = $section.find('textarea.glory-textarea-autosize');
            if($textareas.length){
                //console.log(`      Triggering input for ${$textareas.length} autosize textarea(s) in section "${sectionTitle}"`);
                $textareas.trigger('input');
            }

            // Disparar change en los checkboxes de items multi-precio existentes para asegurar UI correcta
            $section.find('.glory-menu-item-multi-price').each(function(itemIdx) {
                var itemName = $(this).find('.glory-item-name').val() || 'Unnamed multi-price item';
                //console.log(`      Triggering change on checkboxes for existing multi-price item #${itemIdx + 1}: "${itemName}" in section "${sectionTitle}"`);
                $(this).find('.glory-item-is-header-row-checkbox, .glory-item-is-single-price-checkbox').trigger('change');
            });
        });
        //console.log('  Finished initial setup for existing sections and items.');


        // Evento para el checkbox "Is Header Row"
        $menuEditorInstance.on('change', '.glory-item-is-header-row-checkbox', function () {
            var $checkbox = $(this);
            var $itemDiv = $checkbox.closest('.glory-menu-item-multi-price');
            var itemName = $itemDiv.find('.glory-item-name').val() || 'Unnamed Item';
            var isChecked = $checkbox.is(':checked');
            //console.log(`"Is Header Row" checkbox changed for item "${itemName}". Checked: ${isChecked}`);

            var $singlePriceCheckbox = $itemDiv.find('.glory-item-is-single-price-checkbox');
            var $priceFieldsContainer = $itemDiv.find('.glory-item-price-fields');
            var baseNameForItem = $itemDiv.find('.glory-item-name').attr('name').replace(/\[name\]$/, ''); 
            //console.log('    Base name for this item:', baseNameForItem);

            if (isChecked) {
                //console.log('    UI changing TO Header Row.');
                if ($singlePriceCheckbox.is(':checked')) {
                    //console.log('    Unchecking "Is Single Price" checkbox.');
                    $singlePriceCheckbox.prop('checked', false).triggerHandler('change'); // triggerHandler para evitar bucles si el otro handler también hace trigger('change')
                }
                $itemDiv.attr('data-is-header-row', 'true').addClass('glory-menu-item-row-header');
                $itemDiv.attr('data-is-single-price', 'false').removeClass('glory-menu-item-single-price');

                var headerPricesHtml = '';
                var existingPrices = [];
                var $currentPriceInputs = $priceFieldsContainer.find('.glory-item-prices-row input[type="text"]');
                $currentPriceInputs.each(function() { existingPrices.push($(this).val()); });
                //console.log('    Existing price/header values before change:', existingPrices);
                
                var numInitialHeaders = Math.max(1, existingPrices.length); // Al menos un campo de cabecera

                for (var i = 0; i < numInitialHeaders; i++) {
                    headerPricesHtml += `<input type="text" name="${baseNameForItem}[prices][${i}]" value="${existingPrices[i] || ''}" placeholder="${(i18n.headerTextLabelN || 'Header Text %d').replace('%d', i + 1)}" class="regular-text"> `;
                }
                //console.log('    Generated header prices HTML:', headerPricesHtml.substring(0,150) + '...');

                $priceFieldsContainer.html(`
                    <label class="glory-item-prices-label">${i18n.columnHeadersDefinedByThisRowLabel || 'Column Headers Defined by this Row:'}</label>
                    <div class="glory-item-prices-row glory-header-row-prices">${headerPricesHtml}</div>
                    <button type="button" class="button button-small glory-add-header-price-field-to-row" title="${i18n.addHeaderPriceFieldTitle || 'Add another header text field'}">+</button>
                `);
                $itemDiv.find('.glory-item-name').siblings('label').text(i18n.headerRowNameLabel || 'Header Row Name (HTML allowed):');
            } else { // Checkbox "Is Header Row" desmarcado
                //console.log('    UI changing FROM Header Row.');
                $itemDiv.attr('data-is-header-row', 'false').removeClass('glory-menu-item-row-header');
                if (!$singlePriceCheckbox.is(':checked')) { // Si el de single price tampoco está marcado, volver a UI de multi-precio estándar
                    //console.log('    "Is Single Price" is also unchecked. Reverting to standard multi-price UI.');
                    let activeHeaders = [];
                    let $itemsList = $itemDiv.closest('.glory-menu-items-multi-price');
                    let prevHeaderRow = $itemDiv.prevAll('.glory-menu-item-row-header[data-is-header-row="true"]:first');
                    //console.log('    Looking for previous item-defined header row. Found:', prevHeaderRow.length > 0);

                    if (prevHeaderRow.length) {
                        prevHeaderRow.find('.glory-header-row-prices input[type="text"]').each(function () {
                            activeHeaders.push($(this).val() || '');
                        });
                    } else { // Usar cabeceras globales de la sección
                        //console.log('    No previous item-defined header row found. Using global section headers.');
                        $itemDiv.closest('.glory-menu-section.postbox').find('.glory-global-price-headers-editor .glory-price-header-item input[type="text"]').each(function () {
                            activeHeaders.push($(this).val() || '');
                        });
                    }
                    //console.log('    Active headers for standard multi-price UI:', activeHeaders);

                    var numPriceColumns = activeHeaders.length;
                    var pricesHtml = '';
                    var existingHeaderTextsAsPrices = []; 
                    $priceFieldsContainer.find('.glory-header-row-prices input[type="text"]').each(function() { existingHeaderTextsAsPrices.push($(this).val()); });
                    //console.log('    Existing header text values (to be used as prices):', existingHeaderTextsAsPrices);


                    if (numPriceColumns > 0) {
                        for (var i = 0; i < numPriceColumns; i++) {
                            let placeholder = activeHeaders[i] ? activeHeaders[i] : (i18n.priceLabelN || 'Price %d').replace('%d', i + 1);
                            pricesHtml += `<input type="text" name="${baseNameForItem}[prices][${i}]" value="${existingHeaderTextsAsPrices[i] || ''}" placeholder="${placeholder}" class="regular-text"> `;
                        }
                    } else {
                         pricesHtml = `<input type="text" name="${baseNameForItem}[prices][0]" value="${existingHeaderTextsAsPrices[0] || ''}" placeholder="${i18n.priceLabelGeneral || 'Price'}" class="regular-text">`;
                    }
                    //console.log('    Generated standard multi-price HTML:', pricesHtml.substring(0,150) + '...');
                    
                    $priceFieldsContainer.html(`
                        <label class="glory-item-prices-label">${i18n.multiPriceItemsLabel || 'Prices:'}</label>
                        <div class="glory-item-prices-row glory-standard-multi-prices-row">${pricesHtml}</div>
                    `);
                    $itemDiv.find('.glory-item-name').siblings('label').text(i18n.itemNameLabel || 'Item Name:');
                } else {
                    //console.log('    "Is Single Price" is checked. Its handler should manage the UI.');
                }
            }
            //console.log('    Finished "Is Header Row" checkbox change handler.');
        });

        // Evento para el checkbox "Is Single Price"
        $menuEditorInstance.on('change', '.glory-item-is-single-price-checkbox', function () {
            var $checkbox = $(this);
            var $itemDiv = $checkbox.closest('.glory-menu-item-multi-price');
            var itemName = $itemDiv.find('.glory-item-name').val() || 'Unnamed Item';
            var isChecked = $checkbox.is(':checked');
            //console.log(`"Is Single Price" checkbox changed for item "${itemName}". Checked: ${isChecked}`);

            var $headerRowCheckbox = $itemDiv.find('.glory-item-is-header-row-checkbox');
            var $priceFieldsContainer = $itemDiv.find('.glory-item-price-fields');
            var baseNameForItem = $itemDiv.find('.glory-item-name').attr('name').replace(/\[name\]$/, '');
            //console.log('    Base name for this item:', baseNameForItem);

            if (isChecked) {
                //console.log('    UI changing TO Single Price.');
                if ($headerRowCheckbox.is(':checked')) {
                    //console.log('    Unchecking "Is Header Row" checkbox.');
                    $headerRowCheckbox.prop('checked', false).triggerHandler('change'); 
                }
                $itemDiv.attr('data-is-single-price', 'true').addClass('glory-menu-item-single-price');
                $itemDiv.attr('data-is-header-row', 'false').removeClass('glory-menu-item-row-header');

                var firstPriceValue = '';
                var $currentPriceInputs = $priceFieldsContainer.find('.glory-item-prices-row input[type="text"]');
                if ($currentPriceInputs.length > 0) {
                    firstPriceValue = $currentPriceInputs.first().val() || '';
                }
                //console.log('    Value from first current price field (if any):', firstPriceValue);
                
                $priceFieldsContainer.html(`
                    <label class="glory-item-prices-label">${i18n.priceLabelGeneral || 'Price:'}</label>
                    <div class="glory-item-prices-row glory-single-price-row">
                        <input type="text" name="${baseNameForItem}[price]" value="${firstPriceValue}" placeholder="${i18n.priceLabelGeneral || 'Price'}" class="regular-text">
                    </div>
                `); // NOTE: name is [price], not [prices][0] for single price
                $itemDiv.find('.glory-item-name').siblings('label').text(i18n.itemNameLabel || 'Item Name:');
            } else { // Checkbox "Is Single Price" desmarcado
                //console.log('    UI changing FROM Single Price.');
                $itemDiv.attr('data-is-single-price', 'false').removeClass('glory-menu-item-single-price');
                if (!$headerRowCheckbox.is(':checked')) { // Si el de header row tampoco está marcado, volver a UI de multi-precio estándar
                    //console.log('    "Is Header Row" is also unchecked. Reverting to standard multi-price UI.');
                    let activeHeaders = [];
                    let $itemsList = $itemDiv.closest('.glory-menu-items-multi-price');
                    let prevHeaderRow = $itemDiv.prevAll('.glory-menu-item-row-header[data-is-header-row="true"]:first');
                    //console.log('    Looking for previous item-defined header row. Found:', prevHeaderRow.length > 0);
                    if (prevHeaderRow.length) {
                        prevHeaderRow.find('.glory-header-row-prices input[type="text"]').each(function () {
                            activeHeaders.push($(this).val() || '');
                        });
                    } else {
                        //console.log('    No previous item-defined header row found. Using global section headers.');
                        $itemDiv.closest('.glory-menu-section.postbox').find('.glory-global-price-headers-editor .glory-price-header-item input[type="text"]').each(function () {
                            activeHeaders.push($(this).val() || '');
                        });
                    }
                    //console.log('    Active headers for standard multi-price UI:', activeHeaders);
                    
                    var numPriceColumns = activeHeaders.length;
                    var pricesHtml = '';
                    var existingSinglePrice = $priceFieldsContainer.find('.glory-single-price-row input[type="text"]').val();
                    //console.log('    Value from single price field (to be used as first price):', existingSinglePrice);

                    if (numPriceColumns > 0) {
                        for (var i = 0; i < numPriceColumns; i++) {
                            let placeholder = activeHeaders[i] ? activeHeaders[i] : (i18n.priceLabelN || 'Price %d').replace('%d', i + 1);
                            let value = (i === 0 && existingSinglePrice) ? existingSinglePrice : '';
                            pricesHtml += `<input type="text" name="${baseNameForItem}[prices][${i}]" value="${value}" placeholder="${placeholder}" class="regular-text"> `;
                        }
                    } else {
                        pricesHtml = `<input type="text" name="${baseNameForItem}[prices][0]" value="${existingSinglePrice || ''}" placeholder="${i18n.priceLabelGeneral || 'Price'}" class="regular-text">`;
                    }
                    //console.log('    Generated standard multi-price HTML:', pricesHtml.substring(0,150) + '...');

                    $priceFieldsContainer.html(`
                        <label class="glory-item-prices-label">${i18n.multiPriceItemsLabel || 'Prices:'}</label>
                        <div class="glory-item-prices-row glory-standard-multi-prices-row">${pricesHtml}</div>
                    `);
                    $itemDiv.find('.glory-item-name').siblings('label').text(i18n.itemNameLabel || 'Item Name:');
                } else {
                    //console.log('    "Is Header Row" is checked. Its handler should manage the UI.');
                }
            }
            //console.log('    Finished "Is Single Price" checkbox change handler.');
        });

        // Evento para el botón "+" para añadir campos de texto de cabecera a un ítem "Is Header Row"
        $menuEditorInstance.on('click', '.glory-add-header-price-field-to-row', function () {
            var $button = $(this);
            var $itemDiv = $button.closest('.glory-menu-item-multi-price');
            var itemName = $itemDiv.find('.glory-item-name').val() || 'Unnamed Item';
            //console.log(`Add header price field to row clicked for item "${itemName}".`);

            var $headerPricesRow = $button.siblings('.glory-header-row-prices');
            var numCurrentFields = $headerPricesRow.find('input[type="text"]').length;
            var baseNameForItem = $itemDiv.find('.glory-item-name').attr('name').replace(/\[name\]$/, '');
            //console.log('    Num current header fields:', numCurrentFields, 'Base name:', baseNameForItem);

            var newFieldHtml = ` <input type="text" name="${baseNameForItem}[prices][${numCurrentFields}]" value="" placeholder="${(i18n.headerTextLabelN || 'Header Text %d').replace('%d', numCurrentFields + 1)}" class="regular-text">`;
            $headerPricesRow.append(newFieldHtml);
            //console.log('    Appended new header price field:', newFieldHtml);
        });

        //console.log(`Finished initializing Menu Editor Instance #${instanceIndex + 1}`);
    }); // Fin de .glory-menu-structure-admin.each()
    //console.log('Restaurant Menu Admin JS: All menu editor instances processed.');
}); // Fin de jQuery(document).ready
//console.log('Restaurant Menu Admin JS: jQuery(document).ready function registration finished.');