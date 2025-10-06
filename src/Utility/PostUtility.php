<?php

namespace Glory\Utility;

/**
 * Clase de utilidad para funciones relacionadas con los posts.
 * Reemplaza los helpers procedurales para una mejor organización y autoloading.
 */
class PostUtility
{
    /**
     * Obtiene el valor de un metadato para un post específico.
     *
     * @param string $metaKey La clave del metadato que se desea obtener.
     * @param int $postId Opcional. El ID del post. Si es 0 o no se provee, se intentará obtener el ID del post actual en el loop.
     * @return mixed|null El valor del metadato si existe y el post ID es válido, de lo contrario null.
     */
    public static function meta(string $metaKey, int $postId = 0)
    {
        if (!$postId) {
            $postId = get_the_ID();
        }

        if (!$postId || $postId <= 0) {
            return null;
        }

        return get_post_meta($postId, $metaKey, true);
    }
}