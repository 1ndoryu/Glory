<?php

namespace Glory\Services; // Asegúrate que el namespace coincida

use Exception;
use WP_Error;

/**
 * Service class handling AJAX actions for the Email Signup component.
 * Adaptado para usar hooks nativos de WordPress AJAX.
 */
class EmailSignupService
{
    const ACTION_REGISTER = 'glory_register_email';
    const ACTION_UPDATE   = 'glory_update_user_details';
    const NONCE_ACTION    = 'glory_email_signup_action'; 

    /**
     * REGISTRA LOS HOOKS AJAX NATIVOS.
     * Esta función ahora se llamaría desde tu functions.php o inicializador principal.
     */
    public static function registerAjaxHooks(): void
    {
        // Acción para registrar email (usuarios no logueados)
        add_action('wp_ajax_nopriv_' . self::ACTION_REGISTER, [self::class, 'handleRegistration']);
        // Podrías añadir también 'wp_ajax_' si los usuarios logueados también pueden usarlo
        add_action('wp_ajax_' . self::ACTION_REGISTER, [self::class, 'handleRegistration']);

        // Acción para actualizar detalles (usuarios no logueados - ¿o deberían estar logueados?)
        // Asumimos que no necesitan estar logueados porque acaban de registrarse
        add_action('wp_ajax_nopriv_' . self::ACTION_UPDATE, [self::class, 'handleDetailsUpdate']);
        add_action('wp_ajax_' . self::ACTION_UPDATE, [self::class, 'handleDetailsUpdate']);
    }

    /**
     * Handles the initial email registration AJAX request.
     * ¡AHORA verifica nonce y envía respuesta JSON directamente!
     */
    public static function handleRegistration()
    {
        error_log('Glory Service LOG: handleRegistration() ejecutándose - Inicio');
        try {
            // Log del nonce recibido ANTES de verificar
            $received_nonce = isset($_POST['_ajax_nonce']) ? $_POST['_ajax_nonce'] : 'NO RECIBIDO';
            error_log('Glory Service LOG: Nonce recibido del cliente: ' . $received_nonce);

            // Genera el nonce que el servidor esperaría AHORA MISMO
            $expected_nonce = wp_create_nonce(self::NONCE_ACTION);
            error_log('Glory Service LOG: Nonce esperado por el servidor AHORA: ' . $expected_nonce);

            // 1. VERIFICAR NONCE
            error_log('Glory Service LOG: handleRegistration() - Verificando Nonce...');
            // Descomenta la siguiente línea solo si la comparación manual falla y quieres ver el error exacto
            // if (!wp_verify_nonce($received_nonce, self::NONCE_ACTION)) {
            //     error_log('Glory Service LOG: ¡LA VERIFICACIÓN MANUAL DEL NONCE FALLÓ!');
            // }

            check_ajax_referer(self::NONCE_ACTION, '_ajax_nonce');
            error_log('Glory Service LOG: handleRegistration() Nonce verificado'); // <-- Si esto no aparece, falló check_ajax_referer



            // 2. Sanitize y Validar Email (viene de $_POST porque usamos 'application/x-www-form-urlencoded')
            if (!isset($_POST['email']) || !is_email($_POST['email'])) {
                error_log('Glory Service LOG: handleRegistration() Email no válido');
                wp_send_json_error(['message' => 'Please provide a valid email address.'], 400); // Bad Request
                // wp_die(); // wp_send_json_* ya incluye die()
            }
            $email = sanitize_email($_POST['email']);
            error_log('Glory Service LOG: handleRegistration() Email validado: ' . $email . ' - Verificando si existe');

            // 3. Verificar si existe
            if (email_exists($email)) {
                error_log('Glory Service LOG: handleRegistration() Email ya registrado: ' . $email);
                wp_send_json_error(['message' => 'This email address is already registered.'], 409); // Conflict
                return;
            }
            error_log('Glory Service LOG: handleRegistration() Email no registrado - Creando usuario');

            // 4. Crear usuario
            $password = wp_generate_password(12, true, true);
            $user_id = wp_create_user($email, $password, $email);
            error_log('Glory Service LOG: handleRegistration() Usuario creado: ' . $user_id . ' - Verificando si hay error');

            // 5. Verificar error de creación
            if (is_int($user_id)) {
                error_log('Glory Service LOG: handleRegistration() Usuario creado correctamente: ' . $user_id);
            }

            if (is_wp_error($user_id)) {
                error_log('Glory Signup - Error Creación: ' . $user_id->get_error_message());
                wp_send_json_error(['message' => 'Could not create account. Please try again later.'], 500); // Internal Server Error
            }

            // 6. ÉXITO: Enviar respuesta JSON de éxito
            wp_send_json_success([
                'userId' => $user_id
                // 'message' => 'Optional success message here'
            ]);
            error_log('Glory Service LOG: handleRegistration() Respuesta enviada correctamente');
            // wp_die(); // wp_send_json_success ya incluye die()
        } catch (Exception $e) {
            error_log('Glory Service LOG: handleRegistration() Excepción: ' . $e->getMessage());
            wp_send_json_error(['message' => 'An unexpected error occurred.'], 500);
            return;
        }
    }

    /**
     * Handles the user details update AJAX request.
     * ¡AHORA verifica nonce y envía respuesta JSON directamente!
     */
    public static function handleDetailsUpdate()
    {
        error_log('Glory Service LOG: handleDetailsUpdate() ejecutándose');
        try {
            // 1. VERIFICAR NONCE (misma acción nonce)
            check_ajax_referer(self::NONCE_ACTION, '_ajax_nonce');
            error_log('Glory Service LOG: handleDetailsUpdate() Nonce verificado');

            // 2. Validar User ID
            if (!isset($_POST['user_id']) || !($userId = absint($_POST['user_id'])) || $userId === 0) {
                error_log('Glory Service LOG: handleDetailsUpdate() User ID no válido');
                wp_send_json_error(['message' => 'Invalid user identifier.'], 400);
            }
            error_log('Glory Service LOG: handleDetailsUpdate() User ID validado: ' . $userId);

            // 3. Verificar si el usuario existe
            if (!get_userdata($userId)) {
                error_log('Glory Service LOG: handleDetailsUpdate() Usuario no encontrado: ' . $userId);
                wp_send_json_error(['message' => 'User not found.'], 404); // Not Found
            }

            // 4. Sanitize names
            $firstName = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
            $lastName = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
            error_log('Glory Service LOG: handleDetailsUpdate() Nombres sanitizados: ' . $firstName . ' ' . $lastName);

            // 5. Update User Meta
            update_user_meta($userId, 'first_name', $firstName);
            update_user_meta($userId, 'last_name', $lastName);
            error_log('Glory Service LOG: handleDetailsUpdate() Metadatos actualizados');

            // 6. ÉXITO: Enviar respuesta JSON de éxito
            wp_send_json_success([
                'message' => 'Profile updated successfully!'
            ]);
            // wp_die(); // wp_send_json_success ya incluye die()
        } catch (Exception $e) {
            error_log('Glory Service LOG: handleDetailsUpdate() Excepción: ' . $e->getMessage());
            wp_send_json_error(['message' => 'An unexpected error occurred.'], 500);
        }
    }
}

EmailSignupService::registerAjaxHooks();
