<?php

namespace Glory\Gbn\Components\Secundario;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasFlexbox;
use Glory\Gbn\Traits\HasGrid;
use Glory\Gbn\Traits\HasSpacing;

class SecundarioComponent extends AbstractComponent
{
    use HasFlexbox, HasGrid, HasSpacing;

    protected string $id = 'secundario';
    protected string $label = 'Contenedor Secundario';

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryDivSecundario',
            'dataAttribute' => 'data-gbnSecundario',
        ];
    }

    public function getDefaults(): array
    {
        return [
            'layout' => 'block',
            'flexDirection' => 'row',
            'flexWrap' => 'nowrap',
            'flexJustify' => 'flex-start',
            'flexAlign' => 'stretch',
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // 1. Ancho (Fraction)
        $schema->addOption(
            Option::fraction('width', 'Ancho')
                ->options([
                    '1/1',
                    '5/6',
                    '4/5',
                    '3/4',
                    '2/3',
                    '3/5',
                    '1/2',
                    '2/5',
                    '1/3',
                    '1/4',
                    '1/5',
                    '1/6'
                ])
        );

        // 2. Altura
        $schema->addOption(
            Option::select('height', 'Altura')
                ->options([
                    ['valor' => 'auto', 'etiqueta' => 'Automática'],
                    ['valor' => 'min-content', 'etiqueta' => 'Mínima'],
                    ['valor' => '100vh', 'etiqueta' => 'Altura completa'],
                ])
        );

        // 3. Layout & Flexbox (from Trait)
        foreach ($this->getFlexboxOptions() as $option) {
            $schema->addOption($option);
        }

        // 4. Padding (from Trait)
        foreach ($this->getSpacingOptions() as $option) {
            if ($option->toArray()['id'] === 'padding') {
                // Override label for Secundario
                // Option methods return self, but we are iterating objects.
                // We can't easily modify the object in place without affecting others if it was shared (it's not, getSpacingOptions creates new ones).
                // But Option class methods mutate the object.
                // However, Option::spacing creates a new instance.
                // Let's just create a new Option manually to match exactly or accept the trait's default.
                // Trait says 'Padding'. Registry says 'Padding Interno (Auto)'.
                // I'll stick to the Trait for consistency, or I can manually add it.
                // Let's manually add it to match Registry exactly for now.
                $schema->addOption(
                    Option::spacing('padding', 'Padding Interno (Auto)')
                        ->units(['px', '%', 'rem'])
                        ->step(4)
                        ->min(0)
                        ->max(160)
                        ->fields(['superior', 'derecha', 'inferior', 'izquierda'])
                );
            }
        }

        // 5. Fondo
        $schema->addOption(
            Option::color('fondo', 'Color de fondo')
                ->allowTransparency()
        );

        // 6. Grid (from Trait)
        foreach ($this->getGridOptions() as $option) {
            $schema->addOption($option);
        }

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>';
    }

    public function getTemplate(): string
    {
        return '<div gloryDivSecundario class="divSecundario"></div>';
    }
}
