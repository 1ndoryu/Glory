<?php

namespace Glory\Gbn\Components\Form;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasBorder;

/**
 * SubmitComponent - Botón de Envío para GBN
 * 
 * Botón tipo submit para formularios con estados hover/focus.
 * 
 * @role submit
 * @selector [glorySubmit]
 */
class SubmitComponent extends AbstractComponent
{
    use HasSpacing;
    use HasBorder;

    protected string $id = 'submit';
    protected string $label = 'Botón Enviar';

    public function getSelector(): array
    {
        return [
            'attribute' => 'glorySubmit',
            'dataAttribute' => 'data-gbn-submit',
        ];
    }

    public function getDefaults(): array
    {
        return [
            'texto' => 'Enviar',
            'loadingText' => 'Enviando...',
            'width' => 'auto',
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // Tab: Contenido
        $schema->addOption(
            Option::text('texto', 'Texto del Botón')
                ->default('Enviar')
                ->tab('Contenido')
        );

        $schema->addOption(
            Option::text('loadingText', 'Texto al Enviar')
                ->default('Enviando...')
                ->description('Texto mientras se procesa el formulario')
                ->tab('Contenido')
        );

        // Tab: Estilo
        $schema->addOption(
            Option::iconGroup('width', 'Ancho')
                ->options([
                    ['valor' => 'auto', 'etiqueta' => 'Auto', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M5 12h14M12 5v14"/></svg>'],
                    ['valor' => '100%', 'etiqueta' => 'Completo', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M3 12h18M3 6v12M21 6v12"/></svg>'],
                ])
                ->tab('Estilo')
        );

        $schema->addOption(
            Option::color('color', 'Color de Texto')
                ->tab('Estilo')
        );

        $schema->addOption(
            Option::color('backgroundColor', 'Color de Fondo')
                ->allowTransparency()
                ->tab('Estilo')
        );

        $schema->addOption(
            Option::typography('typography', 'Tipografía')
                ->tab('Estilo')
        );

        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

        $this->addBorderOptions($schema, 'Estilo');

        // Tab: Avanzado
        $schema->addOption(
            Option::text('cursor', 'Cursor')
                ->default('pointer')
                ->tab('Avanzado')
        );

        $schema->addOption(
            Option::text('transition', 'Transición')
                ->default('all 0.2s ease')
                ->description('Ej: all 0.3s ease')
                ->tab('Avanzado')
        );

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="2"/><path d="M9 12h6M12 10v4"/></svg>';
    }

    public function getTemplate(): string
    {
        return '<button type="submit" glorySubmit class="gbn-submit">Enviar</button>';
    }
}
