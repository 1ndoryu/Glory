<?php
// Glory/src/Services/TokenManager.php

namespace Glory\Services;

use WP_Error;

class TokenManager
{
    private const OPCION_CLAVE_SECRETA = 'glory_api_secret_key';
    private const DURACION_TOKEN_SEGUNDOS = 3600; // 1 hora

    private static function getClaveSecreta(): string
    {
        $clave = get_option(self::OPCION_CLAVE_SECRETA);
        if (empty($clave)) {
            $clave = wp_generate_password(64, true, true);
            update_option(self::OPCION_CLAVE_SECRETA, $clave);
        }
        return $clave;
    }

    public static function generarToken(int $idUsuario): string
    {
        $claveSecreta = self::getClaveSecreta();
        $tiempoEmision = time();
        $tiempoExpiracion = $tiempoEmision + self::DURACION_TOKEN_SEGUNDOS;

        $payload = [
            'iss' => get_bloginfo('url'), // Emisor
            'iat' => $tiempoEmision,      // Emitido a las
            'exp' => $tiempoExpiracion,   // Expiración
            'sub' => $idUsuario,         // Sujeto (ID de usuario)
        ];

        $payloadCodificado = base64_encode(json_encode($payload));
        $firma = hash_hmac('sha256', $payloadCodificado, $claveSecreta);

        return $payloadCodificado . '.' . $firma;
    }

    public static function validarToken(string $token): int|WP_Error
    {
        $partes = explode('.', $token);
        if (count($partes) !== 2) {
            return new WP_Error('token_invalido', 'El formato del token es incorrecto.');
        }

        list($payloadCodificado, $firmaRecibida) = $partes;
        $claveSecreta = self::getClaveSecreta();

        $firmaCalculada = hash_hmac('sha256', $payloadCodificado, $claveSecreta);

        if (!hash_equals($firmaCalculada, $firmaRecibida)) {
            return new WP_Error('token_firma_invalida', 'La firma del token no es válida.');
        }

        $payload = json_decode(base64_decode($payloadCodificado), true);

        if ($payload === null) {
            return new WP_Error('token_payload_invalido', 'El payload del token no pudo ser decodificado.');
        }

        if (time() > $payload['exp']) {
            return new WP_Error('token_expirado', 'El token ha expirado.');
        }

        return (int) $payload['sub'];
    }

    public static function revocarToken(int $idUsuario): bool
    {
        // En una implementación sin estado (stateless) como esta, la revocación real
        // implicaría una lista negra. Para simplificar, esta función es un marcador de posición.
        // Una estrategia sería cambiar la clave secreta, invalidando todos los tokens.
        return true;
    }
}
