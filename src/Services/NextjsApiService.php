<?php

namespace Glory\Services;

/* 
 * Servicio para configurar la API REST de WordPress para Next.js SSR
 * - Habilita CORS para permitir requests desde Next.js
 * - Registra endpoints personalizados optimizados
 */

class NextjsApiService
{
    private static bool $inicializado = false;

    /* 
     * Origenes permitidos para CORS
     */
    private static array $origenesPermitidos = [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ];

    /* 
     * Inicializa el servicio (llamar una sola vez)
     */
    public static function inicializar(): void
    {
        if (self::$inicializado) {
            return;
        }

        self::configurarCors();
        self::registrarEndpoints();

        self::$inicializado = true;
    }

    /* 
     * Agrega un origen permitido para CORS
     */
    public static function agregarOrigen(string $origen): void
    {
        if (!in_array($origen, self::$origenesPermitidos)) {
            self::$origenesPermitidos[] = $origen;
        }
    }

    /* 
     * Configura CORS para la REST API
     */
    private static function configurarCors(): void
    {
        add_action('rest_api_init', function () {
            remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');

            add_filter('rest_pre_serve_request', function ($value) {
                $origen = $_SERVER['HTTP_ORIGIN'] ?? '';

                if (in_array($origen, self::$origenesPermitidos)) {
                    header('Access-Control-Allow-Origin: ' . $origen);
                    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                    header('Access-Control-Allow-Headers: Content-Type, Authorization');
                    header('Access-Control-Allow-Credentials: true');
                    header('Access-Control-Max-Age: 86400');
                }

                return $value;
            });
        });

        /* 
         * Manejar preflight requests (OPTIONS)
         */
        add_action('init', function () {
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                $origen = $_SERVER['HTTP_ORIGIN'] ?? '';

                if (in_array($origen, self::$origenesPermitidos)) {
                    header('Access-Control-Allow-Origin: ' . $origen);
                    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                    header('Access-Control-Allow-Headers: Content-Type, Authorization');
                    header('Access-Control-Allow-Credentials: true');
                    header('Access-Control-Max-Age: 86400');
                    header('Content-Length: 0');
                    header('Content-Type: text/plain');
                    exit(0);
                }
            }
        }, 1);
    }

    /* 
     * Registra endpoints personalizados optimizados para Next.js
     */
    private static function registrarEndpoints(): void
    {
        add_action('rest_api_init', function () {
            /* 
             * GET /wp-json/glory/v1/posts
             * Endpoint optimizado para listar posts
             */
            register_rest_route('glory/v1', '/posts', [
                'methods' => 'GET',
                'callback' => [self::class, 'obtenerPosts'],
                'permission_callback' => '__return_true',
                'args' => [
                    'per_page' => [
                        'default' => 10,
                        'sanitize_callback' => 'absint',
                    ],
                    'page' => [
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ],
                    'category' => [
                        'default' => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]);

            /* 
             * GET /wp-json/glory/v1/posts/{slug}
             * Endpoint para obtener un post por slug
             */
            register_rest_route('glory/v1', '/posts/(?P<slug>[a-zA-Z0-9-]+)', [
                'methods' => 'GET',
                'callback' => [self::class, 'obtenerPostPorSlug'],
                'permission_callback' => '__return_true',
            ]);

            /* 
             * GET /wp-json/glory/v1/pages/{slug}
             * Endpoint para obtener una pagina por slug
             */
            register_rest_route('glory/v1', '/pages/(?P<slug>[a-zA-Z0-9-]+)', [
                'methods' => 'GET',
                'callback' => [self::class, 'obtenerPaginaPorSlug'],
                'permission_callback' => '__return_true',
            ]);

            /* 
             * GET /wp-json/glory/v1/site-info
             * Endpoint para obtener informacion del sitio
             */
            register_rest_route('glory/v1', '/site-info', [
                'methods' => 'GET',
                'callback' => [self::class, 'obtenerInfoSitio'],
                'permission_callback' => '__return_true',
            ]);

            /* 
             * POST /wp-json/glory/v1/contact
             * Endpoint para enviar formulario de contacto
             */
            register_rest_route('glory/v1', '/contact', [
                'methods' => 'POST',
                'callback' => [self::class, 'enviarContacto'],
                'permission_callback' => '__return_true',
            ]);
        });
    }

    /* 
     * Callback: Obtener lista de posts
     */
    public static function obtenerPosts(\WP_REST_Request $request): \WP_REST_Response
    {
        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page'),
            'paged' => $request->get_param('page'),
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $categoria = $request->get_param('category');
        if (!empty($categoria)) {
            $args['category_name'] = $categoria;
        }

        $query = new \WP_Query($args);
        $posts = array_map([self::class, 'formatearPost'], $query->posts);

        return new \WP_REST_Response([
            'posts' => $posts,
            'total' => $query->found_posts,
            'totalPages' => $query->max_num_pages,
            'page' => (int) $request->get_param('page'),
        ], 200);
    }

    /* 
     * Callback: Obtener post por slug
     */
    public static function obtenerPostPorSlug(\WP_REST_Request $request): \WP_REST_Response
    {
        $slug = $request->get_param('slug');

        $posts = get_posts([
            'name' => $slug,
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 1,
        ]);

        if (empty($posts)) {
            return new \WP_REST_Response([
                'error' => 'Post no encontrado',
                'slug' => $slug,
            ], 404);
        }

        return new \WP_REST_Response(self::formatearPost($posts[0]), 200);
    }

    /* 
     * Callback: Obtener pagina por slug
     */
    public static function obtenerPaginaPorSlug(\WP_REST_Request $request): \WP_REST_Response
    {
        $slug = $request->get_param('slug');

        $pages = get_posts([
            'name' => $slug,
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => 1,
        ]);

        if (empty($pages)) {
            return new \WP_REST_Response([
                'error' => 'Pagina no encontrada',
                'slug' => $slug,
            ], 404);
        }

        $page = $pages[0];

        return new \WP_REST_Response(self::formatearPost($page), 200);
    }

    /* 
     * Callback: Obtener informacion del sitio
     */
    public static function obtenerInfoSitio(): \WP_REST_Response
    {
        return new \WP_REST_Response([
            'nombre' => get_bloginfo('name'),
            'descripcion' => get_bloginfo('description'),
            'url' => home_url(),
            'logoUrl' => self::obtenerLogo(),
            'redes' => [
                'facebook' => get_option('glory_facebook', ''),
                'instagram' => get_option('glory_instagram', ''),
                'linkedin' => get_option('glory_linkedin', ''),
                'twitter' => get_option('glory_twitter', ''),
            ],
            'contacto' => [
                'email' => get_option('admin_email'),
                'telefono' => get_option('glory_telefono', ''),
                'direccion' => get_option('glory_direccion', ''),
            ],
            'calendlyUrl' => get_option('glory_calendly_url', ''),
            'whatsappUrl' => get_option('glory_whatsapp_url', ''),
        ], 200);
    }

    /* 
     * Callback: Enviar formulario de contacto
     */
    public static function enviarContacto(\WP_REST_Request $request): \WP_REST_Response
    {
        $params = $request->get_json_params();

        $nombre = sanitize_text_field($params['nombre'] ?? '');
        $email = sanitize_email($params['email'] ?? '');
        $mensaje = sanitize_textarea_field($params['mensaje'] ?? '');

        if (empty($nombre) || empty($email) || empty($mensaje)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Todos los campos son requeridos',
            ], 400);
        }

        if (!is_email($email)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Email invalido',
            ], 400);
        }

        $to = get_option('admin_email');
        $subject = '[' . get_bloginfo('name') . '] Nuevo mensaje de contacto';
        $body = "Nombre: {$nombre}\n";
        $body .= "Email: {$email}\n\n";
        $body .= "Mensaje:\n{$mensaje}";
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        $enviado = wp_mail($to, $subject, $body, $headers);

        if ($enviado) {
            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Mensaje enviado correctamente',
            ], 200);
        }

        return new \WP_REST_Response([
            'success' => false,
            'error' => 'Error al enviar el mensaje',
        ], 500);
    }

    /* 
     * Formatea un post de WordPress para la API
     */
    private static function formatearPost(\WP_Post $post): array
    {
        $categorias = wp_get_post_categories($post->ID, ['fields' => 'all']);
        $tags = wp_get_post_tags($post->ID);

        return [
            'id' => $post->ID,
            'slug' => $post->post_name,
            'title' => $post->post_title,
            'excerpt' => get_the_excerpt($post),
            'content' => apply_filters('the_content', $post->post_content),
            'date' => $post->post_date,
            'dateFormatted' => date_i18n('j \d\e F, Y', strtotime($post->post_date)),
            'author' => get_the_author_meta('display_name', $post->post_author),
            'featuredImage' => self::obtenerImagenDestacada($post->ID),
            'permalink' => get_permalink($post),
            'categories' => array_map(function ($cat) {
                return [
                    'id' => $cat->term_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                ];
            }, $categorias),
            'tags' => array_map(function ($tag) {
                return [
                    'id' => $tag->term_id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ];
            }, $tags ?: []),
            'readTime' => self::calcularTiempoLectura($post->post_content),
            'meta' => [
                'seoTitle' => get_post_meta($post->ID, '_glory_seo_title', true),
                'seoDesc' => get_post_meta($post->ID, '_glory_seo_desc', true),
                'seoCanonical' => get_post_meta($post->ID, '_glory_seo_canonical', true),
            ],
        ];
    }

    /* 
     * Obtiene la imagen destacada de un post
     */
    private static function obtenerImagenDestacada(int $postId): ?array
    {
        $thumbnailId = get_post_thumbnail_id($postId);

        if (!$thumbnailId) {
            return null;
        }

        return [
            'id' => $thumbnailId,
            'url' => get_the_post_thumbnail_url($postId, 'large'),
            'urlFull' => get_the_post_thumbnail_url($postId, 'full'),
            'alt' => get_post_meta($thumbnailId, '_wp_attachment_image_alt', true) ?: '',
        ];
    }

    /* 
     * Obtiene el logo del sitio
     */
    private static function obtenerLogo(): ?string
    {
        $customLogoId = get_theme_mod('custom_logo');

        if ($customLogoId) {
            return wp_get_attachment_image_url($customLogoId, 'full');
        }

        return null;
    }

    /* 
     * Calcula el tiempo de lectura estimado
     */
    private static function calcularTiempoLectura(string $content): string
    {
        $palabras = str_word_count(strip_tags($content));
        $minutos = max(1, ceil($palabras / 200));

        return $minutos . ' min';
    }
}
