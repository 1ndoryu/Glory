<?php
// Glory/Admin/RestaurantMenuAdminPanel.php
namespace Glory\Admin;

use Glory\Class\ContentManager;
use Glory\Class\GloryLogger;


class RestaurantMenuAdminPanel
{
    //private static string $menu_slug_parent = 'glory-content-manager';
    private static string $menu_slug = 'glory-restaurant-menu';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'add_admin_menu_page']);
        add_action('admin_init', [self::class, 'register_settings_and_handle_save']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
    }

    public static function add_admin_menu_page(): void
    {
        // Cambiado a add_menu_page para que sea un elemento de menú de nivel superior
        add_menu_page(
            __('Restaurant Menu', 'glory'), // Título de la página
            __('Restaurant Menu', 'glory'), // Título del menú
            'manage_options',               // Capacidad requerida
            self::$menu_slug,               // Slug del menú
            [self::class, 'render_admin_page_html'], // Función para renderizar el contenido
            'dashicons-restaurant',         // Icono (puedes elegir otro de https://developer.wordpress.org/resource/dashicons/)
            31                              // Posición en el menú (ej. justo después de Glory Content si era 30)
        );
    }


    public static function enqueue_admin_assets(string $hook_suffix): void
    {
        // El hook para una página de menú de nivel superior es: toplevel_page_{menu_slug}
        $expected_hook_suffix = 'toplevel_page_' . self::$menu_slug;

        if ($hook_suffix !== $expected_hook_suffix) {
            // GloryLogger::info("RestaurantMenuAdminPanel enqueued_assets: Hook '{$hook_suffix}' does not match expected '{$expected_hook_suffix}'. Skipping.");
            return;
        }
        // GloryLogger::info("RestaurantMenuAdminPanel enqueued_assets: Correct hook '{$hook_suffix}'. Enqueuing assets.");

        wp_enqueue_media();

        $theme_uri  = get_stylesheet_directory_uri();
        $theme_path = get_stylesheet_directory();

        // === CSS ===
        $css_general_handle = 'glory-content-admin-panel-style';
        $css_general_relative = '/Glory/assets/css/content-admin-panel.css';
        $css_general_file_url = $theme_uri . $css_general_relative;
        $css_general_file_path = $theme_path . $css_general_relative;

        if (file_exists($css_general_file_path)) {
            wp_enqueue_style(
                $css_general_handle,
                $css_general_file_url,
                [],
                filemtime($css_general_file_path)
            );
        }

        // === JAVASCRIPT ===
        $js_handle          = 'glory-restaurant-menu-admin-script';
        $js_filename        = 'restaurant-menu-admin.js';
        $js_relative        = "/Glory/assets/js/{$js_filename}";
        $js_file_url        = $theme_uri . $js_relative;
        $js_file_systempath = $theme_path . $js_relative;

        if (file_exists($js_file_systempath) && is_readable($js_file_systempath)) {
            wp_enqueue_script(
                $js_handle,
                $js_file_url,
                ['jquery', 'jquery-ui-sortable', 'wp-util'],
                filemtime($js_file_systempath),
                true
            );

            wp_localize_script($js_handle, 'gloryRestaurantMenuSettings', [
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('glory_restaurant_menu_ajax_nonce'),
                'menuSlug'  => self::$menu_slug,
                'i18n'      => [
                    // ... (todas las traducciones que ya tenías) ...
                    'removeItem' => __('Remove Item', 'glory'),
                    'removeSection' => __('Remove Section', 'glory'),
                    'removeTab' => __('Remove Tab', 'glory'),
                    'confirmRemoveSection' => __('Are you sure you want to remove this entire section?', 'glory'),
                    'confirmRemoveTab' => __('Are you sure you want to remove this tab?', 'glory'),
                    'confirmRemoveItem' => __('Are you sure you want to remove this item?', 'glory'),
                    'dragToReorder' => __('Drag to reorder', 'glory'),
                    'newTabLabel' => __('New Tab', 'glory'),
                    'tabIdPlaceholder' => __('Tab ID (e.g., main_courses)', 'glory'),
                    'tabTextPlaceholder' => __('Tab Text (e.g., Main Courses)', 'glory'),
                    'visibleInTabBarLabel' => __('Visible in main tab bar', 'glory'),
                    'newSectionLabel' => __('New Section', 'glory'),
                    'sectionTitleLabel' => __('Section Title:', 'glory'),
                    'sectionDescriptionLabel' => __('Section Description (optional):', 'glory'),
                    'sectionTypeLabel' => __('Section Type:', 'glory'),
                    'sectionTypeStandard' => __('Standard Items (Name, Price, Description)', 'glory'),
                    'sectionTypeMultiPrice' => __('Multi-Price Items (e.g., Small/Medium/Large)', 'glory'),
                    'sectionTypeMenuPack' => __('Menu Packs (e.g., Combo Deals)', 'glory'),
                    'addItemToSectionLabel' => __('Add Item to this Section', 'glory'),
                    'addStandardItemLabel' => __('Add Standard Item', 'glory'),
                    'itemNameLabel' => __('Item Name:', 'glory'),
                    'itemPriceLabel' => __('Item Price:', 'glory'),
                    'itemDescriptionLabel' => __('Item Description (optional):', 'glory'),
                    'priceColumnsLabel' => __('Price Columns:', 'glory'),
                    'addPriceColumnHeaderLabel' => __('Add Price Column Header', 'glory'),
                    'priceHeaderPlaceholder' => __('e.g., Small', 'glory'),
                    'multiPriceItemsLabel' => __('Multi-Price Items:', 'glory'),
                    'addMultiPriceItemLabel' => __('Add Multi-Price Item', 'glory'),
                    'priceLabelN' => __('Price %d', 'glory'),
                    'priceLabelGeneral' => __('Price', 'glory'),
                    'menuPacksLabel' => __('Menu Packs / Combos:', 'glory'),
                    'addMenuPackLabel' => __('Add Menu Pack', 'glory'),
                    'packNameLabel' => __('Pack Name:', 'glory'),
                    'packPriceLabel' => __('Pack Price:', 'glory'),
                    'packItemsLabel' => __('Pack Items (e.g., "Burger + Fries + Drink"):', 'glory'),
                    'packDescriptionLabel' => __('Pack Description (optional):', 'glory'),
                    'multiPriceUIPlaceholder' => __('Multi-price item editor UI will be implemented later.', 'glory'),
                    'menuPackUIPlaceholder' => __('Menu pack editor UI will be implemented later.', 'glory'),
                    // Nuevas traducciones para la UI de items multi-precio generada por JS
                    'isHeaderRowLabel' => __('Is Header Row (defines new price columns for items below)', 'glory'),
                    'isSinglePriceLabel' => __('Is Single Price Item (ignores columns, uses one price field)', 'glory'),
                    'columnHeadersDefinedByThisRowLabel' => __('Column Headers Defined by this Row (these texts will be used for subsequent items):', 'glory'),
                    'headerRowNameLabel' => __('Header Row Name (HTML allowed):', 'glory'), // Usado en el JS al cambiar a is_header_row
                    'headerTextLabelN' => __('Header Text %d', 'glory'),
                    'addHeaderPriceFieldTitle' => __('Add another header text field for this row', 'glory'),
                ],
            ]);
        } else {
            GloryLogger::info("RestaurantMenuAdminPanel: JS file not found or not readable at {$js_file_systempath}");
        }
    }

    public static function register_settings_and_handle_save(): void
    {
        // Primero, verificar si es una acción de guardado de menú
        if (
            isset($_POST['action']) && $_POST['action'] === 'glory_save_restaurant_menu' &&
            isset($_POST['_wpnonce_glory_restaurant_menu_save']) &&
            !isset($_POST['glory_reset_menu_key_action']) // Asegurarse de que no es una acción de reset
        ) {
            if (wp_verify_nonce($_POST['_wpnonce_glory_restaurant_menu_save'], 'glory_restaurant_menu_save_action')) {
                self::handle_save_menu_data();
            } else {
                GloryLogger::error("RestaurantMenuAdminPanel: Nonce verification FAILED for restaurant menu save.");
                wp_die(__('Nonce verification failed for save!', 'glory'), __('Error', 'glory'), ['response' => 403]);
            }
        }
        // Luego, verificar si es una acción de restablecimiento de un menú específico
        elseif (isset($_POST['glory_reset_menu_key_action']) && !empty($_POST['glory_reset_menu_key_action'])) {
            $menu_key_to_reset = sanitize_key($_POST['glory_reset_menu_key_action']);
            $nonce_action_string = 'glory_restaurant_menu_reset_action_' . $menu_key_to_reset;
            $nonce_name_string = '_wpnonce_glory_restaurant_menu_reset_' . $menu_key_to_reset;

            if (isset($_POST[$nonce_name_string]) && wp_verify_nonce($_POST[$nonce_name_string], $nonce_action_string)) {
                self::handle_reset_single_menu_data($menu_key_to_reset);
            } else {
                GloryLogger::error("RestaurantMenuAdminPanel: Nonce verification FAILED for restaurant menu reset on key '{$menu_key_to_reset}'.");
                wp_die(__('Nonce verification failed for reset!', 'glory'), __('Error', 'glory'), ['response' => 403]);
            }
        }
    }

    private static function handle_save_menu_data(): void
    {
        if (!current_user_can('manage_options')) {
            GloryLogger::error("RestaurantMenuAdminPanel: User without 'manage_options' tried to save menu data.");
            wp_die(__('You do not have sufficient permissions to access this page.', 'glory'));
        }

        $all_registered_fields = ContentManager::getRegisteredContentFields();
        $posted_options = $_POST['glory_content'] ?? [];
        $processed_at_least_one_menu = false;

        foreach ($all_registered_fields as $key => $config) {
            if (!isset($config['type']) || $config['type'] !== 'menu_structure') {
                continue;
            }

            $option_name = ContentManager::OPTION_PREFIX . $key;
            $submitted_structure_for_key = $posted_options[$key] ?? [];

            // GloryLogger::info("RestaurantMenuAdminPanel: Saving menu_structure for key '{$key}'. Raw POST for this key: " . print_r($submitted_structure_for_key, true));

            $reconstructed_menu = [
                'tabs' => [],
                'sections' => [],
                // Otros campos de nivel superior se añadirán después
            ];

            // 1. Reconstruir Pestañas (Tabs)
            if (isset($submitted_structure_for_key['tabs']) && is_array($submitted_structure_for_key['tabs'])) {
                foreach ($submitted_structure_for_key['tabs'] as $tab_data) { // Ya vienen indexados numéricamente del form
                    if (empty($tab_data['id']) || empty($tab_data['text'])) {
                        // GloryLogger::info("RestaurantMenuAdminPanel: Tab skipped due to missing ID or Text. Data: " . print_r($tab_data, true));
                        continue;
                    }
                    $reconstructed_menu['tabs'][] = [
                        'id' => sanitize_text_field($tab_data['id']),
                        'text' => sanitize_text_field($tab_data['text']),
                        'visible_in_tabs' => isset($tab_data['visible_in_tabs']),
                    ];
                }
            }

            // 2. Reconstruir Secciones
            if (isset($submitted_structure_for_key['sections']) && is_array($submitted_structure_for_key['sections'])) {
                foreach ($submitted_structure_for_key['sections'] as $section_id_from_post_key => $section_data) {
                    $section_id = sanitize_key($section_id_from_post_key);

                    if (empty($section_id) || empty($section_data['title'])) {
                        // GloryLogger::info("RestaurantMenuAdminPanel: Section '{$section_id}' skipped due to missing Title. Data: " . print_r($section_data, true));
                        continue;
                    }

                    $current_section_type = sanitize_text_field($section_data['type'] ?? 'standard');
                    $new_section_data = [
                        'title' => sanitize_text_field($section_data['title']),
                        'description' => isset($section_data['description']) ? sanitize_textarea_field($section_data['description']) : null,
                        'type' => $current_section_type,
                    ];

                    if ($current_section_type === 'standard') {
                        $new_section_data['items'] = [];
                        if (isset($section_data['items']) && is_array($section_data['items'])) {
                            foreach ($section_data['items'] as $item_input) { // Indexados numéricamente
                                if (empty($item_input['name'])) {
                                    // GloryLogger::info("RestaurantMenuAdminPanel: Standard item skipped in section '{$section_id}' due to missing name. Data: " . print_r($item_input, true));
                                    continue;
                                }
                                $new_section_data['items'][] = [
                                    'name' => sanitize_text_field($item_input['name']),
                                    'price' => sanitize_text_field($item_input['price'] ?? ''),
                                    'description' => isset($item_input['description']) ? sanitize_textarea_field($item_input['description']) : null,
                                ];
                            }
                        }
                    } elseif ($current_section_type === 'multi_price') {
                        $new_section_data['price_headers'] = [];
                        if (isset($section_data['price_headers']) && is_array($section_data['price_headers'])) {
                            $new_section_data['price_headers'] = array_map('sanitize_text_field', $section_data['price_headers']);
                        }
                        $new_section_data['items'] = [];
                        if (isset($section_data['items']) && is_array($section_data['items'])) {
                            foreach ($section_data['items'] as $item_index => $item_input_value) {
                                if (empty($item_input_value['name'])) {
                                    GloryLogger::info("RestaurantMenuAdminPanel: Multi-price item skipped in section '{$section_id}' due to missing name. Index: {$item_index}. Data: " . print_r($item_input_value, true));
                                    continue;
                                }

                                $current_item_data = [
                                    'description' => isset($item_input_value['description']) ? sanitize_textarea_field(stripslashes($item_input_value['description'])) : null,
                                ];

                                // Procesar flags y campos específicos para multi-price items
                                if (isset($item_input_value['is_header_row']) && $item_input_value['is_header_row']) {
                                    $current_item_data['is_header_row'] = true;
                                    $current_item_data['name'] = wp_kses(stripslashes($item_input_value['name']), ['b' => ['style' => true], 'strong' => [], 'em' => [], 'i' => []]);
                                    // 'prices' para is_header_row son los textos de las cabeceras de columna para los siguientes items
                                    $current_item_data['prices'] = isset($item_input_value['prices']) && is_array($item_input_value['prices']) ? array_map('sanitize_text_field', $item_input_value['prices']) : [];
                                } elseif (isset($item_input_value['is_single_price']) && $item_input_value['is_single_price']) {
                                    $current_item_data['is_single_price'] = true;
                                    $current_item_data['name'] = sanitize_text_field(stripslashes($item_input_value['name']));
                                    $current_item_data['price'] = sanitize_text_field($item_input_value['price'] ?? '');
                                    // Unset 'prices' if it was inadvertently sent for a single price item
                                    if (isset($current_item_data['prices'])) {
                                        unset($current_item_data['prices']);
                                    }
                                } else {
                                    // Ítem multi-precio estándar (ni header, ni single price)
                                    $current_item_data['name'] = sanitize_text_field(stripslashes($item_input_value['name']));
                                    $current_item_data['prices'] = isset($item_input_value['prices']) && is_array($item_input_value['prices']) ? array_map('sanitize_text_field', $item_input_value['prices']) : [];
                                }

                                $new_section_data['items'][] = $current_item_data;
                            }
                        }
                    } elseif ($current_section_type === 'menu_pack') {
                        $new_section_data['packs'] = [];
                        if (isset($section_data['packs']) && is_array($section_data['packs'])) {
                            foreach ($section_data['packs'] as $pack_input) { // $pack_input es un array para un pack
                                // Usar 'pack_title' en lugar de 'name'
                                if (empty($pack_input['pack_title'])) {
                                    GloryLogger::info("RestaurantMenuAdminPanel: Menu pack skipped in section '{$section_id}' due to missing pack_title. Data: " . print_r($pack_input, true));
                                    continue;
                                }

                                $current_pack_data = [
                                    'pack_title' => sanitize_text_field(stripslashes($pack_input['pack_title'])),
                                    'pack_price' => sanitize_text_field($pack_input['pack_price'] ?? ''),
                                    'pack_description' => isset($pack_input['pack_description']) ? sanitize_textarea_field(stripslashes($pack_input['pack_description'])) : null,
                                    'details' => [], // Inicializar array para detalles
                                ];

                                // Procesar los detalles del pack
                                if (isset($pack_input['details']) && is_array($pack_input['details'])) {
                                    foreach ($pack_input['details'] as $detail_input) {
                                        if (empty($detail_input['text']) || empty($detail_input['type'])) {
                                            GloryLogger::info("RestaurantMenuAdminPanel: Pack detail skipped due to missing text or type. Data: " . print_r($detail_input, true));
                                            continue;
                                        }
                                        $sanitized_type = sanitize_key($detail_input['type']); // 'heading' o 'item'
                                        if (!in_array($sanitized_type, ['heading', 'item'])) {
                                            //GloryLogger::warning("RestaurantMenuAdminPanel: Invalid pack detail type '{$sanitized_type}'. Defaulting to 'item'. Detail text: {$detail_input['text']}");
                                            $sanitized_type = 'item'; // Fallback a un tipo conocido
                                        }

                                        $current_pack_data['details'][] = [
                                            'type' => $sanitized_type,
                                            'text' => sanitize_text_field(stripslashes($detail_input['text'])),
                                        ];
                                    }
                                }
                                $new_section_data['packs'][] = $current_pack_data;
                            }
                        }
                    }
                    $reconstructed_menu['sections'][$section_id] = $new_section_data;
                }
            }

            // Manejar 'dropdown_items_order' si se envió como JSON desde un textarea
            if (isset($submitted_structure_for_key['dropdown_items_order_json'])) {
                $decoded_order = json_decode(stripslashes($submitted_structure_for_key['dropdown_items_order_json']), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_order)) {
                    $reconstructed_menu['dropdown_items_order'] = array_map('sanitize_key', $decoded_order);
                } else {
                    // GloryLogger::warning("RestaurantMenuAdminPanel: dropdown_items_order_json for key '{$key}' was not valid JSON. Fallback logic will be used.");
                }
            }

            $original_json_for_top_level = json_decode(stripslashes($submitted_structure_for_key['_json_fallback'] ?? '{}'), true);
            if (is_array($original_json_for_top_level)) {
                foreach ($original_json_for_top_level as $top_key => $top_value) {
                    if (!isset($reconstructed_menu[$top_key])) {
                        if ($top_key === 'dropdown_items_order' && is_array($top_value)) {
                            $reconstructed_menu[$top_key] = array_map('sanitize_key', $top_value);
                        } elseif ($top_key === 'dropdown_config' && is_array($top_value)) { // Ejemplo para dropdown_config
                            $reconstructed_menu[$top_key] = array_map('sanitize_text_field', $top_value); // O una sanitización más específica
                        } elseif ($top_key !== 'tabs' && $top_key !== 'sections') {
                            $reconstructed_menu[$top_key] = $top_value; // Asumir que ya está sanitizado o es seguro
                        }
                    }
                }
            }

            // Asegurar que `dropdown_config` se mantenga si existe en el original y no fue modificado por UI.
            // Es un campo de nivel superior como `tabs` y `sections`.
            if (isset($original_json_for_top_level['dropdown_config']) && !isset($reconstructed_menu['dropdown_config'])) {
                // Realizar una sanitización básica si se considera necesario
                $sane_dropdown_config = [];
                if (isset($original_json_for_top_level['dropdown_config']['max_visible_tabs'])) {
                    $sane_dropdown_config['max_visible_tabs'] = intval($original_json_for_top_level['dropdown_config']['max_visible_tabs']);
                }
                // Añadir otros campos de dropdown_config si existen y necesitan sanitización específica
                $reconstructed_menu['dropdown_config'] = $sane_dropdown_config;
            }


            $value_to_save = $reconstructed_menu;
            // GloryLogger::info("RestaurantMenuAdminPanel: Final reconstructed menu_structure for '{$key}': " . print_r($value_to_save, true));

            $code_default_hash_at_save_time = ContentManager::getCodeDefaultHash($key);

            update_option($option_name, $value_to_save);
            update_option($option_name . ContentManager::OPTION_META_PANEL_SAVED_SUFFIX, true);
            update_option($option_name . ContentManager::OPTION_META_CODE_HASH_SUFFIX, $code_default_hash_at_save_time);
            $processed_at_least_one_menu = true;
        }

        if ($processed_at_least_one_menu) {
            add_settings_error('glory_restaurant_menu_messages', 'glory_menu_saved', __('Restaurant Menu settings saved.', 'glory'), 'updated');
        } else {
            add_settings_error('glory_restaurant_menu_messages', 'glory_no_menu_processed', __('No menu data was processed or found for saving. Ensure at least one "menu_structure" field is registered and submitted.', 'glory'), 'warning');
        }
        set_transient('settings_errors', get_settings_errors(), 30);

        $redirect_url = admin_url('admin.php?page=' . self::$menu_slug . '&settings-updated=true');
        wp_redirect($redirect_url);
        exit;
    }

    private static function handle_reset_single_menu_data(string $menu_key_to_reset): void
    {
        if (!current_user_can('manage_options')) {
            GloryLogger::error("RestaurantMenuAdminPanel: User without 'manage_options' tried to reset menu data for key '{$menu_key_to_reset}'.");
            wp_die(__('You do not have sufficient permissions to access this page.', 'glory'));
        }

        $all_fields = ContentManager::getRegisteredContentFields();
        $field_config = $all_fields[$menu_key_to_reset] ?? null;
        $menu_label = $field_config['label'] ?? $menu_key_to_reset;

        if ($field_config && isset($field_config['type']) && $field_config['type'] === 'menu_structure') {
            if (isset($field_config['default'])) {
                $default_value = $field_config['default'];
                $option_name = ContentManager::OPTION_PREFIX . $menu_key_to_reset;

                update_option($option_name, $default_value);
                delete_option($option_name . ContentManager::OPTION_META_PANEL_SAVED_SUFFIX);
                delete_option($option_name . ContentManager::OPTION_META_CODE_HASH_SUFFIX);

                GloryLogger::info("RestaurantMenuAdminPanel: Menu '{$menu_key_to_reset}' was reset to its default value by user.");
                add_settings_error(
                    'glory_restaurant_menu_messages',
                    'glory_menu_reset_success',
                    sprintf(__('Menu "%s" has been successfully reset to its default values.', 'glory'), esc_html($menu_label)),
                    'updated'
                );
            } else {
                GloryLogger::error("RestaurantMenuAdminPanel: Could not reset menu '{$menu_key_to_reset}'. No default value defined in its registration.");
                add_settings_error(
                    'glory_restaurant_menu_messages',
                    'glory_menu_reset_no_default',
                    sprintf(__('Could not reset menu "%s". No default value is defined for this menu in the code.', 'glory'), esc_html($menu_label)),
                    'error'
                );
            }
        } else {
            GloryLogger::error("RestaurantMenuAdminPanel: Could not reset menu '{$menu_key_to_reset}'. Field not found or not a 'menu_structure' type.");
            add_settings_error(
                'glory_restaurant_menu_messages',
                'glory_menu_reset_not_found',
                sprintf(__('Could not reset menu "%s". The menu field was not found or is not of the correct type.', 'glory'), esc_html($menu_key_to_reset)),
                'error'
            );
        }

        set_transient('settings_errors', get_settings_errors(), 30);
        $redirect_url = admin_url('admin.php?page=' . self::$menu_slug . '&settings-updated=true'); // settings-updated para mostrar el mensaje
        wp_redirect($redirect_url);
        exit;
    }


    public static function render_admin_page_html(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'glory'));
        }

        settings_errors('glory_restaurant_menu_messages');

        $all_fields = ContentManager::getRegisteredContentFields();
        $menu_structure_fields = [];
        foreach ($all_fields as $key => $config) {
            if (isset($config['type']) && $config['type'] === 'menu_structure') {
                if (!isset($config['current_value'])) {
                    $config['current_value'] = ContentManager::get($key, $config['default'] ?? [], false, null, null, null, null, 'menu_structure');
                }
                $menu_structure_fields[$key] = $config;
            }
        }

        echo '<div class="wrap glory-admin-panel-wrap glory-restaurant-menu-panel">'; // Añadida clase específica
        echo '<h1>' . esc_html__('Manage Restaurant Menu', 'glory') . '</h1>';

        if (empty($menu_structure_fields)) {
            echo '<p>' . esc_html__('No restaurant menu fields (type: menu_structure) are currently registered.', 'glory') . '</p>';
            echo '</div>';
            return;
        }

        echo '<form method="post" action="" id="glory-restaurant-menu-form" novalidate="novalidate">';
        wp_nonce_field('glory_restaurant_menu_save_action', '_wpnonce_glory_restaurant_menu_save');
        echo '<input type="hidden" name="action" value="glory_save_restaurant_menu" />';

        // Iterar sobre cada campo 'menu_structure' y renderizarlo usando la nueva función del parcial
        foreach ($menu_structure_fields as $key => $config) {
            echo '<div class="menu-structure-instance-wrapper postbox">'; // Un contenedor para cada instancia de menú
            echo '<h2 class="hndle"><span>' . esc_html($config['label'] ?? ucfirst(str_replace('_', ' ', $key))) . '</span></h2>';
            echo '<div class="inside">';
            if (!empty($config['description'])) {
                echo '<p class="description">' . esc_html($config['description']) . '</p>';
            }

            $option_input_name = 'glory_content[' . esc_attr($key) . ']';

            // Llamar a la función del nuevo parcial para renderizar el campo de menú

            render_menu_structure_field_admin_html($key, $config, $config['current_value'], $option_input_name);

            echo '</div>'; // .inside
            echo '</div>'; // .menu-structure-instance-wrapper (postbox)
        }

        submit_button(__('Save Menu Settings', 'glory'));
        echo '</form>';


        echo '</div>'; // Cierre de .wrap
    }
}
