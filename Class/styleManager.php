<?php

namespace Glory\Class;

if (!class_exists('StyleManager')) {

    /**
     * Manages the registration and enqueuing of CSS stylesheets within the Glory Framework.
     */
    class StyleManager
    {
        /** @var array Stores style definitions. */
        private static $styles = [];

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
         * Defines a single CSS stylesheet to enqueue.
         *
         * @param string $handle WP handle and base name of the style file (without .css).
         * @param string|null $cssPath Relative path to the theme directory (e.g., 'css/custom/mi-estilo.css'). If null, uses "css/{$handle}.css".
         * @param array $deps Dependencies (other style handles).
         * @param string|null $version Style version. If null, uses file modification time if dev mode, else global theme version.
         * @param string $media The media for which this stylesheet has been defined. Accepts 'all', 'print', 'screen', or media queries like '(orientation: portrait)', '(max-width: 640px)'. Default 'all'.
         * @param bool|null $devMode Override development mode for this style: true (always cache-bust), false (never cache-bust), null (use global).
         */
        public static function define(
            string $handle,
            ?string $cssPath = null,
            array $deps = [],
            ?string $version = null,
            string $media = 'all', // Default media type
            ?bool $devMode = null
        ): void {
            # If no path is specified, build the default path
            if (is_null($cssPath)) {
                $cssPath = 'css/' . $handle . '.css';
            }
            if (empty($handle)) {
                error_log("StyleManager: Style handle cannot be empty.");
                return;
            }

            self::$styles[$handle] = [
                'path'      => $cssPath,
                'deps'      => $deps,
                'version'   => $version,
                'media'     => $media, // Store media type
                'dev_mode'  => $devMode,
                'handle'    => $handle
            ];
        }

        /**
         * Defines all .css stylesheets found in a specific folder.
         *
         * @param string $folderRelPath Relative path to the theme directory (e.g., 'css/components'). Defaults to 'css'.
         * @param array $defaultDeps Default dependencies for all styles in this folder.
         * @param string $defaultMedia Default media type for styles in this folder.
         * @param bool|null $folderDevMode Override development mode for all styles in this folder (unless individually overridden).
         * @param string $handlePrefix Optional prefix to add to each style handle.
         */
        public static function defineFolder(
            string $folderRelPath = 'css', // Default folder path
            array $defaultDeps = [],
            string $defaultMedia = 'all', // Default media
            ?bool $folderDevMode = null,
            string $handlePrefix = ''
        ): void {
            $fullFolderPath = get_template_directory() . '/' . trim($folderRelPath, '/\\');

            if (!is_dir($fullFolderPath)) {
                // Only log error if not the default 'css' path, which might not exist
                if ($folderRelPath !== 'css') {
                    error_log("StyleManager: Folder not found at {$fullFolderPath} when defining folder.");
                }
                return;
            }

            $files = glob($fullFolderPath . '/*.css'); // Look for .css files
            if ($files === false) {
                error_log("StyleManager: Failed to scan folder {$fullFolderPath}");
                return;
            }

            foreach ($files as $file) {
                // Sanitize handle
                $raw_handle = $handlePrefix . basename($file, '.css');
                $handle = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw_handle);

                if (empty($handle)) {
                    error_log("StyleManager: Generated handle is empty for file {$file}. Skipping.");
                    continue;
                }

                // Construct relative path correctly
                $relativePath = str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    trim($folderRelPath, '/\\') . '/' . basename($file)
                );

                if (!isset(self::$styles[$handle])) { // Avoid overriding individually defined styles
                    self::define(
                        $handle,
                        $relativePath,
                        $defaultDeps,
                        null, // Version determined during enqueue
                        $defaultMedia, // Use default media
                        $folderDevMode
                    );
                }
            }
        }

        /**
         * Registers the hook to enqueue defined styles.
         * Typically called once during theme setup.
         */
        public static function register(): void
        {
            // Styles should generally be enqueued earlier than scripts,
            // but using the same hook and priority as ScriptManager is usually fine.
            // Using priority 10 or 15 might be slightly better if specific load order matters.
            add_action('wp_enqueue_scripts', [self::class, 'enqueueStyles'], 15); // Slightly earlier priority?
        }

        /**
         * Function executed in the 'wp_enqueue_scripts' hook.
         * Enqueues all defined stylesheets.
         * @internal Do not call directly.
         */
        public static function enqueueStyles(): void
        {
            if (empty(self::$styles)) {
                return;
            }

            foreach (self::$styles as $handle => $styleDef) {
                # 1. Determine full path and URL
                $relativePath = ltrim($styleDef['path'], '/\\');
                $filePath = get_template_directory() . '/' . $relativePath;
                $fileUrl = get_template_directory_uri() . '/' . $relativePath;

                # 2. Verify file exists
                if (!file_exists($filePath)) {
                    error_log("StyleManager: Style file not found at {$filePath} for handle '{$handle}'.");
                    continue;
                }

                # 3. Determine dev mode for THIS style
                $isDev = $styleDef['dev_mode'] ?? self::$globalDevMode;

                # 4. Determine version
                $styleVersion = $styleDef['version'];
                if (is_null($styleVersion)) {
                    $styleVersion = $isDev && file_exists($filePath) ? filemtime($filePath) : self::$themeVersion;
                }

                # 5. Enqueue the style using wp_enqueue_style
                wp_enqueue_style(
                    $handle,
                    $fileUrl,
                    $styleDef['deps'],
                    $styleVersion,
                    $styleDef['media'] // Pass the media type
                );
            }
        }
    } // End class StyleManager

} // End if class_exists