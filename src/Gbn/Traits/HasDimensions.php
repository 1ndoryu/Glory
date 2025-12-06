<?php
namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;

trait HasDimensions
{
    /**
     * Obtiene las opciones de dimensiones (Ancho, Alto, Max-Width, Max-Height).
     * 
     * @param string $tab Pestaña donde mostrar las opciones
     * @return Option[]
     */
    protected function getDimensionsOptions(string $tab = 'Estilo'): array
    {
        return [
            Option::dimensions('dimensions', 'Dimensiones')
                ->tab($tab)
                ->description('Ancho, Alto, Máx Ancho, Máx Alto')
        ];
    }

    /**
     * Agrega opciones de dimensiones al SchemaBuilder.
     * 
     * @param \Glory\Gbn\Schema\SchemaBuilder $builder
     * @param string $tab
     */
    protected function addDimensionsOptions($builder, string $tab = 'Estilo'): void
    {
        foreach ($this->getDimensionsOptions($tab) as $option) {
            $builder->addOption($option);
        }
    }
}
