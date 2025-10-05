<?php

namespace Glory\Support\ContentRender;

class Args
{
	public static function sanitizeModo( string $modo ): string
	{
		$modo = strtolower( trim( $modo ) );
		if ( ! in_array( $modo, [ 'normal', 'carousel', 'toggle' ], true ) ) {
			return 'normal';
		}
		return $modo;
	}

	/**
	 * @return array<int>
	 */
	public static function parseToggleAutoOpen( string $value ): array
	{
		if ( '' === trim( $value ) ) {
			return [];
		}
		$parts = array_map( 'trim', explode( ',', $value ) );
		$ids = [];
		foreach ( $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}
			if ( is_numeric( $part ) ) {
				$ids[] = (int) $part;
			} else {
				$ids[] = absint( $part );
			}
		}
		return array_values( array_filter( array_unique( $ids ), static function ( $n ) {
			return $n > 0;
		} ) );
	}

	/**
	 * Calcula opciones internas de layout si la plantilla lo soporta.
	 */
	public static function collectInternalLayout( array $args, array $supports, string $modo_interaccion ): array
	{
		$layout = [
			'display_mode'             => '',
			'flex_direction'           => '',
			'flex_wrap'                => '',
			'gap'                      => '',
			'align_items'              => '',
			'justify_content'          => '',
			'grid_min_width'           => '',
			'grid_auto_fit'            => '',
			'grid_columns_mode'        => '',
			'grid_columns'             => [],
			'grid_min_columns'         => [],
			'grid_max_columns'         => [],
		];

		if ( empty( $supports['internalLayout'] ) ) {
			return $layout;
		}

		$parse_single = static function ( $value ): ?int {
			if ( is_array( $value ) ) {
				return null;
			}
			$value = trim( (string) $value );
			if ( '' === $value ) {
				return null;
			}
			$int = (int) $value;
			if ( $int <= 0 ) {
				return null;
			}
			return max( 1, min( 12, $int ) );
		};

		$parse_responsive = static function ( $base, $fallback_medium = null, $fallback_small = null ) use ( $parse_single ): array {
			$result = [];

			$assign = static function ( string $breakpoint, $value ) use ( &$result, $parse_single ): void {
				$parsed = $parse_single( $value );
				if ( null !== $parsed ) {
					$result[ $breakpoint ] = $parsed;
				}
			};

			if ( is_array( $base ) ) {
				foreach ( [ 'large', 'medium', 'small' ] as $bp ) {
					if ( array_key_exists( $bp, $base ) ) {
						$assign( $bp, $base[ $bp ] );
					}
				}
			} else {
				$assign( 'large', $base );
			}

			$assign( 'medium', $fallback_medium );
			$assign( 'small', $fallback_small );

			if ( ! isset( $result['large'] ) && isset( $result['medium'] ) ) {
				$result['large'] = $result['medium'];
			}
			if ( ! isset( $result['medium'] ) && isset( $result['large'] ) ) {
				$result['medium'] = $result['large'];
			}
			if ( ! isset( $result['small'] ) && isset( $result['medium'] ) ) {
				$result['small'] = $result['medium'];
			}

			return $result;
		};

		$layout['display_mode']    = isset( $args['internal_display_mode'] ) ? (string) $args['internal_display_mode'] : '';
		$layout['flex_direction']  = isset( $args['internal_flex_direction'] ) ? (string) $args['internal_flex_direction'] : '';
		$layout['flex_wrap']       = isset( $args['internal_flex_wrap'] ) ? (string) $args['internal_flex_wrap'] : '';
		$layout['gap']             = isset( $args['internal_gap'] ) ? (string) $args['internal_gap'] : '';
		$layout['align_items']     = isset( $args['internal_align_items'] ) ? (string) $args['internal_align_items'] : '';
		$layout['justify_content'] = isset( $args['internal_justify_content'] ) ? (string) $args['internal_justify_content'] : '';
		$layout['grid_min_width']  = isset( $args['internal_grid_min_width'] ) ? trim( (string) $args['internal_grid_min_width'] ) : '';

		if ( isset( $args['internal_grid_auto_fit'] ) ) {
			$auto_fit = (string) $args['internal_grid_auto_fit'];
			if ( in_array( $auto_fit, [ 'yes', 'auto-fit' ], true ) ) {
				$layout['grid_auto_fit'] = 'auto-fit';
			} elseif ( in_array( $auto_fit, [ 'no', 'auto-fill' ], true ) ) {
				$layout['grid_auto_fit'] = 'auto-fill';
			}
		}

		if ( isset( $args['internal_grid_columns_mode'] ) ) {
			$mode = (string) $args['internal_grid_columns_mode'];
			if ( in_array( $mode, [ 'fixed', 'auto' ], true ) ) {
				$layout['grid_columns_mode'] = $mode;
			}
		}

		$list_columns = $parse_responsive(
			$args['internal_grid_columns'] ?? [],
			$args['internal_grid_columns_medium'] ?? null,
			$args['internal_grid_columns_small'] ?? null
		);
		$layout['grid_columns'] = $list_columns;

		$list_min_columns = $parse_responsive(
			$args['internal_grid_min_columns'] ?? [],
			$args['internal_grid_min_columns_medium'] ?? null,
			$args['internal_grid_min_columns_small'] ?? null
		);
		$layout['grid_min_columns'] = $list_min_columns;

		$list_max_columns = $parse_responsive(
			$args['internal_grid_max_columns'] ?? [],
			$args['internal_grid_max_columns_medium'] ?? null,
			$args['internal_grid_max_columns_small'] ?? null
		);
		$layout['grid_max_columns'] = $list_max_columns;

		if ( 'toggle' === $modo_interaccion ) {
			if ( '' === $layout['display_mode'] ) {
				$layout['display_mode'] = 'flex';
			}
			if ( '' === $layout['flex_direction'] ) {
				$layout['flex_direction'] = 'column';
			}
			if ( '' === $layout['flex_wrap'] ) {
				$layout['flex_wrap'] = 'nowrap';
			}
			if ( '' === $layout['gap'] ) {
				$layout['gap'] = '8px';
			}
		}

		return $layout;
	}
}


