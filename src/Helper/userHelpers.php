<?php 
// Glory\src\Helper\userHelpers.php

function logeado() {
    return is_user_logged_in();
}

function tieneRoles($roles) {
    if (!estaUsuarioLogueado()) {
        return false;
    }

    $rolesRequeridos = (array) $roles;

    foreach ($rolesRequeridos as $rol) {
        if (current_user_can($rol)) {
            return true;
        }
    }

    return false;
}

function metaUsuario(string $metaKey, int $usuarioId = 0)
{
    if (!$usuarioId) {
        $usuarioId = get_current_user_id();
    }

    if (!$usuarioId) {
        return ''; 
    }

    return get_user_meta($usuarioId, $metaKey, true);
}