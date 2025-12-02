<?php
namespace Glory\Gbn\Ajax\Handlers;

use Glory\Gbn\GbnManager;

class LoggerHandler {

    /**
     * Handle client log submission
     */
    public static function handle() {
        // Verify nonce for security
        if (!check_ajax_referer('glory_gbn_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        $logs = isset($_POST['logs']) ? $_POST['logs'] : [];
        if (empty($logs) || !is_array($logs)) {
            wp_send_json_success(['message' => 'No logs to save']);
        }

        // Define log directory
        // Changed to theme directory as per request
        $log_dir = get_template_directory() . '/Glory/src/Gbn/logs';

        // Create directory if not exists
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            // .gitignore is already created manually, but we can ensure it exists or just rely on the repo
        }

        // Define log file for today
        $date = date('Y-m-d');
        $log_file = $log_dir . "/client-{$date}.log";

        // Format logs
        $content = '';
        foreach ($logs as $log) {
            $timestamp = isset($log['timestamp']) ? $log['timestamp'] : date('H:i:s');
            $level = isset($log['level']) ? strtoupper($log['level']) : 'INFO';
            $message = isset($log['message']) ? $log['message'] : '';
            $context = isset($log['context']) ? json_encode($log['context']) : '';
            
            // Format: [TIME] [LEVEL] Message {Context}
            $content .= "[{$timestamp}] [{$level}] {$message} {$context}" . PHP_EOL;
        }

        // Append to file
        // Use LOCK_EX to prevent race conditions
        file_put_contents($log_file, $content, FILE_APPEND | LOCK_EX);

        wp_send_json_success(['message' => 'Logs saved', 'count' => count($logs)]);
    }
}
