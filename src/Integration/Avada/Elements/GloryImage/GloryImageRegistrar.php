<?php

namespace Glory\Integration\Avada\Elements\GloryImage;

class GloryImageRegistrar
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

        if ( ! class_exists('FusionSC_GloryImage') ) {
            $elementPath = get_template_directory() . '/Glory/src/Integration/Avada/Elements/GloryImage/FusionSC_GloryImage.php';
            $childPath   = get_stylesheet_directory() . '/Glory/src/Integration/Avada/Elements/GloryImage/FusionSC_GloryImage.php';
            if ( is_readable($childPath) ) {
                require_once $childPath;
            } elseif ( is_readable($elementPath) ) {
                require_once $elementPath;
            }
        }

        $params = [
            // Imagen
            [ 'type' => 'upload', 'heading' => __('Imagen', 'glory-ab'), 'param_name' => 'image', 'value' => '', 'dynamic_data' => true, 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('ID de imagen', 'glory-ab'), 'param_name' => 'image_id', 'value' => '', 'hidden' => true, 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'select', 'heading' => __('Tamaño', 'glory-ab'), 'param_name' => 'image_size', 'value' => [ 'thumbnail'=>'thumbnail', 'medium'=>'medium', 'medium_large'=>'medium_large', 'large'=>'large', 'full'=>'full' ], 'default' => 'full', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Alt', 'glory-ab'), 'param_name' => 'alt', 'value' => '', 'group' => __('General', 'glory-ab') ],

            // Diseño
            [ 'type' => 'radio_button_set', 'heading' => __('Alineación', 'glory-ab'), 'param_name' => 'align', 'default' => 'none', 'value' => [ 'none' => __('Flujo de texto','glory-ab'), 'left' => __('Izquierda','glory-ab'), 'center' => __('Centro','glory-ab'), 'right' => __('Derecha','glory-ab') ], 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Aspect ratio', 'glory-ab'), 'param_name' => 'aspect_ratio', 'default' => '1 / 1', 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Object fit', 'glory-ab'), 'param_name' => 'object_fit', 'default' => 'cover', 'value' => [ 'cover'=>'cover', 'contain'=>'contain' ], 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Min width', 'glory-ab'), 'param_name' => 'min_width', 'default' => '', 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'full_width', 'value' => 'yes', 'operator' => '!=' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Height', 'glory-ab'), 'param_name' => 'height', 'default' => '', 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Ancho completo (100%)', 'glory-ab'), 'param_name' => 'full_width', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Diseño', 'glory-ab') ],

            // Título
            [ 'type' => 'radio_button_set', 'heading' => __('Mostrar título', 'glory-ab'), 'param_name' => 'show_title', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Título', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Texto del título', 'glory-ab'), 'param_name' => 'title_text', 'default' => '', 'group' => __('Título', 'glory-ab'), 'dependency' => [ [ 'element' => 'show_title', 'value' => 'yes', 'operator' => '==' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Fuente (font-family)', 'glory-ab'), 'param_name' => 'title_font_family', 'default' => '', 'group' => __('Título', 'glory-ab'), 'dependency' => [ [ 'element' => 'show_title', 'value' => 'yes', 'operator' => '==' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Tamaño de fuente', 'glory-ab'), 'param_name' => 'title_font_size', 'default' => '', 'group' => __('Título', 'glory-ab'), 'dependency' => [ [ 'element' => 'show_title', 'value' => 'yes', 'operator' => '==' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Peso (font-weight)', 'glory-ab'), 'param_name' => 'title_font_weight', 'default' => '', 'group' => __('Título', 'glory-ab'), 'dependency' => [ [ 'element' => 'show_title', 'value' => 'yes', 'operator' => '==' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Ancho máximo del título', 'glory-ab'), 'param_name' => 'title_max_width', 'default' => '', 'description' => __('Ej.: 200px, 30ch, 80%', 'glory-ab'), 'group' => __('Título', 'glory-ab'), 'dependency' => [ [ 'element' => 'show_title', 'value' => 'yes', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Mostrar título solo en hover', 'glory-ab'), 'param_name' => 'title_show_on_hover', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Título', 'glory-ab'), 'dependency' => [ [ 'element' => 'show_title', 'value' => 'yes', 'operator' => '==' ] ] ],
        ];

        if ( function_exists('fusion_builder_frontend_data') ) {
            fusion_builder_map(
                fusion_builder_frontend_data(
                    'FusionSC_GloryImage',
                    [ 'name' => __('Glory Imagen','glory-ab'), 'shortcode' => 'glory_image', 'icon' => 'fusiona-image', 'params' => $params ]
                )
            );
        } else {
            fusion_builder_map([ 'name' => __('Glory Imagen','glory-ab'), 'shortcode' => 'glory_image', 'icon' => 'fusiona-image', 'params' => $params ]);
        }
    }

    public static function ensureShortcode(): void
    {
        if ( function_exists('shortcode_exists') && shortcode_exists('glory_image') ) {
            return;
        }

        if ( ! class_exists('FusionSC_GloryImage') ) {
            $elementPath = get_template_directory() . '/Glory/src/Integration/Avada/Elements/GloryImage/FusionSC_GloryImage.php';
            $childPath   = get_stylesheet_directory() . '/Glory/src/Integration/Avada/Elements/GloryImage/FusionSC_GloryImage.php';
            if ( is_readable($childPath) ) {
                require_once $childPath;
            } elseif ( is_readable($elementPath) ) {
                require_once $elementPath;
            }
        }

        if ( ! shortcode_exists('glory_image') && class_exists('FusionSC_GloryImage') ) {
            // El constructor de FusionSC_GloryImage agrega el shortcode automáticamente.
            new \FusionSC_GloryImage();
        }
    }
}


