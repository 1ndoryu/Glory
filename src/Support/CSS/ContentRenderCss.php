<?php

namespace Glory\Support\CSS;

class ContentRenderCss
{
	/**
	 * Genera el CSS por instancia para ContentRender.
	 *
	 * Este builder es agnóstico y reutilizable. Acepta los mismos $args que el shortcode
	 * y una configuración opcional de instancia para casos avanzados (por ejemplo, layout interno
	 * resuelto por la plantilla, flags de toggle, etc.).
	 */
	public static function build( string $instanceClass, array $args, ?array $instanceConfig = null, string $currentModoInteraccion = 'normal', bool $useFusionTypography = true ): string
	{
		$enableHorizontalDrag = isset( $args['enable_horizontal_drag'] ) && 'yes' === $args['enable_horizontal_drag'] && 'normal' === $currentModoInteraccion;
		$containerClass = '.' . $instanceClass;
		$itemClass = '.' . $instanceClass . '__item';
		$selector_item = isset( $args['selector_item'] ) ? (string) $args['selector_item'] : '';
		$scopedSelector = '' !== trim( $selector_item ) ? $containerClass . ' ' . trim( $selector_item ) : '';

		$layout = self::resolveLayoutOptions( $args, $currentModoInteraccion );
		$display_mode = $layout['display_mode'];
		$flex_direction = $layout['flex_direction'];
		$flex_wrap = $layout['flex_wrap'];
		$gap = $layout['gap'];
		$align_items = $layout['align_items'];
		$justify_content = $layout['justify_content'];
		$grid_min_width = $layout['grid_min_width'];
		$grid_auto_fit = $layout['grid_auto_fit'];
		$mode = $layout['grid_columns_mode'];
		$large_cols = $layout['grid_columns'];
		$medium_cols = $layout['grid_columns_medium'];
		$small_cols = $layout['grid_columns_small'];
		$min_large = $layout['grid_min_columns'];
		$max_large = $layout['grid_max_columns'];
		$min_medium = $layout['grid_min_columns_medium'];
		$max_medium = $layout['grid_max_columns_medium'];
		$min_small = $layout['grid_min_columns_small'];
		$max_small = $layout['grid_max_columns_small'];
		$modo_interaccion = $layout['modo_interaccion'];

		$currentConfig = $instanceConfig ?? [];
		$internalLayout = $currentConfig['internalLayoutOptionsResponsive'] ?? $currentConfig['internalLayoutOptions'] ?? [];

		$img_show = ! isset( $args['img_show'] ) || 'yes' === $args['img_show'];
		$img_aspect_ratio = isset( $args['img_aspect_ratio'] ) ? (string) $args['img_aspect_ratio'] : '1 / 1';
		$img_object_fit = isset( $args['img_object_fit'] ) ? (string) $args['img_object_fit'] : 'cover';

		$normalize = static function ( $v, string $baseName ) use ( $args ) {
			return Responsive::normalize( $v, $baseName, $args );
		};

		$img_min_width_r  = $normalize( $args['img_min_width'] ?? '', 'img_min_width' );
		$img_width_r      = $normalize( $args['img_width'] ?? '', 'img_width' );
		$img_max_width_r  = $normalize( $args['img_max_width'] ?? '', 'img_max_width' );
		$img_min_height_r = $normalize( $args['img_min_height'] ?? '', 'img_min_height' );
		$img_height_r     = $normalize( $args['img_height'] ?? '', 'img_height' );
		$img_max_height_r = $normalize( $args['img_max_height'] ?? '', 'img_max_height' );

		$title_styles = '';
		$title_bold_param = isset( $args['title_bold'] ) ? (string) $args['title_bold'] : '';
		if ( class_exists( 'Fusion_Builder_Element_Helper' ) && $useFusionTypography ) {
			// Solo procesar tipografías de Fusion si existen las claves necesarias
			$fusion_font_keys = [
				'fusion_font_family_title_font',
				'fusion_font_variant_title_font',
				'fusion_font_size_title_font',
				'fusion_line_height_title_font',
				'fusion_letter_spacing_title_font'
			];

			$has_fusion_typography = false;
			foreach ( $fusion_font_keys as $key ) {
				if ( isset( $args[ $key ] ) && ! empty( $args[ $key ] ) ) {
					$has_fusion_typography = true;
					break;
				}
			}

			if ( $has_fusion_typography ) {
				$title_styles .= \Fusion_Builder_Element_Helper::get_font_styling( $args, 'title_font' );
			}
		}

		$internal_styles = '';
		$internal_bold_param = isset( $args['internal_bold'] ) ? (string) $args['internal_bold'] : '';
		$internal_enabled = isset( $args['internal_typography_enable'] ) && 'yes' === (string) $args['internal_typography_enable'];
		if ( $internal_enabled ) {
			$sanitizer = function_exists( 'fusion_library' ) ? fusion_library()->sanitize : null;
			$internal_responsive = [
				'internal_font_size'             => '',
				'internal_line_height'           => '',
				'internal_letter_spacing'        => '',
				'internal_font_size_medium'      => '',
				'internal_line_height_medium'    => '',
				'internal_letter_spacing_medium' => '',
				'internal_font_size_small'       => '',
				'internal_line_height_small'     => '',
				'internal_letter_spacing_small'  => '',
			];

			foreach ( array_keys( $internal_responsive ) as $key ) {
				$value = isset( $args[ $key ] ) ? (string) $args[ $key ] : '';
				if ( '' === $value ) {
					continue;
				}
				if ( $sanitizer ) {
					if ( false !== strpos( $key, 'line_height' ) ) {
						$value = $sanitizer->size( $value );
					} else {
						$value = $sanitizer->get_value_with_unit( $value );
					}
				}
				if ( '' === $value ) {
					continue;
				}
				$args[ $key ] = $value;
				$css_prop = str_contains( $key, 'font_size' ) ? 'font-size' : ( str_contains( $key, 'line_height' ) ? 'line-height' : 'letter-spacing' );
				if ( in_array( $key, [ 'internal_font_size', 'internal_line_height', 'internal_letter_spacing' ], true ) ) {
					$internal_styles .= $css_prop . ':' . esc_attr( $value ) . ';';
				}
			}

			$internal_font_family  = isset( $args['fusion_font_family_internal_font'] ) ? (string) $args['fusion_font_family_internal_font'] : '';
			$internal_font_variant = isset( $args['fusion_font_variant_internal_font'] ) ? (string) $args['fusion_font_variant_internal_font'] : '';
			if ( '' !== $internal_font_family ) {
				$internal_styles .= 'font-family:' . esc_attr( $internal_font_family ) . ';';
			}
			$internal_font_weight = $internal_font_variant;
			if ( 'yes' === $internal_bold_param ) {
				$internal_font_weight = '700';
			} elseif ( 'no' === $internal_bold_param && array_key_exists( 'internal_bold', $args ) ) {
				$internal_font_weight = '400';
			}
			if ( '' !== $internal_font_weight ) {
				$internal_styles .= 'font-weight:' . esc_attr( $internal_font_weight ) . ';';
			}
			$internal_text_transform = isset( $args['internal_text_transform'] ) ? (string) $args['internal_text_transform'] : '';
			if ( '' !== $internal_text_transform ) {
				$internal_styles .= 'text-transform:' . esc_attr( $internal_text_transform ) . ';';
			}
		}

		$sanitizer = function_exists( 'fusion_library' ) ? fusion_library()->sanitize : null;
		$responsive_values = [
			'font_size'              => '',
			'line_height'            => '',
			'letter_spacing'         => '',
			'font_size_medium'       => '',
			'line_height_medium'     => '',
			'letter_spacing_medium'  => '',
			'font_size_small'        => '',
			'line_height_small'      => '',
			'letter_spacing_small'   => '',
		];

		foreach ( array_keys( $responsive_values ) as $key ) {
			$value = isset( $args[ $key ] ) ? (string) $args[ $key ] : '';
			if ( '' === $value ) {
				continue;
			}
			if ( $sanitizer ) {
				if ( false !== strpos( $key, 'line_height' ) ) {
					$value = $sanitizer->size( $value );
				} else {
					$value = $sanitizer->get_value_with_unit( $value );
				}
			}
			if ( '' === $value ) {
				continue;
			}
			$args[ $key ]      = $value;
			$responsive_values[ $key ] = $value;
			if ( in_array( $key, [ 'font_size', 'line_height', 'letter_spacing' ], true ) ) {
				$css_prop = 'font_size' === $key ? 'font-size' : ( 'line_height' === $key ? 'line-height' : 'letter-spacing' );
				$title_styles .= $css_prop . ':' . esc_attr( $value ) . ';';
			}
		}

		if ( 'yes' === $title_bold_param ) {
			$title_styles .= 'font-weight:700;';
		} elseif ( 'no' === $title_bold_param && array_key_exists( 'title_bold', $args ) ) {
			$title_styles .= 'font-weight:400;';
		}
		$title_transform = isset( $args['title_text_transform'] ) ? (string) $args['title_text_transform'] : '';
		$title_min_width = isset( $args['title_min_width'] ) ? (string) $args['title_min_width'] : '';
		$title_width = isset( $args['title_width'] ) ? (string) $args['title_width'] : '';
		$title_max_width = isset( $args['title_max_width'] ) ? (string) $args['title_max_width'] : '';

		// Variables para anchos del contenido
		$content_min_width = isset( $args['content_min_width'] ) ? (string) $args['content_min_width'] : '';
		$content_width = isset( $args['content_width'] ) ? (string) $args['content_width'] : '';
		$content_max_width = isset( $args['content_max_width'] ) ? (string) $args['content_max_width'] : '';
		if ( '' !== $title_transform ) {
			$title_styles .= 'text-transform:' . esc_attr( $title_transform ) . ';';
		}

		$css = '';

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

		$css .= $containerClass . ' .glory-cr__image,' . $itemClass . ' .glory-cr__image{display:' . ( $img_show ? 'block' : 'none' ) . ';aspect-ratio:' . esc_attr( $img_aspect_ratio ) . ';object-fit:' . esc_attr( $img_object_fit ) . ';max-width:100%;' . ( '' !== ( $img_width_r['large'] ?? '' ) ? 'width:' . esc_attr( $img_width_r['large'] ) . ' !important;' : ( 'carousel' === $modo_interaccion ? '' : 'width:100% !important;' ) );
		if ( '' !== ( $img_min_width_r['large'] ?? '' ) ) { $css .= 'min-width:' . esc_attr( $img_min_width_r['large'] ) . ' !important;'; }
		if ( '' !== ( $img_max_width_r['large'] ?? '' ) ) { $css .= 'max-width:' . esc_attr( $img_max_width_r['large'] ) . ' !important;'; }
		if ( '' !== ( $img_height_r['large'] ?? '' ) ) { $css .= 'height:' . esc_attr( $img_height_r['large'] ) . ' !important;'; } else { $css .= 'height:auto !important;'; }
		if ( '' !== ( $img_min_height_r['large'] ?? '' ) ) { $css .= 'min-height:' . esc_attr( $img_min_height_r['large'] ) . ' !important;'; }
		if ( '' !== ( $img_max_height_r['large'] ?? '' ) ) { $css .= 'max-height:' . esc_attr( $img_max_height_r['large'] ) . ' !important;'; }
		$css .= '}';

		$img_min_width_m = (string) ( $img_min_width_r['medium'] ?? '' );
		$img_width_m     = (string) ( $img_width_r['medium'] ?? '' );
		$img_max_width_m = (string) ( $img_max_width_r['medium'] ?? '' );
		$img_min_height_m= (string) ( $img_min_height_r['medium'] ?? '' );
		$img_height_m    = (string) ( $img_height_r['medium'] ?? '' );
		$img_max_height_m= (string) ( $img_max_height_r['medium'] ?? '' );
		$has_medium_rule = ( '' !== $img_min_width_m || '' !== $img_width_m || '' !== $img_max_width_m || '' !== $img_min_height_m || '' !== $img_height_m || '' !== $img_max_height_m );
		if ( $has_medium_rule ) {
			$rule = $containerClass . ' .glory-cr__image,' . $itemClass . ' .glory-cr__image{';
			if ( '' !== $img_width_m ) { $rule .= 'width:' . esc_attr( $img_width_m ) . ' !important;'; } elseif ( 'carousel' !== $modo_interaccion ) { /* keep default */ }
			if ( '' !== $img_min_width_m ) { $rule .= 'min-width:' . esc_attr( $img_min_width_m ) . ' !important;'; }
			if ( '' !== $img_max_width_m ) { $rule .= 'max-width:' . esc_attr( $img_max_width_m ) . ' !important;'; }
			if ( '' !== $img_height_m ) { $rule .= 'height:' . esc_attr( $img_height_m ) . ' !important;'; }
			if ( '' !== $img_min_height_m ) { $rule .= 'min-height:' . esc_attr( $img_min_height_m ) . ' !important;'; }
			if ( '' !== $img_max_height_m ) { $rule .= 'max-height:' . esc_attr( $img_max_height_m ) . ' !important;'; }
			$rule .= '}';
			$css .= '@media (min-width: 768px) and (max-width: 979px){' . $rule . '}';
		}

		$img_min_width_s = (string) ( $img_min_width_r['small'] ?? '' );
		$img_width_s     = (string) ( $img_width_r['small'] ?? '' );
		$img_max_width_s = (string) ( $img_max_width_r['small'] ?? '' );
		$img_min_height_s= (string) ( $img_min_height_r['small'] ?? '' );
		$img_height_s    = (string) ( $img_height_r['small'] ?? '' );
		$img_max_height_s= (string) ( $img_max_height_r['small'] ?? '' );
		$has_small_rule  = ( '' !== $img_min_width_s || '' !== $img_width_s || '' !== $img_max_width_s || '' !== $img_min_height_s || '' !== $img_height_s || '' !== $img_max_height_s );
		if ( $has_small_rule ) {
			$rule = $containerClass . ' .glory-cr__image,' . $itemClass . ' .glory-cr__image{';
			if ( '' !== $img_width_s ) { $rule .= 'width:' . esc_attr( $img_width_s ) . ' !important;'; } elseif ( 'carousel' !== $modo_interaccion ) { /* keep default */ }
			if ( '' !== $img_min_width_s ) { $rule .= 'min-width:' . esc_attr( $img_min_width_s ) . ' !important;'; }
			if ( '' !== $img_max_width_s ) { $rule .= 'max-width:' . esc_attr( $img_max_width_s ) . ' !important;'; }
			if ( '' !== $img_height_s ) { $rule .= 'height:' . esc_attr( $img_height_s ) . ' !important;'; }
			if ( '' !== $img_min_height_s ) { $rule .= 'min-height:' . esc_attr( $img_min_height_s ) . ' !important;'; }
			if ( '' !== $img_max_height_s ) { $rule .= 'max-height:' . esc_attr( $img_max_height_s ) . ' !important;'; }
			$rule .= '}';
			$css .= '@media (max-width: 767px){' . $rule . '}';
		}

		if ( 'carousel' === $modo_interaccion ) {
			$css .= $itemClass . '{flex:0 0 auto;min-width:0;}';
			$css .= $containerClass . '{width:max-content;}';
		}

		if ( is_array( $internalLayout ) && ! empty( array_filter( $internalLayout, static function ( $value ) { return $value !== '' && $value !== null && $value !== []; } ) ) ) {
			$internalSelector = $containerClass . ' .glory-cr__internal';
			$internalDisplay = $internalLayout['display_mode'] ?? '';
			$internalFlexDirection = $internalLayout['flex_direction'] ?? '';
			$internalFlexWrap = $internalLayout['flex_wrap'] ?? '';
			$internalGap = $internalLayout['gap'] ?? '';
			$internalAlign = $internalLayout['align_items'] ?? '';
			$internalJustify = $internalLayout['justify_content'] ?? '';
			$internalGridMinWidth = $internalLayout['grid_min_width'] ?? '';
			$internalGridAutoFit = $internalLayout['grid_auto_fit'] ?? '';
			$internalGridMode = $internalLayout['grid_columns_mode'] ?? '';
			$internalGridCols = $internalLayout['grid_columns'] ?? [];
			$internalGridMinCols = $internalLayout['grid_min_columns'] ?? [];
			$internalGridMaxCols = $internalLayout['grid_max_columns'] ?? [];

			if ( 'grid' === $internalDisplay ) {
				$gridGap = '' !== $internalGap ? 'gap:' . esc_attr( $internalGap ) . ';' : '';
				$gridMinWidth = '' !== $internalGridMinWidth ? esc_attr( $internalGridMinWidth ) : '250px';
				$gridAutoFit = in_array( $internalGridAutoFit, [ 'auto-fit', 'auto-fill' ], true ) ? $internalGridAutoFit : 'auto-fit';
				$css .= $internalSelector . '{display:grid;' . $gridGap . 'grid-template-columns:repeat(' . $gridAutoFit . ', minmax(' . $gridMinWidth . ', 1fr));}';

				if ( 'fixed' === $internalGridMode && ! empty( $internalGridCols ) ) {
					$large = $internalGridCols['large'] ?? reset( $internalGridCols );
					$medium = $internalGridCols['medium'] ?? $large;
					$small = $internalGridCols['small'] ?? $medium;
					$css .= '@media (min-width: 980px) {' . $internalSelector . '{grid-template-columns:repeat(' . $large . ', 1fr);}}';
					$css .= '@media (min-width: 768px) and (max-width: 979px) {' . $internalSelector . '{grid-template-columns:repeat(' . $medium . ', 1fr);}}';
					$css .= '@media (max-width: 767px) {' . $internalSelector . '{grid-template-columns:repeat(' . $small . ', 1fr);}}';
				} elseif ( 'auto' === $internalGridMode && ! empty( $internalGridMinCols ) && ! empty( $internalGridMaxCols ) ) {
					$largeMin = $internalGridMinCols['large'] ?? reset( $internalGridMinCols ) ?? 1;
					$largeMax = $internalGridMaxCols['large'] ?? reset( $internalGridMaxCols ) ?? 12;
					$mediumMin = $internalGridMinCols['medium'] ?? $largeMin;
					$mediumMax = $internalGridMaxCols['medium'] ?? $largeMax;
					$smallMin = $internalGridMinCols['small'] ?? $mediumMin;
					$smallMax = $internalGridMaxCols['small'] ?? $mediumMax;
					$minSizeL = 'max(' . $gridMinWidth . ', calc(100% / ' . $largeMax . '))';
					$maxSizeL = 'min(1fr, calc(100% / ' . $largeMin . '))';
					$minSizeM = 'max(' . $gridMinWidth . ', calc(100% / ' . $mediumMax . '))';
					$maxSizeM = 'min(1fr, calc(100% / ' . $mediumMin . '))';
					$minSizeS = 'max(' . $gridMinWidth . ', calc(100% / ' . $smallMax . '))';
					$maxSizeS = 'min(1fr, calc(100% / ' . $smallMin . '))';
					$css .= '@media (min-width: 980px) {' . $internalSelector . '{grid-template-columns:repeat(' . $gridAutoFit . ', minmax(' . $minSizeL . ', ' . $maxSizeL . '));}}';
					$css .= '@media (min-width: 768px) and (max-width: 979px) {' . $internalSelector . '{grid-template-columns:repeat(' . $gridAutoFit . ', minmax(' . $minSizeM . ', ' . $maxSizeM . '));}}';
					$css .= '@media (max-width: 767px) {' . $internalSelector . '{grid-template-columns:repeat(' . $gridAutoFit . ', minmax(' . $minSizeS . ', ' . $maxSizeS . '));}}';
				}
			} elseif ( 'flex' === $internalDisplay ) {
				$css .= $internalSelector . '{display:flex;';
				if ( '' !== $internalFlexDirection ) { $css .= 'flex-direction:' . esc_attr( $internalFlexDirection ) . ';'; }
				if ( '' !== $internalFlexWrap ) { $css .= 'flex-wrap:' . esc_attr( $internalFlexWrap ) . ';'; }
				if ( '' !== $internalGap ) { $css .= 'gap:' . esc_attr( $internalGap ) . ';'; }
				if ( '' !== $internalAlign ) { $css .= 'align-items:' . esc_attr( $internalAlign ) . ';'; }
				if ( '' !== $internalJustify ) { $css .= 'justify-content:' . esc_attr( $internalJustify ) . ';'; }
				$css .= '}';
			} elseif ( 'block' === $internalDisplay ) {
				$css .= $internalSelector . '{display:block;}';
			}
		}

		$link_enabled = ! isset( $args['link_enabled'] ) || 'yes' === (string) $args['link_enabled'];
		if ( ! $link_enabled ) {
			$css .= $containerClass . ' a{pointer-events:none;cursor:default;}';
		}

		$title_show_on_hover = isset( $args['title_show_on_hover'] ) && 'yes' === (string) $args['title_show_on_hover'];
		if ( $title_show_on_hover ) {
			$hideSelectors = $containerClass . ' .glory-cr__title,' . $containerClass . ' .entry-title,' . $containerClass . ' .fusion-post-title,'
				. $itemClass . ' .glory-cr__title,' . $itemClass . ' .entry-title,' . $itemClass . ' .fusion-post-title';
			if ( '' !== $scopedSelector ) {
				$hideSelectors .= ',' . $scopedSelector . ' .glory-cr__title,' . $scopedSelector . ' .entry-title,' . $scopedSelector . ' .fusion-post-title';
			}
			$css .= $hideSelectors . '{opacity:0;visibility:hidden;transition:opacity .2s ease;}';
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
			$css .= $itemClass . ' .glory-cr__image:hover + .glory-cr__title,' . $itemClass . ' .glory-cr__image:hover ~ .glory-cr__title{opacity:1;visibility:visible;}';
			$css .= $itemClass . ' a:hover .glory-cr__image + .glory-cr__title,' . $itemClass . ' a:hover .glory-cr__image ~ .glory-cr__title{opacity:1;visibility:visible;}';
			if ( '' !== $scopedSelector ) {
				$css .= $scopedSelector . ' .glory-cr__image:hover + .glory-cr__title,' . $scopedSelector . ' .glory-cr__image:hover ~ .glory-cr__title{opacity:1;visibility:visible;}';
				$css .= $scopedSelector . ' a:hover .glory-cr__image + .glory-cr__title,' . $scopedSelector . ' a:hover .glory-cr__image ~ .glory-cr__title{opacity:1;visibility:visible;}';
			}
		}

		$title_show = ! isset( $args['title_show'] ) || 'yes' === (string) $args['title_show'];
		// Color del título: soportar responsive si viene como array
		$title_color_raw = $args['title_color'] ?? '';
		if ( is_array( $title_color_raw ) ) {
			$title_color_l = $title_color_raw['large'] ?? reset( $title_color_raw );
			$title_color_m = $title_color_raw['medium'] ?? $title_color_l;
			$title_color_s = $title_color_raw['small'] ?? $title_color_m;
		} else {
			$title_color_l = $title_color_raw;
			$title_color_m = '';
			$title_color_s = '';
		}

		if ( '' !== (string) $title_color_l ) {
			$title_styles .= 'color:' . esc_attr( $title_color_l ) . ';';
		}

		$css .= $containerClass . ' .glory-cr__title,' . $itemClass . ' .glory-cr__title{display:' . ( $title_show ? 'block' : 'none' ) . ';' . $title_styles;
		if ( '' !== $title_min_width ) { $css .= 'min-width:' . esc_attr( $title_min_width ) . ';'; }
		if ( '' !== $title_width ) { $css .= 'width:' . esc_attr( $title_width ) . ';'; }
		if ( '' !== $title_max_width ) { $css .= 'max-width:' . esc_attr( $title_max_width ) . ';'; }
		$css .= '}';

		$css .= $containerClass . ' .glory-cr__content,' . $itemClass . ' .glory-cr__content{';
		if ( '' !== $content_min_width ) { $css .= 'min-width:' . esc_attr( $content_min_width ) . ';'; }
		if ( '' !== $content_width ) { $css .= 'width:' . esc_attr( $content_width ) . ';'; }
		if ( '' !== $content_max_width ) { $css .= 'max-width:' . esc_attr( $content_max_width ) . ';'; }
		$css .= $title_styles . '}';

		$toggleSeparatorEnabled = ! empty( $currentConfig['toggleSeparator'] );
		if ( $toggleSeparatorEnabled ) {
			$sepColor = isset( $currentConfig['toggleSeparatorColor'] ) ? (string) $currentConfig['toggleSeparatorColor'] : 'rgba(0,0,0,0.1)';
			$css .= $itemClass . ' .servicio-separador{display:block;width:100%;height:1px;background-color:' . esc_attr( $sepColor ) . ';}';
		}

		$medium_styles = '';
		if ( '' !== ( $responsive_values['font_size_medium'] ?? '' ) ) { $medium_styles .= 'font-size:' . esc_attr( $responsive_values['font_size_medium'] ) . ';'; }
		if ( '' !== ( $responsive_values['line_height_medium'] ?? '' ) ) { $medium_styles .= 'line-height:' . esc_attr( $responsive_values['line_height_medium'] ) . ';'; }
		if ( '' !== ( $responsive_values['letter_spacing_medium'] ?? '' ) ) { $medium_styles .= 'letter-spacing:' . esc_attr( $responsive_values['letter_spacing_medium'] ) . ';'; }
		if ( '' !== $medium_styles ) {
			$css .= '@media (min-width: 768px) and (max-width: 979px){' . $containerClass . ' .glory-cr__title,' . $itemClass . ' .glory-cr__title,' . $containerClass . ' .glory-cr__content,' . $itemClass . ' .glory-cr__content{' . $medium_styles . '}}';
		}

		// Color responsive: medium
		if ( '' !== (string) $title_color_m ) {
			$css .= '@media (min-width: 768px) and (max-width: 979px){' . $containerClass . ' .glory-cr__title,' . $itemClass . ' .glory-cr__title{color:' . esc_attr( $title_color_m ) . ';}}';
		}

		$small_styles = '';
		if ( '' !== ( $responsive_values['font_size_small'] ?? '' ) ) { $small_styles .= 'font-size:' . esc_attr( $responsive_values['font_size_small'] ) . ';'; }
		if ( '' !== ( $responsive_values['line_height_small'] ?? '' ) ) { $small_styles .= 'line-height:' . esc_attr( $responsive_values['line_height_small'] ) . ';'; }
		if ( '' !== ( $responsive_values['letter_spacing_small'] ?? '' ) ) { $small_styles .= 'letter-spacing:' . esc_attr( $responsive_values['letter_spacing_small'] ) . ';'; }
		if ( '' !== $small_styles ) {
			$css .= '@media (max-width: 767px){' . $containerClass . ' .glory-cr__title,' . $itemClass . ' .glory-cr__title,' . $containerClass . ' .glory-cr__content,' . $itemClass . ' .glory-cr__content{' . $small_styles . '}}';
		}

		// Color responsive: small
		if ( '' !== (string) $title_color_s ) {
			$css .= '@media (max-width: 767px){' . $containerClass . ' .glory-cr__title,' . $itemClass . ' .glory-cr__title{color:' . esc_attr( $title_color_s ) . ';}}';
		}

		$title_position = isset( $args['title_position'] ) ? (string) $args['title_position'] : 'top';
		$css .= $itemClass . ' .glory-cr__stack{display:flex;flex-direction:column;}';
		$stackTitleSel = $itemClass . ' .glory-cr__stack .glory-cr__title,'
			. $itemClass . ' .glory-cr__stack .entry-title,'
			. $itemClass . ' .glory-cr__stack .fusion-post-title,'
			. $itemClass . ' .glory-cr__stack .portafolio-info,'
			. $itemClass . ' .glory-cr__stack .post-info';
		$stackImageSel = $itemClass . ' .glory-cr__stack .glory-cr__image';
		if ( 'bottom' === $title_position ) {
			$css .= $stackImageSel . '{order:1;}';
			$css .= $stackTitleSel . '{order:2;}';
		} else {
			$css .= $stackTitleSel . '{order:1;}';
			$css .= $stackImageSel . '{order:2;}';
		}

		$css .= $containerClass . ' ' . $itemClass . '.servicio-item--toggle > .servicio-separador{display:block !important;height:1px !important;width:100% !important;margin:8px 0 !important;opacity:1 !important;pointer-events:none !important;}';

		// Patrón alternado de tamaños y orientación
		$layout_pattern_raw = $args['layout_pattern'] ?? 'none';
		$pattern_l = is_array( $layout_pattern_raw ) ? (string) ( $layout_pattern_raw['large'] ?? reset( $layout_pattern_raw ) ?? 'none' ) : (string) $layout_pattern_raw;
		$pattern_m = is_array( $layout_pattern_raw ) ? (string) ( $layout_pattern_raw['medium'] ?? $pattern_l ) : '';
		$pattern_s = is_array( $layout_pattern_raw ) ? (string) ( $layout_pattern_raw['small'] ?? $pattern_m ) : '';
		if ( 'alternado_lr' === $pattern_l ) {
			// Nuevo patrón: filas alternando imagen a la izquierda / derecha,
			// asumiendo imágenes de tamaño similar. Sólo afecta al stack interno,
			// no a los anchos de las tarjetas.
			$pattern_row_gap_raw = $args['pattern_row_gap'] ?? '40px';
			$pattern_row_gap = is_array( $pattern_row_gap_raw ) ? (string) ( $pattern_row_gap_raw['large'] ?? reset( $pattern_row_gap_raw ) ?? '40px' ) : (string) $pattern_row_gap_raw;
			if ( '' === trim( $pattern_row_gap ) ) { $pattern_row_gap = '40px'; }

			// Desktop: imagen/texto en fila, alternando orientación por item.
			$desktop_rules  = $itemClass . ' .glory-cr__stack{flex-direction:row;align-items:stretch;}';
			$desktop_rules .= $itemClass . ':nth-child(2n) .glory-cr__stack{flex-direction:row-reverse;}';
			if ( 'flex' === $display_mode ) {
				// Mantenemos el layout global definido por display_mode,
				// solo ajustamos el espacio vertical entre filas.
				$desktop_rules .= $containerClass . ' > *{margin-bottom:' . esc_attr( $pattern_row_gap ) . ';}';
			} elseif ( 'grid' === $display_mode ) {
				// En grid usamos row-gap para separar filas.
				$desktop_rules .= $containerClass . '{row-gap:' . esc_attr( $pattern_row_gap ) . ';}';
			}
			$css .= '@media (min-width: 980px){' . $desktop_rules . '}';

			// En tablet/mobile dejamos el stack en columna (ya es el valor por defecto),
			// por legibilidad; no añadimos reglas extra.
		} elseif ( 'alternado_slls' === $pattern_l ) {
			$small_w_l = (int) ( $args['pattern_small_width_percent'] ?? 40 );
			$large_w_l = (int) ( $args['pattern_large_width_percent'] ?? 60 );
			$small_w_m = (string) ( $args['pattern_small_width_percent_medium'] ?? '' );
			$large_w_m = (string) ( $args['pattern_large_width_percent_medium'] ?? '' );
			$small_w_s = (string) ( $args['pattern_small_width_percent_small'] ?? '' );
			$large_w_s = (string) ( $args['pattern_large_width_percent_small'] ?? '' );

			$small_w_l = max( 10, min( 90, $small_w_l ) );
			$large_w_l = max( 10, min( 90, $large_w_l ) );

			// Base (desktop)
			$desktop_rules = $itemClass . '{box-sizing:border-box;}';
			$desktop_rules .= $itemClass . ':nth-child(4n+1),' . $itemClass . ':nth-child(4n+4){width:' . $small_w_l . '%;}';
			$desktop_rules .= $itemClass . ':nth-child(4n+2),' . $itemClass . ':nth-child(4n+3){width:' . $large_w_l . '%;}';
			// Row gap (responsive) aplicado cuando hay patrón alternado
			$pattern_row_gap_raw = $args['pattern_row_gap'] ?? '40px';
			$pattern_row_gap = is_array( $pattern_row_gap_raw ) ? (string) ( $pattern_row_gap_raw['large'] ?? reset( $pattern_row_gap_raw ) ?? '40px' ) : (string) $pattern_row_gap_raw;
			if ( '' === trim( $pattern_row_gap ) ) { $pattern_row_gap = '40px'; }

			if ( 'flex' === $display_mode ) {
				$desktop_rules .= $containerClass . '{flex-wrap:wrap;justify-content:space-between;gap:0;}';
				// Aplicar row-gap en flex mediante margin-bottom de items
				$desktop_rules .= $containerClass . ' > *{margin-bottom:' . esc_attr( $pattern_row_gap ) . ';}';
				// Alinear por patrón: start para 1 y 4, end para 2 y 3
				$desktop_rules .= $itemClass . ':nth-child(2n+1){margin-left:0;}';
				$desktop_rules .= $itemClass . ':nth-child(2n){margin-left:auto;}';
			} elseif ( 'grid' === $display_mode ) {
				$desktop_rules .= $containerClass . '{grid-template-columns:repeat(2, 1fr);gap:0;row-gap:' . esc_attr( $pattern_row_gap ) . ';}';
				$desktop_rules .= $itemClass . ':nth-child(2n+1){justify-self:start;}';
				$desktop_rules .= $itemClass . ':nth-child(2n){justify-self:end;}';
			}
			$css .= '@media (min-width: 980px){' . $desktop_rules . '}';

			// Tablet override si se define patrón
			if ( '' !== $pattern_m && in_array( $pattern_m, [ 'none', 'alternado_slls' ], true ) ) {
				$rules = '';
				if ( 'alternado_slls' === $pattern_m ) {
					$swm = '' !== $small_w_m ? (int) $small_w_m : $small_w_l;
					$lwm = '' !== $large_w_m ? (int) $large_w_m : $large_w_l;
						$rules .= $itemClass . ':nth-child(4n+1),' . $itemClass . ':nth-child(4n+4){width:' . $swm . '%;}';
						$rules .= $itemClass . ':nth-child(4n+2),' . $itemClass . ':nth-child(4n+3){width:' . $lwm . '%;}';
					if ( 'flex' === $display_mode ) {
							$rules .= $containerClass . '{flex-wrap:wrap;justify-content:space-between;gap:0;}';
							$rules .= $itemClass . ':nth-child(2n+1){margin-left:0;}';
							$rules .= $itemClass . ':nth-child(2n){margin-left:auto;}';
					} elseif ( 'grid' === $display_mode ) {
							$rules .= $containerClass . '{grid-template-columns:repeat(2, 1fr);gap:0;}';
							$rules .= $itemClass . ':nth-child(2n+1){justify-self:start;}';
							$rules .= $itemClass . ':nth-child(2n){justify-self:end;}';
					}
				} else {
					// none => reset widths
					$rules .= $itemClass . '{width:100%;}';
				}
				if ( '' !== $rules ) {
					$css .= '@media (min-width: 768px) and (max-width: 979px){' . $rules . '}';
				}
			}

			// Mobile override si se define patrón
			if ( '' !== $pattern_s && in_array( $pattern_s, [ 'none', 'alternado_slls' ], true ) ) {
				$rules = '';
				if ( 'alternado_slls' === $pattern_s ) {
					$sws = '' !== $small_w_s ? (int) $small_w_s : $small_w_l;
					$lws = '' !== $large_w_s ? (int) $large_w_s : $large_w_l;
					$rules .= $itemClass . ':nth-child(4n+1),' . $itemClass . ':nth-child(4n+4){width:' . $sws . '%;}';
					$rules .= $itemClass . ':nth-child(4n+2),' . $itemClass . ':nth-child(4n+3){width:' . $lws . '%;}';
					if ( 'flex' === $display_mode ) {
						$rules .= $containerClass . '{flex-wrap:wrap;justify-content:space-between;gap:0;}';
						$rules .= $itemClass . ':nth-child(2n+1){margin-left:0;}';
						$rules .= $itemClass . ':nth-child(2n){margin-left:auto;}';
					} elseif ( 'grid' === $display_mode ) {
						// En mobile, 1 columna; alternar inicio/fin por item
						$rules .= $containerClass . '{grid-template-columns:repeat(1, 1fr);gap:0;}';
						$rules .= $itemClass . ':nth-child(2n+1){justify-self:start;}';
						$rules .= $itemClass . ':nth-child(2n){justify-self:end;}';
					}
				} else {
					$rules .= $itemClass . '{width:100%;}';
				}
				if ( '' !== $rules ) {
					$css .= '@media (max-width: 767px){' . $rules . '}';
				}
			}
		}

		if ( $internal_enabled && '' !== $internal_styles ) {
			$contentSelector  = $containerClass . ' .glory-cr__content,';
			$contentSelector .= $containerClass . ' .glory-cr__content p,';
			$contentSelector .= $containerClass . ' .glory-cr__content li,';
			$contentSelector .= $containerClass . ' .glory-cr__content span,';
			$contentSelector .= $containerClass . ' .glory-cr__content a,';
			$contentSelector .= $containerClass . ' .glory-cr__content strong,';
			$contentSelector .= $containerClass . ' .glory-cr__content em';
			$css .= $contentSelector . '{' . $internal_styles . '}';
		}

		if ( $internal_enabled ) {
			$medium_rules = '';
			if ( '' !== ( $args['internal_font_size_medium'] ?? '' ) ) { $medium_rules .= 'font-size:' . esc_attr( (string) $args['internal_font_size_medium'] ) . ';'; }
			if ( '' !== ( $args['internal_line_height_medium'] ?? '' ) ) { $medium_rules .= 'line-height:' . esc_attr( (string) $args['internal_line_height_medium'] ) . ';'; }
			if ( '' !== ( $args['internal_letter_spacing_medium'] ?? '' ) ) { $medium_rules .= 'letter-spacing:' . esc_attr( (string) $args['internal_letter_spacing_medium'] ) . ';'; }
			if ( '' !== $medium_rules ) {
				$css .= '@media (min-width: 768px) and (max-width: 979px){' . $containerClass . ' .glory-cr__content,' . $containerClass . ' .glory-cr__content p,' . $containerClass . ' .glory-cr__content li,' . $containerClass . ' .glory-cr__content span,' . $containerClass . ' .glory-cr__content a,' . $containerClass . ' .glory-cr__content strong,' . $containerClass . ' .glory-cr__content em{' . $medium_rules . '}}';
			}
			$small_rules = '';
			if ( '' !== ( $args['internal_font_size_small'] ?? '' ) ) { $small_rules .= 'font-size:' . esc_attr( (string) $args['internal_font_size_small'] ) . ';'; }
			if ( '' !== ( $args['internal_line_height_small'] ?? '' ) ) { $small_rules .= 'line-height:' . esc_attr( (string) $args['internal_line_height_small'] ) . ';'; }
			if ( '' !== ( $args['internal_letter_spacing_small'] ?? '' ) ) { $small_rules .= 'letter-spacing:' . esc_attr( (string) $args['internal_letter_spacing_small'] ) . ';'; }
			if ( '' !== $small_rules ) {
				$css .= '@media (max-width: 767px){' . $containerClass . ' .glory-cr__content,' . $containerClass . ' .glory-cr__content p,' . $containerClass . ' .glory-cr__content li,' . $containerClass . ' .glory-cr__content span,' . $containerClass . ' .glory-cr__content a,' . $containerClass . ' .glory-cr__content strong,' . $containerClass . ' .glory-cr__content em{' . $small_rules . '}}';
			}
		}

		$sc_width_r     = $normalize( $args['servicio_contenido_width'] ?? '', 'servicio_contenido_width' );
		$sc_max_width_r = $normalize( $args['servicio_contenido_max_width'] ?? '', 'servicio_contenido_max_width' );
		$sc_selector    = $containerClass . ' .glory-cr__content,' . $itemClass . ' .glory-cr__content';
		$sc_base_rules  = '';
		if ( ! empty( $sc_width_r['large'] ) ) { $sc_base_rules .= 'width:' . esc_attr( $sc_width_r['large'] ) . ' !important;'; }
		if ( ! empty( $sc_max_width_r['large'] ) ) { $sc_base_rules .= 'max-width:' . esc_attr( $sc_max_width_r['large'] ) . ' !important;'; }
		if ( '' !== $sc_base_rules ) { $css .= $sc_selector . '{' . $sc_base_rules . '}'; }
		$sc_tab_rules = '';
		if ( ! empty( $sc_width_r['medium'] ) ) { $sc_tab_rules .= 'width:' . esc_attr( $sc_width_r['medium'] ) . ' !important;'; }
		if ( ! empty( $sc_max_width_r['medium'] ) ) { $sc_tab_rules .= 'max-width:' . esc_attr( $sc_max_width_r['medium'] ) . ' !important;'; }
		if ( '' !== $sc_tab_rules ) { $css .= '@media (min-width: 768px) and (max-width: 979px){' . $sc_selector . '{' . $sc_tab_rules . '}}'; }
		$sc_mob_rules = '';
		if ( ! empty( $sc_width_r['small'] ) ) { $sc_mob_rules .= 'width:' . esc_attr( $sc_width_r['small'] ) . ' !important;'; }
		if ( ! empty( $sc_max_width_r['small'] ) ) { $sc_mob_rules .= 'max-width:' . esc_attr( $sc_max_width_r['small'] ) . ' !important;'; }
		if ( '' !== $sc_mob_rules ) { $css .= '@media (max-width: 767px){' . $sc_selector . '{' . $sc_mob_rules . '}}'; }

		// CSS para arrastre horizontal
		if ( $enableHorizontalDrag ) {
			$css .= $containerClass . '{width:fit-content;overflow-x:auto;overflow-y:hidden;scrollbar-width:none;-ms-overflow-style:none;cursor:grab;}';
			$css .= $containerClass . '::-webkit-scrollbar{display:none;}';
			$css .= $containerClass . ':active{cursor:grabbing;}';
			// Evitar que los elementos internos interfieran con el arrastre
			$css .= $itemClass . '{user-select:none;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;flex-shrink:0;}';
			$css .= $itemClass . ' a{user-select:none;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;}';
		}

		return $css;
	}

	private static function resolveLayoutOptions( array $args, string $modo_interaccion ): array
	{
		$display_mode = isset( $args['display_mode'] ) ? (string) $args['display_mode'] : 'flex';
		$flex_direction = isset( $args['flex_direction'] ) ? (string) $args['flex_direction'] : 'row';
		$flex_wrap = isset( $args['flex_wrap'] ) ? (string) $args['flex_wrap'] : 'wrap';
		$gap = isset( $args['gap'] ) ? (string) $args['gap'] : '20px';
		$align_items = isset( $args['align_items'] ) ? (string) $args['align_items'] : 'stretch';
		$justify_content = isset( $args['justify_content'] ) ? (string) $args['justify_content'] : 'flex-start';
		$grid_min_width = isset( $args['grid_min_width'] ) ? (string) $args['grid_min_width'] : '250px';
		$grid_auto_fit = ( isset( $args['grid_auto_fit'] ) && 'yes' === $args['grid_auto_fit'] ) ? 'auto-fit' : 'auto-fill';
		$mode = isset( $args['grid_columns_mode'] ) ? (string) $args['grid_columns_mode'] : 'fixed';

		// Soportar valores responsivos (arrays) que devuelve Fusion para controls "range responsive"
		$grid_columns_arg = $args['grid_columns'] ?? 4;
		if ( is_array( $grid_columns_arg ) ) {
			$large_cols = (int) ( $grid_columns_arg['large'] ?? reset( $grid_columns_arg ) ?? 4 );
			$medium_cols = (int) ( $grid_columns_arg['medium'] ?? $large_cols );
			$small_cols = (int) ( $grid_columns_arg['small'] ?? $medium_cols );
		} else {
			$large_cols = (int) $grid_columns_arg;
		$medium_cols = isset( $args['grid_columns_medium'] ) && '' !== (string) $args['grid_columns_medium'] ? (int) $args['grid_columns_medium'] : $large_cols;
		$small_cols  = isset( $args['grid_columns_small'] ) && '' !== (string) $args['grid_columns_small'] ? (int) $args['grid_columns_small'] : $medium_cols;
		}

		$grid_min_columns_arg = $args['grid_min_columns'] ?? 1;
		if ( is_array( $grid_min_columns_arg ) ) {
			$min_large = (int) ( $grid_min_columns_arg['large'] ?? reset( $grid_min_columns_arg ) ?? 1 );
			$min_medium = (int) ( $grid_min_columns_arg['medium'] ?? $min_large );
			$min_small = (int) ( $grid_min_columns_arg['small'] ?? $min_medium );
		} else {
			$min_large = (int) $grid_min_columns_arg;
		$min_medium = isset( $args['grid_min_columns_medium'] ) && '' !== (string) $args['grid_min_columns_medium'] ? (int) $args['grid_min_columns_medium'] : $min_large;
			$min_small  = isset( $args['grid_min_columns_small'] ) && '' !== (string) $args['grid_min_columns_small'] ? (int) $args['grid_min_columns_small'] : $min_medium;
		}

		$grid_max_columns_arg = $args['grid_max_columns'] ?? 12;
		if ( is_array( $grid_max_columns_arg ) ) {
			$max_large = (int) ( $grid_max_columns_arg['large'] ?? reset( $grid_max_columns_arg ) ?? 12 );
			$max_medium = (int) ( $grid_max_columns_arg['medium'] ?? $max_large );
			$max_small = (int) ( $grid_max_columns_arg['small'] ?? $max_medium );
		} else {
			$max_large = (int) $grid_max_columns_arg;
		$max_medium = isset( $args['grid_max_columns_medium'] ) && '' !== (string) $args['grid_max_columns_medium'] ? (int) $args['grid_max_columns_medium'] : $max_large;
			$max_small  = isset( $args['grid_max_columns_small'] ) && '' !== (string) $args['grid_max_columns_small'] ? (int) $args['grid_max_columns_small'] : $max_medium;
		}

		if ( 'carousel' === $modo_interaccion ) {
			$display_mode = 'flex';
			$flex_direction = 'row';
			$flex_wrap = 'nowrap';
		}

		return [
			'modo_interaccion'         => $modo_interaccion,
			'display_mode'             => $display_mode,
			'flex_direction'           => $flex_direction,
			'flex_wrap'                => $flex_wrap,
			'gap'                      => $gap,
			'align_items'              => $align_items,
			'justify_content'          => $justify_content,
			'grid_min_width'           => $grid_min_width,
			'grid_auto_fit'            => $grid_auto_fit,
			'grid_columns_mode'        => $mode,
			'grid_columns'             => $large_cols,
			'grid_columns_medium'      => $medium_cols,
			'grid_columns_small'       => $small_cols,
			'grid_min_columns'         => $min_large,
			'grid_max_columns'         => $max_large,
			'grid_min_columns_medium'  => $min_medium,
			'grid_max_columns_medium'  => $max_medium,
			'grid_min_columns_small'   => $min_small,
			'grid_max_columns_small'   => $max_small,
		];
	}
}



