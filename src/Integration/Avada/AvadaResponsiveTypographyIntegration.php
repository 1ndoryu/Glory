<?php

namespace Glory\Integration\Avada;

class AvadaResponsiveTypographyIntegration
{
    public static function register(): void
    {
        if ( ! function_exists('add_filter') ) {
            return;
        }

        // Si el usuario agrega "noResponsiveFont" en la clase del wrapper del título,
        // marcamos un flag para que el siguiente heading lo desactive.
        add_filter('fusion_attr_title-shortcode', [self::class, 'filterTitleWrapper']);

        // En el heading del título, si corresponde, aplicar la desactivación de tipografía responsiva.
        add_filter('fusion_attr_title-shortcode-heading', [self::class, 'filterTitleHeading']);
    }

    public static function filterTitleWrapper($attr)
    {
        try {
            if (!isset($attr['class'])) {
                return $attr;
            }
            if (strpos((string) $attr['class'], 'noResponsiveFont') !== false) {
                $GLOBALS['glory_disable_rt_next_heading'] = true;
            }
        } catch (\Throwable $t) {
        }
        return $attr;
    }

    public static function filterTitleHeading($attr)
    {
        try {
            if (!isset($attr['class'])) {
                $attr['class'] = '';
            }

            // Evitar duplicados.
            if (strpos((string) $attr['class'], 'awb-responsive-type__disable') !== false) {
                return $attr;
            }

            // Si el heading tiene explícitamente la clase "noResponsiveFont".
            if (strpos((string) $attr['class'], 'noResponsiveFont') !== false) {
                $attr['class'] .= ' awb-responsive-type__disable';
                return $attr;
            }

            // Aplicar por flag global, seteado por el wrapper.
            if (isset($GLOBALS['glory_disable_rt_next_heading']) && true === $GLOBALS['glory_disable_rt_next_heading']) {
                $attr['class'] .= ' awb-responsive-type__disable';
                $GLOBALS['glory_disable_rt_next_heading'] = false; // consumir
                return $attr;
            }
        } catch (\Throwable $t) {
        }

        return $attr;
    }
}


