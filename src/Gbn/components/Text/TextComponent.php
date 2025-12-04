<?php

namespace Glory\Gbn\Components\Text;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasCustomCSS;

class TextComponent extends AbstractComponent
{
    use HasSpacing, HasCustomCSS;

    protected string $id = 'text';
    protected string $label = 'Texto';

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryTexto',
            'dataAttribute' => 'data-gbn-text',
        ];
    }

    public function getDefaults(): array
    {
        return [
            'tag' => 'p',
            'texto' => 'Nuevo texto',
            'alineacion' => '',
            'color' => '',
            'size' => ''
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // 1. Tag - Tab: Contenido
        $schema->addOption(
            Option::select('tag', 'Etiqueta HTML')
                ->options([
                    ['valor' => 'p', 'etiqueta' => 'Párrafo (p)'],
                    ['valor' => 'h1', 'etiqueta' => 'Encabezado 1 (h1)'],
                    ['valor' => 'h2', 'etiqueta' => 'Encabezado 2 (h2)'],
                    ['valor' => 'h3', 'etiqueta' => 'Encabezado 3 (h3)'],
                    ['valor' => 'h4', 'etiqueta' => 'Encabezado 4 (h4)'],
                    ['valor' => 'h5', 'etiqueta' => 'Encabezado 5 (h5)'],
                    ['valor' => 'h6', 'etiqueta' => 'Encabezado 6 (h6)'],
                    ['valor' => 'span', 'etiqueta' => 'Span'],
                    ['valor' => 'div', 'etiqueta' => 'Div'],
                ])
                ->tab('Contenido')
        );

        // 2. Texto - Tab: Contenido
        $schema->addOption(
            Option::richText('texto', 'Contenido')
                ->tab('Contenido')
        );

        // 3. Typography - Tab: Estilo
        $schema->addOption(
            Option::typography('typography', 'Tipografía')
                ->tab('Estilo')
        );

        // 4. Alineación - Tab: Estilo
        $schema->addOption(
            Option::iconGroup('alineacion', 'Alineación')
                ->options([
                    ['valor' => 'left', 'etiqueta' => 'Izquierda', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 9.5H3M21 4.5H3M21 14.5H3M17 19.5H3"/></svg>'],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M19 9.5H5M21 4.5H3M21 14.5H3M19 19.5H5"/></svg>'],
                    ['valor' => 'right', 'etiqueta' => 'Derecha', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 9.5H7M21 4.5H3M21 14.5H3M21 19.5H7"/></svg>'],
                    ['valor' => 'justify', 'etiqueta' => 'Justificado', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 9.5H3M21 4.5H3M21 14.5H3M21 19.5H3"/></svg>'],
                ])
                ->tab('Estilo')
        );

        // 5. Color - Tab: Estilo
        $schema->addOption(
            Option::color('color', 'Color')
                ->allowTransparency()
                ->tab('Estilo')
        );

        // 6. Spacing (from Trait) - Tab: Estilo
        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

        // 7. Background - Tab: Estilo
        $schema->addOption(
            Option::color('backgroundColor', 'Color de Fondo')
                ->allowTransparency()
                ->tab('Estilo')
        );

        // 8. Border - Tab: Estilo
        $schema->addOption(
            Option::text('borderWidth', 'Ancho de Borde')
                ->default('')
                ->tab('Estilo')
                ->description('Ej: 1px, 2px')
        );

        $schema->addOption(
            Option::select('borderStyle', 'Estilo de Borde')
                ->options([
                    ['valor' => '', 'etiqueta' => 'Ninguno'],
                    ['valor' => 'solid', 'etiqueta' => 'Sólido'],
                    ['valor' => 'dashed', 'etiqueta' => 'Discontinuo'],
                    ['valor' => 'dotted', 'etiqueta' => 'Punteado'],
                    ['valor' => 'double', 'etiqueta' => 'Doble'],
                ])
                ->tab('Estilo')
        );

        $schema->addOption(
            Option::color('borderColor', 'Color de Borde')
                ->allowTransparency()
                ->tab('Estilo')
        );

        $schema->addOption(
            Option::text('borderRadius', 'Radio de Borde')
                ->default('')
                ->tab('Estilo')
                ->description('Ej: 4px, 8px, 50%')
        );

        // 9. Custom CSS - Tab: Avanzado
        $schema->addOption($this->getCustomCSSOption());

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>';
    }

    public function getTemplate(): string
    {
        return '<div gloryTexto="p" opciones="texto: \'Nuevo texto\'">Nuevo texto</div>';
    }
}
