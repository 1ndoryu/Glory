<?php

namespace Glory\Integration\Avada\Options;

use Glory\Core\OpcionRepository;
use Glory\Manager\OpcionManager;

final class Sync
{
    public static function filterPreOptionFromAvada($pre, $option, $default)
    {
        if (!class_exists('Fusion_Settings')) {
            return $pre;
        }
        try {
            $optionName = \Fusion_Settings::get_option_name();
            $globals    = get_option($optionName, []);
            if (is_array($globals) && array_key_exists($option, $globals)) {
                $value = Normalizer::normalizeOptionValueForRead((string) $option, $globals[$option]);
                if (Normalizer::isBooleanOption((string) $option)) {
                    $ret = $value ? 1 : 0;
                    Logger::log('filterPreOptionFromAvada:boolean', ['id' => $option, 'stored' => $globals[$option], 'normalized' => $ret]);
                    return $ret;
                }
                Logger::log('filterPreOptionFromAvada:value', ['id' => $option, 'stored' => $globals[$option], 'normalized' => $value]);
                return $value;
            }
            Logger::log('filterPreOptionFromAvada:miss', ['id' => $option]);
        } catch (\Throwable $t) {
            Logger::log('filterPreOptionFromAvada:error', ['id' => (string) $option, 'err' => $t->getMessage()]);
        }
        return $pre;
    }

    public static function mirrorUpdateToAvada($value, $old_value, $option)
    {
        if (!class_exists('Fusion_Settings')) {
            return $value;
        }
        try {
            $optionName = \Fusion_Settings::get_option_name();
            $globals    = get_option($optionName, []);
            if (!is_array($globals)) {
                $globals = [];
            }
            if (is_array($globals) && array_key_exists($option, $globals)) {
                Logger::log('mirrorUpdateToAvada:skip_existing', ['id' => $option, 'existing' => $globals[$option], 'incoming' => $value]);
                return $value;
            }
            $globals[$option] = Normalizer::normalizeOptionValueForWrite((string) $option, $value);
            Logger::log('mirrorUpdateToAvada:write', ['id' => $option, 'write' => $globals[$option]]);
            update_option($optionName, $globals);
        } catch (\Throwable $t) {
            Logger::log('mirrorUpdateToAvada:error', ['id' => (string) $option, 'err' => $t->getMessage()]);
        }
        return $value;
    }

    public static function mirrorDeleteFromAvada($option): void
    {
        if (!class_exists('Fusion_Settings')) {
            return;
        }
        try {
            $optionName = \Fusion_Settings::get_option_name();
            $globals    = get_option($optionName, []);
            if (is_array($globals) && array_key_exists($option, $globals)) {
                unset($globals[$option]);
                update_option($optionName, $globals);
                Logger::log('mirrorDeleteFromAvada:deleted', ['id' => $option]);
            }
        } catch (\Throwable $t) {
            Logger::log('mirrorDeleteFromAvada:error', ['id' => (string) $option, 'err' => $t->getMessage()]);
        }
    }

    public static function filterMergeGloryValuesIntoAvadaOptions($value)
    {
        try {
            if (!is_array($value)) {
                $value = is_array($value) ? $value : [];
            }

            $ids = Discovery::discoverOptionIds();
            if (empty($ids)) {
                $ids = Discovery::getFallbackOptionIds();
            }

            $merged = [];
            foreach ($ids as $id) {
                if (Discovery::shouldExcludeById($id)) {
                    continue;
                }
                try {
                    $repoVal = OpcionRepository::get($id);
                    if ($repoVal !== OpcionRepository::getCentinela()) {
                        $value[$id] = Normalizer::normalizeOptionValueForWrite($id, $repoVal);
                        $merged[$id] = $value[$id];
                    }
                } catch (\Throwable $t) {
                }
            }
            if (!empty($merged)) {
                Logger::log('option_merge:merged_repo_values', ['keys' => $merged]);
            }
        } catch (\Throwable $t) {
            Logger::log('option_merge:error', ['err' => $t->getMessage()]);
        }
        return $value;
    }

    public static function debugLogFusionOptionsUpdate($value, $old_value, $option)
    {
        try {
            $is_user_save = false;
            if (isset($_POST['data']) && is_string($_POST['data'])) {
                $is_user_save = true;
            }
            if (defined('DOING_AJAX') && DOING_AJAX) {
                $is_user_save = true;
            }
            if (isset($_POST['action'])) {
                $action = (string) $_POST['action'];
                if (stripos($action, 'fusion') !== false || stripos($action, 'fusionredux') !== false) {
                    $is_user_save = true;
                }
            }
            $reqUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
            if (stripos($reqUri, 'admin-ajax.php') !== false) {
                $is_user_save = true;
            }
            if ((isset($_REQUEST['avada_update']) && (string) $_REQUEST['avada_update'] === '1')) {
                $is_user_save = true;
            }
            Logger::log('fusion_options:pre_update_hook_start', ['user_save' => $is_user_save ? 1 : 0]);

            $existing = get_option($option, []);
            if (!is_array($existing)) {
                $existing = [];
            }

            $glory_existing = [];
            foreach ($existing as $k => $v) {
                if (strpos((string) $k, 'glory_') === 0) {
                    $glory_existing[$k] = $v;
                }
            }
            Logger::log('fusion_options:existing_glory_db_values', $glory_existing);

            if (is_array($value)) {
                $ids = Discovery::discoverOptionIds();
                $preserved = [];

                foreach ($ids as $id) {
                    if ($id === 'glory_componente_navegacion_ajax_activado') {
                        $opt_def_debug = Discovery::getGloryOptionDefinition($id);
                        $default_value_debug = $opt_def_debug['valorDefault'] ?? 'not_found';
                        $normalized_default_debug = Normalizer::normalizeOptionValueForWrite($id, $default_value_debug);
                        $incoming_value_debug = $value[$id] ?? 'null';
                        $existing_value_debug = $existing[$id] ?? 'null';
                        $norm_incoming_debug = Normalizer::normalizeOptionValueForWrite($id, $value[$id] ?? null);
                        $norm_existing_debug = Normalizer::normalizeOptionValueForWrite($id, $existing[$id] ?? null);

                        Logger::log('fusion_options:DEBUG_CHECK', [
                            'id' => $id,
                            'incoming' => $incoming_value_debug,
                            'existing' => $existing_value_debug,
                            'default' => $default_value_debug,
                            'norm_incoming' => $norm_incoming_debug,
                            'norm_existing' => $norm_existing_debug,
                            'norm_default' => $normalized_default_debug,
                        ]);
                    }

                    $opt_def = Discovery::getGloryOptionDefinition($id);
                    if (!$opt_def) {
                        continue;
                    }

                    $default_value = $opt_def['valorDefault'] ?? null;
                    if ($default_value === null) {
                        continue;
                    }

                    $normalized_default = Normalizer::normalizeOptionValueForWrite($id, $default_value);

                    $incoming_value = $value[$id] ?? null;
                    $existing_value = $existing[$id] ?? null;

                    $repo_raw = null;
                    $repo_exists = false;
                    try {
                        $repo_tmp = OpcionRepository::get($id);
                        if ($repo_tmp !== OpcionRepository::getCentinela()) {
                            $repo_raw = $repo_tmp;
                            $repo_exists = true;
                        }
                    } catch (\Throwable $t) {
                    }

                    if ($incoming_value !== null && $existing_value !== null) {
                        $norm_incoming = Normalizer::normalizeOptionValueForWrite($id, $incoming_value);
                        $norm_existing = Normalizer::normalizeOptionValueForWrite($id, $existing_value);

                        if (!$is_user_save) {
                            if ($norm_incoming == $normalized_default && $norm_existing != $normalized_default) {
                                $value[$id] = $existing_value;
                                $preserved[$id] = ['kept' => $existing_value, 'incoming' => $incoming_value];
                            }
                        }
                    } elseif ($incoming_value === null && $existing_value !== null) {
                        $value[$id] = $existing_value;
                        $preserved[$id] = ['kept' => $existing_value, 'incoming' => 'null (missing)'];
                    }

                    if (!$is_user_save && $repo_exists) {
                        $norm_default = $normalized_default;
                        $norm_repo = Normalizer::normalizeOptionValueForWrite($id, $repo_raw);
                        $norm_incoming2 = ($incoming_value === null) ? null : Normalizer::normalizeOptionValueForWrite($id, $incoming_value);

                        if ($incoming_value === null || ($norm_incoming2 === $norm_default && $norm_repo !== $norm_default)) {
                            $value[$id] = $repo_raw;
                            $preserved[$id] = ['kept' => $repo_raw, 'reason' => 'repo_hydrate'];
                        }
                    }
                }

                if (!empty($preserved)) {
                    Logger::log('fusion_options:preserved_existing_values', ['keys' => $preserved]);
                }

                $synced = [];
                foreach ($ids as $id) {
                    if (!array_key_exists($id, $value)) {
                        continue;
                    }
                    try {
                        $finalVal = $value[$id];
                        $writeVal = Normalizer::normalizeOptionValueForWrite($id, $finalVal);
                        $currentRepo = null;
                        $hasCurrent = false;
                        try {
                            $tmp = OpcionRepository::get($id);
                            if ($tmp !== OpcionRepository::getCentinela()) {
                                $currentRepo = Normalizer::normalizeOptionValueForWrite($id, $tmp);
                                $hasCurrent = true;
                            }
                        } catch (\Throwable $t) {}

                        if (!$hasCurrent || $currentRepo !== $writeVal) {
                            OpcionRepository::save($id, $writeVal);
                            $synced[$id] = $writeVal;
                        }
                    } catch (\Throwable $t) {
                        Logger::log('fusion_options:sync_error', ['id' => (string) $id, 'err' => $t->getMessage()]);
                    }
                }
                if (!empty($synced)) {
                    if (class_exists(OpcionManager::class) && method_exists(OpcionManager::class, 'clearCache')) {
                        OpcionManager::clearCache();
                    }
                    Logger::log('fusion_options:synced_to_glory_repo', ['keys' => $synced]);
                }
            }

            $changed = [];
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (strpos((string) $k, 'glory_') === 0) {
                        $old = is_array($old_value) && array_key_exists($k, $old_value) ? $old_value[$k] : null;
                        if ($old !== $v) {
                            $changed[$k] = ['old' => $old, 'new' => $v];
                        }
                    }
                }
            }
            if (!empty($changed)) {
                Logger::log('fusion_options:update', ['changed' => $changed]);
            } else {
                Logger::log('fusion_options:update_no_changes_glory');
            }
        } catch (\Throwable $t) {
            Logger::log('fusion_options:update_error', ['err' => $t->getMessage()]);
        }
        return $value;
    }

    /**
     * Post-guardado de Avada: sincroniza únicamente claves glory_* hacia el repositorio Glory.
     */
    public static function handleFusionOptionsSaved(array $data, array $changed): void
    {
        try {
            $ids = Discovery::discoverOptionIds();
            if (empty($ids)) {
                $ids = Discovery::getFallbackOptionIds();
            }
            $synced = [];
            foreach ($ids as $id) {
                if (!array_key_exists($id, $data)) {
                    continue;
                }
                try {
                    $writeVal = Normalizer::normalizeOptionValueForWrite($id, $data[$id]);
                    $current = null;
                    $hasCurrent = false;
                    try {
                        $tmp = OpcionRepository::get($id);
                        if ($tmp !== OpcionRepository::getCentinela()) {
                            $current = Normalizer::normalizeOptionValueForWrite($id, $tmp);
                            $hasCurrent = true;
                        }
                    } catch (\Throwable $t) {}
                    if (!$hasCurrent || $current !== $writeVal) {
                        OpcionRepository::save($id, $writeVal);
                        $synced[$id] = $writeVal;
                    }
                } catch (\Throwable $t) {
                }
            }
            if (!empty($synced)) {
                if (class_exists(OpcionManager::class) && method_exists(OpcionManager::class, 'clearCache')) {
                    OpcionManager::clearCache();
                }
                Logger::log('fusion_options:post_saved_synced', ['keys' => $synced]);
            }
        } catch (\Throwable $t) {
            Logger::log('fusion_options:post_saved_error', ['err' => $t->getMessage()]);
        }
    }
}


