<?php
namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;

trait HasGrid {
    /**
     * Retorna las opciones estándar de Grid.
     * @return Option[]
     */
    protected function getGridOptions(): array {
        return [
            Option::slider('gridColumns', 'Columnas Grid')
                ->min(1)
                ->max(12)
                ->step(1)
                ->condition('layout', 'grid'),
            
            Option::slider('gridGap', 'Separación Grid')
                ->unit('px')
                ->min(0)
                ->max(120)
                ->step(2)
                ->condition('layout', 'grid'),
        ];
    }
}
