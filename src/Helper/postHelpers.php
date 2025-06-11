<?php
/**
 * Obtiene el valor de un metadato para un post específico.
 *
 * @param string $metaKey La clave del metadato que se desea obtener.
 * @param int $postId Opcional. El ID del post. Si es 0 o no se provee, se intentará obtener el ID del post actual en el loop.
 * @return mixed|null El valor del metadato si existe y el post ID es válido, de lo contrario null.
 */
function metaPost(string $metaKey, int $postId = 0)
{
    if (!$postId) {
        $postId = get_the_ID();
    }

    // Si después de intentar obtener el ID del post actual, sigue siendo inválido, retorna null.
    if (!$postId || $postId <= 0) {
        return null;
    }

    return get_post_meta($postId, $metaKey, true);
}

// Considerar convertir estos helpers en métodos de una clase de utilidad estática para mejor organización y autoloading. - Jules