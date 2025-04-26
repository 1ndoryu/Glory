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
    // Action names for the AJAX endpoints
    const ACTION_REGISTER = 'glory_register_email';
    const ACTION_UPDATE   = 'glory_update_user_details';

    // Nonce action strings (MUST MATCH the ones used in the forms)
    const NONCE_ACTION_REGISTER = 'glory_email_signup_action';        // Used by EmailFormBuilder
    const NONCE_ACTION_UPDATE   = 'glory_update_user_details_nonce'; // *** Used by FormModalBuilder for the profile update ***

    /**
     * Registers native AJAX hooks.
     * This function should now be called from your functions.php or main initializer.
     */
    public static function registerAjaxHooks(): void
    {
        // Hook for initial email registration (allows non-logged-in users)
        add_action('wp_ajax_nopriv_' . self::ACTION_REGISTER, [self::class, 'handleRegistration']);
        add_action('wp_ajax_'        . self::ACTION_REGISTER, [self::class, 'handleRegistration']);
        add_action('wp_ajax_nopriv_' . self::ACTION_UPDATE, [self::class, 'handleDetailsUpdate']);
        add_action('wp_ajax_'        . self::ACTION_UPDATE, [self::class, 'handleDetailsUpdate']); 
    }

    /**
     * Handles the initial email registration AJAX request.
     * Verifies nonce and sends JSON response directly.
     */
    public static function handleRegistration()
    {
        try {
            // 1. VERIFY NONCE for the registration form
            check_ajax_referer(self::NONCE_ACTION_REGISTER, '_ajax_nonce'); // Use the registration nonce

            // 2. Sanitize and Validate Email
            if (!isset($_POST['email']) || !is_email($_POST['email'])) {
                error_log('Glory Service ERROR: handleRegistration() Invalid Email - ' . (isset($_POST['email']) ? sanitize_text_field($_POST['email']) : 'Email not received'));
                wp_send_json_error(['message' => 'Please provide a valid email address.'], 400);
            }
            $email = sanitize_email($_POST['email']);

            // 3. Check if email exists
            if (email_exists($email)) {
                error_log('Glory Service LOG: handleRegistration() Email already registered: ' . $email);
                wp_send_json_error(['message' => 'This email address is already registered.'], 409); // Conflict
            }

            // 4. Create user
            $password = wp_generate_password(12, true, true);
            $user_id = wp_create_user($email, $password, $email);

            // 5. Check for creation error
            if (is_wp_error($user_id)) {
                error_log('Glory Service ERROR: handleRegistration() Error Creación: ' . $user_id->get_error_message());
                wp_send_json_error(['message' => 'Could not create account. Please try again later.'], 500);
            }

            // 6. SUCCESS: Send JSON success response with the new user ID
            // The JS (GloryEmailSignup) needs this userId to populate the modal form
            wp_send_json_success(['userId' => $user_id]);
        } catch (Exception $e) {
            // Catch potential exceptions from check_ajax_referer if nonce fails severely
            error_log('Glory Service LOG: handleRegistration() Exception: ' . $e->getMessage());
            wp_send_json_error(['message' => 'An unexpected error occurred during registration.'], 500);
        }
        // wp_send_json_* functions call wp_die() implicitly, so no need for it here.
    }

    /**
     * Handles the user details update AJAX request.
     * Verifies nonce and sends JSON response directly.
     */
    public static function handleDetailsUpdate()
    {
        try {
            // 1. VERIFY NONCE for the user details update form
            // *** USE THE CORRECT NONCE ACTION FOR THIS HANDLER ***
            check_ajax_referer(self::NONCE_ACTION_UPDATE, '_ajax_nonce');

            // 2. Validate User ID (sent from the modal form's hidden field)
            if (!isset($_POST['user_id']) || !($userId = absint($_POST['user_id'])) || $userId === 0) {
                error_log('Glory Service ERROR: handleDetailsUpdate() User ID no válido: ' . print_r($_POST, true));
                wp_send_json_error(['message' => 'Invalid user identifier.'], 400);
            }

            // 3. Verify user exists
            if (!get_userdata($userId)) {
                error_log('Glory Service ERROR: handleDetailsUpdate() Usuario no encontrado: ' . $userId);
                wp_send_json_error(['message' => 'User not found.'], 404);
            }

            // 4. Sanitize names from the modal form
            $firstName = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
            $lastName = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';

            // Basic validation: Ensure required fields (like first_name) are not empty if needed
            if (empty($firstName)) {
                error_log('Glory Service ERROR: handleDetailsUpdate() First name missing for user: ' . $userId);
                wp_send_json_error(['message' => 'First name is required.'], 400);
            }


            // 5. Update User Meta
            update_user_meta($userId, 'first_name', $firstName);
            update_user_meta($userId, 'last_name', $lastName);
            // Consider updating display_name as well?
            // wp_update_user(['ID' => $userId, 'display_name' => trim($firstName . ' ' . $lastName)]);

            // 6. SUCCESS: Send JSON success response with a confirmation message
            // This message will be used by the 'glory.modalForm.success' event listener in GloryEmailSignup.js
            wp_send_json_success(['message' => 'Profile updated successfully!']);
        } catch (Exception $e) {
            // Catch potential exceptions
            error_log('Glory Service LOG: handleDetailsUpdate() Exception: ' . $e->getMessage());
            wp_send_json_error(['message' => 'An unexpected error occurred while updating details.'], 500);
        }
        // wp_send_json_* functions call wp_die() implicitly.
    }
}

EmailSignupService::registerAjaxHooks();
