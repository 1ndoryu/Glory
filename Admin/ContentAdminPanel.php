<?php
// glory/Admin/ContentAdminPanel.php
namespace Glory\Admin;

use Glory\Class\ContentManager;
use Glory\Class\GloryLogger;

class ContentAdminPanel {
    private static string $menu_slug = 'glory-content-manager';

    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_admin_page']);
        add_action('admin_init', [self::class, 'register_settings_and_handle_save']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
    }

    public static function enqueue_admin_assets(string $hook_suffix): void {
        $actual_hook_for_page = 'toplevel_page_' . self::$menu_slug;
        if ($hook_suffix !== $actual_hook_for_page) {
            return;
        }
        
        wp_enqueue_media();

        $inline_css = "
            .glory-content-panel .postbox .hndle { cursor: default; padding: 8px 12px; }
            .glory-content-panel .glory-section-wrap { margin-bottom: 20px; }
            .glory-image-preview img { border: 1px solid #ddd; margin-top: 5px; max-width:150px; max-height:150px; display:block; }
            .glory-schedule-editor .glory-schedule-day-row { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #ccc; }
            .glory-schedule-editor .glory-schedule-day-row:last-child { border-bottom: none; }
            .glory-schedule-editor label { margin-right: 10px; display: inline-block; }
            .glory-schedule-editor input[type='time'], .glory-schedule-editor select { vertical-align: middle; }
            .glory-content-panel .form-table th { width: 200px; }
            .glory-content-panel .form-table td .regular-text, .glory-content-panel .form-table td .large-text { width: 100%; max-width: 500px; }
            .glory-content-panel .form-table td textarea.large-text { min-height: 120px; }
        ";
        wp_add_inline_style('wp-admin', $inline_css);

        wp_add_inline_script('jquery', self::get_image_uploader_js());
    }
    
    private static function get_image_uploader_js(): string {
        return "
        jQuery(document).ready(function($){
            $(document).on('click', '.glory-upload-image-button', function(e) {
                e.preventDefault();
                var button = $(this);
                var inputField = button.prev('.glory-image-url-field');
                var imagePreviewContainer = button.siblings('.glory-image-preview');

                var frame = wp.media({
                    title: '" . esc_js(__('Select or Upload Image', 'glory')) . "',
                    button: { text: '" . esc_js(__('Use this image', 'glory')) . "' },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    inputField.val(attachment.url);
                    imagePreviewContainer.html('<img src=\"' + attachment.url + '\">');
                });
                frame.open();
            });

            $(document).on('click', '.glory-remove-image-button', function(e) {
                e.preventDefault();
                var button = $(this);
                var inputField = button.siblings('.glory-image-url-field');
                var imagePreviewContainer = button.siblings('.glory-image-preview');
                inputField.val('');
                imagePreviewContainer.html('');
            });
        });
        ";
    }

    public static function add_admin_page(): void {
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

    public static function register_settings_and_handle_save(): void {
        if (isset($_POST['action']) && $_POST['action'] === 'glory_save_content' && isset($_POST['_wpnonce_glory_content_save'])) {
            if (wp_verify_nonce($_POST['_wpnonce_glory_content_save'], 'glory_content_save_action')) {
                self::handle_save_data();
            } else {
                wp_die(__('Nonce verification failed!', 'glory'), __('Error', 'glory'), ['response' => 403]);
            }
        }
    }

    private static function handle_save_data(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'glory'));
        }

        GloryLogger::info('ContentAdminPanel: Handling save action.');
        $registered_fields = ContentManager::getRegisteredContentFields();
        $posted_options = $_POST['glory_content'] ?? [];

        foreach ($registered_fields as $key => $config) {
            $option_name = ContentManager::OPTION_PREFIX . $key;
            $panel_value_exists_in_post = array_key_exists($key, $posted_options);
            $value_to_save = null; // Inicializar

            if ($config['type'] === 'schedule') {
                $schedule_data = [];
                if ($panel_value_exists_in_post && is_array($posted_options[$key]) && isset($posted_options[$key][0]['day'])) { // Verificación ajustada para schedule
                    $count = count($posted_options[$key]); // Cada $posted_options[$key][i] es un día
                    for ($i = 0; $i < $count; $i++) {
                        // Asegurarse de que el índice 'day' existe en el sub-array
                        $day_name = sanitize_text_field($posted_options[$key][$i]['day'] ?? '');
                        if (empty($day_name)) continue;

                        $status = sanitize_text_field($posted_options[$key][$i]['status'] ?? 'closed');
                        $open_time = sanitize_text_field($posted_options[$key][$i]['open'] ?? '');
                        $close_time = sanitize_text_field($posted_options[$key][$i]['close'] ?? '');
                        $hours_str = '';

                        if ($status === 'open' && !empty($open_time) && !empty($close_time)) {
                            $hours_str = $open_time . '-' . $close_time;
                        } elseif ($status === 'closed') {
                            $hours_str = 'Cerrado';
                            $open_time = '';
                            $close_time = '';
                        }
                        // Ajustado para coincidir con la estructura de ContentManager::schedule
                        $schedule_data[] = ['day' => $day_name, 'open' => $open_time, 'close' => $close_time, 'status' => $status, 'hours' => $hours_str];
                    }
                }
                $value_to_save = $schedule_data;

            // ***** INICIO CAMBIO PARA RAW *****
            } elseif ($config['type'] === 'raw') {
                if ($panel_value_exists_in_post) {
                    $json_string_from_textarea = sanitize_textarea_field(stripslashes($posted_options[$key]));
                    $decoded_value = json_decode($json_string_from_textarea, true); // true para array asociativo

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value_to_save = $decoded_value; // Guardar el array/objeto decodificado
                        GloryLogger::info("ContentAdminPanel: Raw field '{$key}' saved as decoded JSON.");
                    } else {
                        // JSON inválido: guardar el string tal cual para que el usuario pueda corregirlo.
                        // O podrías optar por no guardar y mostrar un error, o guardar el default.
                        $value_to_save = $json_string_from_textarea;
                        GloryLogger::info("ContentAdminPanel: Raw field '{$key}' contained invalid JSON. Saved as raw string. Error: " . json_last_error_msg());
                        add_settings_error(
                            'glory_content_messages',
                            'glory_invalid_json_' . $key,
                            sprintf(__('Warning: The content for "%s" was not valid JSON and has been saved as a raw string. Please correct it.', 'glory'), $config['label']),
                            'warning'
                        );
                    }
                } else {
                    // Campo 'raw' no enviado, podría interpretarse como un array/objeto vacío
                    $value_to_save = []; // o $config['default'] si prefieres
                }
            // ***** FIN CAMBIO PARA RAW *****

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
                    default: // Otros tipos que no sean schedule o raw
                         $value_to_save = sanitize_text_field(stripslashes($raw_posted_value)); // Fallback genérico
                }
            } else {
                 // Campo no enviado (ej. checkbox desmarcado o campo borrado intencionalmente).
                 // Guardar un valor vacío para tipos de texto, o el default para otros.
                 $value_to_save = in_array($config['type'], ['text', 'image', 'richText']) ? '' : ($config['default'] ?? '');
            }
            
            update_option($option_name, $value_to_save);
            update_option($option_name . ContentManager::OPTION_META_PANEL_SAVED_SUFFIX, true);
            update_option($option_name . ContentManager::OPTION_META_CODE_HASH_SUFFIX, ContentManager::getCodeDefaultHash($key));
            GloryLogger::info("ContentAdminPanel: Updated option '{$option_name}'. Panel save flag set.");
        }

        add_settings_error('glory_content_messages', 'glory_content_message', __('Settings Saved', 'glory'), 'updated');
        set_transient('settings_errors', get_settings_errors(), 30);
        
        wp_redirect(admin_url('admin.php?page=' . self::$menu_slug . '&settings-updated=true'));
        exit;
    }

    public static function render_admin_page_html(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'glory'));
        }

        $all_fields = ContentManager::getRegisteredContentFields();
        $fields_by_section = [];
        foreach ($all_fields as $key => $config) {
            $section = $config['section'] ?? 'general';
            $fields_by_section[$section][$key] = $config;
        }
        ksort($fields_by_section);

        ?>
        <div class="wrap glory-content-panel">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php
             if (isset($_GET['settings-updated'])) {
                 // Transitorio ya configurado en handle_save_data
             }
             settings_errors('glory_content_messages'); // Muestra errores guardados en el transitorio o añadidos aquí
            ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . self::$menu_slug)); ?>">
                <input type="hidden" name="action" value="glory_save_content">
                <?php wp_nonce_field('glory_content_save_action', '_wpnonce_glory_content_save'); ?>

                <?php if (empty($fields_by_section)): ?>
                    <p><?php _e('No content fields have been registered yet.', 'glory'); ?></p>
                <?php else: ?>
                    <?php foreach ($fields_by_section as $section_name => $fields_in_section): ?>
                        <div class="glory-section-wrap metabox-holder">
                            <div class="postbox">
                                <h2 class="hndle"><span><?php echo esc_html(ucfirst($section_name)); ?></span></h2>
                                <div class="inside">
                                    <table class="form-table" role="presentation">
                                        <tbody>
                                        <?php foreach ($fields_in_section as $key => $config):
                                            // $current_value es el valor que ContentManager::getRegisteredContentFields() determinó
                                            // que debería estar en el panel (puede ser array para 'raw' o 'schedule')
                                            $current_value_for_field = $config['current_value'] ?? $config['default'] ?? '';
                                            $option_input_name = 'glory_content[' . esc_attr($key) . ']';
                                            $label = $config['label'] ?? ucfirst(str_replace('_', ' ', $key));
                                            $description = $config['description'] ?? '';
                                        ?>
                                            <tr>
                                                <th scope="row">
                                                    <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
                                                </th>
                                                <td>
                                                    <?php self::render_field_input_control($key, $config, $current_value_for_field, $option_input_name); ?>
                                                    <?php if ($description): ?>
                                                        <p class="description"><?php echo esc_html($description); ?></p>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php submit_button(__('Save Changes', 'glory')); ?>
            </form>
        </div>
        <?php
    }

    private static function render_field_input_control(string $key, array $config, $current_value, string $option_input_name): void {
        $field_id = esc_attr($key);
        $type = $config['type'] ?? 'text';

        switch ($type) {
            case 'text':
                echo '<input type="text" id="' . $field_id . '" name="' . $option_input_name . '" value="' . esc_attr($current_value) . '" class="regular-text">';
                break;

            // ***** INICIO CAMBIO PARA RAW *****
            case 'raw':
                $value_for_textarea = $current_value;
                if (is_array($value_for_textarea) || is_object($value_for_textarea)) {
                    // JSON_UNESCAPED_UNICODE y JSON_UNESCAPED_SLASHES para mejor legibilidad y evitar doble escape
                    $json_string = json_encode($value_for_textarea, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    if ($json_string === false) {
                        $value_for_textarea = '/* Error al codificar a JSON: ' . json_last_error_msg() . ' */';
                         GloryLogger::error("ContentAdminPanel: Error encoding raw field '{$key}' to JSON for display. Value: " . print_r($current_value, true));
                    } else {
                        $value_for_textarea = $json_string;
                    }
                } elseif ($value_for_textarea === null) {
                     $value_for_textarea = ''; // O '{}' si prefieres un objeto JSON vacío por defecto
                }
                // Si ya es un string (por ejemplo, JSON inválido guardado previamente), se mostrará tal cual.
                // Asegurarse de que sea un string antes de esc_textarea
                if (!is_string($value_for_textarea)) {
                    $value_for_textarea = (string) $value_for_textarea;
                }
                echo '<textarea id="' . $field_id . '" name="' . $option_input_name . '" rows="10" class="large-text">' . esc_textarea($value_for_textarea) . '</textarea>'; // Aumentado rows para JSON
                echo '<p class="description">' . __('Enter valid JSON. If content is not valid JSON, it will be saved as a raw string.', 'glory') . '</p>';
                break;
            // ***** FIN CAMBIO PARA RAW *****

            case 'richText':
                $value_for_richtext_area = is_string($current_value) ? $current_value : ''; // Asegurar que es string
                echo '<textarea id="' . $field_id . '" name="' . $option_input_name . '" rows="10" class="large-text wp-editor-area">' . esc_textarea($value_for_richtext_area) . '</textarea>';
                echo '<p class="description">' . __('HTML is allowed. Content will be filtered by wp_kses_post on save.', 'glory') . '</p>';
                break;
            case 'image':
                $image_url = is_string($current_value) ? $current_value : ''; // Asegurar que es string
                echo '<input type="text" id="' . $field_id . '" name="' . $option_input_name . '" value="' . esc_url($image_url) . '" class="regular-text glory-image-url-field">';
                echo ' <button type="button" class="button glory-upload-image-button">' . __('Upload Image', 'glory') . '</button>';
                echo ' <button type="button" class="button glory-remove-image-button">' . __('Remove Image', 'glory') . '</button>';
                echo '<div class="glory-image-preview">';
                if (!empty($image_url)) {
                    echo '<img src="' . esc_url($image_url) . '">';
                }
                echo '</div>';
                break;
            case 'schedule':
                // $current_value para schedule ya debería ser un array por ContentManager::getRegisteredContentFields()
                // y la lógica en ContentManager::schedule()
                self::render_schedule_input_control($key, is_array($current_value) ? $current_value : [], $option_input_name);
                break;
            default:
                echo '<input type="text" id="' . $field_id . '" name="' . $option_input_name . '" value="' . esc_attr( is_scalar($current_value) ? $current_value : '' ) . '" class="regular-text">';
                GloryLogger::info("ContentAdminPanel: Unknown field type '{$type}' for key '{$key}'. Defaulting to text input.");
        }
    }

    private static function render_schedule_input_control(string $key, array $schedule_data, string $base_input_name): void {
        // Asegurarse de que $schedule_data sea un array.
        $schedule_data = is_array($schedule_data) ? $schedule_data : [];
        
        $days_of_week_ordered = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        $schedule_map = [];

        // Mapear los datos del horario por nombre de día para fácil acceso
        // Esto maneja la estructura guardada por ContentManager::schedule que tiene 'day', 'open', 'close', 'status', 'hours'
        foreach($schedule_data as $entry) {
            if(isset($entry['day'])) { // 'day' es la clave usada por ContentManager
                $schedule_map[$entry['day']] = $entry;
            }
        }

        echo '<div class="glory-schedule-editor">';
        // Iterar sobre los días de la semana en orden para asegurar que todos se muestren
        foreach ($days_of_week_ordered as $day_name_label) {
            // Obtener la entrada para el día actual del mapa, o usar defaults si no existe
            $entry = $schedule_map[$day_name_label] ?? ['day' => $day_name_label, 'status' => 'closed', 'open' => '', 'close' => ''];
            
            $status = esc_attr($entry['status'] ?? 'closed');
            // Para el panel, usamos 'open' y 'close' directamente, no 'hours'
            $open_time = esc_attr($entry['open'] ?? ''); 
            $close_time = esc_attr($entry['close'] ?? '');

            // El índice del array POST debe ser numérico para que PHP lo interprete como array
            $post_index = array_search($day_name_label, $days_of_week_ordered); 

            $input_name_day    = $base_input_name . '['.$post_index.'][day]';
            $input_name_status = $base_input_name . '['.$post_index.'][status]';
            $input_name_open   = $base_input_name . '['.$post_index.'][open]';
            $input_name_close  = $base_input_name . '['.$post_index.'][close]';
            $unique_id_prefix = esc_attr($key . '_' . $post_index); // Para 'for' y 'id' de labels y inputs

            echo '<div class="glory-schedule-day-row">';
            echo '<strong>' . esc_html($day_name_label) . '</strong><input type="hidden" name="' . $input_name_day . '" value="' . esc_attr($day_name_label) . '">';
            echo '<br>';
            echo '<label for="'.$unique_id_prefix.'_status">'.__('Status:', 'glory').' </label>';
            echo '<select id="'.$unique_id_prefix.'_status" name="' . $input_name_status . '">';
            echo '<option value="open" ' . selected($status, 'open', false) . '>' . __('Open', 'glory') . '</option>';
            echo '<option value="closed" ' . selected($status, 'closed', false) . '>' . __('Closed', 'glory') . '</option>';
            echo '</select>';
            echo '<label for="'.$unique_id_prefix.'_open">' . __('Open:', 'glory') . ' <input id="'.$unique_id_prefix.'_open" type="time" name="' . $input_name_open . '" value="' . $open_time . '"></label>';
            echo '<label for="'.$unique_id_prefix.'_close">' . __('Close:', 'glory') . ' <input id="'.$unique_id_prefix.'_close" type="time" name="' . $input_name_close . '" value="' . $close_time . '"></label>';
            echo '</div>';
        }
        echo '</div>';
    }
}