<?php
// Glory/src/Utility/EmailUtility.php

namespace Glory\Utility;

use Glory\Core\GloryLogger;

/**
 * Clase de utilidad para el envío de correos electrónicos.
 */
class EmailUtility
{
    /**
     * Envía un correo electrónico a todos los administradores del sitio.
     *
     * @param string $asunto  El asunto del correo.
     * @param string $mensaje El cuerpo del correo (puede ser HTML).
     * @param array  $headers Cabeceras adicionales para el correo.
     * @return bool True si el correo se envió con éxito, false en caso contrario.
     */
    public static function sendToAdmins(string $asunto, string $mensaje, array $headers = []): bool
    {
        // Obtener el correo electrónico del administrador principal desde las opciones de WordPress.
        $emailAdmin = get_option('admin_email');
        if (empty($emailAdmin)) {
            GloryLogger::error('No se pudo encontrar el email del administrador en las opciones de WordPress.');
            return false;
        }

        // Para asegurar que el correo se envíe como HTML, establecemos la cabecera por defecto.
        $defaultHeaders = [
            'Content-Type: text/html; charset=UTF-8'
        ];

        // Combinamos las cabeceras por defecto con las que se pasen como argumento.
        $finalHeaders = array_merge($defaultHeaders, $headers);

        // Enviamos el correo usando la función nativa de WordPress.
        $enviado = wp_mail($emailAdmin, $asunto, $mensaje, $finalHeaders);

        if (!$enviado) {
            GloryLogger::error('wp_mail() falló al intentar enviar el correo al administrador.', [
                'to' => $emailAdmin,
                'subject' => $asunto
            ]);
        } else {
            GloryLogger::info('Correo enviado exitosamente al administrador.', [
                'to' => $emailAdmin,
                'subject' => $asunto
            ]);
        }

        return $enviado;
    }
}
