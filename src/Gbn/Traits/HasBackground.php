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

            // Tamaño de Fondo (Condicional + Iconos)
            Option::iconGroup('backgroundSize', 'Tamaño de Fondo')
                ->options([
                    ['valor' => 'cover', 'etiqueta' => 'Cover', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 3l18 18M21 3L3 21" stroke-opacity="0.2"></path></svg>'],
                    ['valor' => 'contain', 'etiqueta' => 'Contain', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="6" y="6" width="12" height="12" rx="1"></rect><rect x="3" y="3" width="18" height="18" rx="2" stroke-opacity="0.3"></rect></svg>'],
                    ['valor' => 'auto', 'etiqueta' => 'Auto', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="3"></circle><rect x="3" y="3" width="18" height="18" rx="2" stroke-opacity="0.3"></rect></svg>'],
                    ['valor' => '100% 100%', 'etiqueta' => 'Stretch', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M3 3h18v18H3z"></path><path d="M7 12h10M12 7v10"></path></svg>']
                ])
                ->default('cover')
                ->condition(['backgroundImage', '!=', '']),

            // Posición de Fondo (Condicional + Iconos)
            Option::iconGroup('backgroundPosition', 'Posición de Fondo')
                ->options([
                    ['valor' => 'top left', 'etiqueta' => 'Arriba Izquierda', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="6" cy="6" r="2"/></svg>'],
                    ['valor' => 'top center', 'etiqueta' => 'Arriba Centro', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="6" r="2"/></svg>'],
                    ['valor' => 'top right', 'etiqueta' => 'Arriba Derecha', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="18" cy="6" r="2"/></svg>'],
                    ['valor' => 'left center', 'etiqueta' => 'Izquierda Centro', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="6" cy="12" r="2"/></svg>'],
                    ['valor' => 'center center', 'etiqueta' => 'Centro', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="2"/></svg>'],
                    ['valor' => 'right center', 'etiqueta' => 'Derecha Centro', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="18" cy="12" r="2"/></svg>'],
                    ['valor' => 'bottom left', 'etiqueta' => 'Abajo Izquierda', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="6" cy="18" r="2"/></svg>'],
                    ['valor' => 'bottom center', 'etiqueta' => 'Abajo Centro', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="18" r="2"/></svg>'],
                    ['valor' => 'bottom right', 'etiqueta' => 'Abajo Derecha', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="18" cy="18" r="2"/></svg>'],
                ])
                ->default('center center')
                ->condition(['backgroundImage', '!=', '']),

            // Repetición (Condicional + Iconos)
            Option::iconGroup('backgroundRepeat', 'Repetición')
                ->options([
                    ['valor' => 'no-repeat', 'etiqueta' => 'No Repetir', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="12" cy="12" r="2" fill="currentColor"></circle></svg>'],
                    ['valor' => 'repeat', 'etiqueta' => 'Repetir', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="2" y="2" width="8" height="8" rx="1"></rect><rect x="14" y="2" width="8" height="8" rx="1"></rect><rect x="2" y="14" width="8" height="8" rx="1"></rect><rect x="14" y="14" width="8" height="8" rx="1"></rect></svg>'],
                    ['valor' => 'repeat-x', 'etiqueta' => 'Repetir X', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="2" y="8" width="8" height="8" rx="1"></rect><rect x="14" y="8" width="8" height="8" rx="1"></rect><path d="M12 4v16" stroke-dasharray="2 2" stroke-opacity="0.5"></path></svg>'],
                    ['valor' => 'repeat-y', 'etiqueta' => 'Repetir Y', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="8" y="2" width="8" height="8" rx="1"></rect><rect x="8" y="14" width="8" height="8" rx="1"></rect><path d="M4 12h16" stroke-dasharray="2 2" stroke-opacity="0.5"></path></svg>']
                ])
                ->default('no-repeat')
                ->condition(['backgroundImage', '!=', '']),

            // Attachment (Condicional + Iconos)
            Option::iconGroup('backgroundAttachment', 'Fijación (Efecto)')
                ->options([
                    ['valor' => 'scroll', 'etiqueta' => 'Scroll', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M12 8v4"></path><path d="M12 16v.01"></path></svg>'],
                    ['valor' => 'fixed', 'etiqueta' => 'Fijo', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 2L12 22"></path><path d="M2 12L22 12"></path><circle cx="12" cy="12" r="3"></circle></svg>'],
                    ['valor' => 'local', 'etiqueta' => 'Local', 'icon' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M8 12h8"></path><path d="M12 8v8"></path></svg>']
                ])
                ->default('scroll')
                ->condition(['backgroundImage', '!=', '']),
        ];
    }
}
