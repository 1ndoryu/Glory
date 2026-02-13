<?php

namespace Glory\Core;

/**
 * Formateador de entradas de log para GloryLogger.
 * Responsabilidad: convertir datos de log en texto legible para error_log().
 */
class LogFormatter
{
    /* Mapa de niveles numéricos a texto */
    private const MAPA_NIVELES = [
        GloryLogger::NIVEL_INFO        => 'INFO',
        GloryLogger::NIVEL_ADVERTENCIA => 'ADVERTENCIA',
        GloryLogger::NIVEL_ERROR       => 'ERROR',
        GloryLogger::NIVEL_CRITICO     => 'CRITICO',
    ];

    /**
     * Formatea y escribe un grupo de logs al sistema de logs de PHP.
     * Los mensajes se agrupan por la función/método que los originó.
     *
     * @param string $nombreFuncion Nombre de la función/método que originó los logs.
     * @param array  $datosLog      Array con los mensajes a registrar.
     */
    public static function formatearYEscribir(string $nombreFuncion, array $datosLog): void
    {
        if (empty($datosLog['mensajes'])) {
            return;
        }

        $bloqueMensajes = "--- GloryLogger Inicio: {$nombreFuncion} ---\n";

        foreach ($datosLog['mensajes'] as $registro) {
            $nivelTexto = self::MAPA_NIVELES[$registro['nivel']] ?? 'INFO';

            $micro = sprintf("%06d", ($registro['marcaTiempo'] - floor($registro['marcaTiempo'])) * 1000000);
            $fecha = new \DateTime(date('Y-m-d H:i:s.' . $micro, (int)$registro['marcaTiempo']));

            $lineaLog = sprintf(
                "[%s] [%s] %s",
                $fecha->format("Y-m-d H:i:s.u"),
                $nivelTexto,
                $registro['mensaje']
            );

            if (!empty($registro['contexto'])) {
                $contextoSerializado = print_r($registro['contexto'], true);
                $contextoNormalizado = preg_replace('/\s+/', ' ', $contextoSerializado);
                $lineaLog .= " | Contexto: " . $contextoNormalizado;
            }

            $bloqueMensajes .= $lineaLog . "\n";
        }

        $bloqueMensajes .= "--- GloryLogger Fin: {$nombreFuncion} ---\n";

        error_log($bloqueMensajes);
    }
}
