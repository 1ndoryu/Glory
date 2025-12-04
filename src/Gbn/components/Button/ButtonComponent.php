<?php

/**
 * ButtonComponent - Componente de Botón para GBN
 * 
 * Diseñado para ser lo más nativo posible. El componente lee:
 * - El texto desde innerHTML
 * - La URL desde el atributo href
 * - El target desde el atributo target  
 * - Las clases CSS nativas para variantes (btnPrimary, btnSecondary, etc.)
 * 
 * No requiere el atributo 'opciones=' para funcionar correctamente.
 */

namespace Glory\Gbn\Components\Button;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasCustomCSS;

class ButtonComponent extends AbstractComponent
{
    use HasSpacing, HasCustomCSS;

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
            'width' => 'auto'
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // =====================================================
        // TAB: CONTENIDO
        // =====================================================

        // 1. Texto del Botón
        // El valor inicial se infiere desde innerHTML en builder.js
        $schema->addOption(
            Option::text('texto', 'Texto del Botón')
                ->default('Click aquí')
                ->tab('Contenido')
        );

        // 2. URL (Enlace)
        // El valor inicial se infiere desde el atributo href en builder.js
        $schema->addOption(
            Option::text('url', 'Enlace (URL)')
                ->default('#')
                ->tab('Contenido')
                ->description('Se lee automáticamente del atributo href')
        );

        // 3. Target (Abrir en...)
        // El valor inicial se infiere desde el atributo target en builder.js
        $schema->addOption(
            Option::select('target', 'Abrir en')
                ->options([
                    '_self' => 'Misma pestaña',
                    '_blank' => 'Nueva pestaña'
                ])
                ->default('_self')
                ->tab('Contenido')
        );

        // =====================================================
        // TAB: ESTILO
        // =====================================================

        // 4. Ancho del Botón
        $schema->addOption(
            Option::select('width', 'Ancho')
                ->options([
                    'auto' => 'Automático',
                    '100%' => 'Completo (100%)',
                    'fit-content' => 'Ajustar al contenido'
                ])
                ->default('auto')
                ->tab('Estilo')
        );

        // 5. Display Type
        $schema->addOption(
            Option::iconGroup('display', 'Display')
                ->options([
                    ['valor' => 'inline-block', 'etiqueta' => 'Inline Block', 'icon' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="8" width="8" height="8" rx="1"/><rect x="13" y="8" width="8" height="8" rx="1"/></svg>'],
                    ['valor' => 'block', 'etiqueta' => 'Block', 'icon' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>'],
                    ['valor' => 'inline-flex', 'etiqueta' => 'Inline Flex', 'icon' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="6" width="8" height="12" rx="1"/><rect x="13" y="6" width="8" height="12" rx="1"/></svg>'],
                ])
                ->tab('Estilo')
        );

        // 6. Alineación del texto
        $schema->addOption(
            Option::iconGroup('textAlign', 'Alineación')
                ->options([
                    ['valor' => 'left', 'etiqueta' => 'Izquierda', 'icon' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 9.5H3M21 4.5H3M21 14.5H3M17 19.5H3"/></svg>'],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M19 9.5H5M21 4.5H3M21 14.5H3M19 19.5H5"/></svg>'],
                    ['valor' => 'right', 'etiqueta' => 'Derecha', 'icon' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 9.5H7M21 4.5H3M21 14.5H3M21 19.5H7"/></svg>'],
                ])
                ->tab('Estilo')
        );

        // 7. Tipografía (con font-weight)
        $schema->addOption(
            Option::typography('typography', 'Tipografía')
                ->tab('Estilo')
        );

        // 8. Color de Fondo
        $schema->addOption(
            Option::color('backgroundColor', 'Fondo')
                ->allowTransparency()
                ->tab('Estilo')
                ->description('Sobrescribe el color de la clase CSS')
        );
        
        // 9. Color de Texto
        $schema->addOption(
            Option::color('color', 'Color de Texto')
                ->allowTransparency()
                ->tab('Estilo')
        );

        // 10. Spacing (Padding & Margin)
        foreach ($this->getSpacingOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }
        
        // 11. Border Radius
        $schema->addOption(
            Option::text('borderRadius', 'Radio de Borde')
                ->tab('Estilo')
                ->description('Ej: 4px, 8px, 50px')
        );

        // 12. Border
        $schema->addOption(
            Option::text('borderWidth', 'Ancho de Borde')
                ->default('')
                ->tab('Estilo')
                ->description('Ej: 1px, 2px')
        );

        $schema->addOption(
            Option::select('borderStyle', 'Estilo de Borde')
                ->options([
                    ['valor' => '', 'etiqueta' => 'Ninguno'],
                    ['valor' => 'solid', 'etiqueta' => 'Sólido'],
                    ['valor' => 'dashed', 'etiqueta' => 'Discontinuo'],
                    ['valor' => 'dotted', 'etiqueta' => 'Punteado'],
                ])
                ->tab('Estilo')
        );

        $schema->addOption(
            Option::color('borderColor', 'Color de Borde')
                ->allowTransparency()
                ->tab('Estilo')
        );

        // =====================================================
        // TAB: AVANZADO
        // =====================================================

        // 13. Cursor
        $schema->addOption(
            Option::select('cursor', 'Cursor')
                ->options([
                    'pointer' => 'Pointer (Mano)',
                    'default' => 'Default (Flecha)',
                    'not-allowed' => 'Not Allowed'
                ])
                ->default('pointer')
                ->tab('Avanzado')
        );

        // 14. Transition
        $schema->addOption(
            Option::text('transition', 'Transición')
                ->default('all 0.3s ease')
                ->tab('Avanzado')
                ->description('Ej: all 0.3s ease, transform 0.2s')
        );

        // 15. Transform (hover effect hint)
        $schema->addOption(
            Option::text('transform', 'Transform')
                ->default('')
                ->tab('Avanzado')
                ->description('Ej: skewX(-10deg), scale(1.05)')
        );

        // 16. Custom CSS
        $schema->addOption($this->getCustomCSSOption());

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="2" ry="2"></rect></svg>';
    }

    /**
     * Template mínimo para nuevos botones
     * No usa opciones=, todo se infiere de atributos nativos HTML
     */
    public function getTemplate(): string
    {
        return '<a href="#" gloryButton class="btn">Click aquí</a>';
    }
}
