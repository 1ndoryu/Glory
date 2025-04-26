<?php
# App/Glory/Class/PostTypeManager.php

namespace Glory\Class;

use Glory\Class\GloryLogger;
use WP_Error; // Import WP_Error for type hinting and checking

# Ensure this file is included only once
if (!class_exists('PostTypeManager')) {

    /**
     * Manages the definition and registration of Custom Post Types within the Glory Framework.
     */
    class PostTypeManager
    {
        /**
         * Stores the definitions of CPTs to manage for the current request.
         * Key: post type slug. Value: array containing 'args', 'singular', 'plural'.
         * @var array
         */
        private static $postTypes = [];

        /**
         * Define a Custom Post Type to be managed by the theme.
         *
         * @param string $post_type The post type slug (max 20 characters, lowercase alphanumeric/underscore).
         * @param array $args An array of arguments compatible with WordPress's register_post_type function.
         *                    See: https://developer.wordpress.org/reference/functions/register_post_type/#parameters
         * @param string|null $singular_name (Optional) Singular name for the post type (e.g., 'Movie'). If provided AND $args['labels'] is not set, basic labels will be generated.
         * @param string|null $plural_name (Optional) Plural name for the post type (e.g., 'Movies'). Used with $singular_name for label generation.
         */
        public static function define(string $post_type, array $args, ?string $singular_name = null, ?string $plural_name = null): void
        {
            // Input validation for post type slug
            if (empty($post_type)) {
                GloryLogger::error("PostTypeManager: Post type slug cannot be empty.");
                return;
            }
            if (strlen($post_type) > 20) {
                GloryLogger::error("PostTypeManager: Post type slug '{$post_type}' exceeds maximum 20 characters.");
                // Optionally truncate or just return
                return;
            }
            // Basic check for valid characters (WordPress allows underscores)
            if (!preg_match('/^[a-z0-9_]+$/', $post_type)) {
                GloryLogger::error("PostTypeManager: Invalid post type slug '{$post_type}'. Slugs must be lowercase alphanumeric with underscores.");
                return;
            }
            // Reserved post types check
            $reserved_types = ['post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'action', 'author', 'order', 'theme'];
            if (in_array($post_type, $reserved_types, true)) {
                GloryLogger::error("PostTypeManager: Post type slug '{$post_type}' is reserved by WordPress. Choose a different slug.");
                return;
            }

            // Check if already defined
            if (isset(self::$postTypes[$post_type])) {
                GloryLogger::info("PostTypeManager: Post type '{$post_type}' is already defined. Skipping duplicate definition.");
                return;
            }

            // --- Automatic Label Generation (Optional) ---
            if ($singular_name && $plural_name && !isset($args['labels'])) {
                GloryLogger::info("PostTypeManager: Generating labels for '{$post_type}' using singular '{$singular_name}' and plural '{$plural_name}'.");
                $args['labels'] = self::_generateLabels($singular_name, $plural_name);
            } elseif (($singular_name || $plural_name) && !isset($args['labels'])) {
                GloryLogger::error("PostTypeManager: Both singular and plural names are required for label generation for '{$post_type}' if 'labels' argument is not provided.");
                // Decide if you want to proceed without labels or return
                // return;
            }
            // --- End Label Generation ---

            // Ensure 'public' defaults reasonably if not set (common oversight)
            if (!isset($args['public'])) {
                $args['public'] = true; // Default to public
                GloryLogger::info("PostTypeManager: Setting 'public' to true by default for '{$post_type}'.");
            }
            // Ensure 'supports' is set if basic labels were generated (otherwise might not get title/editor)
            if (isset($args['labels']) && !isset($args['supports']) && $singular_name && $plural_name) {
                $args['supports'] = ['title', 'editor', 'thumbnail']; // Sensible defaults when auto-generating labels
                GloryLogger::info("PostTypeManager: Setting default 'supports' (title, editor, thumbnail) for '{$post_type}' due to generated labels.");
            }


            self::$postTypes[$post_type] = [
                'args'     => $args,
                'singular' => $singular_name, // Store for potential future use/reference
                'plural'   => $plural_name,   // Store for potential future use/reference
            ];

            GloryLogger::info("PostTypeManager: Defined post type '{$post_type}'.");
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
            // Basic text domain - replace 'glory' with your actual theme/plugin text domain
            $text_domain = 'glory';

            return [
                'name'                  => _x($plural, 'Post type general name', $text_domain),
                'singular_name'         => _x($singular, 'Post type singular name', $text_domain),
                'menu_name'             => _x($plural, 'Admin Menu text', $text_domain),
                'name_admin_bar'        => _x($singular, 'Add New on Toolbar', $text_domain),
                'add_new'               => __('Add New', $text_domain),
                'add_new_item'          => __('Add New ' . $singular, $text_domain),
                'new_item'              => __('New ' . $singular, $text_domain),
                'edit_item'             => __('Edit ' . $singular, $text_domain),
                'view_item'             => __('View ' . $singular, $text_domain),
                'all_items'             => __('All ' . $plural, $text_domain),
                'search_items'          => __('Search ' . $plural, $text_domain),
                'parent_item_colon'     => __('Parent ' . $singular . ':', $text_domain),
                'not_found'             => __('No ' . strtolower($plural) . ' found.', $text_domain),
                'not_found_in_trash'    => __('No ' . strtolower($plural) . ' found in Trash.', $text_domain),
                'featured_image'        => _x('Featured Image', $singular . ' featured image', $text_domain),
                'set_featured_image'    => _x('Set featured image', $singular . ' featured image', $text_domain),
                'remove_featured_image' => _x('Remove featured image', $singular . ' featured image', $text_domain),
                'use_featured_image'    => _x('Use as featured image', $singular . ' featured image', $text_domain),
                'archives'              => _x($plural . ' Archives', $singular . ' archives', $text_domain),
                'insert_into_item'      => _x('Insert into ' . strtolower($singular), 'insert into ' . strtolower($singular), $text_domain),
                'uploaded_to_this_item' => _x('Uploaded to this ' . strtolower($singular), 'uploaded to this ' . strtolower($singular), $text_domain),
                'filter_items_list'     => _x('Filter ' . strtolower($plural) . ' list', 'filter ' . strtolower($plural) . ' list', $text_domain),
                'items_list_navigation' => _x($plural . ' list navigation', $plural . ' list navigation', $text_domain),
                'items_list'            => _x($plural . ' list', $plural . ' list', $text_domain),
            ];
        }

        /**
         * Registers WordPress hooks.
         * Call this once during your theme/plugin setup.
         */
        public static function register(): void
        {
            // CPTs must be registered during the 'init' action.
            // Priority 10 is standard, but can be earlier if needed.
            add_action('init', [self::class, 'processPostTypes'], 10);

            // Flush rewrite rules on activation/deactivation - IMPORTANT for CPTs with custom slugs/archives
            // Note: This requires the class file to be loaded during activation/deactivation hooks.
            // register_activation_hook(__FILE__, [self::class, 'flushRewriteRules']); // Adjust __FILE__ if needed
            // register_deactivation_hook(__FILE__, [self::class, 'flushRewriteRules']); // Adjust __FILE__ if needed
        }

        /**
         * Registers the defined Custom Post Types with WordPress.
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
                // Check if already registered *in this specific request* (less crucial now, but harmless)
                // WordPress's own register_post_type handles re-registration attempts gracefully.
                if (post_type_exists($post_type)) {
                    // Although WP handles re-registration, maybe log if you *expect* define() to only run once
                    // GloryLogger::info("Post type '{$post_type}' already exists (possibly registered by other code or earlier call). Proceeding with registration attempt (WP handles updates).");
                }


                GloryLogger::info("PostTypeManager processPostTypes: Registering post type '{$post_type}'.", ['args' => $definition['args']]);

                // Register the post type
                $result = register_post_type($post_type, $definition['args']);

                // Check for errors
                if (is_wp_error($result)) {
                    GloryLogger::error("PostTypeManager: FAILED to register post type '{$post_type}'.", [
                        'error_code' => $result->get_error_code(),
                        'error_message' => $result->get_error_message(),
                        'args_used' => $definition['args'] // Log the args that caused the error
                    ]);
                } else {
                    // Optional: Log success for this specific registration attempt
                    // GloryLogger::info("PostTypeManager: Successfully registered/updated post type '{$post_type}'.");
                }
            }
            GloryLogger::info("PostTypeManager processPostTypes: Finished processing definitions.");

            // Note: Flushing rewrite rules here on *every* init is generally discouraged
            // as it's computationally expensive. It's better done on activation/deactivation
            // or when CPT definitions actually change significantly (harder to detect automatically).
            // flush_rewrite_rules(); // <-- Avoid this here unless absolutely necessary and you understand the performance impact.
        }

        /**
         * Flush rewrite rules. Call this on theme/plugin activation and deactivation.
         * Needs to be called from a context where the CPTs have just been registered (or unregistered).
         * @internal
         */
        public static function flushRewriteRules(): void
        {
            // Ensure CPTs are registered before flushing
            self::processPostTypes(); // Call registration logic again
            flush_rewrite_rules();
            GloryLogger::info("PostTypeManager: Rewrite rules flushed.");
        }
    } # End class PostTypeManager

} # End if class_exists