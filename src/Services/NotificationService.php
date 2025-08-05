<?php

namespace Glory\Services;

use Glory\Core\GloryLogger;

class NotificationService
{
    public static function notificarNuevaReservaCliente(int $postId): void
    {
        $correoCliente = get_post_meta($postId, 'correo_cliente', true);
        if (!is_email($correoCliente)) {
            GloryLogger::warning('No se pudo enviar email de confirmación: correo de cliente inválido.', ['post_id' => $postId]);
            return;
        }

        $nombreCliente = get_the_title($postId);
        $fecha = get_post_meta($postId, 'fecha_reserva', true);
        $hora = get_post_meta($postId, 'hora_reserva', true);

        $servicios = get_the_terms($postId, 'servicio');
        $nombreServicio = (!is_wp_error($servicios) && !empty($servicios)) ? $servicios[0]->name : 'No especificado';

        $asunto = 'Confirmación de tu reserva en Barbería El Kinze';
        $mensaje = "Hola {$nombreCliente},\n\n";
        $mensaje .= "Tu reserva ha sido confirmada con éxito.\n\n";
        $mensaje .= "Detalles:\n";
        $mensaje .= "- Servicio: {$nombreServicio}\n";
        $mensaje .= "- Fecha: {$fecha}\n";
        $mensaje .= "- Hora: {$hora}\n\n";
        $mensaje .= "¡Te esperamos!\n";
        $mensaje .= "Barbería El Kinze";

        wp_mail($correoCliente, $asunto, $mensaje);
        GloryLogger::info('Email de confirmación enviado al cliente.', ['post_id' => $postId, 'email' => $correoCliente]);
    }

    public static function notificarNuevaReservaAdmin(int $postId): void
    {
        $adminEmail = get_option('admin_email');
        if (!is_email($adminEmail)) {
            GloryLogger::warning('No se pudo enviar email de notificación al admin: email de admin no configurado.', ['post_id' => $postId]);
            return;
        }

        $nombreCliente = get_the_title($postId);
        $fecha = get_post_meta($postId, 'fecha_reserva', true);
        $hora = get_post_meta($postId, 'hora_reserva', true);

        $servicios = get_the_terms($postId, 'servicio');
        $nombreServicio = (!is_wp_error($servicios) && !empty($servicios)) ? $servicios[0]->name : 'No especificado';

        $barberos = get_the_terms($postId, 'barbero');
        $nombreBarbero = (!is_wp_error($barberos) && !empty($barberos)) ? $barberos[0]->name : 'No especificado';

        $asunto = 'Nueva Reserva Creada en Barbería El Kinze';
        $mensaje = "Se ha creado una nueva reserva.\n\n";
        $mensaje .= "Detalles:\n";
        $mensaje .= "- Cliente: {$nombreCliente}\n";
        $mensaje .= "- Servicio: {$nombreServicio}\n";
        $mensaje .= "- Barbero: {$nombreBarbero}\n";
        $mensaje .= "- Fecha: {$fecha}\n";
        $mensaje .= "- Hora: {$hora}\n\n";
        $mensaje .= "Puedes ver los detalles en el panel de administración.";

        wp_mail($adminEmail, $asunto, $mensaje);
        GloryLogger::info('Email de notificación enviado al admin.', ['post_id' => $postId, 'admin_email' => $adminEmail]);
    }
}
