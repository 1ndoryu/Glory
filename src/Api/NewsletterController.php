<?php

/**
 * Glory Newsletter Controller - REST API para suscripciones
 *
 * Endpoint para guardar emails de newsletter en la base de datos de WP.
 * Usa una tabla custom `wp_glory_newsletter` para almacenar las suscripciones.
 *
 * Endpoints:
 * - POST /wp-json/glory/v1/newsletter  (suscribir email)
 *
 * @package Glory\Api
 */

namespace Glory\Api;

class NewsletterController
{
    private const API_NAMESPACE = 'glory/v1';
    private const TABLE_SUFFIX = 'glory_newsletter';

    /*
     * Registra los endpoints REST
     */
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
        add_action('after_setup_theme', [self::class, 'crearTabla']);
    }

    /*
     * Registrar rutas REST
     */
    public static function registerRoutes(): void
    {
        register_rest_route(self::API_NAMESPACE, '/newsletter', [
            'methods'  => 'POST',
            'callback' => [self::class, 'suscribir'],
            'permission_callback' => '__return_true',
            'args' => [
                'email' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => function ($param) {
                        return is_email($param);
                    }
                ]
            ]
        ]);
    }

    /*
     * Crear tabla si no existe
     * Usa get_option para evitar SHOW TABLES en cada carga
     */
    public static function crearTabla(): void
    {
        if (get_option('glory_newsletter_tabla_creada')) {
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_SUFFIX;

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla)) === $tabla) {
            update_option('glory_newsletter_tabla_creada', true);
            return;
        }

        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $tabla (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            fecha_suscripcion datetime DEFAULT CURRENT_TIMESTAMP,
            activo tinyint(1) DEFAULT 1,
            ip varchar(45) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('glory_newsletter_tabla_creada', true);
    }

    /*
     * Endpoint: Suscribir email
     */
    public static function suscribir(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            /* Rate limiting simple: max 5 suscripciones por IP cada 10 minutos */
            $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
            $rateLimitKey = 'glory_newsletter_rate_' . md5($ip);
            $intentos = (int) get_transient($rateLimitKey);
            if ($intentos >= 5) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Demasiados intentos. Intenta de nuevo más tarde.'
                ], 429);
            }
            set_transient($rateLimitKey, $intentos + 1, 600);

            global $wpdb;
            $tabla = $wpdb->prefix . self::TABLE_SUFFIX;
            $email = $request->get_param('email');

            if (empty($email) || !is_email($email)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Email inválido.'
                ], 400);
            }

            /* Verificar si ya existe */
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla WHERE email = %s",
                $email
            ));

            if ($existe) {
                return new \WP_REST_Response([
                    'success' => true,
                    'message' => 'Ya estás suscrito.'
                ], 200);
            }

            /* Insertar nuevo suscriptor */
            $resultado = $wpdb->insert($tabla, [
                'email' => $email,
                'ip'    => $ip
            ], ['%s', '%s']);

            if ($resultado === false) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Error al guardar la suscripción.'
                ], 500);
            }

            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Suscripción exitosa.'
            ], 201);
        } catch (\Throwable $e) {
            error_log('[NewsletterController] Error en suscribir: ' . $e->getMessage());
            return new \WP_REST_Response(['error' => 'Error interno del servidor'], 500);
        }
    }
}
