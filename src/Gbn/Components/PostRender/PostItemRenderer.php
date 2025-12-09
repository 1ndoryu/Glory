<?php

/**
 * PostItemRenderer - Renderizador de items individuales de PostRender
 * 
 * Maneja la lógica de renderizado de cada item (post) individual,
 * incluyendo la clonación del template y agregado de atributos.
 * 
 * Parte del REFACTOR-003: División de PostRenderProcessor.php
 * 
 * @package Glory\Gbn\Components\PostRender
 */

namespace Glory\Gbn\Components\PostRender;

use WP_Post;

class PostItemRenderer
{
    /**
     * Template HTML del PostItem.
     */
    private string $template;

    /**
     * Configuración del PostRender.
     */
    private array $config;

    /**
     * Constructor.
     * 
     * @param string $template HTML del template
     * @param array $config Configuración del PostRender
     */
    public function __construct(string $template, array $config)
    {
        $this->template = $template;
        $this->config = $config;
    }

    /**
     * Renderiza un item individual con el template.
     * 
     * @param WP_Post $post El post actual
     * @return string HTML del item
     */
    public function render(WP_Post $post): string
    {
        $html = $this->template;

        // Procesar campos semánticos [gloryPostField="xxx"]
        $html = PostFieldProcessor::process($html, $post);

        // Agregar atributos de item
        $html = $this->addItemAttributes($html, $post);

        return $html;
    }

    /**
     * Agrega atributos al item (data-post-id, data-categories, clases, etc).
     * 
     * @param string $html HTML del item
     * @param WP_Post $post Post actual
     * @return string HTML con atributos agregados
     */
    private function addItemAttributes(string $html, WP_Post $post): string
    {
        // Obtener categorías del post
        $postType = $this->config['postType'] ?? 'post';
        $taxonomy = $postType === 'post' ? 'category' : ($postType . '_category');

        // Buscar taxonomía válida
        if (!taxonomy_exists($taxonomy)) {
            $taxonomies = get_object_taxonomies($postType);
            $taxonomy = !empty($taxonomies) ? $taxonomies[0] : 'category';
        }

        $terms = get_the_terms($post->ID, $taxonomy);
        $catSlugs = '';
        if ($terms && !is_wp_error($terms)) {
            $catSlugs = implode(',', array_map(fn($t) => $t->slug, $terms));
        }

        // Agregar data-post-id y data-categories al primer elemento con gloryPostItem
        $pattern = '/(<[^>]+)\s*(gloryPostItem)([^>]*>)/i';

        // Obtener permalink del post para hacer clickeable la tarjeta
        $permalink = get_permalink($post);

        return preg_replace_callback($pattern, function ($matches) use ($post, $catSlugs, $permalink) {
            $dataAttrs = sprintf(
                ' data-post-id="%d" data-categories="%s" data-permalink="%s"',
                $post->ID,
                esc_attr($catSlugs),
                esc_url($permalink)
            );
            return $matches[1] . $dataAttrs . ' ' . $matches[2] . $matches[3];
        }, $html, 1);
    }

    /**
     * Obtiene la taxonomía principal para un post type.
     * 
     * @param string $postType Tipo de post
     * @return string Nombre de la taxonomía
     */
    public static function getTaxonomyForPostType(string $postType): string
    {
        $taxonomy = $postType === 'post' ? 'category' : ($postType . '_category');

        if (!taxonomy_exists($taxonomy)) {
            $taxonomies = get_object_taxonomies($postType);
            $taxonomy = !empty($taxonomies) ? $taxonomies[0] : 'category';
        }

        return $taxonomy;
    }
}
