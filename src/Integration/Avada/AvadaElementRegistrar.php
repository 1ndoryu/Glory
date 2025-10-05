<?php

namespace Glory\Integration\Avada;

/**
 * Orquestador para el registro de elementos de Avada.
 */
class AvadaElementRegistrar
{
    public static function register(): void
    {
        if ( ! function_exists('add_action') ) {
            return;
        }
        $gloryCrRegistrar = '\\Glory\\Integration\\Avada\\Elements\\GloryContentRender\\GloryContentRenderRegistrar';
        if ( class_exists($gloryCrRegistrar) ) {
            $gloryCrRegistrar::register();
        }
        $gloryImgRegistrar = '\\Glory\\Integration\\Avada\\Elements\\GloryImage\\GloryImageRegistrar';
        if ( class_exists($gloryImgRegistrar) ) {
            $gloryImgRegistrar::register();
        }
        $gloryGalleryRegistrar = '\\Glory\\Integration\\Avada\\Elements\\GloryGallery\\GloryGalleryRegistrar';
        if ( class_exists($gloryGalleryRegistrar) ) {
            $gloryGalleryRegistrar::register();
        }
        // Asegura registro de shortcodes en frontend
        add_action('init', [self::class, 'registerShortcodes']);
    }

    public static function registerShortcodes(): void
    {
        // Cargar elemento principal para que el shortcode quede disponible en frontend
        $elementPath = get_template_directory() . '/Glory/src/Integration/Avada/Elements/GloryContentRender/FusionSC_GloryContentRender.php';
        $childPath   = get_stylesheet_directory() . '/Glory/src/Integration/Avada/Elements/GloryContentRender/FusionSC_GloryContentRender.php';
        if ( is_readable($childPath) ) {
            require_once $childPath;
        } elseif ( is_readable($elementPath) ) {
            require_once $elementPath;
        }
    }
}
