<?php 

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