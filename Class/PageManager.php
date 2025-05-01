<?php

namespace Glory\Class;

use Glory\Class\GloryLogger;

# Ensure this file is included only once
if (!class_exists('PageManager')) {

    class PageManager
    {
        # Meta key to identify pages managed by this class
        private const MANAGED_META_KEY = '_page_manager_managed';

        # Stores the definitions of pages to manage for the current request
        private static $pages = [];

        // Removed static $frontPageId - it wasn't reliably used across methods/requests.
        // We rely on processPages determining it and passing to updateFrontPageOptions.

        /**
         * Define a page to be managed by the theme.
         * @param string $slug
         * @param string|null $title
         * @param string|null $template
         */
        public static function define(string $slug, string $title = null, string $template = null)
        {
            // Input validation for slug
            if (empty($slug) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                GloryLogger::error("PageManager: Invalid slug '{$slug}'. Slugs must be lowercase alphanumeric with hyphens.");
                 return;
            }

            if (is_null($title)) {
                $title = ucwords(str_replace(['-', '_'], ' ', $slug));
            }
            if (is_null($template)) {
                $templateName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug)));
                $template = "Template{$templateName}.php"; // Ensure this template file exists in your theme!
            }
            self::$pages[$slug] = [
                'title'    => $title,
                'template' => $template,
                'slug'     => $slug
            ];
        }

        /**
         * Register hooks
         */
        public static function register()
        {
            # Process pages on init. Priority 10 is usually fine.
            add_action('init', [self::class, 'processPages'], 10);

            # Add the reconciliation hook. Priority 100 ensures it runs after most 'init' actions.
            add_action('init', [self::class, 'reconcileManagedPages'], 100);
        }

        /**
         * Create/Update defined pages.
         * @internal
         */
        public static function processPages()
        {
            $processedFrontPageId = null;
            $processedPageIds = []; // Keep track of IDs processed in this run

            // Proceed even if self::$pages is empty, to ensure front page option is handled
            // and the transient is set (potentially empty).

            if (!empty(self::$pages)) {
                foreach (self::$pages as $slug => $pageDef) {
                    $pageSlug = $pageDef['slug'];
                    $pageTitle = $pageDef['title'];
                    $pageTemplate = $pageDef['template'];
                    $currentPageId = null;

                    // Validate template exists (optional but recommended)
                    // if ($pageTemplate && !locate_template($pageTemplate)) {
                    //     GloryLogger::error("PageManager: Template file '{$pageTemplate}' not found for page '{$pageSlug}'.");
                    //     // Decide: continue without template, or skip page? Let's continue for now.
                    //     // $pageTemplate = ''; // Clear template if not found?
                    // }


                    $existingPage = get_page_by_path($pageSlug, OBJECT, 'page');

                    if (!$existingPage) {
                        # Create Page
                        $pageData = [
                            'post_title'    => $pageTitle,
                            'post_content'  => "<!-- Page managed by Glory PageManager. Slug: {$pageSlug} -->", // Add placeholder content
                            'post_status'   => 'publish',
                            'post_type'     => 'page',
                            'post_name'     => $pageSlug,
                            // Only set template if it's not empty/null
                            'page_template' => $pageTemplate ?: '',
                        ];
                        $insertedId = wp_insert_post($pageData, true); // Pass true to return WP_Error on failure

                        if (!is_wp_error($insertedId) && $insertedId > 0) {
                            $currentPageId = $insertedId;
                            update_post_meta($currentPageId, self::MANAGED_META_KEY, true);
                            GloryLogger::info("PageManager: CREATED and marked page '{$pageSlug}' (ID: {$currentPageId}) as managed.");
                        } else {
                            $error_message = is_wp_error($insertedId) ? $insertedId->get_error_message() : 'Unknown error (ID 0)';
                            GloryLogger::error("PageManager: FAILED to create page '{$pageSlug}': " . $error_message);
                            continue; // Skip to next page definition
                        }
                    } else {
                        # Update Page (if needed)
                        $currentPageId = $existingPage->ID;

                        // Ensure it's marked as managed
                        update_post_meta($currentPageId, self::MANAGED_META_KEY, true);

                        // Check and update template if necessary
                        $currentTemplate = get_post_meta($currentPageId, '_wp_page_template', true);
                        $newTemplateValue = $pageTemplate ?: ''; // Use empty string if template is null/empty

                        if ($currentTemplate !== $newTemplateValue) {
                            update_post_meta($currentPageId, '_wp_page_template', $newTemplateValue);
                            GloryLogger::info("PageManager: Updated template for managed page '{$pageSlug}' (ID: {$currentPageId}) to '{$newTemplateValue}'.");
                        }

                        // Optional Title Update (Risky - user might change it intentionally)
                        // if ($existingPage->post_title !== $pageTitle && !empty($pageTitle)) {
                        //     wp_update_post(['ID' => $currentPageId, 'post_title' => $pageTitle]);
                        //     error_log("PageManager: Updated title for managed page '{$pageSlug}' (ID: {$currentPageId}).");
                        // }

                         // Optional: Ensure status is 'publish' if found in another state?
                        // if ($existingPage->post_status !== 'publish') {
                        //     wp_update_post(['ID' => $currentPageId, 'post_status' => 'publish']);
                        //      error_log("PageManager: Set status to 'publish' for managed page '{$pageSlug}' (ID: {$currentPageId}).");
                        // }
                    }

                    # Track successfully processed page ID
                    if ($currentPageId) {
                        $processedPageIds[] = $currentPageId;

                        # Check specifically for the 'home' slug
                        if ($pageSlug === 'home') {
                            $processedFrontPageId = $currentPageId;
                        }
                    }
                } # End foreach
            } else {
                GloryLogger::error("PageManager processPages: No pages defined in self::\$pages.");
                 // If no pages are defined, ensure 'home' isn't lingering as the front page if it wasn't processed.
                 // Check if the current front page ID is managed but *not* in the (empty) processed list.
                 $currentFrontPageId = (int) get_option('page_on_front');
                 if ($currentFrontPageId > 0 && get_option('show_on_front') === 'page') {
                      if (get_post_meta($currentFrontPageId, self::MANAGED_META_KEY, true)) {
                          // Current front page IS managed, but wasn't processed (because no pages defined).
                          // We should likely revert to 'posts'. 
                          GloryLogger::error("Current front page (ID: {$currentFrontPageId}) is managed but no pages defined. Setting front page to null.");
                          $processedFrontPageId = null; // Ensure it gets unset
                      }
                 }
            }


            # Update front page settings based on this run's results.
            self::updateFrontPageOptions($processedFrontPageId);

            # Store processed IDs for the reconciliation step. INCREASED EXPIRY.
            # Always set the transient, even if the array is empty.
            set_transient('pagemanager_processed_ids', $processedPageIds, 15 * MINUTE_IN_SECONDS);
        }


        /**
         * Deletes pages marked as managed but no longer defined in the code.
         * @internal
         */
        public static function reconcileManagedPages() {
            GloryLogger::info("PageManager reconcileManagedPages: Starting reconciliation...");

            $currentlyDefinedAndProcessedIds = get_transient('pagemanager_processed_ids');
            // Don't delete transient immediately, might need it if we reconstruct

            if ($currentlyDefinedAndProcessedIds === false) {
                // Fallback: Reconstruct expected IDs based on current definitions in self::$pages
                $currentlyDefinedAndProcessedIds = [];
                if (!empty(self::$pages)) {
                    $definedSlugs = array_keys(self::$pages);
                    $args = [
                        'post_type' => 'page',
                        'post_status' => 'any',
                        'posts_per_page' => -1,
                        'meta_key' => self::MANAGED_META_KEY,
                        'meta_value' => true,
                        'fields' => 'ids',
                        // Optimization: Only fetch pages whose slugs *might* be in our definitions
                        // Requires WP 4.4+ for post_name__in
                        // 'post_name__in' => $definedSlugs, // This helps narrow down the query
                    ];
                    $reconstructedIds = get_posts($args);

                     // We need to double-check the slugs match because get_posts meta_query might not be perfect with post_name__in
                     if (!empty($reconstructedIds)) {
                        foreach ($reconstructedIds as $pageId) {
                            $pageSlug = get_post_field('post_name', $pageId);
                            if (in_array($pageSlug, $definedSlugs, true)) {
                                $currentlyDefinedAndProcessedIds[] = $pageId;
                            }
                        }
                     }

                    GloryLogger::info("PageManager reconcileManagedPages: Reconstructed expected IDs based on definitions: " . (!empty($currentlyDefinedAndProcessedIds) ? implode(', ', $currentlyDefinedAndProcessedIds) : 'None'));
                } else {
                    GloryLogger::error("PageManager reconcileManagedPages: No pages defined, reconciliation based on definitions yields no expected IDs.");
                }
                 // If still false/empty after reconstruction attempt, there's nothing to compare against *from definitions*. 
                 // We still need to compare against ALL managed pages found in DB below.
                 if (empty($currentlyDefinedAndProcessedIds)) {
                    GloryLogger::info("PageManager reconcileManagedPages: Fallback reconstruction resulted in empty list. Reconciliation will compare ALL DB managed pages against an empty 'current' list.");
                 }
                 // Now delete the (potentially expired) transient if we haven't already
                 delete_transient('pagemanager_processed_ids');

            } else {
                 // Transient was found, log it and delete it.
                 delete_transient('pagemanager_processed_ids');
                 // Ensure it's an array, even if empty from transient
                 if (!is_array($currentlyDefinedAndProcessedIds)) {
                     $currentlyDefinedAndProcessedIds = [];
                 }
            }


            # Find ALL pages marked as managed by us in the database
            $args_all_managed = [
                'post_type'      => 'page',
                'post_status'    => 'any', // Include trash, draft etc.
                'posts_per_page' => -1,
                'meta_key'       => self::MANAGED_META_KEY,
                'meta_value'     => true,
                'fields'         => 'ids',
            ];
            $potentiallyManagedPageIds = get_posts($args_all_managed);

            if (empty($potentiallyManagedPageIds)) {
                GloryLogger::info("PageManager reconcileManagedPages: No pages found marked as managed in DB. Reconciliation complete.");
                return;
            }

            GloryLogger::info("PageManager reconcileManagedPages: Found managed page IDs in DB: " . implode(', ', $potentiallyManagedPageIds));

            # Figure out which pages have the flag but are NOT in the current definition list (or transient)
            $pagesToDeleteIds = array_diff($potentiallyManagedPageIds, $currentlyDefinedAndProcessedIds);

            if (empty($pagesToDeleteIds)) {
                GloryLogger::info("All managed pages in DB are accounted for. No pages to delete.");
                return;
            }

            GloryLogger::info("Pages marked for potential DELETION (Managed in DB but not in current definition/process run): " . implode(', ', $pagesToDeleteIds));

            # --- DANGER ZONE ---.
            $force_delete = true; // Set to false to move to trash instead
            $currentFrontPageId = (int) get_option('page_on_front');
            $currentPostsPageId = (int) get_option('page_for_posts');

            foreach ($pagesToDeleteIds as $pageId) {
                // SAFETY CHECKS:
                // 1. Don't delete the page currently assigned as 'page_on_front'
                if ($pageId === $currentFrontPageId && $currentFrontPageId > 0) { 
                    GloryLogger::info("SKIPPING deletion of page ID {$pageId} because it is currently set as the static front page.");
                    continue;
                }
                // 2. Don't delete the page currently assigned as 'page_for_posts'
                if ($pageId === $currentPostsPageId && $currentPostsPageId > 0) { 
                    error_log("PageManager reconcileManagedPages: SKIPPING deletion of page ID {$pageId} because it is currently set as the posts page.");
                    continue;
                }

                GloryLogger::info("PageManager reconcileManagedPages: Attempting to delete page ID: {$pageId} (Force delete: " . ($force_delete ? 'Yes' : 'No') . ")");
                $deleted = wp_delete_post($pageId, $force_delete);

                if ($deleted) {
                    GloryLogger::info("PageManager reconcileManagedPages: DELETED managed page with ID: {$pageId}.");
                } else {
                    // wp_delete_post returns false or null or WP_Error. Could be permissions, already deleted, etc.
                    GloryLogger::error("PageManager reconcileManagedPages: FAILED to delete managed page with ID: {$pageId}. It might already be deleted or another issue occurred.");
                }
            }
            # --- END DANGER ZONE ---
            GloryLogger::info("PageManager reconcileManagedPages: Reconciliation finished.");
        }


        /**
         * Updates WordPress options for the static front page.
         * @internal
         * @param int|null $homePageId The ID of the page designated as 'home', or null.
         */
        private static function updateFrontPageOptions(?int $homePageId): void
        {
            $current_show_on_front = get_option('show_on_front');
            $current_page_on_front = (int) get_option('page_on_front'); // Cast to int
            $current_page_for_posts = (int) get_option('page_for_posts');

            GloryLogger::info("PageManager updateFrontPageOptions: Received ID: " . ($homePageId ?? 'null') . ". Current settings: show_on_front='{$current_show_on_front}', page_on_front='{$current_page_on_front}', page_for_posts='{$current_page_for_posts}'");

            if ($homePageId && $homePageId > 0) {
                 // Validate the provided ID corresponds to a real, published page
                 $homePageObject = get_post($homePageId);
                 if (!$homePageObject || $homePageObject->post_type !== 'page' || $homePageObject->post_status !== 'publish') {
                    GloryLogger::error("PageManager updateFrontPageOptions: Provided home page ID {$homePageId} is invalid, not a page, or not published. Cannot set as front page.");
                     // Optional: If the *current* setting points to this invalid ID, should we revert to posts?
                     if ($current_show_on_front === 'page' && $current_page_on_front === $homePageId) {
                         error_log("PageManager updateFrontPageOptions: Reverting to 'posts' because current front page ID {$homePageId} is invalid.");
                         update_option('show_on_front', 'posts'); 
                         update_option('page_on_front', 0);
                     }
                     return; // Do not proceed with setting this invalid ID
                 }

                 // Proceed only if the page is valid
                 $optionsChanged = false;
                 if ($current_show_on_front !== 'page') {
                     update_option('show_on_front', 'page');
                    GloryLogger::info("PageManager updateFrontPageOptions: Set show_on_front = 'page'");
                     $optionsChanged = true;
                 }
                 if ($current_page_on_front !== $homePageId) {
                     update_option('page_on_front', $homePageId);
                    GloryLogger::info("PageManager updateFrontPageOptions: Set page_on_front = {$homePageId}");
                     $optionsChanged = true;

                     // If the new front page was previously the posts page, unset the posts page.
                     if ($current_page_for_posts === $homePageId) {
                         update_option('page_for_posts', 0);
                         GloryLogger::info("Unset page_for_posts because it matched the new front page ID {$homePageId}");
                     }
                 }

                 if ($optionsChanged) {
                    GloryLogger::info("PageManager updateFrontPageOptions: Front page options updated.");
                    // It might be beneficial to flush rewrite rules here, although often not strictly necessary for option changes.
                    // flush_rewrite_rules(); // Use with caution - potentially slow. Only if needed.
                 } else { 
                    GloryLogger::info("Front page options were already correctly set for ID {$homePageId}.");
                 }

            } else {
                 // No valid home page ID provided (or 'home' slug wasn't defined/found)
                 // We should set the front page to display 'posts' IF it's currently 'page'.
                 if ($current_show_on_front === 'page') {
                     update_option('show_on_front', 'posts');
                     update_option('page_on_front', 0); // Unset the specific page ID
                     // Optional: Also unset page_for_posts? Usually yes.
                     // update_option('page_for_posts', 0);
                    GloryLogger::info("PageManager updateFrontPageOptions: No valid home page ID provided; set show_on_front = 'posts'.");
                 } else {
                    GloryLogger::info("PageManager updateFrontPageOptions: No valid home page ID provided, and show_on_front is already 'posts'. No changes needed.");
                }
            }
        }

    } # End class PageManager

} # End if class_exists