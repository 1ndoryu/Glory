<?php
namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;
use Glory\Gbn\Icons\IconRegistry;
use Glory\Gbn\Schema\SchemaConstants;

/**
 * Trait HasLayoutOptions - Unifica todas las opciones de layout
 * 
 * Reemplaza y combina:
 * - HasFlexbox (parcialmente)
 * - HasGrid (parcialmente)  
 * - Opciones duplicadas en PostRenderComponent
 * 
 * Configuraciones disponibles:
 * - 'basic': solo displayMode (block/flex/grid)
 * - 'flex': displayMode + todas las opciones flex
 * - 'grid': displayMode + todas las opciones grid
 * - 'full': displayMode + flex + grid
 */
trait HasLayoutOptions
{
    /**
     * Obtiene las opciones de layout según el nivel de detalle requerido.
     * 
     * @param string $level Nivel: 'basic', 'flex', 'grid', 'full'
     * @param string $fieldPrefix Prefijo para los campos (ej: 'display' vs 'layout')
     * @return Option[]
     */
    protected function getLayoutOptions(string $level = 'full', string $fieldPrefix = 'layout'): array
    {
        $options = [];

        // Opción principal de modo de visualización
        $options[] = Option::iconGroup($fieldPrefix, 'Modo de Visualización')
            ->options([
                [
                    'valor' => 'block',
                    'etiqueta' => 'Bloque',
                    'icon' => IconRegistry::get('layout.block')
                ],
                [
                    'valor' => 'flex',
                    'etiqueta' => 'Flexbox',
                    'icon' => IconRegistry::get('layout.flex')
                ],
                [
                    'valor' => 'grid',
                    'etiqueta' => 'Grid',
                    'icon' => IconRegistry::get('layout.grid')
                ],
            ])
            ->default('block');

        // Opciones Flex
        if (in_array($level, ['flex', 'full'])) {
            $options = array_merge($options, $this->getFlexLayoutOptions($fieldPrefix));
        }

        // Opciones Grid
        if (in_array($level, ['grid', 'full'])) {
            $options = array_merge($options, $this->getGridLayoutOptions($fieldPrefix));
        }

        return $options;
    }

    /**
     * Opciones específicas de Flexbox.
     */
    protected function getFlexLayoutOptions(string $conditionField = 'layout'): array
    {
        return [
            Option::iconGroup(SchemaConstants::FIELD_FLEX_DIRECTION, 'Dirección')
                ->options([
                    ['valor' => 'row', 'etiqueta' => 'Horizontal', 'icon' => IconRegistry::get('direction.row')],
                    ['valor' => 'column', 'etiqueta' => 'Vertical', 'icon' => IconRegistry::get('direction.column')],
                ])
                ->default('row')
                ->condition([$conditionField, '==', 'flex']),

            Option::iconGroup(SchemaConstants::FIELD_FLEX_WRAP, 'Envoltura')
                ->options([
                    ['valor' => 'nowrap', 'etiqueta' => 'No envolver', 'icon' => IconRegistry::get('wrap.nowrap')],
                    ['valor' => 'wrap', 'etiqueta' => 'Envolver', 'icon' => IconRegistry::get('wrap.wrap')],
                ])
                ->default('nowrap')
                ->condition([$conditionField, '==', 'flex']),

            Option::iconGroup(SchemaConstants::FIELD_JUSTIFY, 'Justificación') // Usando nombre canónico (Phase 9 anticipada) o mantener flexJustify?
                                                                 // Plan Phase 3 says create HasLayoutOptions. 
                                                                 // Plan Phase 9 says "Unificar Nombres". 
                                                                 // Using standard names here 'justifyContent' is better for the future.
                                                                 // BUT check HasFlexbox existing names: 'flexJustify', 'flexAlign'.
                                                                 // If I use different names here, I might break style mapping if I don't update StyleMapper.
                                                                 // The plan says "HasLayoutOptions - Unifica... Reemplaza".
                                                                 // I should probably stick to `flexJustify` for compatibility if I'm not doing Phase 9 yet, OR be bold.
                                                                 // Plan Phase 1 says "Posibles inconsistencias visuales... Nombres de campos inconsistentes".
                                                                 // I'll stick to 'flexJustify' for now to match HasFlexbox unless new components use this trait.
                                                                 // Wait, PostRender uses 'justifyContent'. 
                                                                 // HasLayoutOptions is NEW. So I should use the BEST name.
                                                                 // I will use 'justifyContent' and 'alignItems' as per standard CSS and PostRender.
                                                                 // StyleMapper might need updates.
                ->options(IconRegistry::getGroup([
                    'justify.start' => ['valor' => 'flex-start', 'etiqueta' => 'Inicio'],
                    'justify.center' => ['valor' => 'center', 'etiqueta' => 'Centro'],
                    'justify.end' => ['valor' => 'flex-end', 'etiqueta' => 'Fin'],
                    'justify.between' => ['valor' => 'space-between', 'etiqueta' => 'Espacio entre'],
                    'justify.around' => ['valor' => 'space-around', 'etiqueta' => 'Espacio alrededor']
                ])) // Usando helper getGroup para demostrar uso
                ->default('flex-start')
                ->condition([$conditionField, '==', 'flex']),

            Option::iconGroup(SchemaConstants::FIELD_ALIGN, 'Alineación')
                ->options(IconRegistry::getGroup([
                    'align.stretch' => ['valor' => 'stretch', 'etiqueta' => 'Estirar'],
                    'align.start' => ['valor' => 'flex-start', 'etiqueta' => 'Inicio'],
                    'align.center' => ['valor' => 'center', 'etiqueta' => 'Centro'],
                    'align.end' => ['valor' => 'flex-end', 'etiqueta' => 'Fin']
                ]))
                ->default('stretch')
                ->condition([$conditionField, '==', 'flex']),

            Option::gap(SchemaConstants::FIELD_GAP, 'Separación (Gap)')
                ->default(0)
                ->condition([$conditionField, '==', 'flex']),
        ];
    }

    protected function getGridLayoutOptions(string $conditionField = 'layout'): array
    {
        return [
            Option::slider(SchemaConstants::FIELD_GRID_COLUMNS, 'Columnas')
                ->min(1)
                ->max(12)
                ->step(1)
                ->default(3)
                ->condition([$conditionField, '==', 'grid']),

            Option::gap(SchemaConstants::FIELD_GRID_GAP, 'Separación Grid')
                ->default(20)
                ->condition([$conditionField, '==', 'grid']),
        ];
    }
}
