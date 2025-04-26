<?php

namespace Glory\Class;


if (!class_exists('ScriptManager')) {

    class ScriptManager
    {
        /** @var array Stores script definitions. Keys are handles. */
        private static $scripts = [];

        /** @var bool Global development mode status. */
        private static $globalDevMode = false;

        /** @var string Global theme version (fallback). */
        private static $themeVersion = '1.0.0';

        /**
         * Sets global development mode status.
         * @param bool $enabled True to enable, False to disable.
         */
        public static function setGlobalDevMode(bool $enabled): void
        {
            self::$globalDevMode = $enabled;
        }

        /**
         * Sets global theme version to use as fallback.
         * @param string $version
         */
        public static function setThemeVersion(string $version): void
        {
            self::$themeVersion = $version;
        }

        /**
         * Defines a single script to enqueue, optionally with localization data.
         * If a script with the same handle is already defined, this definition will be ignored.
         *
         * @param string $handle WP handle for the script.
         * @param string|null $path Relative path from the theme root (e.g., 'js/custom/my-script.js'). If null, assumes "js/{$handle}.js".
         * @param array $deps Dependencies (other script handles).
         * @param string|null $version Script version. If null, calculated based on dev mode.
         * @param bool $in_footer Load in footer (true) or head (false).
         * @param array|null $localize Optional. Array with 'object_name' (string) and 'data' (array).
         * @param bool|null $devMode Override global dev mode for this script.
         */
        public static function define(
            string $handle,
            ?string $path = null,
            array $deps = [],
            ?string $version = null,
            bool $in_footer = true,
            ?array $localize = null,
            ?bool $devMode = null
        ): void {
            if (empty($handle)) {
                error_log("ScriptManager: Script handle cannot be empty. Definition skipped.");
                return;
            }

            // --- CHANGE: Check if handle already exists ---
            if (isset(self::$scripts[$handle])) {
                // Optional: Log that we are skipping a duplicate definition. Useful for debugging.
                 // error_log("ScriptManager: Script handle '{$handle}' is already defined. Skipping duplicate definition.");
                return; // Script already defined, do nothing further.
            }
            // --- END CHANGE ---

            # If no path is specified, build the default path
            if (is_null($path)) {
                $path = 'js/' . $handle . '.js'; // Default path convention
            }

            # Basic validation for localization data structure
            if (!is_null($localize)) {
                if (!isset($localize['object_name']) || !is_string($localize['object_name']) || empty($localize['object_name'])) {
                     error_log("ScriptManager: Invalid or empty 'object_name' for localization data for handle '{$handle}'. Localization skipped.");
                     $localize = null;
                } elseif (!isset($localize['data']) || !is_array($localize['data'])) {
                    error_log("ScriptManager: Invalid 'data' (must be an array) for localization data for handle '{$handle}'. Localization skipped.");
                    $localize = null;
                }
            }

            // Store the definition
            self::$scripts[$handle] = [
                'path'      => $path,
                'deps'      => $deps,
                'version'   => $version, // Store null if not specified, will be calculated later
                'in_footer' => $in_footer,
                'localize'  => $localize,
                'dev_mode'  => $devMode,   // Store null if not specified, will use global later
                'handle'    => $handle     // Store handle for consistency (though it's the array key)
            ];
        }

        /**
         * Defines all .js scripts found in a specific folder.
         * Skips files if a handle derived from the filename is already defined.
         * Note: Does not support localization for folder definitions. Define individually if needed.
         *
         * @param string $folderRelPath Relative path to the theme directory (e.g., 'js/vendor').
         * @param array $defaultDeps Default dependencies for all scripts in this folder.
         * @param bool $defaultInFooter Load in footer by default for these scripts.
         * @param bool|null $folderDevMode Override development mode for all scripts in this folder (unless individually overridden).
         * @param string $handlePrefix Optional prefix to add to each generated script handle.
         */
        public static function defineFolder(
            string $folderRelPath = 'js',
            array $defaultDeps = [],
            bool $defaultInFooter = true,
            ?bool $folderDevMode = null,
            string $handlePrefix = ''
        ): void {
            $themeDir = get_template_directory();
            // Normalize path separators and trim slashes
            $normalizedFolderRelPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $folderRelPath);
            $fullFolderPath = rtrim($themeDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($normalizedFolderRelPath, DIRECTORY_SEPARATOR);

            if (!is_dir($fullFolderPath)) {
                 // Only log if it's not the root 'js' folder, which might legitimately not exist.
                 if (trim($normalizedFolderRelPath, DIRECTORY_SEPARATOR) !== 'js') {
                      error_log("ScriptManager: Folder not found at {$fullFolderPath} when defining folder '{$folderRelPath}'.");
                 }
                return;
            }

            $files = glob($fullFolderPath . DIRECTORY_SEPARATOR . '*.js');
            if ($files === false) {
                error_log("ScriptManager: Failed to scan folder {$fullFolderPath} for scripts.");
                return;
            }

            foreach ($files as $file) {
                $filename = basename($file, '.js');
                // Sanitize handle: keep alphanumeric, hyphen, underscore. Convert others to hyphen.
                $raw_handle = $handlePrefix . $filename;
                $handle = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $raw_handle));
                $handle = trim($handle, '-'); // Remove leading/trailing hyphens potentially created

                if (empty($handle)) {
                    error_log("ScriptManager: Generated handle is empty for file '{$filename}' in folder '{$folderRelPath}'. Skipping.");
                    continue;
                }

                // Construct relative path using forward slashes for web URLs
                $relativePath = trim(str_replace(DIRECTORY_SEPARATOR, '/', $normalizedFolderRelPath), '/') . '/' . basename($file);


                // Check if handle already defined BEFORE calling define()
                // This check is technically redundant now because define() has its own check,
                // but it's slightly more efficient to check here first.
                if (!isset(self::$scripts[$handle])) {
                    self::define(
                        $handle,
                        $relativePath,
                        $defaultDeps,
                        null, // Version determined during enqueue
                        $defaultInFooter,
                        null, // Localization not supported directly for folders
                        $folderDevMode // Pass folder-level dev mode override
                    );
                } else {
                    // Optional: Log that we skipped a file because handle exists.
                    // error_log("ScriptManager: Script handle '{$handle}' derived from file '{$filename}' in folder '{$folderRelPath}' already defined. Skipping file.");
                }
            }
        }

        /**
         * Registers the WordPress hook to enqueue the defined scripts.
         */
        public static function register(): void
        {
            // Priority 20 is usually good to run after default theme/plugin scripts but before late scripts.
            add_action('wp_enqueue_scripts', [self::class, 'enqueueScripts'], 20);
        }

        /**
         * Callback function for the 'wp_enqueue_scripts' action.
         * Iterates through defined scripts and enqueues them using WordPress functions.
         * @internal Do not call directly. This is hooked into WordPress.
         */
        public static function enqueueScripts(): void
        {
            if (empty(self::$scripts)) {
                return; // No scripts defined.
            }

            $wp_scripts = wp_scripts(); // Get the WP_Scripts object to check registered status

            foreach (self::$scripts as $handle => $scriptDef) {

                // Double-check if script *already* enqueued/registered by something else (less likely now but safe)
                // This primarily prevents issues if another plugin/theme uses the same handle *before* our hook runs.
                if (wp_script_is($handle, 'registered') || wp_script_is($handle, 'enqueued')) {
                    // If already registered/enqueued, potentially localize if needed and not already done.
                    if (!empty($scriptDef['localize'])) {
                        // Check if localization data under this object name is already present for this handle
                        // This check is complex because WP stores localization data internally.
                        // A simpler approach is to just call wp_localize_script again; WP might handle duplicates gracefully
                        // or overwrite, which might be acceptable for localization updates.
                        // Let's call it to ensure our data is attempted.
                        wp_localize_script(
                            $handle,
                            $scriptDef['localize']['object_name'],
                            $scriptDef['localize']['data']
                        );
                    }
                    continue; // Skip re-registering/enqueuing if WP already knows about it.
                }


                # 1. Determine full path and URL
                 // Ensure relative path starts without a slash for concatenation
                $relativePath = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $scriptDef['path']), '/');
                $filePath = get_template_directory() . '/' . $relativePath;
                $fileUrl = get_template_directory_uri() . '/' . $relativePath;

                # 2. Verify file exists physically
                if (!file_exists($filePath)) {
                    error_log("ScriptManager: Script file not found at '{$filePath}' for handle '{$handle}'. Skipping enqueue.");
                    continue;
                }

                # 3. Determine effective development mode for this script
                // Priority: Script-specific override > Global setting
                $isDev = $scriptDef['dev_mode'] ?? self::$globalDevMode;

                # 4. Determine script version
                $scriptVersion = $scriptDef['version']; // Use explicitly defined version if provided
                if (is_null($scriptVersion)) {
                    // If no explicit version: use file modification time in dev mode, otherwise use global theme version.
                    // Ensure filemtime() doesn't fail if file disappears between checks (unlikely but possible)
                    $mtime = @filemtime($filePath); // Use @ to suppress potential warning if file gone
                    $scriptVersion = ($isDev && $mtime) ? $mtime : self::$themeVersion;
                }

                # 5. Register the script (use wp_register_script first for clarity and better dependency handling)
                $registered = wp_register_script(
                    $handle,
                    $fileUrl,
                    $scriptDef['deps'],
                    $scriptVersion,
                    $scriptDef['in_footer']
                );

                 if (!$registered) {
                     error_log("ScriptManager: Failed to register script '{$handle}' at URL '{$fileUrl}'. Skipping.");
                     continue;
                 }


                # 6. Localize script IF data is provided AND registration succeeded
                if (!empty($scriptDef['localize'])) {
                    wp_localize_script(
                        $handle,
                        $scriptDef['localize']['object_name'],
                        $scriptDef['localize']['data']
                    );
                    // Note: wp_localize_script implicitly enqueues the script it's attached to.
                } else {
                     # 7. Enqueue the script if not localized (localization auto-enqueues)
                     wp_enqueue_script($handle);
                }

            } // End foreach loop
        } // End enqueueScripts method

    } // End ScriptManager class

} // End if class_exists