<?php

namespace Glory\Integration\Avada\Elements\GlorySplitContent;

use Glory\Components\ContentRender;

class GlorySplitContentTemplate
{
    public static function titleItem(\WP_Post $post, string $itemClass): void
    {
        $postType = get_post_type($post);
        $isLink = ($postType === 'glory_link');
        $title = get_the_title($post);
        $slug = isset($post->post_name) ? (string) $post->post_name : '';

        // Contenedor del item: siempre incluir data-post-slug; para posts normales agregar data-post-id
        $attrs = ' data-post-slug="' . esc_attr($slug) . '"';
        if (!$isLink) {
            $attrs .= ' data-post-id="' . esc_attr((string) $post->ID) . '"';
        }
        echo '<div class="glory-split__item ' . esc_attr($itemClass) . '"' . $attrs . '>';
        if ($isLink) {
            $url = (string) get_post_meta($post->ID, '_glory_url', true);
            if ($url !== '') {
                echo '<a class="glory-split__title" href="' . esc_url($url) . '" target="_blank" rel="noopener">';
                echo '<span class="glory-split__title-text">' . esc_html($title) . '</span>';
                echo '</a>';
            } else {
                echo '<span class="glory-split__title">';
                echo '<span class="glory-split__title-text">' . esc_html($title) . '</span>';
                echo '</span>';
            }
        } else {
            echo '<button type="button" class="glory-split__title">';
            echo '<span class="glory-split__title-text">' . esc_html($title) . '</span>';
            echo '</button>';
        }
        echo '</div>';
    }
}
