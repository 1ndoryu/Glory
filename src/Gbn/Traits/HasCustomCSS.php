<?php

namespace Glory\Gbn\Traits;

use Glory\Gbn\Schema\Option;

trait HasCustomCSS
{
    protected function getCustomCSSOption(): Option
    {
        return Option::code('custom_css', 'CSS Personalizado')
            ->tab('Avanzado')
            ->default('')
            ->description('Escribe CSS válido. Usa & para referirte al selector del componente.');
    }
}
