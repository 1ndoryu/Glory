<?php

/**
 * PostFieldComponent - Campos semánticos para PostRender
 * 
 * Define campos que se llenan automáticamente con datos del post.
 * Usa el Strategy Pattern para extensibilidad de tipos de campo.
 * 
 * @package Glory\Gbn\Components\PostRender
 */

namespace Glory\Gbn\Components\PostRender;

use Glory\Gbn\Components\AbstractComponent;
use Glory\Gbn\Schema\SchemaBuilder;
use Glory\Gbn\Schema\Option;
use WP_Post;

class PostFieldComponent extends AbstractComponent
{
    protected string $id = 'postField';
    protected string $label = 'Post Field';

    /**
     * Campos semánticos disponibles y sus estrategias.
     * Cada campo define: label, tag por defecto, y método de renderizado.
     */
    private const FIELD_TYPES = [
        'title' => [
            'label' => 'Título',
            'defaultTag' => 'h3',
            'cssProperty' => null,
        ],
        'featuredImage' => [
            'label' => 'Imagen Destacada',
            'defaultTag' => 'div',
            'cssProperty' => null,
        ],
        'excerpt' => [
            'label' => 'Extracto',
            'defaultTag' => 'p',
            'cssProperty' => null,
        ],
        'content' => [
            'label' => 'Contenido',
            'defaultTag' => 'div',
            'cssProperty' => null,
        ],
        'date' => [
            'label' => 'Fecha',
            'defaultTag' => 'span',
            'cssProperty' => null,
        ],
        'author' => [
            'label' => 'Autor',
            'defaultTag' => 'span',
            'cssProperty' => null,
        ],
        'authorAvatar' => [
            'label' => 'Avatar del Autor',
            'defaultTag' => 'img',
            'cssProperty' => null,
        ],
        'link' => [
            'label' => 'Enlace/Botón',
            'defaultTag' => 'a',
            'cssProperty' => 'href',
        ],
        'categories' => [
            'label' => 'Categorías',
            'defaultTag' => 'div',
            'cssProperty' => null,
        ],
        'tags' => [
            'label' => 'Etiquetas',
            'defaultTag' => 'div',
            'cssProperty' => null,
        ],
        'commentCount' => [
            'label' => 'Comentarios',
            'defaultTag' => 'span',
            'cssProperty' => null,
        ],
    ];

    public function getSelector(): array
    {
        return [
            'attribute' => 'gloryPostField',
            'dataAttribute' => 'data-gbn-post-field',
            // El valor del atributo indica el tipo de campo
            'hasValue' => true,
        ];
    }

    public function getDefaults(): array
    {
        return [
            'fieldType' => 'title',
            'tag' => '',  // Si vacío, usa el defaultTag del tipo
            'linkText' => 'Leer más',  // Para tipo 'link'
            'dateFormat' => 'd M, Y',  // Para tipo 'date'
            'wordLimit' => 20,  // Para tipo 'excerpt'
            'imageSize' => 'medium',  // Para tipo 'featuredImage'
            'avatarSize' => 48,  // Para tipo 'authorAvatar'
            'separator' => ', ',  // Para tipos 'categories' y 'tags'
            'showLinks' => true,  // Para taxonomías

            // Meta y ACF
            'metaKey' => '',  // Para tipo 'meta:xxx'
            'acfField' => '',  // Para tipo 'acf:xxx'
        ];
    }

    public function getSchema(): array
    {
        $schema = SchemaBuilder::create();

        // ═══════════════════════════════════════════════
        // Tab: CONTENIDO
        // ═══════════════════════════════════════════════

        $schema->addOption(
            Option::select('fieldType', 'Tipo de Campo')
                ->options($this->getFieldTypeOptions())
                ->default('title')
                ->tab('Contenido')
                ->description('Datos a mostrar del post')
        );

        // Opciones específicas por tipo de campo

        // Para 'link'
        $schema->addOption(
            Option::text('linkText', 'Texto del Enlace')
                ->default('Leer más')
                ->tab('Contenido')
                ->condition(['fieldType', '==', 'link'])
        );

        // Para 'date'
        $schema->addOption(
            Option::text('dateFormat', 'Formato de Fecha')
                ->default('d M, Y')
                ->tab('Contenido')
                ->condition(['fieldType', '==', 'date'])
                ->description('Formato PHP: d=día, M=mes, Y=año')
        );

        // Para 'excerpt'
        $schema->addOption(
            Option::slider('wordLimit', 'Límite de Palabras')
                ->min(5)
                ->max(100)
                ->step(1)
                ->default(20)
                ->tab('Contenido')
                ->condition(['fieldType', '==', 'excerpt'])
        );

        // Para 'featuredImage'
        $schema->addOption(
            Option::select('imageSize', 'Tamaño de Imagen')
                ->options([
                    ['valor' => 'thumbnail', 'etiqueta' => 'Miniatura'],
                    ['valor' => 'medium', 'etiqueta' => 'Mediano'],
                    ['valor' => 'large', 'etiqueta' => 'Grande'],
                    ['valor' => 'full', 'etiqueta' => 'Completo'],
                ])
                ->default('medium')
                ->tab('Contenido')
                ->condition(['fieldType', '==', 'featuredImage'])
        );

        // Para 'authorAvatar'
        $schema->addOption(
            Option::slider('avatarSize', 'Tamaño Avatar')
                ->min(24)
                ->max(128)
                ->step(8)
                ->default(48)
                ->tab('Contenido')
                ->condition(['fieldType', '==', 'authorAvatar'])
        );

        // Para taxonomías
        $schema->addOption(
            Option::text('separator', 'Separador')
                ->default(', ')
                ->tab('Contenido')
                ->condition(['fieldType', 'in', ['categories', 'tags']])
        );

        $schema->addOption(
            Option::toggle('showLinks', 'Mostrar como Enlaces')
                ->default(true)
                ->tab('Contenido')
                ->condition(['fieldType', 'in', ['categories', 'tags']])
        );

        // Para meta personalizado
        $schema->addOption(
            Option::text('metaKey', 'Clave Meta')
                ->tab('Contenido')
                ->condition(['fieldType', '==', 'meta'])
                ->description('Nombre del campo meta (post_meta)')
        );

        // Para ACF
        $schema->addOption(
            Option::text('acfField', 'Campo ACF')
                ->tab('Contenido')
                ->condition(['fieldType', '==', 'acf'])
                ->description('Nombre del campo ACF')
        );

        // ═══════════════════════════════════════════════
        // Tab: AVANZADO
        // ═══════════════════════════════════════════════

        $schema->addOption(
            Option::select('tag', 'Etiqueta HTML')
                ->options([
                    ['valor' => '', 'etiqueta' => 'Auto (según tipo)'],
                    ['valor' => 'h1', 'etiqueta' => 'H1'],
                    ['valor' => 'h2', 'etiqueta' => 'H2'],
                    ['valor' => 'h3', 'etiqueta' => 'H3'],
                    ['valor' => 'h4', 'etiqueta' => 'H4'],
                    ['valor' => 'h5', 'etiqueta' => 'H5'],
                    ['valor' => 'h6', 'etiqueta' => 'H6'],
                    ['valor' => 'p', 'etiqueta' => 'Párrafo (p)'],
                    ['valor' => 'span', 'etiqueta' => 'Span'],
                    ['valor' => 'div', 'etiqueta' => 'Div'],
                    ['valor' => 'a', 'etiqueta' => 'Enlace (a)'],
                ])
                ->default('')
                ->tab('Avanzado')
                ->description('Sobrescribir la etiqueta por defecto')
        );

        return $schema->toArray();
    }

    /**
     * Genera las opciones para el selector de tipo de campo.
     * 
     * @return array Opciones formateadas
     */
    private function getFieldTypeOptions(): array
    {
        $options = [];

        foreach (self::FIELD_TYPES as $type => $config) {
            $options[] = [
                'valor' => $type,
                'etiqueta' => $config['label'],
            ];
        }

        // Añadir opciones para meta y ACF
        $options[] = ['valor' => 'meta', 'etiqueta' => 'Meta Field (personalizado)'];
        $options[] = ['valor' => 'acf', 'etiqueta' => 'Campo ACF'];
        $options[] = ['valor' => 'taxonomy', 'etiqueta' => 'Taxonomía Personalizada'];

        return $options;
    }

    /**
     * Renderiza el contenido del campo para un post específico.
     * 
     * @param WP_Post $post El post actual
     * @param array $config Configuración del campo
     * @return string HTML renderizado
     */
    public static function renderField(WP_Post $post, array $config): string
    {
        $fieldType = $config['fieldType'] ?? 'title';

        // Detectar tipos dinámicos (meta:xxx, acf:xxx, taxonomy:xxx)
        if (strpos($fieldType, 'meta:') === 0) {
            $key = substr($fieldType, 5);
            return esc_html(get_post_meta($post->ID, $key, true));
        }

        if (strpos($fieldType, 'acf:') === 0) {
            if (function_exists('get_field')) {
                $key = substr($fieldType, 4);
                return esc_html(get_field($key, $post->ID));
            }
            return '';
        }

        if (strpos($fieldType, 'taxonomy:') === 0) {
            $taxonomy = substr($fieldType, 9);
            return self::renderTaxonomy($post, $taxonomy, $config);
        }

        // Tipos estáticos
        return match ($fieldType) {
            'title' => self::renderTitle($post, $config),
            'featuredImage' => self::renderFeaturedImage($post, $config),
            'excerpt' => self::renderExcerpt($post, $config),
            'content' => self::renderContent($post),
            'date' => self::renderDate($post, $config),
            'author' => self::renderAuthor($post),
            'authorAvatar' => self::renderAuthorAvatar($post, $config),
            'link' => self::renderLink($post, $config),
            'categories' => self::renderCategories($post, $config),
            'tags' => self::renderTags($post, $config),
            'commentCount' => self::renderCommentCount($post),
            'meta' => self::renderMeta($post, $config),
            'acf' => self::renderAcf($post, $config),
            default => '',
        };
    }

    // ─────────────────────────────────────────────────
    // Métodos de renderizado por tipo
    // ─────────────────────────────────────────────────

    private static function renderTitle(WP_Post $post, array $config): string
    {
        return esc_html(get_the_title($post));
    }

    private static function renderFeaturedImage(WP_Post $post, array $config): string
    {
        if (!has_post_thumbnail($post)) {
            return '';
        }

        $size = $config['imageSize'] ?? 'medium';

        // Usar ImageUtility para optimizar via Jetpack Photon CDN en produccion
        // En LOCAL devuelve el thumbnail sin optimizar
        if (class_exists(\Glory\Utility\ImageUtility::class)) {
            return \Glory\Utility\ImageUtility::optimizar($post, $size, 60);
        }

        // Fallback si ImageUtility no esta disponible
        return get_the_post_thumbnail($post, $size, ['loading' => 'lazy']);
    }

    private static function renderExcerpt(WP_Post $post, array $config): string
    {
        $excerpt = get_the_excerpt($post);
        $wordLimit = (int) ($config['wordLimit'] ?? 20);

        if ($wordLimit > 0) {
            $words = explode(' ', $excerpt);
            if (count($words) > $wordLimit) {
                $excerpt = implode(' ', array_slice($words, 0, $wordLimit)) . '...';
            }
        }

        return esc_html($excerpt);
    }

    private static function renderContent(WP_Post $post): string
    {
        // Aplicar filtros the_content para shortcodes, etc.
        return apply_filters('the_content', $post->post_content);
    }

    private static function renderDate(WP_Post $post, array $config): string
    {
        $format = $config['dateFormat'] ?? 'd M, Y';
        return esc_html(get_the_date($format, $post));
    }

    private static function renderAuthor(WP_Post $post): string
    {
        return esc_html(get_the_author_meta('display_name', $post->post_author));
    }

    private static function renderAuthorAvatar(WP_Post $post, array $config): string
    {
        $size = (int) ($config['avatarSize'] ?? 48);
        return get_avatar($post->post_author, $size);
    }

    private static function renderLink(WP_Post $post, array $config): string
    {
        $text = $config['linkText'] ?? 'Leer más';
        $url = get_permalink($post);
        return '<a href="' . esc_url($url) . '">' . esc_html($text) . '</a>';
    }

    private static function renderCategories(WP_Post $post, array $config): string
    {
        $categories = get_the_category($post->ID);
        return self::renderTermsList($categories, $config);
    }

    private static function renderTags(WP_Post $post, array $config): string
    {
        $tags = get_the_tags($post->ID);
        if (!$tags) {
            return '';
        }
        return self::renderTermsList($tags, $config);
    }

    private static function renderTaxonomy(WP_Post $post, string $taxonomy, array $config): string
    {
        $terms = get_the_terms($post->ID, $taxonomy);
        if (!$terms || is_wp_error($terms)) {
            return '';
        }
        return self::renderTermsList($terms, $config);
    }

    private static function renderTermsList(array $terms, array $config): string
    {
        if (empty($terms)) {
            return '';
        }

        $separator = $config['separator'] ?? ', ';
        $showLinks = $config['showLinks'] ?? true;

        $items = array_map(function ($term) use ($showLinks) {
            if ($showLinks) {
                $url = get_term_link($term);
                return '<a href="' . esc_url($url) . '">' . esc_html($term->name) . '</a>';
            }
            return esc_html($term->name);
        }, $terms);

        return implode($separator, $items);
    }

    private static function renderCommentCount(WP_Post $post): string
    {
        $count = get_comments_number($post->ID);
        // translators: %d is the number of comments
        return sprintf(_n('%d comentario', '%d comentarios', $count, 'glory'), $count);
    }

    private static function renderMeta(WP_Post $post, array $config): string
    {
        $key = $config['metaKey'] ?? '';
        if (empty($key)) {
            return '';
        }
        return esc_html(get_post_meta($post->ID, $key, true));
    }

    private static function renderAcf(WP_Post $post, array $config): string
    {
        if (!function_exists('get_field')) {
            return '';
        }

        $fieldName = $config['acfField'] ?? '';
        if (empty($fieldName)) {
            return '';
        }

        $value = get_field($fieldName, $post->ID);

        // Si es array (ej: gallery, repeater), devolver JSON para JS
        if (is_array($value)) {
            return wp_json_encode($value);
        }

        return esc_html((string) $value);
    }

    public function getIcon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
    }

    public function getTemplate(): string
    {
        return '<span gloryPostField="title">Título del Post</span>';
    }
}
