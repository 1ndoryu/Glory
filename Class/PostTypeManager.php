<?php
# App/Glory/Class/PostTypeManager.php

namespace Glory\Class;

use Glory\Class\GloryLogger; // Asume que GloryLogger está disponible y autocargado
use WP_Error;               // Import WP_Error for type hinting and checking
use WP_Post;                // Import WP_Post for type hinting in the hook callback

# Ensure this file is included only once
if (!class_exists('PostTypeManager')) {

    /**
     * Manages the definition and registration of Custom Post Types within the Glory Framework,
     * including setting default meta values for new posts.
     */
    class PostTypeManager
    {
        /**
         * Stores the definitions of CPTs to manage.
         * Key: post type slug. Value: array containing 'args', 'singular', 'plural', 'default_meta'.
         * @var array
         */
        private static $postTypes = [];

        /**
         * Define a Custom Post Type to be managed by the theme.
         *
         * @param string $post_type The post type slug (max 20 characters, lowercase alphanumeric/underscore).
         * @param array $args An array of arguments compatible with WordPress's register_post_type function.
         * @param string|null $singular_name (Optional) Singular name for the post type. Used for automatic label generation if $args['labels'] is not set.
         * @param string|null $plural_name (Optional) Plural name for the post type. Used with $singular_name for label generation.
         * @param array $default_meta (Optional) An associative array of default meta fields and their values to add when a new post of this type is created. Example: ['my_meta_key' => 'default value', 'price' => 0].
         */
        public static function define(
            string $post_type,
            array $args,
            ?string $singular_name = null,
            ?string $plural_name = null,
            array $default_meta = [] // <-- Nuevo parámetro para metas por defecto
        ): void {
            // --- Input validation for post type slug ---
            if (empty($post_type)) {
                GloryLogger::error("PostTypeManager: Post type slug cannot be empty.");
                return;
            }
            if (strlen($post_type) > 20) {
                GloryLogger::error("PostTypeManager: Post type slug '{$post_type}' exceeds maximum 20 characters.");
                return;
            }
            if (!preg_match('/^[a-z0-9_]+$/', $post_type)) {
                GloryLogger::error("PostTypeManager: Invalid post type slug '{$post_type}'. Slugs must be lowercase alphanumeric with underscores.");
                return;
            }
            $reserved_types = ['post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'action', 'author', 'order', 'theme'];
            if (in_array($post_type, $reserved_types, true)) {
                GloryLogger::error("PostTypeManager: Post type slug '{$post_type}' is reserved by WordPress. Choose a different slug.");
                return;
            }
            if (isset(self::$postTypes[$post_type])) {
                GloryLogger::info("PostTypeManager: Post type '{$post_type}' is already defined. Skipping duplicate definition.");
                return;
            }

            // --- Automatic Label Generation ---
            if ($singular_name && $plural_name && !isset($args['labels'])) {
                GloryLogger::info("PostTypeManager: Generating labels for '{$post_type}' using singular '{$singular_name}' and plural '{$plural_name}'.");
                $args['labels'] = self::_generateLabels($singular_name, $plural_name);
            } elseif (($singular_name || $plural_name) && !isset($args['labels'])) {
                GloryLogger::error("PostTypeManager: Both singular and plural names are required for label generation for '{$post_type}' if 'labels' argument is not provided.");
            }

            // --- Sensible Defaults ---
            if (!isset($args['public'])) {
                $args['public'] = true;
                GloryLogger::info("PostTypeManager: Setting 'public' to true by default for '{$post_type}'.");
            }
            // Set default supports if labels were generated and supports not set
            if (isset($args['labels']) && !isset($args['supports']) && $singular_name && $plural_name) {
                $args['supports'] = ['title', 'editor', 'thumbnail'];
                GloryLogger::info("PostTypeManager: Setting default 'supports' (title, editor, thumbnail) for '{$post_type}' due to generated labels.");
            }
            // Ensure 'supports' is an array before trying to add 'custom-fields'
            if (!isset($args['supports']) || !is_array($args['supports'])) {
                 $args['supports'] = []; // Initialize as array if not set or not an array
            }
            // Ensure 'custom-fields' support is added if default meta is used (good practice, though add_post_meta works regardless)
            if (!empty($default_meta) && !in_array('custom-fields', $args['supports'], true)) {
                $args['supports'][] = 'custom-fields';
                GloryLogger::info("PostTypeManager: Adding 'custom-fields' to 'supports' for '{$post_type}' as default meta fields are defined.");
            } elseif (!in_array('custom-fields', $args['supports'], true)) {
                 // Optionally always add 'custom-fields' support if you expect metas often
                 // $args['supports'][] = 'custom-fields';
                 // GloryLogger::info("PostTypeManager: Adding 'custom-fields' to 'supports' for '{$post_type}'.");
            }


            // --- Store Definition ---
            self::$postTypes[$post_type] = [
                'args'         => $args,
                'singular'     => $singular_name,
                'plural'       => $plural_name,
                'default_meta' => $default_meta, // <-- Store default meta definitions
            ];

            $log_message = "PostTypeManager: Defined post type '{$post_type}'.";
            if (!empty($default_meta)) {
                $log_message .= " With default meta keys: " . implode(', ', array_keys($default_meta));
            }
            GloryLogger::info($log_message, ['args' => $args, 'default_meta' => $default_meta]);
        }

        /**
         * Generates a basic array of labels for a custom post type.
         *
         * @param string $singular Singular name (e.g., 'Movie').
         * @param string $plural Plural name (e.g., 'Movies').
         * @return array Array suitable for the 'labels' argument of register_post_type.
         */
        private static function _generateLabels(string $singular, string $plural): array
        {
            $text_domain = 'glory'; // Replace 'glory' with your actual theme/plugin text domain

            return [
                'name'                  => _x($plural, 'Post type general name', $text_domain),
                'singular_name'         => _x($singular, 'Post type singular name', $text_domain),
                'menu_name'             => _x($plural, 'Admin Menu text', $text_domain),
                'name_admin_bar'        => _x($singular, 'Add New on Toolbar', $text_domain),
                'add_new'               => __('Add New', $text_domain),
                'add_new_item'          => sprintf(__('Add New %s', $text_domain), $singular),
                'new_item'              => sprintf(__('New %s', $text_domain), $singular),
                'edit_item'             => sprintf(__('Edit %s', $text_domain), $singular),
                'view_item'             => sprintf(__('View %s', $text_domain), $singular),
                'view_items'            => sprintf(__('View %s', $text_domain), $plural), // Added in WP 5.0+
                'all_items'             => sprintf(__('All %s', $text_domain), $plural),
                'search_items'          => sprintf(__('Search %s', $text_domain), $plural),
                'parent_item_colon'     => sprintf(__('Parent %s:', $text_domain), $singular),
                'not_found'             => sprintf(__('No %s found.', $text_domain), strtolower($plural)),
                'not_found_in_trash'    => sprintf(__('No %s found in Trash.', $text_domain), strtolower($plural)),
                'featured_image'        => _x('Featured Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', $text_domain),
                'set_featured_image'    => _x('Set featured image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', $text_domain),
                'remove_featured_image' => _x('Remove featured image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', $text_domain),
                'use_featured_image'    => _x('Use as featured image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', $text_domain),
                'archives'              => _x(sprintf('%s Archives', $singular), 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', $text_domain),
                'insert_into_item'      => _x(sprintf('Insert into %s', strtolower($singular)), 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', $text_domain),
                'uploaded_to_this_item' => _x(sprintf('Uploaded to this %s', strtolower($singular)), 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', $text_domain),
                'filter_items_list'     => _x(sprintf('Filter %s list', strtolower($plural)), 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', $text_domain),
                'items_list_navigation' => _x(sprintf('%s list navigation', $plural), 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', $text_domain),
                'items_list'            => _x(sprintf('%s list', $plural), 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', $text_domain),
                // Puedes añadir más etiquetas específicas si las necesitas
            ];
        }

        /**
         * Registers WordPress hooks.
         * Call this once during your theme/plugin setup.
         */
        public static function register(): void
        {
            add_action('init', [self::class, 'processPostTypes'], 10);

            // Flush rewrite rules on activation/deactivation (important!)
            // Asegúrate de que este fichero se cargue cuando se ejecuten estos hooks
            // register_activation_hook( YOUR_PLUGIN_MAIN_FILE or THEME_SETUP_FILE, [self::class, 'flushRewriteRules']);
            // register_deactivation_hook( YOUR_PLUGIN_MAIN_FILE or THEME_SETUP_FILE, [self::class, 'flushRewriteRules']);
        }

        /**
         * Registers the defined Custom Post Types and sets up hooks for default meta.
         * @internal Callback for the 'init' action. Do not call directly.
         */
        public static function processPostTypes(): void
        {
            if (empty(self::$postTypes)) {
                GloryLogger::info("PostTypeManager processPostTypes: No post types defined.");
                return;
            }

            GloryLogger::info("PostTypeManager processPostTypes: Processing " . count(self::$postTypes) . " post type definitions.");

            foreach (self::$postTypes as $post_type => $definition) {

                GloryLogger::info("PostTypeManager processPostTypes: Registering post type '{$post_type}'.", ['args' => $definition['args']]);

                // Register the post type
                $result = register_post_type($post_type, $definition['args']);

                // Check for registration errors
                if (is_wp_error($result)) {
                    GloryLogger::error("PostTypeManager: FAILED to register post type '{$post_type}'.", [
                        'error_code' => $result->get_error_code(),
                        'error_message' => $result->get_error_message(),
                        'args_used' => $definition['args']
                    ]);
                    continue; // Skip to next CPT if registration failed
                } else {
                    // GloryLogger::info("PostTypeManager: Successfully registered/updated post type '{$post_type}'.");
                }

                // --- Add Hook for Default Meta Fields ---
                // Check if default meta fields are defined and not empty for this CPT
                if (!empty($definition['default_meta']) && is_array($definition['default_meta'])) {
                    // Use the dynamic save_post_{post_type} hook which fires after a post is saved.
                    // Priority 10, accepts 3 arguments ($post_id, $post, $update)
                    add_action('save_post_' . $post_type, [self::class, 'addDefaultMeta'], 10, 3);
                    GloryLogger::info("PostTypeManager processPostTypes: Added hook 'save_post_{$post_type}' for default meta fields.");
                }
                 // --- End Hook for Default Meta ---
            }
            GloryLogger::info("PostTypeManager processPostTypes: Finished processing definitions.");
            // Avoid flush_rewrite_rules() here for performance reasons.
        }

        /**
         * Adds default meta values to a post when it's first created.
         * @internal Callback for the save_post_{$post->post_type} action hook.
         *
         * @param int     $post_id The ID of the post being saved.
         * @param WP_Post $post    The post object being saved.
         * @param bool    $update  Whether this is an update to an existing post. False if it's a new post.
         */
        public static function addDefaultMeta(int $post_id, WP_Post $post, bool $update): void
        {
            // --- Prevent Unnecessary Execution ---
            // Bail if this is an autosave.
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            // Bail if this is a post revision.
            if (wp_is_post_revision($post_id)) {
                return;
            }
            // Bail if this is an update to an existing post (we only want to set defaults on creation).
            if ($update) {
                return;
            }
            // Bail if the post type doesn't match expectations (safety check, though the hook is specific)
            $post_type = $post->post_type;
            if (!isset(self::$postTypes[$post_type])) {
                // This shouldn't happen if the hook was added correctly, but good to check.
                GloryLogger::info("PostTypeManager addDefaultMeta: Hook triggered for post ID {$post_id}, but post type '{$post_type}' definition not found internally.", ['post_status' => $post->post_status]);
                return;
            }

            // --- Main Logic ---
            $definition = self::$postTypes[$post_type];

            // Check again if default meta is defined and not empty for this type
            if (!empty($definition['default_meta']) && is_array($definition['default_meta'])) {

                $default_meta = $definition['default_meta'];
                GloryLogger::info("PostTypeManager addDefaultMeta: Processing default meta for NEW post ID {$post_id} of type '{$post_type}'.", ['default_meta' => $default_meta]);

                foreach ($default_meta as $meta_key => $default_value) {
                    // Use add_post_meta() with $unique set to true.
                    // This ensures the meta field is only added if it doesn't already exist for this post.
                    // It returns the meta_id on success, false on failure or if the key already exists ($unique = true).
                    $added = add_post_meta($post_id, $meta_key, $default_value, true);

                    if ($added !== false) {
                        GloryLogger::info("PostTypeManager addDefaultMeta: Added default meta key '{$meta_key}' (value: '{$default_value}') for post ID {$post_id}.");
                    } else {
                        // Check if it failed because the key already existed (perhaps added by another process between save_post and this hook execution)
                        $existing_value = get_post_meta($post_id, $meta_key, true);
                        if ($existing_value !== '') {
                            // Meta key already existed, so we didn't overwrite it (which is intended behavior with $unique = true).
                            GloryLogger::info("PostTypeManager addDefaultMeta: Meta key '{$meta_key}' already exists for post ID {$post_id}. Default value was NOT applied.", ['existing_value' => $existing_value]);
                        } else {
                            // add_post_meta truly failed for some other reason.
                            GloryLogger::error("PostTypeManager addDefaultMeta: FAILED to add meta key '{$meta_key}' for post ID {$post_id}. add_post_meta returned false.");
                        }
                    }
                }
                 GloryLogger::info("PostTypeManager addDefaultMeta: Finished processing default meta for post ID {$post_id}.");
            }
        }

        /**
         * Flush WordPress rewrite rules.
         * Call this on theme/plugin activation and deactivation to ensure CPT slugs and archives work correctly.
         * @internal
         */
        public static function flushRewriteRules(): void
        {
            // Ensure CPTs are registered before flushing, especially important on activation.
            self::processPostTypes();
            flush_rewrite_rules();
            GloryLogger::info("PostTypeManager: Rewrite rules flushed.");
        }

    } 

} 