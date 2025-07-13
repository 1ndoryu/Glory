<?php

namespace Glory\Utility;

use Glory\Core\GloryLogger;

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

    /**
     * Obtiene el ID de un adjunto a partir de un nombre de archivo en /assets/images/.
     * Si el adjunto no existe en la Biblioteca de Medios, lo importa desde la carpeta de assets.
     *
     * @param string $nombreArchivo El nombre del archivo de imagen (ej. 'mi-imagen.jpg').
     * @return int|null El ID del adjunto o null si hay un error.
     */
    public static function get_attachment_id_from_asset(string $nombreArchivo): ?int
    {
        static $cache = [];
        if (isset($cache[$nombreArchivo])) {
            return $cache[$nombreArchivo];
        }

        $rutaAssetRelativa = '/assets/images/' . $nombreArchivo;
        $rutaAssetCompleta = get_template_directory() . $rutaAssetRelativa;

        if (!file_exists($rutaAssetCompleta)) {
            GloryLogger::error("AssetsUtility: El archivo asset '{$nombreArchivo}' no se encontró en '{$rutaAssetCompleta}'.");
            return null;
        }

        // Si estamos en el front-end y el adjunto no existe todavía, NO intentes importarlo para evitar bloqueos.
        if (!is_admin()) {
            $args = [
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'meta_query'     => [
                    [
                        'key'   => '_glory_asset_source',
                        'value' => $rutaAssetRelativa,
                    ],
                ],
                'posts_per_page' => 1,
                'fields'        => 'ids',
                'no_found_rows' => true,
            ];
            $q = new \WP_Query($args);
            $cache[$nombreArchivo] = $q->have_posts() ? (int) $q->posts[0] : null;
            return $cache[$nombreArchivo];
        }

        // Buscar si ya existe un adjunto que provenga de este asset
        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'meta_query' => [
                [
                    'key' => '_glory_asset_source',
                    'value' => $rutaAssetRelativa
                ]
            ],
            'posts_per_page' => 1,
            'fields' => 'ids'
        ];
        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            return $query->posts[0];
        }

        // Si no existe, se "sube" el archivo a la biblioteca de medios
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // wp_upload_bits está obsoleto, es mejor usar wp_upload_bits con wp_handle_sideload
        $archivoTemporal = wp_tempnam($nombreArchivo);
        copy($rutaAssetCompleta, $archivoTemporal);

        $datosArchivo = [
            'name' => basename($nombreArchivo),
            'tmp_name' => $archivoTemporal,
            'error' => 0,
            'size' => filesize($rutaAssetCompleta)
        ];

        $sobrescribir = ['test_form' => false];
        $subida = wp_handle_sideload($datosArchivo, $sobrescribir);

        if (isset($subida['error'])) {
            GloryLogger::error("AssetsUtility: Error al subir el asset '{$nombreArchivo}'.", ['error' => $subida['error']]);
            if (file_exists($archivoTemporal)) {
                unlink($archivoTemporal);
            }
            return null;
        }

        $idAdjunto = wp_insert_attachment([
            'guid' => $subida['url'],
            'post_mime_type' => $subida['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($subida['file'])),
            'post_content' => '',
            'post_status' => 'inherit'
        ], $subida['file']);

        if (is_wp_error($idAdjunto)) {
            GloryLogger::error("AssetsUtility: Error al insertar el adjunto '{$nombreArchivo}'.", ['error' => $idAdjunto->get_error_message()]);
            return null;
        }

        // Generar metadatos para el adjunto (esencial para que se muestre correctamente)
        $metadatosAdjunto = wp_generate_attachment_metadata($idAdjunto, $subida['file']);
        wp_update_attachment_metadata($idAdjunto, $metadatosAdjunto);

        // Guardar la referencia a su origen para futuras búsquedas
        update_post_meta($idAdjunto, '_glory_asset_source', $rutaAssetRelativa);

        GloryLogger::info("AssetsUtility: El asset '{$nombreArchivo}' ha sido importado a la Biblioteca de Medios.", ['attachment_id' => $idAdjunto]);

        return $idAdjunto;
    }
}