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
     */
    public static function crearTabla(): void
    {
        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_SUFFIX;

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") === $tabla) {
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
    }

    /*
     * Endpoint: Suscribir email
     */
    public static function suscribir(\WP_REST_Request $request): \WP_REST_Response
    {
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
            'ip'    => $_SERVER['REMOTE_ADDR'] ?? null
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
    }
}
