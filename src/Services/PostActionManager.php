<?php
/* Glory/src/Services/PostActionManager.php */

namespace Glory\Services;

use Glory\Core\GloryLogger;
use WP_Error;
use WP_Post;

class PostActionManager
{
    public static function crearPost(string $tipoPost, array $datos, bool $retornarWpError = false): int|WP_Error
    {
        if (!current_user_can('edit_posts')) {
            GloryLogger::error("PostActionManager::crearPost() - Usuario sin permisos para crear posts.");
            return $retornarWpError ? new WP_Error('unauthorized', 'No tienes permisos para crear posts.') : 0;
        }

        if (empty($tipoPost) || !post_type_exists($tipoPost)) {
            GloryLogger::error("PostActionManager::crearPost() - Invalid or non-existent post type: '{$tipoPost}'.");
            return $retornarWpError ? new WP_Error('invalid_post_type', "Invalid post type '{$tipoPost}'.") : 0;
        }

        if (!isset($datos['post_title']) || empty(trim($datos['post_title']))) {
            GloryLogger::error("PostActionManager::crearPost() - 'post_title' is required in data array.");
            return $retornarWpError ? new WP_Error('missing_title', 'Post title is required.') : 0;
        }

        $datos['post_type'] = $tipoPost;
        if (!isset($datos['post_status'])) {
            $datos['post_status'] = 'draft';
        }

        $resultado = wp_insert_post($datos, $retornarWpError);

        if (is_wp_error($resultado)) {
            GloryLogger::error('PostActionManager::crearPost() - FAILED to insert post.', [
                'codigoError'  => $resultado->get_error_code(),
                'mensajeError' => $resultado->get_error_message(),
                'datosUsados'  => $datos
            ]);
        } elseif ($resultado === 0) {
            GloryLogger::error('PostActionManager::crearPost() - FAILED to insert post (returned 0).', ['datosUsados' => $datos]);
        }
        return $resultado;
    }

    public static function updatePost(array $datos, bool $retornarWpError = false): int|WP_Error
    {
        $checkId = isset($datos['ID']) ? absint($datos['ID']) : 0;
        if ($checkId && !current_user_can('edit_post', $checkId)) {
            GloryLogger::error("PostActionManager::updatePost() - Usuario sin permisos para editar post ID: {$checkId}.");
            return $retornarWpError ? new WP_Error('unauthorized', "No tienes permisos para editar el post {$checkId}.") : 0;
        }

        if (!isset($datos['ID']) || !($postId = absint($datos['ID'])) || $postId === 0) {
            GloryLogger::error("PostActionManager::updatePost() - Missing or invalid 'ID' in data array.");
            return $retornarWpError ? new WP_Error('missing_id', 'Post ID is required for update.') : 0;
        }

        if (!self::getPostById($postId)) {
            GloryLogger::error("PostActionManager::updatePost() - Post ID {$postId} not found. Cannot update.");
            return $retornarWpError ? new WP_Error('post_not_found', "Post ID {$postId} not found.") : 0;
        }

        $resultado = wp_update_post($datos, $retornarWpError);

        if (is_wp_error($resultado)) {
            GloryLogger::error("PostActionManager::updatePost() - FAILED to update post ID: {$postId}.", [
                'codigoError'  => $resultado->get_error_code(),
                'mensajeError' => $resultado->get_error_message(),
                'datosUsados'  => $datos
            ]);
        } elseif ($resultado === 0) {
            if (!self::getPostById($postId)) {
                GloryLogger::error("PostActionManager::updatePost() - FAILED to update post ID: {$postId} (returned 0, post seems gone).", ['datosUsados' => $datos]);
            } else {
                $resultado = $postId;
            }
        }
        // Emitir evento realtime genérico por post actualizado
        try {
            $postType = get_post_type($postId);
            if ($postType) {
                EventBus::emit('post_' . $postType);
            }
        } catch (\Throwable $e) {
            // Silencioso: no romper flujo por notificaciones
        }
        return $resultado;
    }

    public static function deletePost(int $postId, bool $forzarBorrado = false): bool
    {
        if (!current_user_can('delete_post', $postId)) {
            GloryLogger::error("PostActionManager::deletePost() - Usuario sin permisos para eliminar post ID: {$postId}.");
            return false;
        }

        if (!self::_validarPostId($postId, !$forzarBorrado)) {
            return false;
        }
        $resultado = wp_delete_post($postId, $forzarBorrado);
        if ($resultado instanceof WP_Post || $resultado === true) {
            try {
                $postType = is_object($resultado) ? $resultado->post_type : get_post_type($postId);
                if ($postType) {
                    EventBus::emit('post_' . $postType);
                }
            } catch (\Throwable $e) {}
            return true;
        } else {
            GloryLogger::error("PostActionManager::deletePost() - FAILED to delete post ID: {$postId}. wp_delete_post returned: " . print_r($resultado, true));
            return false;
        }
    }

    public static function trashPost(int $postId): bool
    {
        if (!self::_validarPostId($postId, true, 'publish,draft,pending,private,future')) {
            return false;
        }
        $resultado = wp_trash_post($postId);
        if ($resultado) {
            return true;
        } else {
            GloryLogger::error("PostActionManager::trashPost() - FAILED to move post ID: {$postId} to trash.");
            return false;
        }
    }

    public static function untrashPost(int $postId): bool
    {
        if (!self::_validarPostId($postId, true, 'trash')) {
            return false;
        }
        $resultado = wp_untrash_post($postId);
        if ($resultado) {
            return true;
        } else {
            GloryLogger::error("PostActionManager::untrashPost() - FAILED to restore post ID: {$postId} from trash.");
            return false;
        }
    }

    public static function getPostById(int $postId, string $formatoSalida = OBJECT): WP_Post|array|null
    {
        if ($postId <= 0) {
            return null;
        }
        return get_post($postId, $formatoSalida);
    }

    public static function getPostBySlug(string $slug, string $tipoPost, string $formatoSalida = OBJECT): WP_Post|array|null
    {
        if (empty($slug) || empty($tipoPost)) {
            GloryLogger::error('PostActionManager::getPostBySlug() - Slug and Post Type are required.', ['slug' => $slug, 'tipoPost' => $tipoPost]);
            return null;
        }
        return get_page_by_path($slug, $formatoSalida, $tipoPost);
    }

    // ANTERIOR: postExists
    public static function postExiste(int|string $identificador, string $tipoPost = 'post', string $campo = 'id'): bool
    {
        if ($campo === 'id') {
            if (!is_numeric($identificador) || intval($identificador) <= 0)
                return false;
            return (bool) get_post_status(intval($identificador));
        } elseif ($campo === 'slug') {
            if (empty($identificador) || !is_string($identificador))
                return false;
            return !is_null(self::getPostBySlug($identificador, $tipoPost));
        } else {
            GloryLogger::error("PostActionManager::postExists() - Invalid field type specified: '{$campo}'. Use 'id' or 'slug'.");
            return false;
        }
    }

    // ANTERIOR: _validatePostId
    private static function _validarPostId(int $postId, bool $logSiNoEncontrado = true, string|array|null $verificarEstado = null): bool
    {
        if ($postId <= 0) {
            GloryLogger::error("PostActionManager - Invalid Post ID provided: {$postId}.");
            return false;
        }
        $estado = get_post_status($postId);
        if (!$estado) {
            if ($logSiNoEncontrado) {
                GloryLogger::error("PostActionManager - Post with ID {$postId} does not exist.");
            }
            return false;
        }
        if (!is_null($verificarEstado)) {
            $estadosPermitidos = is_array($verificarEstado) ? $verificarEstado : array_map('trim', explode(',', $verificarEstado));
            if (!in_array($estado, $estadosPermitidos, true)) {
                GloryLogger::error("PostActionManager - Post ID {$postId} has invalid status '{$estado}'. Expected one of: " . implode(', ', $estadosPermitidos) . '.');
                return false;
            }
        }
        return true;
    }
}
