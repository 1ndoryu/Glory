<?php

namespace Glory\Integration\Avada\Elements\GlorySplitContent;

use Glory\Components\ContentRender;

class GlorySplitContentTemplate
{
    public static function titleItem(\WP_Post $post, string $itemClass): void
    {
        $postType = get_post_type($post);
        $isLink = ($postType === 'glory_link');
        $isHeader = ($postType === 'glory_header');
        $title = get_the_title($post);
        $slug = isset($post->post_name) ? (string) $post->post_name : '';
        $permalink = get_permalink($post);

        // Contenedor del item: siempre incluir data-post-slug y data-post-id para todos los posts
        $attrs = ' data-post-slug="' . esc_attr($slug) . '"';
        $attrs .= ' data-post-id="' . esc_attr((string) $post->ID) . '"';

        $hasLink = false;
        $url = '';
        if ($isLink) {
            // Para links, agregar la URL externa
            $url = (string) get_post_meta($post->ID, '_glory_url', true);
            if ($url !== '') {
                $attrs .= ' data-post-url="' . esc_url($url) . '"';
                $hasLink = true;
            }
        }
        if (!empty($permalink)) {
            $attrs .= ' data-post-permalink="' . esc_url($permalink) . '"';
        }

        // Para headers, agregar estilos inline de padding
        $style = '';
        if ($isHeader) {
            $paddingTop = (string) get_post_meta($post->ID, '_glory_header_padding_top', true);
            $paddingBottom = (string) get_post_meta($post->ID, '_glory_header_padding_bottom', true);
            if ($paddingTop !== '') {
                $style .= 'padding-top:' . esc_attr($paddingTop) . ';';
            }
            if ($paddingBottom !== '') {
                $style .= 'padding-bottom:' . esc_attr($paddingBottom) . ';';
            }
            if ($style !== '') {
                $attrs .= ' style="' . $style . '"';
            }
        }

        $itemClasses = [$itemClass];
        if ($isHeader) {
            $itemClasses[] = 'glory-split__item--header';
        }

        echo '<div class="glory-split__item ' . esc_attr(implode(' ', $itemClasses)) . '"' . $attrs . '>';
        // Usar button para ambos tipos para mantener consistencia visual
        $titleClasses = ['glory-split__title'];
        if ($isHeader) {
            $titleClasses[] = 'glory-split__title--header';
        }
        echo '<button type="button" class="' . esc_attr(implode(' ', $titleClasses)) . '">';
        echo '<span class="glory-split__title-text">' . esc_html($title) . '</span>';
        // Añadir icono SVG al final del título solo si el item representa un enlace (glory_link) y la opción está activada
        $showIcon = ContentRender::getCurrentOption('post_title_link_icon', 'yes') === 'yes';
        if ($showIcon && $hasLink && ! $isHeader) {
            $svg = '<span class="glory-split__title-icon"><svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.75011 4H6.00011V5.5H6.75011H9.43945L5.46978 9.46967L4.93945 10L6.00011 11.0607L6.53044 10.5303L10.499 6.56182V9.25V10H11.999V9.25V5C11.999 4.44772 11.5512 4 10.999 4H6.75011Z" fill="currentColor"></path></svg></span>';
            echo $svg;
        }
        echo '</button>';
        echo '</div>';
    }
}
