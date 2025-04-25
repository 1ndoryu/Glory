<?php
# App/Glory/Services/EmailSignupService.php

namespace Glory\Services; // Asegúrate que el namespace coincida con tu estructura

// Importa las clases necesarias
use Glory\Class\AjaxRequestHandler;
use WP_Error;                     

/**
 * Service class handling AJAX actions for the Email Signup component.
 * Contains the logic previously in global functions.
 */
class EmailSignupService
{
    // --- Constants for action names and nonce ---
    // Es buena práctica definir constantes para evitar errores tipográficos
    const ACTION_REGISTER = 'glory_register_email';
    const ACTION_UPDATE   = 'glory_update_user_details';
    // Usa un nombre de acción nonce específico y descriptivo
    const NONCE_ACTION    = 'glory_email_signup_action';

    /**
     * Registers the AJAX actions with the central handler.
     * Call this method once during initialization (e.g., from functions.php or the main plugin file).
     */
    public static function registerAjaxActions(): void
    {
        // Register the email registration action
        AjaxRequestHandler::registerAction(
            self::ACTION_REGISTER,              // The action name (string)
            [self::class, 'handleRegistration'], // The static callback method in *this* class
            false,                              // isPrivate = false (accessible to logged-out users)
            self::NONCE_ACTION                  // The nonce action name to verify
        );

        // Register the user details update action
        AjaxRequestHandler::registerAction(
            self::ACTION_UPDATE,
            [self::class, 'handleDetailsUpdate'],
            false, // Also accessible to logged-out users
            self::NONCE_ACTION // Use the same nonce for the whole signup flow
        );
    }

    /**
     * Handles the initial email registration AJAX request.
     * This method is called automatically by AjaxRequestHandler after nonce verification.
     * It should return data on success or a WP_Error object on failure.
     *
     * @internal Should only be called via AjaxRequestHandler.
     * @return array|WP_Error Data array (e.g., ['userId' => $id]) on success, WP_Error on failure.
     */
    public static function handleRegistration() // Removed wp_die() and wp_send_json_* calls
    {
        // 1. Sanitize and Validate Email (Data comes from $_POST or $_GET)
        // AjaxRequestHandler doesn't automatically pass data, access $_POST directly here.
        if (!isset($_POST['email']) || !is_email($_POST['email'])) {
            // Return a WP_Error object for failure
            return new WP_Error('invalid_email', 'Please provide a valid email address.');
        }
        $email = sanitize_email($_POST['email']);

        // 2. Check if user exists
        if (email_exists($email)) {
            return new WP_Error('email_exists', 'This email address is already registered.');
        }

        // 3. Generate Password
        $password = wp_generate_password(12, true, true);

        // 4. Create User
        $user_id = wp_create_user($email, $password, $email);

        // 5. Check for errors during creation
        if (is_wp_error($user_id)) {
            error_log('Glory Signup - User Creation Error: ' . $user_id->get_error_message());
            // You can return the original WP_Error or create a more generic one
            return new WP_Error('creation_failed', 'Could not create account. Please try again later.', ['original_error' => $user_id->get_error_code()]);
        }

        // 6. Success: Return data for the frontend
        // AjaxRequestHandler will wrap this in { success: true, data: ... }
        return [
            // 'message' => 'Account created successfully!', // Optional: message handled by JS potentially
            'userId' => $user_id
        ];
    }

    /**
     * Handles the user details update AJAX request.
     * Called by AjaxRequestHandler after nonce verification.
     *
     * @internal Should only be called via AjaxRequestHandler.
     * @return array|WP_Error Data array on success, WP_Error on failure.
     */
    public static function handleDetailsUpdate() // Removed wp_die() and wp_send_json_* calls
    {
        // 1. Validate User ID
        if (!isset($_POST['user_id']) || !($userId = absint($_POST['user_id'])) || $userId === 0) {
            return new WP_Error('invalid_user_id', 'Invalid user identifier.');
        }

        // 2. Check if user exists
        if (!get_userdata($userId)) {
            return new WP_Error('user_not_found', 'User not found.');
        }

        // 3. Sanitize names
        $firstName = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $lastName = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';

        // 4. Update User Meta
        update_user_meta($userId, 'first_name', $firstName);
        update_user_meta($userId, 'last_name', $lastName);

        // 5. Success: Return data/message
        return [
            'message' => 'Profile updated successfully!'
        ];
    }
} // End class EmailSignupService


EmailSignupService::registerAjaxActions();