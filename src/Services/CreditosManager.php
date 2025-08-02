<?php

namespace Glory\Manager;

use Glory\Core\GloryLogger;

/**
 * Gestiona el sistema de créditos para los usuarios.
 *
 * Proporciona métodos para agregar, quitar, establecer y consultar créditos.
 * Permite saldos negativos. Incluye un sistema de recarga periódica configurable
 * que puede ser activado o desactivado globalmente.
 */
class CreditosManager
{
    /**
     * @var string Meta key para los créditos del usuario.
     */
    private static $creditosMetaKey = 'gloryCreditos';

    /**
     * @var string Option name para el estado de la recarga.
     */
    private static $recargaActivaOpcion = 'gloryRecargaActiva';

    /**
     * @var string Option name para la cantidad de la recarga.
     */
    private static $recargaCantidadOpcion = 'gloryRecargaCantidad';

    /**
     * @var string Hook para el evento cron de recarga.
     */
    private static $recargaHook = 'gloryRecargaCreditos';

    /**
     * Inicializa la clase.
     * Configura el evento cron para la recarga periódica.
     */
    public static function init(): void
    {
        add_action(self::$recargaHook, [self::class, 'ejecutarRecargaGlobal']);
        self::gestionarProgramacionCron();
    }

    /**
     * Obtiene el saldo de créditos de un usuario.
     *
     * @param int $usuarioId ID del usuario.
     * @return int El número de créditos.
     */
    public static function getCreditos(int $usuarioId): int
    {
        $creditos = get_user_meta($usuarioId, self::$creditosMetaKey, true);
        return is_numeric($creditos) ? (int)$creditos : 0;
    }

    /**
     * Establece un número específico de créditos para un usuario.
     *
     * @param int $usuarioId ID del usuario.
     * @param int $cantidad La cantidad total de créditos a establecer (puede ser negativa).
     * @param string $motivo Razón de la modificación.
     * @return bool Siempre true.
     */
    public static function setCreditos(int $usuarioId, int $cantidad, string $motivo = 'Ajuste manual'): bool
    {
        $saldoPrevio = self::getCreditos($usuarioId);
        update_user_meta($usuarioId, self::$creditosMetaKey, $cantidad);

        GloryLogger::info('Créditos establecidos directamente.', [
            'usuarioId' => $usuarioId,
            'saldoPrevio' => $saldoPrevio,
            'saldoNuevo' => $cantidad,
            'motivo' => $motivo
        ]);

        return true;
    }

    /**
     * Agrega créditos a un usuario.
     *
     * @param int $usuarioId ID del usuario.
     * @param int $cantidad Cantidad de créditos a agregar.
     * @param string $motivo Razón de la transacción.
     * @return bool True si la operación fue exitosa, false si la cantidad es cero.
     */
    public static function agregar(int $usuarioId, int $cantidad, string $motivo = 'Adición de créditos'): bool
    {
        if ($cantidad === 0) {
            GloryLogger::info('Intento de agregar cero créditos.', [
                'usuarioId' => $usuarioId
            ]);
            return false;
        }

        $saldoPrevio = self::getCreditos($usuarioId);
        $saldoNuevo = $saldoPrevio + $cantidad;

        update_user_meta($usuarioId, self::$creditosMetaKey, $saldoNuevo);

        GloryLogger::info('Créditos agregados exitosamente.', [
            'usuarioId' => $usuarioId,
            'cantidadAgregada' => $cantidad,
            'saldoPrevio' => $saldoPrevio,
            'saldoNuevo' => $saldoNuevo,
            'motivo' => $motivo
        ]);

        return true;
    }

    /**
     * Quita créditos a un usuario.
     *
     * @param int $usuarioId ID del usuario.
     * @param int $cantidad Cantidad de créditos a quitar.
     * @param string $motivo Razón de la transacción.
     * @return bool True si la operación fue exitosa, false si la cantidad es cero.
     */
    public static function quitar(int $usuarioId, int $cantidad, string $motivo = 'Uso de créditos'): bool
    {
        if ($cantidad === 0) {
            GloryLogger::info('Intento de quitar cero créditos.', [
                'usuarioId' => $usuarioId
            ]);
            return false;
        }

        $saldoPrevio = self::getCreditos($usuarioId);
        $saldoNuevo = $saldoPrevio - $cantidad;
        update_user_meta($usuarioId, self::$creditosMetaKey, $saldoNuevo);

        GloryLogger::info('Créditos quitados exitosamente.', [
            'usuarioId' => $usuarioId,
            'cantidadQuitada' => $cantidad,
            'saldoPrevio' => $saldoPrevio,
            'saldoNuevo' => $saldoNuevo,
            'motivo' => $motivo
        ]);

        return true;
    }

    /**
     * Configura los parámetros de la recarga periódica.
     *
     * @param bool $activa True para activar la recarga, false para desactivarla.
     * @param int $cantidad El límite de créditos hasta el cual se recargará.
     */
    public static function recargaPeriodica(bool $activa, int $cantidad, int $dias = 1): void
    {
        if ($cantidad < 0) {
            GloryLogger::error('Se intentó configurar la recarga con una cantidad negativa.', ['cantidad' => $cantidad]);
            return;
        }

        if ($dias <= 0) {
            GloryLogger::error('El intervalo de días para la recarga debe ser un entero positivo.', ['dias' => $dias]);
            return;
        }

        update_option(self::$recargaActivaOpcion, $activa);
        update_option(self::$recargaCantidadOpcion, $cantidad);
        update_option('gloryRecargaIntervalo', $dias); // Nueva opción para el intervalo

        self::gestionarProgramacionCron();

        GloryLogger::info('Configuración de recarga periódica actualizada.', [
            'activa' => $activa,
            'cantidad' => $cantidad,
            'intervaloDias' => $dias
        ]);
    }

    /**
     * Ejecuta la recarga de créditos para todos los usuarios.
     * Este método es invocado por el cron de WordPress.
     */
    public static function ejecutarRecargaGlobal(): void
    {
        $recargaActiva = (bool) get_option(self::$recargaActivaOpcion, false);
        if (!$recargaActiva) {
            GloryLogger::info('Ejecución de recarga global omitida: la función está desactivada.');
            return;
        }

        $cantidadRecarga = (int) get_option(self::$recargaCantidadOpcion, 0);
        if ($cantidadRecarga <= 0) {
            GloryLogger::warning('Ejecución de recarga global omitida: la cantidad es cero o negativa.', [
                'cantidad' => $cantidadRecarga
            ]);
            return;
        }

        GloryLogger::info('Iniciando proceso de recarga global de créditos.', [
            'cantidadLimite' => $cantidadRecarga
        ]);

        $usuarios = get_users(['fields' => 'ID']);
        foreach ($usuarios as $usuarioId) {
            $creditosActuales = self::getCreditos($usuarioId);
            if ($creditosActuales < $cantidadRecarga) {
                self::setCreditos($usuarioId, $cantidadRecarga, 'Recarga periódica automática');
            }
        }

        GloryLogger::info('Proceso de recarga global de créditos finalizado.');
    }

    /**
     * Gestiona la programación o desprogramación del evento cron.
     */
    private static function gestionarProgramacionCron(): void
    {
        $recargaActiva = (bool) get_option(self::$recargaActivaOpcion, false);

        if ($recargaActiva && !wp_next_scheduled(self::$recargaHook)) {
            wp_schedule_event(time(), 'daily', self::$recargaHook);
            GloryLogger::info('Evento cron de recarga de créditos programado.');
        } elseif (!$recargaActiva && wp_next_scheduled(self::$recargaHook)) {
            wp_clear_scheduled_hook(self::$recargaHook);
            GloryLogger::info('Evento cron de recarga de créditos desprogramado.');
        }
    }
}
