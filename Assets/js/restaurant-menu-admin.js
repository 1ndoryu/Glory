jQuery(document).ready(function ($) {
    // Verificar si estamos en la página correcta y si hay editores de menú
    if (!$('body').hasClass('glory-content_page_glory-restaurant-menu') || !$('.glory-menu-structure-admin').length) {
        // console.log('Restaurant Menu Admin JS: No glory-menu-structure-admin found or not on the correct page. Exiting.');
        return;
    }
    // console.log('Restaurant Menu Admin JS Loaded');

    // === UTILITIES ===
    /**
     * Genera un ID único simple.
     * @returns {string}
     */
    function generarIdUnico() {
        return 'gloryid_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Reindexa los atributos 'name' y 'data-*-index' de los elementos dentro de un contenedor.
     * @param {jQuery} $container El contenedor de los elementos a reindexar.
     * @param {string} itemSelector Selector para los items individuales.
     * @param {string} namePatternRegex Regex para capturar el prefijo y el sufijo del atributo 'name'.
     *                                 Debe tener dos grupos de captura: (prefijo_del_indice)(sufijo_despues_del_indice)
     *                                 Ejemplo para tabs: /^(.*\[tabs\]\[)\d+(\]\[.*)$/
     *                                 Ejemplo para items: /^(.*\[items\]\[)\d+(\]\[.*)$/
     * @param {string} dataAttributeForIndex (Opcional) El nombre del atributo data que almacena el índice. Ej: 'data-item-index'.
     */
    function reindexarElementos($container, itemSelector, namePatternRegex, dataAttributeForIndex) {
        $container.find(itemSelector).each(function (newIndex, element) {
            var $element = $(element);
            if (dataAttributeForIndex) {
                $element.attr(dataAttributeForIndex, newIndex);
            }
            $element.find('input, textarea, select').each(function () {
                var currentName = $(this).attr('name');
                if (currentName) {
                    var newName = currentName.replace(namePatternRegex, '$1' + newIndex + '$2');
                    $(this).attr('name', newName);
                }
            });
        });
    }

    /**
     * Ajusta la altura de los textareas automáticamente.
     */
    function inicializarTextareaAutosize() {
        $(document).on('input', 'textarea.glory-textarea-autosize', function () {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
        $('textarea.glory-textarea-autosize').each(function () {
            $(this).trigger('input');
        });
    }
    inicializarTextareaAutosize();

    // === INICIALIZACIÓN PARA CADA INSTANCIA DE EDITOR DE MENÚ ===
    $('.glory-menu-structure-admin').each(function () {
        var $menuEditorInstance = $(this);
        var menuKey = $menuEditorInstance.data('menu-key'); // glory_content[menu_key_X]
        var baseInputNameForMenu = 'glory_content[' + menuKey + ']';
        var i18n = gloryRestaurantMenuSettings.i18n; // Alias para facilidad de uso

        // --- Sortables (jQuery UI) ---
        // Sortable para Pestañas
        $menuEditorInstance.find('.glory-menu-tabs-editor').sortable({
            items: '.glory-menu-tab-item',
            handle: '.glory-sortable-handle',
            axis: 'y',
            update: function (event, ui) {
                reindexarElementos($(this), '.glory-menu-tab-item', new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[tabs\\]\\[)\\d+(\\]\\[.*)$'), 'data-tab-index');
            }
        });

        // Sortable para Secciones
        $menuEditorInstance.find('.glory-menu-sections-editor').sortable({
            items: '.glory-menu-section.postbox',
            handle: '.glory-sortable-handle', // El handle dentro del h2/hndle de la sección
            axis: 'y'
            // No se necesita reindexar secciones porque usan IDs únicos como claves, no índices numéricos.
            // Pero podrías querer guardar el orden si fuera necesario.
        });

        // Sortable para Items dentro de secciones (se activa dinámicamente)
        function activarSortableParaItems($itemsList) {
            if (!$itemsList.hasClass('ui-sortable')) {
                var baseNamePattern = new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[sections\\]\\[[^\\s\\]]+\\]\\[(?:items|packs|price_headers)\\]\\[)\\d+(\\](?:\\[.*\\])?)$');
                $itemsList.sortable({
                    items: '.glory-menu-item, .glory-price-header-item', // Selector genérico para diferentes tipos de items
                    handle: '.glory-sortable-handle',
                    axis: 'y',
                    update: function (event, ui) {
                        // Determinar el tipo de item para el patrón de reindexación correcto
                        let itemSelector = '.glory-menu-item'; // Default para standard, multi-price items, packs
                        if (ui.item.hasClass('glory-price-header-item')) {
                            itemSelector = '.glory-price-header-item';
                        }
                        reindexarElementos($(this), itemSelector, baseNamePattern, 'data-item-index');
                    }
                });
            }
        }
        // Activar para los existentes al cargar
        $menuEditorInstance.find('.glory-menu-items-list, .glory-menu-packs-list, .glory-price-headers-editor').each(function () {
            activarSortableParaItems($(this));
        });

        // --- Gestión de Pestañas (Tabs) ---
        $menuEditorInstance.on('click', '.glory-add-menu-tab', function () {
            var $tabsEditor = $(this).closest('.menu-tabs-container').find('.glory-menu-tabs-editor');
            var newTabIndex = $tabsEditor.find('.glory-menu-tab-item').length;
            var newTabId = generarIdUnico(); // Generar ID único para la pestaña

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
            if (confirm(i18n.confirmRemoveTab || 'Are you sure you want to remove this tab?')) {
                var $tabItem = $(this).closest('.glory-menu-tab-item');
                var $tabsEditor = $tabItem.parent();
                $tabItem.remove();
                reindexarElementos($tabsEditor, '.glory-menu-tab-item', new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[tabs\\]\\[)\\d+(\\]\\[.*)$'), 'data-tab-index');
            }
        });

        // --- Gestión de Secciones del Menú ---
        $menuEditorInstance.on('click', '.glory-add-menu-section', function () {
            var $sectionsEditor = $(this).closest('.menu-sections-container').find('.glory-menu-sections-editor');
            var newSectionId = generarIdUnico(); // ID único para la nueva sección
            var baseNameForNewSection = `${baseInputNameForMenu}[sections][${newSectionId}]`;

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
            $sectionsEditor.append($newSection);
            $newSection.find('.glory-section-type-select').trigger('change'); // Para cargar la UI del tipo por defecto
            $newSection.find('textarea.glory-textarea-autosize').trigger('input'); // Ajustar altura
            // Activar sortable para la lista de items si la nueva sección es de un tipo que los usa
            activarSortableParaItems($newSection.find('.glory-menu-items-list, .glory-menu-packs-list, .glory-price-headers-editor'));
        });

        $menuEditorInstance.on('input', '.glory-section-title-input', function () {
            var $input = $(this);
            var newTitle = $input.val() || i18n.newSectionLabel || 'New Section';
            $input.closest('.glory-menu-section').find('.glory-section-title-display').text(newTitle);
        });

        $menuEditorInstance.on('click', '.glory-remove-menu-section', function () {
            if (confirm(i18n.confirmRemoveSection || 'Are you sure you want to remove this entire section?')) {
                $(this).closest('.glory-menu-section.postbox').remove();
            }
        });

        // --- Gestión de Tipos de Sección (Standard, Multi-Price, Menu Pack) ---
        $menuEditorInstance.on('change', '.glory-section-type-select', function () {
            var $select = $(this);
            var selectedType = $select.val();
            var $sectionPostbox = $select.closest('.glory-menu-section.postbox');
            var sectionId = $sectionPostbox.data('section-id');
            var $contentContainer = $sectionPostbox.find('.glory-section-type-specific-content');
            var baseNameForSectionContent = $select.attr('name').replace(/\[type\]$/, ''); // Ej: glory_content[menu][sections][id_seccion]

            $contentContainer.html(''); // Limpiar contenido anterior

            if (selectedType === 'standard') {
                var html = `
                <div class="glory-menu-items-list-container">
                    <h4>${i18n.itemNameLabel || 'Items:'}</h4>
                    <div class="glory-menu-items-list"></div>
                    <button type="button" class="button glory-add-menu-item" data-item-type="standard">${i18n.addStandardItemLabel || 'Add Standard Item'}</button>
                </div>`;
                $contentContainer.html(html);
            } else if (selectedType === 'multi_price') {
                var html = `
                <div class="glory-menu-section-multi-price-container">
                    <h4>${i18n.priceColumnsLabel || 'Price Columns:'}</h4>
                    <div class="glory-price-headers-editor"></div>
                    <button type="button" class="button glory-add-price-header">${i18n.addPriceColumnHeaderLabel || 'Add Price Column Header'}</button>
                    <hr>
                    <h4>${i18n.multiPriceItemsLabel || 'Multi-Price Items:'}</h4>
                    <div class="glory-menu-items-list glory-menu-items-multi-price"></div>
                    <button type="button" class="button glory-add-menu-item" data-item-type="multi_price">${i18n.addMultiPriceItemLabel || 'Add Multi-Price Item'}</button>
                </div>`;
                $contentContainer.html(html);
            } else if (selectedType === 'menu_pack') {
                var html = `
                <div class="glory-menu-section-menu-pack-container">
                    <h4>${i18n.menuPacksLabel || 'Menu Packs / Combos:'}</h4>
                    <div class="glory-menu-packs-list"></div>
                    <button type="button" class="button glory-add-menu-item" data-item-type="menu_pack">${i18n.addMenuPackLabel || 'Add Menu Pack'}</button>
                </div>`;
                $contentContainer.html(html);
            }
            // Activar sortables para las nuevas listas que se hayan podido crear
            activarSortableParaItems($contentContainer.find('.glory-menu-items-list, .glory-menu-packs-list, .glory-price-headers-editor'));
            $contentContainer.find('textarea.glory-textarea-autosize').trigger('input');
        });

        // --- Gestión de Items (Botón genérico ".glory-add-menu-item") ---
        $menuEditorInstance.on('click', '.glory-add-menu-item', function () {
            var $button = $(this);
            var itemType = $button.data('item-type');
            var $sectionPostbox = $button.closest('.glory-menu-section.postbox');
            var baseNameForSection = $sectionPostbox
                .find('.glory-section-type-select')
                .attr('name')
                .replace(/\[type\]$/, '');

            if (itemType === 'standard') {
                var $itemsList = $sectionPostbox.find('.glory-menu-items-list');
                var newItemIndex = $itemsList.find('.glory-menu-item-standard').length;
                var baseNameForItems = `${baseNameForSection}[items]`;
                var html = `
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
                var $itemsList = $sectionPostbox.find('.glory-menu-items-multi-price');
                var newItemIndex = $itemsList.find('.glory-menu-item-multi-price').length;
                var baseNameForItems = `${baseNameForSection}[items]`;

                // Determinar las cabeceras de precio activas actualmente para este nuevo ítem
                // Esto es un poco más complejo porque las cabeceras pueden venir de un 'is_header_row' anterior
                // o de las globales de la sección. Por simplicidad al añadir, usaremos las globales
                // o las últimas definidas por un is_header_row si es fácil de acceder.
                // Para una mayor precisión, el PHP ya renderiza correctamente las existentes.
                // Al añadir nuevo, asumimos que sigue el contexto del último header o global.

                let activeHeaders = [];
                let lastHeaderRow = $itemsList.find('.glory-menu-item-row-header[data-is-header-row="true"]').last();

                if (lastHeaderRow.length) {
                    // Si hay un ítem de cabecera de fila, tomar sus textos de cabecera
                    lastHeaderRow.find('.glory-header-row-prices input[type="text"]').each(function () {
                        activeHeaders.push($(this).val() || '');
                    });
                } else {
                    // Si no, tomar las cabeceras globales de la sección
                    $sectionPostbox.find('.glory-global-price-headers-editor .glory-price-header-item input[type="text"]').each(function () {
                        activeHeaders.push($(this).val() || '');
                    });
                }

                var numPriceColumns = activeHeaders.length || 1; // Al menos 1 precio por defecto

                var pricesHtml = '';
                for (var i = 0; i < numPriceColumns; i++) {
                    let placeholder = activeHeaders[i] ? activeHeaders[i] : (i18n.priceLabelN || 'Price %d').replace('%d', i + 1);
                    pricesHtml += `<input type="text" name="${baseNameForItems}[${newItemIndex}][prices][${i}]" value="" placeholder="${placeholder}" class="regular-text"> `;
                }
                if (numPriceColumns === 0) {
                    pricesHtml = `<input type="text" name="${baseNameForItems}[${newItemIndex}][prices][0]" value="" placeholder="${i18n.priceLabelGeneral || 'Price'}" class="regular-text">`;
                }

                var html = `
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
                activarSortableParaItems($itemsList);
                $newItem.find('textarea.glory-textarea-autosize').last().trigger('input');
                // Disparar un evento para actualizar la UI de este nuevo ítem si es necesario (ej. si se pre-marca un checkbox)
                $newItem.find('.glory-item-is-header-row-checkbox, .glory-item-is-single-price-checkbox').trigger('change');
            } else if (itemType === 'menu_pack') {
                var $packsList = $sectionPostbox.find('.glory-menu-packs-list');
                var newItemIndex = $packsList.find('.glory-menu-item-pack').length;
                var baseNameForPacks = `${baseNameForSection}[packs]`;
                var html = `
                <div class="glory-menu-item glory-menu-item-pack" data-item-index="${newItemIndex}">
                    <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                    <button type="button" class="button button-small button-link-delete glory-remove-menu-item" title="${i18n.removeItem || 'Remove Item'}"><span class="dashicons dashicons-no-alt"></span></button>
                    <p><label>${i18n.packNameLabel || 'Pack Name:'}</label><br><input type="text" name="${baseNameForPacks}[${newItemIndex}][name]" value="" class="large-text"></p>
                    <p><label>${i18n.packPriceLabel || 'Pack Price:'}</label><br><input type="text" name="${baseNameForPacks}[${newItemIndex}][price]" value="" class="regular-text"></p>
                    <p><label>${i18n.packItemsLabel || 'Pack Items:'}</label><br><textarea name="${baseNameForPacks}[${newItemIndex}][items]" rows="2" class="large-text glory-textarea-autosize"></textarea></p>
                    <p><label>${i18n.packDescriptionLabel || 'Pack Description:'}</label><br><textarea name="${baseNameForPacks}[${newItemIndex}][description]" rows="1" class="large-text glory-textarea-autosize"></textarea></p>
                </div>`;
                $packsList.append(html);
                activarSortableParaItems($packsList);
                $packsList.find('textarea.glory-textarea-autosize').last().trigger('input');
            }
        });

        // Eliminar Item (genérico para cualquier tipo de item dentro de una sección)
        $menuEditorInstance.on('click', '.glory-remove-menu-item', function () {
            if (confirm(i18n.confirmRemoveItem || 'Are you sure you want to remove this item?')) {
                var $itemDiv = $(this).closest('.glory-menu-item');
                var $itemsList = $itemDiv.parent(); // .glory-menu-items-list, .glory-menu-packs-list, etc.
                var itemClass = $itemDiv
                    .attr('class')
                    .split(' ')
                    .find(cls => cls.startsWith('glory-menu-item-')); // glory-menu-item-standard, etc.
                var itemSelectorForList = '.' + itemClass;

                $itemDiv.remove();

                // Reindexar items restantes en esa lista específica
                var baseNamePatternForItems = new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[sections\\]\\[[^\\s\\]]+\\]\\[(?:items|packs)\\]\\[)\\d+(\\](?:\\[.*\\])?)$');
                reindexarElementos($itemsList, itemSelectorForList, baseNamePatternForItems, 'data-item-index');
            }
        });

        // --- Gestión de Headers de Precio (para tipo Multi-Price) ---
        $menuEditorInstance.on('click', '.glory-add-price-header', function () {
            var $button = $(this);
            var $headersEditor = $button.siblings('.glory-price-headers-editor');
            var newHeaderIndex = $headersEditor.find('.glory-price-header-item').length;
            var baseNameForSection = $button
                .closest('.glory-menu-section-multi-price-container')
                .parent()
                .parent()
                .find('.glory-section-type-select')
                .attr('name')
                .replace(/\[type\]$/, '');
            var baseNameForHeaders = `${baseNameForSection}[price_headers]`;

            var html = `
            <div class="glory-price-header-item" data-item-index="${newHeaderIndex}">
                <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                <input type="text" name="${baseNameForHeaders}[${newHeaderIndex}]" value="" placeholder="${i18n.priceHeaderPlaceholder || 'e.g., Small'}">
                <button type="button" class="button button-small button-link-delete glory-remove-price-header"><span class="dashicons dashicons-no-alt"></span></button>
            </div>`;
            $headersEditor.append(html);
            activarSortableParaItems($headersEditor);

            // Actualizar campos de precio en items multi-precio existentes
            var $itemsList = $button.closest('.glory-menu-section-multi-price-container').find('.glory-menu-items-multi-price');
            var numPriceColumns = $headersEditor.find('.glory-price-header-item').length;
            $itemsList.find('.glory-menu-item-multi-price').each(function () {
                var $item = $(this);
                var $pricesRow = $item.find('.glory-item-prices-row');
                var existingPrices = $pricesRow.find('input[type="text"]').length;
                var baseNameForItemPrices = $item
                    .find('input[name*="[name]"]')
                    .attr('name')
                    .replace(/\[name\]$/, '[prices]');

                if (numPriceColumns > existingPrices) {
                    // Añadir campos de precio
                    for (var i = existingPrices; i < numPriceColumns; i++) {
                        $pricesRow.append(`<input type="text" name="${baseNameForItemPrices}[${i}]" value="" placeholder="${(i18n.priceLabelN || 'Price %d').replace('%d', i + 1)}" class="regular-text"> `);
                    }
                } else if (numPriceColumns < existingPrices) {
                    // Quitar campos de precio sobrantes
                    $pricesRow.find('input[type="text"]:gt(' + (numPriceColumns - 1) + ')').remove();
                }
            });
        });

        $menuEditorInstance.on('click', '.glory-remove-price-header', function () {
            var $headerItem = $(this).closest('.glory-price-header-item');
            var $headersEditor = $headerItem.parent();
            var removedIndex = $headerItem.index(); // O data('item-index') si es más fiable después de reordenar
            $headerItem.remove();

            var baseNamePatternForHeaders = new RegExp('^(' + baseInputNameForMenu.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '\\[sections\\]\\[[^\\s\\]]+\\]\\[price_headers\\]\\[)\\d+(\\])$');
            reindexarElementos($headersEditor, '.glory-price-header-item', baseNamePatternForHeaders, 'data-item-index');

            // Actualizar campos de precio en items multi-precio existentes para reflejar la columna eliminada
            var $itemsList = $headersEditor.closest('.glory-menu-section-multi-price-container').find('.glory-menu-items-multi-price');
            $itemsList.find('.glory-menu-item-multi-price').each(function () {
                $(this).find('.glory-item-prices-row input[type="text"]').eq(removedIndex).remove();
                // Reindexar los inputs de precio restantes para ese item
                $(this)
                    .find('.glory-item-prices-row input[type="text"]')
                    .each(function (priceIdx, priceInput) {
                        var currentName = $(priceInput).attr('name');
                        if (currentName) {
                            var newName = currentName.replace(/\[prices\]\[\d+\]/, `[prices][${priceIdx}]`);
                            $(priceInput).attr('name', newName);
                            $(priceInput).attr('placeholder', (i18n.priceLabelN || 'Price %d').replace('%d', priceIdx + 1));
                        }
                    });
            });
        });

        // --- Disparar 'change' inicial para los selects de tipo de sección ---
        // Esto asegura que la UI correcta se muestre al cargar la página para las secciones existentes.
        $menuEditorInstance.find('.glory-menu-section.postbox').each(function () {
            var $section = $(this);
            var initialType = $section.find('.glory-section-type-select').val();
            // Renderizar el contenido específico para el tipo inicial
            var $contentContainer = $section.find('.glory-section-type-specific-content');
            var $select = $section.find('.glory-section-type-select');
            var baseNameForSectionContent = $select.attr('name').replace(/\[type\]$/, '');

            // Obtener los datos actuales del _json_fallback o directamente de los campos si ya están renderizados por PHP
            var menuDataJson = $menuEditorInstance.find('.glory-menu-structure-json-fallback').val();
            var menuData = menuDataJson ? JSON.parse(menuDataJson) : {};
            var sectionId = $section.data('section-id');
            var currentSectionData = menuData.sections && menuData.sections[sectionId] ? menuData.sections[sectionId] : {};

            if (initialType === 'standard') {
                var itemsHtml = '';
                if (currentSectionData.items) {
                    currentSectionData.items.forEach(function (item, idx) {
                        itemsHtml += `
                        <div class="glory-menu-item glory-menu-item-standard" data-item-index="${idx}">
                            <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                            <button type="button" class="button button-small button-link-delete glory-remove-menu-item" title="${i18n.removeItem || 'Remove Item'}"><span class="dashicons dashicons-no-alt"></span></button>
                            <p><label>${i18n.itemNameLabel || 'Item Name:'}</label><br><input type="text" name="${baseNameForSectionContent}[items][${idx}][name]" value="${item.name || ''}" class="large-text"></p>
                            <p><label>${i18n.itemPriceLabel || 'Item Price:'}</label><br><input type="text" name="${baseNameForSectionContent}[items][${idx}][price]" value="${item.price || ''}" class="regular-text"></p>
                            <p><label>${i18n.itemDescriptionLabel || 'Item Description:'}</label><br><textarea name="${baseNameForSectionContent}[items][${idx}][description]" rows="1" class="large-text glory-textarea-autosize">${item.description || ''}</textarea></p>
                        </div>`;
                    });
                }
                var html = `
                <div class="glory-menu-items-list-container">
                    <h4>${i18n.itemNameLabel || 'Items:'}</h4>
                    <div class="glory-menu-items-list">${itemsHtml}</div>
                    <button type="button" class="button glory-add-menu-item" data-item-type="standard">${i18n.addStandardItemLabel || 'Add Standard Item'}</button>
                </div>`;
                $contentContainer.html(html);
            } else if (initialType === 'multi_price') {
                var headersHtml = '';
                var priceHeaders = currentSectionData.price_headers || [];
                priceHeaders.forEach(function (header, idx) {
                    headersHtml += `
                    <div class="glory-price-header-item" data-item-index="${idx}">
                        <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                        <input type="text" name="${baseNameForSectionContent}[price_headers][${idx}]" value="${header || ''}" placeholder="${i18n.priceHeaderPlaceholder || 'e.g., Small'}">
                        <button type="button" class="button button-small button-link-delete glory-remove-price-header"><span class="dashicons dashicons-no-alt"></span></button>
                    </div>`;
                });

                var itemsHtml = '';
                if (currentSectionData.items) {
                    currentSectionData.items.forEach(function (item, itemIdx) {
                        var pricesForItemHtml = '';
                        var numCols = priceHeaders.length || 1;
                        for (var i = 0; i < numCols; i++) {
                            pricesForItemHtml += `<input type="text" name="${baseNameForSectionContent}[items][${itemIdx}][prices][${i}]" value="${(item.prices && item.prices[i]) || ''}" placeholder="${(i18n.priceLabelN || 'Price %d').replace('%d', i + 1)}" class="regular-text"> `;
                        }
                        if (numCols === 0) pricesForItemHtml = `<input type="text" name="${baseNameForSectionContent}[items][${itemIdx}][prices][0]" value="${(item.prices && item.prices[0]) || ''}" placeholder="${i18n.priceLabelGeneral || 'Price'}" class="regular-text">`;

                        itemsHtml += `
                        <div class="glory-menu-item glory-menu-item-multi-price" data-item-index="${itemIdx}">
                            <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                            <button type="button" class="button button-small button-link-delete glory-remove-menu-item" title="${i18n.removeItem || 'Remove Item'}"><span class="dashicons dashicons-no-alt"></span></button>
                            <p><label>${i18n.itemNameLabel || 'Item Name:'}</label><br><input type="text" name="${baseNameForSectionContent}[items][${itemIdx}][name]" value="${item.name || ''}" class="large-text"></p>
                            <div class="glory-item-prices-row">${pricesForItemHtml}</div>
                            <p><label>${i18n.itemDescriptionLabel || 'Item Description:'}</label><br><textarea name="${baseNameForSectionContent}[items][${itemIdx}][description]" rows="1" class="large-text glory-textarea-autosize">${item.description || ''}</textarea></p>
                        </div>`;
                    });
                }

                var html = `
                <div class="glory-menu-section-multi-price-container">
                    <h4>${i18n.priceColumnsLabel || 'Price Columns:'}</h4>
                    <div class="glory-price-headers-editor">${headersHtml}</div>
                    <button type="button" class="button glory-add-price-header">${i18n.addPriceColumnHeaderLabel || 'Add Price Column Header'}</button>
                    <hr>
                    <h4>${i18n.multiPriceItemsLabel || 'Multi-Price Items:'}</h4>
                    <div class="glory-menu-items-list glory-menu-items-multi-price">${itemsHtml}</div>
                    <button type="button" class="button glory-add-menu-item" data-item-type="multi_price">${i18n.addMultiPriceItemLabel || 'Add Multi-Price Item'}</button>
                </div>`;
                $contentContainer.html(html);
            } else if (initialType === 'menu_pack') {
                var packsHtml = '';
                if (currentSectionData.packs) {
                    currentSectionData.packs.forEach(function (pack, idx) {
                        packsHtml += `
                        <div class="glory-menu-item glory-menu-item-pack" data-item-index="${idx}">
                            <span class="dashicons dashicons-menu glory-sortable-handle" title="${i18n.dragToReorder || 'Drag to reorder'}"></span>
                            <button type="button" class="button button-small button-link-delete glory-remove-menu-item" title="${i18n.removeItem || 'Remove Item'}"><span class="dashicons dashicons-no-alt"></span></button>
                            <p><label>${i18n.packNameLabel || 'Pack Name:'}</label><br><input type="text" name="${baseNameForSectionContent}[packs][${idx}][name]" value="${pack.name || ''}" class="large-text"></p>
                            <p><label>${i18n.packPriceLabel || 'Pack Price:'}</label><br><input type="text" name="${baseNameForSectionContent}[packs][${idx}][price]" value="${pack.price || ''}" class="regular-text"></p>
                            <p><label>${i18n.packItemsLabel || 'Pack Items:'}</label><br><textarea name="${baseNameForSectionContent}[packs][${idx}][items]" rows="2" class="large-text glory-textarea-autosize">${pack.items || ''}</textarea></p>
                            <p><label>${i18n.packDescriptionLabel || 'Pack Description:'}</label><br><textarea name="${baseNameForSectionContent}[packs][${idx}][description]" rows="1" class="large-text glory-textarea-autosize">${pack.description || ''}</textarea></p>
                        </div>`;
                    });
                }
                var html = `
                <div class="glory-menu-section-menu-pack-container">
                    <h4>${i18n.menuPacksLabel || 'Menu Packs / Combos:'}</h4>
                    <div class="glory-menu-packs-list">${packsHtml}</div>
                    <button type="button" class="button glory-add-menu-item" data-item-type="menu_pack">${i18n.addMenuPackLabel || 'Add Menu Pack'}</button>
                </div>`;
                $contentContainer.html(html);
            }
            activarSortableParaItems($contentContainer.find('.glory-menu-items-list, .glory-menu-packs-list, .glory-price-headers-editor'));
            $contentContainer.find('textarea.glory-textarea-autosize').trigger('input');
        });

        // Evento para el checkbox "Is Header Row"
        $menuEditorInstance.on('change', '.glory-item-is-header-row-checkbox', function () {
            var $checkbox = $(this);
            var $itemDiv = $checkbox.closest('.glory-menu-item-multi-price');
            var $singlePriceCheckbox = $itemDiv.find('.glory-item-is-single-price-checkbox');
            var $priceFieldsContainer = $itemDiv.find('.glory-item-price-fields');
            var baseNameForItem = $itemDiv
                .find('.glory-item-name')
                .attr('name')
                .replace(/\[name\]$/, ''); // Ej: ...[items][0]

            if ($checkbox.is(':checked')) {
                $singlePriceCheckbox.prop('checked', false).trigger('change'); // Desmarcar el otro y disparar su evento para limpiar su UI
                $itemDiv.attr('data-is-header-row', 'true').addClass('glory-menu-item-row-header');
                $itemDiv.attr('data-is-single-price', 'false').removeClass('glory-menu-item-single-price');

                // Transformar UI a campos para definir textos de cabecera
                var headerPricesHtml = '';
                // Cuántos campos de cabecera mostrar inicialmente? Podríamos empezar con 2 o leer existentes si se está transformando.
                // Por ahora, si no hay, empezamos con 1. Si hay 'prices' en el data original, usarlos.
                var existingPrices = [];
                $priceFieldsContainer.find('.glory-item-prices-row input[type="text"]').each(function (idx) {
                    existingPrices.push($(this).val());
                });

                var numInitialHeaders = Math.max(1, existingPrices.length);

                for (var i = 0; i < numInitialHeaders; i++) {
                    headerPricesHtml += `<input type="text" name="${baseNameForItem}[prices][${i}]" value="${existingPrices[i] || ''}" placeholder="${(i18n.headerTextLabelN || 'Header Text %d').replace('%d', i + 1)}" class="regular-text"> `;
                }

                $priceFieldsContainer.html(`
            <label class="glory-item-prices-label">${i18n.columnHeadersDefinedByThisRowLabel || 'Column Headers Defined by this Row:'}</label>
            <div class="glory-item-prices-row glory-header-row-prices">${headerPricesHtml}</div>
            <button type="button" class="button button-small glory-add-header-price-field-to-row" title="${i18n.addHeaderPriceFieldTitle || 'Add another header text field'}">+</button>
        `);
                $itemDiv
                    .find('.glory-item-name')
                    .siblings('label')
                    .text(i18n.headerRowNameLabel || 'Header Row Name (HTML allowed):');
            } else {
                $itemDiv.attr('data-is-header-row', 'false').removeClass('glory-menu-item-row-header');
                // Si no está marcado y el de single price tampoco, volver a UI de multi-precio estándar
                if (!$singlePriceCheckbox.is(':checked')) {
                    // Revertir a la UI de multi-precio estándar (similar a cuando se añade uno nuevo)
                    // Necesitamos las cabeceras activas actuales para los placeholders
                    let activeHeaders = [];
                    let $itemsList = $itemDiv.closest('.glory-menu-items-multi-price');
                    let prevHeaderRow = $itemDiv.prevAll('.glory-menu-item-row-header[data-is-header-row="true"]:first');

                    if (prevHeaderRow.length) {
                        prevHeaderRow.find('.glory-header-row-prices input[type="text"]').each(function () {
                            activeHeaders.push($(this).val() || '');
                        });
                    } else {
                        $itemDiv
                            .closest('.glory-menu-section.postbox')
                            .find('.glory-global-price-headers-editor .glory-price-header-item input[type="text"]')
                            .each(function () {
                                activeHeaders.push($(this).val() || '');
                            });
                    }
                    var numPriceColumns = activeHeaders.length || 1;
                    var pricesHtml = '';
                    var existingStdPrices = []; // Podríamos intentar preservar valores si el usuario cambia de tipo
                    $priceFieldsContainer.find('.glory-item-prices-row input[type="text"]').each(function (idx) {
                        existingStdPrices.push($(this).val());
                    });

                    for (var i = 0; i < numPriceColumns; i++) {
                        let placeholder = activeHeaders[i] ? activeHeaders[i] : (i18n.priceLabelN || 'Price %d').replace('%d', i + 1);
                        pricesHtml += `<input type="text" name="${baseNameForItem}[prices][${i}]" value="${existingStdPrices[i] || ''}" placeholder="${placeholder}" class="regular-text"> `;
                    }
                    if (numPriceColumns === 0) {
                        pricesHtml = `<input type="text" name="${baseNameForItem}[prices][0]" value="${existingStdPrices[0] || ''}" placeholder="${i18n.priceLabelGeneral || 'Price'}" class="regular-text">`;
                    }

                    $priceFieldsContainer.html(`
                <label class="glory-item-prices-label">${i18n.multiPriceItemsLabel || 'Prices:'}</label>
                <div class="glory-item-prices-row glory-standard-multi-prices-row">${pricesHtml}</div>
            `);
                    $itemDiv
                        .find('.glory-item-name')
                        .siblings('label')
                        .text(i18n.itemNameLabel || 'Item Name:');
                }
            }
        });

        // Evento para el checkbox "Is Single Price"
        $menuEditorInstance.on('change', '.glory-item-is-single-price-checkbox', function () {
            var $checkbox = $(this);
            var $itemDiv = $checkbox.closest('.glory-menu-item-multi-price');
            var $headerRowCheckbox = $itemDiv.find('.glory-item-is-header-row-checkbox');
            var $priceFieldsContainer = $itemDiv.find('.glory-item-price-fields');
            var baseNameForItem = $itemDiv
                .find('.glory-item-name')
                .attr('name')
                .replace(/\[name\]$/, '');

            if ($checkbox.is(':checked')) {
                $headerRowCheckbox.prop('checked', false).trigger('change'); // Desmarcar el otro y disparar su evento
                $itemDiv.attr('data-is-single-price', 'true').addClass('glory-menu-item-single-price');
                $itemDiv.attr('data-is-header-row', 'false').removeClass('glory-menu-item-row-header');

                // Guardar el primer precio existente (si hay) para usarlo en el campo de precio único
                var firstPriceValue = $priceFieldsContainer.find('.glory-item-prices-row input[type="text"]').first().val() || '';

                $priceFieldsContainer.html(`
            <label class="glory-item-prices-label">${i18n.priceLabelGeneral || 'Price:'}</label>
            <div class="glory-item-prices-row glory-single-price-row">
                <input type="text" name="${baseNameForItem}[price]" value="${firstPriceValue}" placeholder="${i18n.priceLabelGeneral || 'Price'}" class="regular-text">
            </div>
        `);
                $itemDiv
                    .find('.glory-item-name')
                    .siblings('label')
                    .text(i18n.itemNameLabel || 'Item Name:');
            } else {
                $itemDiv.attr('data-is-single-price', 'false').removeClass('glory-menu-item-single-price');
                // Si no está marcado y el de header row tampoco, volver a UI de multi-precio estándar
                if (!$headerRowCheckbox.is(':checked')) {
                    // Esta lógica es idéntica a la del 'else' en el handler de is_header_row_checkbox
                    // Considera refactorizarla en una función separada si se vuelve muy repetitiva.
                    let activeHeaders = [];
                    let $itemsList = $itemDiv.closest('.glory-menu-items-multi-price');
                    let prevHeaderRow = $itemDiv.prevAll('.glory-menu-item-row-header[data-is-header-row="true"]:first');

                    if (prevHeaderRow.length) {
                        prevHeaderRow.find('.glory-header-row-prices input[type="text"]').each(function () {
                            activeHeaders.push($(this).val() || '');
                        });
                    } else {
                        $itemDiv
                            .closest('.glory-menu-section.postbox')
                            .find('.glory-global-price-headers-editor .glory-price-header-item input[type="text"]')
                            .each(function () {
                                activeHeaders.push($(this).val() || '');
                            });
                    }
                    var numPriceColumns = activeHeaders.length || 1;
                    var pricesHtml = '';
                    var existingSinglePrice = $priceFieldsContainer.find('.glory-single-price-row input[type="text"]').val();

                    for (var i = 0; i < numPriceColumns; i++) {
                        let placeholder = activeHeaders[i] ? activeHeaders[i] : (i18n.priceLabelN || 'Price %d').replace('%d', i + 1);
                        // Si venimos de single price, intentamos poner ese valor en el primer campo de precio múltiple
                        let value = i === 0 && existingSinglePrice ? existingSinglePrice : '';
                        pricesHtml += `<input type="text" name="${baseNameForItem}[prices][${i}]" value="${value}" placeholder="${placeholder}" class="regular-text"> `;
                    }
                    if (numPriceColumns === 0) {
                        pricesHtml = `<input type="text" name="${baseNameForItem}[prices][0]" value="${existingSinglePrice || ''}" placeholder="${i18n.priceLabelGeneral || 'Price'}" class="regular-text">`;
                    }

                    $priceFieldsContainer.html(`
                <label class="glory-item-prices-label">${i18n.multiPriceItemsLabel || 'Prices:'}</label>
                <div class="glory-item-prices-row glory-standard-multi-prices-row">${pricesHtml}</div>
            `);
                    $itemDiv
                        .find('.glory-item-name')
                        .siblings('label')
                        .text(i18n.itemNameLabel || 'Item Name:');
                }
            }
        });

        // Evento para el botón "+" para añadir campos de texto de cabecera a un ítem "Is Header Row"
        $menuEditorInstance.on('click', '.glory-add-header-price-field-to-row', function () {
            var $button = $(this);
            var $headerPricesRow = $button.siblings('.glory-header-row-prices');
            var numCurrentFields = $headerPricesRow.find('input[type="text"]').length;
            var baseNameForItem = $button
                .closest('.glory-menu-item-multi-price')
                .find('.glory-item-name')
                .attr('name')
                .replace(/\[name\]$/, '');

            $headerPricesRow.append(`<input type="text" name="${baseNameForItem}[prices][${numCurrentFields}]" value="" placeholder="${(i18n.headerTextLabelN || 'Header Text %d').replace('%d', numCurrentFields + 1)}" class="regular-text"> `);
            // Podrías añadir un botón de eliminar para cada campo de texto de cabecera también.
        });
    }); // Fin de .glory-menu-structure-admin.each()
}); // Fin de jQuery(document).ready
