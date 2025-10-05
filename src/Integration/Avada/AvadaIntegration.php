<?php

namespace Glory\Integration\Avada;

use Glory\Core\GloryFeatures;

class AvadaIntegration
{
    public static function register(): void
    {
        // Respetar flag global de integración con Avada
        if (\Glory\Core\GloryFeatures::isEnabled('avadaIntegration') === false) {
            return;
        }
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
        if (class_exists(AvadaResponsiveTypographyIntegration::class)) {
            AvadaResponsiveTypographyIntegration::register();
        }
        // Habilitar Avada Builder para todos los CPT públicos automáticamente.
        if (class_exists(AvadaBuilderCptSupport::class)) {
            AvadaBuilderCptSupport::register();
        }
        if ( ! function_exists('add_action') ) {
            return;
        }
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
    }

    // Delegaciones al registrador de elementos/shortcode
    public static function registerElement(): void
    {
        $gloryCrRegistrar = '\\Glory\\Integration\\Avada\\Elements\\GloryContentRender\\GloryContentRenderRegistrar';
        if (class_exists($gloryCrRegistrar) && method_exists($gloryCrRegistrar, 'registerElement')) {
            $gloryCrRegistrar::registerElement();
        }
        $gloryImgRegistrar = '\\Glory\\Integration\\Avada\\Elements\\GloryImage\\GloryImageRegistrar';
        if (class_exists($gloryImgRegistrar) && method_exists($gloryImgRegistrar, 'registerElement')) {
            $gloryImgRegistrar::registerElement();
        }
        $gloryGalleryRegistrar = '\\Glory\\Integration\\Avada\\Elements\\GloryGallery\\GloryGalleryRegistrar';
        if (class_exists($gloryGalleryRegistrar) && method_exists($gloryGalleryRegistrar, 'registerElement')) {
            $gloryGalleryRegistrar::registerElement();
        }
    }

    public static function ensureShortcode(): void
    {
        $gloryCrRegistrar = '\\Glory\\Integration\\Avada\\Elements\\GloryContentRender\\GloryContentRenderRegistrar';
        if (class_exists($gloryCrRegistrar) && method_exists($gloryCrRegistrar, 'ensureShortcode')) {
            $gloryCrRegistrar::ensureShortcode();
        }
        $gloryImgRegistrar = '\\Glory\\Integration\\Avada\\Elements\\GloryImage\\GloryImageRegistrar';
        if (class_exists($gloryImgRegistrar) && method_exists($gloryImgRegistrar, 'ensureShortcode')) {
            $gloryImgRegistrar::ensureShortcode();
        }
        $gloryGalleryRegistrar = '\\Glory\\Integration\\Avada\\Elements\\GloryGallery\\GloryGalleryRegistrar';
        if (class_exists($gloryGalleryRegistrar) && method_exists($gloryGalleryRegistrar, 'ensureShortcode')) {
            $gloryGalleryRegistrar::ensureShortcode();
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

    public static function enqueueAssets(): void
    {
        if ( ! function_exists('wp_enqueue_script') ) {
            return;
        }
        $themeDir = get_template_directory_uri();
        $childDir = get_stylesheet_directory_uri();

        // Intentar cargar desde child-theme si existe
        $childPath = get_stylesheet_directory() . '/Glory/assets/js/glory-carousel.js';
        $themePath = get_template_directory() . '/Glory/assets/js/glory-carousel.js';
        if ( is_readable($childPath) ) {
            wp_enqueue_script('glory-carousel', $childDir . '/Glory/assets/js/glory-carousel.js', [], null, true);
        } elseif ( is_readable($themePath) ) {
            wp_enqueue_script('glory-carousel', $themeDir . '/Glory/assets/js/glory-carousel.js', [], null, true);
        }
    }
}


