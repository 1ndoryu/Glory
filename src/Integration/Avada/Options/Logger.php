<?php

namespace Glory\Integration\Avada\Options;

final class Logger
{
    public static function log(string $message, array $context = []): void
    {
        // Logging desactivado para evitar entradas en debug.log durante operación normal.
        // Si necesitas reactivar temporalmente, cambia a: if (!defined('WP_DEBUG') || !WP_DEBUG) { return; }
        return;
    }
}


