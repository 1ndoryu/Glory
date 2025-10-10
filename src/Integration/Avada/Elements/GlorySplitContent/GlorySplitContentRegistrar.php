<?php

namespace Glory\Integration\Avada\Elements\GlorySplitContent;

class GlorySplitContentRegistrar
{
    public static function register(): void
    {
        if ( ! function_exists('add_action') ) {
            return;
        }
        add_action('fusion_builder_before_init', [self::class, 'registerElement']);
        add_action('init', [self::class, 'ensureShortcode']);
    }

    public static function registerElement(): void
    {
        if ( ! function_exists('fusion_builder_map') ) {
            return;
        }

        if ( ! class_exists('FusionSC_GlorySplitContent') ) {
            $elementPath = get_template_directory() . '/Glory/src/Integration/Avada/Elements/GlorySplitContent/FusionSC_GlorySplitContent.php';
            $childPath   = get_stylesheet_directory() . '/Glory/src/Integration/Avada/Elements/GlorySplitContent/FusionSC_GlorySplitContent.php';
            if ( is_readable($childPath) ) {
                require_once $childPath;
            } elseif ( is_readable($elementPath) ) {
                require_once $elementPath;
            }
        }

        $params = GlorySplitContentParams::all();

        if ( function_exists('fusion_builder_frontend_data') ) {
            fusion_builder_map(
                fusion_builder_frontend_data(
                    'FusionSC_GlorySplitContent',
                    [ 'name' => __('Glory Split Content','glory-ab'), 'shortcode' => 'glory_split_content', 'icon' => 'fusiona-list', 'params' => $params ]
                )
            );
        } else {
            fusion_builder_map([ 'name' => __('Glory Split Content','glory-ab'), 'shortcode' => 'glory_split_content', 'icon' => 'fusiona-list', 'params' => $params ]);
        }
    }

    public static function ensureShortcode(): void
    {
        if ( function_exists('shortcode_exists') && shortcode_exists('glory_split_content') ) {
            return;
        }

        if ( ! class_exists('FusionSC_GlorySplitContent') ) {
            $elementPath = get_template_directory() . '/Glory/src/Integration/Avada/Elements/GlorySplitContent/FusionSC_GlorySplitContent.php';
            $childPath   = get_stylesheet_directory() . '/Glory/src/Integration/Avada/Elements/GlorySplitContent/FusionSC_GlorySplitContent.php';
            if ( is_readable($childPath) ) {
                require_once $childPath;
            } elseif ( is_readable($elementPath) ) {
                require_once $elementPath;
            }
        }

        if ( ! shortcode_exists('glory_split_content') && class_exists('FusionSC_GlorySplitContent') ) {
            new \FusionSC_GlorySplitContent();
        }
    }
}


