<?php

namespace Glory\Integration\Avada\Elements\GlorySplitContent;

class GlorySplitContentParams
{
    public static function all(): array
    {
        return [
            // General
            [ 'type' => 'select', 'heading' => __('Tipo de contenido', 'glory-ab'), 'param_name' => 'post_type', 'default' => 'post', 'value' => self::discoverPublicPostTypes(), 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'range', 'heading' => __('Publicaciones por página', 'glory-ab'), 'param_name' => 'publicaciones_por_pagina', 'default' => 10, 'min' => 1, 'max' => 100, 'step' => 1, 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textarea', 'heading' => __('Argumentos de consulta (JSON)', 'glory-ab'), 'param_name' => 'argumentos_json', 'default' => '', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('IDs específicos (CSV)', 'glory-ab'), 'param_name' => 'post_ids', 'default' => '', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'multiple_select', 'heading' => __('Seleccionar posts', 'glory-ab'), 'param_name' => 'post_ids_select', 'default' => [], 'value' => self::discoverRecentPosts(), 'placeholder' => __('Busca por título...', 'glory-ab'), 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Auto abrir primero'), 'param_name' => 'auto_open_first', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('General', 'glory-ab') ],

            // Lista (títulos)
            [ 'type' => 'radio_button_set', 'heading' => __('Dirección de la lista'), 'param_name' => 'list_direction', 'default' => 'vertical', 'value' => [ 'vertical' => __('Vertical','glory-ab'), 'horizontal' => __('Horizontal','glory-ab') ], 'group' => __('Lista', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Separación (gap)'), 'param_name' => 'list_gap', 'default' => '12px', 'group' => __('Lista', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Ancho del panel de títulos'), 'param_name' => 'list_panel_width', 'default' => '30%', 'description' => __('Ej.: 30%, 260px', 'glory-ab'), 'group' => __('Lista', 'glory-ab') ],
            [ 'type' => 'colorpickeralpha', 'heading' => __('Color de títulos'), 'param_name' => 'titles_color', 'default' => '', 'group' => __('Lista', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Fuente títulos (font-family)'), 'param_name' => 'titles_font_family', 'default' => '', 'group' => __('Lista', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Tamaño títulos (font-size)'), 'param_name' => 'titles_font_size', 'default' => '', 'group' => __('Lista', 'glory-ab') ],

            // Contenido (detalle)
            [ 'type' => 'colorpickeralpha', 'heading' => __('Color del contenido'), 'param_name' => 'content_color', 'default' => '', 'group' => __('Contenido', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Fuente contenido (font-family)'), 'param_name' => 'content_font_family', 'default' => '', 'group' => __('Contenido', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Tamaño contenido (font-size)'), 'param_name' => 'content_font_size', 'default' => '', 'group' => __('Contenido', 'glory-ab') ],
        ];
    }

    private static function discoverPublicPostTypes(): array
    {
        $options = [ 'post' => 'post' ];
        $pts = get_post_types([ 'public' => true ], 'objects');
        if ( is_array($pts) ) {
            foreach ($pts as $pt) {
                $label = $pt->labels->singular_name ?? ($pt->label ?? $pt->name);
                $options[$pt->name] = $label;
            }
        }
        return $options;
    }

    private static function discoverRecentPosts(): array
    {
        $posts = get_posts([
            'post_type' => 'any',
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish',
            'suppress_filters' => true,
            'fields' => 'ids',
        ]);
        $options = [];
        foreach ( (array) $posts as $pid ) {
            $title = get_the_title($pid);
            $options[(string) $pid] = $title !== '' ? $title : ('#' . $pid);
        }
        return $options;
    }
}


