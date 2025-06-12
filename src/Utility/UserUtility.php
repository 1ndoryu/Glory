<?php

namespace Glory\Utility;

/**
 * Clase de utilidad para funciones relacionadas con el usuario.
 * Reemplaza los helpers procedurales para una mejor organización y autoloading.
 */
class UserUtility
{
    /**
     * Verifica si el usuario actual está logueado.
     *
     * @return bool True si el usuario está logueado, false en caso contrario.
     */
    public static function logeado(): bool
    {
        return is_user_logged_in();
    }

    /**
     * Verifica si el usuario actual tiene al menos uno de los roles especificados.
     *
     * @param string|array $roles Un string con el nombre del rol o un array de nombres de roles a verificar.
     * @return bool True si el usuario tiene al menos uno de los roles y está logueado, false en caso contrario.
     */
    public static function tieneRoles($roles): bool
    {
        if (!self::logeado()) {
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

    /**
     * Obtiene el valor de un metadato para un usuario específico.
     *
     * @param string $metaKey La clave del metadato que se desea obtener.
     * @param int $usuarioId Opcional. El ID del usuario. Si es 0 o no se provee, se usará el ID del usuario actual.
     * @return mixed|null El valor del metadato si existe y el ID de usuario es válido, de lo contrario null.
     */
    public static function meta(string $metaKey, int $usuarioId = 0)
    {
        if (!$usuarioId) {
            $usuarioId = get_current_user_id();
        }

        if (!$usuarioId || $usuarioId <= 0) {
            return null;
        }

        return get_user_meta($usuarioId, $metaKey, true);
    }
}
