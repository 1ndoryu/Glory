<?php
/**
 * Archivo de funciones globales del framework Glory.
 *
 * Este archivo contiene funciones de utilidad para la manipulación de imágenes
 * y la configuración inicial del tema.
 *
 * @package Glory
 */

use Glory\Utility\ImageUtility;

if (!function_exists('optimizarImagen')) {
    /**
     * Optimiza una imagen dada utilizando ImageUtility.
     *
     * @param mixed  $publicacion El post ID o objeto WP_Post.
     * @param string $tamano      El tamaño de la imagen deseado (por defecto 'medium_large').
     * @param int    $calidad     La calidad de la imagen (0-100).
     * @param string $recorte     El modo de recorte (por defecto 'all').
     * @return string La URL de la imagen optimizada.
     */
    function optimizarImagen($publicacion, string $tamano = 'medium_large', int $calidad = 60, string $recorte = 'all'): string
    {
        return ImageUtility::optimizar($publicacion, $tamano, $calidad, $recorte);
    }
}

if (!function_exists('get_the_post_thumbnail_optimized')) {
    /**
     * Obtiene la miniatura del post optimizada.
     *
     * Wrapper para la función de optimización de imágenes.
     *
     * @param mixed  $publicacion El post ID o objeto WP_Post.
     * @param string $tamano      El tamaño de la imagen.
     * @param int    $calidad     La calidad de la imagen.
     * @return string La URL de la imagen optimizada.
     */
    function get_the_post_thumbnail_optimized($publicacion, string $tamano = 'medium_large', int $calidad = 60): string
    {
        return ImageUtility::optimizar($publicacion, $tamano, $calidad);
    }
}

if (!function_exists('jetpack_photon_url')) {
    /**
     * Genera una URL de Jetpack Photon para una imagen.
     *
     * @param string $url        La URL original de la imagen.
     * @param array  $argumentos Argumentos adicionales para Photon.
     * @return string La URL procesada por Photon.
     */
    function jetpack_photon_url(string $url, array $argumentos = []): string
    {
        return ImageUtility::jetpack_photon_url($url, $argumentos);
    }
}

/**
 * Configuración inicial del tema.
 *
 * Registra el soporte para características del tema como 'title-tag' y 'post-thumbnails'
 * basándose en la configuración de GloryFeatures.
 *
 * @return void
 */
function configurarTema()
{
    // Añadir soporte de tema solo si la característica correspondiente está activa.
    if (\Glory\Core\GloryFeatures::isActive('titleTag') !== false) {
        add_theme_support('title-tag');
    }
    if (\Glory\Core\GloryFeatures::isActive('postThumbnails') !== false) {
        add_theme_support('post-thumbnails');
    }
}
add_action('after_setup_theme', 'configurarTema');
