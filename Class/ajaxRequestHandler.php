<?php

namespace Glory\Class; 

use Exception; // Use PHP's base Exception class

/**
 * Centralized AJAX Request Handler for the Glory Framework.
 *
 * Allows registering specific actions and their corresponding callbacks,
 * handling nonce verification and response formatting.
 */
class AjaxRequestHandler
{
    /** @var array Stores registered AJAX actions and their callbacks. */
    private static $actions = [];

    /** @var string Default nonce field name expected from the frontend. */
    private const NONCE_FIELD_NAME = '_ajax_nonce';

    /**
     * Register an AJAX action with its callback and security settings.
     *
     * Call this method for each AJAX action your framework/theme needs.
     * Typically called when setting up your services (e.g., inside Services/EmailFormBuilder.php).
     *
     * @param string   $action      The unique action name (matches JS 'action' parameter).
     * @param callable $callback    The function or static method to execute (e.g., [MyServiceClass::class, 'handleSignup']).
     * @param bool     $isPrivate   true = requires logged-in user (wp_ajax_), false = accessible to non-logged-in users (wp_ajax_nopriv_).
     * @param string   $nonceAction The specific nonce action string for verification. If empty, defaults to the $action name.
     */
    public static function registerAction(string $action, callable $callback, bool $isPrivate = false, string $nonceAction = ''): void
    {
        if (empty($action)) {
            error_log('Glory AjaxRequestHandler: Action name cannot be empty.');
            return;
        }
        if (!is_callable($callback)) {
            error_log("Glory AjaxRequestHandler: Callback for action '{$action}' is not callable.");
            return;
        }

        self::$actions[$action] = [
            'callback'     => $callback,
            'is_private'   => $isPrivate,
            'nonce_action' => !empty($nonceAction) ? $nonceAction : $action, // Default nonce action to action name
        ];
    }

    /**
     * Initializes the AJAX handling system by hooking into WordPress.
     *
     * Call this method once during your theme/plugin setup (e.g., in functions.php or main plugin file).
     */
    public static function initialize(): void
    {
        if (empty(self::$actions)) {
            return; // No actions registered
        }

        // The same handler method is used for all registered actions.
        // It will internally look up the correct callback based on the 'action' parameter.
        foreach (self::$actions as $action => $details) {
            // Hook for logged-in users (always available if registered)
            add_action('wp_ajax_' . $action, [self::class, 'handleRequest']);

            // Hook for non-logged-in users if $isPrivate is false
            if (!$details['is_private']) {
                add_action('wp_ajax_nopriv_' . $action, [self::class, 'handleRequest']);
            }
        }
    }

    /**
     * Central handler method hooked into wp_ajax_ actions.
     * Verifies nonce, finds the registered callback, executes it, and sends JSON response.
     * @internal Do not call directly. Triggered by WordPress AJAX hooks.
     */
    public static function handleRequest(): void
    {
        // 1. Get the action name from the request
        $action = $_REQUEST['action'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce check happens next based on action.

        if (!$action || !isset(self::$actions[$action])) {
            wp_send_json_error(['message' => 'Invalid AJAX action specified.'], 400); // Bad Request
            wp_die();
        }

        $actionDetails = self::$actions[$action];
        $nonceAction = $actionDetails['nonce_action'];
        $callback = $actionDetails['callback'];

        // 2. Verify Nonce - CRITICAL SECURITY STEP
        // check_ajax_referer sends a 403 response and dies if verification fails.
        check_ajax_referer($nonceAction, self::NONCE_FIELD_NAME);

        // 3. Execute the registered callback
        try {
            // Data is typically accessed directly from $_POST or $_GET within the callback function.
            // We could potentially pass $_POST data as an argument, but direct access is common in WP AJAX.
            $result = call_user_func($callback); // Execute the actual logic function/method

            // Callbacks should ideally return an array for wp_send_json_success
            // or throw an exception / return WP_Error for failure.
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()], 400); // Send specific error message
            } elseif ($result === false) {
                // Handle simple false return as a generic error if needed
                wp_send_json_error(['message' => 'An unspecified error occurred during processing.'], 500); // Internal Server Error
            } else {
                // Assume success if no error/false. $result contains the data payload.
                // If $result is null or void, send success with no extra data.
                wp_send_json_success($result); // Sends { success: true, data: $result }
            }
        } catch (Exception $e) {
            // Catch any uncaught exceptions from the callback
            error_log("Glory AjaxRequestHandler: Exception in action '{$action}': " . $e->getMessage());
            wp_send_json_error(['message' => 'An unexpected error occurred.'], 500); // Internal Server Error
        }

        wp_die(); // Required for AJAX handlers
    }
}
