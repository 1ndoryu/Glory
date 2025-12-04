<?php

namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;

/**
 * Trait HasBorder - Fase 11 Refactorización SOLID
 * 
 * Proporciona opciones de borde reutilizables para componentes.
 * Equivalente PHP del trait de border en renderer-traits.js
 * 
 * Opciones incluidas:
 * - borderWidth: Ancho del borde
 * - borderStyle: Estilo del borde (solid, dashed, dotted, etc.)
 * - borderColor: Color del borde
 * - borderRadius: Radio de esquinas
 * 
 * @package Glory\Gbn\Traits
 */
trait HasBorder
{
    /**
     * Agrega opciones de borde al SchemaBuilder
     * 
     * @param SchemaBuilder $builder
     * @param string $tab Tab donde mostrar ('estilo' por defecto)
     * @return void
     */
    protected function addBorderOptions(SchemaBuilder $builder, string $tab = 'Estilo'): void
    {
        // 1. Toggle
        $builder->addOption(
            Option::toggle('hasBorder', 'Borde')
                ->default(false)
                ->tab($tab)
        );

        // 2. Width
        $builder->addOption(
            Option::text('borderWidth', 'Ancho de Borde')
                ->default('')
                ->tab($tab)
                ->condition('hasBorder', true)
                ->description('Ej: 1px, 2px')
        );

        // 3. Style (Icon Group)
        $builder->addOption(
            Option::iconGroup('borderStyle', 'Estilo de Borde')
                ->options([
                    ['valor' => 'solid', 'etiqueta' => 'Sólido', 'icon' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><line x1="2" y1="12" x2="22" y2="12"/></svg>'],
                    ['valor' => 'dashed', 'etiqueta' => 'Discontinuo', 'icon' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M5 12h2"/><path d="M11 12h2"/><path d="M17 12h2"/></svg>'],
                    ['valor' => 'dotted', 'etiqueta' => 'Punteado', 'icon' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><circle cx="4" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="20" cy="12" r="1"/></svg>'],
                    ['valor' => 'double', 'etiqueta' => 'Doble', 'icon' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><line x1="2" y1="9" x2="22" y2="9"/><line x1="2" y1="15" x2="22" y2="15"/></svg>'],
                ])
                ->default('solid')
                ->tab($tab)
                ->condition('hasBorder', true)
        );

        // 4. Color
        $builder->addOption(
            Option::color('borderColor', 'Color de Borde')
                ->allowTransparency()
                ->tab($tab)
                ->condition('hasBorder', true)
        );

        // 5. Radius
        $builder->addOption(
            Option::text('borderRadius', 'Radio de Borde')
                ->tab($tab)
                ->condition('hasBorder', true)
                ->description('Ej: 4px, 8px, 50px')
        );
    }

    /**
     * Agrega solo opciones básicas de borde (width, style, color)
     * Sin borderRadius
     * 
     * @param SchemaBuilder $builder
     * @param string $tab
     * @return void
     */
    protected function addBasicBorderOptions(SchemaBuilder $builder, string $tab = 'estilo'): void
    {
        $builder->addOption(
            Option::text('borderWidth')
                ->label('Ancho Borde')
                ->placeholder('ej: 1px')
                ->tab($tab)
        );

        $builder->addOption(
            Option::select('borderStyle')
                ->label('Estilo Borde')
                ->options([
                    'none'   => 'Ninguno',
                    'solid'  => 'Sólido',
                    'dashed' => 'Línea',
                    'dotted' => 'Puntos',
                ])
                ->tab($tab)
        );

        $builder->addOption(
            Option::color('borderColor')
                ->label('Color Borde')
                ->tab($tab)
        );
    }

    /**
     * Agrega solo borderRadius
     * Útil para elementos que no necesitan borde pero sí esquinas redondeadas
     * 
     * @param SchemaBuilder $builder
     * @param string $tab
     * @return void
     */
    protected function addBorderRadiusOption(SchemaBuilder $builder, string $tab = 'estilo'): void
    {
        $builder->addOption(
            Option::text('borderRadius')
                ->label('Radio Esquinas')
                ->placeholder('ej: 4px, 50%')
                ->tab($tab)
        );
    }
}
