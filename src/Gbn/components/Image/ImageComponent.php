<?php

namespace Glory\Gbn\Components\Image;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasCustomCSS;

class ImageComponent extends AbstractComponent
{
    use HasSpacing, HasCustomCSS;

    protected string $id = 'image';
    protected string $label = 'Imagen';

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryImage',
            'dataAttribute' => 'data-gbn-image',
        ];
    }

    public function getDefaults(): array
    {
        return [
            'src' => 'https://via.placeholder.com/150',
            'alt' => 'Imagen',
            'width' => '',
            'height' => '',
            'objectFit' => 'cover',
            'borderRadius' => ''
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // 1. Source - Tab: Contenido
        $schema->addOption(
            Option::image('src', 'Fuente de Imagen')
                ->tab('Contenido')
        );

        // 2. Alt Text - Tab: Contenido
        $schema->addOption(
            Option::text('alt', 'Texto Alternativo')
                ->tab('Contenido')
        );

        // 3. Dimensiones - Tab: Estilo
        $schema->addOption(
            Option::text('width', 'Ancho')
                ->tab('Estilo')
                ->description('Ej: 100%, 500px, auto')
        );

        $schema->addOption(
            Option::text('height', 'Alto')
                ->tab('Estilo')
                ->description('Ej: 300px, auto')
        );

        // 4. Object Fit - Tab: Estilo
        $schema->addOption(
            Option::select('objectFit', 'Ajuste de Imagen')
                ->options([
                    'cover' => 'Cubrir (Cover)',
                    'contain' => 'Contener (Contain)',
                    'fill' => 'Llenar (Fill)',
                    'none' => 'Ninguno',
                    'scale-down' => 'Reducir'
                ])
                ->tab('Estilo')
        );
        
        // 5. Border Radius - Tab: Estilo
         $schema->addOption(
            Option::text('borderRadius', 'Radio de Borde')
                ->tab('Estilo')
                ->description('Ej: 8px, 50%')
        );


        // 6. Spacing
        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

        // 7. Custom CSS
        $schema->addOption($this->getCustomCSSOption());

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
    }

    public function getTemplate(): string
    {
        return '<img gloryImage opciones="src: \'https://via.placeholder.com/150\', alt: \'Imagen\'" src="https://via.placeholder.com/150" alt="Imagen">';
    }
}
