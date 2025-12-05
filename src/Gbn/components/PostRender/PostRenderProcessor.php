<?php

/**
 * PostRenderProcessor - Procesador de renderizado para PostRender
 * 
 * Maneja la lógica de clonación del template y llenado de campos semánticos.
 * Separa la lógica de renderizado del componente para mantener SRP.
 * 
 * @package Glory\Gbn\Components\PostRender
 */

namespace Glory\Gbn\Components\PostRender;

use Glory\Gbn\Services\PostRenderService;
use WP_Post;
use DOMDocument;
use DOMXPath;

class PostRenderProcessor
{
    /**
     * Configuración actual del procesador.
     */
    private array $config;

    /**
     * Clase CSS única para esta instancia (scoped CSS).
     */
    private string $instanceClass;

    /**
     * Template HTML del PostItem (se clona por cada post).
     */
    private string $template;

    /**
     * Constructor.
     * 
     * @param array $config Configuración del PostRender
     * @param string $template HTML del PostItem template
     */
    public function __construct(array $config, string $template)
    {
        $this->config = $config;
        $this->template = $template;
        $this->instanceClass = 'gbn-pr-' . substr(md5(uniqid('', true)), 0, 8);
    }

    /**
     * Renderiza el componente PostRender completo.
     * 
     * @return string HTML renderizado
     */
    public function render(): string
    {
        // 1. Ejecutar query
        $query = PostRenderService::query($this->config);
        
        if (!$query->have_posts()) {
            return $this->renderEmpty();
        }

        // 2. Generar CSS scoped
        $css = $this->generateScopedCss();

        // 3. Renderizar items
        $items = '';
        while ($query->have_posts()) {
            $query->the_post();
            $items .= $this->renderItem(get_post());
        }
        wp_reset_postdata();

        // 4. Generar filtro de categorías si está habilitado
        $categoryFilter = $this->config['categoryFilter'] 
            ? $this->renderCategoryFilter($query->posts) 
            : '';

        // 5. Construir contenedor
        $containerStyles = $this->getContainerStyles();
        $containerAttrs = $this->getContainerAttributes();

        $html = '';
        
        // CSS inline
        if (!empty($css)) {
            $html .= '<style>' . $css . '</style>';
        }

        // Filtro de categorías
        $html .= $categoryFilter;

        // Contenedor
        $html .= sprintf(
            '<div class="%s" %s style="%s">%s</div>',
            esc_attr($this->instanceClass . ' gbn-post-render'),
            $containerAttrs,
            esc_attr($containerStyles),
            $items
        );

        // Paginación
        if ($this->config['pagination'] ?? false) {
            $html .= $this->renderPagination($query);
        }

        return $html;
    }

    /**
     * Renderiza un item individual con el template.
     * 
     * @param WP_Post $post El post actual
     * @return string HTML del item
     */
    private function renderItem(WP_Post $post): string
    {
        $html = $this->template;

        // Procesar campos semánticos [gloryPostField="xxx"]
        $html = $this->processPostFields($html, $post);

        // Agregar atributos de item
        $html = $this->addItemAttributes($html, $post);

        return $html;
    }

    /**
     * Procesa todos los campos [gloryPostField] en el template.
     * 
     * @param string $html HTML del template
     * @param WP_Post $post Post actual
     * @return string HTML con campos rellenados
     */
    private function processPostFields(string $html, WP_Post $post): string
    {
        // Usar regex para encontrar elementos con gloryPostField
        $pattern = '/<([a-z0-9]+)([^>]*)\s+gloryPostField="([^"]+)"([^>]*)>([^<]*)<\/\1>/i';
        
        return preg_replace_callback($pattern, function ($matches) use ($post) {
            $tag = $matches[1];
            $attrsBefore = $matches[2];
            $fieldType = $matches[3];
            $attrsAfter = $matches[4];
            $originalContent = $matches[5];

            // Parsear opciones del campo si existen
            $config = $this->parseFieldConfig($attrsBefore . $attrsAfter);
            $config['fieldType'] = $fieldType;

            // Renderizar contenido
            $content = PostFieldComponent::renderField($post, $config);
            
            // Si no hay contenido, usar el texto original (placeholder)
            if (empty($content)) {
                $content = $originalContent;
            }

            // Caso especial: imágenes
            if ($fieldType === 'featuredImage' && has_post_thumbnail($post)) {
                return $this->renderFeaturedImageContainer($post, $attrsBefore, $attrsAfter, $config);
            }

            // Caso especial: enlaces
            if ($fieldType === 'link' || $tag === 'a') {
                $attrsBefore .= ' href="' . esc_url(get_permalink($post)) . '"';
            }

            return sprintf(
                '<%s%s gloryPostField="%s"%s>%s</%s>',
                $tag,
                $attrsBefore,
                esc_attr($fieldType),
                $attrsAfter,
                $content,
                $tag
            );
        }, $html);
    }

    /**
     * Renderiza el contenedor de imagen destacada.
     * 
     * @param WP_Post $post Post actual
     * @param string $attrsBefore Atributos antes de gloryPostField
     * @param string $attrsAfter Atributos después de gloryPostField
     * @param array $config Configuración del campo
     * @return string HTML del contenedor de imagen
     */
    private function renderFeaturedImageContainer(WP_Post $post, string $attrsBefore, string $attrsAfter, array $config): string
    {
        $size = $config['imageSize'] ?? 'medium';
        $imageHtml = get_the_post_thumbnail($post, $size, [
            'loading' => 'lazy',
            'style' => 'width: 100%; height: 100%; object-fit: cover;',
        ]);

        return sprintf(
            '<div%s gloryPostField="featuredImage"%s>%s</div>',
            $attrsBefore,
            $attrsAfter,
            $imageHtml
        );
    }

    /**
     * Parsea la configuración del campo desde los atributos opciones="...".
     * 
     * @param string $attrs String de atributos HTML
     * @return array Configuración parseada
     */
    private function parseFieldConfig(string $attrs): array
    {
        $config = [];
        
        // Buscar atributo opciones
        if (preg_match('/opciones="([^"]+)"/', $attrs, $matches)) {
            // Parsear formato key: value, key: value
            preg_match_all("/(\w+):\s*'([^']*)'|(\w+):\s*([^,\s]+)/", $matches[1], $opts);
            
            foreach ($opts[0] as $i => $match) {
                $key = !empty($opts[1][$i]) ? $opts[1][$i] : $opts[3][$i];
                $value = !empty($opts[2][$i]) ? $opts[2][$i] : $opts[4][$i];
                $config[$key] = $value;
            }
        }

        return $config;
    }

    /**
     * Agrega atributos al item (data-post-id, clases, etc).
     * 
     * @param string $html HTML del item
     * @param WP_Post $post Post actual
     * @return string HTML con atributos agregados
     */
    private function addItemAttributes(string $html, WP_Post $post): string
    {
        // Agregar data-post-id al primer elemento con gloryPostItem
        $pattern = '/(<[^>]+)\s*(gloryPostItem)([^>]*>)/i';
        
        return preg_replace_callback($pattern, function ($matches) use ($post) {
            $dataAttr = sprintf(' data-post-id="%d"', $post->ID);
            return $matches[1] . $dataAttr . ' ' . $matches[2] . $matches[3];
        }, $html, 1);
    }

    /**
     * Genera estilos CSS scoped para esta instancia.
     * 
     * @return string CSS generado
     */
    private function generateScopedCss(): string
    {
        $css = [];
        $class = '.' . $this->instanceClass;

        // Layout del contenedor según displayMode
        $displayMode = $this->config['displayMode'] ?? 'grid';
        
        if ($displayMode === 'grid') {
            $columns = (int) ($this->config['gridColumns'] ?? 3);
            $gap = $this->config['gap'] ?? '20px';
            
            $css[] = "{$class} { display: grid; grid-template-columns: repeat({$columns}, 1fr); gap: {$gap}; }";
            
            // Responsive: 2 columnas en tablet, 1 en móvil
            $css[] = "@media (max-width: 768px) { {$class} { grid-template-columns: repeat(2, 1fr); } }";
            $css[] = "@media (max-width: 480px) { {$class} { grid-template-columns: 1fr; } }";
        } elseif ($displayMode === 'flex') {
            $direction = $this->config['flexDirection'] ?? 'row';
            $wrap = $this->config['flexWrap'] ?? 'wrap';
            $align = $this->config['alignItems'] ?? 'stretch';
            $justify = $this->config['justifyContent'] ?? 'flex-start';
            $gap = $this->config['gap'] ?? '20px';
            
            $css[] = "{$class} { display: flex; flex-direction: {$direction}; flex-wrap: {$wrap}; align-items: {$align}; justify-content: {$justify}; gap: {$gap}; }";
        }

        // Estilos de item (PostItem)
        $css[] = "{$class} [gloryPostItem] { transition: box-shadow 0.3s ease, transform 0.3s ease; }";
        
        // Efecto hover del item
        $hoverEffect = $this->config['hoverEffect'] ?? 'none';
        if ($hoverEffect === 'lift') {
            $css[] = "{$class} [gloryPostItem]:hover { box-shadow: 0 8px 16px rgba(0,0,0,0.1); transform: translateY(-4px); }";
        } elseif ($hoverEffect === 'scale') {
            $css[] = "{$class} [gloryPostItem]:hover { transform: scale(1.02); }";
        } elseif ($hoverEffect === 'glow') {
            $css[] = "{$class} [gloryPostItem]:hover { box-shadow: 0 0 20px rgba(59, 130, 246, 0.3); }";
        }

        return implode("\n", $css);
    }

    /**
     * Genera estilos inline para el contenedor.
     * 
     * @return string Estilos inline
     */
    private function getContainerStyles(): string
    {
        $styles = [];

        // Padding/Margin del contenedor
        if (!empty($this->config['padding'])) {
            $styles[] = 'padding: ' . $this->config['padding'];
        }
        if (!empty($this->config['margin'])) {
            $styles[] = 'margin: ' . $this->config['margin'];
        }

        // Border
        if (!empty($this->config['hasBorder']) && $this->config['hasBorder']) {
            if (!empty($this->config['borderWidth'])) {
                $styles[] = 'border-width: ' . $this->config['borderWidth'];
            }
            if (!empty($this->config['borderStyle'])) {
                $styles[] = 'border-style: ' . $this->config['borderStyle'];
            }
            if (!empty($this->config['borderColor'])) {
                $styles[] = 'border-color: ' . $this->config['borderColor'];
            }
        }
        if (!empty($this->config['borderRadius'])) {
            $styles[] = 'border-radius: ' . $this->config['borderRadius'];
        }

        return implode('; ', $styles);
    }

    /**
     * Genera atributos data-* para el contenedor.
     * 
     * @return string Atributos HTML
     */
    private function getContainerAttributes(): string
    {
        $attrs = [
            'data-post-type="' . esc_attr($this->config['postType'] ?? 'post') . '"',
            'data-posts-per-page="' . esc_attr($this->config['postsPerPage'] ?? 6) . '"',
        ];

        return implode(' ', $attrs);
    }

    /**
     * Renderiza mensaje cuando no hay posts.
     * 
     * @return string HTML del mensaje vacío
     */
    private function renderEmpty(): string
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
    private function renderCategoryFilter(array $posts): string
    {
        $postType = $this->config['postType'] ?? 'post';
        $taxonomy = $postType === 'post' ? 'category' : ($postType . '_category');
        
        // Verificar si la taxonomía existe
        if (!taxonomy_exists($taxonomy)) {
            // Intentar con la taxonomía principal
            $taxonomies = get_object_taxonomies($postType);
            $taxonomy = !empty($taxonomies) ? $taxonomies[0] : 'category';
        }

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
    private function renderPagination(\WP_Query $query): string
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
     * Obtiene la clase de instancia generada.
     * 
     * @return string Clase CSS única
     */
    public function getInstanceClass(): string
    {
        return $this->instanceClass;
    }
}
