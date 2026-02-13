<?php

namespace Glory\Services;

use Glory\Core\DefaultContentRegistry;

/**
 * ReactContentProvider - Provee contenido de WordPress a React
 * 
 * Este servicio permite inyectar contenido definido en DefaultContentManager
 * a componentes React. Funciona de dos formas:
 * 
 * 1. Via props: ReactIslands::render('Island', ReactContentProvider::getContent('post_type'))
 * 2. Via window global: ReactContentProvider::injectGlobal() (para acceso desde cualquier componente)
 * 
 * Uso:
 * En PHP:
 *   ReactContentProvider::register('blog', 'post', ['posts_per_page' => 6]);
 *   ReactContentProvider::injectGlobal();
 * 
 * En React:
 *   const posts = useContent('blog'); // Array de posts
 */
class ReactContentProvider
{
    // Contenido registrado para inyectar
    private static array $registeredContent = [];

    // Si ya se inyectó el contenido global
    private static bool $injected = false;

    /**
     * Registra contenido para ser inyectado a React.
     * 
     * @param string $key Clave unica para acceder al contenido en React
     * @param string $postType Tipo de post de WordPress
     * @param array $queryArgs Argumentos para WP_Query
     */
    public static function register(string $key, string $postType, array $queryArgs = []): void
    {
        $defaultArgs = [
            'post_type' => $postType,
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $args = array_merge($defaultArgs, $queryArgs);

        self::$registeredContent[$key] = [
            'type' => 'query',
            'args' => $args,
        ];
    }

    /**
     * Registra contenido estatico (sin query).
     * 
     * @param string $key Clave unica
     * @param array $data Datos a inyectar
     */
    public static function registerStatic(string $key, array $data): void
    {
        self::$registeredContent[$key] = [
            'type' => 'static',
            'data' => $data,
        ];
    }

    /**
     * Registra contenido desde DefaultContentManager.
     * Usa las definiciones del registro para obtener los posts.
     * 
     * @param string $key Clave unica
     * @param string $postType Tipo de post definido en DefaultContentManager
     * @param array $queryArgs Argumentos adicionales para WP_Query
     */
    public static function registerFromDefaults(string $key, string $postType, array $queryArgs = []): void
    {
        // Obtener los slugs definidos en DefaultContentManager
        $definiciones = DefaultContentRegistry::getDefiniciones();
        $slugs = [];

        if (isset($definiciones[$postType]['posts'])) {
            foreach ($definiciones[$postType]['posts'] as $post) {
                if (isset($post['slugDefault'])) {
                    $slugs[] = $post['slugDefault'];
                }
            }
        }

        $defaultArgs = [
            'post_type' => $postType,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        // Si hay slugs definidos, filtrar por ellos
        if (!empty($slugs)) {
            $defaultArgs['post_name__in'] = $slugs;
        }

        $args = array_merge($defaultArgs, $queryArgs);

        self::$registeredContent[$key] = [
            'type' => 'query',
            'args' => $args,
        ];
    }

    /**
     * Obtiene el contenido preparado para React.
     * 
     * @param string $key Clave del contenido
     * @return array Datos formateados para React
     */
    public static function getContent(string $key): array
    {
        if (!isset(self::$registeredContent[$key])) {
            return [];
        }

        $config = self::$registeredContent[$key];

        if ($config['type'] === 'static') {
            return $config['data'];
        }

        // Ejecutar query
        return self::executeQuery($config['args']);
    }

    /**
     * Obtiene todo el contenido registrado.
     * 
     * @return array Mapa de key => contenido
     */
    public static function getAllContent(): array
    {
        $result = [];

        foreach (array_keys(self::$registeredContent) as $key) {
            $result[$key] = self::getContent($key);
        }

        return $result;
    }

    /**
     * Ejecuta una WP_Query y formatea los resultados para React.
     */
    private static function executeQuery(array $args): array
    {
        $query = new \WP_Query($args);
        $posts = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $posts[] = self::formatPost(get_post());
            }
            wp_reset_postdata();
        }

        return $posts;
    }

    /**
     * Formatea un post de WordPress para React.
     */
    private static function formatPost(\WP_Post $post): array
    {
        $featuredImage = null;
        $thumbnailId = get_post_thumbnail_id($post->ID);

        if ($thumbnailId) {
            $featuredImage = [
                'id' => $thumbnailId,
                'url' => get_the_post_thumbnail_url($post->ID, 'large'),
                'alt' => get_post_meta($thumbnailId, '_wp_attachment_image_alt', true) ?: $post->post_title,
            ];
        }

        return [
            'id' => (string) $post->ID,
            'slug' => $post->post_name,
            'title' => get_the_title($post),
            'excerpt' => get_the_excerpt($post),
            'content' => apply_filters('the_content', $post->post_content),
            'date' => get_the_date('Y-m-d', $post),
            'dateFormatted' => get_the_date('', $post),
            'author' => get_the_author_meta('display_name', $post->post_author),
            'featuredImage' => $featuredImage,
            'permalink' => get_permalink($post),
            'categories' => self::getTerms($post->ID, 'category'),
            'tags' => self::getTerms($post->ID, 'post_tag'),
            'meta' => self::getPostMeta($post->ID),
            'readTime' => self::calculateReadTime($post->post_content),
        ];
    }

    /**
     * Obtiene terminos de una taxonomia para un post.
     */
    private static function getTerms(int $postId, string $taxonomy): array
    {
        $terms = get_the_terms($postId, $taxonomy);

        if (!$terms || is_wp_error($terms)) {
            return [];
        }

        return array_map(function ($term) {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ];
        }, $terms);
    }

    /**
     * Obtiene meta relevante de un post.
     */
    private static function getPostMeta(int $postId): array
    {
        // Solo incluir meta publico (sin _ prefix)
        $allMeta = get_post_meta($postId);
        $publicMeta = [];

        foreach ($allMeta as $key => $values) {
            // Excluir meta privado
            if (strpos($key, '_') === 0) {
                continue;
            }
            $publicMeta[$key] = count($values) === 1 ? $values[0] : $values;
        }

        return $publicMeta;
    }

    /**
     * Calcula el tiempo estimado de lectura.
     */
    private static function calculateReadTime(string $content): string
    {
        $wordCount = str_word_count(strip_tags($content));
        $minutes = max(1, ceil($wordCount / 200)); // 200 palabras por minuto
        return $minutes . ' min';
    }

    /**
     * Inyecta el contenido como variable global de JavaScript.
     * Llamar en wp_head o antes de los scripts de React.
     */
    public static function injectGlobal(): void
    {
        if (self::$injected) {
            return;
        }

        $content = self::getAllContent();

        if (empty($content)) {
            return;
        }

        add_action('wp_head', function () use ($content) {
            $json = wp_json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
            echo '<script>window.__GLORY_CONTENT__ = ' . $json . ';</script>' . PHP_EOL;
        }, 1);

        self::$injected = true;
    }

    /**
     * Resetea el estado (util para tests).
     */
    public static function reset(): void
    {
        self::$registeredContent = [];
        self::$injected = false;
    }
}
