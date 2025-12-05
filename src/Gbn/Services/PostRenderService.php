<?php

/**
 * PostRenderService - Lógica de WP_Query para PostRender
 * 
 * Este servicio encapsula toda la lógica de consulta a WordPress,
 * extraída y simplificada del ContentRender original.
 * 
 * @package Glory\Gbn\Services
 */

namespace Glory\Gbn\Services;

use WP_Query;
use WP_Post;

class PostRenderService
{
    /**
     * Duración de cache por defecto (5 minutos).
     */
    private const CACHE_EXPIRATION = 5 * MINUTE_IN_SECONDS;

    /**
     * Ejecuta una consulta WP_Query basándose en la configuración del componente.
     * 
     * @param array $config Configuración del PostRender
     * @param bool $useCache Si se debe usar cache (false para preview/editor)
     * @return WP_Query La consulta ejecutada
     */
    public static function query(array $config, bool $useCache = true): WP_Query
    {
        $args = self::buildQueryArgs($config);
        
        // No usar cache en modo preview o si se desactiva explícitamente
        if (!$useCache || isset($config['noCache']) || is_admin() || self::isEditorMode()) {
            return new WP_Query($args);
        }

        // Generar key de cache única basada en los argumentos
        $cacheKey = self::generateCacheKey($args);
        $cached = get_transient($cacheKey);

        if ($cached !== false && $cached instanceof WP_Query) {
            return $cached;
        }

        // Ejecutar query y cachear
        $query = new WP_Query($args);
        
        // Solo cachear si hay resultados
        if ($query->have_posts()) {
            set_transient($cacheKey, $query, self::CACHE_EXPIRATION);
        }

        return $query;
    }

    /**
     * Genera una key de cache única basada en los argumentos de query.
     * 
     * @param array $args Argumentos de WP_Query
     * @return string Key de cache
     */
    private static function generateCacheKey(array $args): string
    {
        $postType = $args['post_type'] ?? 'post';
        $hash = md5(serialize($args));
        return 'gbn_pr_' . $postType . '_' . $hash;
    }

    /**
     * Detecta si estamos en modo editor GBN.
     * 
     * @return bool
     */
    private static function isEditorMode(): bool
    {
        // Si el usuario puede editar, probablemente está en el editor
        return current_user_can('edit_posts') && 
               (wp_doing_ajax() || isset($_GET['preview']) || isset($_GET['gbn-edit']));
    }

    /**
     * Obtiene solo un preview limitado de posts (para el editor GBN).
     * 
     * @param array $config Configuración del PostRender
     * @param int $limit Límite de posts para preview (default: 3)
     * @return array Array de WP_Post
     */
    public static function preview(array $config, int $limit = 3): array
    {
        $config['postsPerPage'] = $limit;
        $query = self::query($config);
        return $query->posts ?? [];
    }

    /**
     * Construye los argumentos para WP_Query a partir de la config.
     * 
     * @param array $config Configuración del componente
     * @return array Argumentos para WP_Query
     */
    private static function buildQueryArgs(array $config): array
    {
        $args = [
            'post_type'           => $config['postType'] ?? 'post',
            'posts_per_page'      => $config['postsPerPage'] ?? 6,
            'ignore_sticky_posts' => true,
            'post_status'         => $config['status'] ?? 'publish',
        ];

        // Paginación
        $paged = $config['paged'] ?? (get_query_var('paged') ?: 1);
        $args['paged'] = (int) $paged;

        // Offset
        if (!empty($config['offset']) && (int) $config['offset'] > 0) {
            $args['offset'] = (int) $config['offset'];
        }

        // Orden
        $orderBy = $config['orderBy'] ?? 'date';
        $order = strtoupper($config['order'] ?? 'DESC');
        
        // Soporte para meta_value orden
        if (strpos($orderBy, 'meta:') === 0) {
            $metaKey = substr($orderBy, 5);
            $args['meta_key'] = $metaKey;
            $args['orderby'] = 'meta_value';
        } else {
            $args['orderby'] = $orderBy;
        }
        $args['order'] = in_array($order, ['ASC', 'DESC']) ? $order : 'DESC';

        // Posts específicos
        if (!empty($config['postIn'])) {
            $ids = self::parseIds($config['postIn']);
            if (!empty($ids)) {
                $args['post__in'] = $ids;
                // Respetar orden de IDs si no se especifica otro
                if (empty($config['orderBy']) || $config['orderBy'] === 'post__in') {
                    $args['orderby'] = 'post__in';
                }
            }
        }

        // Excluir posts
        if (!empty($config['postNotIn'])) {
            $ids = self::parseIds($config['postNotIn']);
            if (!empty($ids)) {
                $args['post__not_in'] = $ids;
            }
        }

        // Taxonomy Query
        if (!empty($config['taxonomyQuery']) && is_array($config['taxonomyQuery'])) {
            $taxQuery = self::buildTaxQuery($config['taxonomyQuery']);
            if (!empty($taxQuery)) {
                $args['tax_query'] = $taxQuery;
            }
        }

        return $args;
    }

    /**
     * Parsea un string de IDs separados por coma a un array de enteros.
     * 
     * @param string|array $input IDs como string (comma-separated) o array
     * @return array Array de IDs enteros válidos
     */
    private static function parseIds($input): array
    {
        if (is_array($input)) {
            return array_filter(array_map('absint', $input));
        }
        
        $ids = explode(',', (string) $input);
        return array_filter(array_map(fn($id) => absint(trim($id)), $ids));
    }

    /**
     * Construye la tax_query a partir de la configuración.
     * 
     * @param array $taxConfig Configuración de taxonomía
     * @return array tax_query formateada para WP_Query
     */
    private static function buildTaxQuery(array $taxConfig): array
    {
        $taxQuery = [];
        
        foreach ($taxConfig as $query) {
            if (empty($query['taxonomy']) || empty($query['terms'])) {
                continue;
            }

            $terms = is_array($query['terms']) 
                ? $query['terms'] 
                : explode(',', (string) $query['terms']);
            
            $terms = array_filter(array_map('trim', $terms));
            
            if (empty($terms)) {
                continue;
            }

            $taxQuery[] = [
                'taxonomy' => sanitize_key($query['taxonomy']),
                'field'    => $query['field'] ?? 'slug',
                'terms'    => $terms,
                'operator' => $query['operator'] ?? 'IN',
            ];
        }

        if (!empty($taxQuery) && count($taxQuery) > 1) {
            $taxQuery['relation'] = $taxConfig['relation'] ?? 'AND';
        }

        return $taxQuery;
    }

    /**
     * Obtiene los tipos de post disponibles para el selector.
     * 
     * @return array Opciones formateadas para Option::select()
     */
    public static function getPostTypeOptions(): array
    {
        $postTypes = get_post_types(['public' => true], 'objects');
        $options = [];

        foreach ($postTypes as $postType) {
            // Excluir attachment
            if ($postType->name === 'attachment') {
                continue;
            }

            $options[] = [
                'valor' => $postType->name,
                'etiqueta' => $postType->labels->singular_name,
            ];
        }

        return $options;
    }

    /**
     * Limpia la caché relacionada a PostRender cuando cambia un post.
     * 
     * @param int $postId ID del post modificado
     */
    public static function clearCacheOnPostChange(int $postId): void
    {
        if (wp_is_post_revision($postId)) {
            return;
        }

        $post = get_post($postId);
        if (!$post) {
            return;
        }

        self::clearCacheForPostType($post->post_type);
    }

    /**
     * Limpia caché de un post_type específico.
     * 
     * @param string $postType Tipo de post
     */
    public static function clearCacheForPostType(string $postType): void
    {
        global $wpdb;

        $prefix = '_transient_gbn_pr_' . $postType . '_';
        $timeoutPrefix = '_transient_timeout_gbn_pr_' . $postType . '_';

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like($prefix) . '%',
            $wpdb->esc_like($timeoutPrefix) . '%'
        ));
    }

    /**
     * Obtiene lista de taxonomías de un post_type.
     * 
     * @param string $postType Tipo de post
     * @return array Taxonomías con sus términos
     */
    public static function getTaxonomiesForPostType(string $postType): array
    {
        $taxonomies = get_object_taxonomies($postType, 'objects');
        $result = [];

        foreach ($taxonomies as $taxonomy) {
            if (!$taxonomy->public) {
                continue;
            }

            $terms = get_terms([
                'taxonomy' => $taxonomy->name,
                'hide_empty' => true,
            ]);

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            $result[] = [
                'name' => $taxonomy->name,
                'label' => $taxonomy->labels->singular_name,
                'terms' => array_map(fn($term) => [
                    'slug' => $term->slug,
                    'name' => $term->name,
                    'count' => $term->count,
                ], $terms),
            ];
        }

        return $result;
    }
}
