<?php

namespace Glory\Support\CSS;

class Responsive
{
    /**
     * Normaliza un valor responsive que puede venir como escalar o array {large,medium,small}.
     * Usa fallbacks *_medium y *_small desde $source cuando $value es escalar.
     *
     * @param mixed $value
     * @param string $baseName
     * @param array $source
     * @return array{large:string,medium:string,small:string}
     */
    public static function normalize( $value, string $baseName, array $source ): array
    {
        if ( is_array( $value ) ) {
            return [
                'large'  => (string) ( $value['large'] ?? '' ),
                'medium' => (string) ( $value['medium'] ?? '' ),
                'small'  => (string) ( $value['small'] ?? '' ),
            ];
        }
        $medium = (string) ( $source[$baseName . '_medium'] ?? '' );
        $small  = (string) ( $source[$baseName . '_small'] ?? '' );
        return [ 'large' => (string) $value, 'medium' => $medium, 'small' => $small ];
    }
}
