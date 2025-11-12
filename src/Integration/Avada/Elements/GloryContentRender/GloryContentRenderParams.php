<?php

namespace Glory\Integration\Avada\Elements\GloryContentRender;

class GloryContentRenderParams
{
    public static function all(): array
    {
        $params = [
            [ 'type' => 'select', 'heading' => 'Content Type', 'param_name' => 'post_type', 'default' => 'post', 'value' => self::discoverPublicPostTypes(), 'group' => 'General' ],
            [ 'type' => 'select', 'heading' => 'Content Template', 'param_name' => 'template_id', 'default' => '__default', 'value' => self::discoverTemplates(), 'group' => 'General' ],
            [ 'type' => 'radio_button_set', 'heading' => 'Use ContentRender', 'param_name' => 'usar_content_render', 'default' => 'yes', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'General' ],
            [ 'type' => 'range', 'heading' => 'Posts per page', 'param_name' => 'publicaciones_por_pagina', 'default' => 10, 'min' => 1, 'max' => 100, 'step' => 1, 'group' => 'General' ],
            [ 'type' => 'textfield', 'heading' => 'Container class', 'param_name' => 'clase_contenedor', 'default' => 'glory-content-list', 'group' => 'General' ],
            [ 'type' => 'textfield', 'heading' => 'Item class', 'param_name' => 'clase_item', 'default' => 'glory-content-item', 'group' => 'General' ],
            [ 'type' => 'radio_button_set', 'heading' => 'AJAX Pagination', 'param_name' => 'paginacion', 'default' => 'no', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'General' ],
            [ 'type' => 'select', 'heading' => 'Order', 'param_name' => 'orden', 'default' => 'fecha', 'value' => [ 'fecha' => 'Date', 'random' => 'Random', 'meta' => 'By meta' ], 'group' => 'General' ],
            [ 'type' => 'textfield', 'heading' => 'Meta key (for meta ordering)', 'param_name' => 'meta_key', 'default' => '', 'group' => 'General', 'dependency' => [ [ 'element' => 'orden', 'value' => 'meta', 'operator' => '==' ] ] ],
            [ 'type' => 'select', 'heading' => 'Direction (for meta)', 'param_name' => 'meta_order', 'default' => 'ASC', 'value' => [ 'ASC' => 'ASC', 'DESC' => 'DESC' ], 'group' => 'General', 'dependency' => [ [ 'element' => 'orden', 'value' => 'meta', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => 'Minimum pages', 'param_name' => 'min_paginas', 'default' => 1, 'min' => 1, 'max' => 50, 'step' => 1, 'group' => 'General' ],
            [ 'type' => 'range', 'heading' => 'Cache time (seconds)', 'param_name' => 'tiempo_cache', 'default' => 3600, 'min' => 0, 'max' => 86400, 'step' => 60, 'description' => '0 to disable cache.', 'group' => 'General' ],
            [ 'type' => 'radio_button_set', 'heading' => 'Force no cache', 'param_name' => 'forzar_sin_cache', 'default' => 'no', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'General' ],
            [ 'type' => 'textfield', 'heading' => 'Actions (CSV)', 'param_name' => 'acciones', 'default' => '', 'group' => 'General' ],
            [ 'type' => 'radio_button_set', 'heading' => 'Submenu enabled', 'param_name' => 'submenu', 'default' => 'no', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'General' ],
            [ 'type' => 'select', 'heading' => 'Action event', 'param_name' => 'evento_accion', 'default' => 'dblclick', 'value' => [ 'click' => 'click', 'dblclick' => 'dblclick', 'longpress' => 'longpress' ], 'group' => 'General' ],
            [ 'type' => 'textfield', 'heading' => 'Item CSS selector', 'param_name' => 'selector_item', 'default' => '[id^="post-"]', 'group' => 'General' ],
            [ 'type' => 'textarea', 'heading' => 'Advanced query arguments (JSON)', 'param_name' => 'argumentos_json', 'default' => '', 'group' => 'General' ],
            [ 'type' => 'textfield', 'heading' => 'Specific IDs (CSV)', 'param_name' => 'post_ids', 'default' => '', 'description' => 'E.g.: 45,103,22', 'group' => 'General' ],
            [ 'type' => 'multiple_select', 'heading' => 'Select posts', 'param_name' => 'post_ids_select', 'default' => [], 'value' => self::discoverRecentPosts(), 'placeholder' => 'Search posts by title...', 'description' => 'Optional. If selected, only these will be shown.', 'group' => 'General' ],
            [ 'type' => 'radio_button_set', 'heading' => 'Interaction', 'param_name' => 'interaccion_modo', 'default' => 'normal', 'value' => [ 'normal' => 'Normal', 'carousel' => 'Carousel', 'toggle' => 'Toggle' ], 'group' => 'General' ],
            [ 'type' => 'range', 'heading' => 'Carousel speed (px/s)', 'param_name' => 'carousel_speed', 'default' => 20, 'min' => 1, 'max' => 200, 'step' => 1, 'group' => 'General', 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'carousel', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => 'Toggle separator', 'param_name' => 'toggle_separator', 'default' => 'no', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'General', 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'toggle', 'operator' => '==' ] ] ],
            [ 'type' => 'colorpickeralpha', 'heading' => 'Separator color', 'param_name' => 'toggle_separator_color', 'default' => 'rgba(0,0,0,0.1)', 'group' => 'General', 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'toggle', 'operator' => '==' ], [ 'element' => 'toggle_separator', 'value' => 'yes', 'operator' => '==' ] ] ],
            [ 'type' => 'textfield', 'heading' => 'Toggle auto-open (IDs CSV)', 'param_name' => 'toggle_auto_open', 'default' => '', 'description' => 'E.g.: 1,3,5 to open specific positions.', 'group' => 'General', 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'toggle', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => 'Default state (toggle)', 'param_name' => 'toggle_default_state', 'default' => 'collapsed', 'value' => [ 'collapsed' => 'Hidden', 'expanded' => 'Expanded' ], 'group' => 'General', 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'toggle', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => 'Enable horizontal drag', 'param_name' => 'enable_horizontal_drag', 'default' => 'no', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'General', 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'normal', 'operator' => '==' ] ] ],

            // Design / Layout
            [ 'type' => 'radio_button_set', 'heading' => 'Display', 'param_name' => 'display_mode', 'default' => 'flex', 'value' => [ 'flex' => 'Flex', 'grid' => 'Grid', 'block' => 'Block' ], 'group' => 'Design', 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'carousel', 'operator' => '!=' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => 'Direction (flex-direction)', 'param_name' => 'flex_direction', 'default' => 'row', 'value' => [ 'row' => 'row', 'column' => 'column' ], 'group' => 'Design', 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'flex', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => 'Flex wrap', 'param_name' => 'flex_wrap', 'default' => 'wrap', 'value' => [ 'nowrap' => 'nowrap', 'wrap' => 'wrap' ], 'group' => 'Design', 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'flex', 'operator' => '==' ] ] ],
            [ 'type' => 'textfield', 'heading' => 'Gap', 'param_name' => 'gap', 'default' => '20px', 'group' => 'Design' ],
            [ 'type' => 'select', 'heading' => 'Align items', 'param_name' => 'align_items', 'default' => 'stretch', 'value' => [ 'stretch'=>'stretch','flex-start'=>'flex-start','center'=>'center','flex-end'=>'flex-end' ], 'group' => 'Design', 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'flex', 'operator' => '==' ] ] ],
            [ 'type' => 'select', 'heading' => 'Justify content', 'param_name' => 'justify_content', 'default' => 'flex-start', 'value' => [ 'flex-start'=>'flex-start','center'=>'center','space-between'=>'space-between','space-around'=>'space-around','flex-end'=>'flex-end' ], 'group' => 'Design' ],
            [ 'type' => 'textfield', 'heading' => 'Grid min-width', 'param_name' => 'grid_min_width', 'default' => '250px', 'group' => 'Design', 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => 'Grid auto-fit', 'param_name' => 'grid_auto_fit', 'default' => 'yes', 'value' => [ 'yes' => 'auto-fit', 'no' => 'auto-fill' ], 'group' => 'Design', 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => 'Column mode', 'param_name' => 'grid_columns_mode', 'default' => 'fixed', 'value' => [ 'fixed' => 'Fixed', 'auto' => 'Auto (min/max)' ], 'group' => 'Design', 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => 'Columns', 'param_name' => 'grid_columns', 'value' => [ 'large' => 4, 'medium' => '', 'small' => '' ], 'default' => 4, 'min' => 1, 'max' => 12, 'step' => 1, 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => true ], 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'grid_columns_mode', 'value' => 'fixed', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => 'Minimum columns', 'param_name' => 'grid_min_columns', 'value' => [ 'large' => 1, 'medium' => '', 'small' => '' ], 'default' => 1, 'min' => 1, 'max' => 12, 'step' => 1, 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => true ], 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'grid_columns_mode', 'value' => 'auto', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => 'Maximum columns', 'param_name' => 'grid_max_columns', 'value' => [ 'large' => 12, 'medium' => '', 'small' => '' ], 'default' => 12, 'min' => 1, 'max' => 12, 'step' => 1, 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => true ], 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'grid_columns_mode', 'value' => 'auto', 'operator' => '==' ] ] ],

            // Grid rotate effect
            [ 'type' => 'radio_button_set', 'heading' => 'Rotate grid', 'param_name' => 'grid_rotate', 'default' => 'no', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'Design', 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'grid_columns_mode', 'value' => 'fixed', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => 'Rotate interval (ms)', 'param_name' => 'grid_rotate_interval', 'default' => 3000, 'min' => 200, 'max' => 60000, 'step' => 100, 'group' => 'Design', 'dependency' => [ [ 'element' => 'grid_rotate', 'value' => 'yes', 'operator' => '==' ], [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => 'Fade duration (ms)', 'param_name' => 'grid_rotate_fade', 'default' => 400, 'min' => 50, 'max' => 5000, 'step' => 50, 'group' => 'Design', 'dependency' => [ [ 'element' => 'grid_rotate', 'value' => 'yes', 'operator' => '==' ], [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'textfield', 'heading' => 'Fade offset', 'param_name' => 'grid_rotate_offset', 'default' => '10px', 'description' => 'CSS length, e.g. 10px', 'group' => 'Design', 'dependency' => [ [ 'element' => 'grid_rotate', 'value' => 'yes', 'operator' => '==' ], [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],

            // Internal Layout (if template supports it)
            [ 'type' => 'radio_button_set', 'heading' => 'Internal layout', 'param_name' => 'internal_display_mode', 'default' => '', 'value' => [ '' => 'Default', 'flex' => 'Flex', 'grid' => 'Grid', 'block' => 'Block' ], 'group' => 'Internal Design' ],
            [ 'type' => 'radio_button_set', 'heading' => 'Internal direction', 'param_name' => 'internal_flex_direction', 'default' => '', 'value' => [ '' => 'Default', 'row' => 'row', 'column' => 'column' ], 'group' => 'Internal Design' ],
            [ 'type' => 'radio_button_set', 'heading' => 'Internal flex wrap', 'param_name' => 'internal_flex_wrap', 'default' => '', 'value' => [ '' => 'Default', 'nowrap' => 'nowrap', 'wrap' => 'wrap' ], 'group' => 'Internal Design' ],
            [ 'type' => 'textfield', 'heading' => 'Internal gap', 'param_name' => 'internal_gap', 'default' => '', 'group' => 'Internal Design' ],
            [ 'type' => 'select', 'heading' => 'Internal align items', 'param_name' => 'internal_align_items', 'default' => '', 'value' => [ '' => 'Default', 'stretch'=>'stretch','flex-start'=>'flex-start','center'=>'center','flex-end'=>'flex-end' ], 'group' => 'Internal Design' ],
            [ 'type' => 'select', 'heading' => 'Internal justify content', 'param_name' => 'internal_justify_content', 'default' => '', 'value' => [ '' => 'Default', 'flex-start'=>'flex-start','center'=>'center','space-between'=>'space-between','space-around'=>'space-around','flex-end'=>'flex-end' ], 'group' => 'Internal Design' ],
            [ 'type' => 'textfield', 'heading' => 'Internal grid min-width', 'param_name' => 'internal_grid_min_width', 'default' => '', 'group' => 'Internal Design', 'dependency' => [ [ 'element' => 'internal_display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => 'Internal grid auto-fit', 'param_name' => 'internal_grid_auto_fit', 'default' => '', 'value' => [ '' => 'Default', 'yes' => 'auto-fit', 'no' => 'auto-fill' ], 'group' => 'Internal Design', 'dependency' => [ [ 'element' => 'internal_display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => 'Internal column mode', 'param_name' => 'internal_grid_columns_mode', 'default' => '', 'value' => [ '' => 'Default', 'fixed' => 'Fixed', 'auto' => 'Auto (min/max)' ], 'group' => 'Internal Design', 'dependency' => [ [ 'element' => 'internal_display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => 'Internal columns', 'param_name' => 'internal_grid_columns', 'value' => [ 'large' => '', 'medium' => '', 'small' => '' ], 'default' => '', 'min' => 1, 'max' => 12, 'step' => 1, 'group' => 'Internal Design', 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ], 'dependency' => [ [ 'element' => 'internal_display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'internal_grid_columns_mode', 'value' => 'fixed', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => 'Internal minimum columns', 'param_name' => 'internal_grid_min_columns', 'value' => [ 'large' => '', 'medium' => '', 'small' => '' ], 'default' => '', 'min' => 1, 'max' => 12, 'step' => 1, 'group' => 'Internal Design', 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ], 'dependency' => [ [ 'element' => 'internal_display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'internal_grid_columns_mode', 'value' => 'auto', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => 'Internal maximum columns', 'param_name' => 'internal_grid_max_columns', 'value' => [ 'large' => '', 'medium' => '', 'small' => '' ], 'default' => '', 'min' => 1, 'max' => 12, 'step' => 1, 'group' => 'Internal Design', 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ], 'dependency' => [ [ 'element' => 'internal_display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'internal_grid_columns_mode', 'value' => 'auto', 'operator' => '==' ] ] ],

            // Image + Title (Design)
            [ 'type' => 'radio_button_set', 'heading' => 'Show image', 'param_name' => 'img_show', 'default' => 'yes', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'Design' ],
            [ 'type' => 'select', 'heading' => 'Image size', 'param_name' => 'img_size', 'default' => 'medium', 'value' => self::discoverImageSizes(), 'group' => 'Design' ],
            [ 'type' => 'textfield', 'heading' => 'Aspect ratio', 'param_name' => 'img_aspect_ratio', 'default' => '1 / 1', 'group' => 'Design' ],
            [ 'type' => 'radio_button_set', 'heading' => 'Object fit', 'param_name' => 'img_object_fit', 'default' => 'cover', 'value' => [ 'cover'=>'cover','contain'=>'contain' ], 'group' => 'Design' ],
            [ 'type' => 'textfield', 'heading' => 'Min width', 'param_name' => 'img_min_width', 'default' => '', 'description' => '', 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ] ],
            [ 'type' => 'textfield', 'heading' => 'Width', 'param_name' => 'img_width', 'default' => '', 'description' => '', 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ] ],
            [ 'type' => 'textfield', 'heading' => 'Max width', 'param_name' => 'img_max_width', 'default' => '', 'description' => '', 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ] ],
            [ 'type' => 'textfield', 'heading' => 'Min height', 'param_name' => 'img_min_height', 'default' => '', 'description' => '', 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ] ],
            [ 'type' => 'textfield', 'heading' => 'Height', 'param_name' => 'img_height', 'default' => '', 'description' => '', 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ] ],
            [ 'type' => 'textfield', 'heading' => 'Max height', 'param_name' => 'img_max_height', 'default' => '', 'description' => '', 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => 'Optimize image', 'param_name' => 'img_optimize', 'default' => 'yes', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'Design' ],
            [ 'type' => 'range', 'heading' => 'Image quality', 'param_name' => 'img_quality', 'default' => 60, 'min' => 10, 'max' => 100, 'step' => 1, 'group' => 'Design' ],
            [ 'type' => 'radio_button_set', 'heading' => 'Show title', 'param_name' => 'title_show', 'default' => 'yes', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'Design' ],
            [ 'type' => 'typography', 'heading' => 'Title typography', 'param_name' => 'title_typography', 'remove_from_atts' => true, 'group' => 'Design', 'choices' => [ 'font-family' => 'title_font', 'variant' => 'title_font', 'font-size' => 'font_size', 'line-height' => 'line_height', 'letter-spacing' => 'letter_spacing' ], 'default' => [ 'font-family' => '', 'variant' => '', 'font-size' => '', 'line-height' => '', 'letter-spacing' => '' ] ],
            [ 'type' => 'colorpickeralpha', 'heading' => 'Title color', 'param_name' => 'title_color', 'default' => '', 'group' => 'Design' ],
            [ 'type' => 'radio_button_set', 'heading' => 'Text transformation', 'param_name' => 'title_text_transform', 'default' => '', 'value' => [ ''=>'None','uppercase'=>'uppercase','capitalize'=>'capitalize','lowercase'=>'lowercase' ], 'group' => 'Design' ],
            [ 'type' => 'textfield', 'heading' => 'Title minimum width', 'param_name' => 'title_min_width', 'default' => '', 'description' => 'E.g.: 100px, 20ch, 50%', 'group' => 'Design' ],
            [ 'type' => 'textfield', 'heading' => 'Title width', 'param_name' => 'title_width', 'default' => '', 'description' => 'E.g.: 200px, 30ch, 80%', 'group' => 'Design' ],
            [ 'type' => 'textfield', 'heading' => 'Title maximum width', 'param_name' => 'title_max_width', 'default' => '', 'description' => 'E.g.: 300px, 40ch, 90%', 'group' => 'Design' ],
            [ 'type' => 'radio_button_set', 'heading' => 'Show title only on hover', 'param_name' => 'title_show_on_hover', 'default' => 'no', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'Design' ],
            [ 'type' => 'radio_button_set', 'heading' => 'Title position', 'param_name' => 'title_position', 'default' => 'top', 'value' => [ 'top' => 'Top', 'bottom' => 'Bottom' ], 'group' => 'Design' ],
			// Layout pattern (alternating S-LL-S) - responsive
			[ 'type' => 'radio_button_set', 'heading' => 'Layout pattern', 'param_name' => 'layout_pattern', 'default' => 'none', 'value' => [ 'none' => 'None', 'alternado_slls' => 'Alternating S-LL-S' ], 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => true, 'additional_states' => [ 'medium', 'small' ] ] ],
			[ 'type' => 'textfield', 'heading' => 'Row gap (pattern)', 'param_name' => 'pattern_row_gap', 'default' => '40px', 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => true, 'additional_states' => [ 'medium', 'small' ] ], 'dependency' => [ [ 'element' => 'layout_pattern', 'value' => 'none', 'operator' => '!=' ] ] ],
			[ 'type' => 'range', 'heading' => 'Small width %', 'param_name' => 'pattern_small_width_percent', 'value' => [ 'large' => 40, 'medium' => '', 'small' => '' ], 'default' => 40, 'min' => 10, 'max' => 90, 'step' => 1, 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => true, 'additional_states' => [ 'medium', 'small' ] ], 'dependency' => [ [ 'element' => 'layout_pattern', 'value' => 'none', 'operator' => '!=' ] ] ],
			[ 'type' => 'range', 'heading' => 'Large width %', 'param_name' => 'pattern_large_width_percent', 'value' => [ 'large' => 60, 'medium' => '', 'small' => '' ], 'default' => 60, 'min' => 10, 'max' => 90, 'step' => 1, 'group' => 'Design', 'responsive' => [ 'state' => 'large', 'default_value' => true, 'additional_states' => [ 'medium', 'small' ] ], 'dependency' => [ [ 'element' => 'layout_pattern', 'value' => 'none', 'operator' => '!=' ] ] ],

            // Internal content typography
            [ 'type' => 'radio_button_set', 'heading' => 'Enable internal typography', 'param_name' => 'internal_typography_enable', 'default' => 'no', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'Design' ],
            [ 'type' => 'typography', 'heading' => 'Content typography', 'param_name' => 'internal_typography', 'remove_from_atts' => true, 'group' => 'Design', 'choices' => [ 'font-family' => 'internal_font', 'variant' => 'internal_font', 'font-size' => 'internal_font_size', 'line-height' => 'internal_line_height', 'letter-spacing' => 'internal_letter_spacing' ], 'default' => [ 'font-family' => '', 'variant' => '', 'font-size' => '', 'line-height' => '', 'letter-spacing' => '' ], 'dependency' => [ [ 'element' => 'internal_typography_enable', 'value' => 'yes', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => 'Text transformation (content)', 'param_name' => 'internal_text_transform', 'default' => '', 'value' => [ ''=>'None','uppercase'=>'uppercase','capitalize'=>'capitalize','lowercase'=>'lowercase' ], 'group' => 'Design', 'dependency' => [ [ 'element' => 'internal_typography_enable', 'value' => 'yes', 'operator' => '==' ] ] ],

            // Link
            [ 'type' => 'radio_button_set', 'heading' => 'Enable link', 'param_name' => 'link_enabled', 'default' => 'yes', 'value' => [ 'yes' => 'Yes', 'no' => 'No' ], 'group' => 'General' ],
            [ 'type' => 'textfield', 'heading' => 'Content minimum width', 'param_name' => 'content_min_width', 'default' => '', 'description' => 'E.g.: 200px, 20ch, 50%', 'group' => 'Design' ],
            [ 'type' => 'textfield', 'heading' => 'Content width', 'param_name' => 'content_width', 'default' => '', 'description' => 'E.g.: 300px, 30ch, 80%', 'group' => 'Design' ],
            [ 'type' => 'textfield', 'heading' => 'Content maximum width', 'param_name' => 'content_max_width', 'default' => '', 'description' => 'E.g.: 500px, 50ch, 100%', 'group' => 'Design' ],
        ];

        // Inyectar parámetros dinámicos declarados por cada plantilla
        if ( class_exists('Glory\\Utility\\TemplateRegistry') ) {
            try {
                $templateOptions = \Glory\Utility\TemplateRegistry::options(null);
                if ( is_array($templateOptions) ) {
                    // Recoger reglas de ocultación por plantilla
                    $hideByTemplate = [];
                    foreach ( $templateOptions as $templateId => $templateLabel ) {
                        if ( '__default' === (string) $templateId ) {
                            continue;
                        }
                        $supports = \Glory\Utility\TemplateRegistry::supports( (string) $templateId );
                        if ( is_array($supports) && ! empty($supports['hideControls']) && is_array($supports['hideControls']) ) {
                            $hideByTemplate[(string) $templateId] = array_map('strval', $supports['hideControls']);
                        }
                    }

                    foreach ( $templateOptions as $templateId => $templateLabel ) {
                        if ( '__default' === (string) $templateId ) {
                            continue;
                        }
                        $supports = \Glory\Utility\TemplateRegistry::supports( (string) $templateId );
                        if ( is_array( $supports ) && ! empty( $supports['options'] ) && is_array( $supports['options'] ) ) {
                            foreach ( $supports['options'] as $opt ) {
                                if ( ! is_array( $opt ) || empty( $opt['param_name'] ) ) {
                                    continue;
                                }
                                $param = $opt;
                                if ( empty($param['description']) ) {
                                    $param['description'] = '';
                                }
                                // Normalizar nombres de grupo a inglés para evitar pestañas duplicadas
                                $groupRaw = $param['group'] ?? '';
                                if (! is_string($groupRaw) || '' === trim($groupRaw)) {
                                    $param['group'] = 'General';
                                } else {
                                    $groupMap = [
                                        'Diseño' => 'Design',
                                        'Diseño interno' => 'Internal Design',
                                        'Dise o' => 'Design', // fallback weird encoding
                                    ];
                                    $normalized = $groupRaw;
                                    foreach ($groupMap as $from => $to) {
                                        if ($groupRaw === $from || mb_stripos($groupRaw, $from) !== false) {
                                            $normalized = $to;
                                            break;
                                        }
                                    }
                                    $param['group'] = $normalized;
                                }
                                $dependency = $param['dependency'] ?? [];
                                if ( ! is_array( $dependency ) ) {
                                    $dependency = [];
                                }
                                $dependency[] = [ 'element' => 'template_id', 'value' => (string) $templateId, 'operator' => '==' ];
                                $param['dependency'] = $dependency;
                                $params[] = $param;
                            }
                        }

                        // Ocultar controles genéricos cuando la plantilla lo solicite
                        if ( isset($hideByTemplate[(string) $templateId]) && is_array($hideByTemplate[(string) $templateId]) ) {
                            $toHide = $hideByTemplate[(string) $templateId];
                            foreach ($params as $idx => $p) {
                                if (! is_array($p) || empty($p['param_name'])) {
                                    continue;
                                }
                                if (in_array($p['param_name'], $toHide, true)) {
                                    $dep = $params[$idx]['dependency'] ?? [];
                                    if (! is_array($dep)) { $dep = []; }
                                    // Mostrar SOLO cuando el template seleccionado sea distinto
                                    $dep[] = [ 'element' => 'template_id', 'value' => (string) $templateId, 'operator' => '!=' ];
                                    $params[$idx]['dependency'] = $dep;
                                }
                            }
                        }
                    }
                }
            } catch ( \Throwable $t ) {}
        }

        // Normalizar 'description' para evitar warnings en Fusion Builder.
        foreach ($params as $idx => $param) {
            if (! is_array($param)) {
                continue;
            }
            if (! array_key_exists('description', $param)) {
                $params[$idx]['description'] = '';
            }
        }

        return $params;
    }

    private static function discoverPublicPostTypes(): array
    {
        $options = [ 'post' => 'post' ];
        $pts = get_post_types([ 'public' => true ], 'objects');
        if ( is_array($pts) ) {
            foreach ($pts as $pt) {
                $label = $pt->labels->singular_name ?? ($pt->label ?? $pt->name);
                // map Spanish custom post types to English display labels without touching the slug (key)
                $ptMap = [
                    'proyecto' => 'Project',
                    'proyectos' => 'Project',
                    'servicio' => 'Service',
                    'servicios' => 'Service',
                ];
                $keyLower = mb_strtolower((string) $pt->name);
                if ( isset($ptMap[$keyLower]) ) {
                    $options[$pt->name] = $ptMap[$keyLower];
                } else {
                    $options[$pt->name] = $label;
                }
            }
        }
        return $options;
    }

    private static function discoverTemplates(): array
    {
        // Preservamos las claves (template IDs) pero transformamos SOLO las etiquetas
        // que se muestran en el UI para evitar romper opciones almacenadas.
        $templates = [ '__default' => 'Default template (generic)' ];
        if ( class_exists('Glory\\Utility\\TemplateRegistry') ) {
            try {
                $opts = \Glory\Utility\TemplateRegistry::options(null);
                if ( is_array($opts) ) {
                    // Mantener keys, pero mapear valores conocidos a inglés
                    $mapping = [
                        'Plantilla de Portafolio' => 'Portfolio Template',
                        'Plantilla por defecto (genérica)' => 'Default template (generic)',
                        'Plantilla de Brands' => 'Brands Template',
                        'Plantilla de Libro' => 'Book Template',
                        'Plantilla de Posts' => 'Posts Template',
                        'Plantilla de Servicios' => 'Services Template',
                        'Team Template' => 'Team Template',
                    ];
                    foreach ($opts as $id => $label) {
                        if ( is_string($label) && isset($mapping[$label]) ) {
                            $templates[$id] = $mapping[$label];
                        } else {
                            // fallback: si la etiqueta contiene 'Plantilla', la convertimos quitando
                            // el prefijo y capitalizando, sin tocar el id.
                            if ( is_string($label) && mb_stripos($label, 'Plantilla') !== false ) {
                                $clean = preg_replace('/Plantilla\s+de\s+/iu', '', $label);
                                $clean = trim($clean);
                                // convertir a un label en inglés simple manteniendo palabras (fallback)
                                $templates[$id] = $clean;
                            } else {
                                $templates[$id] = $label;
                            }
                        }
                    }
                }
            } catch (\Throwable $t) {}
        }
        return $templates;
    }

    private static function discoverImageSizes(): array
    {
        $sizes = function_exists('get_intermediate_image_sizes') ? (array) get_intermediate_image_sizes() : [];
        $sizes = array_values(array_unique(array_merge(['thumbnail','medium','medium_large','large'], $sizes)));
        $options = [];
        foreach ($sizes as $s) {
            $label = $s;
            if ($s === 'thumbnail') { $label = 'thumbnail'; }
            if ($s === 'medium') { $label = 'medium'; }
            if ($s === 'medium_large') { $label = 'medium_large'; }
            if ($s === 'large') { $label = 'large'; }
            if ($s === 'full') { $label = 'full'; }
            $options[$s] = $label;
        }
        $options['full'] = $options['full'] ?? 'full';
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


