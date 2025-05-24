<?php
# App/Glory/Class/PostActionManager.php

namespace Glory\Class;

use WP_Post;
use WP_Error;
use Glory\Class\GloryLogger; // Assuming GloryLogger is available and autoloaded

# Ensure this file is included only once
if (!class_exists('Glory\Class\PostActionManager')) {

    /**
     * Provides static utility methods for common WordPress post actions (CRUD).
     * Simplifies creating, updating, deleting, and fetching posts with integrated logging.
     */
    class PostActionManager
    {
        /**
         * Creates a new post.
         *
         * Wraps wp_insert_post() with logging and basic validation.
         * Requires 'post_title' in the $data array. Sets 'post_status' to 'draft' if not provided.
         *
         * @param string $post_type The post type slug.
         * @param array  $data      An array of post data (compatible with wp_insert_post, e.g., 'post_title', 'post_content', 'post_status', 'meta_input').
         * @param bool   $return_wp_error (Optional) Whether to return a WP_Error object on failure. Default false.
         *
         * @return int|WP_Error The new post ID on success, or 0 or WP_Error on failure.
         * @see wp_insert_post()
         */
        public static function createPost(string $post_type, array $data, bool $return_wp_error = false): int|WP_Error
        {
            GloryLogger::info("PostActionManager::createPost() called for type '{$post_type}'.", ['data' => $data]);

            if (empty($post_type) || !post_type_exists($post_type)) {
                GloryLogger::error("PostActionManager::createPost() - Invalid or non-existent post type: '{$post_type}'.");
                return $return_wp_error ? new WP_Error('invalid_post_type', "Invalid post type '{$post_type}'.") : 0;
            }

            if (!isset($data['post_title']) || empty(trim($data['post_title']))) {
                GloryLogger::error("PostActionManager::createPost() - 'post_title' is required in data array.");
                return $return_wp_error ? new WP_Error('missing_title', "Post title is required.") : 0;
            }

            // Ensure post_type is set in the array
            $data['post_type'] = $post_type;

            // Default status to 'draft' if not specified
            if (!isset($data['post_status'])) {
                $data['post_status'] = 'draft';
                // GloryLogger::info("PostActionManager::createPost() - Setting default post_status to 'draft'.");
            }

            $result = wp_insert_post($data, $return_wp_error);

            if (is_wp_error($result)) {
                GloryLogger::error("PostActionManager::createPost() - FAILED to insert post.", [
                    'error_code' => $result->get_error_code(),
                    'error_message' => $result->get_error_message(),
                    'data_used' => $data
                ]);
            } elseif ($result === 0) {
                GloryLogger::error("PostActionManager::createPost() - FAILED to insert post (returned 0).", ['data_used' => $data]);
            } else {
                GloryLogger::info("PostActionManager::createPost() - Successfully inserted post ID: {$result}.");
            }

            return $result;
        }

        /**
         * Updates an existing post.
         *
         * Wraps wp_update_post() with logging and checks if the post exists.
         * Requires the 'ID' key within the $data array.
         *
         * @param array $data An array of post data to update (MUST include 'ID'). Can include 'meta_input'.
         * @param bool  $return_wp_error (Optional) Whether to return a WP_Error object on failure. Default false.
         *
         * @return int|WP_Error The post ID on success, or 0 or WP_Error on failure.
         * @see wp_update_post()
         */
        public static function updatePost(array $data, bool $return_wp_error = false): int|WP_Error
        {
            if (!isset($data['ID']) || !($post_id = absint($data['ID'])) || $post_id === 0) {
                GloryLogger::error("PostActionManager::updatePost() - Missing or invalid 'ID' in data array.");
                return $return_wp_error ? new WP_Error('missing_id', "Post ID is required for update.") : 0;
            }

            GloryLogger::info("PostActionManager::updatePost() called for Post ID: {$post_id}.", ['data' => $data]);

            // Check if post exists before attempting update
            if (!self::getPostById($post_id)) {
                GloryLogger::error("PostActionManager::updatePost() - Post ID {$post_id} not found. Cannot update.");
                return $return_wp_error ? new WP_Error('post_not_found', "Post ID {$post_id} not found.") : 0;
            }

            $result = wp_update_post($data, $return_wp_error);

            if (is_wp_error($result)) {
                GloryLogger::error("PostActionManager::updatePost() - FAILED to update post ID: {$post_id}.", [
                    'error_code' => $result->get_error_code(),
                    'error_message' => $result->get_error_message(),
                    'data_used' => $data
                ]);
            } elseif ($result === 0) {
                // wp_update_post returns 0 if there was an error OR if the post was not updated (no changes)
                // We need to differentiate. Let's re-fetch the post status to see if it's an error.
                if (!self::getPostById($post_id)) { // If post suddenly disappeared (unlikely)
                    GloryLogger::error("PostActionManager::updatePost() - FAILED to update post ID: {$post_id} (returned 0, post seems gone).", ['data_used' => $data]);
                } else {
                    // Assume no changes were made if the post still exists. This is considered success in terms of no error.
                    GloryLogger::info("PostActionManager::updatePost() - Post ID: {$post_id} updated (or no changes needed - wp_update_post returned 0).", ['data_used' => $data]);
                    $result = $post_id; // Return the ID to indicate no *error*, even if no change.
                }
            } else {
                GloryLogger::info("PostActionManager::updatePost() - Successfully updated post ID: {$result}.");
            }

            return $result; // Returns post ID (even if 0 if no change needed but no error), or WP_Error
        }

        /**
         * Deletes a post (moves to trash by default or permanently).
         *
         * Wraps wp_delete_post() with logging.
         *
         * @param int  $post_id      The ID of the post to delete.
         * @param bool $force_delete (Optional) Whether to bypass trash and force delete. Default false.
         *
         * @return bool True on success, false on failure.
         * @see wp_delete_post()
         */
        public static function deletePost(int $post_id, bool $force_delete = false): bool
        {
            GloryLogger::info("PostActionManager::deletePost() called for Post ID: {$post_id}. Force delete: " . ($force_delete ? 'Yes' : 'No') . ".");

            if (!self::_validatePostId($post_id, !$force_delete)) { // Check if exists (unless forcing delete, where it might be trashed)
                return false; // Error logged in _validatePostId
            }

            $result = wp_delete_post($post_id, $force_delete);

            if ($result instanceof WP_Post || $result === true) { // Success returns the post object or true on force delete after WP 5.5
                GloryLogger::info("PostActionManager::deletePost() - Successfully deleted post ID: {$post_id}.");
                return true;
            } else {
                GloryLogger::error("PostActionManager::deletePost() - FAILED to delete post ID: {$post_id}. wp_delete_post returned: " . print_r($result, true));
                return false;
            }
        }

        /**
         * Moves a post to the trash.
         *
         * Wraps wp_trash_post() with logging.
         *
         * @param int $post_id The ID of the post to trash.
         *
         * @return bool True on success, false on failure.
         * @see wp_trash_post()
         */
        public static function trashPost(int $post_id): bool
        {
            GloryLogger::info("PostActionManager::trashPost() called for Post ID: {$post_id}.");

            if (!self::_validatePostId($post_id, true, 'publish,draft,pending,private,future')) { // Ensure it exists and isn't already trashed
                return false; // Error logged in _validatePostId
            }

            $result = wp_trash_post($post_id);

            if ($result) { // wp_trash_post returns the post data on success
                GloryLogger::info("PostActionManager::trashPost() - Successfully moved post ID: {$post_id} to trash.");
                return true;
            } else {
                GloryLogger::error("PostActionManager::trashPost() - FAILED to move post ID: {$post_id} to trash.");
                return false;
            }
        }

        /**
         * Restores a post from the trash.
         *
         * Wraps wp_untrash_post() with logging.
         *
         * @param int $post_id The ID of the post to restore.
         *
         * @return bool True on success, false on failure.
         * @see wp_untrash_post()
         */
        public static function untrashPost(int $post_id): bool
        {
            GloryLogger::info("PostActionManager::untrashPost() called for Post ID: {$post_id}.");

            if (!self::_validatePostId($post_id, true, 'trash')) { // Ensure it exists and IS trashed
                return false; // Error logged in _validatePostId
            }

            $result = wp_untrash_post($post_id);

            if ($result) { // wp_untrash_post returns the post data on success
                GloryLogger::info("PostActionManager::untrashPost() - Successfully restored post ID: {$post_id} from trash.");
                return true;
            } else {
                GloryLogger::error("PostActionManager::untrashPost() - FAILED to restore post ID: {$post_id} from trash.");
                return false;
            }
        }

        /**
         * Gets a post object by its ID.
         *
         * Wraps get_post() with logging.
         *
         * @param int    $post_id The ID of the post.
         * @param string $output  (Optional) Type of output. OBJECT, ARRAY_A, or ARRAY_N. Default OBJECT.
         *
         * @return WP_Post|array|null The post object/array, or null if not found.
         * @see get_post()
         */
        public static function getPostById(int $post_id, string $output = OBJECT): WP_Post|array|null
        {
            if ($post_id <= 0) {
                GloryLogger::info("PostActionManager::getPostById() - Invalid Post ID: {$post_id}.");
                return null;
            }

            $post = get_post($post_id, $output);

            if ($post) {
                // GloryLogger::info("PostActionManager::getPostById() - Found post ID: {$post_id}.");
            } else {
                GloryLogger::info("PostActionManager::getPostById() - Post ID: {$post_id} not found.");
            }

            return $post;
        }

        /**
         * Gets a post object by its slug and post type.
         *
         * Wraps get_page_by_path() with logging. Note: Despite the name, it works for any hierarchical CPT.
         * For non-hierarchical CPTs, a WP_Query might be needed for robustness.
         *
         * @param string $slug      The post slug.
         * @param string $post_type The post type slug.
         * @param string $output    (Optional) Type of output. OBJECT, ARRAY_A, or ARRAY_N. Default OBJECT.
         *
         * @return WP_Post|array|null The post object/array, or null if not found.
         * @see get_page_by_path()
         */
        public static function getPostBySlug(string $slug, string $post_type, string $output = OBJECT): WP_Post|array|null
        {
            if (empty($slug) || empty($post_type)) {
                GloryLogger::error("PostActionManager::getPostBySlug() - Slug and Post Type are required.", ['slug' => $slug, 'post_type' => $post_type]);
                return null;
            }

            // Using get_page_by_path - works well for 'page' and hierarchical CPTs.
            // For non-hierarchical, it *might* work but isn't guaranteed across WP versions/setups.
            // A WP_Query is the most reliable for non-hierarchical CPTs by slug.
            // Let's stick with get_page_by_path for simplicity, add a note.
            // GloryLogger::info("PostActionManager::getPostBySlug() - Searching for slug '{$slug}' in post type '{$post_type}'. (Note: Relies on get_page_by_path)");

            $post = get_page_by_path($slug, $output, $post_type);

            if ($post) {
                // GloryLogger::info("PostActionManager::getPostBySlug() - Found post with slug '{$slug}' in '{$post_type}'.");
            } else {
                GloryLogger::info("PostActionManager::getPostBySlug() - Post not found with slug '{$slug}' in '{$post_type}'.");
            }

            return $post;

            /* // Alternative using WP_Query (more reliable for non-hierarchical)
             $query_args = [
                 'post_type' => $post_type,
                 'name' => $slug,
                 'post_status' => 'any', // Find regardless of status initially
                 'posts_per_page' => 1,
                 'no_found_rows' => true,
                 'fields' => 'ids' // Get ID first for efficiency
             ];
             $query = new WP_Query($query_args);
             if ($query->have_posts()) {
                 $post_id = $query->posts[0];
                 return self::getPostById($post_id, $output); // Reuse getPostById
             } else {
                 GloryLogger::info("PostActionManager::getPostBySlug() - Post not found with slug '{$slug}' in '{$post_type}' (using WP_Query).");
                 return null;
             }
            */
        }

        /**
         * Checks if a post exists based on ID or slug.
         *
         * @param int|string $identifier The post ID or slug.
         * @param string $post_type (Optional) The post type slug. Required if identifier is a slug. Default 'post'.
         * @param string $field     (Optional) The field the identifier represents ('id' or 'slug'). Default 'id'.
         *
         * @return bool True if the post exists, false otherwise.
         */
        public static function postExists(int|string $identifier, string $post_type = 'post', string $field = 'id'): bool
        {
            if ($field === 'id') {
                if (!is_numeric($identifier) || intval($identifier) <= 0) return false;
                $status = get_post_status(intval($identifier));
                $exists = (bool) $status; // get_post_status returns false if post doesn't exist
                // GloryLogger::info("PostActionManager::postExists() - Check by ID {$identifier}: " . ($exists ? 'Yes' : 'No'));
                return $exists;
            } elseif ($field === 'slug') {
                if (empty($identifier) || !is_string($identifier)) return false;
                $post = self::getPostBySlug($identifier, $post_type); // Use our method which logs
                $exists = !is_null($post);
                // GloryLogger::info("PostActionManager::postExists() - Check by slug '{$identifier}' in '{$post_type}': " . ($exists ? 'Yes' : 'No'));
                return $exists;
            } else {
                GloryLogger::error("PostActionManager::postExists() - Invalid field type specified: '{$field}'. Use 'id' or 'slug'.");
                return false;
            }
        }

        // --- Helper Methods ---

        /**
         * Validates a post ID and checks its status.
         *
         * @internal
         * @param int    $post_id       The post ID to validate.
         * @param bool   $log_not_found Log an error if the post is not found.
         * @param string|null $check_status Check if post has one of these statuses (comma-separated or array). Null bypasses status check.
         * @return bool True if valid and matches status (if checked), false otherwise.
         */
        private static function _validatePostId(int $post_id, bool $log_not_found = true, string|array|null $check_status = null): bool
        {
            if ($post_id <= 0) {
                GloryLogger::error("PostActionManager - Invalid Post ID provided: {$post_id}.");
                return false;
            }

            $status = get_post_status($post_id);

            if (!$status) {
                if ($log_not_found) {
                    GloryLogger::error("PostActionManager - Post with ID {$post_id} does not exist.");
                }
                return false;
            }

            if (!is_null($check_status)) {
                $allowed_statuses = is_array($check_status) ? $check_status : array_map('trim', explode(',', $check_status));
                if (!in_array($status, $allowed_statuses, true)) {
                    GloryLogger::error("PostActionManager - Post ID {$post_id} has invalid status '{$status}'. Expected one of: " . implode(', ', $allowed_statuses) . ".");
                    return false;
                }
            }

            return true; // ID is valid and post exists (and matches status if checked)
        }
    } // End class PostActionManager

} // End if class_exists

