<?php

namespace Glory\Gbn\Components\Menu;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Schema\SchemaConstants;
use Glory\Gbn\Icons\IconRegistry;
use Glory\Gbn\Traits\HasTypography;

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
    use HasTypography;
    
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
        $schema = SchemaBuilder::create();

        // ═══════════════════════════════════════════════
        // Tab: CONFIGURACIÓN
        // ═══════════════════════════════════════════════

        $schema->addOption(
            Option::iconGroup('menuSource', 'Fuente del Menú')
                ->options([
                    [
                        'valor' => 'wordpress',
                        'etiqueta' => 'WordPress',
                        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>'
                    ],
                    [
                        'valor' => 'manual',
                        'etiqueta' => 'Manual',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>'
                    ]
                ])
                ->default('wordpress')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::select('menuLocation', 'Ubicación del Menú')
                ->options(self::getMenuLocations())
                ->default('main_navigation')
                ->tab('Configuración')
                ->condition(['menuSource', '==', 'wordpress'])
                ->description('Selecciona una ubicación de menú registrada')
        );

        $schema->addOption(
            Option::text('menuId', 'ID HTML del Menú')
                ->default('mainMenu')
                ->tab('Configuración')
        );

        $schema->addOption(
            Option::slider('menuDepth', 'Profundidad del Menú')
                ->min(1)
                ->max(5)
                ->default(3)
                ->tab('Configuración')
                ->condition(['menuSource', '==', 'wordpress'])
                ->description('Niveles de submenú a mostrar')
        );

        $schema->addOption(
            Option::richText('manualItems', 'Items del Menú (Manual)')
                ->default('')
                ->tab('Configuración')
                ->condition(['menuSource', '==', 'manual'])
                ->description('Formato: Título|URL por línea. Ej: Inicio|/')
        );

        // ═══════════════════════════════════════════════
        // Tab: ESTILO
        // ═══════════════════════════════════════════════

        $schema->addOption(
            Option::iconGroup(SchemaConstants::FIELD_FLEX_DIRECTION, 'Orientación')
                ->options([
                    [
                        'valor' => 'row',
                        'etiqueta' => 'Horizontal',
                        'icon' => IconRegistry::get('direction.row')
                    ],
                    [
                        'valor' => 'column',
                        'etiqueta' => 'Vertical',
                        'icon' => IconRegistry::get('direction.column')
                    ]
                ])
                ->default('row')
                ->tab('Estilo')
        );

        $schema->addOption(
            Option::gap()
                ->default(32) // 2rem approx 32px
                ->tab('Estilo')
        );

        // Replace manual typography fields with HasTypography options
        // This adds: typography (size, weight, transform...), color (text), textAlign
        $this->addTypographyOptions($schema, 'Estilo');

        $schema->addOption(
            Option::color('linkColorHover', 'Color Hover')
                ->tab('Estilo')
        );

        // ═══════════════════════════════════════════════
        // Tab: MÓVIL
        // ═══════════════════════════════════════════════

        $schema->addOption(
            Option::text('mobileBreakpoint', 'Breakpoint Móvil')
                ->default('768px')
                ->tab('Móvil')
                ->description('Ancho donde aparece el menú hamburguesa')
        );

        $schema->addOption(
            Option::color('mobileBackgroundColor', 'Fondo (Móvil)')
                ->default('rgba(248, 248, 248, 0.95)')
                ->tab('Móvil')
        );

        $schema->addOption(
            Option::select('mobileAnimation', 'Animación de Apertura')
                ->options([
                    ['valor' => 'slideDown', 'etiqueta' => 'Deslizar hacia abajo'],
                    ['valor' => 'slideLeft', 'etiqueta' => 'Deslizar desde derecha'],
                    ['valor' => 'fadeIn', 'etiqueta' => 'Desvanecer'],
                ])
                ->default('slideDown')
                ->tab('Móvil')
        );

        return $schema->toArray();
    }

    public function getDefaults(): array
    {
        return [
            'menuSource' => 'wordpress',
            'menuLocation' => 'main_navigation',
            'menuId' => 'mainMenu',
            'menuDepth' => 3,
            'manualItems' => '',
            SchemaConstants::FIELD_FLEX_DIRECTION => 'row',
            'gap' => 32,
            'color' => '', // Replaces linkColor
            'linkColorHover' => '',
            'typography' => [ // Replaces fontSize, fontWeight, textTransform
                'size' => '1rem',
                'weight' => '400',
                'transform' => 'none'
            ],
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
