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

        $url = self::jetpack_photon_url($url, [
            'quality' => $quality,
            'strip'   => $strip,
            'resize'  => ($width && $height) ? "{$width},{$height}" : null,
        ]);

        $alt = esc_attr(get_the_title($post));

        return sprintf('<img src="%s" alt="%s" loading="lazy" width="%d" height="%d" />', esc_url($url), $alt, $width, $height);
    }

    public static function jetpack_photon_url(string $url, array $args = []): string
    {
        if (defined('LOCAL') && LOCAL) {
            return $url;
        }

        if (empty($url)) {
            return '';
        }

        $parsed_url = parse_url($url);
        if (isset($parsed_url['host']) && preg_match('/^i\d\.wp\.com$/', $parsed_url['host'])) {
            $cdn_url = $url;
            if (function_exists('remove_query_arg')) {
                $cdn_url = remove_query_arg(['quality', 'strip', 'resize', 'w', 'h'], $cdn_url);
            }
        } else {
            $path = ($parsed_url['host'] ?? '') . ($parsed_url['path'] ?? '');
            $cdn_url = 'https://i0.wp.com/' . ltrim($path, '/');
        }

        $query = [];
        if (isset($args['quality']) && is_numeric($args['quality'])) {
            $query['quality'] = (int) $args['quality'];
        }
        if (isset($args['strip']) && in_array($args['strip'], ['all', 'info', 'none'], true)) {
            $query['strip'] = $args['strip'];
        }
        if (isset($args['resize']) && preg_match('/^\d+,[0-9]+$/', $args['resize'])) {
            $query['resize'] = $args['resize'];
        }

        foreach ($args as $k => $v) {
            if (!isset($query[$k]) && $v !== null) {
                $query[$k] = $v;
            }
        }

        if (!empty($query)) {
            $final_url = function_exists('add_query_arg') ? add_query_arg($query, $cdn_url) : $cdn_url . '?' . http_build_query($query);
        } else {
            $final_url = $cdn_url;
        }

        return function_exists('esc_url') ? esc_url($final_url) : $final_url;
    }
}