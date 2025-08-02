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
        return optimizarImagen($post, $size, $quality);
    }
}



if (!function_exists('jetpack_photon_url')) {
    /**
     * Implementación simplificada de jetpack_photon_url que genera una URL optimizada
     * a través del CDN de WordPress.com (iX.wp.com) cuando Jetpack no está disponible.
     * Soporta los parámetros más habituales: quality, strip y resize.
     */
    function jetpack_photon_url(string $url, array $args = []): string
    {

        if (defined('LOCAL') && LOCAL) {
            return $url;
        }
        
        if ($url === '' || $url === null) {
            return '';
        }

        // Extraer parámetros relevantes
        $quality = $args['quality'] ?? null;
        $strip   = $args['strip']   ?? 'all';
        $resize  = $args['resize']  ?? null; // formato "ancho,alto"

        $parsed_url = parse_url($url);

        // Comprobar si la URL ya apunta al CDN de wp.com (i0.wp.com, i1.wp.com, etc.)
        if (isset($parsed_url['host']) && preg_match('/^i\d\.wp\.com$/', $parsed_url['host'])) {
            $cdn_url = $url;
            // Eliminar posibles parámetros ya existentes para evitar duplicados
            if (function_exists('remove_query_arg')) {
                $cdn_url = remove_query_arg(['quality', 'strip', 'resize', 'w', 'h'], $cdn_url);
            }
        } else {
            // Construir la ruta interna (host + path o solo path si es relativa)
            if (isset($parsed_url['host'])) {
                $path = $parsed_url['host'] . ($parsed_url['path'] ?? '');
            } else {
                $path = ltrim($parsed_url['path'] ?? '', '/');
            }
            $cdn_url = 'https://i0.wp.com/' . ltrim($path, '/');
        }

        // Preparar parámetros de consulta
        $query = [];
        if ($quality !== null && is_numeric($quality)) {
            $query['quality'] = (int) $quality;
        }
        if ($strip !== null && in_array($strip, ['all', 'info', 'none'], true)) {
            $query['strip'] = $strip;
        }
        if ($resize !== null && preg_match('/^\d+,[0-9]+$/', $resize)) {
            $query['resize'] = $resize;
        }

        // Añadir cualquier otro parámetro pasado que no se haya procesado explícitamente
        foreach ($args as $k => $v) {
            if (!isset($query[$k]) && $v !== null) {
                $query[$k] = $v;
            }
        }

        // Construir URL final
        if (!empty($query)) {
            if (function_exists('add_query_arg')) {
                $final_url = add_query_arg($query, $cdn_url);
            } else {
                $final_url = $cdn_url . (strpos($cdn_url, '?') === false ? '?' : '&') . http_build_query($query);
            }
        } else {
            $final_url = $cdn_url;
        }

        return function_exists('esc_url') ? esc_url($final_url) : $final_url;
    }
}

function themeSetup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'themeSetup');
add_filter('show_admin_bar', '__return_false');