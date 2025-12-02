<?php
namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;

trait HasSpacing {
    /**
     * Retorna las opciones estándar de espaciado.
     * @return Option[]
     */
    protected function getSpacingOptions(): array {
        return [
            Option::spacing('padding', 'Padding')
                ->units(['px', '%', 'rem'])
                ->step(4)
                ->min(0)
                ->max(240)
                ->fields(['superior', 'derecha', 'inferior', 'izquierda']),
            
            Option::spacing('margin', 'Margen')
                ->units(['px', '%', 'rem'])
                ->step(4)
                ->min(-100)
                ->max(240)
                ->fields(['superior', 'derecha', 'inferior', 'izquierda']),
        ];
    }
}
