<?php

namespace Glory\Integration\Avada;

/**
 * Orquestador para el registro de elementos de Avada.
 */
class AvadaElementRegistrar
{
    public static function register(): void
    {
        if ( ! function_exists('add_action') ) {
            return;
        }
        $gloryCrRegistrar = '\\Glory\\Integration\\Avada\\Elements\\GloryContentRender\\GloryContentRenderRegistrar';
        if ( class_exists($gloryCrRegistrar) ) {
            $gloryCrRegistrar::register();
        }
        $gloryImgRegistrar = '\\Glory\\Integration\\Avada\\Elements\\GloryImage\\GloryImageRegistrar';
        if ( class_exists($gloryImgRegistrar) ) {
            $gloryImgRegistrar::register();
        }
    }
}
