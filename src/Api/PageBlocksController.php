<?php

/**
 * Glory Page Builder - REST API Controller
 *
 * Maneja los endpoints para guardar y obtener los bloques
 * de una pagina del Page Builder.
 *
 * Endpoints:
 * - GET  /wp-json/glory/v1/page-blocks/{page_id}
 * - POST /wp-json/glory/v1/page-blocks/{page_id}
 *
 * @package Glory\Api
 */

namespace Glory\Api;

class PageBlocksController
{
    /**
     * Nombre del meta key donde se guardan los bloques
     */
    private const META_KEY = '_glory_page_blocks';

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
        register_rest_route(self::API_NAMESPACE, '/page-blocks/(?P<page_id>\d+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [self::class, 'getBlocks'],
                'permission_callback' => [self::class, 'canReadBlocks'],
                'args' => [
                    'page_id' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        }
                    ]
                ]
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [self::class, 'saveBlocks'],
                'permission_callback' => [self::class, 'canEditBlocks'],
                'args' => [
                    'page_id' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        }
                    ],
                    'blocks' => [
                        'required' => true,
                        'validate_callback' => function ($param) {
                            return is_array($param);
                        }
                    ]
                ]
            ]
        ]);
    }

    /**
     * Verifica si el usuario puede leer los bloques
     */
    public static function canReadBlocks(\WP_REST_Request $request): bool
    {
        /* 
         * Los bloques son publicos para lectura 
         * (se usan en el renderizado SSR)
         */
        return true;
    }

    /**
     * Verifica si el usuario puede editar los bloques
     */
    public static function canEditBlocks(\WP_REST_Request $request): bool
    {
        $pageId = (int) $request->get_param('page_id');

        /* Verificar que el usuario puede editar la pagina */
        if (!current_user_can('edit_post', $pageId)) {
            return false;
        }

        return true;
    }

    /**
     * Obtiene los bloques de una pagina
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function getBlocks(\WP_REST_Request $request): \WP_REST_Response
    {
        $pageId = (int) $request->get_param('page_id');

        /* Verificar que la pagina existe */
        $post = get_post($pageId);
        if (!$post) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Pagina no encontrada'
            ], 404);
        }

        /* Obtener bloques guardados */
        $blocksJson = get_post_meta($pageId, self::META_KEY, true);
        $blocks = $blocksJson ? json_decode($blocksJson, true) : null;

        return new \WP_REST_Response([
            'success' => true,
            'data' => [
                'pageId' => $pageId,
                'blocks' => $blocks,
                'lastModified' => $post->post_modified
            ]
        ], 200);
    }

    /**
     * Guarda los bloques de una pagina
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function saveBlocks(\WP_REST_Request $request): \WP_REST_Response
    {
        $pageId = (int) $request->get_param('page_id');
        $blocks = $request->get_param('blocks');

        /* Verificar que la pagina existe */
        $post = get_post($pageId);
        if (!$post) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Pagina no encontrada'
            ], 404);
        }

        /* Validar estructura de bloques */
        if (!self::validateBlocks($blocks)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Estructura de bloques invalida'
            ], 400);
        }

        /* Preparar datos para guardar */
        $pageData = [
            'version' => '1.0',
            'time' => time() * 1000,
            'blocks' => $blocks
        ];

        /* Guardar en post_meta */
        $jsonData = wp_json_encode($pageData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $result = update_post_meta($pageId, self::META_KEY, $jsonData);

        if ($result === false) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => 'Error al guardar los bloques'
            ], 500);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Bloques guardados correctamente',
            'data' => [
                'pageId' => $pageId,
                'blocksCount' => count($blocks),
                'savedAt' => current_time('mysql')
            ]
        ], 200);
    }

    /**
     * Valida la estructura de los bloques
     *
     * @param array $blocks
     * @return bool
     */
    private static function validateBlocks(array $blocks): bool
    {
        foreach ($blocks as $block) {
            /* Cada bloque debe tener id, type y props */
            if (!isset($block['id']) || !isset($block['type']) || !isset($block['props'])) {
                return false;
            }

            /* id y type deben ser strings */
            if (!is_string($block['id']) || !is_string($block['type'])) {
                return false;
            }

            /* props debe ser un array u objeto */
            if (!is_array($block['props'])) {
                return false;
            }
        }

        return true;
    }
}
