<?php

/**
 * Glory Form Controller - REST API para formularios de contacto
 *
 * Endpoint genérico para recibir datos de formularios del frontend React.
 * Guarda los envíos en tabla custom y opcionalmente envía email de notificación.
 *
 * Endpoints:
 * - POST /wp-json/glory/v1/form  (enviar formulario)
 *
 * @package Glory\Api
 */

namespace Glory\Api;

class FormController
{
    private const API_NAMESPACE = 'glory/v1';
    private const TABLE_SUFFIX = 'glory_form_entries';

    /*
     * Registra los endpoints REST y la creación de tabla
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
        register_rest_route(self::API_NAMESPACE, '/form', [
            'methods'  => 'POST',
            'callback' => [self::class, 'procesarFormulario'],
            'permission_callback' => '__return_true',
            'args' => [
                'formId' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'nombre' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'email' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => function ($param) {
                        return is_email($param);
                    },
                ],
                'telefono' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'mensaje' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
                'extra' => [
                    'required' => false,
                    'type' => 'object',
                    'default' => [],
                ],
            ],
        ]);
    }

    /*
     * Crear tabla si no existe
     */
    public static function crearTabla(): void
    {
        if (get_option('glory_form_entries_tabla_creada')) {
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . self::TABLE_SUFFIX;

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla)) === $tabla) {
            update_option('glory_form_entries_tabla_creada', true);
            return;
        }

        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $tabla (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id varchar(100) NOT NULL,
            nombre varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            telefono varchar(50) DEFAULT NULL,
            mensaje text DEFAULT NULL,
            extra longtext DEFAULT NULL,
            ip varchar(45) DEFAULT NULL,
            fecha datetime DEFAULT CURRENT_TIMESTAMP,
            leido tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY fecha (fecha)
        ) $charset;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('glory_form_entries_tabla_creada', true);
    }

    /*
     * Endpoint: Procesar envío de formulario
     */
    public static function procesarFormulario(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            /* Rate limiting: máx 3 envíos por IP cada 5 minutos */
            $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
            $rateLimitKey = 'glory_form_rate_' . md5($ip);
            $intentos = (int) get_transient($rateLimitKey);

            if ($intentos >= 3) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Demasiados envíos. Inténtalo de nuevo en unos minutos.',
                ], 429);
            }
            set_transient($rateLimitKey, $intentos + 1, 300);

            $formId   = $request->get_param('formId');
            $nombre   = $request->get_param('nombre');
            $email    = $request->get_param('email');
            $telefono = $request->get_param('telefono') ?? '';
            $mensaje  = $request->get_param('mensaje') ?? '';
            $extra    = $request->get_param('extra') ?? [];

            if (empty($email) || !is_email($email)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'El email proporcionado no es válido.',
                ], 400);
            }

            if (empty($nombre)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'El nombre es obligatorio.',
                ], 400);
            }

            /* Guardar en base de datos */
            global $wpdb;
            $tabla = $wpdb->prefix . self::TABLE_SUFFIX;

            $resultado = $wpdb->insert($tabla, [
                'form_id'  => $formId,
                'nombre'   => $nombre,
                'email'    => $email,
                'telefono' => $telefono,
                'mensaje'  => $mensaje,
                'extra'    => wp_json_encode($extra),
                'ip'       => $ip,
            ], ['%s', '%s', '%s', '%s', '%s', '%s', '%s']);

            if ($resultado === false) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Error al guardar el formulario. Inténtalo más tarde.',
                ], 500);
            }

            /* Enviar email de notificación al admin */
            $adminEmail = get_option('admin_email');
            $siteName   = get_bloginfo('name');
            $asunto     = sprintf('[%s] Nuevo formulario: %s', $siteName, $formId);

            $cuerpo = sprintf(
                "Nuevo envío de formulario\n\n" .
                "Formulario: %s\n" .
                "Nombre: %s\n" .
                "Email: %s\n" .
                "Teléfono: %s\n" .
                "Mensaje:\n%s\n",
                $formId,
                $nombre,
                $email,
                $telefono,
                $mensaje
            );

            if (!empty($extra)) {
                $cuerpo .= "\nDatos adicionales:\n";
                foreach ($extra as $clave => $valor) {
                    $cuerpo .= sprintf("- %s: %s\n", sanitize_text_field($clave), sanitize_text_field($valor));
                }
            }

            wp_mail($adminEmail, $asunto, $cuerpo, [
                'Content-Type: text/plain; charset=UTF-8',
                sprintf('Reply-To: %s <%s>', $nombre, $email),
            ]);

            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Formulario enviado correctamente. Nos pondremos en contacto pronto.',
            ], 201);
        } catch (\Throwable $e) {
            error_log('[FormController] Error en procesarFormulario: ' . $e->getMessage());
            return new \WP_REST_Response(['error' => 'Error interno del servidor'], 500);
        }
    }
}
