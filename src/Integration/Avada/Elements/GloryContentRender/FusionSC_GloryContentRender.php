<?php

// Clase global sin namespace para ajustarse al patrón de Avada.

if (! class_exists('FusionSC_GloryContentRender') && class_exists('Fusion_Element')) {

    class FusionSC_GloryContentRender extends Fusion_Element
    {

        private $counter = 1;
        private $dedupActive = false;
        private $currentTemplateSupports = [];
        private $currentModoInteraccion = 'normal';
        private $currentInstanceConfig = [];
        private $currentInstanceClass = '';

        public function __construct()
        {
            parent::__construct();
            add_shortcode('glory_content_render', [$this, 'render']);
        }

        public static function get_element_defaults()
        {
            return [
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
                // Interacción
                'interaccion_modo'         => 'normal', // normal | carousel | toggle
                'carousel_speed'           => 20,
                'toggle_separator'         => 'no',
                'toggle_separator_color'   => 'rgba(0,0,0,0.1)',
                'toggle_auto_open'         => '',
                'toggle_default_state'     => 'collapsed',
                // Layout (por instancia)
                'display_mode'             => 'flex', // flex | grid | block
                'flex_direction'           => 'row',
                'flex_wrap'                => 'wrap',
                'gap'                      => '20px',
                'align_items'              => 'stretch',
                'justify_content'          => 'flex-start',
                'grid_min_width'           => '250px',
                'grid_auto_fit'            => 'yes', // yes => auto-fit, no => auto-fill
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
				// Grid rotate effect
				'grid_rotate'              => 'no',
				'grid_rotate_interval'     => 3000,
				'grid_rotate_fade'         => 400,
				'grid_rotate_offset'       => '10px',
                // Imagen (por instancia)
                'img_show'                 => 'yes',
                'img_aspect_ratio'         => '1 / 1',
                'img_object_fit'           => 'cover',
                'img_size'                 => 'medium',
                'img_min_width'            => '',
                'img_width'                => '',
                'img_max_width'            => '',
                'img_height'               => '',
                'img_min_height'           => '',
                'img_max_height'           => '',
                // Imagen responsive
                'img_min_width_medium'     => '',
                'img_width_medium'         => '',
                'img_max_width_medium'     => '',
                'img_min_height_medium'    => '',
                'img_height_medium'        => '',
                'img_max_height_medium'    => '',
                'img_min_width_small'      => '',
                'img_width_small'          => '',
                'img_max_width_small'      => '',
                'img_min_height_small'     => '',
                'img_height_small'         => '',
                'img_max_height_small'     => '',
                'img_optimize'             => 'yes',
                'img_quality'              => 60,
                // Título (por instancia)
                'title_show'                     => 'yes',
                'title_bold'                     => 'no',
                'fusion_font_family_title_font'  => '',
                'fusion_font_variant_title_font' => '',
                'font_size'                      => '',
                'line_height'                    => '',
                'letter_spacing'                 => '',
                'font_size_medium'               => '',
                'font_size_small'                => '',
                'line_height_medium'             => '',
                'line_height_small'              => '',
                'letter_spacing_medium'          => '',
                'letter_spacing_small'           => '',
                'title_text_transform'           => '',
                'title_min_width'                => '',
                'title_width'                    => '',
                'title_max_width'                => '',
                'title_margin_top'               => '',
                'title_margin_bottom'            => '',
                // Ancho del contenido (.glory-cr__content)
                'content_min_width'              => '',
                'content_width'                  => '',
                'content_max_width'              => '',
                // Opciones específicas de plantilla (Posts)
                'mostrar_contenido'              => 'yes',
                'mostrar_fecha'                  => 'yes',
                'contenido_max_palabras'         => '55',
                'title_show_on_hover'            => 'no',
                'title_position'                 => 'top', // top | bottom
				// Color del título
				'title_color'                    => '',
                'content_opacity'                => 0.9,
				// Patrón de layout alternado
				'layout_pattern'                 => 'none', // responsive: large/medium/small
			'pattern_small_width_percent'    => 40,
			'pattern_row_gap'                 => '40px',
				'pattern_small_width_percent_medium' => '',
				'pattern_small_width_percent_small'  => '',
				'pattern_large_width_percent'    => 60,
				'pattern_large_width_percent_medium' => '',
				'pattern_large_width_percent_small'  => '',
                'pattern_lr_align_text'          => 'yes',
                'pattern_lr_vertical_align'      => 'start',
                'pattern_lr_split_mode'           => 'no',
                // Filtro por categorías
                'category_filter_enable'        => 'no',
                'category_filter_all_label'     => 'All',
                'category_filter_gap'           => '12px',
                'category_filter_margin_top'    => '0px',
                'category_filter_margin_bottom' => '20px',
                'category_filter_justify'       => 'center',
                'category_filter_text_align'    => 'center',
                'category_filter_padding'       => '',
                'category_filter_border_radius' => '',
                'category_filter_typography_enable' => 'no',
                'category_filter_text_color'    => '',
                'category_filter_active_text_color' => '',
                'category_filter_background'    => '',
                'category_filter_active_background' => '',
                'category_filter_border_color'  => '',
                'category_filter_active_border_color' => '',
                'category_filter_border_width'  => '',
                'fusion_font_family_category_filter_font' => '',
                'fusion_font_variant_category_filter_font'  => '',
                'category_filter_font_size'     => '',
                'category_filter_line_height'   => '',
                'category_filter_letter_spacing'=> '',
                'category_filter_text_transform'=> '',
                // Tipografía interna (por instancia) - mismas opciones del título pero separadas
                'internal_typography_enable'           => 'no',
                'internal_bold'                        => 'no',
                'fusion_font_family_internal_font'     => '',
                'fusion_font_variant_internal_font'    => '',
                'internal_font_size'                   => '',
                'internal_line_height'                 => '',
                'internal_letter_spacing'              => '',
                'internal_font_size_medium'            => '',
                'internal_font_size_small'             => '',
                'internal_line_height_medium'          => '',
                'internal_line_height_small'           => '',
                'internal_letter_spacing_medium'       => '',
                'internal_letter_spacing_small'        => '',
                'internal_text_transform'              => '',
                // Meta (fecha, etc.)
                'post_meta_typography_enable'          => 'no',
                'fusion_font_family_post_meta_font'    => '',
                'fusion_font_variant_post_meta_font'   => '',
                'post_meta_font_size'                  => '',
                'post_meta_line_height'                => '',
                'post_meta_letter_spacing'             => '',
                'post_meta_color'                      => '',
                'post_meta_text_transform'             => '',
                // Enlace
                'link_enabled'             => 'yes',
                'internal_display_mode'    => '',
                'internal_flex_direction'  => '',
                'internal_flex_wrap'       => '',
                'internal_gap'             => '',
                'internal_align_items'     => '',
                'internal_justify_content' => '',
                'internal_grid_min_width'           => '',
                'internal_grid_auto_fit'            => '',
                'internal_grid_columns_mode'        => '',
                'internal_grid_columns'             => '',
                'internal_grid_columns_medium'      => '',
                'internal_grid_columns_small'       => '',
                'internal_grid_min_columns'         => '',
                'internal_grid_min_columns_medium'  => '',
                'internal_grid_min_columns_small'   => '',
                'internal_grid_max_columns'         => '',
                'internal_grid_max_columns_medium'  => '',
                'internal_grid_max_columns_small'   => '',
                // Contenido (servicio) - opciones responsive por instancia
                'servicio_contenido_width'          => '',
                'servicio_contenido_width_medium'   => '',
                'servicio_contenido_width_small'    => '',
                'servicio_contenido_max_width'      => '',
                'servicio_contenido_max_width_medium' => '',
                'servicio_contenido_max_width_small'  => '',
                // Opciones específicas de plantillas
                'portafolio_mostrar_categorias'     => 'yes',
                'portafolio_categoria_typography_enable' => 'no',
                'fusion_font_family_portafolio_categoria_font' => '',
                'fusion_font_variant_portafolio_categoria_font' => '',
                'portafolio_categoria_font_size'    => '',
                'portafolio_categoria_line_height'  => '',
                'portafolio_categoria_letter_spacing' => '',
                'portafolio_categoria_color'        => '',
                'portafolio_categoria_text_transform' => '',
                'portafolio_categoria_margin_top'   => '',
                'portafolio_categoria_margin_bottom'=> '',
                'portafolio_mostrar_contenido'      => 'no',
                'portafolio_contenido_max_palabras' => '40',
                'portafolio_boton_mostrar'          => 'yes',
                'portafolio_boton_text'             => 'View project',
                'portafolio_boton_typography_enable'=> 'no',
                'fusion_font_family_portafolio_boton_font' => '',
                'fusion_font_variant_portafolio_boton_font' => '',
                'portafolio_boton_font_size'        => '',
                'portafolio_boton_line_height'      => '',
                'portafolio_boton_letter_spacing'   => '',
                'portafolio_boton_text_transform'   => '',
                'portafolio_boton_text_color'       => '',
                'portafolio_boton_text_color_hover' => '',
                'portafolio_boton_background'       => '',
                'portafolio_boton_background_hover' => '',
                'portafolio_boton_border_color'     => '',
                'portafolio_boton_border_color_hover' => '',
                'portafolio_boton_border_width'     => '',
                'portafolio_boton_border_radius'    => '',
                'portafolio_boton_padding'          => '',
                // Team template
                'team_show_role'                    => 'yes',
                'team_show_profession'              => 'yes',
                // Arrastre horizontal
                'enable_horizontal_drag'           => 'no',
            ];
        }

        public function render($args, $content = '')
        {
            $this->defaults = self::get_element_defaults();
            $this->args     = FusionBuilder::set_shortcode_defaults($this->defaults, $args, 'glory_content_render');

            $this->set_element_id($this->counter);

            $titulo = isset($this->args['titulo']) ? (string) $this->args['titulo'] : '';
            $html   = '';
            if ('' !== $titulo) {
                $html .= '<h3>' . esc_html($titulo) . '</h3>';
            }

            $usar_cr  = (isset($this->args['usar_content_render']) && 'yes' === $this->args['usar_content_render']);
            $postType = isset($this->args['post_type']) ? sanitize_key($this->args['post_type']) : 'post';

            if ($usar_cr && class_exists('\\Glory\\Components\\ContentRender')) {
                $callable    = ['\\Glory\\Components\\ContentRender', 'defaultTemplate'];
                $template_id = isset($this->args['template_id']) ? (string) $this->args['template_id'] : '';
                $this->currentTemplateSupports = [];
                if ('' !== $template_id && '__default' !== $template_id && class_exists('\\Glory\\Utility\\TemplateRegistry')) {
                    // Permitir cualquier plantilla con cualquier post_type: ignorar appliesTo
                    $c = call_user_func(['\\Glory\\Utility\\TemplateRegistry', 'get'], $template_id);
                    if (is_callable($c)) {
                        $callable = $c;
                    }
                    $supports = call_user_func(['\\Glory\\Utility\\TemplateRegistry', 'supports'], $template_id);
                    if (is_array($supports)) {
                        $this->currentTemplateSupports = $supports;
                    }
                }

                $modo_interaccion = \Glory\Support\ContentRender\Args::sanitizeModo((string) ($this->args['interaccion_modo'] ?? 'normal'));
                if ('toggle' === $modo_interaccion && empty($this->currentTemplateSupports['toggle'])) {
                    if (current_user_can('manage_options')) {
                        $html .= '<div class="glory-warning">The selected template does not declare support for toggle mode. Normal mode will be used.</div>';
                    }
                    $modo_interaccion = 'normal';
                }
                $this->currentModoInteraccion = $modo_interaccion;

                // Aplicar overrides forzados por plantilla (agnóstico)
                if (! empty($this->currentTemplateSupports['containerOverrides']) && is_array($this->currentTemplateSupports['containerOverrides'])) {
                    foreach ((array) $this->currentTemplateSupports['containerOverrides'] as $k => $v) {
                        $this->args[$k] = $v;
                    }
                }
                if (! empty($this->currentTemplateSupports['forceArgs']) && is_array($this->currentTemplateSupports['forceArgs'])) {
                    foreach ((array) $this->currentTemplateSupports['forceArgs'] as $k => $v) {
                        $this->args[$k] = $v;
                    }
                }

                $carousel_speed = isset($this->args['carousel_speed']) ? max(1.0, (float) $this->args['carousel_speed']) : 20.0;
                $toggle_separator_raw = isset($this->args['toggle_separator']) ? (string) $this->args['toggle_separator'] : '';
                $toggle_separator = in_array(strtolower(trim($toggle_separator_raw)), ['yes', '1', 'true', 'on'], true);
                $toggle_separator_color = isset($this->args['toggle_separator_color']) ? sanitize_text_field((string) $this->args['toggle_separator_color']) : 'rgba(0,0,0,0.1)';
                $toggle_auto_open = isset($this->args['toggle_auto_open']) ? \Glory\Support\ContentRender\Args::parseToggleAutoOpen((string) $this->args['toggle_auto_open']) : [];
                $toggle_default_state_raw = isset($this->args['toggle_default_state']) ? (string) $this->args['toggle_default_state'] : 'collapsed';
                $toggle_default_state = in_array(strtolower(trim($toggle_default_state_raw)), ['collapsed', 'expanded'], true) ? strtolower(trim($toggle_default_state_raw)) : 'collapsed';
                $internal_layout_options = \Glory\Support\ContentRender\Args::collectInternalLayout($this->args, $this->currentTemplateSupports, $modo_interaccion);
                $internal_layout_options_for_template = $internal_layout_options;
                if (! empty($internal_layout_options_for_template['grid_columns']) && is_array($internal_layout_options_for_template['grid_columns'])) {
                    $internal_layout_options_for_template['grid_columns'] = $internal_layout_options_for_template['grid_columns']['large'] ?? reset($internal_layout_options_for_template['grid_columns']);
                }
                if (! empty($internal_layout_options_for_template['grid_min_columns']) && is_array($internal_layout_options_for_template['grid_min_columns'])) {
                    $internal_layout_options_for_template['grid_min_columns'] = $internal_layout_options_for_template['grid_min_columns']['large'] ?? reset($internal_layout_options_for_template['grid_min_columns']);
                }
                if (! empty($internal_layout_options_for_template['grid_max_columns']) && is_array($internal_layout_options_for_template['grid_max_columns'])) {
                    $internal_layout_options_for_template['grid_max_columns'] = $internal_layout_options_for_template['grid_max_columns']['large'] ?? reset($internal_layout_options_for_template['grid_max_columns']);
                }

                $argumentosConsulta = [];
                if (! empty($this->args['argumentos_json'])) {
                    $json   = trim((string) $this->args['argumentos_json']);
                    $parsed = json_decode($json, true);
                    if (is_array($parsed)) {
                        $argumentosConsulta = $parsed;
                    } else {
                        if (current_user_can('manage_options')) {
                            $html .= '<div class="glory-warning">Invalid JSON in query arguments. Ignoring.</div>';
                        }
                    }
                }

                // Aplicar post__in desde CSV y/o selección múltiple (helper agnóstico)
                $argumentosConsulta = \Glory\Support\ContentRender\QueryArgs::mergePostIds($this->args, $argumentosConsulta);

                $orden     = isset($this->args['orden']) ? (string) $this->args['orden'] : 'fecha';
                $metaKey   = isset($this->args['meta_key']) ? trim((string) $this->args['meta_key']) : '';
                $metaOrder = isset($this->args['meta_order']) ? strtoupper((string) $this->args['meta_order']) : 'ASC';

				// Grid rotate effect: parse args
				$display_mode_raw      = isset($this->args['display_mode']) ? (string) $this->args['display_mode'] : 'flex';
				$grid_columns_mode_raw = isset($this->args['grid_columns_mode']) ? (string) $this->args['grid_columns_mode'] : 'fixed';
				$grid_rotate_raw       = isset($this->args['grid_rotate']) ? (string) $this->args['grid_rotate'] : 'no';
				$grid_rotate           = in_array(strtolower(trim($grid_rotate_raw)), ['yes','1','true','on'], true);
				$grid_rotate_interval  = isset($this->args['grid_rotate_interval']) ? max(100, (int) $this->args['grid_rotate_interval']) : 3000;
				$grid_rotate_fade      = isset($this->args['grid_rotate_fade']) ? max(50, (int) $this->args['grid_rotate_fade']) : 400;
				$grid_rotate_offset    = isset($this->args['grid_rotate_offset']) ? (string) $this->args['grid_rotate_offset'] : '10px';
				$gridRotateEnabled     = ($grid_rotate && 'grid' === $display_mode_raw && 'fixed' === $grid_columns_mode_raw);

                $config = [
                    'publicacionesPorPagina' => isset($this->args['publicaciones_por_pagina']) ? (int) $this->args['publicaciones_por_pagina'] : 10,
                    'claseContenedor'        => $this->args['clase_contenedor'] ?? 'glory-content-list',
                    'claseItem'              => $this->args['clase_item'] ?? 'glory-content-item',
					'paginacion'             => $gridRotateEnabled ? false : (isset($this->args['paginacion']) && 'yes' === $this->args['paginacion']),
                    'plantillaCallback'      => $callable,
                    'argumentosConsulta'     => $argumentosConsulta,
                    'orden'                  => ('meta' === $orden && '' !== $metaKey) ? $metaOrder : ('random' === $orden ? 'random' : 'fecha'),
                    'metaKey'                => '' !== $metaKey ? $metaKey : null,
                    'minPaginas'             => isset($this->args['min_paginas']) ? (int) $this->args['min_paginas'] : 1,
                    'tiempoCache'            => isset($this->args['tiempo_cache']) ? (int) $this->args['tiempo_cache'] : 3600,
                    'forzarSinCache'         => (isset($this->args['forzar_sin_cache']) && 'yes' === $this->args['forzar_sin_cache']),
                    'acciones'               => ! empty($this->args['acciones']) ? array_map('trim', explode(',', (string) $this->args['acciones'])) : [],
                    'submenu'                => (isset($this->args['submenu']) && 'yes' === $this->args['submenu']),
                    'eventoAccion'           => $this->args['evento_accion'] ?? 'dblclick',
                    'selectorItem'           => $this->args['selector_item'] ?? '[id^="post-"]',
                    'linkEnabled'            => (! isset($this->args['link_enabled']) || 'yes' === (string) $this->args['link_enabled']),
                    // Imagen: optimización/calidad
                    'imgOptimize'            => (! isset($this->args['img_optimize']) || 'yes' === (string) $this->args['img_optimize']),
                    'imgQuality'             => isset($this->args['img_quality']) ? (int) $this->args['img_quality'] : 60,
                    'imgSize'                => isset($this->args['img_size']) ? (string) $this->args['img_size'] : 'medium',
                    'modoInteraccion'        => $modo_interaccion,
                    'carouselSpeed'          => $carousel_speed,
                    'toggleSeparator'        => $toggle_separator,
                    'toggleSeparatorColor'   => $toggle_separator_color,
                    'toggleAutoOpen'         => $toggle_auto_open,
                    'toggleDefaultState'     => $toggle_default_state,
                    'enableHorizontalDrag'   => (isset($this->args['enable_horizontal_drag']) && 'yes' === $this->args['enable_horizontal_drag']),
                    'internalLayoutOptions'  => $internal_layout_options,
					// Grid rotate effect
					'gridRotate'             => $gridRotateEnabled,
					'gridRotateInterval'     => $grid_rotate_interval,
					'gridRotateFade'         => $grid_rotate_fade,
					'gridRotateOffset'       => $grid_rotate_offset,
                ];
                $categoryFilterEnabled = (isset($this->args['category_filter_enable']) && 'yes' === $this->args['category_filter_enable']);
                $categoryFilterLabel = isset($this->args['category_filter_all_label']) && '' !== trim((string) $this->args['category_filter_all_label'])
                    ? (string) $this->args['category_filter_all_label']
                    : __('All', 'glory-ab');
                $config['categoryFilter'] = [
                    'enabled'  => $categoryFilterEnabled,
                    'allLabel' => $categoryFilterLabel,
                ];

                $layoutPatternRaw = $this->args['layout_pattern'] ?? 'none';
                if (is_array($layoutPatternRaw)) {
                    $firstPattern   = reset($layoutPatternRaw);
                    $patternLarge   = (string) ($layoutPatternRaw['large'] ?? $firstPattern ?? 'none');
                    $patternMedium  = (string) ($layoutPatternRaw['medium'] ?? $patternLarge);
                    $patternSmall   = (string) ($layoutPatternRaw['small'] ?? $patternMedium);
                } else {
                    $patternLarge  = (string) $layoutPatternRaw;
                    $patternMedium = $patternLarge;
                    $patternSmall  = $patternMedium;
                }
                $config['layoutPattern'] = [
                    'large'  => $patternLarge,
                    'medium' => $patternMedium,
                    'small'  => $patternSmall,
                ];
                $patternSplitRaw = $this->args['pattern_lr_split_mode'] ?? 'no';
                $patternSplitEnabled = in_array(strtolower((string) $patternSplitRaw), ['yes', 'true', '1'], true) && ('alternado_lr' === $patternLarge || 'alternado_lr' === $patternMedium || 'alternado_lr' === $patternSmall);
                $config['patternLrSplit'] = $patternSplitEnabled;

                // Opciones específicas de plantilla (Posts): mapear directamente a config
                $mostrarContenidoArg = isset($this->args['mostrar_contenido']) ? (string) $this->args['mostrar_contenido'] : 'yes';
                $mostrarFechaArg     = isset($this->args['mostrar_fecha']) ? (string) $this->args['mostrar_fecha'] : 'yes';
                $contenidoMaxArg     = isset($this->args['contenido_max_palabras']) ? (string) $this->args['contenido_max_palabras'] : '55';

                $config['mostrar_contenido']      = $mostrarContenidoArg;
                $config['mostrar_fecha']          = $mostrarFechaArg;
                $config['contenido_max_palabras'] = is_numeric($contenidoMaxArg) ? (int) $contenidoMaxArg : (int) $contenidoMaxArg;

                // Clase única por instancia para poder aplicar CSS aislado
                $instanceClass = 'glory-cr-' . substr(md5(uniqid('', true)), 0, 8);
                $config['claseContenedor'] = trim((string) ($config['claseContenedor'] ?? 'glory-content-list') . ' ' . $instanceClass);
                if ($patternSplitEnabled) {
                    $config['claseContenedor'] = trim($config['claseContenedor'] . ' glory-cr--lr-split');
                }
                $config['claseItem'] = trim((string) ($config['claseItem'] ?? 'glory-content-item') . ' ' . $instanceClass . '__item');
                $config['instanceClass'] = $instanceClass;

                $this->currentInstanceConfig = $config;
                $this->currentInstanceConfig['internalLayoutOptionsResponsive'] = $internal_layout_options;
                $this->currentInstanceClass = $instanceClass;
                \Glory\Components\ContentRender::setCurrentOption('indiceItem', 0);
                \Glory\Components\ContentRender::setCurrentOption('internalLayoutOptions', $internal_layout_options_for_template);
                \Glory\Components\ContentRender::setCurrentOption('modoInteraccion', $modo_interaccion);
                \Glory\Components\ContentRender::setCurrentOption('toggleSeparator', $toggle_separator);
                \Glory\Components\ContentRender::setCurrentOption('toggleSeparatorColor', $toggle_separator_color);
                \Glory\Components\ContentRender::setCurrentOption('toggleAutoOpen', $toggle_auto_open);
                $contentOpacityRaw = isset($this->args['content_opacity']) ? (float) $this->args['content_opacity'] : 0.9;
                if ($contentOpacityRaw < 0) { $contentOpacityRaw = 0; }
                if ($contentOpacityRaw > 1) { $contentOpacityRaw = 1; }
                \Glory\Components\ContentRender::setCurrentOption('contentOpacity', $contentOpacityRaw);
                // Propagar opciones específicas de plantilla (portafolio)
                $raw_mostrar = isset($this->args['portafolio_mostrar_categorias']) ? (string) $this->args['portafolio_mostrar_categorias'] : 'yes';
                $mostrar_categorias = in_array(strtolower($raw_mostrar), ['yes', 'true', '1'], true);
                \Glory\Components\ContentRender::setCurrentOption('portafolioMostrarCategorias', $mostrar_categorias);
                $config['portafolioMostrarCategorias'] = $mostrar_categorias;
                $catTypoEnableRaw = isset($this->args['portafolio_categoria_typography_enable']) ? (string) $this->args['portafolio_categoria_typography_enable'] : 'no';
                $categoryOptions = [
                    'typographyEnabled' => in_array(strtolower($catTypoEnableRaw), ['yes', 'true', '1'], true),
                    'font_family'       => $this->args['fusion_font_family_portafolio_categoria_font'] ?? '',
                    'font_variant'      => $this->args['fusion_font_variant_portafolio_categoria_font'] ?? '',
                    'font_size'         => $this->args['portafolio_categoria_font_size'] ?? '',
                    'line_height'       => $this->args['portafolio_categoria_line_height'] ?? '',
                    'letter_spacing'    => $this->args['portafolio_categoria_letter_spacing'] ?? '',
                    'color'             => $this->args['portafolio_categoria_color'] ?? '',
                    'text_transform'    => $this->args['portafolio_categoria_text_transform'] ?? '',
                    'margin_top'        => $this->args['portafolio_categoria_margin_top'] ?? '',
                    'margin_bottom'     => $this->args['portafolio_categoria_margin_bottom'] ?? '',
                ];
                \Glory\Components\ContentRender::setCurrentOption('portafolioCategoryOptions', $categoryOptions);
                $config['portafolioCategoryOptions'] = $categoryOptions;
                // Pasar valores crudos para que la plantilla los interprete
                $raw_porta_contenido = isset($this->args['portafolio_mostrar_contenido']) ? (string) $this->args['portafolio_mostrar_contenido'] : 'no';
                $raw_porta_len       = isset($this->args['portafolio_contenido_max_palabras']) ? (string) $this->args['portafolio_contenido_max_palabras'] : '40';
                \Glory\Components\ContentRender::setCurrentOption('portafolioMostrarContenido', $raw_porta_contenido);
                \Glory\Components\ContentRender::setCurrentOption('portafolioContenidoMaxPalabras', $raw_porta_len);
                // Incluir opciones de portafolio en config para que afecten a la clave de caché
                $config['portafolioMostrarContenido']     = $raw_porta_contenido;
                $config['portafolioContenidoMaxPalabras'] = $raw_porta_len;
                $rawButtonShow = isset($this->args['portafolio_boton_mostrar']) ? (string) $this->args['portafolio_boton_mostrar'] : 'yes';
                $defaultButtonLabel = __('View project', 'glory-ab');
                $buttonText = isset($this->args['portafolio_boton_text']) && '' !== trim((string) $this->args['portafolio_boton_text'])
                    ? (string) $this->args['portafolio_boton_text']
                    : $defaultButtonLabel;
                $buttonTypographyEnabled = in_array(strtolower((string) ($this->args['portafolio_boton_typography_enable'] ?? 'no')), ['yes', 'true', '1'], true);
                $buttonConfig = [
                    'show'            => in_array(strtolower($rawButtonShow), ['yes', 'true', '1'], true),
                    'text'            => $buttonText,
                    'typography'      => [
                        'enabled'        => $buttonTypographyEnabled,
                        'font_family'    => $this->args['fusion_font_family_portafolio_boton_font'] ?? '',
                        'font_variant'   => $this->args['fusion_font_variant_portafolio_boton_font'] ?? '',
                        'font_size'      => $this->args['portafolio_boton_font_size'] ?? '',
                        'line_height'    => $this->args['portafolio_boton_line_height'] ?? '',
                        'letter_spacing' => $this->args['portafolio_boton_letter_spacing'] ?? '',
                        'text_transform' => $this->args['portafolio_boton_text_transform'] ?? '',
                    ],
                    'colors'         => [
                        'text'       => $this->args['portafolio_boton_text_color'] ?? '',
                        'textHover'  => $this->args['portafolio_boton_text_color_hover'] ?? '',
                        'background' => $this->args['portafolio_boton_background'] ?? '',
                        'backgroundHover' => $this->args['portafolio_boton_background_hover'] ?? '',
                        'border'     => $this->args['portafolio_boton_border_color'] ?? '',
                        'borderHover'=> $this->args['portafolio_boton_border_color_hover'] ?? '',
                    ],
                    'borders'        => [
                        'width'  => $this->args['portafolio_boton_border_width'] ?? '',
                        'radius' => $this->args['portafolio_boton_border_radius'] ?? '',
                    ],
                    'padding'        => $this->args['portafolio_boton_padding'] ?? '',
                ];
                \Glory\Components\ContentRender::setCurrentOption('portafolioButton', $buttonConfig);
                $config['portafolioButton'] = $buttonConfig;
                $config['portafolioMostrarCategorias'] = $mostrar_categorias ? 'yes' : 'no';
                // Propagar opciones de plantilla Team
                $raw_team_role = isset($this->args['team_show_role']) ? (string) $this->args['team_show_role'] : 'yes';
                $raw_team_prof = isset($this->args['team_show_profession']) ? (string) $this->args['team_show_profession'] : 'yes';
                \Glory\Components\ContentRender::setCurrentOption('teamShowRole', 'yes' === $raw_team_role);
                \Glory\Components\ContentRender::setCurrentOption('teamShowProfession', 'yes' === $raw_team_prof);
                \Glory\Components\ContentRender::setCurrentOption('titleTypography', [
                    'font_family'            => $this->args['fusion_font_family_title_font'] ?? '',
                    'font_variant'           => $this->args['fusion_font_variant_title_font'] ?? '',
                    'font_size'              => $this->args['font_size'] ?? '',
                    'line_height'            => $this->args['line_height'] ?? '',
                    'letter_spacing'         => $this->args['letter_spacing'] ?? '',
                    'font_size_medium'       => $this->args['font_size_medium'] ?? '',
                    'font_size_small'        => $this->args['font_size_small'] ?? '',
                    'line_height_medium'     => $this->args['line_height_medium'] ?? '',
                    'line_height_small'      => $this->args['line_height_small'] ?? '',
                    'letter_spacing_medium'  => $this->args['letter_spacing_medium'] ?? '',
                    'letter_spacing_small'   => $this->args['letter_spacing_small'] ?? '',
                    'title_min_width'        => $this->args['title_min_width'] ?? '',
                    'title_width'            => $this->args['title_width'] ?? '',
                    'title_max_width'        => $this->args['title_max_width'] ?? '',
                    'title_text_transform'   => $this->args['title_text_transform'] ?? '',
                    'title_show_on_hover'    => $this->args['title_show_on_hover'] ?? 'no',
                    'title_position'         => $this->args['title_position'] ?? 'top',
                    'title_show'             => $this->args['title_show'] ?? 'yes',
                ]);
                // Propagar opciones específicas de plantilla: Service Kura
                $rawBorderShow = isset($this->args['service_kura_border_show']) ? (string) $this->args['service_kura_border_show'] : 'yes';
                \Glory\Components\ContentRender::setCurrentOption('serviceKuraBorderShow', ('yes' === strtolower($rawBorderShow)));
                \Glory\Components\ContentRender::setCurrentOption('serviceKuraBorderColor', isset($this->args['service_kura_border_color']) ? (string) $this->args['service_kura_border_color'] : 'rgba(0,0,0,0.15)');
                \Glory\Components\ContentRender::setCurrentOption('serviceKuraBorderWidth', isset($this->args['service_kura_border_width']) ? (string) $this->args['service_kura_border_width'] : '1px');
                $instanceConfigSnapshot = [];
                ob_start();
                try {
                    $this->enable_dedup_filters();
                    // Señalizar en la clase del item cuando la plantilla es portafolio y la opción está activa
                    if ('plantilla_portafolio' === $template_id) {
                        $raw_mostrar = isset($this->args['portafolio_mostrar_categorias']) ? (string) $this->args['portafolio_mostrar_categorias'] : 'no';
                        if ('yes' === $raw_mostrar) {
                            $config['claseItem'] = trim((string) ($config['claseItem'] ?? '') . ' glory-portafolio--show-categories');
                            $this->currentInstanceConfig['claseItem'] = $config['claseItem'];
                        }
                    }
                    call_user_func(['\\Glory\\Components\\ContentRender', 'print'], $postType, $config);
                    $instanceConfigSnapshot = $this->currentInstanceConfig;
                } catch (\Throwable $t) {
                    if (current_user_can('manage_options')) {
                        $html .= '<div class="glory-widget-error">' . esc_html($t->getMessage()) . '</div>';
                    }
                } finally {
                    $this->disable_dedup_filters();
                    $this->currentInstanceConfig = [];
                    $this->currentInstanceClass = '';
                    \Glory\Components\ContentRender::setCurrentOption('indiceItem', null);
                    \Glory\Components\ContentRender::setCurrentOption('internalLayoutOptions', null);
                    \Glory\Components\ContentRender::setCurrentOption('modoInteraccion', null);
                    \Glory\Components\ContentRender::setCurrentOption('toggleSeparator', null);
                    \Glory\Components\ContentRender::setCurrentOption('toggleSeparatorColor', null);
                    \Glory\Components\ContentRender::setCurrentOption('toggleAutoOpen', null);
                    \Glory\Components\ContentRender::setCurrentOption('portafolioMostrarCategorias', null);
                    \Glory\Components\ContentRender::setCurrentOption('portafolioMostrarContenido', null);
                    \Glory\Components\ContentRender::setCurrentOption('portafolioContenidoMaxPalabras', null);
                    \Glory\Components\ContentRender::setCurrentOption('teamShowRole', null);
                    \Glory\Components\ContentRender::setCurrentOption('teamShowProfession', null);
                    \Glory\Components\ContentRender::setCurrentOption('serviceKuraBorderShow', null);
                    \Glory\Components\ContentRender::setCurrentOption('serviceKuraBorderColor', null);
                    \Glory\Components\ContentRender::setCurrentOption('serviceKuraBorderWidth', null);
                }
                $html .= ob_get_clean();

                // CSS inline por instancia (layout, imagen, título, contenido interno, enlace)
                $css = \Glory\Support\CSS\ContentRenderCss::build($instanceClass, $this->args, $instanceConfigSnapshot, $this->currentModoInteraccion);
                if ('' !== $css) {
                    $html .= '<style id="' . esc_attr($instanceClass) . '-css">' . $css . '</style>';
                }

                $selector = '.' . $instanceClass;
                $config['instanceClass'] = $this->currentInstanceClass;
                $html .= \Glory\Support\Scripts\ContentRenderScripts::buildAll($selector, $config);

				// Inline CSS+JS para el efecto Rotate Grid (scoped por instancia)
				if ($gridRotateEnabled) {
					$cssRotate = ''
						. $selector . ' { overflow: hidden; --gr-fade-duration: ' . (int) $grid_rotate_fade . 'ms; transition: opacity var(--gr-fade-duration) ease; }'
						. $selector . '.gr-fading { opacity: 0; }'
						. $selector . '.gr-measure { opacity: 0; }';

					$html .= '<style id="' . esc_attr($instanceClass) . '-grid-rotate-css">' . $cssRotate . '</style>';

					$intervalMs = (int) $grid_rotate_interval;
					$js  = '(function(){try{';
					$js .= 'var root=document.querySelector("' . addslashes($selector) . '");if(!root){return;}';
					$js .= 'var items=Array.prototype.slice.call(root.querySelectorAll(".' . addslashes($instanceClass) . '__item"));if(!items.length){return;}';
					$js .= 'var interval=' . $intervalMs . ';var fade=' . (int) $grid_rotate_fade . ';';
					$js .= 'function measureCols(){root.classList.add("gr-measure");var tops=items.map(function(el){var r=el.getBoundingClientRect();return Math.round(r.top);});root.classList.remove("gr-measure");if(!tops.length){return 1;}var minTop=Math.min.apply(Math,tops);var cols=0;for(var i=0;i<tops.length;i++){if(Math.abs(tops[i]-minTop)<2){cols++;}else{break;}}return Math.max(1,cols);}';
					$js .= 'function measureRowHeight(cols){if(items.length===0||cols<1){return 0;}var minTop=Infinity;var maxBottom=-Infinity;for(var i=0;i<Math.min(cols,items.length);i++){var r=items[i].getBoundingClientRect();if(r.height===0){continue;}if(r.top<minTop){minTop=r.top;}if(r.bottom>maxBottom){maxBottom=r.bottom;}}return (maxBottom>minTop)?Math.round(maxBottom-minTop):0;}';
					$js .= 'function sliceIdx(start,count){var out=[];for(var i=start;i<Math.min(start+count,items.length);i++){out.push(i);}return out;}';
					$js .= 'function reorderTo(indices){var set={};indices.forEach(function(i){set[i]=true;});var order=indices.slice();for(var k=0;k<items.length;k++){if(!set[k]){order.push(k);}}var frag=document.createDocumentFragment();var newItems=[];for(var q=0;q<order.length;q++){var node=items[order[q]];frag.appendChild(node);newItems.push(node);}root.appendChild(frag);items=newItems;}';
					$js .= 'function nextChunk(currStart,cols){var nextStart=currStart+cols;var next=sliceIdx(nextStart,cols);if(next.length<cols){var rem=cols-next.length;var pool=sliceIdx(currStart,cols);while(rem>0&&pool.length){var p=Math.floor(Math.random()*pool.length);next.push(pool.splice(p,1)[0]);rem--;}}return{chunk:next,start:(nextStart>=items.length?0:nextStart)};}';
					$js .= 'var cols=measureCols();if(items.length<=cols){return;}';
					$js .= 'root.classList.add("gr-fading");var rowH=measureRowHeight(cols);if(rowH>0){root.style.height=rowH+"px";}reorderTo(sliceIdx(0,cols));requestAnimationFrame(function(){root.classList.remove("gr-fading");});';
					$js .= 'var start=0;';
					$js .= 'function tick(){var res=nextChunk(start,cols);root.classList.add("gr-fading");setTimeout(function(){reorderTo(res.chunk);var h=measureRowHeight(cols);if(h>0){root.style.height=h+"px";}root.classList.remove("gr-fading");start=res.start;},fade);}';
					$js .= 'var timer=setInterval(tick,interval);';
					$js .= 'root.addEventListener("mouseenter",function(){if(timer){clearInterval(timer);timer=null;}});';
					$js .= 'root.addEventListener("mouseleave",function(){if(!timer){timer=setInterval(tick,interval);}});';
					$js .= 'function onResize(){var newCols=measureCols();if(newCols!==cols){cols=newCols;if(items.length<=cols){if(timer){clearInterval(timer);timer=null;}root.style.height="";root.classList.remove("gr-fading");return;}start=0;reorderTo(sliceIdx(0,cols));var h=measureRowHeight(cols);if(h>0){root.style.height=h+"px";}}}';
					$js .= 'if("ResizeObserver" in window){var ro=new ResizeObserver(onResize);ro.observe(root);}else{window.addEventListener("resize",onResize);}';
					$js .= '}catch(e){}})();';

					$html .= '<script id="' . esc_attr($instanceClass) . '-grid-rotate-js">' . $js . '</script>';
				}

                $this->counter++;
                $this->on_render();
                return $html;
            }

            $function_name = isset($this->args['function_name']) ? (string) $this->args['function_name'] : '';
            if ('' !== $function_name && function_exists($function_name)) {
                ob_start();
                try {
                    call_user_func($function_name);
                } catch (\Throwable $t) {
                    if (current_user_can('manage_options')) {
                        echo '<div class="glory-widget-error">' . esc_html($t->getMessage()) . '</div>';
                    }
                }
                $html .= ob_get_clean();

                $this->counter++;
                $this->on_render();
                return $html;
            }

            $sc = isset($this->args['shortcode']) ? trim((string) $this->args['shortcode']) : '';
            if ('' !== $sc) {
                $html .= do_shortcode($sc);
                $this->counter++;
                $this->on_render();
                return $html;
            }

            $raw = isset($this->args['raw_content']) ? (string) $this->args['raw_content'] : '';
            $html .= $raw;

            $this->counter++;
            $this->on_render();
            return $html;
        }


        private function enable_dedup_filters(): void
        {
            if ($this->dedupActive) {
                return;
            }
            $this->dedupActive = true;

            \Glory\Support\WP\PostsDedup::enable();
        }

        private function disable_dedup_filters(): void
        {
            if (! $this->dedupActive) {
                return;
            }

            \Glory\Support\WP\PostsDedup::disable();

            $this->dedupActive = false;
        }

        public function filter_posts_distinct($distinct, $query)
        {
            if ($this->dedupActive) {
                return 'DISTINCT';
            }
            return $distinct;
        }

        public function filter_the_posts_dedup($posts, $query)
        {
            if (! $this->dedupActive || ! is_array($posts)) {
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
    }

    new FusionSC_GloryContentRender();
}
