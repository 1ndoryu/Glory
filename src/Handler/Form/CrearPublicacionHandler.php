<?
namespace Glory\Handler\Form;

use Glory\Core\GloryLogger;

class CrearPublicacionHandler implements FormHandlerInterface
{
    public function procesar(array $postDatos, array $archivos): array
    {
        // 1. Verificación de permisos
        if (!current_user_can('publish_posts')) {
            throw new \Exception('No tienes permiso para publicar.');
        }

        $autorId = get_current_user_id();
        GloryLogger::info("Iniciando creación de publicación para el usuario {$autorId}.");

        // 2. Preparar datos básicos de la publicación
        $contenidoPost = sanitize_textarea_field($postDatos['postContent'] ?? '');

        if (empty($contenidoPost) && empty($archivos)) {
            throw new \Exception('La publicación debe tener contenido o al menos un archivo adjunto.');
        }

        $datosPost = [
            'post_author' => $autorId,
            'post_content' => $contenidoPost,
            'post_status' => 'publish', // O 'draft' si se requiere revisión
            'post_type'   => 'post', // Asumimos el tipo de post por defecto
        ];

        // 3. Insertar la publicación en la base de datos
        $postId = wp_insert_post($datosPost);

        if (is_wp_error($postId)) {
            GloryLogger::error('Error al insertar la publicación.', ['error' => $postId->get_error_message()]);
            throw new \Exception('No se pudo crear la publicación.');
        }

        GloryLogger::info("Publicación #{$postId} creada exitosamente. Guardando metadatos.");

        // 4. Guardar metadatos (checkboxes y otros campos)
        $metaAChequear = [
            'areaFans', 'areaArtistas', 'permitirDescargas', 'esExclusivo', 
            'permitirColab', 'lanzamientoMusical', 'enVenta', 'esEfimero'
        ];

        foreach ($metaAChequear as $clave) {
            // Los checkboxes no marcados no se envían, los marcados llegan como "true"
            $valor = isset($postDatos[$clave]) && $postDatos[$clave] === 'true' ? '1' : '0';
            update_post_meta($postId, $clave, $valor);
        }

        // 5. Procesar y adjuntar archivos
        if (!empty($archivos)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            foreach ($archivos as $claveInput => $archivo) {
                if ($archivo['error'] !== UPLOAD_ERR_OK) continue;

                $attachmentId = media_handle_upload($claveInput, $postId);

                if (is_wp_error($attachmentId)) {
                    GloryLogger::error("Error al adjuntar archivo '{$claveInput}'.", ['error' => $attachmentId->get_error_message()]);
                } else {
                    // Guardamos el ID del adjunto como metadato del post
                    // por ejemplo, 'meta_archivoImagen' => ID_del_adjunto
                    update_post_meta($postId, 'meta_' . $claveInput, $attachmentId);
                    GloryLogger::info("Archivo '{$claveInput}' (ID: {$attachmentId}) adjuntado a la publicación #{$postId}.");
                }
            }
        }
        
        // 6. Respuesta al frontend
        return ['alert' => '¡Publicación creada con éxito!'];
    }
}
