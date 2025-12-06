<?php
namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;
use Glory\Gbn\Icons\IconRegistry;

trait HasFlexbox {
    /**
     * Retorna las opciones estándar de Flexbox.
     * @return Option[]
     */
    protected function getFlexboxOptions(): array {
        return [
            Option::iconGroup('layout', 'Layout')
                ->options([
                    ['valor' => 'block', 'etiqueta' => 'Bloque', 'icon' => IconRegistry::get('layout.block')],
                    ['valor' => 'flex', 'etiqueta' => 'Flexbox', 'icon' => IconRegistry::get('layout.flex')],
                    ['valor' => 'grid', 'etiqueta' => 'Grid', 'icon' => IconRegistry::get('layout.grid')],
                ])
                ->default('block'),
            
            Option::iconGroup('flexDirection', 'Dirección')
                ->options([
                    ['valor' => 'row', 'etiqueta' => 'Horizontal', 'icon' => IconRegistry::get('direction.row')],
                    ['valor' => 'column', 'etiqueta' => 'Vertical', 'icon' => IconRegistry::get('direction.column')],
                ])
                ->condition('layout', 'flex'),

            Option::iconGroup('flexWrap', 'Envoltura')
                ->options([
                    ['valor' => 'nowrap', 'etiqueta' => 'No envolver', 'icon' => IconRegistry::get('wrap.nowrap')],
                    ['valor' => 'wrap', 'etiqueta' => 'Envolver', 'icon' => IconRegistry::get('wrap.wrap')],
                ])
                ->condition('layout', 'flex'),

            Option::iconGroup('flexJustify', 'Justificación')
                ->options([
                    ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => IconRegistry::get('justify.start')],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => IconRegistry::get('justify.center')],
                    ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => IconRegistry::get('justify.end')],
                    ['valor' => 'space-between', 'etiqueta' => 'Espacio entre', 'icon' => IconRegistry::get('justify.between')],
                    ['valor' => 'space-around', 'etiqueta' => 'Espacio alrededor', 'icon' => IconRegistry::get('justify.around')],
                ])
                ->condition('layout', 'flex'),

            Option::iconGroup('flexAlign', 'Alineación')
                ->options([
                    ['valor' => 'stretch', 'etiqueta' => 'Estirar', 'icon' => IconRegistry::get('align.stretch')],
                    ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => IconRegistry::get('align.start')],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => IconRegistry::get('align.center')],
                    ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => IconRegistry::get('align.end')],
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
