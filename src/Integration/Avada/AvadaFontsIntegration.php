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
        add_action('wp_enqueue_scripts', [self::class, 'enqueueInlineFontsCss'], 1);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueInlineFontsCss'], 1);
        add_action('enqueue_block_editor_assets', [self::class, 'enqueueInlineFontsCss'], 1);

        $optionNames = [];
        if ( class_exists('\\Avada') && method_exists('\\Avada', 'get_option_name') ) {
            $optionNames[] = \Avada::get_option_name();
        }
        $optionNames[] = 'fusion_options';
        $optionNames[] = 'avada_options';
        $optionNames = array_values(array_unique(array_filter($optionNames)));
        foreach ($optionNames as $opt) {
            add_filter('option_' . $opt, [self::class, 'filterInjectCustomFontsOption']);
            add_filter('pre_option_' . $opt, [self::class, 'filterInjectCustomFontsOption']);
        }

        add_action('admin_init', [self::class, 'ensureCustomFontsPersisted'], 20);
    }

    public static function filterAddCustomFontGroups($fontGroups)
    {
        try {
            $families = AvadaFontsUtils::discoverFontFamilies();
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
            $families = AvadaFontsUtils::discoverFontFamilies();
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

    public static function filterInjectCustomFontsOption($options)
    {
        try {
            $families = AvadaFontsUtils::discoverFontFamilies();
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
}


