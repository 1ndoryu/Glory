<?php

namespace Glory\Support\Scripts;

class ContentRenderScripts
{
	public static function buildAll( string $selector, array $config ): string
	{
		$scripts = '';
		$modo = (string) ( $config['modoInteraccion'] ?? 'normal' );

		// Encolado por modo
		if ( 'carousel' === $modo ) {
			Carousel::enqueue();
		} elseif ( 'toggle' === $modo ) {
			Toggle::enqueue();
		}

		// Arrastre horizontal si está habilitado
		$enableHorizontalDragRaw = $config['enableHorizontalDrag'] ?? null;
		$enableHorizontalDrag = (! empty( $enableHorizontalDragRaw ) && ($enableHorizontalDragRaw === 'yes' || $enableHorizontalDragRaw === true || $enableHorizontalDragRaw === 1));
		if ( $enableHorizontalDrag && 'normal' === $modo ) {
			HorizontalDrag::enqueue();
		}

		// Desactivar enlaces si corresponde
		$linkEnabled = ! empty( $config['linkEnabled'] );
		if ( ! $linkEnabled ) {
			$scripts .= self::buildDisableLinksScript( $selector );
		}

		// Carrusel: init o stop
		if ( 'carousel' === $modo ) {
			$speed = (float) ( $config['carouselSpeed'] ?? 20.0 );
			$scripts .= Carousel::buildInitOrQueue( $selector, $speed );
		} else {
			$scripts .= Carousel::buildStop( $selector );
		}

		// Toggle: init si corresponde
		if ( 'toggle' === $modo ) {
			$options = [
				'separator'      => ! empty( $config['toggleSeparator'] ),
				'separatorColor' => (string) ( $config['toggleSeparatorColor'] ?? 'rgba(0,0,0,0.1)' ),
				'autoOpen'       => (array)  ( $config['toggleAutoOpen'] ?? [] ),
				'defaultState'   => (string) ( $config['toggleDefaultState'] ?? 'collapsed' ),
				'instanceClass'  => (string) ( $config['instanceClass'] ?? '' ),
			];
			$scripts .= Toggle::buildInitScript( $selector, $options );
		}

		// Arrastre horizontal: init si está habilitado
		if ( $enableHorizontalDrag && 'normal' === $modo ) {
			$scripts .= HorizontalDrag::buildInitScript( $selector );
		}

		return $scripts;
	}

	public static function buildDisableLinksScript( string $selector ): string
	{
		$sel = function_exists('wp_json_encode') ? wp_json_encode($selector) : json_encode($selector);
		return '<script>(function(){var s=' . $sel . ';function prevent(e){var a=e.target.closest("a");if(a&&a.closest(s)){e.preventDefault();e.stopPropagation();}}document.addEventListener("click",prevent,true);document.addEventListener("keydown",function(e){if((e.key||e.keyCode)==="Enter"||e.keyCode===13){prevent(e);}},true);})();</script>';
	}
}



