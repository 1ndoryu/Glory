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
        error_log('[Glory DEBUG - ContentAdminPanel.php] enqueue_admin_assets llamado. Hook actual: ' . $hook_suffix . '. Hook esperado: ' . $actual_hook);
        if ($hook_suffix !== $actual_hook) {
            return;
        }

        // Carga los módulos de media de WP (uploader, galerías, etc.)
        wp_enqueue_media();

        // Encolar scripts y estilos para el editor de código (CodeMirror)
        wp_enqueue_code_editor(array('type' => 'application/json')); // Esto ayuda a asegurar que todo esté listo
        wp_enqueue_script('code-editor');
        wp_enqueue_style('code-editor');


        // Rutas del tema
        $theme_uri  = get_stylesheet_directory_uri();
        $theme_path = get_stylesheet_directory();

        // === CSS ===
        $css_handle       = 'glory-content-admin-panel-style';
        $css_relative     = '/Glory/Assets/css/content-admin-panel.css';
        $css_file_url     = $theme_uri . $css_relative;
        $css_file_path    = $theme_path . $css_relative;

        if (file_exists($css_file_path)) {
            wp_enqueue_style(
                $css_handle,
                $css_file_url,
                ['wp-codemirror'], // Dependencia de los estilos de CodeMirror
                null // Evitar el versionado automático que añade ?ver=
            );
            error_log('[Glory DEBUG - ContentAdminPanel.php] CSS encolado CORRECTAMENTE: ' . $css_file_url . ' (Hook: ' . $hook_suffix . ')');
        } else {
            error_log('[Glory DEBUG - ContentAdminPanel.php] ERROR CSS: Archivo no encontrado en ' . $css_file_path . ' (Hook: ' . $hook_suffix . ')');
        }

        // === JAVASCRIPT ===
        $js_handle          = 'glory-content-admin-panel-script';
        $js_filename        = 'content-admin-panel.js';
        $js_relative        = "/Glory/Assets/js/{$js_filename}";
        $js_file_url        = $theme_uri . $js_relative;
        $js_file_systempath = $theme_path . $js_relative;

        if (file_exists($js_file_systempath) && is_readable($js_file_systempath)) {
            wp_enqueue_script(
                $js_handle,
                $js_file_url,
                ['jquery', 'media-editor', 'code-editor'], // Añadir 'code-editor' como dependencia
                null, // Evitar el versionado automático que añade ?ver=
                true
            );

            // Configuración para el editor de código JSON
            $code_editor_settings = wp_get_code_editor_settings(['type' => 'application/json']);

            wp_localize_script($js_handle, 'gloryAdminPanelSettings', [
                'ajaxUrl'             => admin_url('admin-ajax.php'),
                'nonce'               => wp_create_nonce('glory_admin_ajax_nonce'),
                'menuSlug'            => self::$menu_slug,
                'i18n'                => [
                    'selectOrUploadImage' => esc_js(__('Seleccionar o Subir Imagen', 'glory')),
                    'useThisImage'        => esc_js(__('Usar esta imagen', 'glory')),
                ],
                'codeEditorSettings' => $code_editor_settings, // Pasar settings del editor JSON
            ]);
        }
    }


    public static function add_admin_page(): void
    {
        add_menu_page(
            __('Panel', 'glory'),
            __('Panel', 'glory'),
            'manage_options',
            self::$menu_slug,
            [self::class, 'render_admin_page_html'],
            'dashicons-edit-page',
            30
        );
    }

    public static function register_settings_and_handle_save(): void
    {
        // Guardar datos de una sección
        if (
            isset($_POST['action']) && $_POST['action'] === 'glory_save_content' &&
            isset($_POST['_wpnonce_glory_content_save']) && isset($_POST['glory_active_section'])
        ) {
            $active_section_key = sanitize_text_field($_POST['glory_active_section']);
            if (wp_verify_nonce($_POST['_wpnonce_glory_content_save'], 'glory_content_save_action_' . $active_section_key)) {
                self::handle_save_data($active_section_key); // Este método ya redirige
            } else {
                GloryLogger::error("ContentAdminPanel: Nonce verification FAILED for SAVE action, section {$active_section_key}.");
                wp_die(__('¡Falló la verificación Nonce para la acción de guardar!', 'glory'), __('Error', 'glory'), ['response' => 403]);
            }
            return; // Salir después de manejar el guardado
        }

        // Resetear una sección a los valores por defecto
        if (
            isset($_POST['action']) && $_POST['action'] === 'glory_reset_section' &&
            isset($_POST['_wpnonce_glory_content_reset']) && isset($_POST['glory_active_section'])
        ) {
            $active_section_key = sanitize_text_field($_POST['glory_active_section']);
            if (wp_verify_nonce($_POST['_wpnonce_glory_content_reset'], 'glory_content_reset_action_' . $active_section_key)) {
                if (!current_user_can('manage_options')) {
                    GloryLogger::error("ContentAdminPanel: User without 'manage_options' tried to RESET section {$active_section_key}.");
                    wp_die(__('No tiene permisos suficientes para realizar esta acción.', 'glory'));
                }

                $reset_results = ContentManager::resetSectionToDefaults($active_section_key);
                $section_display_name = ucfirst(str_replace('-', ' ', $active_section_key));

                if ($reset_results['fields_processed_count'] > 0) {
                    add_settings_error(
                        'glory_content_messages',
                        'glory_content_reset_success',
                        sprintf(
                            __('La sección "%s" se ha restablecido correctamente a los valores predeterminados. Se restablecieron %d campo(s).', 'glory'),
                            esc_html($section_display_name),
                            $reset_results['fields_processed_count']
                        ),
                        'updated'
                    );
                } elseif ($reset_results['not_found_or_empty'] && $reset_results['fields_processed_count'] === 0) {
                    add_settings_error(
                        'glory_content_messages',
                        'glory_content_reset_no_fields',
                        sprintf(
                            __('La sección "%s" no se encontró, está vacía o no contenía campos aplicables para restablecer por este panel.', 'glory'),
                            esc_html($section_display_name)
                        ),
                        'warning'
                    );
                } else { // Podría haber errores específicos en el futuro, por ahora es un error genérico
                    add_settings_error(
                        'glory_content_messages',
                        'glory_content_reset_failed',
                        sprintf(__('No se pudo restablecer la sección "%s". Por favor, revise los registros.', 'glory'), esc_html($section_display_name)),
                        'error'
                    );
                }
                set_transient('settings_errors', get_settings_errors(), 30);
                $redirect_url = admin_url('admin.php?page=' . self::$menu_slug . '&tab=' . $active_section_key . '&settings-reset=true');
                wp_redirect($redirect_url);
                exit;
            } else {
                GloryLogger::error("ContentAdminPanel: Nonce verification FAILED for RESET action, section {$active_section_key}.");
                wp_die(__('¡Falló la verificación Nonce para la acción de restablecer!', 'glory'), __('Error', 'glory'), ['response' => 403]);
            }
            return; // Salir después de manejar el reseteo
        }
    }

    private static function handle_save_data(string $active_section_key): void // $active_section_key ya está sanitizado
    {
        if (!current_user_can('manage_options')) {
            GloryLogger::error("ContentAdminPanel: User without 'manage_options' tried to save data for section {$active_section_key}.");
            wp_die(__('No tiene permisos suficientes para acceder a esta página.', 'glory'));
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

        $processed_at_least_one_field = false;

        if (!empty($fields_in_current_section)) {
            foreach ($fields_in_current_section as $key => $config) {
                // Si el tipo es 'menu_structure', lo saltamos. Será manejado por RestaurantMenuAdminPanel.
                if (isset($config['type']) && $config['type'] === 'menu_structure') {
                    GloryLogger::info("ContentAdminPanel: Field '{$key}' is of type 'menu_structure' and will be handled by a dedicated menu panel. Skipping save in ContentAdminPanel.");
                    continue;
                }

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
                                $hours_str = 'Cerrado'; // Ya está en español
                                $open_time = '';
                                $close_time = '';
                            }
                            $schedule_data[] = ['day' => $day_name, 'open' => $open_time, 'close' => $close_time, 'status' => $status, 'hours' => $hours_str];
                        }
                    }
                    $value_to_save = $schedule_data;
                } elseif ($config['type'] === 'raw') {
                    if ($panel_value_exists_in_post) {
                        $raw_value_from_textarea = stripslashes($posted_options[$key]); // No sanitizar aquí para no alterar scripts
                        // Intentar decodificar como JSON solo si se espera que sea JSON (podríamos añadir una sub-propiedad en config, ej. 'is_json_raw')
                        // Por ahora, para 'header_scripts', asumimos que no es JSON y lo guardamos tal cual.
                        // Si en el futuro tenemos campos 'raw' que SÍ son JSON, necesitaremos una forma de distinguirlos.
                        // Una opción simple es verificar si el 'default' es un array o si el string parece JSON.
                        $is_likely_json = (is_array($config['default']) || (is_string($raw_value_from_textarea) && (strpos(trim($raw_value_from_textarea), '{') === 0 || strpos(trim($raw_value_from_textarea), '[') === 0)));

                        if ($is_likely_json) {
                            $decoded_value = json_decode($raw_value_from_textarea, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $value_to_save = $decoded_value;
                            } else {
                                $value_to_save = sanitize_textarea_field($raw_value_from_textarea); // Sanitizar si era JSON inválido
                                GloryLogger::info("ContentAdminPanel: Raw field '{$key}' (expected JSON) contained invalid JSON. Saved as sanitized string. Error: " . json_last_error_msg());
                                add_settings_error('glory_content_messages', 'glory_invalid_json_' . $key, sprintf(__('Advertencia: El contenido para "%s" (esperado como JSON) no era JSON válido y se ha guardado como una cadena de texto sanitizada. Por favor, corríjalo.', 'glory'), $config['label']), 'warning');
                            }
                        } else {
                            // No es JSON, guardar como texto crudo (scripts, HTML, etc.)
                            // Usar wp_kses_post o una sanitización más permisiva si es necesario, 
                            // pero para scripts <script>...</script> necesitamos que se guarde tal cual.
                            // La función sanitize_textarea_field es demasiado agresiva para scripts.
                            // Considerar usar wp_kses($raw_value_from_textarea, 'post') si se quiere permitir HTML seguro pero no scripts.
                            // Para scripts, realmente no queremos sanitización que los rompa.
                            $value_to_save = $raw_value_from_textarea; 
                        }
                    } else {
                        $value_to_save = $config['default'] ?? [];
                    }
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
                    // Para checkboxes no marcados o campos que no vienen en POST pero están en la sección.
                    // Para tipos específicos como 'image', 'text', 'richText', si no vienen en POST, se guardan como string vacío.
                    // Para otros tipos (que podrían ser arrays o estructuras complejas por defecto), se usa el 'default' del config.
                    // Un 'raw' que no venga en POST pero tenga un default array, usará ese default. Si no, un array vacío.
                    $value_to_save = in_array($config['type'], ['text', 'image', 'richText']) ? '' : ($config['default'] ?? '');
                    if ($config['type'] === 'raw' && $value_to_save === '') $value_to_save = []; // raw sin valor en POST ni default, default a array vacío.
                }

                $code_default_hash_at_save_time = ContentManager::getCodeDefaultHash($key);

                GloryLogger::info("ContentAdminPanel: PRE-SAVE Key '{$key}'. Option: '{$option_name}'. Value to save: " . print_r($value_to_save, true));
                GloryLogger::info("ContentAdminPanel: PRE-SAVE Key '{$key}'. Meta panel_saved_flag will be TRUE.");
                GloryLogger::info("ContentAdminPanel: PRE-SAVE Key '{$key}'. Meta code_hash_on_save will be '{$code_default_hash_at_save_time}'.");

                update_option($option_name, $value_to_save);
                update_option($option_name . ContentManager::OPTION_META_PANEL_SAVED_SUFFIX, true);
                update_option($option_name . ContentManager::OPTION_META_CODE_HASH_SUFFIX, $code_default_hash_at_save_time);
                $processed_at_least_one_field = true;
            }

            if ($processed_at_least_one_field) {
                add_settings_error('glory_content_messages', 'glory_content_message', __('Ajustes guardados para la sección:', 'glory') . ' ' . esc_html(ucfirst(str_replace('-', ' ', $active_section_key))), 'updated');
            } else {
                // Si todos los campos eran 'menu_structure' (o no había campos procesables)
                add_settings_error('glory_content_messages', 'glory_no_fields_processed_message', __('No se procesaron campos aplicables para guardar en esta sección para el Panel de Contenido.', 'glory'), 'info');
            }
            set_transient('settings_errors', get_settings_errors(), 30);

            $redirect_url = admin_url('admin.php?page=' . self::$menu_slug . '&tab=' . $active_section_key . '&settings-updated=true');
            wp_redirect($redirect_url);
            exit;
        } else { // $fields_in_current_section estaba vacío desde el inicio
            add_settings_error('glory_content_messages', 'glory_no_fields_saved', __('No se configuraron campos para guardar en esta sección.', 'glory'), 'warning');
            set_transient('settings_errors', get_settings_errors(), 30);

            $redirect_url = admin_url('admin.php?page=' . self::$menu_slug . '&tab=' . $active_section_key);
            wp_redirect($redirect_url);
            exit;
        }
    }

    public static function render_admin_page_html(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tiene permisos suficientes para acceder a esta página.', 'glory'));
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
            echo '<div class="wrap"><h1>Error</h1><p>' . __('Falta la función de renderizado del panel de administración.', 'glory') . '</p></div>';
            GloryLogger::error("ContentAdminPanel: renderContentPanel() function not found.");
        }
    }
}
