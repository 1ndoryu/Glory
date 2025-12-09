<?php

namespace Glory\Gbn\Components\Form;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasBorder;

/**
 * SelectComponent - Selector Desplegable para GBN
 * 
 * Campo select con opciones configurables.
 * 
 * @role select
 * @selector [glorySelect]
 */
class SelectComponent extends AbstractComponent
{
    use HasSpacing;
    use HasBorder;

    protected string $id = 'select';
    protected string $label = 'Selector';

    public function getSelector(): array
    {
        return [
            'attribute' => 'glorySelect',
            'dataAttribute' => 'data-gbn-select',
        ];
    }

    public function getDefaults(): array
    {
        return [
            'name' => '',
            'label' => 'Seleccionar',
            'placeholder' => 'Seleccione una opción',
            'options' => "opcion1:Opción 1\nopcion2:Opción 2\nopcion3:Opción 3",
            'required' => false,
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // Tab: Configuración
        $schema->addOption(
            Option::text('name', 'Nombre del Campo')
                ->default('')
                ->description('Atributo name del select (obligatorio)')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::text('label', 'Etiqueta')
                ->default('Seleccionar')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::text('placeholder', 'Texto Placeholder')
                ->default('Seleccione una opción')
                ->description('Primera opción deshabilitada')
                ->tab('Configuración')
        );

        // Opciones como campo de texto: valor:etiqueta (una por línea)
        // Usamos richText para permitir edición multilínea
        $schema->addOption(
            Option::richText('options', 'Opciones')
                ->default("opcion1:Opción 1\nopcion2:Opción 2\nopcion3:Opción 3")
                ->description('Una opción por línea. Formato: valor:etiqueta')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::toggle('required', 'Campo Obligatorio')
                ->default(false)
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

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M8 12h8M12 10l4 4-4 4"/></svg>';
    }

    public function getTemplate(): string
    {
        return '<div glorySelect class="gbn-select-field"><label class="gbn-label">Seleccionar</label><select name="" class="gbn-select"><option value="" disabled selected>Seleccione una opción</option></select></div>';
    }
}
