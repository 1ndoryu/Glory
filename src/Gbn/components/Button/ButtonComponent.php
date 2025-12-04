<?php

namespace Glory\Gbn\Components\Button;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasTypography;
use Glory\Gbn\Traits\HasCustomCSS;

class ButtonComponent extends AbstractComponent
{
    use HasSpacing, HasTypography, HasCustomCSS;

    protected string $id = 'button';
    protected string $label = 'Botón';

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryButton',
            'dataAttribute' => 'data-gbn-button',
        ];
    }

    public function getDefaults(): array
    {
        return [
            'texto' => 'Click aquí',
            'url' => '#',
            'target' => '_self',
            'variant' => 'primary',
            'size' => 'medium',
            'width' => 'auto',
            'align' => 'center'
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // 1. Contenido - Tab: Contenido
        $schema->addOption(
            Option::text('texto', 'Texto del Botón')
                ->default('Click aquí')
                ->tab('Contenido')
        );

        $schema->addOption(
            Option::text('url', 'Enlace (URL)')
                ->default('#')
                ->tab('Contenido')
        );

        $schema->addOption(
            Option::select('target', 'Abrir en')
                ->options([
                    '_self' => 'Misma pestaña',
                    '_blank' => 'Nueva pestaña'
                ])
                ->default('_self')
                ->tab('Contenido')
        );

        // 2. Variantes - Tab: Estilo
        $schema->addOption(
            Option::select('variant', 'Estilo (Variante)')
                ->options([
                    'primary' => 'Primario (Sólido)',
                    'secondary' => 'Secundario (Borde)',
                    'ghost' => 'Fantasma (Texto)',
                    'link' => 'Link Simple'
                ])
                ->default('primary')
                ->tab('Estilo')
        );

        $schema->addOption(
            Option::select('size', 'Tamaño')
                ->options([
                    'small' => 'Pequeño',
                    'medium' => 'Mediano',
                    'large' => 'Grande'
                ])
                ->default('medium')
                ->tab('Estilo')
        );
        
        // 3. Dimensiones - Tab: Estilo
        $schema->addOption(
            Option::select('width', 'Ancho')
                ->options([
                    'auto' => 'Automático (Auto)',
                    '100%' => 'Completo (100%)'
                ])
                ->default('auto')
                ->tab('Estilo')
        );

        // 4. Tipografía - Tab: Estilo
        $schema->addOption(
            Option::typography('typography', 'Tipografía')
                ->tab('Estilo')
        );

        // 5. Colores Personalizados - Tab: Estilo
        $schema->addOption(
            Option::color('customBg', 'Fondo Personalizado')
                ->tab('Estilo')
                ->description('Sobrescribe la variante')
        );
        
        $schema->addOption(
            Option::color('customColor', 'Texto Personalizado')
                ->tab('Estilo')
                ->description('Sobrescribe la variante')
        );

        // 6. Spacing - Tab: Estilo
        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }
        
        // 7. Border Radius
        $schema->addOption(
            Option::text('borderRadius', 'Radio de Borde')
                ->tab('Estilo')
                ->description('Ej: 4px, 50px')
        );

        // 8. Custom CSS
        $schema->addOption($this->getCustomCSSOption());

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="2" ry="2"></rect></svg>';
    }

    public function getTemplate(): string
    {
        return '<a href="#" gloryButton class="btn btn-primary" opciones="texto: \'Click aquí\', variant: \'primary\'">Click aquí</a>';
    }
}
