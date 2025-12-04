<?php

namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;

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
            ->options([
                ['valor' => 'static', 'etiqueta' => 'Static', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>'],
                ['valor' => 'relative', 'etiqueta' => 'Relative', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2" stroke-dasharray="4 4"/><rect x="8" y="8" width="8" height="8"/></svg>'],
                ['valor' => 'absolute', 'etiqueta' => 'Absolute', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" stroke-opacity="0.2"/><rect x="12" y="4" width="8" height="8"/></svg>'],
                ['valor' => 'fixed', 'etiqueta' => 'Fixed', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20"/><circle cx="12" cy="12" r="3"/></svg>'],
                ['valor' => 'sticky', 'etiqueta' => 'Sticky', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 8h16"/></svg>'],
            ])
            ->default('static')
            ->tab('Avanzado');

        // 2. Z-Index - Tab: Avanzado
        $options[] = Option::text('zIndex', 'Z-Index')
            ->default('')
            ->tab('Avanzado')
            ->description('Orden de apilamiento (ej: 10, 100, -1)');

        return $options;
    }
}
