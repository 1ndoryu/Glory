<?php

/**
 * Renderiza la imagen de perfil del usuario.
 *
 * @return string El HTML del componente de imagen de perfil.
 */
function renderImagenPerfil(): string
{
    $usuarioID = get_current_user_id();
    $imagenPerfilId = get_user_meta($usuarioID, 'imagenPerfil', true);

    if (!empty($imagenPerfilId)) {
        $imagenPerfil = wp_get_attachment_image_url($imagenPerfilId, 'thumbnail');
    } else {
        $imagenPerfil = get_avatar_url($usuarioID);
    }

    ob_start();
?>
    <div class="imagenPerfil">
        <img src="<?php echo esc_url($imagenPerfil); ?>" alt="Imagen de Perfil">
    </div>
<?php
    return ob_get_clean();
}
