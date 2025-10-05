<?php

namespace Glory\Integration\Avada\Elements\GloryContentRender;

class GloryContentRenderRegistrar
{
    public static function register(): void
    {
        if ( ! function_exists('add_action') ) {
            return;
        }
        add_action('fusion_builder_before_init', [self::class, 'registerElement']);
    }

    public static function registerElement(): void
    {
        if ( ! function_exists('fusion_builder_map') ) {
            return; 
        }

        if ( ! class_exists('FusionSC_GloryContentRender') ) {
            $elementPath = get_template_directory() . '/Glory/src/Integration/Avada/Elements/GloryContentRender/FusionSC_GloryContentRender.php';
            $childPath   = get_stylesheet_directory() . '/Glory/src/Integration/Avada/Elements/GloryContentRender/FusionSC_GloryContentRender.php';
            if ( is_readable($childPath) ) {
                require_once $childPath;
            } elseif ( is_readable($elementPath) ) {
                require_once $elementPath;
            }
        }

        $params = GloryContentRenderParams::all();

        if ( function_exists('fusion_builder_frontend_data') ) {
            fusion_builder_map(
                fusion_builder_frontend_data(
                    'FusionSC_GloryContentRender',
                    [ 'name' => 'Glory Content Render', 'shortcode' => 'glory_content_render', 'icon' => 'fusiona-blog', 'params' => $params ]
                )
            );
        } else {
            fusion_builder_map([ 'name' => 'Glory Content Render', 'shortcode' => 'glory_content_render', 'icon' => 'fusiona-blog', 'params' => $params ]);
        }
    }
}






