<?php

namespace Glory\Integration;

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
    public static function avadaActivo(): bool
    {
        return defined('AVADA_VERSION');
    }
}
