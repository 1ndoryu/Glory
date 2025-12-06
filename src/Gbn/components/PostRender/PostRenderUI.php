<?php

/**
 * PostRenderUI - Componentes de interfaz de usuario para PostRender
 * 
 * Maneja la generación de elementos UI auxiliares como mensajes vacíos,
 * filtros de categoría y controles de paginación.
 * 
 * Parte del REFACTOR-003: División de PostRenderProcessor.php
 * 
 * @package Glory\Gbn\Components\PostRender
 */

namespace Glory\Gbn\Components\PostRender;

class PostRenderUI
{
    /**
     * Configuración del PostRender.
     */
    private array $config;

    /**
     * Clase CSS única de la instancia.
     */
    private string $instanceClass;

    /**
     * Constructor.
     * 
     * @param array $config Configuración del PostRender
     * @param string $instanceClass Clase CSS única de la instancia
     */
    public function __construct(array $config, string $instanceClass)
    {
        $this->config = $config;
        $this->instanceClass = $instanceClass;
    }

    /**
     * Renderiza mensaje cuando no hay posts.
     * 
     * @return string HTML del mensaje vacío
     */
    public function renderEmpty(): string
    {
        $postType = $this->config['postType'] ?? 'post';
        return sprintf(
            '<div class="gbn-post-render-empty"><p>No se encontraron %s.</p></div>',
            esc_html($postType)
        );
    }

    /**
     * Renderiza el filtro por categorías.
     * 
     * @param array $posts Posts encontrados
     * @return string HTML del filtro
     */
    public function renderCategoryFilter(array $posts): string
    {
        $postType = $this->config['postType'] ?? 'post';
        $taxonomy = PostItemRenderer::getTaxonomyForPostType($postType);

        // Obtener categorías de los posts mostrados
        $categories = [];
        foreach ($posts as $post) {
            $terms = get_the_terms($post->ID, $taxonomy);
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories[$term->slug] = $term->name;
                }
            }
        }

        if (empty($categories)) {
            return '';
        }

        // Generar HTML del filtro
        $filterId = $this->instanceClass . '-filter';
        $html = '<div class="gbn-pr-filter" id="' . esc_attr($filterId) . '" data-target=".' . esc_attr($this->instanceClass) . '">';
        $html .= '<button class="gbn-pr-filter-btn active" data-category="all">Todos</button>';
        
        foreach ($categories as $slug => $name) {
            $html .= sprintf(
                '<button class="gbn-pr-filter-btn" data-category="%s">%s</button>',
                esc_attr($slug),
                esc_html($name)
            );
        }
        
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza controles de paginación.
     * 
     * @param \WP_Query $query Query ejecutada
     * @return string HTML de paginación
     */
    public function renderPagination(\WP_Query $query): string
    {
        if ($query->max_num_pages <= 1) {
            return '';
        }

        $html = '<div class="gbn-pr-pagination" data-target=".' . esc_attr($this->instanceClass) . '">';
        
        // Botón anterior
        $html .= '<button class="gbn-pr-page-btn" data-page="prev" aria-label="Página anterior">&larr;</button>';
        
        // Indicador de página
        $html .= sprintf(
            '<span class="gbn-pr-page-info"><span class="current">1</span> / %d</span>',
            $query->max_num_pages
        );
        
        // Botón siguiente
        $html .= '<button class="gbn-pr-page-btn" data-page="next" aria-label="Página siguiente">&rarr;</button>';
        
        $html .= '</div>';

        return $html;
    }

    /**
     * Verifica si el filtro de categorías está habilitado.
     * 
     * @return bool True si está habilitado
     */
    public function isCategoryFilterEnabled(): bool
    {
        return !empty($this->config['categoryFilter']);
    }

    /**
     * Verifica si la paginación está habilitada.
     * 
     * @return bool True si está habilitada
     */
    public function isPaginationEnabled(): bool
    {
        return !empty($this->config['pagination']);
    }
}
