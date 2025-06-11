<?php
// Glory\src\Helper\userHelpers.php

/**
 * Verifica si el usuario actual está logueado.
 *
 * @return bool True si el usuario está logueado, false en caso contrario.
 */
function logeado(): bool
{
    return is_user_logged_in();
}

/**
 * Verifica si el usuario actual tiene al menos uno de los roles especificados.
 *
 * @param string|array $roles Un string con el nombre del rol o un array de nombres de roles a verificar.
 * @return bool True si el usuario tiene al menos uno de los roles y está logueado, false en caso contrario.
 */
function tieneRoles($roles): bool
{
    // Primero verifica si el usuario está logueado usando la función helper local.
    if (!logeado()) {
        return false;
    }

    $rolesRequeridos = (array) $roles; // Asegura que $roles sea un array.

    // Itera sobre los roles requeridos. Si el usuario tiene alguno, retorna true.
    foreach ($rolesRequeridos as $rol) {
        if (current_user_can($rol)) {
            return true;
        }
    }

    return false; // El usuario no tiene ninguno de los roles especificados.
}

/**
 * Obtiene el valor de un metadato para un usuario específico.
 *
 * @param string $metaKey La clave del metadato que se desea obtener.
 * @param int $usuarioId Opcional. El ID del usuario. Si es 0 o no se provee, se usará el ID del usuario actual.
 * @return mixed|null El valor del metadato si existe y el ID de usuario es válido, de lo contrario null.
 */
function metaUsuario(string $metaKey, int $usuarioId = 0)
{
    if (!$usuarioId) {
        $usuarioId = get_current_user_id();
    }

    // Si el ID de usuario no es válido (0 o no se pudo obtener), retorna null.
    if (!$usuarioId || $usuarioId <= 0) {
        return null;
    }

    return get_user_meta($usuarioId, $metaKey, true);
}

// Considerar convertir estos helpers en métodos de una clase de utilidad estática para mejor organización y autoloading. - Jules