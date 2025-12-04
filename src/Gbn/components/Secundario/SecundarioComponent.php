<?php

namespace Glory\Gbn\Components\Secundario;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasFlexbox;
use Glory\Gbn\Traits\HasGrid;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasBackground;

use Glory\Gbn\Traits\HasCustomCSS;

class SecundarioComponent extends AbstractComponent
{
    use HasFlexbox, HasGrid, HasSpacing, HasBackground, HasCustomCSS;

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

        // 3. Layout & Flexbox (from Trait) - Tab: Contenido
        foreach ($this->getFlexboxOptions() as $option) {
            $option->tab('Contenido');
            $schema->addOption($option);
        }

        // 6. Grid (from Trait) - Tab: Contenido
        foreach ($this->getGridOptions() as $option) {
            $option->tab('Contenido');
            $schema->addOption($option);
        }

        // 1. Ancho (Fraction) - Tab: Estilo
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
                ->tab('Estilo')
        );

        // 2. Altura - Tab: Estilo
        $schema->addOption(
            Option::select('height', 'Altura')
                ->options([
                    ['valor' => 'auto', 'etiqueta' => 'Automática'],
                    ['valor' => 'min-content', 'etiqueta' => 'Mínima'],
                    ['valor' => '100vh', 'etiqueta' => 'Altura completa'],
                ])
                ->tab('Estilo')
        );

        // 4. Spacing (Padding & Margin) - Tab: Estilo
        $schema->addOption(
            Option::spacing('padding', 'Padding Interno (Auto)')
                ->units(['px', '%', 'rem'])
                ->step(4)
                ->min(0)
                ->max(160)
                ->fields(['superior', 'derecha', 'inferior', 'izquierda'])
                ->tab('Estilo')
        );

        $schema->addOption(
            Option::spacing('margin', 'Margen')
                ->units(['px', '%', 'rem'])
                ->step(4)
                ->min(-100)
                ->max(160)
                ->fields(['superior', 'derecha', 'inferior', 'izquierda'])
                ->tab('Estilo')
        );

        // 5. Fondo - Tab: Estilo
        $schema->addOption(
            Option::color('fondo', 'Color de fondo')
                ->allowTransparency()
                ->tab('Estilo')
        );

        // 6. Background Image (from Trait) - Tab: Estilo
        foreach ($this->getBackgroundOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

        // 7. Custom CSS - Tab: Avanzado
        $schema->addOption($this->getCustomCSSOption());

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
