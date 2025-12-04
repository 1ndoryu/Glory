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
    protected function addBorderOptions(SchemaBuilder $builder, string $tab = 'estilo'): void
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
                    'double' => 'Doble',
                    'groove' => 'Surco',
                    'ridge'  => 'Relieve',
                    'inset'  => 'Hundido',
                    'outset' => 'Elevado',
                ])
                ->tab($tab)
        );

        $builder->addOption(
            Option::color('borderColor')
                ->label('Color Borde')
                ->tab($tab)
        );

        $builder->addOption(
            Option::text('borderRadius')
                ->label('Radio Esquinas')
                ->placeholder('ej: 4px')
                ->tab($tab)
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
