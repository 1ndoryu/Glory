<?php

namespace Glory\Gbn\Components\Menu;

use Glory\Gbn\Components\AbstractComponent;

/**
 * Componente Menu para GBN.
 * 
 * Subcomponente del Header que gestiona el menú de navegación.
 * Soporta menús dinámicos de WordPress y menús manuales.
 * Reutiliza la lógica de MenuWalker de Glory pero es independiente.
 * 
 * @role menu
 * @selector [gloryMenu]
 */
class MenuComponent extends AbstractComponent
{
    protected string $id = 'menu';
    protected string $label = 'Menu';

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
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>';
    }

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryMenu',
            'dataAttribute' => 'data-gbn-menu'
        ];
    }

    public function getTemplate(): string
    {
        return '<nav gloryMenu class="siteMenuNav gbn-menu" role="navigation">
    <ul class="menu menu-level-1 gbn-menu-list">
        <li class="gbn-menu-item"><a href="#">Inicio</a></li>
        <li class="gbn-menu-item"><a href="#">Servicios</a></li>
        <li class="gbn-menu-item"><a href="#">Nosotros</a></li>
        <li class="gbn-menu-item"><a href="#">Contacto</a></li>
    </ul>
</nav>';
    }

    public function getSchema(): array
    {
        return [
            // Tab: Configuración
            [
                'id' => 'menuSource',
                'type' => 'iconGroup',
                'label' => 'Fuente del Menú',
                'default' => 'wordpress',
                'tab' => 'configuracion',
                'options' => [
                    [
                        'value' => 'wordpress',
                        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>',
                        'label' => 'WordPress'
                    ],
                    [
                        'value' => 'manual',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
                        'label' => 'Manual'
                    ]
                ]
            ],
            [
                'id' => 'menuLocation',
                'type' => 'select',
                'label' => 'Ubicación del Menú',
                'default' => 'main_navigation',
                'tab' => 'configuracion',
                'condition' => ['menuSource', '===', 'wordpress'],
                'options' => self::getMenuLocations(),
                'description' => 'Selecciona una ubicación de menú registrada'
            ],
            [
                'id' => 'menuId',
                'type' => 'text',
                'label' => 'ID HTML del Menú',
                'default' => 'mainMenu',
                'tab' => 'configuracion'
            ],
            [
                'id' => 'menuDepth',
                'type' => 'slider',
                'label' => 'Profundidad del Menú',
                'default' => 3,
                'min' => 1,
                'max' => 5,
                'tab' => 'configuracion',
                'condition' => ['menuSource', '===', 'wordpress'],
                'description' => 'Niveles de submenú a mostrar'
            ],
            [
                'id' => 'manualItems',
                'type' => 'richText',
                'label' => 'Items del Menú (Manual)',
                'default' => '',
                'tab' => 'configuracion',
                'condition' => ['menuSource', '===', 'manual'],
                'description' => 'Formato: Título|URL por línea. Ej: Inicio|/'
            ],
            // Tab: Estilo
            [
                'id' => 'layout',
                'type' => 'iconGroup',
                'label' => 'Orientación',
                'default' => 'horizontal',
                'tab' => 'estilo',
                'options' => [
                    [
                        'value' => 'horizontal',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
                        'label' => 'Horizontal'
                    ],
                    [
                        'value' => 'vertical',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>',
                        'label' => 'Vertical'
                    ]
                ]
            ],
            [
                'id' => 'gap',
                'type' => 'text',
                'label' => 'Espacio entre Items',
                'default' => '2rem',
                'tab' => 'estilo'
            ],
            [
                'id' => 'linkColor',
                'type' => 'color',
                'label' => 'Color de Enlaces',
                'default' => '',
                'tab' => 'estilo'
            ],
            [
                'id' => 'linkColorHover',
                'type' => 'color',
                'label' => 'Color Hover',
                'default' => '',
                'tab' => 'estilo'
            ],
            [
                'id' => 'fontSize',
                'type' => 'text',
                'label' => 'Tamaño de Fuente',
                'default' => '1rem',
                'tab' => 'estilo'
            ],
            [
                'id' => 'fontWeight',
                'type' => 'select',
                'label' => 'Peso de Fuente',
                'default' => '400',
                'tab' => 'estilo',
                'options' => [
                    '300' => 'Light',
                    '400' => 'Normal',
                    '500' => 'Medium',
                    '600' => 'Semi Bold',
                    '700' => 'Bold'
                ]
            ],
            [
                'id' => 'textTransform',
                'type' => 'select',
                'label' => 'Transformación de Texto',
                'default' => 'none',
                'tab' => 'estilo',
                'options' => [
                    'none' => 'Normal',
                    'uppercase' => 'Mayúsculas',
                    'lowercase' => 'Minúsculas',
                    'capitalize' => 'Capitalizado'
                ]
            ],
            // Tab: Móvil
            [
                'id' => 'mobileBreakpoint',
                'type' => 'text',
                'label' => 'Breakpoint Móvil',
                'default' => '768px',
                'tab' => 'movil',
                'description' => 'Ancho de pantalla donde el menú se convierte en hamburguesa'
            ],
            [
                'id' => 'mobileBackgroundColor',
                'type' => 'color',
                'label' => 'Fondo (Móvil)',
                'default' => 'rgba(248, 248, 248, 0.95)',
                'tab' => 'movil'
            ],
            [
                'id' => 'mobileAnimation',
                'type' => 'select',
                'label' => 'Animación de Apertura',
                'default' => 'slideDown',
                'tab' => 'movil',
                'options' => [
                    'slideDown' => 'Deslizar hacia abajo',
                    'slideLeft' => 'Deslizar desde derecha',
                    'fadeIn' => 'Desvanecer'
                ]
            ]
        ];
    }

    public function getDefaults(): array
    {
        return [
            'menuSource' => 'wordpress',
            'menuLocation' => 'main_navigation',
            'menuId' => 'mainMenu',
            'menuDepth' => 3,
            'manualItems' => '',
            'layout' => 'horizontal',
            'gap' => '2rem',
            'linkColor' => '',
            'linkColorHover' => '',
            'fontSize' => '1rem',
            'fontWeight' => '400',
            'textTransform' => 'none',
            'mobileBreakpoint' => '768px',
            'mobileBackgroundColor' => 'rgba(248, 248, 248, 0.95)',
            'mobileAnimation' => 'slideDown'
        ];
    }

    /**
     * El menú puede contener items de menú personalizados.
     */
    public function getAllowedChildren(): array
    {
        return ['menuItem'];
    }

    /**
     * Obtiene las ubicaciones de menú registradas en WordPress.
     * Usado para el campo select de menuLocation.
     * 
     * @return array
     */
    public static function getMenuLocations(): array
    {
        $locations = get_registered_nav_menus();
        $options = [];

        foreach ($locations as $slug => $description) {
            $options[$slug] = $description;
        }

        // Fallback si no hay ubicaciones
        if (empty($options)) {
            $options['main_navigation'] = 'Main Navigation (default)';
        }

        return $options;
    }
}
