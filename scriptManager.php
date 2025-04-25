<?php
# App/Glory/ScriptManager.php
namespace Glory;


if (!class_exists('ScriptManager')) {

    class ScriptManager
    {
        /** @var array Stores script definitions. */
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
         *
         * @param string $handle WP handle and base name of the script file (without .js).
         * @param string|null $path Relative path to the theme directory (e.g., 'js/custom/mi-script.js'). If null, uses "js/{$handle}.js".
         * @param array $deps Dependencies (other script handles).
         * @param string|null $version Script version. If null, uses file modification time if dev mode, else global theme version.
         * @param bool $in_footer Load in footer (true) or in head (false).
         * @param array|null $localize Optional. Array with 'object_name' (string) and 'data' (array) to pass to the script.
         * @param bool|null $devMode Override development mode for this script: true (always cache-bust), false (never cache-bust), null (use global).
         */
        public static function define(
            string $handle,
            ?string $path = null,
            array $deps = [],
            ?string $version = null,
            bool $in_footer = true,
            ?array $localize = null, // <-- New parameter
            ?bool $devMode = null
        ): void {
            # If no path is specified, build the default path
            if (is_null($path)) {
                $path = 'js/' . $handle . '.js';
            }
            if (empty($handle)) {
                error_log("ScriptManager: Script handle cannot be empty.");
                return;
            }

            # Basic validation for localization data structure
            if (!is_null($localize) && (!isset($localize['object_name']) || !is_string($localize['object_name']) || !isset($localize['data']) || !is_array($localize['data']))) {
                error_log("ScriptManager: Invalid localize data structure for handle '{$handle}'. Requires 'object_name' (string) and 'data' (array).");
                $localize = null; // Ignore invalid data
            }


            self::$scripts[$handle] = [
                'path'      => $path,
                'deps'      => $deps,
                'version'   => $version,
                'in_footer' => $in_footer,
                'localize'  => $localize, // <-- Store localization data
                'dev_mode'  => $devMode,
                'handle'    => $handle
            ];
        }

        /**
         * Defines all .js scripts found in a specific folder.
         * Note: Does not support localization for folder definitions. Define individually if localization is needed.
         *
         * @param string $folderRelPath Relative path to the theme directory (e.g., 'js/vendor').
         * @param array $defaultDeps Default dependencies for all scripts in this folder.
         * @param bool $defaultInFooter Load in footer by default for these scripts.
         * @param bool|null $folderDevMode Override development mode for all scripts in this folder (unless individually overridden).
         * @param string $handlePrefix Optional prefix to add to each script handle.
         */
        public static function defineFolder(
            string $folderRelPath = 'js',
            array $defaultDeps = [],
            bool $defaultInFooter = true,
            ?bool $folderDevMode = null,
            string $handlePrefix = ''
        ): void {
            $fullFolderPath = get_template_directory() . '/' . trim($folderRelPath, '/\\'); // Trim both slashes

            if (!is_dir($fullFolderPath)) {
                error_log("ScriptManager: Folder not found at {$fullFolderPath} when defining folder.");
                return;
            }


            $files = glob($fullFolderPath . '/*.js');
            if ($files === false) {
                error_log("ScriptManager: Failed to scan folder {$fullFolderPath}");
                return; # Error reading directory
            }

            foreach ($files as $file) {
                // Sanitize handle: remove special chars except hyphen and underscore
                $raw_handle = $handlePrefix . basename($file, '.js');
                $handle = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw_handle);

                if (empty($handle)) {
                    error_log("ScriptManager: Generated handle is empty for file {$file}. Skipping.");
                    continue;
                }

                // Construct relative path correctly, ensuring forward slashes
                $relativePath = str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    trim($folderRelPath, '/\\') . '/' . basename($file)
                );


                if (!isset(self::$scripts[$handle])) {
                    self::define(
                        $handle,
                        $relativePath,
                        $defaultDeps,
                        null, // Version determined during enqueue
                        $defaultInFooter,
                        null, // Localization not supported directly here
                        $folderDevMode
                    );
                }
            }
        }

        /**
         * Registers the hook to enqueue defined scripts.
         */
        public static function register(): void
        {
            # Use a slightly higher priority (e.g., 20) in case other hooks need these scripts.
            add_action('wp_enqueue_scripts', [self::class, 'enqueueScripts'], 20);
        }

        /**
         * Function executed in the 'wp_enqueue_scripts' hook.
         * @internal Do not call directly.
         */
        public static function enqueueScripts(): void
        {
            if (empty(self::$scripts)) {
                return;
            }

            foreach (self::$scripts as $handle => $scriptDef) {
                # 1. Determine full path and URL
                $relativePath = ltrim($scriptDef['path'], '/\\'); // Ensure no leading slash
                $filePath = get_template_directory() . '/' . $relativePath;
                $fileUrl = get_template_directory_uri() . '/' . $relativePath;

                # 2. Verify file exists
                if (!file_exists($filePath)) {
                    error_log("ScriptManager: Script file not found at {$filePath} for handle '{$handle}'.");
                    continue; # Skip this script if it doesn't exist
                }

                # 3. Determine dev mode for THIS script
                $isDev = $scriptDef['dev_mode'] ?? self::$globalDevMode; # Uses override or global

                # 4. Determine version
                $scriptVersion = $scriptDef['version']; // Use specific version if provided
                if (is_null($scriptVersion)) {
                    // If no specific version, use filemtime in dev mode, else global theme version
                    $scriptVersion = $isDev ? filemtime($filePath) : self::$themeVersion;
                }

                # 5. Enqueue the script
                wp_enqueue_script(
                    $handle,
                    $fileUrl,
                    $scriptDef['deps'],
                    $scriptVersion, // Use calculated version
                    $scriptDef['in_footer']
                );

                # 6. Localize script if data is provided
                if (!empty($scriptDef['localize'])) {
                    wp_localize_script(
                        $handle,
                        $scriptDef['localize']['object_name'],
                        $scriptDef['localize']['data']
                    );
                }
            }
        }
    } // End class ScriptManager

} // End if class_exists