<?php

/**
 * Glory Images Controller - REST API para imágenes optimizadas
 *
 * Expone endpoints para obtener URLs de imágenes optimizadas
 * con soporte para CDN (Jetpack Photon) y filtrado por alias.
 *
 * Endpoints:
 * - GET /wp-json/glory/v1/images            (listar imágenes de un alias)
 * - GET /wp-json/glory/v1/images/url        (obtener URL optimizada de una imagen)
 * - GET /wp-json/glory/v1/images/random     (obtener imágenes aleatorias)
 *
 * @package Glory\Api
 */

namespace Glory\Api;

use Glory\Utility\AssetsUtility;
use Glory\Utility\ImageUtility;

class ImagesController
{
    private const API_NAMESPACE = 'glory/v1';

    /*
     * Aliases predefinidos disponibles para el frontend.
     * Solo estos aliases serán accesibles por seguridad.
     */
    private const ALLOWED_ALIASES = [
        'glory',
        'colors',
        'elements',
        'logos',
        'tema'
    ];

    /*
     * Registra los endpoints REST
     */
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    /*
     * Define las rutas REST
     */
    public static function registerRoutes(): void
    {
        /* GET /images - Listar imágenes de un alias */
        register_rest_route(self::API_NAMESPACE, '/images', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'listImages'],
            'permission_callback' => '__return_true',
            'args' => [
                'alias' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => 'Alias del directorio de imágenes (colors, elements, logos, tema, glory)'
                ],
                'limit' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 50,
                    'sanitize_callback' => 'absint',
                    'description' => 'Cantidad máxima de imágenes a retornar'
                ],
                'minSize' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                    'description' => 'Tamaño mínimo en bytes para filtrar imágenes'
                ]
            ]
        ]);

        /* GET /images/url - Obtener URL optimizada de una imagen específica */
        register_rest_route(self::API_NAMESPACE, '/images/url', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'getImageUrl'],
            'permission_callback' => '__return_true',
            'args' => [
                'ref' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => 'Referencia de imagen en formato alias::archivo'
                ],
                'width' => [
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                    'description' => 'Ancho deseado para redimensionar'
                ],
                'height' => [
                    'required' => false,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                    'description' => 'Alto deseado para redimensionar'
                ],
                'quality' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 80,
                    'sanitize_callback' => 'absint',
                    'description' => 'Calidad de compresión (1-100)'
                ]
            ]
        ]);

        /* GET /images/random - Obtener imágenes aleatorias sin repetir */
        register_rest_route(self::API_NAMESPACE, '/images/random', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'getRandomImages'],
            'permission_callback' => '__return_true',
            'args' => [
                'alias' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => 'Alias del directorio de imágenes'
                ],
                'count' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 5,
                    'sanitize_callback' => 'absint',
                    'description' => 'Cantidad de imágenes aleatorias'
                ],
                'minSize' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                    'description' => 'Tamaño mínimo en bytes'
                ],
                'exclude' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => 'Lista de archivos a excluir, separados por coma'
                ]
            ]
        ]);

        /* GET /images/aliases - Listar aliases disponibles */
        register_rest_route(self::API_NAMESPACE, '/images/aliases', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [self::class, 'getAliases'],
            'permission_callback' => '__return_true'
        ]);
    }

    /*
     * Lista imágenes disponibles de un alias
     *
     * GET /wp-json/glory/v1/images?alias=colors&limit=10
     */
    public static function listImages(\WP_REST_Request $request): \WP_REST_Response
    {
        $alias = $request->get_param('alias');
        $limit = $request->get_param('limit') ?? 50;
        $minSize = $request->get_param('minSize') ?? 0;

        if (!self::isAliasAllowed($alias)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Alias no permitido',
                'allowedAliases' => self::ALLOWED_ALIASES
            ], 400);
        }

        $images = $minSize > 0
            ? AssetsUtility::listImagesForAliasWithMinSize($alias, $minSize)
            : AssetsUtility::listImagesForAlias($alias);

        if (empty($images)) {
            return new \WP_REST_Response([
                'success' => true,
                'alias' => $alias,
                'images' => [],
                'count' => 0
            ], 200);
        }

        /* Limitar resultados */
        $images = array_slice($images, 0, $limit);

        /* Construir respuesta con URLs optimizadas */
        $response = [];
        foreach ($images as $filename) {
            $ref = "{$alias}::{$filename}";
            $url = AssetsUtility::imagenUrl($ref);

            if ($url) {
                $response[] = [
                    'filename' => $filename,
                    'ref' => $ref,
                    'url' => $url,
                    'urlCdn' => self::getCdnUrl($url)
                ];
            }
        }

        return new \WP_REST_Response([
            'success' => true,
            'alias' => $alias,
            'images' => $response,
            'count' => count($response)
        ], 200);
    }

    /*
     * Obtiene la URL optimizada de una imagen específica
     *
     * GET /wp-json/glory/v1/images/url?ref=colors::imagen.jpg&width=400
     */
    public static function getImageUrl(\WP_REST_Request $request): \WP_REST_Response
    {
        $ref = $request->get_param('ref');
        $width = $request->get_param('width');
        $height = $request->get_param('height');
        $quality = $request->get_param('quality') ?? 80;

        /* Validar formato de referencia */
        if (strpos($ref, '::') === false) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Formato de referencia inválido. Use alias::archivo'
            ], 400);
        }

        list($alias, $filename) = explode('::', $ref, 2);

        if (!self::isAliasAllowed($alias)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Alias no permitido'
            ], 400);
        }

        $urlBase = AssetsUtility::imagenUrl($ref);

        if (!$urlBase) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Imagen no encontrada'
            ], 404);
        }

        /* Construir argumentos para CDN */
        $cdnArgs = ['quality' => $quality, 'strip' => 'all'];

        if ($width && $height) {
            $cdnArgs['resize'] = "{$width},{$height}";
        } elseif ($width) {
            $cdnArgs['w'] = $width;
        } elseif ($height) {
            $cdnArgs['h'] = $height;
        }

        $urlCdn = ImageUtility::jetpack_photon_url($urlBase, $cdnArgs);

        return new \WP_REST_Response([
            'success' => true,
            'ref' => $ref,
            'url' => $urlBase,
            'urlCdn' => $urlCdn,
            'dimensions' => [
                'width' => $width,
                'height' => $height
            ],
            'quality' => $quality
        ], 200);
    }

    /*
     * Obtiene imágenes aleatorias sin repetir
     *
     * GET /wp-json/glory/v1/images/random?alias=colors&count=5
     */
    public static function getRandomImages(\WP_REST_Request $request): \WP_REST_Response
    {
        $alias = $request->get_param('alias');
        $count = $request->get_param('count') ?? 5;
        $minSize = $request->get_param('minSize') ?? 0;
        $excludeParam = $request->get_param('exclude') ?? '';

        if (!self::isAliasAllowed($alias)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Alias no permitido'
            ], 400);
        }

        /* Obtener lista de imágenes excluyendo las ya usadas */
        $excludeList = $excludeParam ? array_map('trim', explode(',', $excludeParam)) : [];

        $allImages = AssetsUtility::pickRandomImages($alias, $count + count($excludeList), $minSize);

        /* Filtrar excluidas */
        $filteredImages = array_filter($allImages, fn($img) => !in_array($img, $excludeList));
        $images = array_slice(array_values($filteredImages), 0, $count);

        /* Construir respuesta con URLs */
        $response = [];
        foreach ($images as $filename) {
            $ref = "{$alias}::{$filename}";
            $url = AssetsUtility::imagenUrl($ref);

            if ($url) {
                $response[] = [
                    'filename' => $filename,
                    'ref' => $ref,
                    'url' => $url,
                    'urlCdn' => self::getCdnUrl($url)
                ];
            }
        }

        return new \WP_REST_Response([
            'success' => true,
            'alias' => $alias,
            'images' => $response,
            'count' => count($response)
        ], 200);
    }

    /*
     * Retorna la lista de aliases disponibles
     *
     * GET /wp-json/glory/v1/images/aliases
     */
    public static function getAliases(\WP_REST_Request $request): \WP_REST_Response
    {
        return new \WP_REST_Response([
            'success' => true,
            'aliases' => self::ALLOWED_ALIASES,
            'description' => [
                'glory' => 'Imágenes generales del framework Glory',
                'colors' => 'Imágenes de colores y fondos',
                'elements' => 'Elementos decorativos y UI',
                'logos' => 'Logotipos y marcas',
                'tema' => 'Imágenes específicas del tema actual'
            ]
        ], 200);
    }

    /*
     * Verifica si un alias está permitido
     */
    private static function isAliasAllowed(string $alias): bool
    {
        return in_array($alias, self::ALLOWED_ALIASES, true);
    }

    /*
     * Obtiene la URL de CDN para una imagen
     */
    private static function getCdnUrl(string $url): string
    {
        return ImageUtility::jetpack_photon_url($url, [
            'quality' => 80,
            'strip' => 'all'
        ]);
    }
}
