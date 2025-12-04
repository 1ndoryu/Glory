<?php

namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;

trait HasBackground
{
    public function getBackgroundOptions(): array
    {
        return [
            // Imagen de Fondo
            Option::image('backgroundImage', 'Imagen de Fondo')
                ->description('URL de la imagen de fondo'),

            // Tamaño de Fondo
            Option::select('backgroundSize', 'Tamaño de Fondo')
                ->options([
                    'cover' => 'Cubrir (Cover)',
                    'contain' => 'Contener (Contain)',
                    'auto' => 'Automático',
                    '100% 100%' => 'Estirar (100% 100%)'
                ])
                ->default('cover'),

            // Posición de Fondo
            Option::select('backgroundPosition', 'Posición de Fondo')
                ->options([
                    'center center' => 'Centro Centro',
                    'top center' => 'Arriba Centro',
                    'bottom center' => 'Abajo Centro',
                    'left center' => 'Izquierda Centro',
                    'right center' => 'Derecha Centro',
                    'top left' => 'Arriba Izquierda',
                    'top right' => 'Arriba Derecha',
                    'bottom left' => 'Abajo Izquierda',
                    'bottom right' => 'Abajo Derecha'
                ])
                ->default('center center'),

            // Repetición
            Option::select('backgroundRepeat', 'Repetición')
                ->options([
                    'no-repeat' => 'No Repetir',
                    'repeat' => 'Repetir (Mosaico)',
                    'repeat-x' => 'Repetir Horizontal',
                    'repeat-y' => 'Repetir Vertical'
                ])
                ->default('no-repeat'),

            // Attachment (Parallax simple)
            Option::select('backgroundAttachment', 'Fijación (Efecto)')
                ->options([
                    'scroll' => 'Normal (Scroll)',
                    'fixed' => 'Fijo (Parallax)',
                    'local' => 'Local'
                ])
                ->default('scroll'),
                
            // Color de Superposición (Overlay) - Opcional, requiere soporte en JS para pseudo-elemento o gradiente
            // Por ahora usaremos solo color de fondo base que ya existe en los componentes.
        ];
    }
}
