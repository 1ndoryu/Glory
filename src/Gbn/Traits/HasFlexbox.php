<?php
namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;
use Glory\Gbn\Icons\IconRegistry;
use Glory\Gbn\Schema\SchemaConstants;

trait HasFlexbox {
    /**
     * Retorna las opciones estándar de Flexbox.
     * @return Option[]
     */
    protected function getFlexboxOptions(): array {
        return [
            Option::iconGroup(SchemaConstants::FIELD_LAYOUT, 'Layout')
                ->options([
                    ['valor' => 'block', 'etiqueta' => 'Bloque', 'icon' => IconRegistry::get('layout.block')],
                    ['valor' => 'flex', 'etiqueta' => 'Flexbox', 'icon' => IconRegistry::get('layout.flex')],
                    ['valor' => 'grid', 'etiqueta' => 'Grid', 'icon' => IconRegistry::get('layout.grid')],
                ])
                ->default('block'),
            
            Option::iconGroup(SchemaConstants::FIELD_FLEX_DIRECTION, 'Dirección')
                ->options([
                    ['valor' => 'row', 'etiqueta' => 'Horizontal', 'icon' => IconRegistry::get('direction.row')],
                    ['valor' => 'column', 'etiqueta' => 'Vertical', 'icon' => IconRegistry::get('direction.column')],
                ])
                ->condition(SchemaConstants::FIELD_LAYOUT, '==', 'flex'),

            Option::iconGroup(SchemaConstants::FIELD_FLEX_WRAP, 'Envoltura')
                ->options([
                    ['valor' => 'nowrap', 'etiqueta' => 'No envolver', 'icon' => IconRegistry::get('wrap.nowrap')],
                    ['valor' => 'wrap', 'etiqueta' => 'Envolver', 'icon' => IconRegistry::get('wrap.wrap')],
                ])
                ->condition(SchemaConstants::FIELD_LAYOUT, '==', 'flex'),

            Option::iconGroup(SchemaConstants::FIELD_JUSTIFY, 'Justificación')
                ->options([
                    ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => IconRegistry::get('justify.start')],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => IconRegistry::get('justify.center')],
                    ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => IconRegistry::get('justify.end')],
                    ['valor' => 'space-between', 'etiqueta' => 'Espacio entre', 'icon' => IconRegistry::get('justify.between')],
                    ['valor' => 'space-around', 'etiqueta' => 'Espacio alrededor', 'icon' => IconRegistry::get('justify.around')],
                ])
                ->condition(SchemaConstants::FIELD_LAYOUT, '==', 'flex'),

            Option::iconGroup(SchemaConstants::FIELD_ALIGN, 'Alineación')
                ->options([
                    ['valor' => 'stretch', 'etiqueta' => 'Estirar', 'icon' => IconRegistry::get('align.stretch')],
                    ['valor' => 'flex-start', 'etiqueta' => 'Inicio', 'icon' => IconRegistry::get('align.start')],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => IconRegistry::get('align.center')],
                    ['valor' => 'flex-end', 'etiqueta' => 'Fin', 'icon' => IconRegistry::get('align.end')],
                ])
                ->condition(SchemaConstants::FIELD_LAYOUT, '==', 'flex'),

            Option::gap(SchemaConstants::FIELD_GAP, 'Separación (Gap)')
                ->condition(SchemaConstants::FIELD_LAYOUT, '==', 'flex'),
        ];
    }
}
