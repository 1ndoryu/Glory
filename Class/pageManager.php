<?php

namespace Glory\Class;

# Ensure this file is included only once
if (!class_exists('PageManager')) {

    class PageManager
    {
        # Meta key to identify pages managed by this class
        private const MANAGED_META_KEY = '_page_manager_managed';

        # Stores the definitions of pages to manage for the current request
        private static $pages = [];

        # Stores the ID of the designated front page after processing
        private static $frontPageId = null;

        /**
         * Define a page to be managed by the theme.
         * @param string $slug
         * @param string|null $title
         * @param string|null $template
         */
        public static function define(string $slug, string $title = null, string $template = null)
        {
            if (is_null($title)) {
                $title = ucwords(str_replace(['-', '_'], ' ', $slug));
            }
            if (is_null($template)) {
                $templateName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug)));
                $template = "Template{$templateName}.php";
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
            # Process pages on init (adjust priority if needed, e.g., 11 to run slightly later)
            add_action('init', [self::class, 'processPages'], 10);

            # Add the reconciliation (deletion check) hook. Runs later.
            add_action('init', [self::class, 'reconcileManagedPages'], 100); # Run much later in 'init'
        }

        /**
         * Create/Update defined pages.
         * @internal
         */
        public static function processPages()
        {
            if (empty(self::$pages)) {
                # Even if no pages are defined now, we might need to delete old ones later
                # So we don't return early here completely, reconciliation might still run.
                # However, the front page logic needs the ID from this loop.
                # Consider if front page should be unset if 'home' definition removed.
                 self::updateFrontPageOptions(null); # Explicitly clear if no home defined
                return;
            }

            $processedFrontPageId = null;
            $processedPageIds = []; // Keep track of IDs processed in this run

            foreach (self::$pages as $slug => $pageDef) {
                $pageSlug = $pageDef['slug'];
                $pageTitle = $pageDef['title'];
                $pageTemplate = $pageDef['template'];
                $currentPageId = null;

                $existingPage = get_page_by_path($pageSlug, OBJECT, 'page');

                if (!$existingPage) {
                    # Create Page
                    $pageData = [
                        'post_title'    => $pageTitle,
                        'post_content'  => '',
                        'post_status'   => 'publish',
                        'post_type'     => 'page',
                        'post_name'     => $pageSlug,
                        'page_template' => $pageTemplate,
                    ];
                    $insertedId = wp_insert_post($pageData, true);

                    if (!is_wp_error($insertedId)) {
                        $currentPageId = $insertedId;
                        # MARK THE PAGE AS MANAGED!
                        update_post_meta($currentPageId, self::MANAGED_META_KEY, true);
                        #error_log("PageManager: Created and marked page '{$pageSlug}' (ID: {$currentPageId}) as managed.");
                    } else {
                        error_log("PageManager: Failed to create page '{$pageSlug}': " . $insertedId->get_error_message());
                        continue;
                    }
                } else {
                    # Update Page (if needed)
                    $currentPageId = $existingPage->ID;
                    $currentTemplate = get_post_meta($currentPageId, '_wp_page_template', true);

                    # Ensure it's marked as managed (in case it existed before management)
                    update_post_meta($currentPageId, self::MANAGED_META_KEY, true);

                    if ($currentTemplate !== $pageTemplate) {
                        update_post_meta($currentPageId, '_wp_page_template', $pageTemplate);
                         #error_log("PageManager: Updated template for managed page '{$pageSlug}' (ID: {$currentPageId}).");
                    }
                    # Optional Title Update (still risky)
                    # if ($existingPage->post_title !== $pageTitle) { ... }
                }

                # Track successfully processed page ID
                if($currentPageId) {
                    $processedPageIds[] = $currentPageId;
                }


                # Check for front page
                if ($pageSlug === 'home' && $currentPageId) {
                    $processedFrontPageId = $currentPageId;
                }
            } # End foreach

            # Update front page settings based on this run's results
            self::updateFrontPageOptions($processedFrontPageId);

            # Store processed IDs for the reconciliation step (using a transient for cross-request storage)
            # Using a transient allows the reconcile function (running later) to know what was just processed.
            # Set a short expiration, e.g., 1 minute.
            set_transient('pagemanager_processed_ids', $processedPageIds, MINUTE_IN_SECONDS);

        }


        /**
         * Deletes pages marked as managed but no longer defined in the code.
         * Runs later on init to ensure all defines have happened.
         * @internal
         */
        public static function reconcileManagedPages() {
             #error_log("PageManager: Starting reconciliation...");

            # Get the list of page IDs that were confirmed/created in *this specific run*
            $currentlyDefinedAndProcessedIds = get_transient('pagemanager_processed_ids');
            # Clean up the transient immediately
            delete_transient('pagemanager_processed_ids');

             if ($currentlyDefinedAndProcessedIds === false) {
                 // This might happen if processPages didn't run or set the transient correctly.
                 // Or if the transient expired between processPages and reconcile.
                 // Decide how to handle this: either log an error and bail, or proceed cautiously
                 // by perhaps fetching IDs based on self::$pages (less reliable if processPages failed partially).
                 // For safety, let's bail if we don't have the confirmed list from the transient. 
                 error_log("PageManager: Reconciliation skipped. Could not retrieve processed IDs transient. This might be normal on first load after transient expiry or if processPages failed.");
                 return;
             }

            # Find ALL pages marked as managed by us in the database
            $args = [
                'post_type'      => 'page',
                'post_status'    => 'any', # Check all statuses (publish, draft, trash, etc.)
                'posts_per_page' => -1,      # Get all pages
                'meta_key'       => self::MANAGED_META_KEY,
                'meta_value'     => true,   # That have our flag
                'fields'         => 'ids',  # Only get the IDs
            ];
            $potentiallyManagedPageIds = get_posts($args);

            if (empty($potentiallyManagedPageIds)) {
                 #error_log("PageManager: Reconciliation - No pages found marked as managed.");
                return; # Nothing found marked as managed
            }

            #error_log("PageManager: Reconciliation - Found managed page IDs in DB: " . implode(', ', $potentiallyManagedPageIds));
            #error_log("PageManager: Reconciliation - Currently defined & processed IDs: " . implode(', ', $currentlyDefinedAndProcessedIds));


            # Figure out which pages have the flag but are NOT in the current definition list
            $pagesToDeleteIds = array_diff($potentiallyManagedPageIds, $currentlyDefinedAndProcessedIds);

            if (empty($pagesToDeleteIds)) {
                 #error_log("PageManager: Reconciliation - No pages to delete.");
                return; # All managed pages are accounted for in the current definition.
            }

            error_log("PageManager: Reconciliation - Pages marked for DELETION: " . implode(', ', $pagesToDeleteIds));

            # --- DANGER ZONE ---
            # Delete the identified pages
            foreach ($pagesToDeleteIds as $pageId) {
                # Double check it's not the front page ID IF it was processedFrontPageId was null earlier?
                 # Maybe add extra checks? e.g., don't delete if ID == get_option('page_on_front')?
                 # For now, we trust that if 'home' wasn't defined, updateFrontPageOptions cleared it.

                # wp_delete_post( $postid, $force_delete );
                # true = bypass trash, delete permanently. false = move to trash.
                $force_delete = true; # <<< SET TO false TO MOVE TO TRASH INSTEAD!
                $deleted = wp_delete_post($pageId, $force_delete);

                if ($deleted) {
                    #error_log("PageManager: DELETED managed page with ID: {$pageId} (Force delete: " . ($force_delete ? 'Yes' : 'No') . ")");
                } else {
                    # This can happen if the page was already deleted, or permissions issue, or hook interference.
                    error_log("PageManager: FAILED to delete managed page with ID: {$pageId}. It might already be deleted or another issue occurred.");
                }
            }
            # --- END DANGER ZONE ---
        }


        /**
         * Updates WordPress options for the static front page.
         * @internal
         * @param int|null $homePageId The ID of the page designated as 'home', or null.
         */
        private static function updateFrontPageOptions(?int $homePageId): void
        {
            # Simplified logic from before - ensures options match the $homePageId status
            $current_show_on_front = get_option('show_on_front');
            $current_page_on_front = get_option('page_on_front');
            # $current_page_for_posts = get_option('page_for_posts'); # Assuming blog page not managed here

             if ($homePageId) {
                 # Need static front page
                 if ($current_show_on_front !== 'page' || $current_page_on_front != $homePageId) {
                     update_option('show_on_front', 'page');
                     update_option('page_on_front', $homePageId);
                      #error_log("PageManager: Set front page to ID: {$homePageId}");
                     # Optional: Unset posts page if it conflicts
                     if (get_option('page_for_posts') == $homePageId) {
                         update_option('page_for_posts', 0);
                     }
                 }
             } else {
                 # Need latest posts on front
                 if ($current_show_on_front === 'page') {
                     update_option('show_on_front', 'posts');
                     update_option('page_on_front', 0);
                     # update_option('page_for_posts', 0); # Usually unset too
                     #error_log("PageManager: Set front page to 'posts' (no home page defined/found).");
                 }
             }
        }

    } # End class PageManager

} # End if class_exists