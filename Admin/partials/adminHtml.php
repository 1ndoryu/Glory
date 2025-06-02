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
        echo '<label for="' . $unique_id_prefix . '_status">' . __('Estado:', 'glory') . ' </label>';
        echo '<select id="' . $unique_id_prefix . '_status" name="' . $input_name_status . '">';
        echo '<option value="open" ' . selected($status, 'open', false) . '>' . __('Abierto', 'glory') . '</option>';
        echo '<option value="closed" ' . selected($status, 'closed', false) . '>' . __('Cerrado', 'glory') . '</option>';
        echo '</select>';
        echo '<label for="' . $unique_id_prefix . '_open">' . __('Apertura:', 'glory') . ' <input id="' . $unique_id_prefix . '_open" type="time" name="' . $input_name_open . '" value="' . $open_time . '" ' . ($status === 'closed' ? 'disabled' : '') . '></label>';
        echo '<label for="' . $unique_id_prefix . '_close">' . __('Cierre:', 'glory') . ' <input id="' . $unique_id_prefix . '_close" type="time" name="' . $input_name_close . '" value="' . $close_time . '" ' . ($status === 'closed' ? 'disabled' : '') . '></label>';
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
        // El case 'menu_structure' ha sido eliminado de aquí.
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
            // Añadimos la clase 'glory-json-editor-area'
            echo '<textarea id="' . $field_id . '" name="' . $option_input_name . '" rows="10" class="large-text glory-json-editor-area">' . esc_textarea($value_for_textarea) . '</textarea>';
            echo '<p class="description">' . __('Introduzca JSON válido. Si el contenido no es JSON válido, se guardará como una cadena de texto sin formato.', 'glory') . '</p>';
            break;
        case 'richText':
            $value_for_richtext_area = is_string($current_value) ? $current_value : '';
            $editor_settings = [
                'textarea_name' => $option_input_name,
                'media_buttons' => true, // Habilitar botones de medios
                'tinymce'       => true, // Usar TinyMCE
                'quicktags'     => true, // Habilitar Quicktags
                'textarea_rows' => 10,
            ];
            wp_editor(wp_kses_post($value_for_richtext_area), $field_id, $editor_settings); // Usar wp_kses_post para limpiar el contenido antes de mostrarlo
            echo '<p class="description">' . __('Se permite HTML. El contenido será filtrado por wp_kses_post al guardar.', 'glory') . '</p>';
            break;
        case 'image':
            $image_url = is_string($current_value) ? $current_value : '';
            echo '<input type="text" id="' . $field_id . '" name="' . $option_input_name . '" value="' . esc_url($image_url) . '" class="regular-text glory-image-url-field">';
            echo ' <button type="button" class="button glory-upload-image-button">' . __('Subir Imagen', 'glory') . '</button>';
            echo ' <button type="button" class="button glory-remove-image-button">' . __('Quitar Imagen', 'glory') . '</button>';
            echo '<div class="glory-image-preview">';
            if (!empty($image_url)) echo '<img src="' . esc_url($image_url) . '">';
            echo '</div>';
            break;
        case 'schedule':
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
                        // No renderizar pestaña si todos los campos son 'menu_structure' ya que se manejan en otro panel
                        $non_menu_fields_exist = false;
                        foreach ($fields_in_section as $field_config) {
                            if (($field_config['type'] ?? 'text') !== 'menu_structure') {
                                $non_menu_fields_exist = true;
                                break;
                            }
                        }
                        if (!$non_menu_fields_exist && $menu_slug_for_url_building === 'glory-content-manager') {
                            // Si estamos en el panel general y esta sección solo tenía campos de menú, no mostrar la pestaña.
                            // Esto es para evitar pestañas vacías si los campos de menú se movieron a su propio panel.
                            continue;
                        }


                        $section_label_raw = __('General', 'glory');
                        if (!empty($fields_in_section)) {
                            // Intentar obtener la etiqueta de la primera configuración de campo no-menú
                            $first_field_config = null;
                            foreach ($fields_in_section as $f_conf) {
                                if (($f_conf['type'] ?? 'text') !== 'menu_structure') {
                                    $first_field_config = $f_conf;
                                    break;
                                }
                            }
                            if ($first_field_config) {
                                $section_label_raw = $first_field_config['section_label'] ?? $first_field_config['section'] ?? ucfirst(str_replace('-', ' ', $section_slug));
                            } elseif (!empty($fields_in_section)) { // Si solo hay campos de menú (improbable aquí después del continue)
                                $first_field_config = reset($fields_in_section);
                                $section_label_raw = $first_field_config['section_label'] ?? $first_field_config['section'] ?? ucfirst(str_replace('-', ' ', $section_slug));
                            }
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
                    <p><?php _e('No se han definido secciones de contenido.', 'glory'); ?></p>
                <?php endif; ?>
            </div>

            <div class="glory-tabs-content-container">
                <?php if (empty($fields_by_section)): ?>
                    <p><?php _e('Aún no se han registrado campos de contenido.', 'glory'); ?></p>
                <?php else: ?>
                    <?php foreach ($fields_by_section as $section_slug => $fields_in_section): ?>
                        <?php
                        // Filtrar campos 'menu_structure' de la renderización en este panel general
                        $fields_to_render_in_section = [];
                        $section_display_name_raw = __('General', 'glory');
                        $has_non_menu_fields = false;

                        foreach ($fields_in_section as $key_loop => $config_loop) {
                            if (($config_loop['type'] ?? 'text') !== 'menu_structure') {
                                $fields_to_render_in_section[$key_loop] = $config_loop;
                                if (!$has_non_menu_fields) { // Tomar la etiqueta de la primera config no-menu
                                    $section_display_name_raw = $config_loop['section_label'] ?? $config_loop['section'] ?? ucfirst(str_replace('-', ' ', $section_slug));
                                    $has_non_menu_fields = true;
                                }
                            }
                        }

                        // Si después de filtrar, no quedan campos para esta sección (porque todos eran menu_structure),
                        // y estamos en el panel general, no renderizar el contenido de la pestaña.
                        if (empty($fields_to_render_in_section) && $menu_slug_for_url_building === 'glory-content-manager') {
                            continue;
                        }
                        // Si aún no se pudo determinar el nombre y hay campos, usar el primero disponible (aunque sean de menú, caso borde)
                        if (!$has_non_menu_fields && !empty($fields_in_section)) {
                            $first_field_config = reset($fields_in_section);
                            $section_display_name_raw = $first_field_config['section_label'] ?? $first_field_config['section'] ?? ucfirst(str_replace('-', ' ', $section_slug));
                        }


                        $tab_id_attr = 'tab-' . $section_slug;
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

                                                        // Usar $fields_to_render_in_section para la galería también
                                                        if (isset($fields_to_render_in_section[$image_key])) {
                                                            $image_config = $fields_to_render_in_section[$image_key];
                                                            $alt_config = $fields_to_render_in_section[$alt_key] ?? null;

                                                            $image_url = $image_config['current_value'] ?? $image_config['default'] ?? '';
                                                            $alt_text = '';
                                                            if ($alt_config) {
                                                                $alt_text = $alt_config['current_value'] ?? $alt_config['default'] ?? '';
                                                            }

                                                            $image_option_input_name = 'glory_content[' . esc_attr($image_key) . ']';
                                                            $alt_option_input_name = 'glory_content[' . esc_attr($alt_key) . ']';
                                                            $image_label = $image_config['label'] ?? sprintf(__('Imagen #%d', 'glory'), $i);
                                                    ?>
                                                            <div class="glory-gallery-item">
                                                                <div class="glory-image-preview">
                                                                    <?php if (!empty($image_url)): ?>
                                                                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php printf(esc_attr__('Vista previa para %s', 'glory'), esc_attr($image_label)); ?>">
                                                                    <?php else: ?>
                                                                        <p><?php _e('No hay imagen establecida', 'glory'); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="glory-image-controls">
                                                                    <input type="hidden" name="<?php echo esc_attr($image_option_input_name); ?>" value="<?php echo esc_url($image_url); ?>" class="glory-image-url-field">
                                                                    <button type="button" class="button glory-upload-image-button"><?php _e('Establecer/Cambiar Imagen', 'glory'); ?></button>
                                                                    <button type="button" class="button glory-remove-image-button"><?php _e('Quitar Imagen', 'glory'); ?></button>
                                                                </div>
                                                                <label for="<?php echo esc_attr($alt_key); ?>"><?php _e('Texto Alternativo:', 'glory'); ?> (<?php echo esc_html($image_label); ?>)</label>
                                                                <input type="text" id="<?php echo esc_attr($alt_key); ?>" name="<?php echo esc_attr($alt_option_input_name); ?>" value="<?php echo esc_attr($alt_text); ?>" class="regular-text">
                                                            </div>
                                                    <?php
                                                            $processed_gallery_keys[] = $image_key;
                                                            $processed_gallery_keys[] = $alt_key;
                                                        }
                                                    } ?>
                                                </div>
                                                <?php
                                                // Asegurarse de que $remaining_fields_in_section también use los campos filtrados
                                                $remaining_fields_in_section = array_diff_key($fields_to_render_in_section, array_flip($processed_gallery_keys));
                                                if (!empty($remaining_fields_in_section)):
                                                ?>
                                                    <hr>
                                                    <p><em><?php _e('Otros ajustes para esta sección:', 'glory'); ?></em></p>
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
                                                                        <?php renderFieldInput($key, $config, $current_value_for_field, $option_input_name);
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
                                                        <?php foreach ($fields_to_render_in_section as $key => $config): // Iterar sobre los campos filtrados
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
                                                                    <?php renderFieldInput($key, $config, $current_value_for_field, $option_input_name);
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
                                <?php if (!empty($fields_to_render_in_section)): ?>
                                    <div class="glory-form-actions">
                                        <?php submit_button(__('Guardar Cambios para esta Sección', 'glory'), 'primary', 'submit_save', false); ?>
                                    </div>
                                <?php endif; ?>
                            </form>
                            <?php // Formulario para el botón de reset
                            if (!empty($fields_to_render_in_section)): ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . $menu_slug_for_url_building . '&tab=' . $section_slug)); ?>" style="margin-top: 10px;">
                                    <input type="hidden" name="action" value="glory_reset_section">
                                    <input type="hidden" name="glory_active_section" value="<?php echo esc_attr($section_slug); ?>">
                                    <?php wp_nonce_field('glory_content_reset_action_' . $section_slug, '_wpnonce_glory_content_reset'); ?>
                                    <?php submit_button(__('Restablecer Esta Sección a los Valores Predeterminados', 'glory'), 'delete button-secondary', 'submit_reset', false, ['onclick' => "return confirm('" . esc_js(sprintf(__('¿Está seguro de que desea restablecer la sección "%s" a sus valores predeterminados? Esta acción no se puede deshacer.', 'glory'), esc_js($section_display_name_raw))) . "');"]); ?>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}


?>