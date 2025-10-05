<?php

namespace Glory\Integration\Avada;

class AvadaFontsIntegration
{
    public static function register(): void
    {
        if ( ! function_exists('add_filter') ) {
            return;
        }

        add_filter('fusion_redux_typography_font_groups', [self::class, 'filterAddCustomFontGroups'], 999);
        add_filter('avada_dynamic_css', [self::class, 'filterAppendCustomFontsCss'], 999);
        add_filter('avada_dynamic_css', [self::class, 'filterSanitizeFontVarBackup'], 2000);
        add_filter('fusion_dynamic_css', [self::class, 'filterSanitizeFontVarBackup'], 2000);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueInlineFontsCss'], 1);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueInlineFontsCss'], 1);
        add_action('enqueue_block_editor_assets', [self::class, 'enqueueInlineFontsCss'], 1);

        // Persistencia segura tras guardar opciones de Avada y en admin init.
        add_action('fusionredux/options/fusion_options/saved', [self::class, 'onOptionsSaved'], 10, 2);
        add_action('admin_init', [self::class, 'ensureCustomFontsPersisted'], 20);
        add_action('admin_init', [self::class, 'sanitizeTypographyBackups'], 25);

        // Evitar escribir en fusion_options automáticamente en admin_init para no sobrescribir valores.
        // Si es necesario persistir nombres de fuentes, hacerlo en un hook post-guardado específico.
    }

    public static function filterAddCustomFontGroups($fontGroups)
    {
        try {
            $families = self::buildAvailableFamilyNames();
            if (empty($families)) {
                return $fontGroups;
            }

            if (!isset($fontGroups['customfonts'])) {
                $fontGroups['customfonts'] = [ 'text' => __('Custom Fonts', 'Avada'), 'children' => [] ];
            }

            $existing = [];
            foreach ((array) ($fontGroups['customfonts']['children'] ?? []) as $child) {
                if (isset($child['id'])) {
                    $existing[(string) $child['id']] = true;
                }
            }

            foreach ($families as $family) {
                $id = (string) $family;
                if (isset($existing[$id])) {
                    continue;
                }
                $fontGroups['customfonts']['children'][] = [
                    'id'          => $id,
                    'text'        => $id,
                    'data-google' => 'false',
                ];
            }

            return $fontGroups;
        } catch (\Throwable $t) {
            return $fontGroups;
        }
    }

    public static function ensureCustomFontsPersisted(): void
    {
        try {
            $families = self::buildAvailableFamilyNames();
            if (empty($families)) {
                return;
            }

            $optName = null;
            if (class_exists('Fusion_Settings') && method_exists('Fusion_Settings', 'get_option_name')) {
                $optName = (string) \Fusion_Settings::get_option_name();
            } elseif (class_exists('Avada') && method_exists('Avada', 'get_option_name')) {
                $optName = (string) \Avada::get_option_name();
            } else {
                $optName = 'fusion_options';
            }

            $options = get_option($optName, []);
            if (!is_array($options)) { $options = []; }
            if (!isset($options['custom_fonts']) || !is_array($options['custom_fonts'])) { $options['custom_fonts'] = []; }
            if (!isset($options['custom_fonts']['name']) || !is_array($options['custom_fonts']['name'])) { $options['custom_fonts']['name'] = []; }

            $existingNames = array_filter(array_map('strval', (array) $options['custom_fonts']['name']));
            $changed = false;
            foreach ($families as $family) {
                if (!in_array($family, $existingNames, true)) {
                    $existingNames[] = $family;
                    $changed = true;
                }
            }
            if ($changed) {
                $options['custom_fonts']['name'] = $existingNames;
                update_option($optName, $options, false);
            }
        } catch (\Throwable $t) {
        }
    }

    public static function onOptionsSaved($data, $changed_values): void
    {
        try {
            self::ensureCustomFontsPersisted();
            self::sanitizeTypographyBackups();
        } catch (\Throwable $t) {
        }
    }

    public static function filterInjectCustomFontsOption($options)
    {
        try {
            $families = self::buildAvailableFamilyNames();
            if (empty($families)) {
                return $options;
            }

            if (!is_array($options)) {
                $options = [];
            }
            if (!isset($options['custom_fonts']) || !is_array($options['custom_fonts'])) {
                $options['custom_fonts'] = [];
            }
            if (!isset($options['custom_fonts']['name']) || !is_array($options['custom_fonts']['name'])) {
                $options['custom_fonts']['name'] = [];
            }

            $existingNames = array_filter(array_map('strval', (array) $options['custom_fonts']['name']));
            foreach ($families as $family) {
                if (!in_array($family, $existingNames, true)) {
                    $existingNames[] = $family;
                }
            }
            $options['custom_fonts']['name'] = $existingNames;
            return $options;
        } catch (\Throwable $t) {
            return $options;
        }
    }

    public static function filterAppendCustomFontsCss($css)
    {
        try {
            $fonts = AvadaFontsUtils::discoverFonts();
            if (empty($fonts)) {
                return $css;
            }

            $fontFace = AvadaFontsUtils::buildFontFaceCss($fonts);
            return $fontFace . $css;
        } catch (\Throwable $t) {
            return $css;
        }
    }

    /**
     * Elimina ", 1" al final de variables --*-font-family, causado por 'font-backup' booleano.
     */
    public static function filterSanitizeFontVarBackup($css)
    {
        try {
            // Coincide variables que terminan en ", 1;" y quita la parte ", 1".
            $css = preg_replace('/(--[^:]*-font-family\s*:\s*)([^;]*?),(\s*1)(\s*;)/i', '$1$2$4', (string) $css);
            return $css;
        } catch (\Throwable $t) {
            return $css;
        }
    }

    public static function enqueueInlineFontsCss(): void
    {
        try {
            $fonts = AvadaFontsUtils::discoverFonts();
            if (empty($fonts)) {
                return;
            }
            $css = AvadaFontsUtils::buildFontFaceCss($fonts);
            if ('' === trim($css)) {
                return;
            }
            if ( ! wp_style_is('glory-auto-fonts', 'registered') ) {
                wp_register_style('glory-auto-fonts', false, [], null);
            }
            wp_enqueue_style('glory-auto-fonts');
            wp_add_inline_style('glory-auto-fonts', $css);
        } catch (\Throwable $t) {
        }
    }

    private static function buildAvailableFamilyNames(): array
    {
        // Familias base detectadas.
        $map = AvadaFontsUtils::discoverFonts(); // array<string, array<int,string>>
        $families = [];

        foreach ($map as $family => $files) {
            if (empty($files)) { continue; }
            // Agrupar por peso y exponer familias derivadas por peso, ej: founders-grotesk-500, -700.
            $byWeight = [];
            foreach ($files as $url) {
                $w = AvadaFontsUtils::inferWeightFromFilename($url);
                $byWeight[$w] = true;
            }

            // Siempre incluir la familia base por compatibilidad.
            $families[] = $family;
            foreach (array_keys($byWeight) as $w) {
                $suffix = is_numeric($w) ? (string) $w : 'normal';
                $families[] = $family . '-' . $suffix;
            }
        }

        // Únicos y ordenados.
        $families = array_values(array_unique($families));
        sort($families);
        return $families;
    }

    /**
     * Evita que 'font-backup' sea booleano (true/1) para no generar ", 1" en font-family.
     */
    public static function sanitizeTypographyBackups(): void
    {
        try {
            $optName = null;
            if (class_exists('Fusion_Settings') && method_exists('Fusion_Settings', 'get_option_name')) {
                $optName = (string) \Fusion_Settings::get_option_name();
            } elseif (class_exists('Avada') && method_exists('Avada', 'get_option_name')) {
                $optName = (string) \Avada::get_option_name();
            } else {
                $optName = 'fusion_options';
            }

            $options = get_option($optName, []);
            if (!is_array($options) || empty($options)) { return; }

            $fields = [
                'body_typography',
                'h1_typography','h2_typography','h3_typography','h4_typography','h5_typography','h6_typography',
                'post_title_typography','post_titles_extras_typography',
            ];
            $changed = false;

            foreach ($fields as $field) {
                if (isset($options[$field]) && is_array($options[$field]) && array_key_exists('font-backup', $options[$field])) {
                    $val = $options[$field]['font-backup'];
                    if (true === $val || 1 === $val || '1' === $val || 'true' === $val) {
                        $options[$field]['font-backup'] = '';
                        $changed = true;
                    }
                }
            }

            if ($changed) {
                update_option($optName, $options, false);
            }
        } catch (\Throwable $t) {
        }
    }
}


