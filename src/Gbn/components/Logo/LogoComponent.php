<?php

namespace Glory\Gbn\Components\Logo;

use Glory\Gbn\Components\AbstractComponent;

/**
 * Componente Logo para GBN.
 * 
 * Subcomponente del Header que gestiona el logo del sitio.
 * Soporta modos: imagen, texto o SVG personalizado.
 * Independiente de Glory\Components\LogoRenderer.
 * 
 * @role logo
 * @selector [gloryLogo]
 */
class LogoComponent extends AbstractComponent
{
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
        return [
            // Tab: Contenido
            [
                'id' => 'logoMode',
                'type' => 'iconGroup',
                'label' => 'Tipo de Logo',
                'default' => 'text',
                'tab' => 'contenido',
                'options' => [
                    [
                        'value' => 'image',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
                        'label' => 'Imagen'
                    ],
                    [
                        'value' => 'text',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3M9 20h6M12 4v16"/></svg>',
                        'label' => 'Texto'
                    ],
                    [
                        'value' => 'svg',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
                        'label' => 'SVG'
                    ]
                ]
            ],
            [
                'id' => 'logoText',
                'type' => 'text',
                'label' => 'Texto del Logo',
                'default' => '',
                'tab' => 'contenido',
                'condition' => ['logoMode', '===', 'text'],
                'description' => 'Deja vacío para usar el nombre del sitio'
            ],
            [
                'id' => 'logoImage',
                'type' => 'image',
                'label' => 'Imagen del Logo',
                'default' => '',
                'tab' => 'contenido',
                'condition' => ['logoMode', '===', 'image']
            ],
            [
                'id' => 'logoSvg',
                'type' => 'richText',
                'label' => 'SVG del Logo',
                'default' => '',
                'tab' => 'contenido',
                'condition' => ['logoMode', '===', 'svg'],
                'description' => 'Pega el código SVG directamente'
            ],
            [
                'id' => 'linkUrl',
                'type' => 'text',
                'label' => 'URL del Enlace',
                'default' => '/',
                'tab' => 'contenido',
                'description' => 'Por defecto: página de inicio'
            ],
            // Tab: Estilo
            [
                'id' => 'maxHeight',
                'type' => 'text',
                'label' => 'Altura Máxima',
                'default' => '2.8rem',
                'tab' => 'estilo'
            ],
            [
                'id' => 'maxWidth',
                'type' => 'text',
                'label' => 'Ancho Máximo',
                'default' => 'auto',
                'tab' => 'estilo'
            ],
            [
                'id' => 'color',
                'type' => 'color',
                'label' => 'Color del Texto',
                'default' => '',
                'tab' => 'estilo',
                'condition' => ['logoMode', '===', 'text']
            ],
            [
                'id' => 'fontSize',
                'type' => 'text',
                'label' => 'Tamaño de Fuente',
                'default' => '1rem',
                'tab' => 'estilo',
                'condition' => ['logoMode', '===', 'text']
            ],
            [
                'id' => 'fontWeight',
                'type' => 'select',
                'label' => 'Peso de Fuente',
                'default' => '600',
                'tab' => 'estilo',
                'condition' => ['logoMode', '===', 'text'],
                'options' => [
                    '300' => 'Light',
                    '400' => 'Normal',
                    '500' => 'Medium',
                    '600' => 'Semi Bold',
                    '700' => 'Bold',
                    '800' => 'Extra Bold'
                ]
            ],
            [
                'id' => 'filter',
                'type' => 'select',
                'label' => 'Filtro de Color',
                'default' => 'none',
                'tab' => 'estilo',
                'condition' => ['logoMode', '===', 'image'],
                'options' => [
                    'none' => 'Sin filtro',
                    'white' => 'Blanco',
                    'black' => 'Negro',
                    'grayscale' => 'Escala de grises'
                ],
                'description' => 'Aplicar filtro CSS a la imagen'
            ],
            [
                'id' => 'padding',
                'type' => 'spacing',
                'label' => 'Padding',
                'default' => [
                    'superior' => '0',
                    'derecho' => '0',
                    'inferior' => '0',
                    'izquierdo' => '0'
                ],
                'tab' => 'estilo'
            ]
        ];
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
            'color' => '',
            'fontSize' => '1rem',
            'fontWeight' => '600',
            'filter' => 'none',
            'padding' => [
                'superior' => '0',
                'derecho' => '0',
                'inferior' => '0',
                'izquierdo' => '0'
            ]
        ];
    }

    /**
     * El logo no contiene otros componentes.
     */
    public function getAllowedChildren(): array
    {
        return [];
    }
}
