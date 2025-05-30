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
     * - Supports custom post dates (`post_date`, `post_date_gmt`).
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
         *                              ('post_title', 'post_content', 'post_status', 'post_excerpt', 
         *                               'post_date', 'post_date_gmt', 'meta_input').
         *                              Example:
         *                              [
         *                                  [
         *                                      'default_slug' => 'portfolio-alpha', // *** REQUIRED & UNIQUE 
         *                                      'post_title'   => 'My First Default Post',
         *                                      'post_content' => 'This is the default content.',
         *                                      'post_status'  => 'publish',
         *                                      'post_date'    => '2024-01-01 12:00:00', // Optional
         *                                      'post_date_gmt'=> '2024-01-01 17:00:00', // Optional, recommended if post_date is set
         *                                      'meta_input'   => [ '_my_custom_field' => 'Default Value 1' ]
         *                                  ],
         *                                  // ... more posts
         *                              ]
         * @param string $update_mode How to handle existing posts that match a 'default_slug'.
         *                            - 'smart' (Default): Create if not exists. If exists, update only if the definition
         *                                       in the code has changed AND the post has NOT been marked as manually edited
         *                                       (via `_glory_default_content_edited` meta). Meta fields are updated additively
         *                                       (existing meta not in the definition is preserved).
         *                            - 'force': Create if not exists. If exists, *always* update its content (title, content, excerpt, status, date)
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
                
                // Validate post_date format if present
                if (isset($post_data['post_date'])) {
                    if (!is_string($post_data['post_date']) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $post_data['post_date'])) {
                        GloryLogger::error("DefaultContentManager: Invalid 'post_date' format (must be YYYY-MM-DD HH:MM:SS) for default_slug '{$current_slug}', post type '{$post_type}'. Skipping definition.");
                        return;
                    }
                    if (!isset($post_data['post_date_gmt'])) {
                         GloryLogger::info("DefaultContentManager: 'post_date' is set for '{$current_slug}' ('{$post_type}') but 'post_date_gmt' is not. WordPress will attempt to calculate GMT. Provide 'post_date_gmt' for precision or set to '0000-00-00 00:00:00'.");
                    }
                }
                // Validate post_date_gmt format if present
                if (isset($post_data['post_date_gmt'])) {
                    if (!is_string($post_data['post_date_gmt']) || ($post_data['post_date_gmt'] !== '0000-00-00 00:00:00' && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $post_data['post_date_gmt']))) {
                         GloryLogger::error("DefaultContentManager: Invalid 'post_date_gmt' format (must be YYYY-MM-DD HH:MM:SS or 0000-00-00 00:00:00) for default_slug '{$current_slug}', post type '{$post_type}'. Skipping definition.");
                        return;
                    }
                    if (!isset($post_data['post_date']) && $post_data['post_date_gmt'] !== '0000-00-00 00:00:00') {
                        GloryLogger::error("DefaultContentManager: 'post_date_gmt' is set for '{$current_slug}' ('{$post_type}') but 'post_date' is not. 'post_date' is required if 'post_date_gmt' is set (unless GMT is '0000-00-00 00:00:00'). Skipping definition.");
                        return;
                    }
                }

                if (isset($post_data['meta_input']) && !is_array($post_data['meta_input'])) {
                    GloryLogger::error("DefaultContentManager: Invalid 'meta_input' (must be an array if provided) for default_slug '{$current_slug}', post type '{$post_type}'. Skipping definition.");
                    return;
                }
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
            add_action('init', [self::class, 'processDefinitions'], 20);
            add_action('save_post', [self::class, 'detectManualEdit'], 99, 3);
        }

        /**
         * Processes all defined default content, creating, updating, or deleting posts as needed.
         * @internal Callback for the 'init' action. Do not call directly.
         */
        public static function processDefinitions(): void
        {
            if (empty(self::$definitions)) {
                return; 
            }

            static $processing = false;
            if ($processing) {
                return;
            }
            $processing = true;

            GloryLogger::info("DefaultContentManager: processDefinitions() running...");

            $defined_slugs_map = [];
            foreach (self::$definitions as $pt => $def) {
                $defined_slugs_map[$pt] = array_column($def['posts'], 'default_slug');
            }

            foreach (self::$definitions as $post_type => $definition_config) {
                $post_definitions = $definition_config['posts'];
                $update_mode = $definition_config['update_mode'];
                $allow_deletion = $definition_config['allow_deletion'];
                $defined_slugs_current_pt = $defined_slugs_map[$post_type] ?? [];

                if (!post_type_exists($post_type)) {
                    GloryLogger::error("DefaultContentManager: Cannot process definition because post type '{$post_type}' does not exist or is not registered yet.");
                    continue; 
                }

                GloryLogger::info("DefaultContentManager: Processing '{$post_type}' (Update mode: {$update_mode}, Deletion: " . ($allow_deletion ? 'Enabled' : 'Disabled') . ")...");

                $processed_db_post_ids = []; 
                foreach ($post_definitions as $post_data) {
                    $default_slug = trim($post_data['default_slug']);
                    $existing_post = self::findExistingDefaultPost($post_type, $default_slug);

                    if ($existing_post) {
                        $post_id = $existing_post->ID;
                        $processed_db_post_ids[] = $post_id; 
                        $is_manually_edited = get_post_meta($post_id, self::META_KEY_MANUALLY_EDITED, true) === '1';

                        GloryLogger::info("DefaultContentManager: Found existing post (ID: {$post_id}, Edited: " . ($is_manually_edited ? 'Yes' : 'No') . ") for '{$post_type}' with default_slug '{$default_slug}'.");

                        if ($update_mode === 'force') {
                            self::updatePost($post_id, $post_type, $post_data, true);
                        } elseif ($update_mode === 'smart') {
                            if ($is_manually_edited) {
                                GloryLogger::info("DefaultContentManager: Update mode is 'smart', skipping update for manually edited post ID {$post_id}.");
                            } else {
                                if (self::definitionDiffersFromPost($existing_post, $post_data)) {
                                    self::updatePost($post_id, $post_type, $post_data, false); 
                                } else {
                                    GloryLogger::info("DefaultContentManager: Update mode is 'smart', definition matches post ID {$post_id}. No update needed.");
                                }
                            }
                        } else { // 'none'
                            GloryLogger::info("DefaultContentManager: Update mode is 'none', skipping update for existing post ID {$post_id}.");
                        }
                    } else {
                        GloryLogger::info("DefaultContentManager: No existing post found for '{$post_type}' with default_slug '{$default_slug}'. Creating new post.");
                        $new_post_id = self::createNewPost($post_type, $post_data);
                        if ($new_post_id) {
                            $processed_db_post_ids[] = $new_post_id; 
                        }
                    }
                } 

                if ($allow_deletion) {
                    GloryLogger::info("DefaultContentManager: Checking for posts to delete for '{$post_type}'...");
                    $args = [
                        'post_type'      => $post_type,
                        'post_status'    => 'any',
                        'posts_per_page' => -1, 
                        'meta_query'     => [
                            [
                                'key'     => self::META_KEY_DEFAULT_SLUG,
                                'compare' => 'EXISTS', 
                            ],
                        ],
                        'fields'         => 'ids', 
                        'no_found_rows'  => true,
                        'update_post_meta_cache' => true, 
                        'update_post_term_cache' => false,
                    ];
                    $potential_delete_query = new WP_Query($args);
                    $potential_delete_ids = $potential_delete_query->posts;

                    if (!empty($potential_delete_ids)) {
                        foreach ($potential_delete_ids as $post_id_to_check) {
                            if (in_array($post_id_to_check, $processed_db_post_ids)) {
                                continue;
                            }

                            $post_default_slug = get_post_meta($post_id_to_check, self::META_KEY_DEFAULT_SLUG, true);
                            if (in_array($post_default_slug, $defined_slugs_current_pt, true)) {
                                continue;
                            }

                            $is_manually_edited = get_post_meta($post_id_to_check, self::META_KEY_MANUALLY_EDITED, true) === '1';

                            if ($is_manually_edited) {
                                GloryLogger::info("DefaultContentManager: Would delete post ID {$post_id_to_check} (slug: '{$post_default_slug}') as its definition was removed, but skipping because it was manually edited.");
                            } else {
                                GloryLogger::info("DefaultContentManager: Deleting post ID {$post_id_to_check} (slug: '{$post_default_slug}') because its definition was removed and it was not manually edited.");
                                $deleted = wp_delete_post($post_id_to_check, true);
                                if (!$deleted) {
                                    GloryLogger::error("DefaultContentManager: FAILED to delete post ID {$post_id_to_check}.");
                                }
                            }
                        }
                    } else {
                        GloryLogger::info("DefaultContentManager: No posts found requiring deletion check for '{$post_type}'.");
                    }
                } 
            } 

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
                'post_status'    => 'any', 
                'posts_per_page' => 1,     
                'meta_query'     => [
                    [
                        'key'     => self::META_KEY_DEFAULT_SLUG,
                        'value'   => $default_slug,
                        'compare' => '=',
                    ],
                ],
                'fields'         => 'ids', 
                'no_found_rows'  => true, 
                'update_post_meta_cache' => false, 
                'update_post_term_cache' => false, 
            ];

            $query = new WP_Query($query_args);
            $post_ids = $query->posts;

            if (!empty($post_ids)) {
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
            if ($post->post_title !== ($definition['post_title'] ?? '')) return true;
            if ($post->post_content !== ($definition['post_content'] ?? '')) return true;
            if ($post->post_status !== ($definition['post_status'] ?? 'publish')) return true;
            if ($post->post_excerpt !== ($definition['post_excerpt'] ?? '')) return true;
            
            // Compare post_date if defined
            if (isset($definition['post_date'])) {
                if ($post->post_date !== $definition['post_date']) return true;
            }
            // Compare post_date_gmt if defined
            if (isset($definition['post_date_gmt'])) {
                // If post_date_gmt is '0000-00-00 00:00:00' in definition, it means WP should calculate it
                // or it was explicitly set to be invalid/default. We should compare against the actual post_date_gmt
                // unless the definition specifically asks for '0000-00-00 00:00:00'.
                if ($definition['post_date_gmt'] === '0000-00-00 00:00:00') {
                    // If definition explicitly says GMT is '0000...', check if post reflects that.
                    // This is a tricky case. Usually if post_date is set, post_date_gmt won't be '0000...'.
                    // For simplicity, if definition's GMT is '0000...' we assume it means "let WP handle it",
                    // so we don't force a specific check against the post's GMT unless it's also '0000...'.
                    // A more robust check might be needed if this specific GMT value is critical.
                    // However, typically, if post_date is valid, post_date_gmt will also be valid (not '0000...').
                    if ($post->post_date_gmt !== $definition['post_date_gmt'] && isset($definition['post_date'])) {
                        // Only consider it a difference if post_date was also set, implying GMT should follow.
                        // And if the actual GMT is not what the definition (potentially incorrectly) states as '0000...'
                    }

                } elseif ($post->post_date_gmt !== $definition['post_date_gmt']) {
                     return true;
                }
            }


            $defined_meta = $definition['meta_input'] ?? [];
            foreach ($defined_meta as $key => $value) {
                $db_value = get_post_meta($post->ID, $key, true);
                if ($db_value != $value) return true; 
            }

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
                'post_title'   => $post_data['post_title'], 
                'post_content' => $post_data['post_content'] ?? '',
                'post_status'  => $post_data['post_status'] ?? 'publish',
                'post_excerpt' => $post_data['post_excerpt'] ?? '',
            ];

            // Add date fields if they are defined
            if (isset($post_data['post_date'])) {
                $insert_data['post_date'] = $post_data['post_date'];
            }
            if (isset($post_data['post_date_gmt'])) {
                $insert_data['post_date_gmt'] = $post_data['post_date_gmt'];
            } else if (isset($post_data['post_date'])) {
                // If post_date is set but post_date_gmt is not, set gmt to '0000-00-00 00:00:00'
                // to let wp_insert_post calculate it from post_date and timezone.
                $insert_data['post_date_gmt'] = '0000-00-00 00:00:00';
            }


            $meta_input = $post_data['meta_input'] ?? [];
            $meta_input[self::META_KEY_DEFAULT_SLUG] = $default_slug; 
            unset($meta_input[self::META_KEY_MANUALLY_EDITED]);
            $insert_data['meta_input'] = $meta_input;

            remove_action('save_post', [self::class, 'detectManualEdit'], 99);
            $post_id = wp_insert_post($insert_data, true); 
            add_action('save_post', [self::class, 'detectManualEdit'], 99, 3);

            if (is_wp_error($post_id)) {
                GloryLogger::error("DefaultContentManager: FAILED to insert default post for '{$post_type}' (slug: {$default_slug}).", [
                    'error_code'    => $post_id->get_error_code(),
                    'error_message' => $post_id->get_error_message(),
                    'data'          => $insert_data 
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

            $update_data = [
                'ID'           => $post_id, 
                'post_title'   => $post_data['post_title'],
                'post_content' => $post_data['post_content'] ?? '',
                'post_status'  => $post_data['post_status'] ?? 'publish',
                'post_excerpt' => $post_data['post_excerpt'] ?? '',
            ];

            // Add date fields if they are defined
            if (isset($post_data['post_date'])) {
                $update_data['post_date'] = $post_data['post_date'];
            }
            if (isset($post_data['post_date_gmt'])) {
                $update_data['post_date_gmt'] = $post_data['post_date_gmt'];
            } else if (isset($post_data['post_date'])) {
                 // If post_date is set but post_date_gmt is not, set gmt to '0000-00-00 00:00:00'
                // to let wp_update_post calculate it from post_date and timezone.
                $update_data['post_date_gmt'] = '0000-00-00 00:00:00';
            }


            remove_action('save_post', [self::class, 'detectManualEdit'], 99);
            $updated_post_id_or_error = wp_update_post($update_data, true); 
            add_action('save_post', [self::class, 'detectManualEdit'], 99, 3);


            if (is_wp_error($updated_post_id_or_error)) {
                GloryLogger::error("DefaultContentManager: FAILED to update core data for post ID {$post_id} ('{$post_type}', slug: {$default_slug}). Mode: {$mode_label}.", [
                    'error_code'    => $updated_post_id_or_error->get_error_code(),
                    'error_message' => $updated_post_id_or_error->get_error_message(),
                    'data' => $update_data 
                ]);
                return;
            }

            $new_meta = $post_data['meta_input'] ?? [];
            
            foreach ($new_meta as $meta_key => $meta_value) {
                if ($meta_key === self::META_KEY_DEFAULT_SLUG || $meta_key === self::META_KEY_MANUALLY_EDITED) {
                    continue;
                }
                update_post_meta($post_id, $meta_key, $meta_value);
            }

            if ($is_force_update) {
                $existing_meta = get_post_meta($post_id);
                $existing_meta_keys = is_array($existing_meta) ? array_keys($existing_meta) : [];
                $new_meta_keys_from_def = array_keys($new_meta);

                foreach ($existing_meta_keys as $key_to_check) {
                    if (
                        $key_to_check === self::META_KEY_DEFAULT_SLUG ||
                        $key_to_check === self::META_KEY_MANUALLY_EDITED ||
                        strpos($key_to_check, '_') === 0 
                    ) {
                        continue;
                    }
                    if (!in_array($key_to_check, $new_meta_keys_from_def, true)) {
                        delete_post_meta($post_id, $key_to_check);
                        GloryLogger::info("DefaultContentManager: Deleted meta key '{$key_to_check}' from post ID {$post_id} as it's no longer in the 'force' definition.");
                    }
                }
                delete_post_meta($post_id, self::META_KEY_MANUALLY_EDITED);
                GloryLogger::info("DefaultContentManager: Removed manual edit flag for post ID {$post_id} due to 'force' update.");
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
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
            if (wp_is_post_revision($post_id)) return;
            if (isset($_REQUEST['bulk_edit'])) return; 

            if (!is_admin() || !current_user_can('edit_post', $post_id)) {
                return;
            }

            global $pagenow;
            if (!in_array($pagenow, ['post.php', 'post-new.php']) && (!defined('REST_REQUEST') || !REST_REQUEST)) {
                // Not a standard edit screen or REST API request, could be programmatic within admin.
                // If we want to be very strict, we could return here.
                // However, better to proceed and check our internal flag.
            }

            $default_slug = get_post_meta($post_id, self::META_KEY_DEFAULT_SLUG, true);

            if (!empty($default_slug)) {
                $is_already_marked = get_post_meta($post_id, self::META_KEY_MANUALLY_EDITED, true) === '1';

                if (!$is_already_marked) {
                    // Check if our class is performing the update (action hook temporarily removed)
                    if (!has_action('save_post', [self::class, 'detectManualEdit'])) {
                        return; 
                    }
                    GloryLogger::info("DefaultContentManager: Detected manual edit via WP Admin for default content post ID {$post_id} (slug: '{$default_slug}'). Setting flag.");
                    update_post_meta($post_id, self::META_KEY_MANUALLY_EDITED, '1');
                }
            }
        }
    } // End class DefaultContentManager

} // End if class_exists

