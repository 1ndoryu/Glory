<?php

namespace Glory\Handler;

use Glory\Services\EventBus;
use Glory\Core\GloryLogger;

class RealtimeAjaxHandler
{
    public function __construct()
    {
        add_action('wp_ajax_glory_realtime_versions', [$this, 'getVersions']);
        add_action('wp_ajax_nopriv_glory_realtime_versions', [$this, 'getVersions']);
    }

    public function getVersions(): void
    {
        $channels = isset($_POST['channels']) ? (array) $_POST['channels'] : [];
        $channels = array_values(array_filter(array_map('sanitize_key', $channels)));
        if (empty($channels)) {
            wp_send_json_success(['channels' => []]);
            return;
        }
        $versions = EventBus::getVersions($channels);

        // Incluir, cuando exista, el último payload almacenado por canal
        foreach ($versions as $ch => &$info) {
            $payloadJson = get_option('glory_eventbus_last_' . $ch, '');
            if (is_string($payloadJson) && $payloadJson !== '') {
                $decoded = json_decode($payloadJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $info['payload'] = $decoded;
                }
            }
        }
        unset($info);

        wp_send_json_success(['channels' => $versions]);
    }
}


