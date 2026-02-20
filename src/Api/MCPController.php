<?php

/**
 * Glory MCP Controller - REST API para configuración MCP
 *
 * Maneja los endpoints para generar, verificar y revocar
 * tokens de Application Password para la conexión MCP.
 *
 * Endpoints:
 * - GET    /wp-json/glory/v1/mcp/token  (verificar existencia)
 * - POST   /wp-json/glory/v1/mcp/token  (generar token)
 * - DELETE /wp-json/glory/v1/mcp/token  (revocar token)
 * - GET    /wp-json/glory/v1/mcp/config (obtener configuración)
 *
 * @package Glory\Api
 */

namespace Glory\Api;

class MCPController
{
    /**
     * Nombre del Application Password para MCP
     */
    private const APP_PASSWORD_NAME = 'Glory MCP Access';

    /**
     * Namespace de la API
     */
    private const API_NAMESPACE = 'glory/v1';

    /**
     * Registra los endpoints REST
     */
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    /**
     * Define las rutas REST
     */
    public static function registerRoutes(): void
    {
        /* GET /mcp/token - Verificar si existe token */
        register_rest_route(self::API_NAMESPACE, '/mcp/token', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'getToken'],
            'permission_callback' => [self::class, 'canManageToken']
        ]);

        /* POST /mcp/token - Generar nuevo token */
        register_rest_route(self::API_NAMESPACE, '/mcp/token', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'createToken'],
            'permission_callback' => [self::class, 'canManageToken']
        ]);

        /* DELETE /mcp/token - Revocar token */
        register_rest_route(self::API_NAMESPACE, '/mcp/token', [
            'methods' => \WP_REST_Server::DELETABLE,
            'callback' => [self::class, 'deleteToken'],
            'permission_callback' => [self::class, 'canManageToken']
        ]);

        /* GET /mcp/config - Obtener configuración JSON */
        register_rest_route(self::API_NAMESPACE, '/mcp/config', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'getConfig'],
            'permission_callback' => [self::class, 'canManageToken']
        ]);
    }

    /**
     * Verifica si el usuario puede gestionar tokens MCP
     * Requiere manage_options (solo administradores)
     */
    public static function canManageToken(): bool
    {
        return is_user_logged_in() && current_user_can('manage_options');
    }

    /**
     * Obtiene el estado del token MCP
     * 
     * GET /wp-json/glory/v1/mcp/token
     */
    public static function getToken(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $userId = get_current_user_id();
            $tokenExistente = self::findExistingToken($userId);

            if ($tokenExistente) {
                return new \WP_REST_Response([
                    'success' => true,
                    'existe' => true,
                    'nombre' => self::APP_PASSWORD_NAME,
                    'fechaCreacion' => $tokenExistente['created'],
                    'ultimoUso' => $tokenExistente['last_used'] ?? null
                ], 200);
            }

            return new \WP_REST_Response([
                'success' => true,
                'existe' => false
            ], 200);
        } catch (\Throwable $e) {
            error_log('[MCPController] Error en getToken: ' . $e->getMessage());
            return new \WP_REST_Response(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Genera un nuevo Application Password para MCP
     * 
     * POST /wp-json/glory/v1/mcp/token
     */
    public static function createToken(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $userId = get_current_user_id();

            /* Verificar si ya existe - revocar el anterior */
            $tokenExistente = self::findExistingToken($userId);
            if ($tokenExistente) {
                \WP_Application_Passwords::delete_application_password($userId, $tokenExistente['uuid']);
            }

            /* Generar nuevo Application Password */
            $resultado = \WP_Application_Passwords::create_new_application_password(
                $userId,
                [
                    'name' => self::APP_PASSWORD_NAME,
                ]
            );

            if (is_wp_error($resultado)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => $resultado->get_error_message()
                ], 500);
            }

            /* El resultado contiene [0] => password, [1] => item info */
            list($password, $item) = $resultado;

            /* Generar token Base64 para Authorization header */
            $user = wp_get_current_user();
            $tokenBase64 = base64_encode($user->user_login . ':' . $password);

            return new \WP_REST_Response([
                'success' => true,
                'token' => $password,
                'tokenBase64' => $tokenBase64,
                'nombre' => self::APP_PASSWORD_NAME,
                'fechaCreacion' => gmdate('c')
            ], 201);
        } catch (\Throwable $e) {
            error_log('[MCPController] Error en createToken: ' . $e->getMessage());
            return new \WP_REST_Response(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Revoca el token MCP existente
     * 
     * DELETE /wp-json/glory/v1/mcp/token
     */
    public static function deleteToken(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $userId = get_current_user_id();
            $tokenExistente = self::findExistingToken($userId);

            if (!$tokenExistente) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'No existe token MCP para revocar'
                ], 404);
            }

            $resultado = \WP_Application_Passwords::delete_application_password($userId, $tokenExistente['uuid']);

            if ($resultado === false) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Error al revocar el token'
                ], 500);
            }

            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Token revocado correctamente'
            ], 200);
        } catch (\Throwable $e) {
            error_log('[MCPController] Error en deleteToken: ' . $e->getMessage());
            return new \WP_REST_Response(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Obtiene la configuración JSON para los clientes MCP
     * 
     * GET /wp-json/glory/v1/mcp/config
     */
    public static function getConfig(\WP_REST_Request $request): \WP_REST_Response
    {
        try {
            $apiUrl = rest_url('glory/v1');
            $themePath = get_template_directory();

            /* Configuración para Claude Desktop */
            $claudeConfig = [
                'mcpServers' => [
                    'glory-tareas' => [
                        'command' => 'node',
                        'args' => [str_replace('/', '\\', $themePath) . '\\mcp\\dist\\index.js'],
                        'env' => [
                            'GLORY_API_URL' => $apiUrl,
                            'GLORY_AUTH_TOKEN' => 'TU_TOKEN_AQUI'
                        ]
                    ]
                ]
            ];

            /* Configuración para Cursor IDE */
            $cursorConfig = [
                'glory-tareas' => [
                    'command' => 'node',
                    'args' => ['./mcp/dist/index.js'],
                    'env' => [
                        'GLORY_API_URL' => $apiUrl,
                        'GLORY_AUTH_TOKEN' => 'TU_TOKEN_AQUI'
                    ]
                ]
            ];

            /* Rutas de archivos de configuración */
            $rutasClaude = self::getClaudeConfigPath();
            $rutasCursor = '.cursor/mcp.json';

            return new \WP_REST_Response([
                'success' => true,
                'claudeDesktop' => $claudeConfig,
                'cursor' => $cursorConfig,
                'rutaConfigClaude' => $rutasClaude,
                'rutaConfigCursor' => $rutasCursor,
                'apiUrl' => $apiUrl,
                'mcpPath' => $themePath . '/mcp/dist/index.js'
            ], 200);
        } catch (\Throwable $e) {
            error_log('[MCPController] Error en getConfig: ' . $e->getMessage());
            return new \WP_REST_Response(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Busca un Application Password existente con el nombre MCP
     */
    private static function findExistingToken(int $userId): ?array
    {
        $passwords = \WP_Application_Passwords::get_user_application_passwords($userId);

        foreach ($passwords as $password) {
            if ($password['name'] === self::APP_PASSWORD_NAME) {
                return $password;
            }
        }

        return null;
    }

    /**
     * Obtiene la ruta del archivo de configuración de Claude
     * según el sistema operativo
     */
    private static function getClaudeConfigPath(): string
    {
        /* Windows */
        if (PHP_OS_FAMILY === 'Windows') {
            return '%APPDATA%\\Claude\\claude_desktop_config.json';
        }

        /* macOS */
        if (PHP_OS_FAMILY === 'Darwin') {
            return '~/Library/Application Support/Claude/claude_desktop_config.json';
        }

        /* Linux */
        return '~/.config/Claude/claude_desktop_config.json';
    }
}
