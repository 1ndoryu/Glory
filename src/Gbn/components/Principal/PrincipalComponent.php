<?php
namespace Glory\Gbn\Components\Principal;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use Glory\Gbn\Traits\HasFlexbox;
use Glory\Gbn\Traits\HasGrid;
use Glory\Gbn\Traits\HasSpacing;

class PrincipalComponent extends AbstractComponent {
    use HasFlexbox, HasGrid, HasSpacing;

    protected string $id = 'principal';
    protected string $label = 'Contenedor Principal';

    public function getSelector(): array {
        return [
            'attribute' => 'gloryDiv',
            'dataAttribute' => 'data-gbnPrincipal',
        ];
    }

    public function getDefaults(): array {
        return [
            'layout' => 'flex',
            'flexDirection' => 'row',
            'flexWrap' => 'wrap',
            'flexJustify' => 'flex-start',
            'flexAlign' => 'stretch',
            'maxAncho' => '1200px',
        ];
    }

    public function getSchema(): array {
        $schema = SchemaBuilder::create();

        // 1. Altura
        $schema->addOption(
            Option::select('height', 'Altura')
                ->options([
                    ['valor' => 'auto', 'etiqueta' => 'Automática'],
                    ['valor' => 'min-content', 'etiqueta' => 'Mínima'],
                    ['valor' => '100vh', 'etiqueta' => 'Altura completa'],
                ])
        );

        // 2. Padding (from Trait)
        foreach ($this->getSpacingOptions() as $option) {
            // Solo queremos padding para Principal por ahora, según ContainerRegistry
            if ($option->toArray()['id'] === 'padding') {
                $schema->addOption($option);
            }
        }

        // 3. Alineación
        $schema->addOption(
            Option::iconGroup('alineacion', 'Alineación del contenido')
                ->options([
                    ['valor' => 'inherit', 'etiqueta' => 'Hereda', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/></svg>'],
                    ['valor' => 'left', 'etiqueta' => 'Izquierda', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M4 12h10"/><path d="M4 18h12"/></svg>'],
                    ['valor' => 'center', 'etiqueta' => 'Centro', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M7 12h10"/><path d="M6 18h12"/></svg>'],
                    ['valor' => 'right', 'etiqueta' => 'Derecha', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M10 12h10"/><path d="M8 18h12"/></svg>'],
                ])
        );

        // 4. Ancho Máximo
        $schema->addOption(
            Option::text('maxAncho', 'Ancho máximo')
                ->default('1200px')
        );

        // 5. Fondo
        $schema->addOption(
            Option::color('fondo', 'Color de fondo')
                ->allowTransparency()
        );

        // 6. Layout & Flexbox (from Trait)
        // HasFlexbox incluye 'gap' al final, pero en ContainerRegistry 'gap' está antes de layout?
        // En ContainerRegistry: gap, layout, flexDirection...
        // En HasFlexbox: layout, flexDirection..., gap.
        // El orden importa para la UI.
        // Voy a añadir las opciones manualmente para respetar el orden exacto si es crítico, 
        // o confiar en el trait si el orden no es estricto.
        // ContainerRegistry orden: gap (cond flex), layout, flexDir...
        // Espera, en ContainerRegistry 'gap' está ANTES de 'layout'??
        // Line 73: gap. Line 83: layout.
        // Pero gap tiene condicion ['layout', 'flex']. Si layout está después, ¿cómo funciona la condición inicial?
        // Probablemente el JS maneja la reactividad sin importar el orden.
        // Usaré el trait HasFlexbox que pone layout primero, lo cual tiene más sentido lógico (definir layout antes de sus opciones).
        
        foreach ($this->getFlexboxOptions() as $option) {
             $schema->addOption($option);
        }

        // 7. Grid (from Trait)
        foreach ($this->getGridOptions() as $option) {
            $schema->addOption($option);
        }

        return $schema->toArray();
    }
}
