<?php
/**
 * Renderizador de Perfil de Usuario
 *
 * Muestra la imagen de perfil del usuario actual, priorizando un metadato
 * personalizado ('imagenPerfil') o usando el avatar por defecto de WordPress (Gravatar).
 *
 * @package Glory\Components
 */

namespace Glory\Components;

/**
 * Clase PerfilRenderer.
 *
 * Componente visual para la imagen del usuario logueado.
 */
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
        $usuarioId = get_current_user_id();
        if (!$usuarioId) {
            return '';
        }

        $imagenPerfilId  = get_user_meta($usuarioId, 'imagenPerfil', true);
        $imagenPerfilUrl = '';

        if (!empty($imagenPerfilId)) {
            $imagenPerfilUrl = wp_get_attachment_image_url($imagenPerfilId, 'thumbnail');
        } else {
            $imagenPerfilUrl = get_avatar_url($usuarioId);
        }

        if (empty($imagenPerfilUrl)) {
            return '';
        }

        ob_start();
        ?>
        <div class="imagenPerfil">
            <img src="<?php echo esc_url($imagenPerfilUrl); ?>" alt="<?php esc_attr_e('Imagen de Perfil', 'glory'); ?>">
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
