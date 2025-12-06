<?php

namespace Glory\Gbn\Components\Footer;

use Glory\Gbn\Components\AbstractComponent;

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
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="15" width="20" height="6" rx="1"/><line x1="4" y1="18" x2="8" y2="18"/><line x1="12" y1="18" x2="20" y2="18"/></svg>';
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
        return [
            // Tab: Contenido
            [
                'id' => 'copyrightText',
                'type' => 'text',
                'label' => 'Texto de Copyright',
                'default' => '© {year} Todos los derechos reservados.',
                'tab' => 'contenido',
                'description' => 'Usa {year} para el año actual'
            ],
            [
                'id' => 'showSocialLinks',
                'type' => 'toggle',
                'label' => 'Mostrar Redes Sociales',
                'default' => false,
                'tab' => 'contenido'
            ],
            // Tab: Estilo
            [
                'id' => 'backgroundColor',
                'type' => 'color',
                'label' => 'Color de Fondo',
                'default' => '#1a1a1a',
                'tab' => 'estilo'
            ],
            [
                'id' => 'textColor',
                'type' => 'color',
                'label' => 'Color de Texto',
                'default' => '#ffffff',
                'tab' => 'estilo'
            ],
            [
                'id' => 'linkColor',
                'type' => 'color',
                'label' => 'Color de Enlaces',
                'default' => '#cccccc',
                'tab' => 'estilo'
            ],
            [
                'id' => 'linkColorHover',
                'type' => 'color',
                'label' => 'Color Enlaces Hover',
                'default' => '#ffffff',
                'tab' => 'estilo'
            ],
            [
                'id' => 'padding',
                'type' => 'spacing',
                'label' => 'Padding',
                'default' => [
                    'superior' => '2rem',
                    'derecho' => '1rem',
                    'inferior' => '2rem',
                    'izquierdo' => '1rem'
                ],
                'tab' => 'estilo'
            ],
            [
                'id' => 'columnsLayout',
                'type' => 'iconGroup',
                'label' => 'Distribución de Columnas',
                'default' => '3',
                'tab' => 'estilo',
                'options' => [
                    [
                        'value' => '1',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="1"/></svg>',
                        'label' => '1 Columna'
                    ],
                    [
                        'value' => '2',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="18" rx="1"/></svg>',
                        'label' => '2 Columnas'
                    ],
                    [
                        'value' => '3',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="5" height="18" rx="1"/><rect x="9" y="3" width="6" height="18" rx="1"/><rect x="17" y="3" width="5" height="18" rx="1"/></svg>',
                        'label' => '3 Columnas'
                    ],
                    [
                        'value' => '4',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="4" height="18" rx="1"/><rect x="8" y="3" width="4" height="18" rx="1"/><rect x="14" y="3" width="4" height="18" rx="1"/><rect x="20" y="3" width="2" height="18" rx="1"/></svg>',
                        'label' => '4 Columnas'
                    ]
                ]
            ],
            [
                'id' => 'gap',
                'type' => 'text',
                'label' => 'Espacio entre Columnas',
                'default' => '2rem',
                'tab' => 'estilo'
            ],
            [
                'id' => 'containerMaxWidth',
                'type' => 'text',
                'label' => 'Ancho Máximo',
                'default' => '1200px',
                'tab' => 'estilo'
            ],
            // Tab: Avanzado
            [
                'id' => 'customClass',
                'type' => 'text',
                'label' => 'Clases Personalizadas',
                'default' => '',
                'tab' => 'avanzado'
            ],
            [
                'id' => 'excludeOnPages',
                'type' => 'text',
                'label' => 'Excluir en Páginas (IDs)',
                'default' => '',
                'tab' => 'avanzado',
                'description' => 'IDs de páginas separadas por coma donde no se mostrará el footer'
            ]
        ];
    }

    public function getDefaults(): array
    {
        return [
            'copyrightText' => '© {year} Todos los derechos reservados.',
            'showSocialLinks' => false,
            'backgroundColor' => '#1a1a1a',
            'textColor' => '#ffffff',
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
