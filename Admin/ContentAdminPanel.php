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
        $actual_hook_for_page = 'toplevel_page_' . self::$menu_slug;
        if ($hook_suffix !== $actual_hook_for_page) {
            return;
        }

        wp_enqueue_media();

        $inline_css = "
            .glory-content-panel .nav-tab-wrapper { margin-bottom: 20px; }
            .glory-content-panel .glory-tab-content { display: none; }
            .glory-content-panel .glory-tab-content.active { display: block; }
            .glory-content-panel .postbox .hndle { cursor: default; padding: 8px 12px; }
            /* .glory-content-panel .glory-section-wrap { margin-bottom: 20px; } */ /* No longer needed per section, but per tab */
            .glory-image-preview img { border: 1px solid #ddd; margin-top: 5px; max-width:150px; max-height:150px; display:block; }
            .glory-schedule-editor .glory-schedule-day-row { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #ccc; }
            .glory-schedule-editor .glory-schedule-day-row:last-child { border-bottom: none; }
            .glory-schedule-editor label { margin-right: 10px; display: inline-block; }
            .glory-schedule-editor input[type='time'], .glory-schedule-editor select { vertical-align: middle; }
            .glory-content-panel .form-table th { width: 200px; }
            .glory-content-panel .form-table td .regular-text, .glory-content-panel .form-table td .large-text { width: 100%; max-width: 500px; }
            .glory-content-panel .form-table td textarea.large-text { min-height: 120px; }

            /* Styles for sidebar tabs */
            .glory-tabs-nav-container {
                width: 200px; /* Adjust as needed */
                padding-right: 20px;
                border-right: 1px solid #ccd0d4;
            }
            .glory-tabs-nav-container .nav-tab {
                display: block;
                margin-bottom: 5px; /* Space between tabs */
                border: 1px solid #ccd0d4;
                background: #f0f0f1;
            }
            .glory-tabs-nav-container .nav-tab-active {
                background: #fff;
                border-bottom-color: #ccd0d4; /* Or #fff if you want it to merge */
            }
            .glory-tabs-content-container {
                flex-grow: 1;
                padding-left: 20px;
            }
            .glory-tab-content .postbox { margin-top: 0; } /* Remove top margin for postbox inside tab */

            .glory-content-panel {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .postbox {
                position: relative;
                min-width: 255px;
                border: 1px solid #c3c4c7;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                background: #fff;
                padding: 15px;
            }

            .glory-tabs-container-two {
                display: flex;
            }
            #wpbody-content .metabox-holder {
                padding-top: 0px; 
            }
        ";
        wp_add_inline_style('wp-admin', $inline_css);

        wp_add_inline_script('jquery', self::get_image_uploader_js() . self::get_tabs_js());
    }

    private static function get_image_uploader_js(): string
    {
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

    private static function get_tabs_js(): string
    {
        return "
        jQuery(document).ready(function($) {
            var gloryTabs = $('.glory-tabs-nav-container .nav-tab');
            var gloryTabContents = $('.glory-tab-content');

            function activateTab(tabLink) {
                var tabId = $(tabLink).attr('href'); // e.g., #tab-general

                gloryTabs.removeClass('nav-tab-active');
                $(tabLink).addClass('nav-tab-active');

                gloryTabContents.removeClass('active').hide();
                $(tabId).addClass('active').show();

                // Update URL without reloading page
                if (history.pushState) {
                    var newUrl = window.location.protocol + '//' + window.location.host + window.location.pathname + '?page=" . self::$menu_slug . "&tab=' + tabId.substring(5); // remove #tab-
                    history.pushState({path: newUrl}, '', newUrl);
                }
            }

            // Activate tab based on URL hash or first tab
            var initialTab = window.location.hash; // e.g. #tab-general
            if (initialTab && $(initialTab).length) {
                var correspondingLink = $('.glory-tabs-nav-container .nav-tab[href=\"' + initialTab + '\"]');
                if (correspondingLink.length) {
                    activateTab(correspondingLink);
                }
            } else if (gloryTabs.length > 0) {
                 // Try to get tab from query param if no hash
                const urlParams = new URLSearchParams(window.location.search);
                const queryTab = urlParams.get('tab');
                let activatedFromQuery = false;
                if (queryTab) {
                    var targetTabLink = $('.glory-tabs-nav-container .nav-tab[data-tab-id=\"' + queryTab + '\"]');
                    if (targetTabLink.length) {
                        activateTab(targetTabLink);
                        activatedFromQuery = true;
                    }
                }
                if (!activatedFromQuery && gloryTabs.first().length) {
                     activateTab(gloryTabs.first());
                }
            }


            gloryTabs.on('click', function(e) {
                e.preventDefault();
                activateTab(this);
                // Update window.location.hash so that if user refreshes, they stay on the same tab
                window.location.hash = $(this).attr('href').substring(1); // remove #
            });
        });
        ";
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
        // Check if our specific form was submitted
        if (
            isset($_POST['action']) && $_POST['action'] === 'glory_save_content' &&
            isset($_POST['_wpnonce_glory_content_save']) && isset($_POST['glory_active_section'])
        ) {

            $active_section_key = sanitize_text_field($_POST['glory_active_section']);

            if (wp_verify_nonce($_POST['_wpnonce_glory_content_save'], 'glory_content_save_action_' . $active_section_key)) {
                self::handle_save_data($active_section_key);
            } else {
                wp_die(__('Nonce verification failed!', 'glory'), __('Error', 'glory'), ['response' => 403]);
            }
        }
    }

    private static function handle_save_data(string $active_section_key): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'glory'));
        }

        GloryLogger::info("ContentAdminPanel: Handling save action for section: {$active_section_key}.");

        $all_registered_fields = ContentManager::getRegisteredContentFields();
        $posted_options = $_POST['glory_content'] ?? [];
        $fields_in_current_section = [];

        // Filter fields to only those in the active section
        foreach ($all_registered_fields as $key => $config) {
            if (($config['section'] ?? 'general') === $active_section_key) {
                $fields_in_current_section[$key] = $config;
            }
        }

        if (empty($fields_in_current_section)) {
            GloryLogger::info("ContentAdminPanel: No fields found for section {$active_section_key} during save.");
            // Optionally add a settings error if this is unexpected
            // add_settings_error('glory_content_messages', 'glory_no_fields_in_section', __('No fields to save for this section.', 'glory'), 'warning');
        }

        foreach ($fields_in_current_section as $key => $config) {
            $option_name = ContentManager::OPTION_PREFIX . $key;
            $panel_value_exists_in_post = array_key_exists($key, $posted_options);
            $value_to_save = null;

            if ($config['type'] === 'schedule') {
                $schedule_data = [];
                if ($panel_value_exists_in_post && is_array($posted_options[$key])) {
                    foreach ($posted_options[$key] as $day_schedule_data) {
                        // Ensure 'day' exists, sanitize, and reconstruct
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
                        GloryLogger::info("ContentAdminPanel: Raw field '{$key}' saved as decoded JSON.");
                    } else {
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
                    $value_to_save = $config['default'] ?? []; // Default for raw might be an empty array/object
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
                $value_to_save = in_array($config['type'], ['text', 'image', 'richText']) ? '' : ($config['default'] ?? '');
            }

            GloryLogger::info("ContentAdminPanel: Preparing to save key '{$key}'. Option name: '{$option_name}'. Value to save: " . print_r($value_to_save, true));
            GloryLogger::info("ContentAdminPanel: Code default for '{$key}' at save time: " . print_r(ContentManager::getCodeDefaultHash($key), true));

            update_option($option_name, $value_to_save);
            update_option($option_name . ContentManager::OPTION_META_PANEL_SAVED_SUFFIX, true);
            update_option($option_name . ContentManager::OPTION_META_CODE_HASH_SUFFIX, ContentManager::getCodeDefaultHash($key));
            GloryLogger::info("ContentAdminPanel: AFTER update_option for '{$option_name}'. Check DB now if possible.");
        }

        add_settings_error('glory_content_messages', 'glory_content_message', __('Settings Saved for section:', 'glory') . ' ' . esc_html(ucfirst($active_section_key)), 'updated');
        set_transient('settings_errors', get_settings_errors(), 30);

        // Redirect back to the current tab
        $redirect_url = admin_url('admin.php?page=' . self::$menu_slug . '&tab=' . $active_section_key . '&settings-updated=true');
        wp_redirect($redirect_url);
        exit;
    }

    public static function render_admin_page_html(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'glory'));
        }

        $all_fields = ContentManager::getRegisteredContentFields();
        $fields_by_section = [];
        foreach ($all_fields as $key => $config) {
            $section_slug = $config['section'] ?? 'general';
            // Ensure section slug is safe for use in HTML attributes
            $section_slug = sanitize_title($section_slug);
            $fields_by_section[$section_slug][$key] = $config;
        }
        ksort($fields_by_section);

        // Determine active tab
        $default_tab = !empty($fields_by_section) ? array_key_first($fields_by_section) : 'general';
        $active_tab = isset($_GET['tab']) ? sanitize_title($_GET['tab']) : $default_tab;
        if (!array_key_exists($active_tab, $fields_by_section) && $active_tab !== 'general' && !empty($fields_by_section)) {
            $active_tab = $default_tab; // Fallback if tab from GET is invalid
        }

?>
        <div class="wrap glory-content-panel">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php
            // settings_errors() displays notices and errors from add_settings_error()
            settings_errors('glory_content_messages');
            ?>
            <div class="glory-tabs-container-two">
                <div class="glory-tabs-nav-container">
                    <?php if (!empty($fields_by_section)): ?>
                        <?php foreach ($fields_by_section as $section_slug => $fields_in_section): ?>
                            <?php
                            // Use label from the first field in section if available, or default to slug
                            $section_label = ucfirst(str_replace('-', ' ', $section_slug)); // Default label
                            if (!empty($fields_in_section)) {
                                $first_field_config = reset($fields_in_section);
                                $section_label = $first_field_config['section_label'] ?? $first_field_config['section'] ?? $section_label;
                            }
                            $tab_id_attr = 'tab-' . $section_slug;
                            ?>
                            <a href="#<?php echo esc_attr($tab_id_attr); ?>"
                                class="nav-tab <?php echo $active_tab === $section_slug ? 'nav-tab-active' : ''; ?>"
                                data-tab-id="<?php echo esc_attr($section_slug); ?>">
                                <?php echo esc_html(ucfirst($section_label)); ?>
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
                            // Use label from the first field in section if available, or default to slug
                            $section_display_name = ucfirst(str_replace('-', ' ', $section_slug)); // Default label
                            if (!empty($fields_in_section)) {
                                $first_field_config = reset($fields_in_section);
                                $section_display_name = $first_field_config['section_label'] ?? $first_field_config['section'] ?? $section_display_name;
                            }
                            ?>
                            <div id="<?php echo esc_attr($tab_id_attr); ?>" class="glory-tab-content <?php echo $active_tab === $section_slug ? 'active' : ''; ?>">
                                <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=' . self::$menu_slug . '&tab=' . $section_slug)); ?>">
                                    <input type="hidden" name="action" value="glory_save_content">
                                    <input type="hidden" name="glory_active_section" value="<?php echo esc_attr($section_slug); ?>">
                                    <?php wp_nonce_field('glory_content_save_action_' . $section_slug, '_wpnonce_glory_content_save'); ?>

                                    <div class="metabox-holder">
                                        <div class="postbox">
                                            <h2 class="hndle"><span><?php echo esc_html(ucfirst($section_display_name)); ?></span></h2>
                                            <div class="inside">
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
                                                                    <?php self::render_field_input_control($key, $config, $current_value_for_field, $option_input_name); ?>
                                                                    <?php if ($description): ?>
                                                                        <p class="description"><?php echo wp_kses_post($description); ?></p>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
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
    }

    // render_field_input_control y render_schedule_input_control permanecen igual que tu versión original
    // porque su lógica interna de renderizado de campos no necesita cambiar para el sistema de pestañas.
    // Solo los he copiado aquí para completitud.

    private static function render_field_input_control(string $key, array $config, $current_value, string $option_input_name): void
    {
        $field_id = esc_attr($key);
        $type = $config['type'] ?? 'text';

        switch ($type) {
            case 'text':
                echo '<input type="text" id="' . $field_id . '" name="' . $option_input_name . '" value="' . esc_attr($current_value) . '" class="regular-text">';
                break;
            case 'raw':
                $value_for_textarea = $current_value;
                if (is_array($value_for_textarea) || is_object($value_for_textarea)) {
                    $json_string = json_encode($value_for_textarea, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    if ($json_string === false) {
                        $value_for_textarea = '/* Error al codificar a JSON: ' . json_last_error_msg() . ' */';
                        GloryLogger::error("ContentAdminPanel: Error encoding raw field '{$key}' to JSON for display. Value: " . print_r($current_value, true));
                    } else {
                        $value_for_textarea = $json_string;
                    }
                } elseif ($value_for_textarea === null) {
                    $value_for_textarea = '';
                }
                if (!is_string($value_for_textarea)) {
                    $value_for_textarea = (string) $value_for_textarea;
                }
                echo '<textarea id="' . $field_id . '" name="' . $option_input_name . '" rows="10" class="large-text">' . esc_textarea($value_for_textarea) . '</textarea>';
                echo '<p class="description">' . __('Enter valid JSON. If content is not valid JSON, it will be saved as a raw string.', 'glory') . '</p>';
                break;
            case 'richText':
                $value_for_richtext_area = is_string($current_value) ? $current_value : '';
                echo '<textarea id="' . $field_id . '" name="' . $option_input_name . '" rows="10" class="large-text wp-editor-area">' . esc_textarea($value_for_richtext_area) . '</textarea>';
                // For actual rich text editor, you'd enqueue wp_editor and use it.
                // For now, it's a textarea, but wp_kses_post will apply on save.
                echo '<p class="description">' . __('HTML is allowed. Content will be filtered by wp_kses_post on save.', 'glory') . '</p>';
                break;
            case 'image':
                $image_url = is_string($current_value) ? $current_value : '';
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
                self::render_schedule_input_control($key, is_array($current_value) ? $current_value : [], $option_input_name);
                break;
            default:
                echo '<input type="text" id="' . $field_id . '" name="' . $option_input_name . '" value="' . esc_attr(is_scalar($current_value) ? $current_value : '') . '" class="regular-text">';
                GloryLogger::info("ContentAdminPanel: Unknown field type '{$type}' for key '{$key}'. Defaulting to text input.");
        }
    }

    private static function render_schedule_input_control(string $key, array $schedule_data, string $base_input_name): void
    {
        $schedule_data = is_array($schedule_data) ? $schedule_data : [];

        $days_of_week_ordered = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        $schedule_map = [];

        foreach ($schedule_data as $entry) {
            if (isset($entry['day'])) {
                $schedule_map[$entry['day']] = $entry;
            }
        }

        echo '<div class="glory-schedule-editor">';
        foreach ($days_of_week_ordered as $idx => $day_name_label) {
            $entry = $schedule_map[$day_name_label] ?? ['day' => $day_name_label, 'status' => 'closed', 'open' => '', 'close' => ''];

            $status = esc_attr($entry['status'] ?? 'closed');
            $open_time = esc_attr($entry['open'] ?? '');
            $close_time = esc_attr($entry['close'] ?? '');

            // Using index for POST array
            $post_index = $idx;

            $input_name_day    = $base_input_name . '[' . $post_index . '][day]';
            $input_name_status = $base_input_name . '[' . $post_index . '][status]';
            $input_name_open   = $base_input_name . '[' . $post_index . '][open]';
            $input_name_close  = $base_input_name . '[' . $post_index . '][close]';
            $unique_id_prefix = esc_attr($key . '_' . strtolower(str_replace(' ', '_', $day_name_label)));

            echo '<div class="glory-schedule-day-row">';
            echo '<strong>' . esc_html($day_name_label) . '</strong><input type="hidden" name="' . $input_name_day . '" value="' . esc_attr($day_name_label) . '">';
            echo '<br>';
            echo '<label for="' . $unique_id_prefix . '_status">' . __('Status:', 'glory') . ' </label>';
            echo '<select id="' . $unique_id_prefix . '_status" name="' . $input_name_status . '">';
            echo '<option value="open" ' . selected($status, 'open', false) . '>' . __('Open', 'glory') . '</option>';
            echo '<option value="closed" ' . selected($status, 'closed', false) . '>' . __('Closed', 'glory') . '</option>';
            echo '</select>';
            echo '<label for="' . $unique_id_prefix . '_open">' . __('Open:', 'glory') . ' <input id="' . $unique_id_prefix . '_open" type="time" name="' . $input_name_open . '" value="' . $open_time . '"></label>';
            echo '<label for="' . $unique_id_prefix . '_close">' . __('Close:', 'glory') . ' <input id="' . $unique_id_prefix . '_close" type="time" name="' . $input_name_close . '" value="' . $close_time . '"></label>';
            echo '</div>';
        }
        echo '</div>';
    }
}
