<?php

namespace Glory\Integration\Avada\Elements\GlorySplitContent;

class GlorySplitContentParams
{
    public static function all(): array
    {
        return [
            // General
            [ 'type' => 'select', 'heading' => __('Content type', 'glory-ab'), 'param_name' => 'post_type', 'default' => 'post', 'value' => self::discoverPublicPostTypes(), 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'range', 'heading' => __('Posts per page', 'glory-ab'), 'param_name' => 'number_of_posts', 'default' => 10, 'min' => 1, 'max' => 100, 'step' => 1, 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textarea', 'heading' => __('Query args (JSON)', 'glory-ab'), 'param_name' => 'query_args', 'default' => '', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Specific IDs (CSV)', 'glory-ab'), 'param_name' => 'include_post_ids', 'default' => '', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'multiple_select', 'heading' => __('Select posts', 'glory-ab'), 'param_name' => 'include_post_ids_select', 'default' => [], 'value' => self::discoverRecentPosts(), 'placeholder' => __('Search by title...', 'glory-ab'), 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Auto open first'), 'param_name' => 'auto_open_first_item', 'default' => 'no', 'value' => [ 'yes' => __('Yes','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Container height'), 'param_name' => 'height', 'default' => '100%', 'description' => __('CSS size, e.g. 100%, 400px, 60vh', 'glory-ab'), 'group' => __('General', 'glory-ab') ],
            // Container padding (match GBN)
            [ 'type' => 'textfield', 'heading' => __('Padding top'), 'param_name' => 'padding_top', 'default' => '30px', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Padding bottom'), 'param_name' => 'padding_bottom', 'default' => '30px', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Padding left'), 'param_name' => 'padding_left', 'default' => '20px', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Padding right'), 'param_name' => 'padding_right', 'default' => '20px', 'group' => __('General', 'glory-ab') ],

            // List
            [ 'type' => 'radio_button_set', 'heading' => __('List direction', 'glory-ab'), 'param_name' => 'list_direction', 'default' => 'vertical', 'value' => [ 'vertical' => __('Vertical','glory-ab'), 'horizontal' => __('Horizontal','glory-ab') ], 'group' => __('List', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('List item spacing'), 'param_name' => 'list_item_spacing', 'default' => '12px', 'group' => __('List', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Titles panel width'), 'param_name' => 'list_panel_width', 'default' => '30%', 'description' => __('E.g.: 30%, 260px', 'glory-ab'), 'group' => __('List', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Enable list scroll', 'glory-ab'), 'param_name' => 'list_scroll_enabled', 'default' => 'yes', 'value' => [ 'yes' => __('Yes','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('List', 'glory-ab') ],
            // List padding
            [ 'type' => 'textfield', 'heading' => __('List padding top'), 'param_name' => 'list_padding_top', 'default' => '0px', 'group' => __('List', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('List padding bottom'), 'param_name' => 'list_padding_bottom', 'default' => '0px', 'group' => __('List', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('List padding left'), 'param_name' => 'list_padding_left', 'default' => '0px', 'group' => __('List', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('List padding right'), 'param_name' => 'list_padding_right', 'default' => '0px', 'group' => __('List', 'glory-ab') ],

            // Titles typography (grouped)
            [
                'type'             => 'typography',
                'remove_from_atts' => true,
                'global'           => true,
                'heading'          => __('Title typography', 'glory-ab'),
                'description'      => __('Controls the title text typography. Leave empty to use global.', 'glory-ab'),
                'param_name'       => 'titles_typography',
                'group'            => __('Titles', 'glory-ab'),
                'choices'          => [
                    'font-family'    => 'title_font_family',
                    'variant'        => 'title_font_variant',
                    'font-size'      => 'title_font_size',
                    'line-height'    => 'title_line_height',
                    'letter-spacing' => 'title_letter_spacing',
                    'text-transform' => 'title_text_transform',
                    'color'          => 'title_color',
                ],
                'default'          => [
                    'font-family'    => '',
                    'variant'        => '',
                    'font-size'      => '',
                    'line-height'    => '',
                    'letter-spacing' => '',
                    'text-transform' => 'none',
                    'color'          => '',
                ],
            ],

            // Content (detail)
            [ 'type' => 'radio_button_set', 'heading' => __('Enable content scroll'), 'param_name' => 'content_scroll_enabled', 'default' => 'yes', 'value' => [ 'yes' => __('Yes','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Content', 'glory-ab') ],
            // Content typography (grouped)
            [
                'type'             => 'typography',
                'remove_from_atts' => true,
                'global'           => true,
                'heading'          => __('Content typography', 'glory-ab'),
                'description'      => __('Controls the content text typography. Leave empty to use global.', 'glory-ab'),
                'param_name'       => 'content_typography',
                'group'            => __('Content', 'glory-ab'),
                'choices'          => [
                    'font-family'    => 'content_font_family',
                    'variant'        => 'content_font_variant',
                    'font-size'      => 'content_font_size',
                    'line-height'    => 'content_line_height',
                    'letter-spacing' => 'content_letter_spacing',
                    'text-transform' => 'content_text_transform',
                    'color'          => 'content_color',
                ],
                'default'          => [
                    'font-family'    => '',
                    'variant'        => '',
                    'font-size'      => '',
                    'line-height'    => '',
                    'letter-spacing' => '',
                    'text-transform' => 'none',
                    'color'          => '',
                ],
            ],
            // Content padding
            [ 'type' => 'textfield', 'heading' => __('Content padding top'), 'param_name' => 'content_padding_top', 'default' => '10px', 'group' => __('Content', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Content padding bottom'), 'param_name' => 'content_padding_bottom', 'default' => '10px', 'group' => __('Content', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Content padding left'), 'param_name' => 'content_padding_left', 'default' => '10px', 'group' => __('Content', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Content padding right'), 'param_name' => 'content_padding_right', 'default' => '10px', 'group' => __('Content', 'glory-ab') ],
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


