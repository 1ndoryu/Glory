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
                // Ancho del contenido (.glory-cr__content)
                'content_min_width'             => '',
                'content_width'                  => '',
                'content_max_width'              => '',
                'title_show_on_hover'            => 'no',
                'title_position'                 => 'top', // top | bottom
				// Color del título
				'title_color'                    => '',
				// Patrón de layout alternado
				'layout_pattern'                 => 'none', // responsive: large/medium/small
			'pattern_small_width_percent'    => 40,
			'pattern_row_gap'                 => '40px',
				'pattern_small_width_percent_medium' => '',
				'pattern_small_width_percent_small'  => '',
				'pattern_large_width_percent'    => 60,
				'pattern_large_width_percent_medium' => '',
				'pattern_large_width_percent_small'  => '',
                // Tipografía interna (por instancia) - mismas opciones del título pero separadas
                'internal_typography_enable'           => 'no',
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
                'portafolio_mostrar_categorias'     => 'no',
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
                        $html .= '<div class="glory-warning">' . esc_html__('La plantilla seleccionada no declara soporte para modo toggle. Se usará el modo normal.', 'glory-ab') . '</div>';
                    }
                    $modo_interaccion = 'normal';
                }
                $this->currentModoInteraccion = $modo_interaccion;

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
                            $html .= '<div class="glory-warning">' . esc_html__('JSON inválido en argumentos de consulta. Ignorando.', 'glory-ab') . '</div>';
                        }
                    }
                }

                // Aplicar post__in desde CSV y/o selección múltiple (helper agnóstico)
                $argumentosConsulta = \Glory\Support\ContentRender\QueryArgs::mergePostIds($this->args, $argumentosConsulta);

                $orden     = isset($this->args['orden']) ? (string) $this->args['orden'] : 'fecha';
                $metaKey   = isset($this->args['meta_key']) ? trim((string) $this->args['meta_key']) : '';
                $metaOrder = isset($this->args['meta_order']) ? strtoupper((string) $this->args['meta_order']) : 'ASC';

                $config = [
                    'publicacionesPorPagina' => isset($this->args['publicaciones_por_pagina']) ? (int) $this->args['publicaciones_por_pagina'] : 10,
                    'claseContenedor'        => $this->args['clase_contenedor'] ?? 'glory-content-list',
                    'claseItem'              => $this->args['clase_item'] ?? 'glory-content-item',
                    'paginacion'             => (isset($this->args['paginacion']) && 'yes' === $this->args['paginacion']),
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
                ];

                // Clase única por instancia para poder aplicar CSS aislado
                $instanceClass = 'glory-cr-' . substr(md5(uniqid('', true)), 0, 8);
                $config['claseContenedor'] = trim((string) ($config['claseContenedor'] ?? 'glory-content-list') . ' ' . $instanceClass);
                $config['claseItem'] = trim((string) ($config['claseItem'] ?? 'glory-content-item') . ' ' . $instanceClass . '__item');

                $this->currentInstanceConfig = $config;
                $this->currentInstanceConfig['internalLayoutOptionsResponsive'] = $internal_layout_options;
                $this->currentInstanceClass = $instanceClass;
                \Glory\Components\ContentRender::setCurrentOption('indiceItem', 0);
                \Glory\Components\ContentRender::setCurrentOption('internalLayoutOptions', $internal_layout_options_for_template);
                \Glory\Components\ContentRender::setCurrentOption('modoInteraccion', $modo_interaccion);
                \Glory\Components\ContentRender::setCurrentOption('toggleSeparator', $toggle_separator);
                \Glory\Components\ContentRender::setCurrentOption('toggleSeparatorColor', $toggle_separator_color);
                \Glory\Components\ContentRender::setCurrentOption('toggleAutoOpen', $toggle_auto_open);
                // Propagar opción específica de plantilla (portafolio)
                $raw_mostrar = isset($this->args['portafolio_mostrar_categorias']) ? (string) $this->args['portafolio_mostrar_categorias'] : '(missing)';
                $mostrar_categorias = ('yes' === $raw_mostrar);
                \Glory\Components\ContentRender::setCurrentOption('portafolioMostrarCategorias', $mostrar_categorias);
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
                    \Glory\Components\ContentRender::setCurrentOption('teamShowRole', null);
                    \Glory\Components\ContentRender::setCurrentOption('teamShowProfession', null);
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
