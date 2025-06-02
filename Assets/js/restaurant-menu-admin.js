jQuery(document).ready(function ($) {
    //console.log('Restaurant Menu Admin JS: Document ready. Checking conditions...');
    if (!$('.glory-menu-structure-admin').length) {
        //console.log('Restaurant Menu Admin JS: Conditions NOT met. Admin structure present:', $('.glory-menu-structure-admin').length > 0, '. Exiting.');
        return;
    }
    //console.log('Restaurant Menu Admin JS Loaded and conditions met.');

    // === UTILITIES ===
    function generarIdUnico(length = 5) {
        if (length === 5) { // Specific request for 5-digit random number
            return Math.floor(10000 + Math.random() * 90000).toString();
        }
        // Fallback to original method for other cases if needed, or adjust as necessary
        var id = 'gloryid_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        return id;
    }

    function reindexarElementos($container, itemSelector, namePatternRegex, dataAttributeForIndex) {
        //console.log('reindexarElementos called. Container:', $container.prop('tagName') + '.' + $container.attr('class'), 'ItemSelector:', itemSelector, 'Regex:', namePatternRegex, 'DataAttr:', dataAttributeForIndex);
        $container.find(itemSelector).each(function (newIndex, element) {
            var $element = $(element);
            // //console.log('  Re-indexing element, newIndex:', newIndex, 'Element:', $element.prop('tagName') + '.' + $element.attr('class'));
            if (dataAttributeForIndex) {
                var currentDataIndexVal = $element.attr(dataAttributeForIndex);
                if (currentDataIndexVal !== undefined && String(currentDataIndexVal) !== String(newIndex)) {
                     //console.log(`    Updating ${dataAttributeForIndex} from ${currentDataIndexVal} to ${newIndex}`);
                    $element.attr(dataAttributeForIndex, newIndex);
                } else if (currentDataIndexVal === undefined) {
                    //console.log(`    Setting new ${dataAttributeForIndex} to ${newIndex}`);
                    $element.attr(dataAttributeForIndex, newIndex);
                }
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
            // MODIFICADO: Asegurar que el data-pack-index en los botones de añadir detalle también se reindexe si el pack mismo se mueve
            if ($element.hasClass('glory-menu-item-pack')) {
                $element.find('.glory-add-menu-pack-detail').attr('data-pack-index', newIndex);
            }
        });
        //console.log('reindexarElementos finished for container:', $container.prop('tagName') + '.' + $container.attr('class'));
    }

    function inicializarTextareaAutosize() {
        //console.log('inicializarTextareaAutosize called');
        $(document).on('input', 'textarea.glory-textarea-autosize', function () {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
        $('textarea.glory-textarea-autosize').each(function () {
            $(this).trigger('input');
        });
        //console.log('inicializarTextareaAutosize finished initial trigger.');
    }
    inicializarTextareaAutosize();

    function reindexarHeaderPriceFields($headerPricesRowContainer, baseNameForItem, i18n) {
        $headerPricesRowContainer.find('.glory-header-price-field-wrapper').each(function(newIndex, wrapper) {
            var $wrapper = $(wrapper);
            var $input = $wrapper.find('input[type="text"].regular-text');
            
            var newName = `${baseNameForItem}[prices][${newIndex}]`;
            
            var placeholderTextFormat = (i18n && i18n.headerTextLabelN && typeof i18n.headerTextLabelN === 'string' && i18n.headerTextLabelN.includes('%d')) 
                                        ? i18n.headerTextLabelN 
                                        : 'Texto de Encabezado %d'; // Fallback por si i18n no está completo
            var newPlaceholder = placeholderTextFormat.replace('%d', newIndex + 1);
    
            $input.attr('name', newName);
            $input.attr('placeholder', newPlaceholder);
        });
    }


    $('.glory-menu-structure-admin').each(function (instanceIndex) {
        //console.log(`Initializing Menu Editor Instance #${instanceIndex + 1}`);
        var $menuEditorInstance = $(this);
        var menuKey = $menuEditorInstance.data('menu-key');
        var baseInputNameForMenu = 'glory_content[' + menuKey + ']';
        //console.log('  Menu Key:', menuKey, 'Base Input Name:', baseInputNameForMenu);
        var i18n = gloryRestaurantMenuSettings.i18n || {};

        $menuEditorInstance.find('.glory-menu-tabs-editor').sortable({
            items: '.glory-menu-tab-item',
            handle: '.glory-sortable-handle',
            axis: 'y',
            update: function (event, ui) {
                reindexarElementos($(this), '.glory-menu-tab-item', new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[tabs\\]\\[)\\d+(\\]\\[.*)$'), 'data-tab-index');
            }
        });

        $menuEditorInstance.find('.glory-menu-sections-editor').sortable({
            items: '.glory-menu-section.postbox',
            handle: '.glory-sortable-handle',
            axis: 'y',
            update: function (event, ui) {
                // No reindexar secciones por ID, pero sí sus contenidos si es necesario.
                // Los contenidos de pack details ahora se reindexarán específicamente.
            }
        });

        function activarSortableParaItems($itemsList) {
            //console.log('activarSortableParaItems called for list:', $itemsList.prop('tagName') + '.' + $itemsList.attr('class'));
            if (!$itemsList.length) {
                console.warn('  activarSortableParaItems: $itemsList is empty or not found.');
                return;
            }
            if (!$itemsList.hasClass('ui-sortable')) {
                //console.log('  Initializing sortable on:', $itemsList.prop('tagName') + '.' + $itemsList.attr('class'));
                
                var baseNamePattern = new RegExp(
                    '^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + // glory_content[key]
                    '\\[sections\\]\\[[^\\s\\]]+\\]' +  // [sections][section_id]
                    '\\[(?:items|packs|price_headers|details)\\]' + 
                    '(?:\\[\\d+\\])?' + 
                    '\\[(?:details\\])?' + 
                    '\\[)\\d+(\\](?:\\[.*\\])?)$' 
                );

                var packDetailNamePattern = new RegExp(
                    '^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + 
                    '\\[sections\\]\\[[^\\s\\]]+\\]' +  
                    '\\[packs\\]\\[\\d+\\]' +        
                    '\\[details\\]\\[)\\d+(\\]\\[(?:type|text)\\])$' 
                );


                $itemsList.sortable({
                    items: $itemsList.hasClass('glory-menu-pack-details-list') ? '.glory-menu-pack-detail-item' 
                         : ($itemsList.hasClass('glory-price-headers-editor') ? '.glory-price-header-item' 
                         : '.glory-menu-item'),
                    handle: '.glory-sortable-handle',
                    axis: 'y',
                    update: function (event, ui) {
                        //console.log('  Items sortable updated in list:', $(this).prop('tagName') + '.' + $(this).attr('class'), 'Item class:', ui.item.attr('class'));
                        let itemSelector;
                        let currentPattern = baseNamePattern; 
                        let dataAttribute = 'data-item-index'; 

                        if (ui.item.hasClass('glory-menu-pack-detail-item')) {
                            itemSelector = '.glory-menu-pack-detail-item';
                            currentPattern = packDetailNamePattern; 
                            dataAttribute = 'data-detail-index';
                            //console.log('    Re-indexing PACK DETAILS with selector:', itemSelector, 'and pattern:', currentPattern, 'data-attr:', dataAttribute);
                        } else if (ui.item.hasClass('glory-price-header-item')) {
                            itemSelector = '.glory-price-header-item';
                             //console.log('    Re-indexing PRICE HEADERS with selector:', itemSelector, 'and pattern:', currentPattern, 'data-attr:', dataAttribute);
                        } else { 
                            itemSelector = '.glory-menu-item';
                            //console.log('    Re-indexing MENU ITEMS/PACKS with selector:', itemSelector, 'and pattern:', currentPattern, 'data-attr:', dataAttribute);
                        }
                        reindexarElementos($(this), itemSelector, currentPattern, dataAttribute);
                    }
                });
            }
        }

        $menuEditorInstance.find('.glory-menu-items-list, .glory-menu-packs-list, .glory-price-headers-editor, .glory-menu-pack-details-list').each(function () { 
            //console.log('  Activating sortable for existing list on load:', $(this).prop('tagName') + '.' + $(this).attr('class'));
            activarSortableParaItems($(this));
        });

        $menuEditorInstance.on('click', '.glory-add-menu-tab', function () {
            var $tabsEditor = $(this).closest('.menu-tabs-container').find('.glory-menu-tabs-editor');
            var newTabIndex = $tabsEditor.find('.glory-menu-tab-item').length;
            var newTabId = generarIdUnico();
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
            $tabsEditor.append(html);
        });

        $menuEditorInstance.on('click', '.glory-remove-menu-tab', function () {
            var $tabItem = $(this).closest('.glory-menu-tab-item');
            if (confirm(i18n.confirmRemoveTab || 'Are you sure you want to remove this tab?')) {
                var $tabsEditor = $tabItem.parent();
                $tabItem.remove();
                reindexarElementos($tabsEditor, '.glory-menu-tab-item', new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[tabs\\]\\[)\\d+(\\]\\[.*)$'), 'data-tab-index');
            }
        });

        $menuEditorInstance.on('click', '.glory-add-menu-section', function () {
            var $sectionsEditor = $(this).closest('.menu-sections-container').find('.glory-menu-sections-editor');
            var newSectionId = generarIdUnico(5); // Generate 5-digit ID
            var baseNameForNewSection = `${baseInputNameForMenu}[sections][${newSectionId}]`;
            var html = `
            <div class="glory-menu-section postbox" data-section-id="${newSectionId}">
                <div class="postbox-header">
                    <h2 class="hndle">
                        <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                        <span class="glory-section-title-display">${i18n.newSectionLabel || 'New Section'}</span>
                        (<label for="${baseNameForNewSection}_id_input_field_new" style="font-weight: normal; font-size: inherit;">${i18n.sectionIdLabel || 'ID:'} </label>
                        <input type="text"
                               id="${baseNameForNewSection}_id_input_field_new"
                               name="${baseNameForNewSection}[id_value]"
                               value="${newSectionId}"
                               class="glory-section-id-input regular-text"
                               style="width: 100px; margin-left: 3px; font-weight: normal; display: inline-block; padding: 2px 4px; line-height: normal; height: auto; font-size: 13px;"
                               placeholder="${i18n.sectionIdPlaceholder || 'Unique ID'}">)
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
                    </div>
                </div>
            </div>`;
            var $newSection = $(html);
            $sectionsEditor.append($newSection);
            $newSection.find('.glory-section-type-select').trigger('change');
            $newSection.find('textarea.glory-textarea-autosize').trigger('input'); 
            activarSortableParaItems($newSection.find('.glory-menu-items-list, .glory-menu-packs-list, .glory-price-headers-editor, .glory-menu-pack-details-list'));
        });

        // Update data-section-id when the ID input changes
        $menuEditorInstance.on('change keyup', '.glory-section-id-input', function() {
            var $input = $(this);
            var newId = $input.val().trim();
            var $section = $input.closest('.glory-menu-section.postbox');
            var oldId = $section.data('section-id');

            if (newId && newId !== oldId) {
                // Basic validation: ensure it's not empty and potentially other checks (e.g., uniqueness within the page)
                // For now, just update it.
                $section.attr('data-section-id', newId);
                // console.log(`Section ID changed from ${oldId} to ${newId}`);

                // IMPORTANT: Update the name attribute of all child inputs of this section to reflect the new ID in the key
                // This is crucial for the form submission to be correct.
                // Example: glory_content[menu_principal][sections][OLD_ID_HERE][title] -> glory_content[menu_principal][sections][NEW_ID_HERE][title]
                $section.find('input, textarea, select').each(function() {
                    var $el = $(this);
                    var name = $el.attr('name');
                    if (name) {
                        var newName = name.replace(new RegExp(`(\[sections\]\[)${oldId}(\])`), `$1${newId}$2`);
                        if (name !== newName) {
                            $el.attr('name', newName);
                            // console.log(`Renamed input: ${name} -> ${newName}`);
                        }
                    }
                });
            }
        });


        $menuEditorInstance.on('input', '.glory-section-title-input', function () {
            var $input = $(this);
            var newTitle = $input.val() || i18n.newSectionLabel || 'New Section';
            $input.closest('.glory-menu-section').find('.glory-section-title-display').text(newTitle);
        });

        $menuEditorInstance.on('click', '.glory-remove-menu-section', function () {
            var $section = $(this).closest('.glory-menu-section.postbox');
            if (confirm(i18n.confirmRemoveSection || 'Are you sure you want to remove this entire section?')) {
                $section.remove();
            }
        });

        $menuEditorInstance.on('change', '.glory-section-type-select', function () {
            var $select = $(this);
            var selectedType = $select.val();
            var $sectionPostbox = $select.closest('.glory-menu-section.postbox');
            var $contentContainer = $sectionPostbox.find('.glory-section-type-specific-content');
            var html = '';
            if (selectedType === 'standard') {
                html = `
                <div class="glory-menu-items-list-container">
                    <h4>${i18n.itemNameLabel || 'Items:'}</h4>
                    <div class="glory-menu-items-list"></div>
                    <button type="button" class="button glory-add-menu-item" data-item-type="standard">${i18n.addStandardItemLabel || 'Add Standard Item'}</button>
                </div>`;
            } else if (selectedType === 'multi_price') {
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
                html = `
                <div class="glory-menu-section-menu-pack-container">
                    <h4>${i18n.menuPacksLabel || 'Menu Packs / Combos:'}</h4>
                    <div class="glory-menu-packs-list"></div>
                    <button type="button" class="button glory-add-menu-item" data-item-type="menu_pack">${i18n.addMenuPackLabel || 'Add Menu Pack'}</button>
                </div>`;
            }
            $contentContainer.html(html);
            var $newListToSort = $contentContainer.find('.glory-menu-items-list, .glory-menu-packs-list, .glory-price-headers-editor, .glory-menu-pack-details-list'); 
            activarSortableParaItems($newListToSort);
            $contentContainer.find('textarea.glory-textarea-autosize').trigger('input');
        });

        $menuEditorInstance.on('click', '.glory-add-menu-item', function () {
            var $button = $(this);
            var itemType = $button.data('item-type');
            var $sectionPostbox = $button.closest('.glory-menu-section.postbox');
            var baseNameForSection = $sectionPostbox.find('.glory-section-type-select').attr('name').replace(/\[type\]$/, '');
            var html = '';
            var $itemsList, newItemIndex, baseNameForItems, $packsList, baseNameForPacks;

            if (itemType === 'standard') {
                $itemsList = $sectionPostbox.find('.glory-menu-items-list');
                newItemIndex = $itemsList.find('.glory-menu-item-standard').length;
                baseNameForItems = `${baseNameForSection}[items]`;
                html = `
                <div class="glory-menu-item glory-menu-item-standard" data-item-index="${newItemIndex}">
                    <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                    <button type="button" class="button button-small button-link-delete glory-remove-menu-item" title="${i18n.removeItem || 'Remove Item'}"><span class="dashicons dashicons-no-alt"></span></button>
                    <p><label>${i18n.itemNameLabel || 'Item Name:'}</label><br><input type="text" name="${baseNameForItems}[${newItemIndex}][name]" value="" class="large-text"></p>
                    <p><label>${i18n.itemPriceLabel || 'Item Price:'}</label><br><input type="text" name="${baseNameForItems}[${newItemIndex}][price]" value="" class="regular-text"></p>
                    <p><label>${i18n.itemDescriptionLabel || 'Item Description:'}</label><br><textarea name="${baseNameForItems}[${newItemIndex}][description]" rows="1" class="large-text glory-textarea-autosize"></textarea></p>
                </div>`;
                $itemsList.append(html);
                activarSortableParaItems($itemsList);
                $itemsList.find('textarea.glory-textarea-autosize').last().trigger('input');
            } else if (itemType === 'multi_price') {
                $itemsList = $sectionPostbox.find('.glory-menu-items-multi-price');
                newItemIndex = $itemsList.find('.glory-menu-item-multi-price').length;
                baseNameForItems = `${baseNameForSection}[items]`;
                let activeHeaders = [];
                let lastItemHeaderRow = $itemsList.find('.glory-menu-item-row-header[data-is-header-row="true"]').last();
                if (lastItemHeaderRow.length) {
                    lastItemHeaderRow.find('.glory-header-row-prices input[type="text"]').each(function () { activeHeaders.push($(this).val() || ''); });
                } else {
                    $sectionPostbox.find('.glory-global-price-headers-editor .glory-price-header-item input[type="text"]').each(function () { activeHeaders.push($(this).val() || ''); });
                }
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
                html = `
                <div class="glory-menu-item glory-menu-item-multi-price" data-item-index="${newItemIndex}" data-is-header-row="false" data-is-single-price="false">
                    <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                    <button type="button" class="button button-small button-link-delete glory-remove-menu-item" title="${i18n.removeItem || 'Remove Item'}"><span class="dashicons dashicons-no-alt"></span></button>
                    <p><label>${i18n.itemNameLabel || 'Item Name:'}</label><br><input type="text" name="${baseNameForItems}[${newItemIndex}][name]" value="" class="large-text glory-item-name"></p>
                    <div class="glory-item-price-fields">
                        <label class="glory-item-prices-label">${i18n.multiPriceItemsLabel || 'Prices:'}</label>
                        <div class="glory-item-prices-row glory-standard-multi-prices-row">${pricesHtml}</div>
                    </div>
                    <p><label><input type="checkbox" class="glory-item-is-header-row-checkbox" name="${baseNameForItems}[${newItemIndex}][is_header_row]" value="1"> ${i18n.isHeaderRowLabel || 'Is Header Row'}</label></p>
                    <p><label><input type="checkbox" class="glory-item-is-single-price-checkbox" name="${baseNameForItems}[${newItemIndex}][is_single_price]" value="1"> ${i18n.isSinglePriceLabel || 'Is Single Price Item'}</label></p>
                    <p><label>${i18n.itemDescriptionLabel || 'Item Description:'}</label><br><textarea name="${baseNameForItems}[${newItemIndex}][description]" rows="1" class="large-text glory-textarea-autosize"></textarea></p>
                </div>`;
                var $newItem = $(html);
                $itemsList.append($newItem);
                activarSortableParaItems($itemsList);
                $newItem.find('textarea.glory-textarea-autosize').last().trigger('input');
                $newItem.find('.glory-item-is-header-row-checkbox, .glory-item-is-single-price-checkbox').trigger('change');
            } else if (itemType === 'menu_pack') {
                $packsList = $sectionPostbox.find('.glory-menu-packs-list');
                newItemIndex = $packsList.find('.glory-menu-item-pack').length;
                baseNameForPacks = `${baseNameForSection}[packs]`; 
                html = `
                <div class="glory-menu-item glory-menu-item-pack" data-item-index="${newItemIndex}">
                    <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                    <button type="button" class="button button-small button-link-delete glory-remove-menu-item" title="${i18n.removePack || 'Remove Pack'}"><span class="dashicons dashicons-no-alt"></span></button>
                    <p><label>${i18n.packTitleLabel || 'Pack Title:'}</label><br><input type="text" name="${baseNameForPacks}[${newItemIndex}][pack_title]" value="" class="large-text"></p>
                    <p><label>${i18n.packPriceLabel || 'Pack Price:'}</label><br><input type="text" name="${baseNameForPacks}[${newItemIndex}][pack_price]" value="" class="regular-text"></p>
                    <p><label>${i18n.packDescriptionLabel || 'Pack Description:'}</label><br><textarea name="${baseNameForPacks}[${newItemIndex}][pack_description]" rows="1" class="large-text glory-textarea-autosize"></textarea></p>
                    
                    <h4>${i18n.packDetailsLabel || 'Pack Details:'}</h4>
                    <div class="glory-menu-pack-details-list"></div>
                    <button type="button" class="button glory-add-menu-pack-detail" data-detail-type="item" data-pack-index="${newItemIndex}">${i18n.addPackItemDetailLabel || 'Add Item Detail'}</button>
                    <button type="button" class="button glory-add-menu-pack-detail" data-detail-type="heading" data-pack-index="${newItemIndex}">${i18n.addPackHeadingDetailLabel || 'Add Heading Detail'}</button>
                </div>`;
                var $newPack = $(html);
                $packsList.append($newPack);
                activarSortableParaItems($packsList); 
                activarSortableParaItems($newPack.find('.glory-menu-pack-details-list')); 
                $newPack.find('textarea.glory-textarea-autosize').last().trigger('input');
            }
        });

        $menuEditorInstance.on('click', '.glory-remove-menu-item', function () {
            var $itemDiv = $(this).closest('.glory-menu-item');
            var itemName = $itemDiv.find('input[name*="[name]"], input[name*="[pack_title]"]').first().val() || 'Unnamed Item/Pack';
            //console.log('Remove Menu Item/Pack clicked for:', itemName);

            if (confirm(i18n.confirmRemoveItem || 'Are you sure you want to remove this item/pack?')) {
                var $itemsListOrPacksList = $itemDiv.parent();
                var itemSelectorForList = '.' + $itemDiv.attr('class').split(' ').find(cls => cls.startsWith('glory-menu-item-'));
                
                $itemDiv.remove();

                var baseNamePatternForItemsOrPacks = new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[sections\\]\\[[^\\s\\]]+\\]\\[(?:items|packs)\\]\\[)\\d+(\\](?:\\[.*\\])?)$');
                reindexarElementos($itemsListOrPacksList, itemSelectorForList, baseNamePatternForItemsOrPacks, 'data-item-index');
            }
        });
        
        $menuEditorInstance.on('click', '.glory-add-menu-pack-detail', function () {
            var $button = $(this);
            var detailType = $button.data('detail-type'); 
            var packIndex = $button.data('pack-index'); 
            var $packItemDiv = $button.closest('.glory-menu-item-pack');
            var $detailsList = $packItemDiv.find('.glory-menu-pack-details-list');
            var newDetailIndex = $detailsList.find('.glory-menu-pack-detail-item').length;

            var baseNameForPack = $packItemDiv.find('input[name*="[pack_title]"]').attr('name').replace(/\[pack_title\]$/, '');
            var baseNameForNewDetail = `${baseNameForPack}[details][${newDetailIndex}]`;
            //console.log(`Adding pack detail. Type: ${detailType}, Pack Index: ${packIndex}, New Detail Index: ${newDetailIndex}, Base Name: ${baseNameForNewDetail}`);

            var placeholderText = detailType === 'heading' ? (i18n.packDetailHeadingPlaceholder || 'Heading text (e.g., First Courses)') : (i18n.packDetailItemPlaceholder || 'Item text (e.g., Salad)');
            var itemClass = `glory-menu-pack-detail-item glory-menu-pack-detail-${detailType}`;

            var html = `
            <div class="${itemClass}" data-detail-index="${newDetailIndex}" data-detail-type="${detailType}">
                <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                <input type="hidden" name="${baseNameForNewDetail}[type]" value="${detailType}">
                <input type="text" name="${baseNameForNewDetail}[text]" value="" placeholder="${placeholderText}" class="large-text glory-pack-detail-text-input">
                <button type="button" class="button button-small button-link-delete glory-remove-menu-pack-detail" title="${i18n.removePackDetail || 'Remove Detail'}">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>`;
            $detailsList.append(html);
            activarSortableParaItems($detailsList); 
        });

        $menuEditorInstance.on('click', '.glory-remove-menu-pack-detail', function () {
            var $detailItemDiv = $(this).closest('.glory-menu-pack-detail-item');
            var detailText = $detailItemDiv.find('.glory-pack-detail-text-input').val() || 'Unnamed Detail';
            //console.log('Remove Pack Detail clicked for text:', detailText);

            if (confirm(i18n.confirmRemovePackDetail || 'Are you sure you want to remove this detail?')) {
                var $detailsList = $detailItemDiv.parent(); 
                var $packItemDiv = $detailsList.closest('.glory-menu-item-pack');
                
                $detailItemDiv.remove();
                var packDetailNamePattern = new RegExp(
                    '^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + 
                    '\\[sections\\]\\[[^\\s\\]]+\\]' +  
                    '\\[packs\\]\\[\\d+\\]' +        
                    '\\[details\\]\\[)\\d+(\\]\\[(?:type|text)\\])$' 
                );
                reindexarElementos($detailsList, '.glory-menu-pack-detail-item', packDetailNamePattern, 'data-detail-index');
            }
        });


        $menuEditorInstance.on('click', '.glory-add-price-header', function () {
            var $button = $(this);
            var $headersEditor = $button.siblings('.glory-price-headers-editor');
            var newHeaderIndex = $headersEditor.find('.glory-price-header-item').length;
            var $sectionPostbox = $button.closest('.glory-menu-section.postbox');
            if (!$sectionPostbox.length) { return; }
            var baseNameForSection = $sectionPostbox.find('.glory-section-type-select').attr('name').replace(/\[type\]$/, '');
            var baseNameForHeaders = `${baseNameForSection}[price_headers]`;
            var html = `
            <div class="glory-price-header-item" data-item-index="${newHeaderIndex}">
                <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                <input type="text" name="${baseNameForHeaders}[${newHeaderIndex}]" value="" placeholder="${i18n.priceHeaderPlaceholder || 'e.g., Small'}">
                <button type="button" class="button button-small button-link-delete glory-remove-price-header"><span class="dashicons dashicons-no-alt"></span></button>
            </div>`;
            $headersEditor.append(html);
            activarSortableParaItems($headersEditor);
        });

        $menuEditorInstance.on('click', '.glory-remove-price-header', function () {
            var $headerItem = $(this).closest('.glory-price-header-item');
            var $headersEditor = $headerItem.parent();
            $headerItem.remove();
            var baseNamePatternForHeaders = new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[sections\\]\\[[^\\s\\]]+\\]\\[price_headers\\]\\[)\\d+(\\])$');
            reindexarElementos($headersEditor, '.glory-price-header-item', baseNamePatternForHeaders, 'data-item-index');
        });

        $menuEditorInstance.find('.glory-menu-section.postbox').each(function(idx) {
            var $section = $(this);
            var $listsToSort = $section.find('.glory-menu-items-list, .glory-menu-packs-list, .glory-price-headers-editor, .glory-menu-pack-details-list'); 
            if($listsToSort.length) {
                 activarSortableParaItems($listsToSort);
            }
            var $textareas = $section.find('textarea.glory-textarea-autosize');
            if($textareas.length){
                $textareas.trigger('input');
            }
            $section.find('.glory-menu-item-multi-price').each(function(itemIdx) {
                $(this).find('.glory-item-is-header-row-checkbox, .glory-item-is-single-price-checkbox').trigger('change');
            });
        });

        $menuEditorInstance.on('change', '.glory-item-is-header-row-checkbox', function () {
            var $checkbox = $(this);
            var $itemDiv = $checkbox.closest('.glory-menu-item-multi-price');
            var isChecked = $checkbox.is(':checked');
            var $singlePriceCheckbox = $itemDiv.find('.glory-item-is-single-price-checkbox');
            var $priceFieldsContainer = $itemDiv.find('.glory-item-price-fields');
            var baseNameForItem = $itemDiv.find('.glory-item-name').attr('name').replace(/\[name\]$/, ''); 
            var removeButtonTitle = (i18n && i18n.removeHeaderPriceFieldTitle) ? i18n.removeHeaderPriceFieldTitle : 'Eliminar este campo de encabezado';

            if (isChecked) {
                if ($singlePriceCheckbox.is(':checked')) { $singlePriceCheckbox.prop('checked', false).triggerHandler('change'); }
                $itemDiv.attr('data-is-header-row', 'true').addClass('glory-menu-item-row-header');
                $itemDiv.attr('data-is-single-price', 'false').removeClass('glory-menu-item-single-price');
                var headerPricesHtml = ''; var existingPrices = [];
                $priceFieldsContainer.find('.glory-item-prices-row input[type="text"]').each(function() { existingPrices.push($(this).val()); });
                var numInitialHeaders = Math.max(1, existingPrices.length); 
                for (var i = 0; i < numInitialHeaders; i++) { 
                    var placeholderTextFormat = (i18n && i18n.headerTextLabelN && typeof i18n.headerTextLabelN === 'string' && i18n.headerTextLabelN.includes('%d')) 
                                                ? i18n.headerTextLabelN 
                                                : 'Texto de Encabezado %d';
                     var placeholder = placeholderTextFormat.replace('%d', i + 1);
                    headerPricesHtml += `
                    <div class="glory-header-price-field-wrapper" style="display: flex; align-items: center; margin-bottom: 5px;">
                        <input type="text" name="${baseNameForItem}[prices][${i}]" value="${existingPrices[i] || ''}" placeholder="${placeholder}" class="regular-text" style="flex-grow: 1; margin-right: 5px;">
                        <button type="button" class="button button-small button-link-delete glory-remove-header-price-field-from-row" title="${removeButtonTitle}">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>`;
                }
                $priceFieldsContainer.html(`<label class="glory-item-prices-label">${i18n.columnHeadersDefinedByThisRowLabel || 'Column Headers Defined by this Row:'}</label><div class="glory-item-prices-row glory-header-row-prices">${headerPricesHtml}</div><button type="button" class="button button-small glory-add-header-price-field-to-row" title="${i18n.addHeaderPriceFieldTitle || 'Add another header text field'}">+</button>`);
                $itemDiv.find('.glory-item-name').siblings('label').text(i18n.headerRowNameLabel || 'Header Row Name (HTML allowed):');
            } else { 
                $itemDiv.attr('data-is-header-row', 'false').removeClass('glory-menu-item-row-header');
                if (!$singlePriceCheckbox.is(':checked')) { 
                    let activeHeaders = []; let $itemsList = $itemDiv.closest('.glory-menu-items-multi-price');
                    let prevHeaderRow = $itemDiv.prevAll('.glory-menu-item-row-header[data-is-header-row="true"]:first');
                    if (prevHeaderRow.length) { prevHeaderRow.find('.glory-header-row-prices .glory-header-price-field-wrapper input[type="text"]').each(function () { activeHeaders.push($(this).val() || ''); });
                    } else { $itemDiv.closest('.glory-menu-section.postbox').find('.glory-global-price-headers-editor .glory-price-header-item input[type="text"]').each(function () { activeHeaders.push($(this).val() || ''); }); }
                    var numPriceColumns = activeHeaders.length; var pricesHtml = '';
                    var existingHeaderTextsAsPrices = []; $priceFieldsContainer.find('.glory-header-row-prices .glory-header-price-field-wrapper input[type="text"]').each(function() { existingHeaderTextsAsPrices.push($(this).val()); });
                    if (numPriceColumns > 0) { for (var i = 0; i < numPriceColumns; i++) { let placeholder = activeHeaders[i] ? activeHeaders[i] : (i18n.priceLabelN || 'Price %d').replace('%d', i + 1); pricesHtml += `<input type="text" name="${baseNameForItem}[prices][${i}]" value="${existingHeaderTextsAsPrices[i] || ''}" placeholder="${placeholder}" class="regular-text"> `; }
                    } else { pricesHtml = `<input type="text" name="${baseNameForItem}[prices][0]" value="${existingHeaderTextsAsPrices[0] || ''}" placeholder="${i18n.priceLabelGeneral || 'Price'}" class="regular-text">`; }
                    $priceFieldsContainer.html(`<label class="glory-item-prices-label">${i18n.multiPriceItemsLabel || 'Prices:'}</label><div class="glory-item-prices-row glory-standard-multi-prices-row">${pricesHtml}</div>`);
                    $itemDiv.find('.glory-item-name').siblings('label').text(i18n.itemNameLabel || 'Item Name:');
                }
            }
        });

        $menuEditorInstance.on('change', '.glory-item-is-single-price-checkbox', function () {
            var $checkbox = $(this);
            var $itemDiv = $checkbox.closest('.glory-menu-item-multi-price');
            var isChecked = $checkbox.is(':checked');
            var $headerRowCheckbox = $itemDiv.find('.glory-item-is-header-row-checkbox');
            var $priceFieldsContainer = $itemDiv.find('.glory-item-price-fields');
            var baseNameForItem = $itemDiv.find('.glory-item-name').attr('name').replace(/\[name\]$/, '');
            if (isChecked) {
                if ($headerRowCheckbox.is(':checked')) { $headerRowCheckbox.prop('checked', false).triggerHandler('change'); }
                $itemDiv.attr('data-is-single-price', 'true').addClass('glory-menu-item-single-price');
                $itemDiv.attr('data-is-header-row', 'false').removeClass('glory-menu-item-row-header');
                var firstPriceValue = ''; var $currentPriceInputs = $priceFieldsContainer.find('.glory-item-prices-row input[type="text"]');
                if ($currentPriceInputs.length > 0) { firstPriceValue = $currentPriceInputs.first().val() || ''; }
                $priceFieldsContainer.html(`<label class="glory-item-prices-label">${i18n.priceLabelGeneral || 'Price:'}</label><div class="glory-item-prices-row glory-single-price-row"><input type="text" name="${baseNameForItem}[price]" value="${firstPriceValue}" placeholder="${i18n.priceLabelGeneral || 'Price'}" class="regular-text"></div>`);
                $itemDiv.find('.glory-item-name').siblings('label').text(i18n.itemNameLabel || 'Item Name:');
            } else { 
                $itemDiv.attr('data-is-single-price', 'false').removeClass('glory-menu-item-single-price');
                if (!$headerRowCheckbox.is(':checked')) { 
                    let activeHeaders = []; let $itemsList = $itemDiv.closest('.glory-menu-items-multi-price');
                    let prevHeaderRow = $itemDiv.prevAll('.glory-menu-item-row-header[data-is-header-row="true"]:first');
                    if (prevHeaderRow.length) { prevHeaderRow.find('.glory-header-row-prices .glory-header-price-field-wrapper input[type="text"]').each(function () { activeHeaders.push($(this).val() || ''); });
                    } else { $itemDiv.closest('.glory-menu-section.postbox').find('.glory-global-price-headers-editor .glory-price-header-item input[type="text"]').each(function () { activeHeaders.push($(this).val() || ''); }); }
                    var numPriceColumns = activeHeaders.length; var pricesHtml = '';
                    var existingSinglePrice = $priceFieldsContainer.find('.glory-single-price-row input[type="text"]').val();
                    if (numPriceColumns > 0) { for (var i = 0; i < numPriceColumns; i++) { let placeholder = activeHeaders[i] ? activeHeaders[i] : (i18n.priceLabelN || 'Price %d').replace('%d', i + 1); let value = (i === 0 && existingSinglePrice) ? existingSinglePrice : ''; pricesHtml += `<input type="text" name="${baseNameForItem}[prices][${i}]" value="${value}" placeholder="${placeholder}" class="regular-text"> `; }
                    } else { pricesHtml = `<input type="text" name="${baseNameForItem}[prices][0]" value="${existingSinglePrice || ''}" placeholder="${i18n.priceLabelGeneral || 'Price'}" class="regular-text">`;}
                    $priceFieldsContainer.html(`<label class="glory-item-prices-label">${i18n.multiPriceItemsLabel || 'Prices:'}</label><div class="glory-item-prices-row glory-standard-multi-prices-row">${pricesHtml}</div>`);
                    $itemDiv.find('.glory-item-name').siblings('label').text(i18n.itemNameLabel || 'Item Name:');
                }
            }
        });

        $menuEditorInstance.on('click', '.glory-add-header-price-field-to-row', function () {
            var $button = $(this); 
            var $itemDiv = $button.closest('.glory-menu-item-multi-price');
            var $headerPricesRowContainer = $button.closest('.glory-header-row-prices'); 
            
            var numCurrentFields = $headerPricesRowContainer.find('.glory-header-price-field-wrapper').length;
            var baseNameForItem = $itemDiv.find('.glory-item-name').attr('name').replace(/\[name\]$/, '');

            var placeholderTextFormat = (i18n && i18n.headerTextLabelN && typeof i18n.headerTextLabelN === 'string' && i18n.headerTextLabelN.includes('%d')) 
                                        ? i18n.headerTextLabelN 
                                        : 'Texto de Encabezado %d';
            var newPlaceholder = placeholderTextFormat.replace('%d', numCurrentFields + 1);
            var removeButtonTitle = (i18n && i18n.removeHeaderPriceFieldTitle) ? i18n.removeHeaderPriceFieldTitle : 'Eliminar este campo de encabezado';

            var newFieldHtml = `
            <div class="glory-header-price-field-wrapper" style="display: flex; align-items: center; margin-bottom: 5px;">
                <input type="text" name="${baseNameForItem}[prices][${numCurrentFields}]" value="" placeholder="${newPlaceholder}" class="regular-text" style="flex-grow: 1; margin-right: 5px;">
                <button type="button" class="button button-small button-link-delete glory-remove-header-price-field-from-row" title="${removeButtonTitle}">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>`;
            
            $(newFieldHtml).insertBefore($button);
        });

        $menuEditorInstance.on('click', '.glory-remove-header-price-field-from-row', function () {
            var $button = $(this);
            var $fieldWrapper = $button.closest('.glory-header-price-field-wrapper');
            var $headerPricesRowContainer = $fieldWrapper.parent(); 
            var $itemDiv = $headerPricesRowContainer.closest('.glory-menu-item-multi-price');
            var baseNameForItem = $itemDiv.find('.glory-item-name').attr('name').replace(/\[name\]$/, '');

            $fieldWrapper.remove();
            reindexarHeaderPriceFields($headerPricesRowContainer, baseNameForItem, i18n);
        });
    }); 
});