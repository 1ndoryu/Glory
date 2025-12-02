<?php
namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;

trait HasTypography {
    /**
     * Retorna las opciones estándar de tipografía.
     * @return Option[]
     */
    protected function getTypographyOptions(): array {
        return [
            Option::text('fontSize', 'Tamaño de fuente')
                ->default('16px'),
            
            Option::color('color', 'Color de texto'),
            
            Option::select('textAlign', 'Alineación')
                ->options([
                    'left' => 'Izquierda',
                    'center' => 'Centro',
                    'right' => 'Derecha',
                    'justify' => 'Justificado'
                ]),
        ];
    }
}
