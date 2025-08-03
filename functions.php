<?php

use Glory\Utility\ImageUtility;

if (!function_exists('optimizarImagen')) {
    function optimizarImagen($post, string $size = 'medium_large', int $quality = 60, string $strip = 'all'): string
    {
        return ImageUtility::optimizar($post, $size, $quality, $strip);
    }
}

if (!function_exists('get_the_post_thumbnail_optimized')) {
    function get_the_post_thumbnail_optimized($post, string $size = 'medium_large', int $quality = 60): string
    {
        return ImageUtility::optimizar($post, $size, $quality);
    }
}

if (!function_exists('jetpack_photon_url')) {
    function jetpack_photon_url(string $url, array $args = []): string
    {
        return ImageUtility::jetpack_photon_url($url, $args);
    }
}

function themeSetup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'themeSetup');
add_filter('show_admin_bar', '__return_false');