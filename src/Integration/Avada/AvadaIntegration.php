<?php

namespace Glory\Integration\Avada;

class AvadaIntegration
{
    public static function register(): void
    {
        // Remueve la pestaña/sección "Portfolio" del panel de opciones globales de Avada.
        add_filter('avada_options_sections', [self::class, 'filterRemovePortfolioSection']);

        // Inyecta la sección "Glory" y puentea lectura/escritura con Avada Options.
        if (class_exists(AvadaOptionsBridge::class)) {
            AvadaOptionsBridge::register();
        }

        if (class_exists(AvadaElementRegistrar::class)) {
            AvadaElementRegistrar::register();
        }
        if (class_exists(AvadaTemplateRegistrar::class)) {
            AvadaTemplateRegistrar::register();
        }
        if (class_exists(AvadaFontsIntegration::class)) {
            AvadaFontsIntegration::register();
        }
    }

    // Delegaciones al registrador de elementos/shortcode
    public static function registerElement(): void
    {
        if (class_exists(AvadaElementRegistrar::class) && method_exists(AvadaElementRegistrar::class, 'registerElement')) {
            AvadaElementRegistrar::registerElement();
        }
    }

    public static function ensureShortcode(): void
    {
        if (class_exists(AvadaElementRegistrar::class) && method_exists(AvadaElementRegistrar::class, 'ensureShortcode')) {
            AvadaElementRegistrar::ensureShortcode();
        }
    }

    // Delegaciones al registrador de plantillas
    public static function registerTemplates(): void
    {
        if (class_exists(AvadaTemplateRegistrar::class) && method_exists(AvadaTemplateRegistrar::class, 'registerTemplates')) {
            AvadaTemplateRegistrar::registerTemplates();
        }
    }

    // Delegaciones a la integración de fuentes
    public static function registerFontsIntegration(): void
    {
        if (class_exists(AvadaFontsIntegration::class) && method_exists(AvadaFontsIntegration::class, 'register')) {
            AvadaFontsIntegration::register();
        }
    }

    public static function filterAddCustomFontGroups($fontGroups)
    {
        if (class_exists(AvadaFontsIntegration::class) && method_exists(AvadaFontsIntegration::class, 'filterAddCustomFontGroups')) {
            return AvadaFontsIntegration::filterAddCustomFontGroups($fontGroups);
        }
        return $fontGroups;
    }

    public static function ensureCustomFontsPersisted(): void
    {
        if (class_exists(AvadaFontsIntegration::class) && method_exists(AvadaFontsIntegration::class, 'ensureCustomFontsPersisted')) {
            AvadaFontsIntegration::ensureCustomFontsPersisted();
        }
    }

    public static function filterInjectCustomFontsOption($options)
    {
        if (class_exists(AvadaFontsIntegration::class) && method_exists(AvadaFontsIntegration::class, 'filterInjectCustomFontsOption')) {
            return AvadaFontsIntegration::filterInjectCustomFontsOption($options);
        }
        return $options;
    }

    public static function filterAppendCustomFontsCss($css)
    {
        if (class_exists(AvadaFontsIntegration::class) && method_exists(AvadaFontsIntegration::class, 'filterAppendCustomFontsCss')) {
            return AvadaFontsIntegration::filterAppendCustomFontsCss($css);
        }
        return $css;
    }

    public static function enqueueInlineFontsCss(): void
    {
        if (class_exists(AvadaFontsIntegration::class) && method_exists(AvadaFontsIntegration::class, 'enqueueInlineFontsCss')) {
            AvadaFontsIntegration::enqueueInlineFontsCss();
        }
    }

    // Delegaciones a utilidades de fuentes
    public static function buildFontFaceCss(array $fonts): string
    {
        if (class_exists(AvadaFontsUtils::class) && method_exists(AvadaFontsUtils::class, 'buildFontFaceCss')) {
            return AvadaFontsUtils::buildFontFaceCss($fonts);
        }
        return '';
    }

    public static function discoverFontFamilies(): array
    {
        if (class_exists(AvadaFontsUtils::class) && method_exists(AvadaFontsUtils::class, 'discoverFontFamilies')) {
            return AvadaFontsUtils::discoverFontFamilies();
        }
        return [];
    }

    public static function discoverFonts(): array
    {
        if (class_exists(AvadaFontsUtils::class) && method_exists(AvadaFontsUtils::class, 'discoverFonts')) {
            return AvadaFontsUtils::discoverFonts();
        }
        return [];
    }

    /**
     * Filtro para eliminar la sección "Portfolio" del panel de opciones de Avada.
     *
     * @param array $sections
     * @return array
     */
    public static function filterRemovePortfolioSection(array $sections): array
    {
        if (isset($sections['portfolio'])) {
            unset($sections['portfolio']);
        }
        return $sections;
    }
}


