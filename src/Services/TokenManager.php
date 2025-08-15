<?php

namespace Glory\Services;

use Glory\Manager\OpcionManager;
use WP_Error;

class TokenManager
{
    /**
     * Valida el token Bearer recibido contra el configurado en opciones del tema.
     *
     * @param string $token
     * @return true|WP_Error
     */
    public static function validarToken(string $token)
    {
        $tokenConfigurado = (string) OpcionManager::get('glory_api_token', '');
        $tokenConfigurado = trim($tokenConfigurado);

        if ($tokenConfigurado === '') {
            return new WP_Error(
                'token_no_configurado',
                'El token de la API no está configurado. Defínelo en las opciones del tema.',
                ['status' => 403]
            );
        }

        if (!hash_equals($tokenConfigurado, trim($token))) {
            return new WP_Error('token_invalido', 'Token inválido.', ['status' => 401]);
        }

        return true;
    }
}
// Fin TokenManager basado en token fijo.
