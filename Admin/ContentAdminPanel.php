<?php
// glory/Admin/ContentAdminPanel.php
namespace Glory\Admin;

use Glory\Class\ContentManager;
use Glory\Class\GloryLogger;

class ContentAdminPanel
{
    private static string $menu_slug = 'glory-content-manager';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'add_admin_page']);
        add_action('admin_init', [self::class, 'register_settings_and_handle_save']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
    }

    public static function enqueue_admin_assets(string $hook_suffix): void
    {
        // Solo en nuestra página de admin
        $actual_hook = 'toplevel_page_' . self::$menu_slug;
        if ($hook_suffix !== $actual_hook) {
            return;
        }

        // Carga los módulos de media de WP (uploader, galerías, etc.)
        wp_enqueue_media();

        // Rutas del tema
        $theme_uri  = get_stylesheet_directory_uri();
        $theme_path = get_stylesheet_directory();

        // === CSS ===
        $css_handle       = 'glory-content-admin-panel-style';
        $css_relative     = '/Glory/assets/css/content-admin-panel.css';
        $css_file_url     = $theme_uri . $css_relative;
        $css_file_path    = $theme_path . $css_relative;

        if (file_exists($css_file_path)) {
            wp_enqueue_style(
                $css_handle,
                $css_file_url,
                [],
                filemtime($css_file_path)
            );
        }

        // === JAVASCRIPT ===
        $js_handle          = 'glory-content-admin-panel-script';
        $js_filename        = 'content-admin-panel.js';
        $js_relative        = "/Glory/assets/js/{$js_filename}";
        $js_file_url        = $theme_uri . $js_relative;
        $js_file_systempath = $theme_path . $js_relative;

        if (file_exists($js_file_systempath) && is_readable($js_file_systempath)) {
            wp_enqueue_script(
                $js_handle,
                $js_file_url,
                ['jquery', 'media-editor'],
                filemtime($js_file_systempath),
                true
            );

            wp_localize_script($js_handle, 'gloryAdminPanelSettings', [
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('glory_admin_ajax_nonce'),
                'menuSlug'  => self::$menu_slug,
                'i18n'      => [
                    'selectOrUploadImage' => esc_js(__('Select or Upload Image', 'glory')),
                    'useThisImage'        => esc_js(__('Use this image', 'glory')),
                ],
            ]);
        }
    }



    public static function add_admin_page(): void
    {
        add_menu_page(
            __('Glory Content', 'glory'),
            __('Glory Content', 'glory'),
            'manage_options',
            self::$menu_slug,
            [self::class, 'render_admin_page_html'],
            'dashicons-edit-page',
            30
        );
    }

    public static function register_settings_and_handle_save(): void
    {
        if (
            isset($_POST['action']) && $_POST['action'] === 'glory_save_content' &&
            isset($_POST['_wpnonce_glory_content_save']) && isset($_POST['glory_active_section'])
        ) {
            $active_section_key = sanitize_text_field($_POST['glory_active_section']);
            if (wp_verify_nonce($_POST['_wpnonce_glory_content_save'], 'glory_content_save_action_' . $active_section_key)) {
                self::handle_save_data($active_section_key);
            } else {
                GloryLogger::error("ContentAdminPanel: Nonce verification FAILED for section {$active_section_key}.");
                wp_die(__('Nonce verification failed!', 'glory'), __('Error', 'glory'), ['response' => 403]);
            }
        }
    }

    private static function handle_save_data(string $active_section_key): void // $active_section_key ya está sanitizado
    {
        if (!current_user_can('manage_options')) {
            GloryLogger::error("ContentAdminPanel: User without 'manage_options' tried to save data for section {$active_section_key}.");
            wp_die(__('You do not have sufficient permissions to access this page.', 'glory'));
        }

        $all_registered_fields = ContentManager::getRegisteredContentFields();
        $posted_options = $_POST['glory_content'] ?? [];
        $fields_in_current_section = [];

        foreach ($all_registered_fields as $key => $config) {
            $field_config_section_slug = sanitize_title($config['section'] ?? 'general');
            if ($field_config_section_slug === $active_section_key) {
                $fields_in_current_section[$key] = $config;
            }
        }

        if (empty($fields_in_current_section)) {
            GloryLogger::info("ContentAdminPanel: No fields found for section '{$active_section_key}' after filtering during save. Original section from POST: '{$_POST['glory_active_section']}'. Check field registrations.");
        }

        if (!empty($fields_in_current_section)) {
            foreach ($fields_in_current_section as $key => $config) {
                $option_name = ContentManager::OPTION_PREFIX . $key;
                $panel_value_exists_in_post = array_key_exists($key, $posted_options);
                $value_to_save = null;

                if ($config['type'] === 'schedule') {
                    $schedule_data = [];
                    if ($panel_value_exists_in_post && is_array($posted_options[$key])) {
                        foreach ($posted_options[$key] as $day_schedule_data) {
                            $day_name = sanitize_text_field($day_schedule_data['day'] ?? '');
                            if (empty($day_name)) continue;
                            $status = sanitize_text_field($day_schedule_data['status'] ?? 'closed');
                            $open_time = sanitize_text_field($day_schedule_data['open'] ?? '');
                            $close_time = sanitize_text_field($day_schedule_data['close'] ?? '');
                            $hours_str = '';
                            if ($status === 'open' && !empty($open_time) && !empty($close_time)) {
                                $hours_str = $open_time . '-' . $close_time;
                            } elseif ($status === 'closed') {
                                $hours_str = 'Cerrado';
                                $open_time = '';
                                $close_time = '';
                            }
                            $schedule_data[] = ['day' => $day_name, 'open' => $open_time, 'close' => $close_time, 'status' => $status, 'hours' => $hours_str];
                        }
                    }
                    $value_to_save = $schedule_data;
                } elseif ($config['type'] === 'raw') {
                    if ($panel_value_exists_in_post) {
                        $json_string_from_textarea = sanitize_textarea_field(stripslashes($posted_options[$key]));
                        $decoded_value = json_decode($json_string_from_textarea, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $value_to_save = $decoded_value;
                        } else {
                            $value_to_save = $json_string_from_textarea;
                            GloryLogger::info("ContentAdminPanel: Raw field '{$key}' contained invalid JSON. Saved as raw string. Error: " . json_last_error_msg());
                            add_settings_error('glory_content_messages', 'glory_invalid_json_' . $key, sprintf(__('Warning: The content for "%s" was not valid JSON and has been saved as a raw string. Please correct it.', 'glory'), $config['label']), 'warning');
                        }
                    } else {
                        $value_to_save = $config['default'] ?? [];
                    }
                } elseif ($config['type'] === 'menu_structure') {
                    GloryLogger::info("ContentAdminPanel: Saving menu_structure for key '{$key}'. Posted data: " . print_r($posted_options[$key] ?? [], true));
                    $submitted_structure = $posted_options[$key] ?? []; // Esto ahora es un array de la UI, no un string JSON
                    $reconstructed_menu = [
                        'tabs' => [],
                        'sections' => [],
                        // Mantener otros campos de nivel superior si existen (ej: dropdown_items_order)
                        // Podríamos tomarlos del _json_fallback si no vienen de la UI
                    ];

                    // 1. Reconstruir Pestañas (Tabs)
                    if (isset($submitted_structure['tabs']) && is_array($submitted_structure['tabs'])) {
                        foreach ($submitted_structure['tabs'] as $tab_data) {
                            if (empty($tab_data['id']) || empty($tab_data['text'])) {
                                GloryLogger::info("ContentAdminPanel: Tab skipped due to missing ID or Text. Data: " . print_r($tab_data, true));
                                continue;
                            }
                            $reconstructed_menu['tabs'][] = [
                                'id' => sanitize_text_field($tab_data['id']),
                                'text' => sanitize_text_field($tab_data['text']),
                                'visible_in_tabs' => isset($tab_data['visible_in_tabs']), // Checkbox value
                            ];
                        }
                    }

                    // 2. Reconstruir Secciones
                    if (isset($submitted_structure['sections']) && is_array($submitted_structure['sections'])) {
                        foreach ($submitted_structure['sections'] as $section_id_from_post_key => $section_data) {
                            // El section_id_from_post_key es el que se usó para agrupar en el HTML (ej. 46580).
                            // Es importante que este ID sea consistente.
                            $section_id = sanitize_key($section_id_from_post_key); // Usar el ID de la clave del array

                            if (empty($section_id) || empty($section_data['title'])) {
                                GloryLogger::info("ContentAdminPanel: Section skipped due to missing ID or Title. Data: " . print_r($section_data, true));
                                continue;
                            }

                            $current_section_type = sanitize_text_field($section_data['type'] ?? 'standard');
                            $new_section_data = [
                                'title' => sanitize_text_field($section_data['title']),
                                'description' => isset($section_data['description']) ? sanitize_textarea_field($section_data['description']) : null,
                                'type' => $current_section_type,
                                // 'items' o 'packs' se añadirán según el tipo
                            ];

                            if ($current_section_type === 'standard') {
                                $new_section_data['items'] = [];
                                if (isset($section_data['items']) && is_array($section_data['items'])) {
                                    foreach ($section_data['items'] as $item_input) {
                                        if (empty($item_input['name'])) { // Un precio podría ser 0 o vacío, pero el nombre es esencial
                                            GloryLogger::info("ContentAdminPanel: Menu item skipped in section '{$section_id}' due to missing name. Data: " . print_r($item_input, true));
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
                                // --- IMPORTANTE: Lógica para multi_price (cuando la UI esté lista) ---
                                // Por ahora, intentaremos tomar del _json_fallback si esta sección existía y era multi_price
                                $original_json = json_decode(stripslashes($submitted_structure['_json_fallback'] ?? '{}'), true);
                                if (isset($original_json['sections'][$section_id]) && $original_json['sections'][$section_id]['type'] === 'multi_price') {
                                    $new_section_data['price_headers'] = $original_json['sections'][$section_id]['price_headers'] ?? [];
                                    $new_section_data['items'] = $original_json['sections'][$section_id]['items'] ?? [];
                                    GloryLogger::info("ContentAdminPanel: multi_price section '{$section_id}' data taken from _json_fallback as UI is not ready.");
                                } else {
                                    $new_section_data['price_headers'] = []; // Default
                                    $new_section_data['items'] = [];       // Default
                                    GloryLogger::info("ContentAdminPanel: multi_price section '{$section_id}' has no UI and no fallback data. Saved empty.");
                                }
                            } elseif ($current_section_type === 'menu_pack') {
                                // --- IMPORTANTE: Lógica para menu_pack (cuando la UI esté lista) ---
                                $original_json = json_decode(stripslashes($submitted_structure['_json_fallback'] ?? '{}'), true);
                                if (isset($original_json['sections'][$section_id]) && $original_json['sections'][$section_id]['type'] === 'menu_pack') {
                                    $new_section_data['packs'] = $original_json['sections'][$section_id]['packs'] ?? [];
                                    GloryLogger::info("ContentAdminPanel: menu_pack section '{$section_id}' data taken from _json_fallback as UI is not ready.");
                                } else {
                                    $new_section_data['packs'] = []; // Default
                                    GloryLogger::info("ContentAdminPanel: menu_pack section '{$section_id}' has no UI and no fallback data. Saved empty.");
                                }
                            }
                            $reconstructed_menu['sections'][$section_id] = $new_section_data;
                        }
                    }

                    // Recuperar otros campos de nivel superior del fallback si existen y no vinieron de la UI
                    $original_json_for_top_level = json_decode(stripslashes($submitted_structure['_json_fallback'] ?? '{}'), true);
                    if (is_array($original_json_for_top_level)) {
                        foreach ($original_json_for_top_level as $top_key => $top_value) {
                            if ($top_key !== 'tabs' && $top_key !== 'sections' && !isset($reconstructed_menu[$top_key])) {
                                $reconstructed_menu[$top_key] = $top_value; //Ej: dropdown_items_order
                            }
                        }
                    }


                    $value_to_save = $reconstructed_menu;
                    GloryLogger::info("ContentAdminPanel: Reconstructed menu_structure for '{$key}': " . print_r($value_to_save, true));

                    // --- FIN DE CÓDIGO MODIFICADO/NUEVO ---
                } elseif ($panel_value_exists_in_post) {
                    $raw_posted_value = $posted_options[$key];
                    switch ($config['type']) {
                        case 'text':
                            $value_to_save = sanitize_text_field(stripslashes($raw_posted_value));
                            break;
                        case 'image':
                            $value_to_save = esc_url_raw(stripslashes($raw_posted_value));
                            break;
                        case 'richText':
                            $value_to_save = wp_kses_post(stripslashes($raw_posted_value));
                            break;
                        default:
                            $value_to_save = sanitize_text_field(stripslashes($raw_posted_value));
                    }
                } else {
                    $value_to_save = in_array($config['type'], ['text', 'image', 'richText']) ? '' : ($config['default'] ?? '');
                    if ($config['type'] === 'raw' && $value_to_save === '') $value_to_save = [];
                }

                $code_default_hash_at_save_time = ContentManager::getCodeDefaultHash($key);

                GloryLogger::info("ContentAdminPanel: PRE-SAVE Key '{$key}'. Option: '{$option_name}'. Value to save: " . print_r($value_to_save, true));
                GloryLogger::info("ContentAdminPanel: PRE-SAVE Key '{$key}'. Meta panel_saved_flag will be TRUE.");
                GloryLogger::info("ContentAdminPanel: PRE-SAVE Key '{$key}'. Meta code_hash_on_save will be '{$code_default_hash_at_save_time}'.");

                update_option($option_name, $value_to_save);
                update_option($option_name . ContentManager::OPTION_META_PANEL_SAVED_SUFFIX, true);
                update_option($option_name . ContentManager::OPTION_META_CODE_HASH_SUFFIX, $code_default_hash_at_save_time);
            }

            add_settings_error('glory_content_messages', 'glory_content_message', __('Settings Saved for section:', 'glory') . ' ' . esc_html(ucfirst(str_replace('-', ' ', $active_section_key))), 'updated');
            set_transient('settings_errors', get_settings_errors(), 30);

            $redirect_url = admin_url('admin.php?page=' . self::$menu_slug . '&tab=' . $active_section_key . '&settings-updated=true');
            wp_redirect($redirect_url);
            exit;
        } else {
            add_settings_error('glory_content_messages', 'glory_no_fields_saved', __('No fields were configured for saving in this section.', 'glory'), 'warning');
            set_transient('settings_errors', get_settings_errors(), 30);

            $redirect_url = admin_url('admin.php?page=' . self::$menu_slug . '&tab=' . $active_section_key);
            wp_redirect($redirect_url);
            exit;
        }
    }

    public static function render_admin_page_html(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'glory'));
        }

        $all_fields = ContentManager::getRegisteredContentFields();
        $fields_by_section = [];
        foreach ($all_fields as $key => $config) {
            $section_slug = sanitize_title($config['section'] ?? 'general');
            $fields_by_section[$section_slug][$key] = $config;
        }
        ksort($fields_by_section);

        $default_tab = !empty($fields_by_section) ? array_key_first($fields_by_section) : 'general';
        $active_tab = isset($_GET['tab']) ? sanitize_title($_GET['tab']) : $default_tab;
        if (!array_key_exists($active_tab, $fields_by_section) && $active_tab !== 'general' && !empty($fields_by_section)) {
            $active_tab = $default_tab;
        }

        // Assuming renderContentPanel() is defined elsewhere and works correctly
        // You might need to include it or ensure it's autoloaded.
        if (function_exists('renderContentPanel')) {
            echo renderContentPanel($fields_by_section, $active_tab, self::$menu_slug);
        } else {
            echo '<div class="wrap"><h1>Error</h1><p>Admin panel rendering function is missing.</p></div>';
            GloryLogger::error("ContentAdminPanel: renderContentPanel() function not found.");
        }
    }
}
