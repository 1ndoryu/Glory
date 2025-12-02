<?php
namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;

trait HasFlexbox {
    /**
     * Retorna las opciones estándar de Flexbox.
     * @return Option[]
     */
    protected function getFlexboxOptions(): array {
        return [
            Option::iconGroup('layout', 'Layout')
                ->options([
                    ['valor' => 'block', 'etiqueta' => 'Bloque', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>'],
                    ['valor' => 'flex', 'etiqueta' => 'Flexbox', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 3v18"/></svg>'],
                    ['valor' => 'grid', 'etiqueta' => 'Grid', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>'],
                ])
                ->default('block'),
            
            Option::iconGroup('flexDirection', 'Dirección')
                ->options([
                    ['valor' => 'row', 'etiqueta' => 'Horizontal', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><path d="M16 8l4 4-4 4"/></svg>'],
                    ['valor' => 'column', 'etiqueta' => 'Vertical', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16"/><path d="M8 16l4 4 4-4"/></svg>'],
                ])
                ->condition('layout', 'flex'),

            Option::iconGroup('flexWrap', 'Envoltura')
                ->options([
                    ['valor' => 'nowrap', 'etiqueta' => 'No envolver', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/></svg>'],
                    ['valor' => 'wrap', 'etiqueta' => 'Envolver', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8h10a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4"/><path d="M8 12l-4 4 4 4"/></svg>'],
                ])
                ->condition('layout', 'flex'),

            Option::iconGroup('flexJustify', 'Justificación')
                ->options([
                    ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="6" height="18" rx="1"/></svg>'],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="3" width="6" height="18" rx="1"/></svg>'],
                    ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="15" y="3" width="6" height="18" rx="1"/></svg>'],
                    ['valor' => 'space-between', 'etiqueta' => 'Espacio entre', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="4" height="18" rx="1"/><rect x="17" y="3" width="4" height="18" rx="1"/></svg>'],
                    ['valor' => 'space-around', 'etiqueta' => 'Espacio alrededor', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="3" width="4" height="18" rx="1"/><rect x="15" y="3" width="4" height="18" rx="1"/></svg>'],
                ])
                ->condition('layout', 'flex'),

            Option::iconGroup('flexAlign', 'Alineación')
                ->options([
                    ['valor' => 'stretch', 'etiqueta' => 'Estirar', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3v18"/><path d="M20 3v18"/><rect x="8" y="6" width="8" height="12" rx="1"/></svg>'],
                    ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3h16"/><rect x="8" y="7" width="8" height="8" rx="1"/></svg>'],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>'],
                    ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21h16"/><rect x="8" y="9" width="8" height="8" rx="1"/></svg>'],
                ])
                ->condition('layout', 'flex'),

            Option::slider('gap', 'Separación (Gap)')
                ->unit('px')
                ->min(0)
                ->max(120)
                ->step(2)
                ->condition('layout', 'flex'),
        ];
    }
}
