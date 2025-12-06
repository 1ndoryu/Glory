<?php

namespace Glory\Gbn\Components\Principal;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasFlexbox;
use Glory\Gbn\Traits\HasGrid;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasBackground;

use Glory\Gbn\Traits\HasCustomCSS;
use Glory\Gbn\Traits\HasPositioning;

class PrincipalComponent extends AbstractComponent
{
    use HasFlexbox, HasGrid, HasSpacing, HasBackground, HasCustomCSS, HasPositioning;

    protected string $id = 'principal';
    protected string $label = 'Contenedor Principal';

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryDiv',
            'dataAttribute' => 'data-gbnPrincipal',
        ];
    }

    public function getDefaults(): array
    {
        return [
            'layout' => 'flex',
            'flexDirection' => 'row',
            'flexWrap' => 'wrap',
            'flexJustify' => 'flex-start',
            'flexAlign' => 'stretch',
            'maxAncho' => '1200px',
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // 1. Layout & Flexbox (from Trait) - Tab: Contenido
        foreach ($this->getFlexboxOptions() as $option) {
            $option->tab('Contenido');
            $schema->addOption($option);
        }

        // 7. Grid (from Trait) - Tab: Contenido
        foreach ($this->getGridOptions() as $option) {
            $option->tab('Contenido');
            $schema->addOption($option);
        }

        // 3. Alineación - Tab: Contenido
        $schema->addOption(
            Option::iconGroup('alineacion', 'Alineación del contenido')
                ->options([
                    ['valor' => 'inherit', 'etiqueta' => 'Hereda', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/></svg>'],
                    ['valor' => 'left', 'etiqueta' => 'Izquierda', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M4 12h10"/><path d="M4 18h12"/></svg>'],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M7 12h10"/><path d="M6 18h12"/></svg>'],
                    ['valor' => 'right', 'etiqueta' => 'Derecha', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M10 12h10"/><path d="M8 18h12"/></svg>'],
                ])
                ->tab('Contenido')
        );

        // 1. Altura (Smart Control) - Tab: Estilo
        $schema->addOption(
            Option::text('height', 'Altura')
                ->default('auto')
                ->tab('Estilo')
                ->description('Ej: auto, 100vh, 500px')
        );

        // 4. Ancho Máximo - Tab: Estilo
        $schema->addOption(
            Option::text('maxAncho', 'Ancho máximo')
                ->default('1200px')
                ->tab('Estilo')
        );

        // 2. Spacing (Padding & Margin) - Tab: Estilo
        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

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

        // 7. Positioning (from Trait) - Tab: Avanzado
        foreach ($this->getPositioningOptions() as $option) {
            $schema->addOption($option);
        }

        // 8. Custom CSS - Tab: Avanzado
        $schema->addOption($this->getCustomCSSOption());

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line></svg>';
    }

    public function getTemplate(): string
    {
        return '<div gloryDiv class="divPrincipal"><div gloryDivSecundario class="divSecundario"></div></div>';
    }

    /**
     * Principal acepta contenedores secundarios como hijos principales.
     * Los secundarios son los bloques de contenido internos.
     * 
     * @return array<string>
     */
    public function getAllowedChildren(): array
    {
        return ['secundario'];
    }
}
