<?php

namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;
use Glory\Gbn\Icons\IconRegistry;

trait HasBackground
{
    public function getBackgroundOptions(): array
    {
        return [
            // Imagen de Fondo
            Option::image('backgroundImage', 'Imagen de Fondo')
                ->description('URL de la imagen de fondo'),

            // Tamaño de Fondo (Condicional + Iconos)
            Option::iconGroup('backgroundSize', 'Tamaño de Fondo')
                ->options(IconRegistry::getGroup([
                    'bg.size.cover' => ['valor' => 'cover', 'etiqueta' => 'Cover'],
                    'bg.size.contain' => ['valor' => 'contain', 'etiqueta' => 'Contain'],
                    'bg.size.auto' => ['valor' => 'auto', 'etiqueta' => 'Auto'],
                    'bg.size.stretch' => ['valor' => '100% 100%', 'etiqueta' => 'Stretch']
                ]))
                ->default('cover')
                ->condition(['backgroundImage', '!=', '']),

            // Posición de Fondo (Condicional + Iconos)
            Option::iconGroup('backgroundPosition', 'Posición de Fondo')
                ->options(IconRegistry::getGroup([
                    'bg.pos.top-left' => ['valor' => 'top left', 'etiqueta' => 'Arriba Izquierda'],
                    'bg.pos.top-center' => ['valor' => 'top center', 'etiqueta' => 'Arriba Centro'],
                    'bg.pos.top-right' => ['valor' => 'top right', 'etiqueta' => 'Arriba Derecha'],
                    'bg.pos.left-center' => ['valor' => 'left center', 'etiqueta' => 'Izquierda Centro'],
                    'bg.pos.center-center' => ['valor' => 'center center', 'etiqueta' => 'Centro'],
                    'bg.pos.right-center' => ['valor' => 'right center', 'etiqueta' => 'Derecha Centro'],
                    'bg.pos.bottom-left' => ['valor' => 'bottom left', 'etiqueta' => 'Abajo Izquierda'],
                    'bg.pos.bottom-center' => ['valor' => 'bottom center', 'etiqueta' => 'Abajo Centro'],
                    'bg.pos.bottom-right' => ['valor' => 'bottom right', 'etiqueta' => 'Abajo Derecha'],
                ]))
                ->default('center center')
                ->condition(['backgroundImage', '!=', '']),

            // Repetición (Condicional + Iconos)
            Option::iconGroup('backgroundRepeat', 'Repetición')
                ->options(IconRegistry::getGroup([
                    'bg.repeat.no-repeat' => ['valor' => 'no-repeat', 'etiqueta' => 'No Repetir'],
                    'bg.repeat.repeat' => ['valor' => 'repeat', 'etiqueta' => 'Repetir'],
                    'bg.repeat.repeat-x' => ['valor' => 'repeat-x', 'etiqueta' => 'Repetir X'],
                    'bg.repeat.repeat-y' => ['valor' => 'repeat-y', 'etiqueta' => 'Repetir Y'],
                ]))
                ->default('no-repeat')
                ->condition(['backgroundImage', '!=', '']),

            // Attachment (Condicional + Iconos)
            Option::iconGroup('backgroundAttachment', 'Fijación (Efecto)')
                ->options(IconRegistry::getGroup([
                    'bg.attach.scroll' => ['valor' => 'scroll', 'etiqueta' => 'Scroll'],
                    'bg.attach.fixed' => ['valor' => 'fixed', 'etiqueta' => 'Fijo'],
                    'bg.attach.local' => ['valor' => 'local', 'etiqueta' => 'Local'],
                ]))
                ->default('scroll')
                ->condition(['backgroundImage', '!=', '']),
        ];
    }
}
