<?php

namespace Glory\Gbn\Components\Form;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasBorder;

/**
 * InputComponent - Campo de Entrada para GBN
 * 
 * Soporta tipos: text, email, tel, number, password, url
 * 
 * @role input
 * @selector [gloryInput]
 */
class InputComponent extends AbstractComponent
{
    use HasSpacing;
    use HasBorder;

    protected string $id = 'input';
    protected string $label = 'Campo de Texto';

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryInput',
            'dataAttribute' => 'data-gbn-input',
        ];
    }

    public function getDefaults(): array
    {
        return [
            'name' => '',
            'type' => 'text',
            'label' => 'Campo',
            'placeholder' => '',
            'required' => false,
            'pattern' => '',
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // Tab: Configuración
        $schema->addOption(
            Option::text('name', 'Nombre del Campo')
                ->default('')
                ->description('Atributo name del input (obligatorio)')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::text('label', 'Etiqueta')
                ->default('Campo')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::select('type', 'Tipo de Campo')
                ->options([
                    ['valor' => 'text', 'etiqueta' => 'Texto'],
                    ['valor' => 'email', 'etiqueta' => 'Email'],
                    ['valor' => 'tel', 'etiqueta' => 'Teléfono'],
                    ['valor' => 'number', 'etiqueta' => 'Número'],
                    ['valor' => 'password', 'etiqueta' => 'Contraseña'],
                    ['valor' => 'url', 'etiqueta' => 'URL'],
                ])
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::text('placeholder', 'Placeholder')
                ->default('')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::toggle('required', 'Campo Obligatorio')
                ->default(false)
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::text('pattern', 'Patrón de Validación')
                ->default('')
                ->description('Expresión regular HTML5 (opcional)')
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
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 10v4M10 10h4"/></svg>';
    }

    public function getTemplate(): string
    {
        return '<div gloryInput class="gbn-input-field"><label class="gbn-label">Campo</label><input type="text" name="" placeholder="" class="gbn-input"></div>';
    }
}
