<?

namespace Glory\Handler\Form;

use Glory\Core\GloryLogger;

class GuardarMetaHandler implements FormHandlerInterface
{
    public function procesar(array $postDatos, array $archivos): array
    {
        $subAccion = $postDatos['subAccion'] ?? 'guardarMeta';
        GloryLogger::info("Iniciando proceso de '{$subAccion}'.");

        // 1. Validar Contexto y Permisos
        $metaTarget = $postDatos['metaTarget'] ?? null;
        if (empty($metaTarget)) {
            throw new \Exception('Contexto de guardado (metaTarget) no especificado.');
        }

        $objectId = !empty($postDatos['objectId']) ? intval($postDatos['objectId']) : null;
        $updateFunction = null;
        $successMessage = '';

        switch ($metaTarget) {
            case 'user':
                $objectId = $objectId ?? get_current_user_id();
                if (empty($objectId) || !current_user_can('edit_user', $objectId)) {
                    throw new \Exception('No tienes permiso para editar este usuario.');
                }
                $updateFunction = 'update_user_meta';
                $successMessage = '¡Perfil de usuario guardado con éxito!';
                break;

            case 'post':
                if (empty($objectId) || !current_user_can('edit_post', $objectId)) {
                    throw new \Exception('No tienes permiso para editar esta entrada.');
                }
                $updateFunction = 'update_post_meta';
                $successMessage = '¡Datos de la entrada guardados con éxito!';
                break;

            default:
                throw new \Exception("El contexto de guardado '{$metaTarget}' no es válido.");
        }

        GloryLogger::info('Contexto y permisos validados.', ['target' => $metaTarget, 'objectId' => $objectId]);

        // 2. Definir claves reservadas que no son metadatos
        $clavesReservadas = ['action', 'subAccion', 'nonce', 'metaTarget', 'objectId'];
        
        // 3. Procesar y Guardar Datos de Texto
        foreach ($postDatos as $clave => $valor) {
            if (in_array($clave, $clavesReservadas, true)) {
                continue;
            }
            $valorSanitizado = sanitize_textarea_field($valor);
            call_user_func($updateFunction, $objectId, $clave, $valorSanitizado);
        }
        GloryLogger::info('Metadatos de texto procesados.', ['objectId' => $objectId]);

        // 4. Procesar y Guardar Archivos
        if (!empty($archivos)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            foreach ($archivos as $clave => $archivo) {
                if ($archivo['error'] !== UPLOAD_ERR_OK) {
                    continue;
                }
                GloryLogger::info('Procesando subida de archivo.', ['clave' => $clave, 'nombre' => $archivo['name']]);
                
                $subida = wp_handle_upload($archivo, ['test_form' => false]);
                if ($subida && empty($subida['error'])) {
                    $attachment = ['post_mime_type' => $subida['type'], 'post_title' => sanitize_file_name(basename($subida['file'])), 'post_content' => '', 'post_status' => 'inherit', 'post_author' => get_current_user_id()];
                    $attachmentId = wp_insert_attachment($attachment, $subida['file']);

                    if (is_wp_error($attachmentId)) {
                         GloryLogger::error('Falló wp_insert_attachment.', ['error' => $attachmentId->get_error_message()]);
                         continue;
                    }
                    
                    $attachmentData = wp_generate_attachment_metadata($attachmentId, $subida['file']);
                    wp_update_attachment_metadata($attachmentId, $attachmentData);
                    call_user_func($updateFunction, $objectId, $clave, $attachmentId);
                    GloryLogger::info('Archivo subido y metadato actualizado.', ['attachmentId' => $attachmentId]);
                } else {
                    GloryLogger::error('Falló wp_handle_upload.', ['error' => $subida['error'] ?? 'Error desconocido.']);
                }
            }
        }

        // 5. Respuesta al Frontend
        GloryLogger::info('Proceso completado exitosamente.');
        return ['alert' => $successMessage];
    }
}