<?php
// Glory/Admin/partials/restaurant-menu-admin-fields.php

// Parche temporal para cargar CSS directamente

if (!defined('ABSPATH')) {
    exit;  // Salir si se accede directamente.
}

use Glory\Class\GloryLogger;

/**
 * Renderiza la interfaz de administración para un campo de tipo 'menu_structure'.
 *
 * @param string $key La clave única del campo de menú.
 * @param array $config La configuración del campo.
 * @param mixed $current_value El valor actual de la estructura del menú (array).
 * @param string $option_input_name El nombre base para los inputs del formulario, ej: "glory_content[menu_principal]".
 */
function render_menu_structure_field_admin_html(string $key, array $config, $current_value, string $option_input_name): void
{
    // $current_value aquí es el array PHP completo de la estructura del menú
    $menu_data            = is_array($current_value) ? $current_value : ['tabs' => [], 'sections' => []];
    $tabs_data            = $menu_data['tabs'] ?? [];
    $sections_data        = $menu_data['sections'] ?? [];              // Estos son los IDs de sección como claves
    $dropdown_items_order = $menu_data['dropdown_items_order'] ?? [];  // Para el orden del dropdown



    $field_id_base = 'glory_menu_field_' . esc_attr($key);

    echo '<div class="glory-menu-structure-admin" id="' . esc_attr($field_id_base) . '_editor" data-menu-key="' . esc_attr($key) . '">';

    // Guardamos una copia del JSON original en un campo oculto.
    // El JS lo usará como fallback y para preservar datos no manejados por la UI avanzada.
    echo '<textarea 
            name="' . esc_attr($option_input_name . '[_json_fallback]') . '" 
            class="glory-menu-structure-json-fallback" 
            style="display:none;"
            aria-hidden="true">' . esc_textarea(json_encode($current_value)) . '</textarea>';

    // Contenedor para las Pestañas del Menú (Tabs)
    echo '<div class="menu-tabs-container postbox">';
    echo '<h3 class="hndle"><span>' . __('Configuración de Pestañas del Menú', 'glory') . '</span></h3>';
    echo '<div class="inside">';
    echo '<div class="glory-menu-tabs-editor">';
    if (!empty($tabs_data)) {
        foreach ($tabs_data as $tab_index => $tab) {
            render_menu_tab_item_admin_html($option_input_name, $tab_index, $tab);
        }
    }
    echo '</div>';  // .glory-menu-tabs-editor
    echo '<button type="button" class="button glory-add-menu-tab">' . __('Añadir Pestaña', 'glory') . '</button>';
    echo '</div>';  // .inside
    echo '</div>';  // .menu-tabs-container
    echo '<hr>';

    // Contenedor para las Secciones del Menú
    echo '<div class="menu-sections-container postbox">';
    echo '<h3 class="hndle"><span>' . __('Secciones del Menú', 'glory') . '</span></h3>';
    echo '<div class="inside">';
    echo '<div class="glory-menu-sections-editor">';                       // El JS buscará este contenedor para añadir secciones
    if (!empty($sections_data)) {
        foreach ($sections_data as $section_id_key => $section_content) {  // $section_id_key es el ID único de la sección
            render_menu_section_admin_html($option_input_name, $section_id_key, $section_content);
        }
    }
    echo '</div>';                                                         // .glory-menu-sections-editor
    echo '<button type="button" class="button button-primary glory-add-menu-section">' . __('Añadir Nueva Sección', 'glory') . '</button>';
    echo '</div>';                                                         // .inside
    echo '</div>';                                                         // .menu-sections-container
    echo '<hr>';

    // Opcional: UI para gestionar 'dropdown_items_order' si existe y se quiere hacer editable
    if (isset($menu_data['dropdown_items_order'])) {
        echo '<div class="menu-dropdown-order-container postbox">';
        echo '<h3 class="hndle"><span>' . __('Orden de Elementos del Desplegable (Avanzado)', 'glory') . '</span></h3>';
        echo '<div class="inside">';
        echo '<p>' . __('Define el orden de los elementos en los desplegables del menú (si aplica). Use los IDs de las secciones.', 'glory') . '</p>';
        // Un simple textarea para editar el array como JSON por ahora, o una UI más avanzada si se desea
        echo '<textarea name="' . esc_attr($option_input_name . '[dropdown_items_order_json]') . '" class="large-text code" rows="3">' . esc_textarea(json_encode($dropdown_items_order)) . '</textarea>';
        echo '<p class="description">' . __('Ingrese un array JSON de IDs de sección, ej: ["entrantes", "principales", "postres"]. Si la UI principal no gestiona esto, se tomará de aquí.', 'glory') . '</p>';
        echo '</div>';  // .inside
        echo '</div>';  // .menu-dropdown-order-container
    }

    // --- INICIO DE CÓDIGO AÑADIDO PARA EL BOTÓN DE RESTABLECER ---
    echo '<div class="glory-menu-actions" style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd;">';

    $reset_nonce_action_string = 'glory_restaurant_menu_reset_action_' . $key;
    $reset_nonce_name_string   = '_wpnonce_glory_restaurant_menu_reset_' . $key;

    // Botón de Restablecer para esta instancia de menú
    echo '<button type="submit" 
                  name="glory_reset_menu_key_action" 
                  value="' . esc_attr($key) . '" 
                  class="button button-secondary glory-reset-single-menu-button"
                  onclick="return confirm(\'' . esc_js(sprintf(__('¿Estás seguro de que quieres restablecer el menú "%s" a sus valores predeterminados? Esta acción no se puede deshacer.', 'glory'), esc_html($config['label'] ?? $key))) . '\');">'
        . esc_html__('Restablecer Este Menú a los Valores Predeterminados', 'glory')
        . '</button>';

    // Nonce específico para este botón/acción de reset
    // El cuarto parámetro 'true' hace que se imprima, el tercero 'true' para referer check.
    wp_nonce_field($reset_nonce_action_string, $reset_nonce_name_string, true, true);

    echo '</div>';  // .glory-menu-actions
    // --- FIN DE CÓDIGO AÑADIDO PARA EL BOTÓN DE RESTABLECER ---

    echo '</div>';  // .glory-menu-structure-admin
}

/**
 * Renderiza un item de pestaña para el editor de menú.
 */
function render_menu_tab_item_admin_html(string $base_menu_input_name, int $tab_index, array $tab_data): void
{
    $tab_id_val      = $tab_data['id'] ?? 'tab_id_' . $tab_index . '_' . wp_rand(100, 999);  // Asegurar un ID único si no existe
    $tab_text_val    = $tab_data['text'] ?? '';
    $tab_visible_val = $tab_data['visible_in_tabs'] ?? true;                                 // Por defecto visible

    $base_tab_name_attr = esc_attr($base_menu_input_name . '[tabs][' . $tab_index . ']');
    ?>
    <div class="glory-menu-tab-item" data-tab-index="<?php echo esc_attr($tab_index); ?>">
        <span class="dashicons dashicons-menu glory-sortable-handle" title="<?php esc_attr_e('Arrastrar para reordenar', 'glory'); ?>"></span>
        <input type="text" name="<?php echo $base_tab_name_attr . '[id]'; ?>" value="<?php echo esc_attr($tab_id_val); ?>" placeholder="<?php esc_attr_e('ID de Pestaña (ej: platos_principales)', 'glory'); ?>" class="regular-text glory-tab-id-input">
        <input type="text" name="<?php echo $base_tab_name_attr . '[text]'; ?>" value="<?php echo esc_attr($tab_text_val); ?>" placeholder="<?php esc_attr_e('Texto de Pestaña (ej: Platos Principales)', 'glory'); ?>" class="regular-text glory-tab-text-input">
        <label class="glory-tab-visibility">
            <input type="checkbox" name="<?php echo $base_tab_name_attr . '[visible_in_tabs]'; ?>" value="1" <?php checked($tab_visible_val); ?>>
            <?php _e('Visible en la barra de pestañas', 'glory'); ?>
        </label>
        <button type="button" class="button button-small button-link-delete glory-remove-menu-tab" title="<?php esc_attr_e('Eliminar Pestaña', 'glory'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>
<?php
}

/**
 * Renderiza una sección completa del menú para el editor.
 */
function render_menu_section_admin_html(string $base_menu_input_name, string $section_id, array $section_data): void
{
    $section_title       = $section_data['title'] ?? '';
    $section_description = $section_data['description'] ?? '';
    $section_type        = $section_data['type'] ?? 'standard';

    // El nombre base para esta sección será $base_menu_input_name . '[sections][' . $section_id . ']'
    $base_section_name_attr = esc_attr($base_menu_input_name . '[sections][' . $section_id . ']');
    ?>
    <div class="glory-menu-section postbox" data-section-id="<?php echo esc_attr($section_id); ?>">
        <div class="postbox-header">
            <h2 class="hndle">
                <!-- No debería existir porque manejar la ubicación de las secciones es algo que se maneja en la configuración de pestañas -->
                <span class="dashicons dashicons-menu glory-sortable-handle" title="<?php esc_attr_e('Arrastrar para reordenar sección', 'glory'); ?>" style="display: none;"></span>
                <span class="glory-section-title-display"><?php echo esc_html($section_title ?: __('Nueva Sección', 'glory')); ?></span>
                (<label for="<?php echo esc_attr($base_section_name_attr . '_id_input_field'); ?>" style="font-weight: normal; font-size: inherit;"><?php _e('ID:', 'glory'); ?> </label>
                <input type="text"
                       id="<?php echo esc_attr($base_section_name_attr . '_id_input_field'); ?>"
                       name="<?php echo esc_attr($base_section_name_attr . '[id_value]'); ?>"
                       value="<?php echo esc_attr($section_id); ?>"
                       class="glory-section-id-input regular-text"
                       style="width: 100px; margin-left: 3px; font-weight: normal; display: inline-block; padding: 2px 4px; line-height: normal; height: auto; font-size: 13px;"
                       placeholder="<?php esc_attr_e('ID único', 'glory'); ?>">)
            </h2>
            <div class="handle-actions hide-if-no-js">
                <button type="button" class="button-link glory-remove-menu-section" title="<?php esc_attr_e('Eliminar Sección', 'glory'); ?>">
                    <span class="dashicons dashicons-trash"></span><span class="screen-reader-text"><?php _e('Eliminar Sección', 'glory'); ?></span>
                </button>
                <button type="button" class="handlediv" aria-expanded="true">
                    <span class="screen-reader-text"><?php printf(__('Alternar panel: %s', 'glory'), esc_html($section_title)); ?></span>
                    <span class="toggle-indicator" aria-hidden="true"></span>
                </button>
            </div>
        </div>
        <div class="inside">
            <p>
                <label for="<?php echo $base_section_name_attr . '[title]'; ?>"><?php _e('Título de la Sección:', 'glory'); ?></label><br>
                <input type="text" id="<?php echo $base_section_name_attr . '[title]'; ?>" name="<?php echo $base_section_name_attr . '[title]'; ?>" value="<?php echo esc_attr($section_title); ?>" class="large-text glory-section-title-input" placeholder="<?php esc_attr_e('Título visible de la sección', 'glory'); ?>">
            </p>
            <p>
                <label for="<?php echo $base_section_name_attr . '[description]'; ?>"><?php _e('Descripción de la Sección (opcional):', 'glory'); ?></label><br>
                <textarea id="<?php echo $base_section_name_attr . '[description]'; ?>" name="<?php echo $base_section_name_attr . '[description]'; ?>" rows="2" class="large-text"><?php echo esc_textarea($section_description); ?></textarea>
            </p>
            <p>
                <label for="<?php echo $base_section_name_attr . '[type]'; ?>"><?php _e('Tipo de Sección:', 'glory'); ?></label><br>
                <select id="<?php echo $base_section_name_attr . '[type]'; ?>" name="<?php echo $base_section_name_attr . '[type]'; ?>" class="glory-section-type-select">
                    <option value="standard" <?php selected($section_type, 'standard'); ?>><?php _e('Elementos Estándar (Nombre, Precio, Descripción)', 'glory'); ?></option>
                    <option value="multi_price" <?php selected($section_type, 'multi_price'); ?>><?php _e('Elementos Multi-Precio (ej: Pequeño/Mediano/Grande)', 'glory'); ?></option>
                    <option value="menu_pack" <?php selected($section_type, 'menu_pack'); ?>><?php _e('Paquetes de Menú (ej: Ofertas Combinadas)', 'glory'); ?></option>
                    <!-- Add other types if needed -->
                </select>
            </p>

            <div class="glory-section-type-specific-content">
                <?php
                // Renderizar contenido específico según el tipo de sección
                // El JS también manejará el cambio de UI, pero esto renderiza el estado actual.
                if ($section_type === 'standard') {
                    echo '<div class="glory-menu-items-list-container">';  // Contenedor para la lista de items
                    echo '<h4>' . __('Elementos:', 'glory') . '</h4>';
                    echo '<div class="glory-menu-items-list">';            // El JS opera sobre este div para items
                    $items = $section_data['items'] ?? [];
                    if (!empty($items)) {
                        foreach ($items as $item_idx => $item_data_loop) {
                            render_menu_item_standard_admin_html($base_section_name_attr . '[items]', $item_idx, $item_data_loop);
                        }
                    }
                    echo '</div>';                                         // .glory-menu-items-list
                    echo '<button type="button" class="button glory-add-menu-item" data-section-id="' . esc_attr($section_id) . '" data-item-type="standard">' . __('Añadir Elemento Estándar', 'glory') . '</button>';
                    echo '</div>';                                         // .glory-menu-items-list-container
                } elseif ($section_type === 'multi_price') {
                    render_menu_section_multi_price_admin_html($base_section_name_attr, $section_data);
                } elseif ($section_type === 'menu_pack') {
                    render_menu_section_menu_pack_admin_html($base_section_name_attr, $section_data);
                }
                ?>
            </div>
        </div>
    </div>
<?php
}

/**
 * Renderiza un ítem estándar (nombre, precio, descripción) para una sección de menú.
 */
function render_menu_item_standard_admin_html(string $base_items_input_name, int $item_index, array $item_data): void
{
    $item_name           = $item_data['name'] ?? '';
    $item_price          = $item_data['price'] ?? '';
    $item_description    = $item_data['description'] ?? '';
    $base_item_name_attr = esc_attr($base_items_input_name . '[' . $item_index . ']');
    ?>
    <div class="glory-menu-item glory-menu-item-standard" data-item-index="<?php echo esc_attr($item_index); ?>">
        <span class="dashicons dashicons-menu glory-sortable-handle" title="<?php esc_attr_e('Arrastrar para reordenar elemento', 'glory'); ?>"></span>
        <button type="button" class="button button-small button-link-delete glory-remove-menu-item" title="<?php esc_attr_e('Eliminar Elemento', 'glory'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
        <div class="divPanelP">
            <p>
                <label><?php _e('Nombre del Elemento:', 'glory'); ?></label><br>
                <input type="text" name="<?php echo $base_item_name_attr . '[name]'; ?>" value="<?php echo esc_attr($item_name); ?>" class="large-text">
            </p>
            <p>
                <label><?php _e('Precio del Elemento:', 'glory'); ?></label><br>
                <input type="text" name="<?php echo $base_item_name_attr . '[price]'; ?>" value="<?php echo esc_attr($item_price); ?>" class="regular-text">
            </p>
            <p style="margin-bottom: 0px;">
                <label><?php _e('Descripción del Elemento (opcional):', 'glory'); ?></label><br>
                <textarea name="<?php echo $base_item_name_attr . '[description]'; ?>" style="min-height: 30px;" rows="1" class="large-text"><?php echo esc_textarea($item_description); ?></textarea>
            </p>
        </div>
    </div>
<?php
}

/**
 * Renderiza la UI para una sección de tipo 'multi_price'.
 */
function render_menu_section_multi_price_admin_html(string $base_section_name_attr, array $section_data): void
{
    $global_price_headers = $section_data['price_headers'] ?? [];  // Estas son las cabeceras definidas a nivel de sección
    $items                = $section_data['items'] ?? [];

    // GloryLogger::info("Rendering multi_price section. Base name: {$base_section_name_attr}. Section Data: " . print_r($section_data, true));

    ?>
    <div class="glory-menu-section-multi-price-container">
        <h4><?php _e('Columnas de Precios Globales (para esta sección si no se usan sub-encabezados):', 'glory'); ?></h4>
        <div class="glory-price-headers-editor glory-global-price-headers-editor">
            <?php if (!empty($global_price_headers)): ?>
                <?php foreach ($global_price_headers as $header_idx => $header_text): ?>
                    <div class="glory-price-header-item">
                        <input type="text" name="<?php echo esc_attr($base_section_name_attr . '[price_headers][' . $header_idx . ']'); ?>" value="<?php echo esc_attr($header_text); ?>" placeholder="<?php esc_attr_e('ej: Pequeño', 'glory'); ?>">
                        <button type="button" class="button button-small button-link-delete glory-remove-price-header"><span class="dashicons dashicons-no-alt"></span></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="button" class="button glory-add-price-header" data-target-editor="global"><?php _e('Añadir Encabezado de Columna de Precio Global', 'glory'); ?></button>
        <hr>
        <h4><?php _e('Elementos (pueden incluir sub-encabezados, elementos de precio único o elementos multi-precio):', 'glory'); ?></h4>

        <div class="glory-menu-items-list glory-menu-items-multi-price">
            <?php
            $current_item_specific_price_headers = $global_price_headers;

            if (!empty($items)) {
                foreach ($items as $item_idx => $item_data_loop) {
                    if (isset($item_data_loop['is_header_row']) && $item_data_loop['is_header_row'] === true) {
                        $current_item_specific_price_headers = $item_data_loop['prices'] ?? [];
                    }

                    $num_columns_for_this_item = count($current_item_specific_price_headers);
                    if (isset($item_data_loop['is_single_price']) && $item_data_loop['is_single_price'] === true) {
                        $num_columns_for_this_item = 1;
                    } elseif (isset($item_data_loop['is_header_row']) && $item_data_loop['is_header_row'] === true) {
                        $num_columns_for_this_item = count($item_data_loop['prices'] ?? []);
                    }

                    render_menu_item_multi_price_admin_html(
                        $base_section_name_attr . '[items]',
                        $item_idx,
                        $item_data_loop,
                        $num_columns_for_this_item,
                        $current_item_specific_price_headers
                    );
                }
            }
            ?>
        </div>

        <button type="button" class="button glory-add-menu-item" data-item-type="multi_price"><?php _e('Añadir Elemento a esta Sección', 'glory'); ?></button>
        <p class="description">
            <?php _e('Usa "Añadir Elemento" para agregar elementos multi-precio estándar, elementos de precio único o encabezados de fila. El tipo de elemento se determinará por los campos que completes. Para un encabezado de fila, usa HTML en el nombre (ej: <b>Encabezado</b>), completa sus "precios" como textos de encabezado y marca "Es Fila de Encabezado". Para precio único, completa el nombre y "Precio 1" y marca "Es Elemento de Precio Único".', 'glory'); ?>
        </p>
    </div>
<?php
}

/**
 * Renderiza un ítem para una sección 'multi_price'.
 */
function render_menu_item_multi_price_admin_html(string $base_items_input_name, int $item_index, array $item_data, int $num_price_columns_for_item, array $active_price_headers_texts = []): void
{
    $item_name           = $item_data['name'] ?? '';
    $item_description    = $item_data['description'] ?? '';
    $base_item_name_attr = esc_attr($base_items_input_name . '[' . $item_index . ']');

    $is_header_row   = isset($item_data['is_header_row']) && $item_data['is_header_row'] === true;
    $is_single_price = isset($item_data['is_single_price']) && $item_data['is_single_price'] === true;

    ?>
    <div class="glory-menu-item glory-menu-item-multi-price <?php echo $is_header_row ? 'glory-menu-item-row-header' : ''; ?> <?php echo $is_single_price ? 'glory-menu-item-single-price' : ''; ?>" data-item-index="<?php echo esc_attr($item_index); ?>" data-is-header-row="<?php echo $is_header_row ? 'true' : 'false'; ?>" data-is-single-price="<?php echo $is_single_price ? 'true' : 'false'; ?>">

        <span class="dashicons dashicons-menu glory-sortable-handle"></span>
        <button type="button" class="button button-small button-link-delete glory-remove-menu-item"><span class="dashicons dashicons-no-alt"></span></button>

        <p>
            <label><?php echo $is_header_row ? __('Nombre de Fila de Encabezado (HTML permitido):', 'glory') : __('Nombre del Elemento:', 'glory'); ?></label><br>
            <input type="text" name="<?php echo $base_item_name_attr . '[name]'; ?>" value="<?php
    echo esc_attr($item_name);  // Para is_header_row, el guardado usará wp_kses
    ?>" class="large-text glory-item-name">
        </p>

        <div class="glory-item-price-fields">
            <?php if ($is_header_row): ?>
                <label><?php _e('Encabezados de Columna Definidos por esta Fila (estos textos se usarán para elementos subsiguientes):', 'glory'); ?></label>
                <div class="glory-item-prices-row glory-header-row-prices">
                    <?php
                    $header_item_prices         = $item_data['prices'] ?? [];
                    // $num_price_columns_for_item ya se calcula correctamente antes de llamar a esta función.
                    // Representa cuántos campos de 'prices' existen para esta fila de cabecera.
                    $num_headers_defined_by_row = $num_price_columns_for_item;

                    for ($i = 0; $i < $num_headers_defined_by_row; $i++):
                        ?>
                        <div class="glory-header-price-field-wrapper" style="display: flex; align-items: center;">
                            <input type="text" name="<?php echo $base_item_name_attr . '[prices][' . $i . ']'; ?>" value="<?php echo esc_attr($header_item_prices[$i] ?? ''); ?>" placeholder="<?php printf(esc_attr__('Texto de Encabezado %d', 'glory'), $i + 1); ?>" class="regular-text" style="flex-grow: 1; margin-right: 5px;">
                            <button type="button" class="button button-small button-link-delete glory-remove-header-price-field-from-row" title="<?php esc_attr_e('Eliminar este campo de encabezado', 'glory'); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                    <?php endfor; ?>
                    <!-- Botón para añadir más campos de texto de cabecera dinámicamente si es necesario -->
                    <button type="button" class="button button-small glory-add-header-price-field-to-row" title="<?php esc_attr_e('Añadir otro campo de texto de encabezado para esta fila', 'glory'); ?>">+</button>
                </div>

            <?php elseif ($is_single_price): ?>
                <label><?php _e('Precio:', 'glory'); ?></label>
                <div class="glory-item-prices-row glory-single-price-row">
                    <input type="text" name="<?php echo $base_item_name_attr . '[price]'; ?>" value="<?php echo esc_attr($item_data['price'] ?? ''); ?>" placeholder="<?php esc_attr_e('Precio', 'glory'); ?>" class="regular-text">
                </div>

            <?php
    else:  // Ítem multi-precio estándar
        ?>
                <label><?php _e('Precios (correspondientes a los encabezados de columna activos):', 'glory'); ?></label>
                <div class="glory-item-prices-row glory-standard-multi-prices-row">
                    <?php
                    $standard_item_prices = $item_data['prices'] ?? [];
                    // $num_price_columns_for_item viene de count($active_price_headers_texts) en la función llamante
                    for ($i = 0; $i < $num_price_columns_for_item; $i++):
                        $placeholder_text = isset($active_price_headers_texts[$i]) && !empty($active_price_headers_texts[$i]) ? $active_price_headers_texts[$i] : sprintf(__('Precio %d', 'glory'), $i + 1);
                        ?>
                        <input type="text" name="<?php echo $base_item_name_attr . '[prices][' . $i . ']'; ?>" value="<?php echo esc_attr($standard_item_prices[$i] ?? ''); ?>" placeholder="<?php echo esc_attr($placeholder_text); ?>" class="regular-text">
                    <?php endfor; ?>
                    <?php
                    if ($num_price_columns_for_item === 0):  // Fallback si no hay cabeceras activas, al menos un campo
                        ?>
                        <input type="text" name="<?php echo $base_item_name_attr . '[prices][0]'; ?>" value="<?php echo esc_attr($standard_item_prices[0] ?? ''); ?>" placeholder="<?php esc_attr_e('Precio', 'glory'); ?>" class="regular-text">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <p>
            <label>
                <input type="checkbox" class="glory-item-is-header-row-checkbox" name="<?php echo $base_item_name_attr . '[is_header_row]'; ?>" value="1" <?php checked($is_header_row); ?>>
                <?php _e('Es Fila de Encabezado (define nuevas columnas de precio para elementos inferiores)', 'glory'); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" class="glory-item-is-single-price-checkbox" name="<?php echo $base_item_name_attr . '[is_single_price]'; ?>" value="1" <?php checked($is_single_price); ?>>
                <?php _e('Es Elemento de Precio Único (ignora columnas, usa un campo de precio)', 'glory'); ?>
            </label>
        </p>

        <p>
            <label><?php _e('Descripción del Elemento (opcional):', 'glory'); ?></label><br>
            <textarea name="<?php echo $base_item_name_attr . '[description]'; ?>" rows="1" class="large-text glory-textarea-autosize"><?php echo esc_textarea($item_description); ?></textarea>
        </p>
    </div>
<?php
}

/**
 * Renderiza la UI para una sección de tipo 'menu_pack'.
 */
function render_menu_section_menu_pack_admin_html(string $base_section_name_attr, array $section_data): void
{
    $packs = $section_data['packs'] ?? [];
    ?>
    <div class="glory-menu-section-menu-pack-container">
        <h4><?php _e('Paquetes de Menú / Combos:', 'glory'); ?></h4>
        <div class="glory-menu-packs-list">
            <?php
            if (!empty($packs)) {
                foreach ($packs as $pack_idx => $pack_data_loop) {
                    render_menu_pack_item_admin_html($base_section_name_attr . '[packs]', $pack_idx, $pack_data_loop);
                }
            }
            ?>
        </div>
        <button type="button" class="button glory-add-menu-pack" data-base-packs-input-name="<?php echo esc_attr($base_section_name_attr . '[packs]'); ?>"><?php _e('Añadir Paquete de Menú', 'glory'); ?></button>
    </div>
<?php
}

/**
 * Renderiza un ítem de "pack de menú".
 */
function render_menu_pack_item_admin_html(string $base_packs_input_name, int $pack_index, array $pack_data): void
{
    // Claves correctas según la estructura JSON y el ejemplo de datos:
    // 'pack_title', 'pack_price', 'pack_description', 'details' (array)
    $pack_title_val       = $pack_data['pack_title'] ?? '';
    $pack_price_val       = $pack_data['pack_price'] ?? '';
    $pack_description_val = $pack_data['pack_description'] ?? '';
    $pack_details_val     = $pack_data['details'] ?? [];  // Array de detalles

    $base_pack_name_attr = esc_attr($base_packs_input_name . '[' . $pack_index . ']');
    ?>
    <div class="glory-menu-item glory-menu-item-pack" data-item-index="<?php echo esc_attr($pack_index); ?>">
        <span class="dashicons dashicons-menu glory-sortable-handle" title="<?php esc_attr_e('Arrastrar para reordenar paquete', 'glory'); ?>"></span>
        <button type="button" class="button button-small button-link-delete glory-remove-menu-item" title="<?php esc_attr_e('Eliminar Paquete', 'glory'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>

        <p>
            <label><?php _e('Título del Paquete:', 'glory'); ?></label><br>
            <input type="text" name="<?php echo $base_pack_name_attr . '[pack_title]'; ?>" value="<?php echo esc_attr($pack_title_val); ?>" class="large-text">
        </p>
        <p>
            <label><?php _e('Precio del Paquete:', 'glory'); ?></label><br>
            <input type="text" name="<?php echo $base_pack_name_attr . '[pack_price]'; ?>" value="<?php echo esc_attr($pack_price_val); ?>" class="regular-text">
        </p>
        <p>
            <label><?php _e('Descripción del Paquete (opcional):', 'glory'); ?></label><br>
            <textarea name="<?php echo $base_pack_name_attr . '[pack_description]'; ?>" rows="1" class="large-text glory-textarea-autosize"><?php echo esc_textarea($pack_description_val); ?></textarea>
        </p>

        <h4><?php _e('Detalles del Paquete (ej: "Primer plato a elegir", "Plato 1", "Plato 2"):', 'glory'); ?></h4>
        <div class="glory-menu-pack-details-list">
            <?php
            if (!empty($pack_details_val)) {
                foreach ($pack_details_val as $detail_idx => $detail_data) {
                    // Llamamos a la nueva función para renderizar cada detalle
                    render_menu_pack_detail_item_admin_html($base_pack_name_attr . '[details]', $detail_idx, $detail_data);
                }
            }
            ?>
        </div>
        <button type="button" class="button glory-add-menu-pack-detail" data-detail-type="item" data-pack-index="<?php echo esc_attr($pack_index); ?>"><?php _e('Añadir Detalle de Elemento', 'glory'); ?></button>
        <button type="button" class="button glory-add-menu-pack-detail" data-detail-type="heading" data-pack-index="<?php echo esc_attr($pack_index); ?>"><?php _e('Añadir Detalle de Encabezado', 'glory'); ?></button>
    </div>
<?php
}

function render_menu_pack_detail_item_admin_html(string $base_details_input_name, int $detail_index, array $detail_data): void
{
    $detail_type_val = $detail_data['type'] ?? 'item';  // 'item' por defecto si no se especifica
    $detail_text_val = $detail_data['text'] ?? '';

    $base_detail_name_attr = esc_attr($base_details_input_name . '[' . $detail_index . ']');
    $item_class            = 'glory-menu-pack-detail-item glory-menu-pack-detail-' . esc_attr($detail_type_val);
    ?>
    <div class="<?php echo $item_class; ?>" data-detail-index="<?php echo esc_attr($detail_index); ?>" data-detail-type="<?php echo esc_attr($detail_type_val); ?>">
        <span class="dashicons dashicons-menu glory-sortable-handle" title="<?php esc_attr_e('Arrastrar para reordenar detalle', 'glory'); ?>"></span>

        <input type="hidden" name="<?php echo $base_detail_name_attr . '[type]'; ?>" value="<?php echo esc_attr($detail_type_val); ?>">

        <input type="text" name="<?php echo $base_detail_name_attr . '[text]'; ?>" value="<?php echo esc_attr($detail_text_val); ?>"
            placeholder="<?php echo $detail_type_val === 'heading' ? esc_attr__('Texto de encabezado (ej: Primeros Platos)', 'glory') : esc_attr__('Texto del elemento (ej: Ensalada)', 'glory'); ?>"
            class="large-text glory-pack-detail-text-input">

        <button type="button" class="button button-small button-link-delete glory-remove-menu-pack-detail" title="<?php esc_attr_e('Eliminar Detalle', 'glory'); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>
<?php
}
