<?php

namespace Glory\Gbn\Components\Tarjeta;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Schema\SchemaConstants;
use Glory\Gbn\Traits\HasFlexbox;
use Glory\Gbn\Traits\HasGrid;
use Glory\Gbn\Traits\HasSpacing;
use Glory\Gbn\Traits\HasBackground;
use Glory\Gbn\Traits\HasCustomCSS;
use Glory\Gbn\Traits\HasPositioning;

/**
 * TarjetaComponent - Componente para tarjetas con imagen de fondo
 * 
 * Unifica la estructura de las service-cards en un solo componente editable.
 * Mantiene la estructura HTML compatible con el CSS existente de .service-card
 * 
 * Uso en HTML:
 * <div gloryTarjeta class="service-card card-dark">
 *     <div gloryTexto class="card-content">
 *         <h3>Titulo</h3>
 *     </div>
 * </div>
 * 
 * La imagen de fondo se configura desde el panel y se aplica al pseudo-elemento
 * .card-bg-image interno que el componente genera automaticamente.
 * 
 * @role tarjeta
 * @selector [gloryTarjeta]
 */
class TarjetaComponent extends AbstractComponent
{
    use HasFlexbox, HasGrid, HasSpacing, HasBackground, HasCustomCSS, HasPositioning;

    protected string $id = 'tarjeta';
    protected string $label = 'Tarjeta';

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryTarjeta',
            'dataAttribute' => 'data-gbnTarjeta',
        ];
    }

    public function getDefaults(): array
    {
        return [
            SchemaConstants::FIELD_LAYOUT => 'block',
            SchemaConstants::FIELD_FLEX_DIRECTION => 'row',
            SchemaConstants::FIELD_FLEX_WRAP => 'nowrap',
            SchemaConstants::FIELD_JUSTIFY => 'flex-start',
            SchemaConstants::FIELD_ALIGN => 'stretch',
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // Tab: Contenido - Imagen de fondo de la tarjeta
        $schema->addOption(
            Option::image('cardBackgroundImage', 'Imagen de Fondo')
                ->tab('Contenido')
                ->description('Imagen que aparece como fondo de la tarjeta')
        );

        // Layout & Flexbox (from Trait) - Tab: Contenido
        foreach ($this->getFlexboxOptions() as $option) {
            $option->tab('Contenido');
            $schema->addOption($option);
        }

        // Grid (from Trait) - Tab: Contenido
        foreach ($this->getGridOptions() as $option) {
            $option->tab('Contenido');
            $schema->addOption($option);
        }

        // Ancho (Fraction) - Tab: Estilo
        $schema->addOption(
            Option::fraction('width', 'Ancho')
                ->options([
                    '1/1',
                    '5/6',
                    '4/5',
                    '3/4',
                    '2/3',
                    '3/5',
                    '1/2',
                    '2/5',
                    '1/3',
                    '1/4',
                    '1/5',
                    '1/6'
                ])
                ->tab('Estilo')
        );

        // Altura (Smart Control) - Tab: Estilo
        $schema->addOption(
            Option::text('height', 'Altura')
                ->default('auto')
                ->tab('Estilo')
                ->description('Ej: auto, 100vh, 500px')
        );

        // Ancho Maximo - Tab: Estilo
        $schema->addOption(
            Option::text('maxAncho', 'Ancho maximo')
                ->default('')
                ->tab('Estilo')
                ->description('Ej: 1200px, 100%')
        );

        // Spacing (Padding & Margin) - Tab: Estilo
        $schema->addOption(
            Option::spacing('padding', 'Padding Interno (Auto)')
                ->units(['px', '%', 'rem'])
                ->step(4)
                ->min(0)
                ->max(160)
                ->fields(['superior', 'derecha', 'inferior', 'izquierda'])
                ->tab('Estilo')
        );

        $schema->addOption(
            Option::spacing('margin', 'Margen')
                ->units(['px', '%', 'rem'])
                ->step(4)
                ->min(-100)
                ->max(160)
                ->fields(['superior', 'derecha', 'inferior', 'izquierda'])
                ->tab('Estilo')
        );

        // Fondo - Tab: Estilo (color de overlay/fondo base)
        $schema->addOption(
            Option::color('fondo', 'Color de fondo')
                ->allowTransparency()
                ->tab('Estilo')
        );

        // Background Image Options (from Trait) - Tab: Estilo
        // Nota: Estas opciones son para el fondo del contenedor, no la imagen de la tarjeta
        foreach ($this->getBackgroundOptions() as $option) {
            $option->tab('Estilo');
            $schema->addOption($option);
        }

        // Positioning (from Trait) - Tab: Avanzado
        foreach ($this->getPositioningOptions() as $option) {
            $schema->addOption($option);
        }

        // Custom CSS - Tab: Avanzado
        $schema->addOption($this->getCustomCSSOption());

        return $schema->toArray();
    }

    public function getIcon(): string
    {
        return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><path d="M9 21V9"></path></svg>';
    }

    public function getTemplate(): string
    {
        return '<div gloryTarjeta class="tarjeta"><div class="card-content"></div><div class="card-bg-image"></div></div>';
    }

    /**
     * Tarjeta es un contenedor que acepta componentes internos
     * como texto, botones, imagenes, etc.
     * 
     * @return array<string>
     */
    public function getAllowedChildren(): array
    {
        return ['secundario', 'text', 'image', 'button'];
    }
}
