<?php

namespace Glory\Gbn\Components\Menu;

use Glory\Gbn\Components\AbstractComponent;

/**
 * Componente MenuItem para GBN.
 * 
 * Representa un item individual del menú de navegación.
 * Permite editar enlaces, texto y estilos de cada item.
 * 
 * @role menuItem
 * @selector [gloryMenuItem]
 */
class MenuItemComponent extends AbstractComponent
{
    protected string $id = 'menuItem';
    protected string $label = 'Menu Item';

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
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';
    }

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryMenuItem',
            'dataAttribute' => 'data-gbn-menu-item'
        ];
    }

    public function getTemplate(): string
    {
        return '<li gloryMenuItem class="gbn-menu-item">
    <a href="#" class="gbn-menu-link">Nuevo Enlace</a>
</li>';
    }

    public function getSchema(): array
    {
        return [
            // Tab: Contenido
            [
                'id' => 'linkText',
                'type' => 'text',
                'label' => 'Texto del Enlace',
                'default' => 'Enlace',
                'tab' => 'contenido'
            ],
            [
                'id' => 'linkUrl',
                'type' => 'text',
                'label' => 'URL',
                'default' => '#',
                'tab' => 'contenido'
            ],
            [
                'id' => 'linkTarget',
                'type' => 'select',
                'label' => 'Abrir en',
                'default' => '_self',
                'tab' => 'contenido',
                'options' => [
                    '_self' => 'Misma ventana',
                    '_blank' => 'Nueva ventana'
                ]
            ],
            [
                'id' => 'hasSubmenu',
                'type' => 'toggle',
                'label' => 'Tiene Submenú',
                'default' => false,
                'tab' => 'contenido'
            ],
            // Tab: Estilo
            [
                'id' => 'color',
                'type' => 'color',
                'label' => 'Color de Texto',
                'default' => '',
                'tab' => 'estilo'
            ],
            [
                'id' => 'colorHover',
                'type' => 'color',
                'label' => 'Color Hover',
                'default' => '',
                'tab' => 'estilo'
            ],
            [
                'id' => 'fontSize',
                'type' => 'text',
                'label' => 'Tamaño de Fuente',
                'default' => '',
                'tab' => 'estilo'
            ],
            [
                'id' => 'fontWeight',
                'type' => 'select',
                'label' => 'Peso de Fuente',
                'default' => '',
                'tab' => 'estilo',
                'options' => [
                    '' => 'Heredar',
                    '300' => 'Light',
                    '400' => 'Normal',
                    '500' => 'Medium',
                    '600' => 'Semi Bold',
                    '700' => 'Bold'
                ]
            ],
            [
                'id' => 'padding',
                'type' => 'spacing',
                'label' => 'Padding',
                'default' => [
                    'superior' => '',
                    'derecho' => '',
                    'inferior' => '',
                    'izquierdo' => ''
                ],
                'tab' => 'estilo'
            ],
            // Tab: Avanzado
            [
                'id' => 'customClass',
                'type' => 'text',
                'label' => 'Clase CSS',
                'default' => '',
                'tab' => 'avanzado'
            ],
            [
                'id' => 'isActive',
                'type' => 'toggle',
                'label' => 'Marcar como Activo',
                'default' => false,
                'tab' => 'avanzado',
                'description' => 'Añade la clase "current-menu-item"'
            ]
        ];
    }

    public function getDefaults(): array
    {
        return [
            'linkText' => 'Enlace',
            'linkUrl' => '#',
            'linkTarget' => '_self',
            'hasSubmenu' => false,
            'color' => '',
            'colorHover' => '',
            'fontSize' => '',
            'fontWeight' => '',
            'padding' => [
                'superior' => '',
                'derecho' => '',
                'inferior' => '',
                'izquierdo' => ''
            ],
            'customClass' => '',
            'isActive' => false
        ];
    }

    /**
     * Un MenuItem puede contener otros MenuItems (para submenús).
     */
    public function getAllowedChildren(): array
    {
        return ['menuItem'];
    }
}
