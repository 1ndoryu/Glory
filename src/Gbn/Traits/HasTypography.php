<?php
namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;
use Glory\Gbn\Icons\IconRegistry;

trait HasTypography {
    /**
     * Retorna las opciones estándar de tipografía.
     * @return Option[]
     */
    protected function getTypographyOptions(string $tab = 'Estilo'): array {
        return [
            Option::typography('typography', 'Tipografía')
                ->tab($tab),
            
            Option::color('color', 'Color de texto')
                ->tab($tab),
            
            Option::iconGroup('textAlign', 'Alineación')
                ->options(IconRegistry::getGroup([
                    'text.align.left' => ['valor' => 'left', 'etiqueta' => 'Izquierda'],
                    'text.align.center' => ['valor' => 'center', 'etiqueta' => 'Centro'],
                    'text.align.right' => ['valor' => 'right', 'etiqueta' => 'Derecha'],
                    'text.align.justify' => ['valor' => 'justify', 'etiqueta' => 'Justificado']
                ]))
                ->tab($tab)
        ];
    }

    /**
     * Agrega opciones de tipografía al SchemaBuilder.
     * 
     * @param \Glory\Gbn\Schema\SchemaBuilder $builder
     * @param string $tab
     */
    protected function addTypographyOptions($builder, string $tab = 'Estilo'): void {
        foreach ($this->getTypographyOptions($tab) as $option) {
            $builder->addOption($option);
        }
    }
}
