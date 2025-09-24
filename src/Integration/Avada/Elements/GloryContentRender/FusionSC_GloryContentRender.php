<?php

// Clase global sin namespace para ajustarse al patrón de Avada.

if ( ! class_exists( 'FusionSC_GloryContentRender' ) && class_exists( 'Fusion_Element' ) ) {

    class FusionSC_GloryContentRender extends Fusion_Element {

        private $counter = 1;
        private $dedupActive = false;

        public function __construct() {
            parent::__construct();
            add_shortcode( 'glory_content_render', [ $this, 'render' ] );
        }

        public static function get_element_defaults() {
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
				// Carrusel
				'carousel'                 => 'no',
				'carousel_speed'           => 20,
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
				'img_optimize'             => 'yes',
				'img_quality'              => 60,
				// Título (por instancia)
				'title_show'               => 'yes',
				'title_font_family'        => '',
				'title_font_size'          => '',
				'title_font_weight'        => '',
				'title_text_transform'     => '',
				'title_max_width'          => '',
				'title_show_on_hover'      => 'no',
				'title_position'           => 'top', // top | bottom
				// Enlace
				'link_enabled'             => 'yes',
            ];
        }

        public function render( $args, $content = '' ) {
            $this->defaults = self::get_element_defaults();
            $this->args     = FusionBuilder::set_shortcode_defaults( $this->defaults, $args, 'glory_content_render' );

            $this->set_element_id( $this->counter );

            $titulo = isset( $this->args['titulo'] ) ? (string) $this->args['titulo'] : '';
            $html   = '';
            if ( '' !== $titulo ) {
                $html .= '<h3>' . esc_html( $titulo ) . '</h3>';
            }

            $usar_cr  = ( isset( $this->args['usar_content_render'] ) && 'yes' === $this->args['usar_content_render'] );
            $postType = isset( $this->args['post_type'] ) ? sanitize_key( $this->args['post_type'] ) : 'post';

            if ( $usar_cr && class_exists( '\\Glory\\Components\\ContentRender' ) ) {
                $callable    = [ '\\Glory\\Components\\ContentRender', 'defaultTemplate' ];
                $template_id = isset( $this->args['template_id'] ) ? (string) $this->args['template_id'] : '';
                if ( '' !== $template_id && '__default' !== $template_id && class_exists( '\\Glory\\Utility\\TemplateRegistry' ) ) {
                    $applies = call_user_func( [ '\\Glory\\Utility\\TemplateRegistry', 'appliesTo' ], $template_id );
                    if ( ! empty( $applies ) && is_array( $applies ) && ! in_array( $postType, $applies, true ) ) {
                        if ( current_user_can( 'manage_options' ) ) {
                            $html .= '<div class="glory-warning">' . esc_html( sprintf( __( 'La plantilla seleccionada no aplica a “%s”. Se usará la plantilla por defecto.', 'glory-ab' ), $postType ) ) . '</div>';
                        }
                    } else {
                        $c = call_user_func( [ '\\Glory\\Utility\\TemplateRegistry', 'get' ], $template_id );
                        if ( is_callable( $c ) ) {
                            $callable = $c;
                        }
                    }
                }

                $argumentosConsulta = [];
                if ( ! empty( $this->args['argumentos_json'] ) ) {
                    $json   = trim( (string) $this->args['argumentos_json'] );
                    $parsed = json_decode( $json, true );
                    if ( is_array( $parsed ) ) {
                        $argumentosConsulta = $parsed;
                    } else {
                        if ( current_user_can( 'manage_options' ) ) {
                            $html .= '<div class="glory-warning">' . esc_html__( 'JSON inválido en argumentos de consulta. Ignorando.', 'glory-ab' ) . '</div>';
                        }
                    }
                }

                // Aplicar post__in desde CSV y/o selección múltiple
                $ids = [];
                if ( ! empty( $this->args['post_ids'] ) ) {
                    $csv = (string) $this->args['post_ids'];
                    $ids = array_filter( array_map( 'absint', array_map( 'trim', explode( ',', $csv ) ) ) );
                }
                if ( ! empty( $this->args['post_ids_select'] ) ) {
                    $sel = $this->args['post_ids_select'];
                    if ( is_string( $sel ) ) {
                        $sel = explode( ',', $sel );
                    }
                    if ( is_array( $sel ) ) {
                        $sel = array_filter( array_map( 'absint', array_map( 'trim', $sel ) ) );
                        $ids = array_values( array_unique( array_merge( $ids, $sel ) ) );
                    }
                }
                if ( ! empty( $ids ) ) {
                    $argumentosConsulta['post__in'] = $ids;
                    $argumentosConsulta['orderby'] = 'post__in';
                }

                $orden     = isset( $this->args['orden'] ) ? (string) $this->args['orden'] : 'fecha';
                $metaKey   = isset( $this->args['meta_key'] ) ? trim( (string) $this->args['meta_key'] ) : '';
                $metaOrder = isset( $this->args['meta_order'] ) ? strtoupper( (string) $this->args['meta_order'] ) : 'ASC';

				$config = [
                    'publicacionesPorPagina' => isset( $this->args['publicaciones_por_pagina'] ) ? (int) $this->args['publicaciones_por_pagina'] : 10,
                    'claseContenedor'        => $this->args['clase_contenedor'] ?? 'glory-content-list',
                    'claseItem'              => $this->args['clase_item'] ?? 'glory-content-item',
                    'paginacion'             => ( isset( $this->args['paginacion'] ) && 'yes' === $this->args['paginacion'] ),
                    'plantillaCallback'      => $callable,
                    'argumentosConsulta'     => $argumentosConsulta,
                    'orden'                  => ( 'meta' === $orden && '' !== $metaKey ) ? $metaOrder : ( 'random' === $orden ? 'random' : 'fecha' ),
                    'metaKey'                => '' !== $metaKey ? $metaKey : null,
                    'minPaginas'             => isset( $this->args['min_paginas'] ) ? (int) $this->args['min_paginas'] : 1,
                    'tiempoCache'            => isset( $this->args['tiempo_cache'] ) ? (int) $this->args['tiempo_cache'] : 3600,
                    'forzarSinCache'         => ( isset( $this->args['forzar_sin_cache'] ) && 'yes' === $this->args['forzar_sin_cache'] ),
                    'acciones'               => ! empty( $this->args['acciones'] ) ? array_map( 'trim', explode( ',', (string) $this->args['acciones'] ) ) : [],
                    'submenu'                => ( isset( $this->args['submenu'] ) && 'yes' === $this->args['submenu'] ),
                    'eventoAccion'           => $this->args['evento_accion'] ?? 'dblclick',
                    'selectorItem'           => $this->args['selector_item'] ?? '[id^="post-"]',
					'linkEnabled'            => ( ! isset( $this->args['link_enabled'] ) || 'yes' === (string) $this->args['link_enabled'] ),
					// Imagen: optimización/calidad
					'imgOptimize'            => ( ! isset( $this->args['img_optimize'] ) || 'yes' === (string) $this->args['img_optimize'] ),
					'imgQuality'             => isset( $this->args['img_quality'] ) ? (int) $this->args['img_quality'] : 60,
					'imgSize'                => isset( $this->args['img_size'] ) ? (string) $this->args['img_size'] : 'medium',
                ];

				// Clase única por instancia para poder aplicar CSS aislado
				$instanceClass = 'glory-cr-' . substr( md5( uniqid( '', true ) ), 0, 8 );
				$config['claseContenedor'] = trim( (string) ( $config['claseContenedor'] ?? 'glory-content-list' ) . ' ' . $instanceClass );
				$config['claseItem'] = trim( (string) ( $config['claseItem'] ?? 'glory-content-item' ) . ' ' . $instanceClass . '__item' );

                ob_start();
                try {
                    $this->enable_dedup_filters();
                    call_user_func( [ '\\Glory\\Components\\ContentRender', 'print' ], $postType, $config );
                } catch ( \Throwable $t ) {
                    if ( current_user_can( 'manage_options' ) ) {
                        $html .= '<div class="glory-widget-error">' . esc_html( $t->getMessage() ) . '</div>';
                    }
                } finally {
                    $this->disable_dedup_filters();
                }
				$html .= ob_get_clean();

				// CSS inline por instancia (layout, imagen, título, enlace)
				$css = $this->build_instance_css( $instanceClass );
				if ( '' !== $css ) {
					$html .= '<style id="' . esc_attr( $instanceClass ) . '-css">' . $css . '</style>';
				}

				// JS para desactivar enlaces si corresponde
				$selector = '.' . $instanceClass;
				$is_link_enabled = ( isset( $this->args['link_enabled'] ) && 'yes' === (string) $this->args['link_enabled'] );
				if ( ! $is_link_enabled ) {
					$html .= '<script>(function(){var s=' . wp_json_encode( $selector ) . ';function prevent(e){var a=e.target.closest("a");if(a&&a.closest(s)){e.preventDefault();e.stopPropagation();}}document.addEventListener("click",prevent,true);document.addEventListener("keydown",function(e){if((e.key||e.keyCode)==="Enter"||e.keyCode===13){prevent(e);}},true);})();</script>';
				}

				// JS carrusel por instancia
				$is_carousel = ( isset( $this->args['carousel'] ) && 'yes' === (string) $this->args['carousel'] );
				if ( $is_carousel ) {
					$speed = isset( $this->args['carousel_speed'] ) ? (float) $this->args['carousel_speed'] : 20.0;
					$html .= '<script>(window.GloryCarousel?window.GloryCarousel:window.GloryCarouselQueue=(window.GloryCarouselQueue||[])).init?window.GloryCarousel.init(' . wp_json_encode( $selector ) . ',{"speed":' . json_encode( $speed ) . '}):window.GloryCarouselQueue.push({selector:' . wp_json_encode( $selector ) . ',options:{"speed":' . json_encode( $speed ) . '}});</script>';
				} else {
					$html .= '<script>window.GloryCarousel&&window.GloryCarousel.stop(' . wp_json_encode( $selector ) . ');</script>';
				}

                $this->counter++;
                $this->on_render();
                return $html;
            }

            $function_name = isset( $this->args['function_name'] ) ? (string) $this->args['function_name'] : '';
            if ( '' !== $function_name && function_exists( $function_name ) ) {
                ob_start();
                try {
                    call_user_func( $function_name );
                } catch ( \Throwable $t ) {
                    if ( current_user_can( 'manage_options' ) ) {
                        echo '<div class="glory-widget-error">' . esc_html( $t->getMessage() ) . '</div>';
                    }
                }
				$html .= ob_get_clean();

                $this->counter++;
                $this->on_render();
                return $html;
            }

            $sc = isset( $this->args['shortcode'] ) ? trim( (string) $this->args['shortcode'] ) : '';
            if ( '' !== $sc ) {
                $html .= do_shortcode( $sc );
                $this->counter++;
                $this->on_render();
                return $html;
            }

            $raw = isset( $this->args['raw_content'] ) ? (string) $this->args['raw_content'] : '';
            $html .= $raw;

            $this->counter++;
            $this->on_render();
            return $html;
        }
        
        private function build_instance_css( string $instanceClass ): string {
            $containerClass = '.' . $instanceClass;
            $itemClass = '.' . $instanceClass . '__item';
            $selector_item = isset( $this->args['selector_item'] ) ? (string) $this->args['selector_item'] : '';
            $scopedSelector = '' !== trim( $selector_item ) ? $containerClass . ' ' . trim( $selector_item ) : '';

            $display_mode = isset( $this->args['display_mode'] ) ? (string) $this->args['display_mode'] : 'flex';
            $flex_direction = isset( $this->args['flex_direction'] ) ? (string) $this->args['flex_direction'] : 'row';
            $flex_wrap = isset( $this->args['flex_wrap'] ) ? (string) $this->args['flex_wrap'] : 'wrap';
            $gap = isset( $this->args['gap'] ) ? (string) $this->args['gap'] : '20px';
            $align_items = isset( $this->args['align_items'] ) ? (string) $this->args['align_items'] : 'stretch';
            $justify_content = isset( $this->args['justify_content'] ) ? (string) $this->args['justify_content'] : 'flex-start';
            $grid_min_width = isset( $this->args['grid_min_width'] ) ? (string) $this->args['grid_min_width'] : '250px';
            $grid_auto_fit = ( isset( $this->args['grid_auto_fit'] ) && 'yes' === $this->args['grid_auto_fit'] ) ? 'auto-fit' : 'auto-fill';

            $mode = isset( $this->args['grid_columns_mode'] ) ? (string) $this->args['grid_columns_mode'] : 'fixed';

            $large_cols = isset( $this->args['grid_columns'] ) ? (int) $this->args['grid_columns'] : 4;
            $medium_cols = isset( $this->args['grid_columns_medium'] ) && '' !== (string) $this->args['grid_columns_medium'] ? (int) $this->args['grid_columns_medium'] : $large_cols;
            $small_cols  = isset( $this->args['grid_columns_small'] ) && '' !== (string) $this->args['grid_columns_small'] ? (int) $this->args['grid_columns_small'] : $medium_cols;

            $min_large = isset( $this->args['grid_min_columns'] ) ? (int) $this->args['grid_min_columns'] : 1;
            $max_large = isset( $this->args['grid_max_columns'] ) ? (int) $this->args['grid_max_columns'] : 12;
            $min_medium = isset( $this->args['grid_min_columns_medium'] ) && '' !== (string) $this->args['grid_min_columns_medium'] ? (int) $this->args['grid_min_columns_medium'] : $min_large;
            $max_medium = isset( $this->args['grid_max_columns_medium'] ) && '' !== (string) $this->args['grid_max_columns_medium'] ? (int) $this->args['grid_max_columns_medium'] : $max_large;
            $min_small = isset( $this->args['grid_min_columns_small'] ) && '' !== (string) $this->args['grid_min_columns_small'] ? (int) $this->args['grid_min_columns_small'] : $min_medium;
            $max_small = isset( $this->args['grid_max_columns_small'] ) && '' !== (string) $this->args['grid_max_columns_small'] ? (int) $this->args['grid_max_columns_small'] : $max_medium;

            // Forzar layout si carrusel activo
            $is_carousel = isset( $this->args['carousel'] ) && 'yes' === (string) $this->args['carousel'];
            if ( $is_carousel ) {
                $display_mode = 'flex';
                $flex_direction = 'row';
                $flex_wrap = 'nowrap';
            }

            $img_show = ! isset( $this->args['img_show'] ) || 'yes' === $this->args['img_show'];
            $img_aspect_ratio = isset( $this->args['img_aspect_ratio'] ) ? (string) $this->args['img_aspect_ratio'] : '1 / 1';
            $img_object_fit = isset( $this->args['img_object_fit'] ) ? (string) $this->args['img_object_fit'] : 'cover';
            $img_min_width = isset( $this->args['img_min_width'] ) ? (string) $this->args['img_min_width'] : '';
            $img_width = isset( $this->args['img_width'] ) ? (string) $this->args['img_width'] : '';
            $img_height = isset( $this->args['img_height'] ) ? (string) $this->args['img_height'] : '';
            $img_max_width = isset( $this->args['img_max_width'] ) ? (string) $this->args['img_max_width'] : '';
            $img_min_height = isset( $this->args['img_min_height'] ) ? (string) $this->args['img_min_height'] : '';
            $img_max_height = isset( $this->args['img_max_height'] ) ? (string) $this->args['img_max_height'] : '';

            $title_font_family = isset( $this->args['title_font_family'] ) ? (string) $this->args['title_font_family'] : '';
            $title_font_size = isset( $this->args['title_font_size'] ) ? (string) $this->args['title_font_size'] : '';
            $title_font_weight = isset( $this->args['title_font_weight'] ) ? (string) $this->args['title_font_weight'] : '';
            $title_text_transform = isset( $this->args['title_text_transform'] ) ? (string) $this->args['title_text_transform'] : '';
			$title_max_width = isset( $this->args['title_max_width'] ) ? (string) $this->args['title_max_width'] : '';

            $css = '';

            // Layout container
				if ( 'grid' === $display_mode ) {
                    $css .= $containerClass . '{display:grid;gap:' . esc_attr( $gap ) . ';grid-template-columns:repeat(' . $grid_auto_fit . ', minmax(' . esc_attr( $grid_min_width ) . ', 1fr));}';
                    if ( 'fixed' === $mode ) {
                        $css .= '@media (min-width: 980px) {' . $containerClass . '{grid-template-columns:repeat(' . $large_cols . ', 1fr);}}';
                        $css .= '@media (min-width: 768px) and (max-width: 979px) {' . $containerClass . '{grid-template-columns:repeat(' . $medium_cols . ', 1fr);}}';
                        $css .= '@media (max-width: 767px) {' . $containerClass . '{grid-template-columns:repeat(' . $small_cols . ', 1fr);}}';
                    } else {
                        $min_size_l = 'max(' . esc_attr( $grid_min_width ) . ', calc(100% / ' . $max_large . '))';
                        $max_size_l = 'min(1fr, calc(100% / ' . $min_large . '))';
                        $css .= '@media (min-width: 980px) {' . $containerClass . '{grid-template-columns:repeat(' . $grid_auto_fit . ', minmax(' . $min_size_l . ', ' . $max_size_l . '));}}';
                        $min_size_m = 'max(' . esc_attr( $grid_min_width ) . ', calc(100% / ' . $max_medium . '))';
                        $max_size_m = 'min(1fr, calc(100% / ' . $min_medium . '))';
                        $css .= '@media (min-width: 768px) and (max-width: 979px) {' . $containerClass . '{grid-template-columns:repeat(' . $grid_auto_fit . ', minmax(' . $min_size_m . ', ' . $max_size_m . '));}}';
                        $min_size_s = 'max(' . esc_attr( $grid_min_width ) . ', calc(100% / ' . $max_small . '))';
                        $max_size_s = 'min(1fr, calc(100% / ' . $min_small . '))';
                        $css .= '@media (max-width: 767px) {' . $containerClass . '{grid-template-columns:repeat(' . $grid_auto_fit . ', minmax(' . $min_size_s . ', ' . $max_size_s . '));}}';
                    }
				} elseif ( 'flex' === $display_mode ) {
					$css .= $containerClass . '{display:flex;flex-direction:' . esc_attr( $flex_direction ) . ';flex-wrap:' . esc_attr( $flex_wrap ) . ';gap:' . esc_attr( $gap ) . ';align-items:' . esc_attr( $align_items ) . ';justify-content:' . esc_attr( $justify_content ) . ';}';
            } else {
                $css .= $containerClass . '{display:block;}';
            }

            // Imagen estándar dentro del item (target por contenedor e item)
            // En carrusel no forzamos width:100% para no alterar tamaños definidos externamente
            $css .= $containerClass . ' .glory-cr__image,' . $itemClass . ' .glory-cr__image{display:' . ( $img_show ? 'block' : 'none' ) . ';aspect-ratio:' . esc_attr( $img_aspect_ratio ) . ';object-fit:' . esc_attr( $img_object_fit ) . ';max-width:100%;' . ( '' !== $img_width ? 'width:' . esc_attr( $img_width ) . ';' : ( $is_carousel ? '' : 'width:100%;' ) );
            if ( '' !== $img_min_width ) {
                $css .= 'min-width:' . esc_attr( $img_min_width ) . ';';
            }
            if ( '' !== $img_max_width ) {
                $css .= 'max-width:' . esc_attr( $img_max_width ) . ';';
            }
            if ( '' !== $img_height ) {
                $css .= 'height:' . esc_attr( $img_height ) . ';';
            } else {
                $css .= 'height:auto;';
            }
            if ( '' !== $img_min_height ) {
                $css .= 'min-height:' . esc_attr( $img_min_height ) . ';';
            }
            if ( '' !== $img_max_height ) {
                $css .= 'max-height:' . esc_attr( $img_max_height ) . ';';
            }
            $css .= '}';

            // En carrusel, evitar que los items se encojan
			if ( $is_carousel ) {
				$css .= $itemClass . '{flex:0 0 auto;min-width:0;}';
				$css .= $containerClass . '{width:max-content;}';
			}

			// Desactivar enlaces si link_enabled = no
			$link_enabled = ! isset( $this->args['link_enabled'] ) || 'yes' === (string) $this->args['link_enabled'];
			if ( ! $link_enabled ) {
				$css .= $containerClass . ' a{pointer-events:none;cursor:default;}';
			}

			// Título solo en hover (opcional)
			$title_show_on_hover = isset( $this->args['title_show_on_hover'] ) && 'yes' === (string) $this->args['title_show_on_hover'];
			if ( $title_show_on_hover ) {
				// Regla amplia por contenedor e item para mayor robustez (incluye fallback por selector_item)
				$hideSelectors = $containerClass . ' .glory-cr__title,' . $containerClass . ' .entry-title,' . $containerClass . ' .fusion-post-title,'
					. $itemClass . ' .glory-cr__title,' . $itemClass . ' .entry-title,' . $itemClass . ' .fusion-post-title';
				if ( '' !== $scopedSelector ) {
					$hideSelectors .= ',' . $scopedSelector . ' .glory-cr__title,' . $scopedSelector . ' .entry-title,' . $scopedSelector . ' .fusion-post-title';
				}
				$css .= $hideSelectors . '{opacity:0;visibility:hidden;transition:opacity .2s ease;}';

				// Mostrar cuando el item está en hover o focus-within
				$css .= $itemClass . '.is-hover .glory-cr__title,' . $itemClass . '.is-hover .entry-title,' . $itemClass . '.is-hover .fusion-post-title{opacity:1;visibility:visible;}';
				$css .= $itemClass . ':hover .glory-cr__title,' . $itemClass . ':focus-within .glory-cr__title,'
					. $itemClass . ':hover .entry-title,' . $itemClass . ':focus-within .entry-title,'
					. $itemClass . ':hover .fusion-post-title,' . $itemClass . ':focus-within .fusion-post-title{opacity:1;visibility:visible;}';
				if ( '' !== $scopedSelector ) {
					$css .= $scopedSelector . '.is-hover .glory-cr__title,' . $scopedSelector . '.is-hover .entry-title,' . $scopedSelector . '.is-hover .fusion-post-title{opacity:1;visibility:visible;}';
					$css .= $scopedSelector . ':hover .glory-cr__title,' . $scopedSelector . ':focus-within .glory-cr__title,'
						. $scopedSelector . ':hover .entry-title,' . $scopedSelector . ':focus-within .entry-title,'
						. $scopedSelector . ':hover .fusion-post-title,' . $scopedSelector . ':focus-within .fusion-post-title{opacity:1;visibility:visible;}';
				}

				// Mostrar cuando se hace hover directamente sobre la imagen o su enlace, usando hermanos
				$css .= $itemClass . ' .glory-cr__image:hover + .glory-cr__title,' . $itemClass . ' .glory-cr__image:hover ~ .glory-cr__title{opacity:1;visibility:visible;}';
				$css .= $itemClass . ' a:hover .glory-cr__image + .glory-cr__title,' . $itemClass . ' a:hover .glory-cr__image ~ .glory-cr__title{opacity:1;visibility:visible;}';
				if ( '' !== $scopedSelector ) {
					$css .= $scopedSelector . ' .glory-cr__image:hover + .glory-cr__title,' . $scopedSelector . ' .glory-cr__image:hover ~ .glory-cr__title{opacity:1;visibility:visible;}';
					$css .= $scopedSelector . ' a:hover .glory-cr__image + .glory-cr__title,' . $scopedSelector . ' a:hover .glory-cr__image ~ .glory-cr__title{opacity:1;visibility:visible;}';
				}
			}

			// Mostrar/ocultar título completo
            $title_show = ! isset( $this->args['title_show'] ) || 'yes' === (string) $this->args['title_show'];
            $css .= $containerClass . ' .glory-cr__title,' . $itemClass . ' .glory-cr__title{display:' . ( $title_show ? 'block' : 'none' ) . ';';
            if ( '' !== $title_font_family ) {
                $css .= 'font-family:' . esc_attr( $title_font_family ) . ';';
            }
            if ( '' !== $title_font_size ) {
                $css .= 'font-size:' . esc_attr( $title_font_size ) . ';';
            }
            if ( '' !== $title_font_weight ) {
                $css .= 'font-weight:' . esc_attr( $title_font_weight ) . ';';
            }
            if ( '' !== $title_text_transform ) {
                $css .= 'text-transform:' . esc_attr( $title_text_transform ) . ';';
            }
			if ( '' !== $title_max_width ) {
				$css .= 'max-width:' . esc_attr( $title_max_width ) . ';';
			}
            $css .= '}';

			// Posición del título respecto a la imagen (requiere contenedor .glory-cr__stack)
			$title_position = isset( $this->args['title_position'] ) ? (string) $this->args['title_position'] : 'top';
			$css .= $itemClass . ' .glory-cr__stack{display:flex;flex-direction:column;}';
			$stackTitleSel = $itemClass . ' .glory-cr__stack .glory-cr__title,'
				. $itemClass . ' .glory-cr__stack .entry-title,'
				. $itemClass . ' .glory-cr__stack .fusion-post-title,'
				. $itemClass . ' .glory-cr__stack .portafolio-info';
			$stackImageSel = $itemClass . ' .glory-cr__stack .glory-cr__image';
			if ( 'bottom' === $title_position ) {
				$css .= $stackImageSel . '{order:1;}';
				$css .= $stackTitleSel . '{order:2;}';
			} else {
				$css .= $stackTitleSel . '{order:1;}';
				$css .= $stackImageSel . '{order:2;}';
			}

            return $css;
        }

        private function enable_dedup_filters(): void {
            if ( $this->dedupActive ) {
                return;
            }
            $this->dedupActive = true;
            add_filter( 'posts_distinct', [ $this, 'filter_posts_distinct' ], 10, 2 );
            add_filter( 'the_posts', [ $this, 'filter_the_posts_dedup' ], 10, 2 );
        }

        private function disable_dedup_filters(): void {
            if ( ! $this->dedupActive ) {
                return;
            }
            remove_filter( 'posts_distinct', [ $this, 'filter_posts_distinct' ], 10 );
            remove_filter( 'the_posts', [ $this, 'filter_the_posts_dedup' ], 10 );
            $this->dedupActive = false;
        }

        public function filter_posts_distinct( $distinct, $query ) {
            if ( $this->dedupActive ) {
                return 'DISTINCT';
            }
            return $distinct;
        }

        public function filter_the_posts_dedup( $posts, $query ) {
            if ( ! $this->dedupActive || ! is_array( $posts ) ) {
                return $posts;
            }
            $seen = [];
            $deduped = [];
            foreach ( $posts as $post ) {
                $id = is_object( $post ) && isset( $post->ID ) ? (int) $post->ID : (int) $post;
                if ( $id && ! isset( $seen[ $id ] ) ) {
                    $seen[ $id ] = true;
                    $deduped[] = $post;
                }
            }
            return $deduped;
        }
    }

    new FusionSC_GloryContentRender();
}


