<?php 

function imagenPerfil() {

    $usuariID = get_current_user_id();
    ob_start()
    ?>

    <div class="imagenPerfil">
        <img src="<?php echo get_avatar_url($usuariID); ?>" alt="imagenPerfil">
    </div>
    
    <?php 
    return ob_get_clean();
}