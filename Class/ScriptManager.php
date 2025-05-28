<?php

namespace Glory\Class; // Asegúrate que GloryLogger está en el mismo namespace o usa el FQCN

use Glory\Class\GloryLogger; // esto solo tiene info y error


if (!class_exists('ScriptManager')) {

    class ScriptManager
    {
        
        private static $scripts = [];
        private static $globalDevMode = false;
        private static $themeVersion = '1.0.0'; // Considera obtener esto dinámicamente

        public static function setGlobalDevMode(bool $enabled): void
        {
            self::$globalDevMode = $enabled;
        }

        public static function setThemeVersion(string $version): void
        {
            self::$themeVersion = $version;
        }

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
                GloryLogger::error("Script handle cannot be empty. Definition skipped.");
                return;
            }

            if (isset(self::$scripts[$handle])) {
                GloryLogger::info("Script handle '{$handle}' is already defined. Skipping duplicate definition.");
                return;
            }

            if (is_null($path)) {
                $path = 'js/' . $handle . '.js';
            }

            if (!is_null($localize)) {
                if (!isset($localize['object_name']) || !is_string($localize['object_name']) || empty($localize['object_name'])) {
                    GloryLogger::error("Invalid or empty 'object_name' for localization data for handle '{$handle}'. Localization skipped.");
                    $localize = null;
                } elseif (!isset($localize['data']) || !is_array($localize['data'])) {
                    GloryLogger::error("Invalid 'data' (must be an array) for localization data for handle '{$handle}'. Localization skipped.");
                    $localize = null;
                } elseif (empty($localize['data'])) {
                     // ADVERTENCIA: Si 'data' está vacío, wp_localize_script podría no generar nada útil.
                     GloryLogger::error("WARNING: 'data' array is EMPTY for localization data for handle '{$handle}'. ajax_url o nonce podrían estar vacíos en el momento de la definición.");
                }
            }

            self::$scripts[$handle] = [
                'path'      => $path,
                'deps'      => $deps,
                'version'   => $version,
                'in_footer' => $in_footer,
                'localize'  => $localize,
                'dev_mode'  => $devMode,
                'handle'    => $handle
            ];
            GloryLogger::info("DEBUG: Defined script '{$handle}'. Localization data: " . ($localize ? print_r($localize, true) : 'None'));
        }

        public static function defineFolder(
            string $folderRelPath = 'js',
            array $defaultDeps = [],
            bool $defaultInFooter = true,
            ?bool $folderDevMode = null,
            string $handlePrefix = ''
        ): void {
            $themeDir = get_template_directory(); 

            $normalizedFolderRelPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $folderRelPath);
            $fullFolderPath = rtrim($themeDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($normalizedFolderRelPath, DIRECTORY_SEPARATOR);

            if (!is_dir($fullFolderPath)) {
                if (trim($normalizedFolderRelPath, DIRECTORY_SEPARATOR) !== 'js') { 
                    GloryLogger::error("Folder not found at {$fullFolderPath} when defining folder '{$folderRelPath}'.");
                }
                return;
            }

            $files = glob($fullFolderPath . DIRECTORY_SEPARATOR . '*.js');
            if ($files === false || empty($files)) {
                GloryLogger::info("ScriptManager: No .js files found in folder {$fullFolderPath} or failed to scan.");
                return; 
            }

            foreach ($files as $file) {
                $filename = basename($file, '.js');
                $raw_handle = $handlePrefix . $filename;
                $handle = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $raw_handle));
                $handle = trim($handle, '-');

                if (empty($handle)) {
                    GloryLogger::error("Generated handle is empty for file '{$filename}' in folder '{$folderRelPath}'. Skipping.");
                    continue;
                }

                $webRelativePath = trim(str_replace(DIRECTORY_SEPARATOR, '/', $normalizedFolderRelPath), '/') . '/' . basename($file);

                if (!isset(self::$scripts[$handle])) {
                    self::define(
                        $handle,
                        $webRelativePath, 
                        $defaultDeps,
                        null,
                        $defaultInFooter,
                        null,
                        $folderDevMode
                    );
                }
            }
        }

        public static function register(): void
        {
            add_action('wp_enqueue_scripts', [self::class, 'enqueueScripts'], 20);
        }


        public static function enqueueScripts(): void
        {
            GloryLogger::info("DEBUG: enqueueScripts CALLED. Scripts defined: " . count(self::$scripts));

            if (empty(self::$scripts)) {
                return;
            }

            foreach (self::$scripts as $handle => $scriptDef) {
                GloryLogger::info("DEBUG: Processing handle '{$handle}' for enqueuing.");

                if (wp_script_is($handle, 'enqueued')) {
                    GloryLogger::info("Script '{$handle}' already enqueued by something else. Attempting to localize if data exists.");
                    if (!empty($scriptDef['localize']) && !empty($scriptDef['localize']['object_name']) && is_array($scriptDef['localize']['data'])) {
                        $localized_already_enqueued = wp_localize_script(
                            $handle,
                            $scriptDef['localize']['object_name'],
                            $scriptDef['localize']['data']
                        );
                        GloryLogger::info("DEBUG: For already enqueued '{$handle}', wp_localize_script returned: " . ($localized_already_enqueued ? 'true' : 'false'));
                    }
                    continue; 
                }

                $relativePath = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $scriptDef['path']), '/');
                $filePath = get_template_directory() . '/' . $relativePath;
                $fileUrl = get_template_directory_uri() . '/' . $relativePath;

                if (!file_exists($filePath)) {
                    GloryLogger::error("Script file NOT FOUND at '{$filePath}' for handle '{$handle}'. Skipping enqueue.");
                    continue;
                }

                $isDev = $scriptDef['dev_mode'] ?? self::$globalDevMode;
                $scriptVersion = $scriptDef['version'];
                if (is_null($scriptVersion)) {
                    $mtime = @filemtime($filePath);
                    $scriptVersion = ($isDev && $mtime) ? (string)$mtime : self::$themeVersion;
                }

                $registered = wp_register_script(
                    $handle,
                    $fileUrl,
                    $scriptDef['deps'],
                    $scriptVersion,
                    $scriptDef['in_footer']
                );

                if (!$registered) {
                    GloryLogger::error("FAILED to register script '{$handle}' at URL '{$fileUrl}'. wp_register_script returned false. Skipping.");
                    continue; 
                }
                GloryLogger::info("DEBUG: Successfully registered script '{$handle}'.");

                $localization_attempted = false;
                $localization_succeeded = false;

                if (!empty($scriptDef['localize']) && !empty($scriptDef['localize']['object_name']) && isset($scriptDef['localize']['data']) && is_array($scriptDef['localize']['data'])) {
                    $localization_attempted = true;
                    GloryLogger::info("DEBUG: Attempting to localize '{$handle}' with object '{$scriptDef['localize']['object_name']}'. Data: " . print_r($scriptDef['localize']['data'], true));

                    $localization_succeeded = wp_localize_script(
                        $handle,
                        $scriptDef['localize']['object_name'],
                        $scriptDef['localize']['data']
                    );

                    if ($localization_succeeded) {
                        GloryLogger::info("DEBUG: wp_localize_script SUCCEEDED for '{$handle}'.");
                    } else {
                        GloryLogger::error("wp_localize_script FAILED for handle '{$handle}'. Object name: '{$scriptDef['localize']['object_name']}'. This usually means the script wasn't properly registered or there's an issue with the parameters.");
                    }
                }

                wp_enqueue_script($handle);
                GloryLogger::info("DEBUG: Called wp_enqueue_script for '{$handle}'. Localized: " . ($localization_succeeded ? 'Yes' : 'No (Attempted: '.($localization_attempted?'Yes':'No').')'));

            } 
        } 
    } 
} 