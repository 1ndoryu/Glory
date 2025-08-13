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
        wp_send_json_success(['channels' => $versions]);
    }
}


