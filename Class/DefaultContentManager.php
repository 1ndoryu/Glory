<?php
# Class/DefaultContentManager.php

namespace Glory\Class;

use Glory\Class\GloryLogger; 
use WP_Query;
use WP_Post;
use WP_Error;

// Ensure this file is included only once
if (!class_exists('Glory\Class\DefaultContentManager')) {

    /**
     * Manages the creation and controlled updating of default posts for Custom Post Types.
     *
     * This class allows developers to define default content (posts) programmatically.
     * It handles creating these posts if they don't exist and provides mechanisms
     * to update them based on code changes, while respecting manual edits made
     * via the WordPress admin interface, unless forced.
     *
     * Key Features:
     * - Defines default posts in code.
     * - Creates posts automatically if they don't exist based on a unique identifier.
     * - Supports update modes:
     *   - 'none': Only creates posts, never updates existing ones.
     *   - 'smart' (Default): Creates posts; updates only if the code definition changes AND the post hasn't been manually edited in WP Admin.
     *   - 'force': Creates posts; always updates existing posts matching the definition, overwriting manual edits.
     * - Supports conditional deletion: Removes posts if their definition is removed from code, unless manually edited.
     * - Uses post meta to track default post identifiers and modification status.
     * - Aims for efficiency by minimizing database queries.
     */
    class DefaultContentManager
    {
        /**
         * Stores the definitions for default content.
         * Structure: ['post_type' => ['posts' => [...], 'update_mode' => '...', 'allow_deletion' => bool]]
         * @var array<string, array{posts: array<int, array<string, mixed>>, update_mode: string, allow_deletion: bool}>
         */
        private static $definitions = [];

        /**
         * Meta key used to store the unique identifier (default_slug) for a default post definition.
         * This links a post in the DB to its definition in the code.
         * @var string
         */
        private const META_KEY_DEFAULT_SLUG = '_glory_default_content_slug';

        /**
         * Meta key used to track if a default post has been manually modified via WP Admin.
         * Value: '1' if modified, absent or other value if not.
         * @var string
         */
        private const META_KEY_MANUALLY_EDITED = '_glory_default_content_edited';

        /**
         * Define default posts to be created or updated for a specific post type.
         *
         * @param string $post_type The slug of the custom post type.
         * @param array  $default_posts An array of default post definitions. Each element MUST be an associative array
         *                              containing a unique 'default_slug' key (string, lowercase, alphanumeric, etc)
         *                              and other keys compatible with wp_insert_post/wp_update_post
         * 
         *                              ('post_title', 'post_content', 'post_status', 'post_excerpt', 'meta_input').
         *                              Example:
         *                              [
         *                                  [
         *                                      'default_slug' => 'portfolio-alpha', // *** REQUIRED & UNIQUE 
         *                                      'post_title'   => 'My First Default Post',
         *                                      'post_content' => 'This is the default content.',
         *                                      'post_status'  => 'publish',
         *                                      'meta_input'   => [ '_my_custom_field' => 'Default Value 1' ]
         *                                  ],
         *                                  // ... more posts
         *                              ]
         * @param string $update_mode How to handle existing posts that match a 'default_slug'.
         *                            - 'smart' (Default): Create if not exists. If exists, update only if the definition
         *                                       in the code has changed AND the post has NOT been marked as manually edited
         *                                       (via `_glory_default_content_edited` meta). Meta fields are updated additively
         *                                       (existing meta not in the definition is preserved).
         *                            - 'force': Create if not exists. If exists, *always* update its content (title, content, excerpt, status)
         *                                       and *overwrite* its meta fields based on the current definition, regardless
         *                                       of manual edits. *** WARNING: This WILL overwrite any manual edits! ***
         *                            - 'none': Only create posts if they don't exist based on 'default_slug'. Never update existing ones. (Safest)
         * @param bool $allow_deletion If true, posts managed by this definition whose `default_slug` is removed
         *                             from the `$default_posts` array will be deleted, *unless* they have been marked
         *                             as manually edited. If false (Default), posts are never deleted by this manager,
         *                             even if their definition is removed. Deletion respects the 'manually edited' flag.
         */
        public static function define(string $post_type, array $default_posts, string $update_mode = 'smart', bool $allow_deletion = false): void
        {
            // Basic validation
            if (empty($post_type)) {
                GloryLogger::error("DefaultContentManager: Post type slug cannot be empty. Definition skipped.");
                return;
            }
            // Note: Empty $default_posts is allowed, might be used for cleanup if $allow_deletion is true.
            // if (empty($default_posts)) {
            //     GloryLogger::info("DefaultContentManager: No default posts provided for post type '{$post_type}'. Definition may only perform cleanup.");
            // }
            if (!in_array($update_mode, ['none', 'force', 'smart'], true)) {
                GloryLogger::info("DefaultContentManager: Invalid update_mode '{$update_mode}' for post type '{$post_type}'. Using 'smart'.");
                $update_mode = 'smart';
            }

            $processed_slugs = [];
            // Validate structure and uniqueness of 'default_slug'
            foreach ($default_posts as $index => $post_data) {
                if (!is_array($post_data)) {
                    GloryLogger::error("DefaultContentManager: Post definition at index {$index} for post type '{$post_type}' is not an array. Skipping definition.");
                    return;
                }
                if (!isset($post_data['default_slug']) || !is_string($post_data['default_slug']) || empty(trim($post_data['default_slug']))) {
                    GloryLogger::error("DefaultContentManager: Invalid or missing 'default_slug' (non-empty string) at index {$index} for post type '{$post_type}'. Skipping definition.");
                    return;
                }
                $current_slug = trim($post_data['default_slug']);
                // Basic slug format check (recommended)
                if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $current_slug)) {
                    GloryLogger::info("DefaultContentManager: 'default_slug' ('{$current_slug}') at index {$index} for '{$post_type}' has non-standard format (recommend lowercase alphanumeric and hyphens). Proceeding, but ensure uniqueness.");
                }

                if (isset($processed_slugs[$current_slug])) {
                    GloryLogger::error("DefaultContentManager: Duplicate 'default_slug' ('{$current_slug}') found within definition for post type '{$post_type}'. Skipping definition.");
                    return;
                }
                $processed_slugs[$current_slug] = true;

                if (!isset($post_data['post_title']) || !is_string($post_data['post_title']) || empty(trim($post_data['post_title']))) {
                    GloryLogger::error("DefaultContentManager: Invalid or missing 'post_title' (non-empty string) for default_slug '{$current_slug}', post type '{$post_type}'. Skipping definition.");
                    return;
                }
                // Ensure meta_input, if provided, is an array
                if (isset($post_data['meta_input']) && !is_array($post_data['meta_input'])) {
                    GloryLogger::error("DefaultContentManager: Invalid 'meta_input' (must be an array if provided) for default_slug '{$current_slug}', post type '{$post_type}'. Skipping definition.");
                    return;
                }
                // Ensure core fields expected by wp_insert/update_post are scalars if present
                $scalar_fields = ['post_content', 'post_status', 'post_excerpt'];
                foreach ($scalar_fields as $field) {
                    if (isset($post_data[$field]) && !is_scalar($post_data[$field])) {
                        GloryLogger::error("DefaultContentManager: Invalid type for '{$field}' (must be scalar) for default_slug '{$current_slug}', post type '{$post_type}'. Skipping definition.");
                        return;
                    }
                }
            }

            self::$definitions[$post_type] = [
                'posts'       => $default_posts,
                'update_mode' => $update_mode,
                'allow_deletion' => $allow_deletion,
            ];

            GloryLogger::info("DefaultContentManager: Defined default content for post type '{$post_type}'. Update mode: '{$update_mode}'. Deletion: " . ($allow_deletion ? 'Enabled' : 'Disabled') . ". (" . count($default_posts) . " posts defined)");
        }

        /**
         * Registers the necessary WordPress hooks.
         */
        public static function register(): void
        {
            // Run after CPTs are likely registered, but before admin loads fully.
            add_action('init', [self::class, 'processDefinitions'], 20);

            // Hook into saving posts from the admin to mark them as manually edited.
            // Priority 99 to run after most other save actions.
            add_action('save_post', [self::class, 'detectManualEdit'], 99, 3);
        }

        /**
         * Processes all defined default content, creating, updating, or deleting posts as needed.
         * @internal Callback for the 'init' action. Do not call directly.
         */
        public static function processDefinitions(): void
        {
            if (empty(self::$definitions)) {
                return; // No definitions to process
            }

            // Prevent recursion if triggered by our own updates
            static $processing = false;
            if ($processing) {
                return;
            }
            $processing = true;

            GloryLogger::info("DefaultContentManager: processDefinitions() running...");

            // Get all currently defined default slugs across all definitions for easier lookup
            $defined_slugs_map = [];
            foreach (self::$definitions as $pt => $def) {
                $defined_slugs_map[$pt] = array_column($def['posts'], 'default_slug');
            }


            foreach (self::$definitions as $post_type => $definition_config) {
                $post_definitions = $definition_config['posts'];
                $update_mode = $definition_config['update_mode'];
                $allow_deletion = $definition_config['allow_deletion'];
                $defined_slugs_current_pt = $defined_slugs_map[$post_type] ?? [];


                // Verify post type exists
                if (!post_type_exists($post_type)) {
                    GloryLogger::error("DefaultContentManager: Cannot process definition because post type '{$post_type}' does not exist or is not registered yet.");
                    continue; // Skip this whole definition
                }

                GloryLogger::info("DefaultContentManager: Processing '{$post_type}' (Update mode: {$update_mode}, Deletion: " . ($allow_deletion ? 'Enabled' : 'Disabled') . ")...");

                // --- 1. Process defined posts (Create/Update) ---
                $processed_db_post_ids = []; // Keep track of posts we handled based on definitions
                foreach ($post_definitions as $post_data) {
                    $default_slug = trim($post_data['default_slug']);

                    $existing_post = self::findExistingDefaultPost($post_type, $default_slug);

                    if ($existing_post) {
                        // --- Post Exists ---
                        $post_id = $existing_post->ID;
                        $processed_db_post_ids[] = $post_id; // Mark as processed
                        $is_manually_edited = get_post_meta($post_id, self::META_KEY_MANUALLY_EDITED, true) === '1';

                        GloryLogger::info("DefaultContentManager: Found existing post (ID: {$post_id}, Edited: " . ($is_manually_edited ? 'Yes' : 'No') . ") for '{$post_type}' with default_slug '{$default_slug}'.");

                        if ($update_mode === 'force') {
                            self::updatePost($post_id, $post_type, $post_data, true); // Force update
                        } elseif ($update_mode === 'smart') {
                            if ($is_manually_edited) {
                                GloryLogger::info("DefaultContentManager: Update mode is 'smart', skipping update for manually edited post ID {$post_id}.");
                            } else {
                                // Check if definition differs from DB post before updating
                                if (self::definitionDiffersFromPost($existing_post, $post_data)) {
                                    self::updatePost($post_id, $post_type, $post_data, false); // Smart update (additive meta)
                                } else {
                                    GloryLogger::info("DefaultContentManager: Update mode is 'smart', definition matches post ID {$post_id}. No update needed.");
                                }
                            }
                        } else { // 'none'
                            GloryLogger::info("DefaultContentManager: Update mode is 'none', skipping update for existing post ID {$post_id}.");
                        }
                    } else {
                        // --- Post Does Not Exist ---
                        GloryLogger::info("DefaultContentManager: No existing post found for '{$post_type}' with default_slug '{$default_slug}'. Creating new post.");
                        $new_post_id = self::createNewPost($post_type, $post_data);
                        if ($new_post_id) {
                            $processed_db_post_ids[] = $new_post_id; // Mark as processed
                        }
                    }
                } // End foreach $post_definitions

                // --- 2. Process Deletions (if enabled) ---
                if ($allow_deletion) {
                    GloryLogger::info("DefaultContentManager: Checking for posts to delete for '{$post_type}'...");
                    // Find all posts of this type that have *our* meta key, but are *not* in the current definition
                    $args = [
                        'post_type'      => $post_type,
                        'post_status'    => 'any',
                        'posts_per_page' => -1, // Get all
                        'meta_query'     => [
                            'relation' => 'AND',
                            [
                                'key'     => self::META_KEY_DEFAULT_SLUG,
                                'compare' => 'EXISTS', // Must have our identifier meta key
                            ],
                            // Optionally exclude posts whose default_slug IS in the current definition
                            // This check is technically redundant if we compare IDs later, but might optimize the query
                            /*
                            [
                                'key'     => self::META_KEY_DEFAULT_SLUG,
                                'value'   => $defined_slugs_current_pt,
                                'compare' => 'NOT IN',
                            ],
                             */
                        ],
                        'fields'         => 'ids', // We only need IDs and meta
                        'no_found_rows'  => true,
                        'update_post_meta_cache' => true, // Need meta cache for edit check
                        'update_post_term_cache' => false,
                    ];
                    $potential_delete_query = new WP_Query($args);
                    $potential_delete_ids = $potential_delete_query->posts;

                    if (!empty($potential_delete_ids)) {
                        foreach ($potential_delete_ids as $post_id_to_check) {
                            // Double check: Ensure this post ID wasn't just created/updated by the loop above
                            if (in_array($post_id_to_check, $processed_db_post_ids)) {
                                continue;
                            }

                            // Check if the slug associated with this post is actually defined anymore
                            $post_default_slug = get_post_meta($post_id_to_check, self::META_KEY_DEFAULT_SLUG, true);
                            if (in_array($post_default_slug, $defined_slugs_current_pt, true)) {
                                // This post's slug IS defined, it shouldn't be deleted.
                                // This might happen if findExistingDefaultPost missed it initially (e.g., race condition, cache)
                                continue;
                            }


                            // Check if it was manually edited
                            $is_manually_edited = get_post_meta($post_id_to_check, self::META_KEY_MANUALLY_EDITED, true) === '1';

                            if ($is_manually_edited) {
                                GloryLogger::info("DefaultContentManager: Would delete post ID {$post_id_to_check} (slug: '{$post_default_slug}') as its definition was removed, but skipping because it was manually edited.");
                            } else {
                                GloryLogger::info("DefaultContentManager: Deleting post ID {$post_id_to_check} (slug: '{$post_default_slug}') because its definition was removed and it was not manually edited.");
                                // Use true to bypass trash, false to move to trash
                                $deleted = wp_delete_post($post_id_to_check, true);
                                if (!$deleted) {
                                    GloryLogger::error("DefaultContentManager: FAILED to delete post ID {$post_id_to_check}.");
                                }
                            }
                        }
                    } else {
                        GloryLogger::info("DefaultContentManager: No posts found requiring deletion check for '{$post_type}'.");
                    }
                } // End if $allow_deletion

            } // End foreach self::$definitions

            $processing = false;
            GloryLogger::info("DefaultContentManager: processDefinitions() finished.");
        }

        /**
         * Finds an existing post based on post type and the default content slug meta key.
         *
         * @param string $post_type
         * @param string $default_slug
         * @return WP_Post|null The post object if found, null otherwise.
         */
        private static function findExistingDefaultPost(string $post_type, string $default_slug): ?WP_Post
        {
            $query_args = [
                'post_type'      => $post_type,
                'post_status'    => 'any', // Find regardless of status
                'posts_per_page' => 1,     // We only need one
                'meta_query'     => [
                    [
                        'key'     => self::META_KEY_DEFAULT_SLUG,
                        'value'   => $default_slug,
                        'compare' => '=',
                    ],
                ],
                'fields'         => 'ids', // More efficient to just get IDs first
                'no_found_rows'  => true, // Performance optimization
                'update_post_meta_cache' => false, // Performance
                'update_post_term_cache' => false, // Performance
            ];

            $query = new WP_Query($query_args);
            $post_ids = $query->posts;

            if (!empty($post_ids)) {
                // Return the full post object for the first found ID
                $post = get_post($post_ids[0]);
                return ($post instanceof WP_Post) ? $post : null;
            }

            return null;
        }

        /**
         * Checks if the current code definition significantly differs from the post in the database.
         * Used in 'smart' mode to avoid unnecessary updates.
         *
         * @param WP_Post $post The existing post object from the database.
         * @param array $definition The current code definition for this post.
         * @return bool True if differences are found, false otherwise.
         */
        private static function definitionDiffersFromPost(WP_Post $post, array $definition): bool
        {
            // Compare core fields
            if ($post->post_title !== ($definition['post_title'] ?? '')) return true;
            // Note: Comparing post_content might be complex due to filters (like wpautop).
            // A simple string comparison might yield false positives/negatives.
            // Let's compare raw content for simplicity, but acknowledge this limitation.
            if ($post->post_content !== ($definition['post_content'] ?? '')) return true;
            if ($post->post_status !== ($definition['post_status'] ?? 'publish')) return true;
            if ($post->post_excerpt !== ($definition['post_excerpt'] ?? '')) return true;

            // Compare meta fields defined in the code
            $defined_meta = $definition['meta_input'] ?? [];
            foreach ($defined_meta as $key => $value) {
                $db_value = get_post_meta($post->ID, $key, true);
                // Note: Type comparison can be tricky (e.g., '1' vs 1).
                // Use loose comparison (==) for flexibility, or strict (===) if types must match exactly.
                if ($db_value != $value) return true; // Using loose comparison
            }

            // If we reached here, no significant differences were found based on the definition keys
            return false;
        }

        /**
         * Creates a new default post.
         *
         * @param string $post_type
         * @param array $post_data The definition data for the post, including 'default_slug'.
         * @return int|false The new post ID on success, false on failure.
         */
        private static function createNewPost(string $post_type, array $post_data): int|false
        {
            $default_slug = trim($post_data['default_slug']);
            $insert_data = [
                'post_type'    => $post_type,
                'post_title'   => $post_data['post_title'], // Already validated as non-empty string
                'post_content' => $post_data['post_content'] ?? '',
                'post_status'  => $post_data['post_status'] ?? 'publish',
                'post_excerpt' => $post_data['post_excerpt'] ?? '',
                // Do NOT set post_name here, let WP generate it or risk conflicts.
                // We identify purely by meta.
            ];

            // Prepare meta input, ensuring our identifier is included
            $meta_input = $post_data['meta_input'] ?? [];
            $meta_input[self::META_KEY_DEFAULT_SLUG] = $default_slug; // Add our identifier
            // Do NOT add the 'edited' flag on initial creation
            unset($meta_input[self::META_KEY_MANUALLY_EDITED]);

            $insert_data['meta_input'] = $meta_input;

            // Temporarily remove our save_post hook to prevent marking creation as an edit
            remove_action('save_post', [self::class, 'detectManualEdit'], 99);

            $post_id = wp_insert_post($insert_data, true); // true for WP_Error on failure

            // Re-add the hook
            add_action('save_post', [self::class, 'detectManualEdit'], 99, 3);

            if (is_wp_error($post_id)) {
                GloryLogger::error("DefaultContentManager: FAILED to insert default post for '{$post_type}' (slug: {$default_slug}).", [
                    'error_code'    => $post_id->get_error_code(),
                    'error_message' => $post_id->get_error_message(),
                    'data'          => $insert_data // Log data for debugging
                ]);
                return false;
            } else {
                GloryLogger::info("DefaultContentManager: Successfully inserted default post for '{$post_type}' (ID: {$post_id}, Slug: '{$default_slug}')");
                return $post_id;
            }
        }

        /**
         * Updates an existing default post based on the specified mode.
         *
         * @param int $post_id The ID of the post to update.
         * @param string $post_type The expected post type.
         * @param array $post_data The current definition data.
         * @param bool $is_force_update True if mode is 'force', false if 'smart' (additive meta).
         */
        private static function updatePost(int $post_id, string $post_type, array $post_data, bool $is_force_update): void
        {
            $default_slug = trim($post_data['default_slug']);
            $mode_label = $is_force_update ? 'force' : 'smart';
            GloryLogger::info("DefaultContentManager: Attempting '{$mode_label}' update for post ID {$post_id} ('{$post_type}', slug: {$default_slug})...");

            // Prepare data for update (core fields)
            $update_data = [
                'ID'           => $post_id, // CRUCIAL for wp_update_post
                'post_title'   => $post_data['post_title'],
                'post_content' => $post_data['post_content'] ?? '',
                'post_status'  => $post_data['post_status'] ?? 'publish',
                'post_excerpt' => $post_data['post_excerpt'] ?? '',
                // Avoid changing post_type or post_name (slug) unless explicitly defined
            ];

            // Temporarily remove our save_post hook to prevent marking this update as a manual edit
            remove_action('save_post', [self::class, 'detectManualEdit'], 99);

            // Update core post data
            $updated_post_id_or_error = wp_update_post($update_data, true); // true for WP_Error

            // Re-add the hook
            add_action('save_post', [self::class, 'detectManualEdit'], 99, 3);


            if (is_wp_error($updated_post_id_or_error)) {
                GloryLogger::error("DefaultContentManager: FAILED to update core data for post ID {$post_id} ('{$post_type}', slug: {$default_slug}). Mode: {$mode_label}.", [
                    'error_code'    => $updated_post_id_or_error->get_error_code(),
                    'error_message' => $updated_post_id_or_error->get_error_message(),
                    'data' => $update_data // Log data for debugging
                ]);
                // Do not proceed with meta updates if core update failed
                return;
            }

            // --- Handle Meta Updates ---
            $new_meta = $post_data['meta_input'] ?? [];
            // Ensure our identifier is conceptually present, but it doesn't need updating
            // $new_meta[self::META_KEY_DEFAULT_SLUG] = $default_slug; // Should already exist

            // 1. Update/Add meta fields from the new definition
            foreach ($new_meta as $meta_key => $meta_value) {
                // Skip our internal keys if they somehow ended up in meta_input
                if ($meta_key === self::META_KEY_DEFAULT_SLUG || $meta_key === self::META_KEY_MANUALLY_EDITED) {
                    continue;
                }
                update_post_meta($post_id, $meta_key, $meta_value);
                // GloryLogger::debug("Updated meta '{$meta_key}' for post ID {$post_id}"); // Optional verbose logging
            }

            // 2. Handle meta deletion (ONLY for 'force' mode)
            if ($is_force_update) {
                // Get all current meta keys for the post
                $existing_meta = get_post_meta($post_id);
                $existing_meta_keys = is_array($existing_meta) ? array_keys($existing_meta) : [];

                $new_meta_keys_from_def = array_keys($new_meta);

                foreach ($existing_meta_keys as $key_to_check) {
                    // Skip our internal keys and WP internal keys (starting with _)
                    if (
                        $key_to_check === self::META_KEY_DEFAULT_SLUG ||
                        $key_to_check === self::META_KEY_MANUALLY_EDITED ||
                        strpos($key_to_check, '_') === 0
                    ) {
                        continue;
                    }

                    // If an existing key is NOT in the new definition's meta_input, delete it
                    if (!in_array($key_to_check, $new_meta_keys_from_def, true)) {
                        delete_post_meta($post_id, $key_to_check);
                        GloryLogger::info("DefaultContentManager: Deleted meta key '{$key_to_check}' from post ID {$post_id} as it's no longer in the 'force' definition.");
                    }
                }
                // In force mode, ensure the 'manually edited' flag is REMOVED, as we are overwriting changes.
                delete_post_meta($post_id, self::META_KEY_MANUALLY_EDITED);
                GloryLogger::info("DefaultContentManager: Removed manual edit flag for post ID {$post_id} due to 'force' update.");
            } else {
                // In 'smart' mode, we specifically do NOT delete meta keys that aren't in the definition.
                // We also don't touch the 'manually edited' flag here (it prevented the update earlier if set).
            }


            GloryLogger::info("DefaultContentManager: Successfully '{$mode_label}' updated post ID {$post_id} ('{$post_type}', slug: {$default_slug}).");
        }


        /**
         * Detects if a post save originated from the WP Admin interface (likely a manual edit).
         * If it's a default content post being saved via admin, mark it as manually edited.
         *
         * @internal Callback for the 'save_post' action. Do not call directly.
         *
         * @param int     $post_id Post ID.
         * @param WP_Post $post    Post object.
         * @param bool    $update  Whether this is an existing post being updated or not.
         */
        public static function detectManualEdit(int $post_id, WP_Post $post, bool $update): void
        {
            // Ignore auto-saves, revisions, and bulk edits
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
            if (wp_is_post_revision($post_id)) return;
            if (isset($_REQUEST['bulk_edit'])) return; // Basic check for bulk edit action

            // Check if it's running from WP-CLI (optional, might treat CLI updates differently)
            // if (defined('WP_CLI') && WP_CLI) return;

            // Check if the save action originates from the WordPress admin interface.
            // This isn't foolproof but covers standard post edit screens.
            if (!is_admin() || !current_user_can('edit_post', $post_id)) {
                // Not an admin action or user lacks permission, likely programmatic - do not mark as edited.
                return;
            }

            // Check if the current request is for the standard post edit screen
            global $pagenow;
            if (!in_array($pagenow, ['post.php', 'post-new.php']) && (!defined('REST_REQUEST') || !REST_REQUEST)) {
                // Probably not a direct edit action (e.g., could be quick edit, but REST API might be used too).
                // REST API check added for Gutenberg/Block Editor saves.
                // Might need refinement depending on plugins/workflows.
                // Let's proceed cautiously and assume it *could* be manual if is_admin() is true.
            }


            // Check if this post is one of our default content posts
            $default_slug = get_post_meta($post_id, self::META_KEY_DEFAULT_SLUG, true);

            if (!empty($default_slug)) {
                // This IS a default content post. Check if it's already marked.
                $is_already_marked = get_post_meta($post_id, self::META_KEY_MANUALLY_EDITED, true) === '1';

                // Check if the content actually changed significantly to avoid marking minor saves.
                // This requires comparing the submitted data with the data before save, which is tricky.
                // For simplicity, we'll mark it on any admin save *if not already marked*.
                // A more robust check could involve comparing `$_POST` data with `$post` object data before save,
                // or checking if the `post_modified` timestamp actually changed significantly.

                if (!$is_already_marked) {
                    // Check if we are in the context of a wp_insert_post or wp_update_post *triggered by this class*.
                    // We use the remove/add action calls around wp_insert/update_post as a proxy.
                    // If the action is currently removed, it means our class is performing the update.
                    if (!has_action('save_post', [self::class, 'detectManualEdit'])) {
                        // GloryLogger::debug("Skipping manual edit flag for post ID {$post_id} - likely internal update.");
                        return; // Do not mark if our own class is saving.
                    }


                    GloryLogger::info("DefaultContentManager: Detected manual edit via WP Admin for default content post ID {$post_id} (slug: '{$default_slug}'). Setting flag.");
                    update_post_meta($post_id, self::META_KEY_MANUALLY_EDITED, '1');
                }
            }
        }
    } // End class DefaultContentManager

} // End if class_exists

/*
// ================== HOW TO USE ==================

// --- 1. Include the Class ---
// Make sure this file is loaded by your theme or plugin, e.g., via require_once or Composer autoload.
// require_once __DIR__ . '/Class/DefaultContentManager.php'; // If not using autoloading
// require_once __DIR__ . '/Class/GloryLogger.php';      // Assuming GloryLogger is also needed

// --- 2. Define Your Default Content (e.g., in functions.php or a dedicated setup file) ---

use Glory\Class\DefaultContentManager;
use Glory\Class\GloryLogger; // Make sure logger is configured if needed

// Example: Define default content for 'portfolio' post type
// Update Mode: 'smart' (default) - create, update if definition changes AND not manually edited.
// Deletion: Disabled (default)
DefaultContentManager::define(
    'portfolio', // The slug of the CPT
    [
        [
            'default_slug' => 'portfolio-alpha', // UNIQUE identifier for this entry
            'post_title'   => 'Sample Project Alpha',
            'post_content' => 'UPDATED AGAIN: This is a detailed description of Sample Project Alpha. It showcases web development skills.', // Change this text
            'post_status'  => 'publish',
            'post_excerpt' => 'A brief summary of Project Alpha.',
            'meta_input'   => [
                '_portfolio_client' => 'Client A Revamped', // Change meta value
                '_portfolio_url'    => 'https://example.com/project-alpha',
                '_portfolio_year'   => 2024, // Change meta value
                // '_new_meta_field' was removed from definition
            ]
        ],
        [
            'default_slug' => 'portfolio-beta', // UNIQUE identifier
            'post_title'   => 'Sample Project Beta',
            'post_content' => 'Details about Project Beta, focusing on design aspects.',
            'post_status'  => 'publish',
            'meta_input'   => [
                '_portfolio_client' => 'Client B',
                '_portfolio_year'   => 2024,
                '_added_meta'       => 'This meta will be added if not present.',
            ]
        ],
        // This definition was previously present but is now REMOVED from code.
        // Since allow_deletion is false (default), the post 'portfolio-gamma' will NOT be deleted.
        // [
        //     'default_slug' => 'portfolio-gamma',
        //     'post_title'   => 'Sample Project Gamma (OLD)',
        //     // ... rest of definition ...
        // ],
    ],
    'smart' // Explicitly setting 'smart' (which is the default)
    // allow_deletion defaults to false
);

// Example: Define default content for 'event' post type
// Update Mode: 'force' - create if missing, ALWAYS overwrite existing posts matching the slug.
// Deletion: Enabled - posts removed from this definition WILL be deleted if not manually edited.
// *** USE 'force' AND 'allow_deletion' WITH CAUTION! ***
DefaultContentManager::define(
    'event', // The slug of the CPT
    [
        [
            'default_slug'    => 'annual-gala-2024', // UNIQUE identifier
            'post_title'      => 'Annual Gala Dinner - 2025 SUPER Edition', // Title will be forced
            'post_content'    => 'Join us for our annual fundraising gala dinner. Dress code: Casual this year.', // Content will be forced
            'post_status'     => 'publish',
            'meta_input'      => [
                '_event_date'     => '2025-01-15', // Meta will be overwritten
                '_event_location' => 'Community Hall', // Meta will be overwritten
                // '_event_theme' was present before, now removed. Because mode is 'force', this meta key will be DELETED from the post.
                '_event_new_meta' => 'Forced Meta Value', // This will be added/updated.
            ]
        ],
        // If you previously had another event defined here (e.g., 'spring-fair') and REMOVED its definition:
        // Because allow_deletion is TRUE for 'event', the 'spring-fair' post would be DELETED
        // (unless you had manually edited it in WP Admin, in which case it would be skipped).
    ],
    'force', // <<< Specifying force update mode
    true     // <<< Specifying allow_deletion = true
);

// Example: Define default content for 'service' post type
// Update Mode: 'none' - only create, never update. Safest option.
// Deletion: Disabled (default)
DefaultContentManager::define(
    'service', // The slug of the CPT
    [
        [
            'default_slug' => 'web-design',
            'post_title'   => 'Web Design Services',
            'post_content' => 'Initial content for web design.',
            'post_status'  => 'publish',
            'meta_input'   => ['_service_price_range' => '$$$']
        ],
        [
            'default_slug' => 'seo-consulting',
            'post_title'   => 'SEO Consulting',
            'post_content' => 'Initial content for SEO.',
            'post_status'  => 'publish',
        ]
    ],
    'none' // <<< Specifying none update mode
    // allow_deletion defaults to false
);


// --- 3. Register the Processor ---
// This should be called once, typically after all definitions.
// It hooks the processing function into WordPress 'init'.
DefaultContentManager::register();

// ================== END HOW TO USE ==================
*/