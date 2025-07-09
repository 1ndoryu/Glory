<?php

namespace Glory\Utility;

/**
 * Clase de utilidad para funciones relacionadas con el usuario.
 * Reemplaza los helpers procedurales para una mejor organización y autoloading.
 */
class UserUtility
{

    public static function logeado(): bool
    {
        return is_user_logged_in();
    }

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
