<?php

namespace Glory\Integration\Avada\Elements\GloryContentRender;

class GloryContentRenderParams
{
    public static function all(): array
    {
        $params = [
            [ 'type' => 'select', 'heading' => __('Tipo de contenido', 'glory-ab'), 'param_name' => 'post_type', 'default' => 'post', 'value' => self::discoverPublicPostTypes(), 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'select', 'heading' => __('Plantilla de contenido', 'glory-ab'), 'param_name' => 'template_id', 'default' => '__default', 'value' => self::discoverTemplates(), 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Usar ContentRender', 'glory-ab'), 'param_name' => 'usar_content_render', 'default' => 'yes', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'range', 'heading' => __('Publicaciones por página', 'glory-ab'), 'param_name' => 'publicaciones_por_pagina', 'default' => 10, 'min' => 1, 'max' => 100, 'step' => 1, 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Clase contenedor', 'glory-ab'), 'param_name' => 'clase_contenedor', 'default' => 'glory-content-list', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Clase de item', 'glory-ab'), 'param_name' => 'clase_item', 'default' => 'glory-content-item', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Paginación AJAX', 'glory-ab'), 'param_name' => 'paginacion', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'select', 'heading' => __('Orden', 'glory-ab'), 'param_name' => 'orden', 'default' => 'fecha', 'value' => [ 'fecha' => __('Fecha','glory-ab'), 'random' => __('Aleatorio','glory-ab'), 'meta' => __('Por meta','glory-ab') ], 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Meta key (para orden por meta)', 'glory-ab'), 'param_name' => 'meta_key', 'default' => '', 'group' => __('General', 'glory-ab'), 'dependency' => [ [ 'element' => 'orden', 'value' => 'meta', 'operator' => '==' ] ] ],
            [ 'type' => 'select', 'heading' => __('Dirección (para meta)', 'glory-ab'), 'param_name' => 'meta_order', 'default' => 'ASC', 'value' => [ 'ASC' => 'ASC', 'DESC' => 'DESC' ], 'group' => __('General', 'glory-ab'), 'dependency' => [ [ 'element' => 'orden', 'value' => 'meta', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => __('Mínimo de páginas', 'glory-ab'), 'param_name' => 'min_paginas', 'default' => 1, 'min' => 1, 'max' => 50, 'step' => 1, 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'range', 'heading' => __('Tiempo de caché (segundos)', 'glory-ab'), 'param_name' => 'tiempo_cache', 'default' => 3600, 'min' => 0, 'max' => 86400, 'step' => 60, 'description' => __('0 para desactivar caché.', 'glory-ab'), 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Forzar sin caché', 'glory-ab'), 'param_name' => 'forzar_sin_cache', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Acciones (CSV)', 'glory-ab'), 'param_name' => 'acciones', 'default' => '', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Submenú habilitado', 'glory-ab'), 'param_name' => 'submenu', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'select', 'heading' => __('Evento de acción', 'glory-ab'), 'param_name' => 'evento_accion', 'default' => 'dblclick', 'value' => [ 'click' => 'click', 'dblclick' => 'dblclick', 'longpress' => 'longpress' ], 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Selector CSS del item', 'glory-ab'), 'param_name' => 'selector_item', 'default' => '[id^="post-"]', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textarea', 'heading' => __('Argumentos de consulta avanzados (JSON)', 'glory-ab'), 'param_name' => 'argumentos_json', 'default' => '', 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('IDs específicos (CSV)', 'glory-ab'), 'param_name' => 'post_ids', 'default' => '', 'description' => __('Ej.: 45,103,22', 'glory-ab'), 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'multiple_select', 'heading' => __('Selecciona posts', 'glory-ab'), 'param_name' => 'post_ids_select', 'default' => [], 'value' => self::discoverRecentPosts(), 'placeholder' => __('Busca posts por título…', 'glory-ab'), 'description' => __('Opcional. Si seleccionas, solo se mostrarán estos.', 'glory-ab'), 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Interacción', 'glory-ab'), 'param_name' => 'interaccion_modo', 'default' => 'normal', 'value' => [ 'normal' => __('Normal','glory-ab'), 'carousel' => __('Carrusel','glory-ab'), 'toggle' => __('Toggle','glory-ab') ], 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'range', 'heading' => __('Velocidad carrusel (px/s)', 'glory-ab'), 'param_name' => 'carousel_speed', 'default' => 20, 'min' => 1, 'max' => 200, 'step' => 1, 'group' => __('General', 'glory-ab'), 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'carousel', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Separador toggle', 'glory-ab'), 'param_name' => 'toggle_separator', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('General', 'glory-ab'), 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'toggle', 'operator' => '==' ] ] ],
            [ 'type' => 'colorpickeralpha', 'heading' => __('Color separador', 'glory-ab'), 'param_name' => 'toggle_separator_color', 'default' => 'rgba(0,0,0,0.1)', 'group' => __('General', 'glory-ab'), 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'toggle', 'operator' => '==' ], [ 'element' => 'toggle_separator', 'value' => 'yes', 'operator' => '==' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Toggle auto-abierto (IDs CSV)', 'glory-ab'), 'param_name' => 'toggle_auto_open', 'default' => '', 'description' => __('Ej.: 1,3,5 para abrir posiciones específicas.', 'glory-ab'), 'group' => __('General', 'glory-ab'), 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'toggle', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Estado por defecto (toggle)', 'glory-ab'), 'param_name' => 'toggle_default_state', 'default' => 'collapsed', 'value' => [ 'collapsed' => __('Ocultos','glory-ab'), 'expanded' => __('Expandidos','glory-ab') ], 'group' => __('General', 'glory-ab'), 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'toggle', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Habilitar arrastre horizontal', 'glory-ab'), 'param_name' => 'enable_horizontal_drag', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('General', 'glory-ab'), 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'normal', 'operator' => '==' ] ] ],

            // Grupo Diseño / Layout
            [ 'type' => 'radio_button_set', 'heading' => __('Display', 'glory-ab'), 'param_name' => 'display_mode', 'default' => 'flex', 'value' => [ 'flex' => 'Flex', 'grid' => 'Grid', 'block' => 'Block' ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'interaccion_modo', 'value' => 'carousel', 'operator' => '!=' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Dirección (flex-direction)', 'glory-ab'), 'param_name' => 'flex_direction', 'default' => 'row', 'value' => [ 'row' => 'row', 'column' => 'column' ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'flex', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Flex wrap', 'glory-ab'), 'param_name' => 'flex_wrap', 'default' => 'wrap', 'value' => [ 'nowrap' => 'nowrap', 'wrap' => 'wrap' ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'flex', 'operator' => '==' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Gap', 'glory-ab'), 'param_name' => 'gap', 'default' => '20px', 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'select', 'heading' => __('Align items', 'glory-ab'), 'param_name' => 'align_items', 'default' => 'stretch', 'value' => [ 'stretch'=>'stretch','flex-start'=>'flex-start','center'=>'center','flex-end'=>'flex-end' ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'flex', 'operator' => '==' ] ] ],
            [ 'type' => 'select', 'heading' => __('Justify content', 'glory-ab'), 'param_name' => 'justify_content', 'default' => 'flex-start', 'value' => [ 'flex-start'=>'flex-start','center'=>'center','space-between'=>'space-between','space-around'=>'space-around','flex-end'=>'flex-end' ], 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Grid min-width', 'glory-ab'), 'param_name' => 'grid_min_width', 'default' => '250px', 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Grid auto-fit', 'glory-ab'), 'param_name' => 'grid_auto_fit', 'default' => 'yes', 'value' => [ 'yes' => 'auto-fit', 'no' => 'auto-fill' ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Modo de columnas', 'glory-ab'), 'param_name' => 'grid_columns_mode', 'default' => 'fixed', 'value' => [ 'fixed' => __('Fijas','glory-ab'), 'auto' => __('Auto (mín/máx)','glory-ab') ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => __('Columnas', 'glory-ab'), 'param_name' => 'grid_columns', 'value' => [ 'large' => 4, 'medium' => '', 'small' => '' ], 'default' => 4, 'min' => 1, 'max' => 12, 'step' => 1, 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => true ], 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'grid_columns_mode', 'value' => 'fixed', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => __('Mínimo columnas', 'glory-ab'), 'param_name' => 'grid_min_columns', 'value' => [ 'large' => 1, 'medium' => '', 'small' => '' ], 'default' => 1, 'min' => 1, 'max' => 12, 'step' => 1, 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => true ], 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'grid_columns_mode', 'value' => 'auto', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => __('Máximo columnas', 'glory-ab'), 'param_name' => 'grid_max_columns', 'value' => [ 'large' => 12, 'medium' => '', 'small' => '' ], 'default' => 12, 'min' => 1, 'max' => 12, 'step' => 1, 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => true ], 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'grid_columns_mode', 'value' => 'auto', 'operator' => '==' ] ] ],

            // Layout interno (si la plantilla lo soporta)
            [ 'type' => 'radio_button_set', 'heading' => __('Layout interno', 'glory-ab'), 'param_name' => 'internal_display_mode', 'default' => '', 'value' => [ '' => __('Por defecto','glory-ab'), 'flex' => 'Flex', 'grid' => 'Grid', 'block' => 'Block' ], 'group' => __('Diseño interno', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Dirección interna', 'glory-ab'), 'param_name' => 'internal_flex_direction', 'default' => '', 'value' => [ '' => __('Por defecto','glory-ab'), 'row' => 'row', 'column' => 'column' ], 'group' => __('Diseño interno', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Flex wrap interno', 'glory-ab'), 'param_name' => 'internal_flex_wrap', 'default' => '', 'value' => [ '' => __('Por defecto','glory-ab'), 'nowrap' => 'nowrap', 'wrap' => 'wrap' ], 'group' => __('Diseño interno', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Gap interno', 'glory-ab'), 'param_name' => 'internal_gap', 'default' => '', 'group' => __('Diseño interno', 'glory-ab') ],
            [ 'type' => 'select', 'heading' => __('Align items interno', 'glory-ab'), 'param_name' => 'internal_align_items', 'default' => '', 'value' => [ '' => __('Por defecto','glory-ab'), 'stretch'=>'stretch','flex-start'=>'flex-start','center'=>'center','flex-end'=>'flex-end' ], 'group' => __('Diseño interno', 'glory-ab') ],
            [ 'type' => 'select', 'heading' => __('Justify content interno', 'glory-ab'), 'param_name' => 'internal_justify_content', 'default' => '', 'value' => [ '' => __('Por defecto','glory-ab'), 'flex-start'=>'flex-start','center'=>'center','space-between'=>'space-between','space-around'=>'space-around','flex-end'=>'flex-end' ], 'group' => __('Diseño interno', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Grid interno min-width', 'glory-ab'), 'param_name' => 'internal_grid_min_width', 'default' => '', 'group' => __('Diseño interno', 'glory-ab'), 'dependency' => [ [ 'element' => 'internal_display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Grid interno auto-fit', 'glory-ab'), 'param_name' => 'internal_grid_auto_fit', 'default' => '', 'value' => [ '' => __('Por defecto','glory-ab'), 'yes' => 'auto-fit', 'no' => 'auto-fill' ], 'group' => __('Diseño interno', 'glory-ab'), 'dependency' => [ [ 'element' => 'internal_display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Modo de columnas interno', 'glory-ab'), 'param_name' => 'internal_grid_columns_mode', 'default' => '', 'value' => [ '' => __('Por defecto','glory-ab'), 'fixed' => __('Fijas','glory-ab'), 'auto' => __('Auto (mín/máx)','glory-ab') ], 'group' => __('Diseño interno', 'glory-ab'), 'dependency' => [ [ 'element' => 'internal_display_mode', 'value' => 'grid', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => __('Columnas internas', 'glory-ab'), 'param_name' => 'internal_grid_columns', 'value' => [ 'large' => '', 'medium' => '', 'small' => '' ], 'default' => '', 'min' => 1, 'max' => 12, 'step' => 1, 'group' => __('Diseño interno', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ], 'dependency' => [ [ 'element' => 'internal_display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'internal_grid_columns_mode', 'value' => 'fixed', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => __('Mínimo columnas internas', 'glory-ab'), 'param_name' => 'internal_grid_min_columns', 'value' => [ 'large' => '', 'medium' => '', 'small' => '' ], 'default' => '', 'min' => 1, 'max' => 12, 'step' => 1, 'group' => __('Diseño interno', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ], 'dependency' => [ [ 'element' => 'internal_display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'internal_grid_columns_mode', 'value' => 'auto', 'operator' => '==' ] ] ],
            [ 'type' => 'range', 'heading' => __('Máximo columnas internas', 'glory-ab'), 'param_name' => 'internal_grid_max_columns', 'value' => [ 'large' => '', 'medium' => '', 'small' => '' ], 'default' => '', 'min' => 1, 'max' => 12, 'step' => 1, 'group' => __('Diseño interno', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ], 'dependency' => [ [ 'element' => 'internal_display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'internal_grid_columns_mode', 'value' => 'auto', 'operator' => '==' ] ] ],

            // Imagen + Título (Diseño)
            [ 'type' => 'radio_button_set', 'heading' => __('Mostrar imagen', 'glory-ab'), 'param_name' => 'img_show', 'default' => 'yes', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'select', 'heading' => __('Tamaño de imagen', 'glory-ab'), 'param_name' => 'img_size', 'default' => 'medium', 'value' => self::discoverImageSizes(), 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Aspect ratio', 'glory-ab'), 'param_name' => 'img_aspect_ratio', 'default' => '1 / 1', 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Object fit', 'glory-ab'), 'param_name' => 'img_object_fit', 'default' => 'cover', 'value' => [ 'cover'=>'cover','contain'=>'contain' ], 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Min width', 'glory-ab'), 'param_name' => 'img_min_width', 'default' => '', 'description' => '', 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Width', 'glory-ab'), 'param_name' => 'img_width', 'default' => '', 'description' => '', 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Max width', 'glory-ab'), 'param_name' => 'img_max_width', 'default' => '', 'description' => '', 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Min height', 'glory-ab'), 'param_name' => 'img_min_height', 'default' => '', 'description' => '', 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Height', 'glory-ab'), 'param_name' => 'img_height', 'default' => '', 'description' => '', 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Max height', 'glory-ab'), 'param_name' => 'img_max_height', 'default' => '', 'description' => '', 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => false, 'additional_states' => [ 'medium', 'small' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Optimizar imagen', 'glory-ab'), 'param_name' => 'img_optimize', 'default' => 'yes', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'range', 'heading' => __('Calidad de imagen', 'glory-ab'), 'param_name' => 'img_quality', 'default' => 60, 'min' => 10, 'max' => 100, 'step' => 1, 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Mostrar título', 'glory-ab'), 'param_name' => 'title_show', 'default' => 'yes', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'typography', 'heading' => __('Tipografía del título', 'glory-ab'), 'param_name' => 'title_typography', 'remove_from_atts' => true, 'group' => __('Diseño', 'glory-ab'), 'choices' => [ 'font-family' => 'title_font', 'variant' => 'title_font', 'font-size' => 'font_size', 'line-height' => 'line_height', 'letter-spacing' => 'letter_spacing' ], 'default' => [ 'font-family' => '', 'variant' => '', 'font-size' => '', 'line-height' => '', 'letter-spacing' => '' ] ],
            [ 'type' => 'colorpickeralpha', 'heading' => __('Color del título', 'glory-ab'), 'param_name' => 'title_color', 'default' => '', 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Transformación de texto', 'glory-ab'), 'param_name' => 'title_text_transform', 'default' => '', 'value' => [ ''=>'Ninguno','uppercase'=>'uppercase','capitalize'=>'capitalize','lowercase'=>'lowercase' ], 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Ancho mínimo del título', 'glory-ab'), 'param_name' => 'title_min_width', 'default' => '', 'description' => __('Ej.: 100px, 20ch, 50%', 'glory-ab'), 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Ancho del título', 'glory-ab'), 'param_name' => 'title_width', 'default' => '', 'description' => __('Ej.: 200px, 30ch, 80%', 'glory-ab'), 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Ancho máximo del título', 'glory-ab'), 'param_name' => 'title_max_width', 'default' => '', 'description' => __('Ej.: 300px, 40ch, 90%', 'glory-ab'), 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Mostrar título solo en hover', 'glory-ab'), 'param_name' => 'title_show_on_hover', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Posición del título', 'glory-ab'), 'param_name' => 'title_position', 'default' => 'top', 'value' => [ 'top' => __('Arriba','glory-ab'), 'bottom' => __('Abajo','glory-ab') ], 'group' => __('Diseño', 'glory-ab') ],
			// Patrón de layout (alternado S-LL-S) - responsive
			[ 'type' => 'radio_button_set', 'heading' => __('Patrón de layout', 'glory-ab'), 'param_name' => 'layout_pattern', 'default' => 'none', 'value' => [ 'none' => __('Ninguno','glory-ab'), 'alternado_slls' => __('Alternado S-LL-S','glory-ab') ], 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => true, 'additional_states' => [ 'medium', 'small' ] ] ],
			[ 'type' => 'textfield', 'heading' => __('Row gap (patrón)', 'glory-ab'), 'param_name' => 'pattern_row_gap', 'default' => '40px', 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => true, 'additional_states' => [ 'medium', 'small' ] ] ],
			[ 'type' => 'range', 'heading' => __('Ancho pequeño %', 'glory-ab'), 'param_name' => 'pattern_small_width_percent', 'value' => [ 'large' => 40, 'medium' => '', 'small' => '' ], 'default' => 40, 'min' => 10, 'max' => 90, 'step' => 1, 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => true, 'additional_states' => [ 'medium', 'small' ] ] ],
			[ 'type' => 'range', 'heading' => __('Ancho grande %', 'glory-ab'), 'param_name' => 'pattern_large_width_percent', 'value' => [ 'large' => 60, 'medium' => '', 'small' => '' ], 'default' => 60, 'min' => 10, 'max' => 90, 'step' => 1, 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => true, 'additional_states' => [ 'medium', 'small' ] ] ],

            // Tipografía del contenido interno
            [ 'type' => 'radio_button_set', 'heading' => __('Habilitar tipografía interna', 'glory-ab'), 'param_name' => 'internal_typography_enable', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'typography', 'heading' => __('Tipografía del contenido', 'glory-ab'), 'param_name' => 'internal_typography', 'remove_from_atts' => true, 'group' => __('Diseño', 'glory-ab'), 'choices' => [ 'font-family' => 'internal_font', 'variant' => 'internal_font', 'font-size' => 'internal_font_size', 'line-height' => 'internal_line_height', 'letter-spacing' => 'internal_letter_spacing' ], 'default' => [ 'font-family' => '', 'variant' => '', 'font-size' => '', 'line-height' => '', 'letter-spacing' => '' ], 'dependency' => [ [ 'element' => 'internal_typography_enable', 'value' => 'yes', 'operator' => '==' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Transformación texto (contenido)', 'glory-ab'), 'param_name' => 'internal_text_transform', 'default' => '', 'value' => [ ''=>'Ninguno','uppercase'=>'uppercase','capitalize'=>'capitalize','lowercase'=>'lowercase' ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'internal_typography_enable', 'value' => 'yes', 'operator' => '==' ] ] ],

            // Enlace
            [ 'type' => 'radio_button_set', 'heading' => __('Habilitar enlace', 'glory-ab'), 'param_name' => 'link_enabled', 'default' => 'yes', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('General', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Ancho mínimo del contenido', 'glory-ab'), 'param_name' => 'content_min_width', 'default' => '', 'description' => __('Ej.: 200px, 20ch, 50%', 'glory-ab'), 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Ancho del contenido', 'glory-ab'), 'param_name' => 'content_width', 'default' => '', 'description' => __('Ej.: 300px, 30ch, 80%', 'glory-ab'), 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Ancho máximo del contenido', 'glory-ab'), 'param_name' => 'content_max_width', 'default' => '', 'description' => __('Ej.: 500px, 50ch, 100%', 'glory-ab'), 'group' => __('Diseño', 'glory-ab') ],
        ];

        // Inyectar parámetros dinámicos declarados por cada plantilla
        if ( class_exists('Glory\\Utility\\TemplateRegistry') ) {
            try {
                $templateOptions = \Glory\Utility\TemplateRegistry::options(null);
                if ( is_array($templateOptions) ) {
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
                                if ( empty( $param['group'] ) ) {
                                    $param['group'] = __( 'General', 'glory-ab' );
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
                $options[$pt->name] = $pt->labels->singular_name ?? ($pt->label ?? $pt->name);
            }
        }
        return $options;
    }

    private static function discoverTemplates(): array
    {
        $templates = [ '__default' => __('Plantilla por defecto (genérica)', 'glory-ab') ];
        if ( class_exists('Glory\\Utility\\TemplateRegistry') ) {
            try {
                $opts = \Glory\Utility\TemplateRegistry::options(null);
                if ( is_array($opts) ) {
                    $templates = array_merge($templates, $opts);
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
            if ($s === 'thumbnail') { $label = __('thumbnail', 'glory-ab'); }
            if ($s === 'medium') { $label = __('medium', 'glory-ab'); }
            if ($s === 'medium_large') { $label = __('medium_large', 'glory-ab'); }
            if ($s === 'large') { $label = __('large', 'glory-ab'); }
            if ($s === 'full') { $label = __('full', 'glory-ab'); }
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


