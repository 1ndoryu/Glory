<?php

namespace Glory\Utility;

/**
 * Clase de utilidad para funciones relacionadas con los assets del tema.
 * Permite imprimir elementos como imágenes de forma rápida y segura.
 */
class AssetsUtility
{
    /**
     * Imprime una etiqueta <img> para una imagen ubicada en la carpeta de assets.
     *
     * Construye la URL completa a la imagen y la imprime en una etiqueta <img>.
     * Permite añadir atributos HTML adicionales de forma dinámica.
     *
     * @param string $nombre    El nombre del archivo de la imagen (sin extensión). Ejemplo: 'inicio'.
     * @param string $extension La extensión del archivo de la imagen. Por defecto es 'jpg'.
     * @param array  $atributos Un array asociativo de atributos HTML adicionales para la etiqueta <img>.
     * Ej: ['class' => 'mi-clase', 'alt' => 'Texto alternativo'].
     * Si no se provee un 'alt', se generará uno a partir del nombre de la imagen.
     */
    public static function imagen(string $nombre, string $extension = 'jpg', array $atributos = []): void
    {
        // Construye la URL de la imagen de forma segura.
        $urlImagen = get_template_directory_uri() . '/assets/images/' . sanitize_file_name($nombre) . '.' . sanitize_key($extension);

        // Establece un texto alternativo (alt) por defecto si no se proporciona uno.
        if (!isset($atributos['alt'])) {
            $atributos['alt'] = ucwords(str_replace(['-', '_'], ' ', $nombre));
        }

        // Convierte el array de atributos en una cadena de texto para el HTML.
        $atributosString = '';
        foreach ($atributos as $clave => $valor) {
            $atributosString .= sprintf(' %s="%s"', esc_attr($clave), esc_attr($valor));
        }

        // Imprime la etiqueta <img> completa, escapando la URL y los atributos para mayor seguridad.
        printf(
            '<img src="%s"%s>',
            esc_url($urlImagen),
            $atributosString
        );
    }

    public static function imagenUrl(string $nombre, string $extension = 'jpg'): void
    {
        // Construye la URL de la imagen de forma segura.
        $urlImagen = get_template_directory_uri() . '/assets/images/' . sanitize_file_name($nombre) . '.' . sanitize_key($extension);

        // Imprime solamente la URL escapada.
        echo esc_url($urlImagen);
    }
}