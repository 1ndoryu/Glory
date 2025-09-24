<?php

namespace Glory\Integration\Avada\Elements\GloryContentRender;

class GloryContentRenderRegistrar
{
    private static $dedupActive = false;
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

        if ( ! class_exists('FusionSC_GloryContentRender') ) {
            $elementPath = get_template_directory() . '/Glory/src/Integration/Avada/Elements/GloryContentRender/FusionSC_GloryContentRender.php';
            $childPath   = get_stylesheet_directory() . '/Glory/src/Integration/Avada/Elements/GloryContentRender/FusionSC_GloryContentRender.php';
            if ( is_readable($childPath) ) {
                require_once $childPath;
            } elseif ( is_readable($elementPath) ) {
                require_once $elementPath;
            }
        }

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

            // Grupo Diseño / Layout
            [ 'type' => 'radio_button_set', 'heading' => __('Display', 'glory-ab'), 'param_name' => 'display_mode', 'default' => 'flex', 'value' => [ 'flex' => 'Flex', 'grid' => 'Grid', 'block' => 'Block' ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'carousel', 'value' => 'yes', 'operator' => '!=' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Dirección (flex-direction)', 'glory-ab'), 'param_name' => 'flex_direction', 'default' => 'row', 'value' => [ 'row' => 'row', 'column' => 'column' ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'flex', 'operator' => '==' ], [ 'element' => 'carousel', 'value' => 'yes', 'operator' => '!=' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Flex wrap', 'glory-ab'), 'param_name' => 'flex_wrap', 'default' => 'wrap', 'value' => [ 'nowrap' => 'nowrap', 'wrap' => 'wrap' ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'flex', 'operator' => '==' ], [ 'element' => 'carousel', 'value' => 'yes', 'operator' => '!=' ] ] ],
            [ 'type' => 'textfield', 'heading' => __('Gap', 'glory-ab'), 'param_name' => 'gap', 'default' => '20px', 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'select', 'heading' => __('Align items', 'glory-ab'), 'param_name' => 'align_items', 'default' => 'stretch', 'value' => [ 'stretch'=>'stretch','flex-start'=>'flex-start','center'=>'center','flex-end'=>'flex-end' ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'flex', 'operator' => '==' ] ] ],
            [ 'type' => 'select', 'heading' => __('Justify content', 'glory-ab'), 'param_name' => 'justify_content', 'default' => 'flex-start', 'value' => [ 'flex-start'=>'flex-start','center'=>'center','space-between'=>'space-between','space-around'=>'space-around','flex-end'=>'flex-end' ], 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Grid min-width', 'glory-ab'), 'param_name' => 'grid_min_width', 'default' => '250px', 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'carousel', 'value' => 'yes', 'operator' => '!=' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Grid auto-fit', 'glory-ab'), 'param_name' => 'grid_auto_fit', 'default' => 'yes', 'value' => [ 'yes' => 'auto-fit', 'no' => 'auto-fill' ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'carousel', 'value' => 'yes', 'operator' => '!=' ] ] ],
            [ 'type' => 'radio_button_set', 'heading' => __('Modo de columnas', 'glory-ab'), 'param_name' => 'grid_columns_mode', 'default' => 'fixed', 'value' => [ 'fixed' => __('Fijas','glory-ab'), 'auto' => __('Auto (mín/máx)','glory-ab') ], 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'carousel', 'value' => 'yes', 'operator' => '!=' ] ], 'description' => __('Selecciona si las columnas son fijas o automáticas por rango.', 'glory-ab') ],
            [ 'type' => 'range', 'heading' => __('Columnas', 'glory-ab'), 'param_name' => 'grid_columns', 'value' => [ 'large' => 4, 'medium' => '', 'small' => '' ], 'min' => 1, 'max' => 12, 'step' => 1, 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => true ], 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'grid_columns_mode', 'value' => 'fixed', 'operator' => '==' ], [ 'element' => 'carousel', 'value' => 'yes', 'operator' => '!=' ] ], 'description' => __('Número de columnas exactas por dispositivo (usar toggle responsive).', 'glory-ab') ],
            [ 'type' => 'range', 'heading' => __('Mínimo columnas', 'glory-ab'), 'param_name' => 'grid_min_columns', 'value' => [ 'large' => 1, 'medium' => '', 'small' => '' ], 'min' => 1, 'max' => 12, 'step' => 1, 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => true ], 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'grid_columns_mode', 'value' => 'auto', 'operator' => '==' ], [ 'element' => 'carousel', 'value' => 'yes', 'operator' => '!=' ] ], 'description' => __('Mínimo de columnas deseado por dispositivo (toggle responsive).', 'glory-ab') ],
            [ 'type' => 'range', 'heading' => __('Máximo columnas', 'glory-ab'), 'param_name' => 'grid_max_columns', 'value' => [ 'large' => 12, 'medium' => '', 'small' => '' ], 'min' => 1, 'max' => 12, 'step' => 1, 'group' => __('Diseño', 'glory-ab'), 'responsive' => [ 'state' => 'large', 'default_value' => true ], 'dependency' => [ [ 'element' => 'display_mode', 'value' => 'grid', 'operator' => '==' ], [ 'element' => 'grid_columns_mode', 'value' => 'auto', 'operator' => '==' ], [ 'element' => 'carousel', 'value' => 'yes', 'operator' => '!=' ] ], 'description' => __('Máximo de columnas permitidas por dispositivo (toggle responsive).', 'glory-ab') ],

            // Carrusel
            [ 'type' => 'radio_button_set', 'heading' => __('Carrusel', 'glory-ab'), 'param_name' => 'carousel', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Diseño', 'glory-ab') ],
            [ 'type' => 'range', 'heading' => __('Velocidad carrusel (px/s)', 'glory-ab'), 'param_name' => 'carousel_speed', 'default' => 20, 'min' => 1, 'max' => 200, 'step' => 1, 'group' => __('Diseño', 'glory-ab'), 'dependency' => [ [ 'element' => 'carousel', 'value' => 'yes', 'operator' => '==' ] ] ],

            // Imagen
            [ 'type' => 'radio_button_set', 'heading' => __('Mostrar imagen', 'glory-ab'), 'param_name' => 'img_show', 'default' => 'yes', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Imagen', 'glory-ab') ],
            [ 'type' => 'select', 'heading' => __('Tamaño de imagen', 'glory-ab'), 'param_name' => 'img_size', 'default' => 'medium', 'value' => self::discoverImageSizes(), 'group' => __('Imagen', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Aspect ratio', 'glory-ab'), 'param_name' => 'img_aspect_ratio', 'default' => '1 / 1', 'group' => __('Imagen', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Object fit', 'glory-ab'), 'param_name' => 'img_object_fit', 'default' => 'cover', 'value' => [ 'cover'=>'cover','contain'=>'contain' ], 'group' => __('Imagen', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Min width', 'glory-ab'), 'param_name' => 'img_min_width', 'default' => '', 'group' => __('Imagen', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Width', 'glory-ab'), 'param_name' => 'img_width', 'default' => '', 'group' => __('Imagen', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Max width', 'glory-ab'), 'param_name' => 'img_max_width', 'default' => '', 'group' => __('Imagen', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Min height', 'glory-ab'), 'param_name' => 'img_min_height', 'default' => '', 'group' => __('Imagen', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Height', 'glory-ab'), 'param_name' => 'img_height', 'default' => '', 'group' => __('Imagen', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Max height', 'glory-ab'), 'param_name' => 'img_max_height', 'default' => '', 'group' => __('Imagen', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Optimizar imagen', 'glory-ab'), 'param_name' => 'img_optimize', 'default' => 'yes', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Imagen', 'glory-ab') ],
            [ 'type' => 'range', 'heading' => __('Calidad de imagen', 'glory-ab'), 'param_name' => 'img_quality', 'default' => 60, 'min' => 10, 'max' => 100, 'step' => 1, 'group' => __('Imagen', 'glory-ab') ],

            // Título
            [ 'type' => 'radio_button_set', 'heading' => __('Mostrar título', 'glory-ab'), 'param_name' => 'title_show', 'default' => 'yes', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Título', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Fuente (font-family)', 'glory-ab'), 'param_name' => 'title_font_family', 'default' => '', 'group' => __('Título', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Tamaño de fuente', 'glory-ab'), 'param_name' => 'title_font_size', 'default' => '', 'group' => __('Título', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Peso (font-weight)', 'glory-ab'), 'param_name' => 'title_font_weight', 'default' => '', 'group' => __('Título', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Transformación de texto', 'glory-ab'), 'param_name' => 'title_text_transform', 'default' => '', 'value' => [ ''=>'Ninguno','uppercase'=>'uppercase','capitalize'=>'capitalize','lowercase'=>'lowercase' ], 'group' => __('Título', 'glory-ab') ],
            [ 'type' => 'textfield', 'heading' => __('Ancho máximo del título', 'glory-ab'), 'param_name' => 'title_max_width', 'default' => '', 'description' => __('Ej.: 200px, 30ch, 80%', 'glory-ab'), 'group' => __('Título', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Mostrar título solo en hover', 'glory-ab'), 'param_name' => 'title_show_on_hover', 'default' => 'no', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('Título', 'glory-ab') ],
            [ 'type' => 'radio_button_set', 'heading' => __('Posición del título', 'glory-ab'), 'param_name' => 'title_position', 'default' => 'top', 'value' => [ 'top' => __('Arriba','glory-ab'), 'bottom' => __('Abajo','glory-ab') ], 'group' => __('Título', 'glory-ab') ],

            // Enlace
            [ 'type' => 'radio_button_set', 'heading' => __('Habilitar enlace', 'glory-ab'), 'param_name' => 'link_enabled', 'default' => 'yes', 'value' => [ 'yes' => __('Sí','glory-ab'), 'no' => __('No','glory-ab') ], 'group' => __('General', 'glory-ab') ],
        ];

        if ( function_exists('fusion_builder_frontend_data') ) {
            fusion_builder_map(
                fusion_builder_frontend_data(
                    'FusionSC_GloryContentRender',
                    [ 'name' => __('Glory Content Render','glory-ab'), 'shortcode' => 'glory_content_render', 'icon' => 'fusiona-blog', 'params' => $params ]
                )
            );
        } else {
            fusion_builder_map([ 'name' => __('Glory Content Render','glory-ab'), 'shortcode' => 'glory_content_render', 'icon' => 'fusiona-blog', 'params' => $params ]);
        }
    }

    public static function ensureShortcode(): void
    {
        if ( function_exists('shortcode_exists') && shortcode_exists('glory_content_render') ) {
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

        if ( ! shortcode_exists('glory_content_render') ) {
            add_shortcode('glory_content_render', function($atts, $content = '') {
                $defaults = [
                    'post_type'                => 'post',
                    'template_id'              => '__default',
                    'usar_content_render'      => 'yes',
                    'publicaciones_por_pagina' => 10,
                    'clase_contenedor'         => 'glory-content-list',
                    'clase_item'               => 'glory-content-item',
                    'paginacion'               => '',
                    'orden'                    => 'fecha',
                    'meta_key'                 => '',
                    'meta_order'               => 'ASC',
                    'min_paginas'              => 1,
                    'tiempo_cache'             => 3600,
                    'forzar_sin_cache'         => '',
                    'acciones'                 => '',
                    'submenu'                  => '',
                    'evento_accion'            => 'dblclick',
                    'selector_item'            => '[id^="post-"]',
                    'argumentos_json'          => '',
                    'post_ids'                 => '',
                    'post_ids_select'          => '',
                    'titulo'                   => '',
                    'function_name'            => '',
                    'shortcode'                => '',
                    'raw_content'              => '',
                    // Opciones de diseño
                    'display_mode'             => 'flex',
                    'flex_direction'           => 'row',
                    'flex_wrap'                => 'wrap',
                    'gap'                      => '20px',
                    'align_items'              => 'stretch',
                    'justify_content'          => 'flex-start',
                    'grid_min_width'           => '250px',
                    'grid_auto_fit'            => 'yes',
                    'grid_columns_mode'        => 'fixed',
                    'grid_columns'             => 4,
                    'grid_columns_medium'      => '',
                    'grid_columns_small'       => '',
                    'grid_min_columns'         => 1,
                    'grid_min_columns_medium'  => '',
                    'grid_min_columns_small'   => '',
                    'grid_max_columns'         => 12,
                    'grid_max_columns_medium'  => '',
                    'grid_max_columns_small'   => '',
                    // Carrusel
                    'carousel'                 => 'no',
                    'carousel_speed'           => 20,
                    // Imagen
                    'img_show'                 => 'yes',
                    'img_aspect_ratio'         => '1 / 1',
                    'img_object_fit'           => 'cover',
                    'img_size'                 => 'medium',
                    'img_min_width'            => '',
                    'img_width'                => '',
                    'img_max_width'            => '',
                    'img_min_height'           => '',
                    'img_height'               => '',
                    'img_max_height'           => '',
                    'img_optimize'             => 'yes',
                    'img_quality'              => 60,
                    // Título
                    'title_show'               => 'yes',
                    'title_font_family'        => '',
                    'title_font_size'          => '',
                    'title_font_weight'        => '',
                    'title_text_transform'     => '',
                    'title_max_width'          => '',
                    'title_show_on_hover'      => 'no',
                    'title_position'           => 'top',
                    // Enlace
                    'link_enabled'             => 'yes',
                ];
                $a = shortcode_atts($defaults, $atts, 'glory_content_render');

                $html = '';
                if (!empty($a['titulo'])) {
                    $html .= '<h3>' . esc_html($a['titulo']) . '</h3>';
                }

                $usarCR = isset($a['usar_content_render']) && 'yes' === $a['usar_content_render'];
                $postType = sanitize_key($a['post_type'] ?? 'post');

                if ($usarCR && class_exists('\\Glory\\Components\\ContentRender')) {
                    $callable = ['\\Glory\\Components\\ContentRender', 'defaultTemplate'];
                    $templateId = (string) ($a['template_id'] ?? '__default');
                    if ($templateId !== '' && $templateId !== '__default' && class_exists('\\Glory\\Utility\\TemplateRegistry')) {
                        $applies = call_user_func(['\\Glory\\Utility\\TemplateRegistry', 'appliesTo'], $templateId);
                        if (empty($applies) || in_array($postType, (array) $applies, true)) {
                            $c = call_user_func(['\\Glory\\Utility\\TemplateRegistry', 'get'], $templateId);
                            if (is_callable($c)) {
                                $callable = $c;
                            }
                        }
                    }

                    $argumentosConsulta = [];
                    if (!empty($a['argumentos_json'])) {
                        $parsed = json_decode((string) $a['argumentos_json'], true);
                        if (is_array($parsed)) {
                            $argumentosConsulta = $parsed;
                        }
                    }

                    $orden = (string) ($a['orden'] ?? 'fecha');
                    $metaKey = trim((string) ($a['meta_key'] ?? ''));
                    $metaOrder = strtoupper((string) ($a['meta_order'] ?? 'ASC'));

                    $instanceClass = 'glory-cr-' . substr(md5(uniqid('', true)), 0, 8);
                    $config = [
                        'publicacionesPorPagina' => (int) ($a['publicaciones_por_pagina'] ?? 10),
                        'claseContenedor'        => trim((string) ($a['clase_contenedor'] ?? 'glory-content-list') . ' ' . $instanceClass),
                        'claseItem'              => trim((string) ($a['clase_item'] ?? 'glory-content-item') . ' ' . $instanceClass . '__item'),
                        'paginacion'             => ('yes' === ($a['paginacion'] ?? '')),
                        'plantillaCallback'      => $callable,
                        'argumentosConsulta'     => $argumentosConsulta,
                        'orden'                  => ('meta' === $orden && '' !== $metaKey) ? $metaOrder : ( 'random' === $orden ? 'random' : 'fecha' ),
                        'metaKey'                => '' !== $metaKey ? $metaKey : null,
                        'minPaginas'             => (int) ($a['min_paginas'] ?? 1),
                        'tiempoCache'            => (int) ($a['tiempo_cache'] ?? 3600),
                        'forzarSinCache'         => ('yes' === ($a['forzar_sin_cache'] ?? '')),
                        'acciones'               => !empty($a['acciones']) ? array_map('trim', explode(',', (string) $a['acciones'])) : [],
                        'submenu'                => ('yes' === ($a['submenu'] ?? '')),
                        'eventoAccion'           => (string) ($a['evento_accion'] ?? 'dblclick'),
                        'selectorItem'           => (string) ($a['selector_item'] ?? '[id^="post-"]'),
                        // Imagen: optimización/calidad
                        'imgOptimize'            => ('yes' === ($a['img_optimize'] ?? 'yes')),
                        'imgQuality'             => (int) ($a['img_quality'] ?? 60),
                        'imgSize'                => (string) ($a['img_size'] ?? 'medium'),
                    ];
                    // Aplicar post__in si se especifican IDs
                    $ids = [];
                    if (!empty($a['post_ids'])) {
                        $ids = array_filter(array_map('absint', array_map('trim', explode(',', (string) $a['post_ids']))));
                    }
                    // Combinar con selección múltiple (IDs vienen como array o CSV)
                    if (!empty($a['post_ids_select'])) {
                        $selected = is_array($a['post_ids_select']) ? $a['post_ids_select'] : explode(',', (string) $a['post_ids_select']);
                        $selected = array_filter(array_map('absint', array_map('trim', $selected)));
                        $ids = array_values(array_unique(array_merge($ids, $selected)));
                    }
                    if (!empty($ids)) {
                        $argumentosConsulta['post__in'] = $ids;
                        $argumentosConsulta['orderby'] = 'post__in';
                        $config['argumentosConsulta'] = $argumentosConsulta;
                    }

                    ob_start();
                    try {
                        self::enableDedupFilters();
                        call_user_func(['\\Glory\\Components\\ContentRender', 'print'], $postType, $config);
                    } catch (\Throwable $t) {
                        if ( function_exists('current_user_can') && current_user_can('manage_options') ) {
                            echo '<div class="glory-widget-error">' . esc_html($t->getMessage()) . '</div>';
                        }
                    } finally {
                        self::disableDedupFilters();
                    }
                    $buffer = ob_get_clean();
                    $css = self::buildInstanceCss($instanceClass, $a);
                    if ($css !== '') {
                        $buffer .= '<style id="' . esc_attr($instanceClass) . '-css">' . $css . '</style>';
                    }
                    // Stop si carrusel OFF
                    $selector = '.' . $instanceClass;
                    $isCarousel = (string)($a['carousel'] ?? 'no') === 'yes';
                    if (!$isCarousel) {
                        $buffer .= '<script>window.GloryCarousel&&window.GloryCarousel.stop(' . wp_json_encode($selector) . ');</script>';
                    }
                    // Init si carrusel ON
                    if ($isCarousel) {
                        $speed = (float)($a['carousel_speed'] ?? 20);
                        $buffer .= '<script>window.GloryCarousel&&window.GloryCarousel.init(' . wp_json_encode($selector) . ',{"speed":' . json_encode($speed) . '});</script>';
                    }
                    // Desactivar enlaces si corresponde
                    $isLinkEnabled = (string)($a['link_enabled'] ?? 'yes') === 'yes';
                    if (!$isLinkEnabled) {
                        $buffer .= '<script>(function(){var s=' . wp_json_encode($selector) . ';function prevent(e){var a=e.target.closest("a");if(a&&a.closest(s)){e.preventDefault();e.stopPropagation();}}document.addEventListener("click",prevent,true);document.addEventListener("keydown",function(e){if((e.key||e.keyCode)===\"Enter\"||e.keyCode===13){prevent(e);}},true);})();</script>';
                    }
                    return $html . $buffer;
                }

                // Fallbacks
                if (!empty($a['function_name']) && function_exists($a['function_name'])) {
                    ob_start();
                    call_user_func($a['function_name']);
                    return $html . ob_get_clean();
                }
                if (!empty($a['shortcode'])) {
                    return $html . do_shortcode($a['shortcode']);
                }
                return $html . (string) ($a['raw_content'] ?? '');
            });
        }
    }

    private static function buildInstanceCss(string $instanceClass, array $a): string
    {
        $containerClass = '.' . $instanceClass;
        $itemClass = '.' . $instanceClass . '__item';
        $selector_item = (string) ($a['selector_item'] ?? '');
        $scopedSelector = trim($selector_item) !== '' ? $containerClass . ' ' . trim($selector_item) : '';

        $display_mode = (string) ($a['display_mode'] ?? 'flex');
        $flex_direction = (string) ($a['flex_direction'] ?? 'row');
        $flex_wrap = (string) ($a['flex_wrap'] ?? 'wrap');
        $gap = (string) ($a['gap'] ?? '20px');
        $align_items = (string) ($a['align_items'] ?? 'stretch');
        $justify_content = (string) ($a['justify_content'] ?? 'flex-start');
        $grid_min_width = (string) ($a['grid_min_width'] ?? '250px');
        $grid_auto_fit = ((string) ($a['grid_auto_fit'] ?? 'yes')) === 'yes' ? 'auto-fit' : 'auto-fill';
        $mode = (string) ($a['grid_columns_mode'] ?? 'fixed');

        $large_cols = (int) ($a['grid_columns'] ?? 4);
        $medium_cols = isset($a['grid_columns_medium']) && '' !== (string) $a['grid_columns_medium'] ? (int) $a['grid_columns_medium'] : $large_cols;
        $small_cols  = isset($a['grid_columns_small']) && '' !== (string) $a['grid_columns_small'] ? (int) $a['grid_columns_small'] : $medium_cols;

        $min_large = (int) ($a['grid_min_columns'] ?? 1);
        $max_large = (int) ($a['grid_max_columns'] ?? 12);
        $min_medium = isset($a['grid_min_columns_medium']) && '' !== (string) $a['grid_min_columns_medium'] ? (int) $a['grid_min_columns_medium'] : $min_large;
        $max_medium = isset($a['grid_max_columns_medium']) && '' !== (string) $a['grid_max_columns_medium'] ? (int) $a['grid_max_columns_medium'] : $max_large;
        $min_small = isset($a['grid_min_columns_small']) && '' !== (string) $a['grid_min_columns_small'] ? (int) $a['grid_min_columns_small'] : $min_medium;
        $max_small = isset($a['grid_max_columns_small']) && '' !== (string) $a['grid_max_columns_small'] ? (int) $a['grid_max_columns_small'] : $max_medium;

        // Forzar layout si carrusel
        $is_carousel = (string) ($a['carousel'] ?? 'no') === 'yes';
        if ($is_carousel) {
            $display_mode = 'flex';
            $flex_direction = 'row';
            $flex_wrap = 'nowrap';
        }

        $img_show = !isset($a['img_show']) || (string) $a['img_show'] === 'yes';
        $img_aspect_ratio = (string) ($a['img_aspect_ratio'] ?? '1 / 1');
        $img_object_fit = (string) ($a['img_object_fit'] ?? 'cover');
        $img_min_width = (string) ($a['img_min_width'] ?? '');
        $img_width = (string) ($a['img_width'] ?? '');
        $img_max_width = (string) ($a['img_max_width'] ?? '');
        $img_min_height = (string) ($a['img_min_height'] ?? '');
        $img_height = (string) ($a['img_height'] ?? '');
        $img_max_height = (string) ($a['img_max_height'] ?? '');

        $title_show = (string) ($a['title_show'] ?? 'yes') === 'yes';
        $title_font_family = (string) ($a['title_font_family'] ?? '');
        $title_font_size = (string) ($a['title_font_size'] ?? '');
        $title_font_weight = (string) ($a['title_font_weight'] ?? '');
        $title_text_transform = (string) ($a['title_text_transform'] ?? '');

        $css = '';
        if ($display_mode === 'grid') {
            $css .= $containerClass . '{display:grid;gap:' . $gap . ';grid-template-columns:repeat(' . $grid_auto_fit . ', minmax(' . $grid_min_width . ', 1fr));}';
            if ($mode === 'fixed') {
                $css .= '@media (min-width: 980px) {' . $containerClass . '{grid-template-columns:repeat(' . $large_cols . ', 1fr);}}';
                $css .= '@media (min-width: 768px) and (max-width: 979px) {' . $containerClass . '{grid-template-columns:repeat(' . $medium_cols . ', 1fr);}}';
                $css .= '@media (max-width: 767px) {' . $containerClass . '{grid-template-columns:repeat(' . $small_cols . ', 1fr);}}';
            } else {
                $min_size_l = 'max(' . $grid_min_width . ', calc(100% / ' . $max_large . '))';
                $max_size_l = 'min(1fr, calc(100% / ' . $min_large . '))';
                $css .= '@media (min-width: 980px) {' . $containerClass . '{grid-template-columns:repeat(' . $grid_auto_fit . ', minmax(' . $min_size_l . ', ' . $max_size_l . '));}}';
                $min_size_m = 'max(' . $grid_min_width . ', calc(100% / ' . $max_medium . '))';
                $max_size_m = 'min(1fr, calc(100% / ' . $min_medium . '))';
                $css .= '@media (min-width: 768px) and (max-width: 979px) {' . $containerClass . '{grid-template-columns:repeat(' . $grid_auto_fit . ', minmax(' . $min_size_m . ', ' . $max_size_m . '));}}';
                $min_size_s = 'max(' . $grid_min_width . ', calc(100% / ' . $max_small . '))';
                $max_size_s = 'min(1fr, calc(100% / ' . $min_small . '))';
                $css .= '@media (max-width: 767px) {' . $containerClass . '{grid-template-columns:repeat(' . $grid_auto_fit . ', minmax(' . $min_size_s . ', ' . $max_size_s . '));}}';
            }
        } elseif ($display_mode === 'flex') {
            $css .= $containerClass . '{display:flex;flex-direction:' . $flex_direction . ';flex-wrap:' . $flex_wrap . ';gap:' . $gap . ';align-items:' . $align_items . ';justify-content:' . $justify_content . ';}';
        } else {
            $css .= $containerClass . '{display:block;}';
        }

        // En carrusel no forzamos width:100% para no alterar tamaños
        $css .= $containerClass . ' .glory-cr__image,' . $itemClass . ' .glory-cr__image{display:' . ($img_show ? 'block' : 'none') . ';aspect-ratio:' . $img_aspect_ratio . ';object-fit:' . $img_object_fit . ';max-width:100%;' . ( $img_width !== '' ? 'width:' . $img_width . ';' : ( $is_carousel ? '' : 'width:100%;' ) );
        if ($img_min_width !== '') { $css .= 'min-width:' . $img_min_width . ';'; }
        if ($img_max_width !== '') { $css .= 'max-width:' . $img_max_width . ';'; }
        if ($img_height !== '') { $css .= 'height:' . $img_height . ';'; } else { $css .= 'height:auto;'; }
        if ($img_min_height !== '') { $css .= 'min-height:' . $img_min_height . ';'; }
        if ($img_max_height !== '') { $css .= 'max-height:' . $img_max_height . ';'; }
        $css .= '}';

        if ($is_carousel) {
            $css .= $itemClass . '{flex:0 0 auto;}';
            $css .= $containerClass . '{width:max-content;}';
        }

        // Estilos de título base
        $css .= $containerClass . ' .glory-cr__title,' . $itemClass . ' .glory-cr__title{display:' . ( $title_show ? 'block' : 'none' ) . ';';
        if ($title_font_family !== '') { $css .= 'font-family:' . $title_font_family . ';'; }
        if ($title_font_size !== '') { $css .= 'font-size:' . $title_font_size . ';'; }
        if ($title_font_weight !== '') { $css .= 'font-weight:' . $title_font_weight . ';'; }
        if ($title_text_transform !== '') { $css .= 'text-transform:' . $title_text_transform . ';'; }
        if (!empty($a['title_max_width'])) { $css .= 'max-width:' . (string) $a['title_max_width'] . ';'; }
        $css .= '}';

        // Título solo en hover (opcional)
        $title_show_on_hover = (string) ($a['title_show_on_hover'] ?? 'no') === 'yes';
        if ($title_show_on_hover) {
            $hideSelectors = $containerClass . ' .glory-cr__title,' . $containerClass . ' .entry-title,' . $containerClass . ' .fusion-post-title,'
                . $itemClass . ' .glory-cr__title,' . $itemClass . ' .entry-title,' . $itemClass . ' .fusion-post-title';
            if ($scopedSelector !== '') {
                $hideSelectors .= ',' . $scopedSelector . ' .glory-cr__title,' . $scopedSelector . ' .entry-title,' . $scopedSelector . ' .fusion-post-title';
            }
            $css .= $hideSelectors . '{opacity:0;visibility:hidden;transition:opacity .2s ease;}';

            $css .= $itemClass . '.is-hover .glory-cr__title,' . $itemClass . '.is-hover .entry-title,' . $itemClass . '.is-hover .fusion-post-title{opacity:1;visibility:visible;}';
            $css .= $itemClass . ':hover .glory-cr__title,' . $itemClass . ':focus-within .glory-cr__title,'
                . $itemClass . ':hover .entry-title,' . $itemClass . ':focus-within .entry-title,'
                . $itemClass . ':hover .fusion-post-title,' . $itemClass . ':focus-within .fusion-post-title{opacity:1;visibility:visible;}';
            if ($scopedSelector !== '') {
                $css .= $scopedSelector . '.is-hover .glory-cr__title,' . $scopedSelector . '.is-hover .entry-title,' . $scopedSelector . '.is-hover .fusion-post-title{opacity:1;visibility:visible;}';
                $css .= $scopedSelector . ':hover .glory-cr__title,' . $scopedSelector . ':focus-within .glory-cr__title,'
                    . $scopedSelector . ':hover .entry-title,' . $scopedSelector . ':focus-within .entry-title,'
                    . $scopedSelector . ':hover .fusion-post-title,' . $scopedSelector . ':focus-within .fusion-post-title{opacity:1;visibility:visible;}';
            }
            // Mostrar cuando se hace hover directamente sobre la imagen o su enlace
            $css .= $itemClass . ' .glory-cr__image:hover + .glory-cr__title,' . $itemClass . ' .glory-cr__image:hover ~ .glory-cr__title{opacity:1;visibility:visible;}';
            $css .= $itemClass . ' a:hover .glory-cr__image + .glory-cr__title,' . $itemClass . ' a:hover .glory-cr__image ~ .glory-cr__title{opacity:1;visibility:visible;}';
            if ($scopedSelector !== '') {
                $css .= $scopedSelector . ' .glory-cr__image:hover + .glory-cr__title,' . $scopedSelector . ' .glory-cr__image:hover ~ .glory-cr__title{opacity:1;visibility:visible;}';
                $css .= $scopedSelector . ' a:hover .glory-cr__image + .glory-cr__title,' . $scopedSelector . ' a:hover .glory-cr__image ~ .glory-cr__title{opacity:1;visibility:visible;}';
            }
        }

        // Posición del título respecto a la imagen dentro de un contenedor de pila
        $title_position = (string) ($a['title_position'] ?? 'top');
        $css .= $itemClass . ' .glory-cr__stack{display:flex;flex-direction:column;}';
        $stackTitleSel = $itemClass . ' .glory-cr__stack .glory-cr__title,'
            . $itemClass . ' .glory-cr__stack .entry-title,'
            . $itemClass . ' .glory-cr__stack .fusion-post-title,'
            . $itemClass . ' .glory-cr__stack .portafolio-info';
        $stackImageSel = $itemClass . ' .glory-cr__stack .glory-cr__image';
        if ($title_position === 'bottom') {
            $css .= $stackImageSel . '{order:1;}';
            $css .= $stackTitleSel . '{order:2;}';
        } else {
            $css .= $stackTitleSel . '{order:1;}';
            $css .= $stackImageSel . '{order:2;}';
        }

        return $css;
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

    private static function enableDedupFilters(): void
    {
        if ( self::$dedupActive ) {
            return;
        }
        self::$dedupActive = true;
        add_filter('posts_distinct', [self::class, 'filterPostsDistinct'], 10, 2);
        add_filter('the_posts', [self::class, 'filterThePostsDedup'], 10, 2);
    }

    private static function disableDedupFilters(): void
    {
        if ( ! self::$dedupActive ) {
            return;
        }
        remove_filter('posts_distinct', [self::class, 'filterPostsDistinct'], 10);
        remove_filter('the_posts', [self::class, 'filterThePostsDedup'], 10);
        self::$dedupActive = false;
    }

    public static function filterPostsDistinct($distinct, $query)
    {
        if ( self::$dedupActive ) {
            return 'DISTINCT';
        }
        return $distinct;
    }

    public static function filterThePostsDedup($posts, $query)
    {
        if ( ! self::$dedupActive || ! is_array($posts) ) {
            return $posts;
        }
        $seen = [];
        $deduped = [];
        foreach ($posts as $post) {
            $id = is_object($post) && isset($post->ID) ? (int) $post->ID : (int) $post;
            if ($id && ! isset($seen[$id])) {
                $seen[$id] = true;
                $deduped[] = $post;
            }
        }
        return $deduped;
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






