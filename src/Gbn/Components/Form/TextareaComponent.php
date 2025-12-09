<?php

namespace Glory\Gbn\Components\Form;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasBorder;

/**
 * TextareaComponent - Área de Texto para GBN
 * 
 * Campo de texto multilínea con configuración de filas.
 * 
 * @role textarea
 * @selector [gloryTextarea]
 */
class TextareaComponent extends AbstractComponent
{
    use HasSpacing;
    use HasBorder;

    protected string $id = 'textarea';
    protected string $label = 'Área de Texto';

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryTextarea',
            'dataAttribute' => 'data-gbn-textarea',
        ];
    }

    public function getDefaults(): array
    {
        return [
            'name' => '',
            'label' => 'Mensaje',
            'placeholder' => '',
            'rows' => 4,
            'required' => false,
            'maxlength' => '',
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // Tab: Configuración
        $schema->addOption(
            Option::text('name', 'Nombre del Campo')
                ->default('')
                ->description('Atributo name del textarea (obligatorio)')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::text('label', 'Etiqueta')
                ->default('Mensaje')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::text('placeholder', 'Placeholder')
                ->default('')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::slider('rows', 'Filas')
                ->min(2)
                ->max(20)
                ->default(4)
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::toggle('required', 'Campo Obligatorio')
                ->default(false)
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::text('maxlength', 'Límite de Caracteres')
                ->default('')
                ->description('Dejar vacío para sin límite')
                ->tab('Configuración')
        );

        // Tab: Estilo
        $schema->addOption(
            Option::color('color', 'Color de Texto')
                ->tab('Estilo')
        );

        $schema->addOption(
            Option::color('backgroundColor', 'Color de Fondo')
                ->allowTransparency()
                ->tab('Estilo')
        );

        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

        $this->addBorderOptions($schema, 'Estilo');

        // Tab: Tipografía
        $schema->addOption(
            Option::typography('typography', 'Tipografía')
                ->tab('Tipografía')
        );

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 7h10M7 11h10M7 15h6"/></svg>';
    }

    public function getTemplate(): string
    {
        return '<div gloryTextarea class="gbn-textarea-field"><label class="gbn-label">Mensaje</label><textarea name="" placeholder="" rows="4" class="gbn-textarea"></textarea></div>';
    }
}
