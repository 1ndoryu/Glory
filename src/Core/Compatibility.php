<?php

namespace Glory\Core;

/**
 * Gestiona las comprobaciones de compatibilidad con otros temas o plugins.
 */
class Compatibility
{
    /**
     * Comprueba si el tema Avada está activo.
     *
     * @return bool Verdadero si la constante AVADA_VERSION está definida, falso en caso contrario.
     */
    public static function is_avada_active(): bool
    {
        return defined('AVADA_VERSION');
    }
}
