<?php

namespace Glory\Support\CSS;

class Typography
{
    public static function variantToCss( string $variant ): string
    {
        $variant = trim( strtolower( (string) $variant ) );
        $style   = '';

        if ( '' === $variant ) {
            return $style;
        }

        if ( false !== strpos( $variant, 'italic' ) ) {
            $style .= 'font-style:italic;';
        } elseif ( false !== strpos( $variant, 'normal' ) ) {
            $style .= 'font-style:normal;';
        }

        $weight = preg_replace( '/[^0-9]/', '', $variant );
        if ( '' !== $weight ) {
            $style .= 'font-weight:' . $weight . ';';
        }

        return $style;
    }
}
