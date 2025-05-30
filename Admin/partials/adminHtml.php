<?php
// Admin/partials/adminHtml.php

use Glory\Class\ContentManager;
use Glory\Class\GloryLogger;


function renderHorario(string $key, array $schedule_data, string $base_input_name): void
{
    $schedule_data = is_array($schedule_data) ? $schedule_data : []; // Asegurar que sea array
    $days_of_week_ordered = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    $schedule_map = [];
    foreach ($schedule_data as $entry) { // Mapear para fácil acceso y asegurar estructura
        if (isset($entry['day'])) $schedule_map[$entry['day']] = $entry;
    }

    echo '<div class="glory-schedule-editor">';
    foreach ($days_of_week_ordered as $idx => $day_name_label) {
        $entry = $schedule_map[$day_name_label] ?? ['day' => $day_name_label, 'status' => 'closed', 'open' => '', 'close' => '', 'hours' => 'Cerrado'];

        // Asegurar que los datos sean consistentes incluso si vienen mal de la BD
        $status = esc_attr($entry['status'] ?? 'closed');
        $open_time = ($status === 'open') ? esc_attr($entry['open'] ?? '') : '';
        $close_time = ($status === 'open') ? esc_attr($entry['close'] ?? '') : '';

        $post_index = $idx; // Usar índice numérico para el array POST
        $input_name_day    = $base_input_name . '[' . $post_index . '][day]';
        $input_name_status = $base_input_name . '[' . $post_index . '][status]';
        $input_name_open   = $base_input_name . '[' . $post_index . '][open]';
        $input_name_close  = $base_input_name . '[' . $post_index . '][close]';
        // No necesitamos input para 'hours', se reconstruye al guardar
        $unique_id_prefix = esc_attr($key . '_' . strtolower(str_replace(' ', '_', $day_name_label)));

        echo '<div class="glory-schedule-day-row">';
        echo '<strong>' . esc_html($day_name_label) . '</strong><input type="hidden" name="' . $input_name_day . '" value="' . esc_attr($day_name_label) . '"><br>';
        echo '<label for="' . $unique_id_prefix . '_status">' . __('Status:', 'glory') . ' </label>';
        echo '<select id="' . $unique_id_prefix . '_status" name="' . $input_name_status . '">';
        echo '<option value="open" ' . selected($status, 'open', false) . '>' . __('Open', 'glory') . '</option>';
        echo '<option value="closed" ' . selected($status, 'closed', false) . '>' . __('Closed', 'glory') . '</option>';
        echo '</select>';
        echo '<label for="' . $unique_id_prefix . '_open">' . __('Open:', 'glory') . ' <input id="' . $unique_id_prefix . '_open" type="time" name="' . $input_name_open . '" value="' . $open_time . '" ' . ($status === 'closed' ? 'disabled' : '') . '></label>';
        echo '<label for="' . $unique_id_prefix . '_close">' . __('Close:', 'glory') . ' <input id="' . $unique_id_prefix . '_close" type="time" name="' . $input_name_close . '" value="' . $close_time . '" ' . ($status === 'closed' ? 'disabled' : '') . '></label>';
        echo '</div>';
    }
    echo '</div>';
    // JS for enabling/disabling time fields is now in the main JS file (Assets/js/content-admin-panel.js)
}

function renderFieldInput(string $key, array $config, $current_value, string $option_input_name): void
{
    $field_id = esc_attr($key);
    $type = $config['type'] ?? 'text';

    switch ($type) {
        case 'text':
            echo '<input type="text" id="' . $field_id . '" name="' . $option_input_name . '" value="' . esc_attr($current_value) . '" class="regular-text">';
            break;
        case 'menu_structure':
            // $current_value aquí es el array PHP completo de la estructura del menú
            $menu_data = is_array($current_value) ? $current_value : [];
            $tabs_data = $menu_data['tabs'] ?? [];
            $sections_data = $menu_data['sections'] ?? [];

            echo '<div class="glory-menu-structure-admin">';

            // Parte 1: Administrador de Pestañas (Tabs) - Simplificado por ahora
            echo '<h4>' . __('Menu Tabs Configuration', 'glory') . '</h4>';
            echo '<div class="glory-menu-tabs-editor">';
            if (!empty($tabs_data)) {
                foreach ($tabs_data as $tab_index => $tab) {
                    $tab_id_val = $tab['id'] ?? 'new_tab_' . $tab_index;
                    $tab_text_val = $tab['text'] ?? '';
                    $tab_visible_val = $tab['visible_in_tabs'] ?? true;
                    $base_tab_name = esc_attr($option_input_name . '[tabs][' . $tab_index . ']');
?>
                    <div class="glory-menu-tab-item">
                        <p>
                            ID: <input type="text" name="<?php echo $base_tab_name . '[id]'; ?>" value="<?php echo esc_attr($tab_id_val); ?>" placeholder="Ej: 46580">
                            Texto: <input type="text" name="<?php echo $base_tab_name . '[text]'; ?>" value="<?php echo esc_attr($tab_text_val); ?>" placeholder="Ej: NUESTROS DESAYUNOS">
                            <label>
                                <input type="checkbox" name="<?php echo $base_tab_name . '[visible_in_tabs]'; ?>" value="1" <?php checked($tab_visible_val); ?>>
                                <?php _e('Visible in main tab bar', 'glory'); ?>
                            </label>
                            <button type="button" class="button button-small glory-remove-menu-tab">X</button>
                        </p>
                    </div>
    <?php
                }
            }
            echo '<button type="button" class="button glory-add-menu-tab">' . __('Add Tab', 'glory') . '</button>';
            echo '</div><hr>';


            // Parte 2: Administrador de Secciones
            echo '<h4>' . __('Menu Sections', 'glory') . '</h4>';
            echo '<div class="glory-menu-sections-editor">';

            if (!empty($sections_data)) {
                foreach ($sections_data as $section_id => $section) {
                    $section_title = $section['title'] ?? '';
                    $section_description = $section['description'] ?? '';
                    $section_type = $section['type'] ?? 'standard'; // Asegurar que type exista
                    $base_section_name = esc_attr($option_input_name . '[sections][' . $section_id . ']');

                    echo '<div class="glory-menu-section postbox">';
                    echo '<h3 class="hndle"><span>' . esc_html($section_title ?: __('New Section', 'glory')) . ' (ID: ' . esc_html($section_id) . ')</span></h3>';
                    echo '<div class="inside">';

                    // Campos comunes a todas las secciones (ID es la clave, no editable directamente aquí)
                    echo '<input type="hidden" name="' . $base_section_name . '[id_placeholder]" value="' . esc_attr($section_id) . '">'; // Para identificar la sección al guardar si el título cambia
                    echo '<p><label>' . __('Section Title:', 'glory') . '</label><br><input type="text" name="' . $base_section_name . '[title]' . '" value="' . esc_attr($section_title) . '" class="large-text"></p>';
                    echo '<p><label>' . __('Section Description (optional):', 'glory') . '</label><br><textarea name="' . $base_section_name . '[description]' . '" rows="3" class="large-text">' . esc_textarea($section_description) . '</textarea></p>';
                    echo '<p><label>' . __('Section Type:', 'glory') . '</label><br>';
                    echo '<select name="' . $base_section_name . '[type]' . '">';
                    echo '<option value="standard" ' . selected($section_type, 'standard', false) . '>' . __('Standard Items', 'glory') . '</option>';
                    echo '<option value="multi_price" ' . selected($section_type, 'multi_price', false) . '>' . __('Multi-Price Items', 'glory') . '</option>';
                    echo '<option value="menu_pack" ' . selected($section_type, 'menu_pack', false) . '>' . __('Menu Packs', 'glory') . '</option>';
                    echo '</select></p>';


                    // Renderizado específico por tipo de sección
                    if ($section_type === 'standard') {
                        echo '<h5>' . __('Items:', 'glory') . '</h5>';
                        echo '<div class="glory-menu-items-list">';
                        $items = $section['items'] ?? [];
                        if (!empty($items)) {
                            foreach ($items as $item_idx => $item_data) {
                                renderMenuRestaurant($section_id, $item_idx, $item_data, $base_section_name . '[items]');
                            }
                        }
                        echo '</div>'; // .glory-menu-items-list
                        echo '<button type="button" class="button glory-add-menu-item" data-section-id="' . esc_attr($section_id) . '">' . __('Add Item to this Section', 'glory') . '</button>';
                    } elseif ($section_type === 'multi_price') {
                        echo '<p><em>' . __('Multi-price item editor UI will be implemented later.', 'glory') . '</em></p>';
                        // Aquí iría la UI para price_headers e items con múltiples precios
                        // Por ahora, mostramos el JSON para no perder datos
                        $fallback_value = $section['items'] ?? [];
                        if (isset($section['price_headers'])) $fallback_value = ['price_headers' => $section['price_headers'], 'items' => $fallback_value];
                        echo '<textarea rows="5" class="large-text code" readonly>' . esc_textarea(json_encode($fallback_value, JSON_PRETTY_PRINT)) . '</textarea>';
                    } elseif ($section_type === 'menu_pack') {
                        echo '<p><em>' . __('Menu pack editor UI will be implemented later.', 'glory') . '</em></p>';
                        // Aquí iría la UI para los packs
                        // Por ahora, mostramos el JSON para no perder datos
                        echo '<textarea rows="5" class="large-text code" readonly>' . esc_textarea(json_encode($section['packs'] ?? [], JSON_PRETTY_PRINT)) . '</textarea>';
                    }

                    echo '<p><button type="button" class="button button-link-delete glory-remove-menu-section">' . __('Remove this Section', 'glory') . '</button></p>';
                    echo '</div>'; // .inside
                    echo '</div>'; // .glory-menu-section
                }
            }
            echo '</div>'; // .glory-menu-sections-editor
            echo '<button type="button" class="button button-primary glory-add-menu-section">' . __('Add New Section', 'glory') . '</button>';

            echo '</div>'; // .glory-menu-structure-admin

            // Guardamos una copia del JSON original en un campo oculto por si algo falla o para referencia
            // Esto es temporal hasta que la UI sea completamente funcional para todos los tipos
            echo '<input type="hidden" name="' . esc_attr($option_input_name . '[_json_fallback]') . '" value="' . esc_attr(json_encode($current_value)) . '">';

            break;
        // --- FIN DE CÓDIGO MODIFICADO/NUEVO ---
        case 'raw':
            $value_for_textarea = $current_value;
            if (is_array($value_for_textarea) || is_object($value_for_textarea)) {
                $json_string = json_encode($value_for_textarea, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($json_string === false) {
                    $value_for_textarea = '/* Error al codificar a JSON: ' . json_last_error_msg() . ' */';
                    GloryLogger::error("ContentAdminPanel Partial: Error encoding raw field '{$key}' to JSON for display. Value: " . print_r($current_value, true) . " Error: " . json_last_error_msg());
                } else {
                    $value_for_textarea = $json_string;
                }
            } elseif ($value_for_textarea === null) {
                $value_for_textarea = '';
            }
            if (!is_string($value_for_textarea)) $value_for_textarea = (string) $value_for_textarea;
            echo '<textarea id="' . $field_id . '" name="' . $option_input_name . '" rows="10" class="large-text">' . esc_textarea($value_for_textarea) . '</textarea>';
            echo '<p class="description">' . __('Enter valid JSON. If content is not valid JSON, it will be saved as a raw string.', 'glory') . '</p>';
            break;
        case 'richText':
            $value_for_richtext_area = is_string($current_value) ? $current_value : '';
            echo '<textarea id="' . $field_id . '" name="' . $option_input_name . '" rows="10" class="large-text wp-editor-area">' . esc_textarea($value_for_richtext_area) . '</textarea>';
            echo '<p class="description">' . __('HTML is allowed. Content will be filtered by wp_kses_post on save.', 'glory') . '</p>';
            break;
        case 'image':
            $image_url = is_string($current_value) ? $current_value : '';
            echo '<input type="text" id="' . $field_id . '" name="' . $option_input_name . '" value="' . esc_url($image_url) . '" class="regular-text glory-image-url-field">';
            echo ' <button type="button" class="button glory-upload-image-button">' . __('Upload Image', 'glory') . '</button>';
            echo ' <button type="button" class="button glory-remove-image-button">' . __('Remove Image', 'glory') . '</button>';
            echo '<div class="glory-image-preview">';
            if (!empty($image_url)) echo '<img src="' . esc_url($image_url) . '">';
            echo '</div>';
            break;
        case 'schedule':
            // Updated call to the renamed global function in this file
            renderHorario($key, is_array($current_value) ? $current_value : [], $option_input_name);
            break;
        default:
            echo '<input type="text" id="' . $field_id . '" name="' . $option_input_name . '" value="' . esc_attr(is_scalar($current_value) ? $current_value : '') . '" class="regular-text">';
            // GloryLogger::info("ContentAdminPanel Partial: Unknown field type '{$type}' for key '{$key}'. Defaulting to text input.");
    }
}

function renderContentPanel(array $fields_by_section, string $active_tab, string $menu_slug_for_url_building): string
{
    ob_start();
    ?>
    <div class="wrap glory-content-panel">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php settings_errors('glory_content_messages'); ?>
        <div class="glory-tabs-container-two">
            <div class="glory-tabs-nav-container">
                <?php if (!empty($fields_by_section)): ?>
                    <?php foreach ($fields_by_section as $section_slug => $fields_in_section): ?>
                        <?php
                        $section_label_raw = 'General';
                        if (!empty($fields_in_section)) {
                            $first_field_config = reset($fields_in_section);
                            $section_label_raw = $first_field_config['section_label'] ?? $first_field_config['section'] ?? ucfirst(str_replace('-', ' ', $section_slug));
                        }
                        $tab_id_attr = 'tab-' . $section_slug;
                        ?>
                        <a href="#<?php echo esc_attr($tab_id_attr); ?>"
                            class="nav-tab <?php echo $active_tab === $section_slug ? 'nav-tab-active' : ''; ?>"
                            data-tab-id="<?php echo esc_attr($section_slug); ?>">
                            <?php echo esc_html(ucfirst($section_label_raw)); ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?php _e('No content sections defined.', 'glory'); ?></p>
                <?php endif; ?>
            </div>

            <div class="glory-tabs-content-container">
                <?php if (empty($fields_by_section)): ?>
                    <p><?php _e('No content fields have been registered yet.', 'glory'); ?></p>
                <?php else: ?>
                    <?php foreach ($fields_by_section as $section_slug => $fields_in_section): ?>
                        <?php
                        $tab_id_attr = 'tab-' . $section_slug;
                        $section_display_name_raw = 'General';
                        if (!empty($fields_in_section)) {
                            $first_field_config = reset($fields_in_section);
                            $section_display_name_raw = $first_field_config['section_label'] ?? $first_field_config['section'] ?? ucfirst(str_replace('-', ' ', $section_slug));
                        }
                        ?>
                        <div id="<?php echo esc_attr($tab_id_attr); ?>" class="glory-tab-content <?php echo $active_tab === $section_slug ? 'active' : ''; ?>">
                            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . $menu_slug_for_url_building . '&tab=' . $section_slug)); ?>">
                                <input type="hidden" name="action" value="glory_save_content">
                                <input type="hidden" name="glory_active_section" value="<?php echo esc_attr($section_slug); ?>">
                                <?php wp_nonce_field('glory_content_save_action_' . $section_slug, '_wpnonce_glory_content_save'); ?>
                                <div class="metabox-holder">
                                    <div class="postbox">
                                        <h2 class="hndle"><span><?php echo esc_html(ucfirst($section_display_name_raw)); ?></span></h2>
                                        <div class="inside">
                                            <?php if ($section_slug === 'galeria-contenido'): ?>
                                                <div class="glory-gallery-admin-grid">
                                                    <?php
                                                    $processed_gallery_keys = [];
                                                    for ($i = 1; $i <= 20; $i++) {
                                                        $image_key = 'gallery_image_' . $i;
                                                        $alt_key = 'gallery_image_alt_' . $i;

                                                        if (isset($fields_in_section[$image_key])) {
                                                            $image_config = $fields_in_section[$image_key];
                                                            $alt_config = $fields_in_section[$alt_key] ?? null;

                                                            $image_url = $image_config['current_value'] ?? $image_config['default'] ?? '';
                                                            $alt_text = '';
                                                            if ($alt_config) {
                                                                $alt_text = $alt_config['current_value'] ?? $alt_config['default'] ?? '';
                                                            }

                                                            $image_option_input_name = 'glory_content[' . esc_attr($image_key) . ']';
                                                            $alt_option_input_name = 'glory_content[' . esc_attr($alt_key) . ']';
                                                            $image_label = $image_config['label'] ?? ('Imagen #' . $i);
                                                    ?>
                                                            <div class="glory-gallery-item">
                                                                <div class="glory-image-preview">
                                                                    <?php if (!empty($image_url)): ?>
                                                                        <img src="<?php echo esc_url($image_url); ?>" alt="Preview for <?php echo esc_attr($image_label); ?>">
                                                                    <?php else: ?>
                                                                        <p><?php _e('No image set', 'glory'); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="glory-image-controls">
                                                                    <input type="hidden" name="<?php echo esc_attr($image_option_input_name); ?>" value="<?php echo esc_url($image_url); ?>" class="glory-image-url-field">
                                                                    <button type="button" class="button glory-upload-image-button"><?php _e('Set/Change Image', 'glory'); ?></button>
                                                                    <button type="button" class="button glory-remove-image-button"><?php _e('Remove Image', 'glory'); ?></button>
                                                                </div>
                                                                <label for="<?php echo esc_attr($alt_key); ?>"><?php _e('Alt Text:', 'glory'); ?> (<?php echo esc_html($image_label); ?>)</label>
                                                                <input type="text" id="<?php echo esc_attr($alt_key); ?>" name="<?php echo esc_attr($alt_option_input_name); ?>" value="<?php echo esc_attr($alt_text); ?>" class="regular-text">
                                                            </div>
                                                    <?php
                                                            $processed_gallery_keys[] = $image_key;
                                                            $processed_gallery_keys[] = $alt_key;
                                                        }
                                                    } ?>
                                                </div>
                                                <?php
                                                $remaining_fields_in_section = array_diff_key($fields_in_section, array_flip($processed_gallery_keys));
                                                if (!empty($remaining_fields_in_section)):
                                                ?>
                                                    <hr>
                                                    <p><em><?php _e('Other settings for this section:', 'glory'); ?></em></p>
                                                    <table class="form-table" role="presentation">
                                                        <tbody>
                                                            <?php foreach ($remaining_fields_in_section as $key => $config):
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
                                                                        <?php renderFieldInput($key, $config, $current_value_for_field, $option_input_name); // Updated call 
                                                                        ?>
                                                                        <?php if ($description): ?>
                                                                            <p class="description"><?php echo wp_kses_post($description); ?></p>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                <?php endif; ?>
                                            <?php else: // For sections other than 'galeria-contenido' 
                                            ?>
                                                <table class="form-table" role="presentation">
                                                    <tbody>
                                                        <?php foreach ($fields_in_section as $key => $config):
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
                                                                    <?php renderFieldInput($key, $config, $current_value_for_field, $option_input_name);  // Updated call 
                                                                    ?>
                                                                    <?php if ($description): ?>
                                                                        <p class="description"><?php echo wp_kses_post($description); ?></p>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php submit_button(__('Save Changes for this Section', 'glory')); ?>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}

function renderMenuRestaurant(string $section_key, int $item_index, array $item_data, string $base_input_name): void
{
    $name = $item_data['name'] ?? '';
    $price = $item_data['price'] ?? '';
    $description = $item_data['description'] ?? '';
?>
    <div class="glory-menu-item" data-item-index="<?php echo $item_index; ?>">
        <button type="button" class="button button-small glory-remove-menu-item">X</button>
        <p>
            <label><?php _e('Item Name:', 'glory'); ?></label><br>
            <input type="text" name="<?php echo esc_attr($base_input_name . '[' . $item_index . '][name]'); ?>" value="<?php echo esc_attr($name); ?>" class="large-text">
        </p>
        <p>
            <label><?php _e('Item Price:', 'glory'); ?></label><br>
            <input type="text" name="<?php echo esc_attr($base_input_name . '[' . $item_index . '][price]'); ?>" value="<?php echo esc_attr($price); ?>" class="regular-text">
        </p>
        <p>
            <label><?php _e('Item Description (optional):', 'glory'); ?></label><br>
            <textarea name="<?php echo esc_attr($base_input_name . '[' . $item_index . '][description]'); ?>" rows="1" class="large-text" style="height: 50px !important; min-height: unset !important;"><?php echo esc_textarea($description); ?></textarea>
        </p>
        <hr>
    </div>
<?php
}

?>

