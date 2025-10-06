<?

namespace Glory\Handler\Form;

use Glory\Core\GloryLogger;
use Glory\Services\PostActionManager;

class CrearPublicacionHandler implements FormHandlerInterface
{
    public function procesar(array $postDatos, array $archivos): array
    {
        // 1. Obtener configuración y establecer valores por defecto
        $tipoPost = sanitize_text_field($postDatos['postType'] ?? 'post');
        $estadoPost = sanitize_text_field($postDatos['postStatus'] ?? 'publish');
        $contenidoPost = sanitize_textarea_field($postDatos['postContent'] ?? '');

        // 2. Verificación de permisos dinámicos
        $infoTipoPost = get_post_type_object($tipoPost);
        if (!$infoTipoPost) {
            throw new \Exception("El tipo de publicación '{$tipoPost}' no es válido.");
        }
        if (!current_user_can($infoTipoPost->cap->publish_posts)) {
            throw new \Exception('No tienes permiso para crear este tipo de publicación.');
        }

        $autorId = get_current_user_id();
        GloryLogger::info("Iniciando creación de '{$tipoPost}' para el usuario {$autorId}.");

        // 3. Preparar datos del post (generar título si es necesario)
        if (empty($contenidoPost) && empty($archivos)) {
            throw new \Exception('La publicación debe tener contenido o al menos un archivo adjunto.');
        }

        $tituloPost = !empty($postDatos['postTitle'])
            ? sanitize_text_field($postDatos['postTitle'])
            : 'Publicación de ' . wp_get_current_user()->display_name . ' - ' . time();

        $datosPost = [
            'post_author'  => $autorId,
            'post_title'   => $tituloPost,
            'post_content' => $contenidoPost,
            'post_status'  => $estadoPost,
        ];

        // 4. Crear la publicación usando el servicio
        $postId = PostActionManager::crearPost($tipoPost, $datosPost, true);

        if (is_wp_error($postId)) {
            throw new \Exception('No se pudo crear la publicación: ' . $postId->get_error_message());
        }

        GloryLogger::info("Publicación #{$postId} de tipo '{$tipoPost}' creada. Procesando meta y archivos.");

        // 5. Guardar metadatos y adjuntar archivos
        $this->guardarMetaYArchivos($postId, $postDatos, $archivos);

        // 6. Respuesta al frontend
        return ['alert' => '¡Publicación creada con éxito!'];
    }

    /**
     * Procesa y guarda metadatos y archivos adjuntos para un post.
     */
    private function guardarMetaYArchivos(int $postId, array $postDatos, array $archivos): void
    {
        // Claves reservadas que no deben ser tratadas como metadatos
        $clavesReservadas = ['action', 'subAccion', 'nonce', 'postType', 'postStatus', 'postContent', 'postTitle'];

        // Guardar metadatos de texto y checkboxes
        foreach ($postDatos as $clave => $valor) {
            if (in_array($clave, $clavesReservadas, true)) {
                continue;
            }
            $valorSanitizado = is_array($valor) ? array_map('sanitize_text_field', $valor) : sanitize_text_field($valor);
            update_post_meta($postId, $clave, $valorSanitizado);
        }

        // Procesar y adjuntar archivos
        if (!empty($archivos)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            foreach ($archivos as $claveInput => $archivo) {
                if ($archivo['error'] !== UPLOAD_ERR_OK) {
                    continue;
                }

                // media_handle_upload asocia directamente el archivo al post
                $attachmentId = media_handle_upload($claveInput, $postId);

                if (is_wp_error($attachmentId)) {
                    GloryLogger::error("Error al adjuntar archivo '{$claveInput}' al post #{$postId}.", ['error' => $attachmentId->get_error_message()]);
                } else {
                    // El metadato se guarda con el nombre del campo del formulario
                    update_post_meta($postId, $claveInput, $attachmentId);
                    GloryLogger::info("Archivo '{$claveInput}' (ID: {$attachmentId}) adjuntado a la publicación #{$postId}.");
                }
            }
        }
    }
}