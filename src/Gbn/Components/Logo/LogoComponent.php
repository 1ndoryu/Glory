<?php

namespace Glory\Gbn\Components\Logo;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasTypography;
use Glory\Gbn\Traits\HasDimensions;
use Glory\Gbn\Traits\HasBorder;

/**
 * Componente Logo para GBN.
 * 
 * Subcomponente del Header que gestiona el logo del sitio.
 * Soporta modos: imagen, texto o SVG personalizado.
 * 
 * @role logo
 * @selector [gloryLogo]
 */
class LogoComponent extends AbstractComponent
{
    use HasSpacing, HasTypography, HasDimensions, HasBorder;

    protected string $id = 'logo';
    protected string $label = 'Logo';

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>';
    }

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryLogo',
            'dataAttribute' => 'data-gbn-logo'
        ];
    }

    public function getTemplate(): string
    {
        return '<div gloryLogo class="siteMenuLogo gbn-logo">
    <a href="/" rel="home" class="gbn-logo-link">
        <span class="gbn-logo-text">Logo</span>
    </a>
</div>';
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // =====================================================
        // TAB: CONTENIDO
        // =====================================================

        // 1. Modo de Logo
        $schema->addOption(
            Option::iconGroup('logoMode', 'Tipo de Logo')
                ->options(\Glory\Gbn\Icons\IconRegistry::getGroup([
                    'format.image' => ['valor' => 'image', 'etiqueta' => 'Imagen'],
                    'format.text' => ['valor' => 'text', 'etiqueta' => 'Texto'],
                    'format.svg' => ['valor' => 'svg', 'etiqueta' => 'SVG']
                ]))
                ->default('text')
                ->tab('Contenido')
        );

        // 2. Texto (Condicional)
        $schema->addOption(
            Option::text('logoText', 'Texto del Logo')
                ->default('')
                ->tab('Contenido')
                ->condition(['logoMode', '===', 'text'])
                ->description('Deja vacío para usar el nombre del sitio')
        );

        // 3. Imagen (Condicional)
        $schema->addOption(
            Option::image('logoImage', 'Imagen del Logo')
                ->default('')
                ->tab('Contenido')
                ->condition(['logoMode', '===', 'image'])
        );

        // 4. SVG (Condicional)
        $schema->addOption(
            Option::richText('logoSvg', 'Código SVG')
                ->default('')
                ->tab('Contenido')
                ->condition(['logoMode', '===', 'svg'])
                ->description('Pega el código SVG directamente')
        );

        // ...

        // 7. Tipografía (HasTypography) - Condicional
        foreach ($this->getTypographyOptions('Estilo') as $option) {
            $option->condition(['logoMode', '===', 'text']);
            $schema->addOption($option);
        }

        // 9. Object Fit (Solo Imagen)
        $schema->addOption(
            Option::iconGroup('objectFit', 'Ajuste de Imagen')
                // ...
                ->default('contain') // Logos suelen necesitar contain por defecto
                ->tab('Estilo')
                ->condition(['logoMode', '===', 'image'])
        );

        // 10. Filter (Específico para Imagen)
        $schema->addOption(
            Option::select('filter', 'Filtro de Color')
                // ...
                ->default('none')
                ->tab('Estilo')
                ->condition(['logoMode', '===', 'image'])
        );

        // 11. Border (HasBorder)
        $this->addBorderOptions($schema, 'Estilo');

        // 12. Spacing (HasSpacing)
        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

        return $schema->toArray();
    }

    public function getDefaults(): array
    {
        return [
            'logoMode' => 'text',
            'logoText' => '',
            'logoImage' => '',
            'logoSvg' => '',
            'linkUrl' => '/',
            'maxHeight' => '2.8rem',
            'maxWidth' => 'auto',
            'filter' => 'none',
        ];
    }

    public function getAllowedChildren(): array
    {
        return [];
    }
}
