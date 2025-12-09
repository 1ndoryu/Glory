<?php

namespace Glory\Gbn\Components\Footer;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasBackground;
use Glory\Gbn\Traits\HasTypography;
use Glory\Gbn\Traits\HasCustomCSS;

/**
 * Componente Footer para GBN.
 * 
 * Contenedor principal del footer del sitio.
 * Independiente y fácil de editar en código.
 * 
 * @role footer
 * @selector [gloryFooter]
 */
class FooterComponent extends AbstractComponent
{
    use HasSpacing, HasBackground, HasTypography, HasCustomCSS;

    protected string $id = 'footer';
    protected string $label = 'Footer';

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
        // SVG personalizado para Footer
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="15" width="20" height="6" rx="1"/><line x1="4" y1="18" x2="8" y2="18"/><line x1="12" y1="18" x2="20" y2="18"/></svg>';
    }

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryFooter',
            'dataAttribute' => 'data-gbn-footer'
        ];
    }

    public function getTemplate(): string
    {
        // Template HTML base para insertar un nuevo footer
        $currentYear = date('Y');
        return '<footer gloryFooter class="gbn-footer" role="contentinfo">
    <div class="gbn-footer-container">
        <div class="gbn-footer-content">
            <div class="gbn-footer-section">
                <h4>Acerca de</h4>
                <p>Descripción breve del sitio o empresa.</p>
            </div>
            <div class="gbn-footer-section">
                <h4>Enlaces</h4>
                <ul>
                    <li><a href="#">Inicio</a></li>
                    <li><a href="#">Servicios</a></li>
                    <li><a href="#">Contacto</a></li>
                </ul>
            </div>
            <div class="gbn-footer-section">
                <h4>Contacto</h4>
                <p>info@ejemplo.com</p>
            </div>
        </div>
        <div class="gbn-footer-bottom">
            <p>&copy; ' . $currentYear . ' Todos los derechos reservados.</p>
        </div>
    </div>
</footer>';
    }

    public function getSchema(): array
    {
        $builder = SchemaBuilder::create();

        // --- Tab: Contenido ---

        // Copyright Text
        $builder->addOption(
            Option::text('copyrightText', 'Texto de Copyright')
                ->default('© {year} Todos los derechos reservados.')
                ->tab('Contenido')
                ->description('Usa {year} para el año actual')
        );

        // Social Links Toggle
        $builder->addOption(
            Option::toggle('showSocialLinks', 'Mostrar Redes Sociales')
                ->default(false)
                ->tab('Contenido')
        );

        // --- Tab: Estilo ---

        // Background Color (Manual, outside of HasBackground usually)
        $builder->addOption(
            Option::color('backgroundColor', 'Color de Fondo')
                ->default('#1a1a1a')
                ->tab('Estilo')
        );

        // Typography Options (Trait)
        // Includes: typography, color (text), textAlign
        foreach ($this->getTypographyOptions('Estilo') as $option) {
            // Set specific defaults if needed
            if ($option->getId() === 'color') {
                $option->default('#ffffff');
            }
            $builder->addOption($option);
        }

        // Link Colors
        $builder->addOption(
            Option::color('linkColor', 'Color de Enlaces')
                ->default('#cccccc')
                ->tab('Estilo')
        );

        $builder->addOption(
            Option::color('linkColorHover', 'Color Enlaces Hover')
                ->default('#ffffff')
                ->tab('Estilo')
        );

        // Background Options (Trait: Image, Size, Position, etc.)
        foreach ($this->getBackgroundOptions() as $option) {
            $option->tab('Estilo');
            $builder->addOption($option);
        }

        // Spacing Options (Trait: Padding, Margin)
        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            if ($option->getId() === 'padding') {
                $option->default([
                    'superior' => '2rem',
                    'derecho' => '1rem',
                    'inferior' => '2rem',
                    'izquierdo' => '1rem'
                ]);
            }
            $builder->addOption($option);
        }

        // Columns Layout
        $builder->addOption(
            Option::iconGroup('columnsLayout', 'Distribución de Columnas')
                ->options([
                    [
                        'valor' => '1',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="1"/></svg>',
                        'etiqueta' => '1 Columna'
                    ],
                    [
                        'valor' => '2',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="18" rx="1"/></svg>',
                        'etiqueta' => '2 Columnas'
                    ],
                    [
                        'valor' => '3',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="5" height="18" rx="1"/><rect x="9" y="3" width="6" height="18" rx="1"/><rect x="17" y="3" width="5" height="18" rx="1"/></svg>',
                        'etiqueta' => '3 Columnas'
                    ],
                    [
                        'valor' => '4',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="4" height="18" rx="1"/><rect x="8" y="3" width="4" height="18" rx="1"/><rect x="14" y="3" width="4" height="18" rx="1"/><rect x="20" y="3" width="2" height="18" rx="1"/></svg>',
                        'etiqueta' => '4 Columnas'
                    ]
                ])
                ->default('3')
                ->tab('Estilo')
        );

        // Gap
        // Using Option::gap if available (checked PrincipalComponent, implies Option::gap exists or is manually created)
        // Docs say: Option::gap() is a helper.
        $builder->addOption(
            Option::gap('gap', 'Espacio entre Columnas')
                ->default('2rem')
                ->tab('Estilo')
        );

        // Max Width
        $builder->addOption(
            Option::text('containerMaxWidth', 'Ancho Máximo')
                ->default('1200px')
                ->tab('Estilo')
        );

        // --- Tab: Avanzado ---

        // Custom CSS (Trait)
        $builder->addOption($this->getCustomCSSOption()->tab('Avanzado'));

        // Exclude on Pages
        $builder->addOption(
            Option::text('excludeOnPages', 'Excluir en Páginas (IDs)')
                ->tab('Avanzado')
                ->description('IDs separados por coma')
        );

        return $builder->toArray();
    }

    public function getDefaults(): array
    {
        // Providing defaults helps with initialization
        return [
            'copyrightText' => '© {year} Todos los derechos reservados.',
            'showSocialLinks' => false,
            'backgroundColor' => '#1a1a1a',
            'color' => '#ffffff', // Renamed from textColor to match HasTypography
            'linkColor' => '#cccccc',
            'linkColorHover' => '#ffffff',
            'padding' => [
                'superior' => '2rem',
                'derecho' => '1rem',
                'inferior' => '2rem',
                'izquierdo' => '1rem'
            ],
            'columnsLayout' => '3',
            'gap' => '2rem',
            'containerMaxWidth' => '1200px',
            'customClass' => '',
            'excludeOnPages' => ''
        ];
    }

    /**
     * Los hijos permitidos dentro del Footer.
     */
    public function getAllowedChildren(): array
    {
        return ['secundario', 'text', 'logo', 'menu', 'button', 'image'];
    }
}
