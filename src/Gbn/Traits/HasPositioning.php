<?php

namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;
use Glory\Gbn\Icons\IconRegistry;

trait HasPositioning
{
    /**
     * Obtiene las opciones de posicionamiento (Position & Z-Index)
     * 
     * @return Option[]
     */
    protected function getPositioningOptions(): array
    {
        $options = [];

        // 1. Position - Tab: Avanzado
        $options[] = Option::iconGroup('position', 'Posición')
            ->options(IconRegistry::getGroup([
                'pos.static' => ['valor' => 'static', 'etiqueta' => 'Static'],
                'pos.relative' => ['valor' => 'relative', 'etiqueta' => 'Relative'],
                'pos.absolute' => ['valor' => 'absolute', 'etiqueta' => 'Absolute'],
                'pos.fixed' => ['valor' => 'fixed', 'etiqueta' => 'Fixed'],
                'pos.sticky' => ['valor' => 'sticky', 'etiqueta' => 'Sticky'],
            ]))
            ->default('static')
            ->tab('Avanzado');

        // 2. Z-Index - Tab: Avanzado
        $options[] = Option::text('zIndex', 'Z-Index')
            ->default('')
            ->tab('Avanzado')
            ->description('Orden de apilamiento (ej: 10, 100, -1)');

        // 3. Overflow - Tab: Avanzado
        $options[] = Option::iconGroup('overflow', 'Desbordamiento (Overflow)')
            ->options(IconRegistry::getGroup([
                'overflow.visible' => ['valor' => 'visible', 'etiqueta' => 'Visible'],
                'overflow.hidden' => ['valor' => 'hidden', 'etiqueta' => 'Oculto'],
                'overflow.scroll' => ['valor' => 'scroll', 'etiqueta' => 'Scroll'],
                'overflow.auto' => ['valor' => 'auto', 'etiqueta' => 'Auto'],
            ]))
            ->default('visible')
            ->tab('Avanzado');

        return $options;
    }
}
