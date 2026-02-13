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
    /* Buffer en memoria para agrupar emisiones y flushear en shutdown */
    private static array $pendingEmits = [];
    private static bool $shutdownRegistered = false;

    /**
     * Acumula la emisión en memoria; se persiste en shutdown para evitar múltiples get/update_option.
     */
    public static function emit(string $channel, mixed $payload = null): void
    {
        $channel = sanitize_key($channel);
        if ($channel === '') {
            return;
        }

        if (!isset(self::$pendingEmits[$channel])) {
            self::$pendingEmits[$channel] = ['count' => 1, 'payload' => $payload];
        } else {
            self::$pendingEmits[$channel]['count']++;
            self::$pendingEmits[$channel]['payload'] = $payload;
        }

        if (!self::$shutdownRegistered) {
            add_action('shutdown', [self::class, 'flushPendingEmits'], 5);
            self::$shutdownRegistered = true;
        }
    }

    /**
     * Persiste todas las emisiones acumuladas al final del request.
     */
    public static function flushPendingEmits(): void
    {
        foreach (self::$pendingEmits as $channel => $data) {
            $versionKey = self::getVersionOptionKey($channel);
            $timeKey    = self::getTimeOptionKey($channel);

            $current = get_option($versionKey, 0);
            $next    = is_numeric($current) ? (intval($current) + $data['count']) : $data['count'];
            update_option($versionKey, $next, false);
            update_option($timeKey, time(), false);

            if (!is_null($data['payload'])) {
                $lastPayloadKey = self::getPayloadOptionKey($channel);
                $encoded = wp_json_encode($data['payload']);
                if (is_string($encoded)) {
                    update_option($lastPayloadKey, $encoded, false);
                }
            }
        }
        self::$pendingEmits = [];
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


