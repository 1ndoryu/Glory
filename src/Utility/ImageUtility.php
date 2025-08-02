<?php

namespace Glory\Utility;

use WP_Post;

class ImageUtility
{
    public static function optimizar(\WP_Post|int $post, string $size = 'medium_large', int $quality = 60, string $strip = 'all'): string
    {
        if (defined('LOCAL') && LOCAL) {
            return get_the_post_thumbnail($post, $size);
        }

        if (!($post instanceof WP_Post)) {
            $post = get_post($post);
            if (!$post) {
                return '';
            }
        }

        if (!has_post_thumbnail($post)) {
            return '';
        }

        $thumb_id   = get_post_thumbnail_id($post);
        $thumb_data = wp_get_attachment_image_src($thumb_id, $size);

        if (!$thumb_data || empty($thumb_data[0])) {
            return '';
        }

        [$url, $width, $height] = $thumb_data;

        if (function_exists('jetpack_photon_url')) {
            $args = [
                'quality' => $quality,
                'strip'   => $strip,
            ];
            if ($width && $height) {
                $args['resize'] = $width . ',' . $height;
            }
            $url = jetpack_photon_url($url, $args);
        }

        $alt = esc_attr(get_the_title($post));

        return sprintf('<img src="%s" alt="%s" loading="lazy" width="%d" height="%d" />', esc_url($url), $alt, $width, $height);
    }
}
