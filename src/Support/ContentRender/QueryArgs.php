<?php

namespace Glory\Support\ContentRender;

class QueryArgs
{
	public static function mergePostIds( array $baseArgs, array $argumentosConsulta ): array
	{
		$ids = [];
		if ( ! empty( $baseArgs['post_ids'] ) ) {
			$csv = (string) $baseArgs['post_ids'];
			$ids = array_filter( array_map( 'absint', array_map( 'trim', explode( ',', $csv ) ) ) );
		}
		if ( ! empty( $baseArgs['post_ids_select'] ) ) {
			$sel = $baseArgs['post_ids_select'];
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
			$argumentosConsulta['orderby']  = 'post__in';
		}
		return $argumentosConsulta;
	}
}


