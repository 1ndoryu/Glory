<?php

namespace Glory\Components;

class PerfilRenderer
{
    /**
     * Imprime el HTML de la imagen de perfil del usuario actual.
     */
    public static function render(): void
    {
        echo self::getHtml();
    }

    /**
     * Obtiene el HTML de la imagen de perfil del usuario actual.
     *
     * @return string El HTML de la imagen o una cadena vacía si no hay usuario o imagen.
     */
    public static function getHtml(): string
    {
        $usuarioID = get_current_user_id();
        if (!$usuarioID) {
            return '';
        }

        $imagenPerfilId = get_user_meta($usuarioID, 'imagenPerfil', true);
        $imagenPerfilUrl = '';

        if (!empty($imagenPerfilId)) {
            $imagenPerfilUrl = wp_get_attachment_image_url($imagenPerfilId, 'thumbnail');
        } else {
            $imagenPerfilUrl = get_avatar_url($usuarioID);
        }

        if (empty($imagenPerfilUrl)) {
            return '';
        }

        ob_start();
        ?>
        <div class="imagenPerfil">
            <img src="<?php echo esc_url($imagenPerfilUrl); ?>" alt="Imagen de Perfil">
        </div>
        <?php
        return ob_get_clean();
    }
}