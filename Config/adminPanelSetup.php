<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/*
// =========================================================================
// Example Glory Content Manager Field Registrations
// =========================================================================
//
// To test the admin panel, you can add registrations like these to your
// theme's functions.php or a relevant setup file that runs after
// the Glory Content Manager framework is loaded.
//
// Make sure Glory\Class\ContentManager is available.
//
// use Glory\Class\ContentManager;
//
// ContentManager::register('site_title', [
//     'default'     => 'My Awesome Website',
//     'type'        => 'text',
//     'label'       => 'Site Title',
//     'section'     => 'general',
//     'description' => 'The main title of the website.'
// ]);
//
// ContentManager::register('welcome_message', [
//     'default'     => '<p>Welcome to our site!</p>',
//     'type'        => 'richText',
//     'label'       => 'Welcome Message',
//     'section'     => 'homepage',
//     'description' => 'A welcome message displayed on the homepage.'
// ]);
//
// ContentManager::register('header_logo', [
//     'default'     => 'https://example.com/logo.png',
//     'type'        => 'image',
//     'label'       => 'Header Logo URL',
//     'section'     => 'general',
//     'description' => 'URL for the main site logo.'
// ]);
//
// ContentManager::register('business_hours', [
//     'default'     => json_encode([
//                        ['day' => 'Monday', 'open' => '09:00', 'close' => '17:00', 'status' => 'open'],
//                        ['day' => 'Tuesday', 'hours' => 'Closed', 'status' => 'closed']
//                      ], JSON_PRETTY_PRINT),
//     'type'        => 'schedule',
//     'label'       => 'Business Hours',
//     'section'     => 'contact',
//     'description' => 'Business opening hours (currently expects JSON).'
// ]);
//
// ContentManager::register('footer_copyright', [
//     'default'     => '© ' . date('Y') . ' All rights reserved.',
//     'type'        => 'text',
//     'label'       => 'Footer Copyright Text',
//     'section'     => 'footer'
// ]);
//
// ContentManager::register('analytics_code', [
//     'default'     => '<!-- GA Code -->',
//     'type'        => 'raw',
//     'label'       => 'Analytics Code',
//     'section'     => 'advanced',
//     'description' => 'Raw HTML/JS for analytics. Handled with wp_kses_post on save for now.'
// ]);
//
// ContentManager::register('another_general_field', [
//     'default'     => 'Another one',
//     'type'        => 'text',
//     'label'       => 'Another General Field',
//     'section'     => 'general',
//     'description' => 'Just to show multiple fields in one section.'
// ]);
//
*/

use Glory\Class\ContentManager;

class GloryContentAdminPanel {

    /**
     * Initialize the admin panel setup.
     * Hooks into WordPress to add the admin page.
     */
    public static function initialize() {
        add_action( 'admin_menu', [ self::class, 'addAdminPage' ] );
    }

    /**
     * Add the admin menu page.
     * This method is the callback for the 'admin_menu' action.
     */
    public static function addAdminPage() {
        add_menu_page(
            __( 'Glory Content Management', 'glory-content-manager' ), // Page title
            __( 'Glory Content', 'glory-content-manager' ),           // Menu title
            'manage_options',                                         // Capability
            'glory-content-manager',                                  // Menu slug
            [ self::class, 'renderAdminPage' ],                       // Callback function
            'dashicons-database-view',                                // Icon URL
            25                                                        // Position
        );
    }

    /**
     * Render the admin page.
     * This method is the callback for displaying the admin page content.
     */
    public static function renderAdminPage() {
        // Handle Revert to Default action
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'glory_revert_content' && isset( $_GET['field_key'] ) && isset( $_GET['_wpnonce'] ) ) {
            $field_key_to_revert = sanitize_key( $_GET['field_key'] );
            $nonce_action = 'glory_revert_content_action_' . $field_key_to_revert;

            if ( ! empty( $field_key_to_revert ) && wp_verify_nonce( $_GET['_wpnonce'], $nonce_action ) ) {
                check_admin_referer( $nonce_action, '_wpnonce' ); // Double check for safety, though wp_verify_nonce should suffice
                
                $option_name = ContentManager::OPTION_PREFIX . $field_key_to_revert;
                delete_option( $option_name );
                ContentManager::invalidateSingleFieldCache( $field_key_to_revert );
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php printf( esc_html__( "Field '%s' reverted to default successfully!", 'glory-content-manager' ), esc_html( $field_key_to_revert ) ); ?></p>
                </div>
                <?php
            } else {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e( 'Failed to revert field: Invalid action or security token.', 'glory-content-manager' ); ?></p>
                </div>
                <?php
            }
        }

        // Handle form submission (Save Action)
        if ( isset( $_POST['glory_content_save_nonce'] ) && wp_verify_nonce( $_POST['glory_content_save_nonce'], 'glory_content_save_action' ) ) {
            check_admin_referer( 'glory_content_save_action', 'glory_content_save_nonce' );

            $all_registered_fields = ContentManager::getRegisteredContentFields();
            $posted_data = isset( $_POST['glory_content'] ) && is_array( $_POST['glory_content'] ) ? $_POST['glory_content'] : [];

            foreach ( $posted_data as $key => $value ) {
                if ( ! isset( $all_registered_fields[ $key ] ) ) {
                    continue; // Skip if this key isn't a registered field
                }

                $field_type = $all_registered_fields[ $key ]['type'] ?? 'text';
                $sanitized_value = '';

                switch ( $field_type ) {
                    case 'richText':
                        $sanitized_value = wp_kses_post( $value );
                        break;
                    case 'text':
                        $sanitized_value = sanitize_text_field( $value );
                        break;
                    case 'image':
                        $sanitized_value = esc_url_raw( $value );
                        break;
                    case 'raw':
                        $sanitized_value = wp_kses_post( $value ); // Assuming HTML content
                        break;
                    case 'schedule':
                        $sanitized_value = sanitize_textarea_field( $value ); // For JSON or structured text
                        break;
                    default:
                        $sanitized_value = sanitize_text_field( $value );
                        break;
                }
                update_option( ContentManager::OPTION_PREFIX . $key, $sanitized_value );
            }
            // Invalidate cache for all fields after updating
            if ( ! empty( $posted_data ) ) {
                ContentManager::invalidateAllFieldCaches();
            }

            // Display success message
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Content saved successfully!', 'glory-content-manager' ); ?></p>
            </div>
            <?php
        }

        // Fetch fields again to get updated values (after potential revert or save)
        $registered_fields = ContentManager::getRegisteredContentFields();

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Glory Content Management', 'glory-content-manager' ); ?></h1>
            <style type="text/css">
                #glory-content-form h2 {
                    margin-top: 30px;
                    margin-bottom: 15px;
                    padding-bottom: 5px;
                    border-bottom: 1px solid #c3c4c7; /* WordPress admin border color */
                }
                #glory-content-form .form-table {
                    margin-bottom: 20px; /* Space after a table of fields */
                }
                .glory-revert-button {
                    margin-left: 10px;
                    vertical-align: middle;
                }
            </style>
            <?php if ( empty( $registered_fields ) ) : ?>
                <p><?php esc_html_e( 'No content fields have been registered yet.', 'glory-content-manager' ); ?></p>
            <?php else : ?>
                <form method="post" action="" id="glory-content-form">
                    <?php wp_nonce_field( 'glory_content_save_action', 'glory_content_save_nonce' ); ?>
                    <?php
                    // Group fields by section
                    $sections = [];
                foreach ( $registered_fields as $key => $field_data ) {
                    $section_name = $field_data['section'] ?? 'general';
                    if ( ! isset( $sections[ $section_name ] ) ) {
                        $sections[ $section_name ] = [];
                    }
                    $sections[ $section_name ][ $key ] = $field_data;
                }

                // Ensure 'general' section is first if it exists
                if ( isset( $sections['general'] ) ) {
                    $general_section = $sections['general'];
                    unset( $sections['general'] );
                    $sections = array_merge( [ 'general' => $general_section ], $sections );
                }

                foreach ( $sections as $section_name => $fields_in_section ) :
                ?>
                    <h2><?php printf( '%s: %s', esc_html__( 'Section', 'glory-content-manager' ), esc_html( ucfirst( $section_name ) ) ); ?></h2>
                    <table class="form-table">
                        <?php foreach ( $fields_in_section as $key => $field_data ) : ?>
                            <tr>
                                <th scope="row">
                                    <label for="glory_content_<?php echo esc_attr( $key ); ?>">
                                        <?php echo esc_html( $field_data['label'] ); ?>
                                    </label>
                                    <?php if ( ! empty( $field_data['description'] ) ) : ?>
                                        <p class="description">
                                            <?php echo esc_html( $field_data['description'] ); ?>
                                        </p>
                                    <?php endif; ?>
                                </th>
                                <td>
                                    <?php
                                    $current_value = $field_data['current_value'] ?? '';
                                    $field_name    = sprintf( 'glory_content[%s]', esc_attr( $key ) );

                                    switch ( $field_data['type'] ) {
                                        case 'text':
                                            ?>
                                            <input type="text"
                                                   id="glory_content_<?php echo esc_attr( $key ); ?>"
                                                   name="<?php echo esc_attr( $field_name ); ?>"
                                                   value="<?php echo esc_attr( $current_value ); ?>"
                                                   class="regular-text">
                                            <?php
                                            break;

                                        case 'raw':
                                            ?>
                                            <textarea id="glory_content_<?php echo esc_attr( $key ); ?>"
                                                      name="<?php echo esc_attr( $field_name ); ?>"
                                                      class="large-text"
                                                      rows="5"><?php echo esc_textarea( $current_value ); ?></textarea>
                                            <?php
                                            break;

                                        case 'richText':
                                            $editor_id = 'glory_content_' . esc_attr( $key );
                                            $content   = htmlspecialchars_decode( $current_value );
                                            wp_editor( $content, $editor_id, [
                                                'textarea_name' => $field_name,
                                                'media_buttons' => true,
                                                'textarea_rows' => 10,
                                            ] );
                                            break;

                                        case 'image':
                                            ?>
                                            <input type="text"
                                                   id="glory_content_<?php echo esc_attr( $key ); ?>"
                                                   name="<?php echo esc_attr( $field_name ); ?>"
                                                   value="<?php echo esc_attr( $current_value ); ?>"
                                                   class="regular-text">
                                            <p class="description">
                                                <?php esc_html_e( 'Ideally, this would be a media uploader.', 'glory-content-manager' ); ?>
                                            </p>
                                            <?php
                                            break;

                                        case 'schedule':
                                            $schedule_value = is_array( $current_value ) ? json_encode( $current_value, JSON_PRETTY_PRINT ) : $current_value;
                                            ?>
                                            <textarea id="glory_content_<?php echo esc_attr( $key ); ?>"
                                                      name="<?php echo esc_attr( $field_name ); ?>"
                                                      class="large-text"
                                                      rows="5"><?php echo esc_textarea( $schedule_value ); ?></textarea>
                                            <p class="description">
                                                <?php esc_html_e( 'Schedule input UI needs enhancement. Current value is JSON encoded if it\'s an array.', 'glory-content-manager' ); ?>
                                            </p>
                                            <?php
                                            break;

                                        default:
                                            printf(
                                                esc_html__( 'Unsupported field type: %s', 'glory-content-manager' ),
                                                esc_html( $field_data['type'] )
                                            );
                                            break;
                                    }

                                    // Add Revert to Default link if the option is set in the database
                                    $option_name_for_revert_check = ContentManager::OPTION_PREFIX . $key;
                                    if ( get_option( $option_name_for_revert_check ) !== false ) {
                                        $base_url     = admin_url( 'admin.php?page=glory-content-manager&action=glory_revert_content&field_key=' . esc_attr( $key ) );
                                        $nonce_action = 'glory_revert_content_action_' . $key;
                                        $revert_url   = wp_nonce_url( $base_url, $nonce_action, '_wpnonce' );
                                        ?>
                                        <a href="<?php echo esc_url( $revert_url ); ?>"
                                           class="button button-small glory-revert-button"
                                           onclick="return confirm('<?php printf( esc_js( __( 'Are you sure you want to revert the field \"%s\" to its default value?', 'glory-content-manager' ) ), esc_js( $field_data['label'] ) ); ?>');">
                                            <?php esc_html_e( 'Revert to Default', 'glory-content-manager' ); ?>
                                        </a>
                                        <?php
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endforeach; ?>
                    <?php submit_button( __( 'Save Content', 'glory-content-manager' ) ); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
}

// Initialize the admin panel
GloryContentAdminPanel::initialize();
