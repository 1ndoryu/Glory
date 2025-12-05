<?php

/**
 * PostRenderHandler - Endpoint AJAX para preview de PostRender en el editor
 * 
 * @package Glory\Gbn\Ajax\Handlers
 */

namespace Glory\Gbn\Ajax\Handlers;

use Glory\Gbn\Services\PostRenderService;

class PostRenderHandler
{
    /**
     * Obtiene un preview de posts para el editor GBN.
     * 
     * Endpoint: wp_ajax_gbn_post_render_preview
     */
    public static function getPreview(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Sin permisos para editar']);
        }

        $config = isset($_POST['config']) ? wp_unslash($_POST['config']) : '{}';
        $config = json_decode((string) $config, true);

        if (!is_array($config)) {
            $config = [];
        }

        // Límite de posts para preview (máximo 5 para rendimiento)
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 3;
        $limit = min($limit, 5);

        try {
            $posts = PostRenderService::preview($config, $limit);

            // Formatear posts para el frontend
            $formattedPosts = array_map(function ($post) {
                return self::formatPostForPreview($post);
            }, $posts);

            wp_send_json_success([
                'posts' => $formattedPosts,
                'count' => count($formattedPosts),
                'postType' => $config['postType'] ?? 'post',
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene los post types disponibles para el selector.
     * 
     * Endpoint: wp_ajax_gbn_get_post_types
     */
    public static function getPostTypes(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $postTypes = PostRenderService::getPostTypeOptions();
        wp_send_json_success(['postTypes' => $postTypes]);
    }

    /**
     * Obtiene las taxonomías de un post_type para filtrar.
     * 
     * Endpoint: wp_ajax_gbn_get_taxonomies
     */
    public static function getTaxonomies(): void
    {
        check_ajax_referer('glory_gbn_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $postType = isset($_POST['postType']) ? sanitize_key($_POST['postType']) : 'post';
        $taxonomies = PostRenderService::getTaxonomiesForPostType($postType);

        wp_send_json_success(['taxonomies' => $taxonomies]);
    }

    /**
     * Formatea un WP_Post para el preview del editor.
     * Incluye todos los campos semánticos que el PostField puede mostrar.
     * 
     * @param \WP_Post $post
     * @return array
     */
    private static function formatPostForPreview(\WP_Post $post): array
    {
        $thumbnailId = get_post_thumbnail_id($post->ID);
        $thumbnailUrl = $thumbnailId ? wp_get_attachment_image_url($thumbnailId, 'medium') : '';

        $author = get_userdata($post->post_author);
        $authorName = $author ? $author->display_name : '';
        $authorAvatar = $author ? get_avatar_url($author->ID, ['size' => 64]) : '';

        $categories = get_the_category($post->ID);
        $categoryList = array_map(fn($cat) => [
            'id' => $cat->term_id,
            'name' => $cat->name,
            'slug' => $cat->slug,
            'link' => get_category_link($cat->term_id),
        ], $categories);

        $tags = get_the_tags($post->ID);
        $tagList = $tags ? array_map(fn($tag) => [
            'id' => $tag->term_id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'link' => get_tag_link($tag->term_id),
        ], $tags) : [];

        return [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'excerpt' => wp_trim_words(get_the_excerpt($post), 20),
            'content' => apply_filters('the_content', $post->post_content),
            'link' => get_permalink($post->ID),
            'date' => get_the_date('d M, Y', $post),
            'dateISO' => get_the_date('c', $post),
            'author' => $authorName,
            'authorAvatar' => $authorAvatar,
            'featuredImage' => $thumbnailUrl,
            'featuredImageId' => $thumbnailId,
            'categories' => $categoryList,
            'tags' => $tagList,
            'commentCount' => (int) get_comments_number($post->ID),
            'postType' => $post->post_type,
        ];
    }

    /**
     * Paginación AJAX para el frontend.
     * Este endpoint está disponible para usuarios anónimos.
     * 
     * Endpoint: wp_ajax_gbn_post_render_paginate
     *           wp_ajax_nopriv_gbn_post_render_paginate
     */
    public static function paginate(): void
    {
        // No requiere permisos de edición - es para el frontend público
        $config = isset($_POST['config']) ? wp_unslash($_POST['config']) : '{}';
        $config = json_decode((string) $config, true);

        if (!is_array($config)) {
            $config = [];
        }

        // Obtener número de página
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $config['paged'] = $page;

        try {
            $posts = PostRenderService::query($config)->posts;

            if (empty($posts)) {
                wp_send_json_success([
                    'html' => '<div class="gbn-post-render-empty"><p>No se encontraron más posts.</p></div>',
                    'page' => $page,
                    'count' => 0,
                ]);
                return;
            }

            // Generar HTML de los items
            $html = self::renderPaginatedItems($posts, $config);

            wp_send_json_success([
                'html' => $html,
                'page' => $page,
                'count' => count($posts),
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Renderiza los items para paginación.
     * Genera HTML simple sin template (para uso frontend).
     * 
     * @param array $posts Array de WP_Post
     * @param array $config Configuración
     * @return string HTML de los items
     */
    private static function renderPaginatedItems(array $posts, array $config): string
    {
        $html = '';

        foreach ($posts as $post) {
            $thumbnailUrl = get_the_post_thumbnail_url($post->ID, 'medium');
            $excerpt = wp_trim_words(get_the_excerpt($post), 20);
            $categories = get_the_category($post->ID);
            $catSlugs = array_map(fn($c) => $c->slug, $categories);
            
            $html .= sprintf(
                '<article gloryPostItem class="gbn-post-item" data-post-id="%d" data-categories="%s">',
                $post->ID,
                esc_attr(implode(',', $catSlugs))
            );

            // Imagen destacada
            if ($thumbnailUrl) {
                $html .= sprintf(
                    '<div gloryPostField="featuredImage" class="gbn-post-image"><img src="%s" alt="%s" loading="lazy" /></div>',
                    esc_url($thumbnailUrl),
                    esc_attr(get_the_title($post))
                );
            }

            // Título
            $html .= sprintf(
                '<h3 gloryPostField="title"><a href="%s">%s</a></h3>',
                esc_url(get_permalink($post)),
                esc_html(get_the_title($post))
            );

            // Excerpt
            if ($excerpt) {
                $html .= sprintf(
                    '<p gloryPostField="excerpt">%s</p>',
                    esc_html($excerpt)
                );
            }

            // Fecha
            $html .= sprintf(
                '<span gloryPostField="date" class="gbn-post-date">%s</span>',
                esc_html(get_the_date('d M, Y', $post))
            );

            $html .= '</article>';
        }

        return $html;
    }
}

