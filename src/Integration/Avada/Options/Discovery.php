<?php

namespace Glory\Integration\Avada\Options;

use Glory\Manager\OpcionManager;

final class Discovery
{
    private const FALLBACK_OPTION_IDS = [
        'glory_logo_mode',
        'glory_logo_text',
        'glory_logo_image',
        'glory_gsc_verification_code',
        'glory_ga4_measurement_id',
        'glory_custom_header_scripts',
    ];

    public static function discoverOptionIds(): array
    {
        $options = self::gatherOptionsFromOpcionManager();
        if (empty($options)) {
            return [];
        }
        $ids = [];
        foreach ($options as $opt) {
            if (isset($opt['id']) && is_string($opt['id']) && $opt['id'] !== '') {
                $ids[] = $opt['id'];
            }
        }
        return array_values(array_unique($ids));
    }

    public static function getFallbackOptionIds(): array
    {
        return self::FALLBACK_OPTION_IDS;
    }

    public static function shouldExcludeById(string $id): bool
    {
        return in_array($id, ['glory_logo_mode','glory_logo_text','glory_logo_image'], true);
    }

    public static function getOptionType(string $id): ?string
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            $opts = self::gatherOptionsFromOpcionManager();
            foreach ($opts as $opt) {
                if (!empty($opt['id']) && !empty($opt['tipo'])) {
                    $cache[(string) $opt['id']] = (string) $opt['tipo'];
                }
            }
        }
        return $cache[$id] ?? null;
    }

    public static function getGloryOptionDefinition(string $id): ?array
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            $opts = self::gatherOptionsFromOpcionManager();
            foreach ($opts as $opt) {
                if (!empty($opt['id'])) {
                    $cache[(string) $opt['id']] = $opt;
                }
            }
        }
        return $cache[$id] ?? null;
    }

    public static function gatherOptionsFromOpcionManager(): array
    {
        $options = [];
        try {
            $filtered = apply_filters('glory_opcion_manager_registry', null);
            if (is_array($filtered)) {
                $options = self::normalizeRegistry($filtered);
                if (!empty($options)) {
                    return $options;
                }
            }

            if (class_exists('Glory\\Manager\\OpcionManager')) {
                $class = 'Glory\\Manager\\OpcionManager';
                foreach (['options','all','registry','getAll'] as $method) {
                    if (is_callable([$class, $method])) {
                        $result = call_user_func([$class, $method]);
                        if (is_array($result)) {
                            $options = self::normalizeRegistry($result);
                            break;
                        }
                    }
                }
                if (empty($options)) {
                    $ref = new \ReflectionClass($class);
                    foreach (['registry','options','_options','store'] as $propName) {
                        if ($ref->hasProperty($propName)) {
                            $prop = $ref->getProperty($propName);
                            if ($prop->isStatic()) {
                                $prop->setAccessible(true);
                                $raw = $prop->getValue();
                                if (is_array($raw)) {
                                    $options = self::normalizeRegistry($raw);
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if (empty($options)) {
                $fromFiles = self::gatherOptionsFromFiles();
                if (!empty($fromFiles)) {
                    $options = $fromFiles;
                }
            }
        } catch (\Throwable $t) {
        }
        return $options;
    }

    public static function gatherOptionsFromFiles(): array
    {
        $files = [];
        try {
            $base = trailingslashit(get_template_directory());
            $files[] = $base . 'Glory/Config/options.php';
            $files[] = $base . 'App/Config/opcionesTema.php';
        } catch (\Throwable $t) {
            return [];
        }

        $found = [];
        foreach ($files as $file) {
            if (!is_readable($file)) {
                continue;
            }
            $code = @file_get_contents($file);
            if (!is_string($code) || '' === $code) {
                continue;
            }
            $code = preg_replace('#/\*.*?\*/#s', '', $code);
            $lines = preg_split('/\r\n|\r|\n/', $code);
            $buffer = '';
            foreach ($lines as $line) {
                $buffer .= $line . "\n";
            }
            if (preg_match_all("#OpcionManager::register\(\s*([\\'\"])\s*([^'\"]+)\s*\\1\s*,\s*(\[.*?\])\s*\)\s*;#s", $buffer, $m, PREG_SET_ORDER)) {
                foreach ($m as $match) {
                    $id = trim($match[2]);
                    $arrLiteral = trim($match[3]);
                    $def = self::parseArrayLiteral($arrLiteral, $id);
                    if (!empty($def)) {
                        $found[] = $def;
                    }
                }
            }
        }
        return $found;
    }

    public static function parseArrayLiteral(string $literal, string $id): array
    {
        $def = [ 'id' => $id ];

        $getString = function(string $key) use ($literal): ?string {
            $pattern = "~'" . preg_quote($key, "~") . "'\\s*=>\\s*'([^']*)'~";
            if (preg_match($pattern, $literal, $m)) { return $m[1]; }
            return null;
        };
        $getBool = function(string $key) use ($literal): ?bool {
            $pattern = "~'" . preg_quote($key, "~") . "'\\s*=>\\s*(true|false)~i";
            if (preg_match($pattern, $literal, $m)) { return strtolower($m[1]) === 'true'; }
            return null;
        };
        $getNum = function(string $key) use ($literal): ?int {
            $pattern = "~'" . preg_quote($key, "~") . "'\\s*=>\\s*(-?[0-9]+)~";
            if (preg_match($pattern, $literal, $m)) { return (int) $m[1]; }
            return null;
        };

        foreach (['tipo','etiqueta','descripcion','seccion','etiquetaSeccion','subSeccion'] as $k) {
            $v = $getString($k);
            if ($v !== null) { $def[$k] = $v; }
        }
        $vb = $getBool('valorDefault');
        if ($vb !== null) { $def['valorDefault'] = $vb; }
        else {
            $vn = $getNum('valorDefault');
            if ($vn !== null) { $def['valorDefault'] = $vn; }
            else {
                $vs = $getString('valorDefault');
                if ($vs !== null) { $def['valorDefault'] = $vs; }
            }
        }
        if (isset($def['tipo']) && $def['tipo'] === 'toggle') {
            $def['tipo'] = 'checkbox';
        }
        return $def;
    }

    public static function normalizeRegistry(array $raw): array
    {
        $normalized = [];
        foreach ($raw as $key => $def) {
            if (is_string($key) && is_array($def)) {
                $def['id'] = $key;
                $normalized[] = $def;
                continue;
            }
            if (is_array($def) && isset($def['id'])) {
                $normalized[] = $def;
            }
        }
        return $normalized;
    }
}


