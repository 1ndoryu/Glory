<?php

namespace Glory\Services;

use Glory\Core\GloryLogger;

/**
 * EventBus simple basado en versiones por canal usando opciones de WP.
 *
 * Agnóstico al proyecto: se puede usar para invalidar vistas/listas cuando haya cambios.
 */
class EventBus
{
    /**
     * Incrementa la versión de un canal y guarda el timestamp del último cambio.
     */
    public static function emit(string $channel, mixed $payload = null): void
    {
        $channel = sanitize_key($channel);
        if ($channel === '') {
            return;
        }
        $versionKey = self::getVersionOptionKey($channel);
        $timeKey    = self::getTimeOptionKey($channel);

        $current = get_option($versionKey, 0);
        $next    = is_numeric($current) ? (intval($current) + 1) : 1;
        update_option($versionKey, $next, false);
        update_option($timeKey, time(), false);

        // Opcional: guardar último payload para diagnósticos (no usar para datos sensibles)
        if (!is_null($payload)) {
            $lastPayloadKey = self::getPayloadOptionKey($channel);
            // Almacenar como JSON compacto para evitar errores de serialización
            $encoded = wp_json_encode($payload);
            if (is_string($encoded)) {
                update_option($lastPayloadKey, $encoded, false);
            }
        }

        // Sin logs en producción; mantener silencioso
    }

    /**
     * Devuelve versiones para una lista de canales.
     * Formato: [ canal => [version => int, updatedAt => int] ]
     */
    public static function getVersions(array $channels): array
    {
        $result = [];
        foreach ($channels as $raw) {
            $channel = sanitize_key((string) $raw);
            if ($channel === '') {
                continue;
            }
            $version = intval(get_option(self::getVersionOptionKey($channel), 0));
            $updated = intval(get_option(self::getTimeOptionKey($channel), 0));
            $result[$channel] = [
                'version'   => $version,
                'updatedAt' => $updated,
            ];
        }
        return $result;
    }

    private static function getVersionOptionKey(string $channel): string
    {
        return 'glory_eventbus_version_' . $channel;
    }

    private static function getTimeOptionKey(string $channel): string
    {
        return 'glory_eventbus_time_' . $channel;
    }

    private static function getPayloadOptionKey(string $channel): string
    {
        return 'glory_eventbus_last_' . $channel;
    }
}


