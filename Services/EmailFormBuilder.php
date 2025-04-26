<?php

namespace Glory\Services; 

use Exception;
use WP_Error;

/**
 * Service class handling AJAX actions for the Email Signup component. 
 * Adapted to use native WordPress AJAX hooks.
 */
class EmailSignupService
{
    const ACTION_REGISTER = 'glory_register_email';
    const ACTION_UPDATE   = 'glory_update_user_details';
    const NONCE_ACTION    = 'glory_email_signup_action'; 

    /**
     * REGISTERS NATIVE AJAX HOOKS.
     * This function should now be called from your functions.php or main initializer.
     */
    public static function registerAjaxHooks(): void
    {
        add_action('wp_ajax_nopriv_' . self::ACTION_REGISTER, [self::class, 'handleRegistration']);
        add_action('wp_ajax_' . self::ACTION_REGISTER, [self::class, 'handleRegistration']);
        add_action('wp_ajax_nopriv_' . self::ACTION_UPDATE, [self::class, 'handleDetailsUpdate']);
        add_action('wp_ajax_' . self::ACTION_UPDATE, [self::class, 'handleDetailsUpdate']);
    }

    /**
     * Handles the initial email registration AJAX request. 
     * NOW verifies nonce and sends JSON response directly!
     */
    public static function handleRegistration()
    {
        try {
            // 1. VERIFY NONCE
            check_ajax_referer(self::NONCE_ACTION, '_ajax_nonce');

            // 2. Sanitize and Validate Email (comes from $_POST because we use 'application/x-www-form-urlencoded')
            if (!isset($_POST['email']) || !is_email($_POST['email'])) {
                error_log('Glory Service ERROR: handleRegistration() Invalid Email - ' . (isset($_POST['email']) ? $_POST['email'] : 'Email not received'));
                wp_send_json_error(['message' => 'Please provide a valid email address.'], 400); // Bad Request
                // wp_die(); // wp_send_json_* already includes die()
            }
            $email = sanitize_email($_POST['email']);

            // 3. Check if it exists
            if (email_exists($email)) {                
                error_log('Glory Service LOG: handleRegistration() Email already registered: ' . $email);
                wp_send_json_error(['message' => 'This email address is already registered.'], 409); // Conflict
                return;
            }
            
            // 4. Create user
            $password = wp_generate_password(12, true, true);
            $user_id = wp_create_user($email, $password, $email);

            // 5. Check for creation error
            if (is_int($user_id)) {
                
            }

            if (is_wp_error($user_id)) {
                error_log('Glory Service ERROR: handleRegistration() Error Creación: ' . $user_id->get_error_message());
                wp_send_json_error(['message' => 'Could not create account. Please try again later.'], 500); // Internal Server Error
                return;
            }

            // 6. SUCCESS: Send JSON success response
            wp_send_json_success(['userId' => $user_id]);
        } catch (Exception $e) {
            error_log('Glory Service LOG: handleRegistration() Exception: ' . $e->getMessage());
            wp_send_json_error(['message' => 'An unexpected error occurred.'], 500);
            return;
        }
    }

    /**
     * Handles the user details update AJAX request. 
     * NOW verifies nonce and sends JSON response directly!
     */
    public static function handleDetailsUpdate()
    {
        try {
            // 1. VERIFY NONCE (same nonce action)
            check_ajax_referer(self::NONCE_ACTION, '_ajax_nonce');

            // 2. Validate User ID
            if (!isset($_POST['user_id']) || !($userId = absint($_POST['user_id'])) || $userId === 0) {
                error_log('Glory Service ERROR: handleDetailsUpdate() User ID no válido');
                wp_send_json_error(['message' => 'Invalid user identifier.'], 400);
            }

            // 3. Verificar si el usuario existe
            if (!get_userdata($userId)) {
                error_log('Glory Service ERROR: handleDetailsUpdate() Usuario no encontrado: ' . $userId);
                wp_send_json_error(['message' => 'User not found.'], 404);
            }
            
            // 4. Sanitize names.
            $firstName = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
            $lastName = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';

            // 5. Update User Meta.
            update_user_meta($userId, 'first_name', $firstName);
            update_user_meta($userId, 'last_name', $lastName);

            // 6. SUCCESS: Send JSON success response
            wp_send_json_success(['message' => 'Profile updated successfully!']);

        } catch (Exception $e) {
            error_log('Glory Service LOG: handleDetailsUpdate() Exception: ' . $e->getMessage());
            wp_send_json_error(['message' => 'An unexpected error occurred.'], 500);
        }
    }
}

EmailSignupService::registerAjaxHooks();
