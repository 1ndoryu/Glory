<?php

namespace Glory\Core;

/**
 * GloryLogger gestiona el registro de eventos y errores de la aplicación.
 *
 * En lugar de guardar logs en la base de datos, utiliza el sistema de logs nativo de PHP (error_log).
 * Los logs se acumulan en un buffer durante la ejecución y se escriben todos juntos al final del script (hook 'shutdown'),
 * optimizando el rendimiento. Permite configurar un nivel mínimo de log para controlar la verbosidad.
 */
class GloryLogger
{
    // Niveles de Log
    const nivelInfo        = 10;
    const nivelAdvertencia = 20;
    const nivelError       = 30;
    const nivelCritico     = 50;

    private static $nivelesValidos = [
        self::nivelInfo,
        self::nivelAdvertencia,
        self::nivelError,
        self::nivelCritico,
    ];

    private static $nivelMinimoGuardado       = self::nivelError;
    private static $bufferLogs                = [];
    private static $hookGuardarLogsRegistrado = false;

    /**
     * Inicializa el logger y opcionalmente establece el nivel mínimo de log a registrar.
     *
     * @param int|null $nivel El nivel mínimo para que los logs se guarden.
     */
    public static function init(?int $nivel = null): void
    {
        if (!is_null($nivel)) {
            self::setNivelMinimoGuardado($nivel);
        }
    }

    /**
     * Establece el nivel mínimo de log que se debe guardar.
     *
     * Solo los grupos de logs cuya criticidad máxima sea igual o superior a este nivel serán guardados.
     *
     * @param int $nivel El nivel de log (ej. self::nivelInfo, self::nivelError).
     */
    public static function setNivelMinimoGuardado(int $nivel): void
    {
        if (in_array($nivel, self::$nivelesValidos, true)) {
            self::$nivelMinimoGuardado = $nivel;
        }
    }

    /**
     * Registra un mensaje de nivel informativo.
     */
    public static function info(string $mensaje, array $contexto = []): void
    {
        self::registrar(self::nivelInfo, $mensaje, $contexto);
    }

    /**
     * Registra un mensaje de advertencia.
     */
    public static function warning(string $mensaje, array $contexto = []): void
    {
        self::registrar(self::nivelAdvertencia, $mensaje, $contexto);
    }

    /**
     * Registra un mensaje de error.
     */
    public static function error(string $mensaje, array $contexto = []): void
    {
        self::registrar(self::nivelError, $mensaje, $contexto);
    }

    /**
     * Registra un mensaje crítico.
     */
    public static function critical(string $mensaje, array $contexto = []): void
    {
        self::registrar(self::nivelCritico, $mensaje, $contexto);
    }

    /**
     * Guarda los logs acumulados en el buffer al final de la ejecución.
     *
     * Este método se engancha al hook 'shutdown' de WordPress.
     */
    public static function guardarLogsEnBuffer(): void
    {
        if (empty(self::$bufferLogs)) {
            return;
        }
        foreach (self::$bufferLogs as $nombreFuncion => $datosLog) {
            if ($datosLog['nivelMaximo'] >= self::$nivelMinimoGuardado) {
                self::crearEntradaLog($nombreFuncion, $datosLog);
            }
        }
        // Limpiar el buffer para la siguiente petición
        self::$bufferLogs                = [];
        self::$hookGuardarLogsRegistrado = false;
    }

    /**
     * Añade un registro al buffer de logs.
     *
     * @param int    $nivel   Nivel del log.
     * @param string $mensaje Mensaje del log.
     * @param array  $contexto Datos adicionales.
     */
    private static function registrar(int $nivel, string $mensaje, array $contexto = []): void
    {
        $nombreLlamador = self::getNombreLlamador();
        $huellaLog      = md5($nivel . '|' . $mensaje . '|' . serialize($contexto));

        if (!isset(self::$bufferLogs[$nombreLlamador])) {
            self::$bufferLogs[$nombreLlamador] = [
                'mensajes'     => [],
                'nivelMaximo'  => self::nivelInfo,
                'hashesUnicos' => [],
            ];
        }

        // Evitar duplicados exactos en la misma ejecución
        if (isset(self::$bufferLogs[$nombreLlamador]['hashesUnicos'][$huellaLog])) {
            return;
        }

        self::$bufferLogs[$nombreLlamador]['hashesUnicos'][$huellaLog] = true;
        self::$bufferLogs[$nombreLlamador]['mensajes'][]              = [
            'marcaTiempo' => microtime(true),
            'nivel'       => $nivel,
            'mensaje'     => $mensaje,
            'contexto'    => $contexto,
        ];

        if ($nivel > self::$bufferLogs[$nombreLlamador]['nivelMaximo']) {
            self::$bufferLogs[$nombreLlamador]['nivelMaximo'] = $nivel;
        }

        self::registrarHookGuardarLogs();
    }

    /**
     * Obtiene el nombre de la función o método que invocó al logger.
     *
     * @return string
     */
    private static function getNombreLlamador(): string
    {
        $traza = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        for ($i = 2; $i < count($traza); $i++) {
            if (isset($traza[$i]['class']) && $traza[$i]['class'] === self::class) {
                continue;
            }
            if (isset($traza[$i])) {
                $infoLlamador = $traza[$i];
                $funcion      = $infoLlamador['function'] ?? '[unknown_function]';
                if (isset($infoLlamador['class'])) {
                    $funcion = $infoLlamador['class'] . '::' . $funcion;
                }
                return $funcion;
            }
        }
        return '[unknown_caller]';
    }

    /**
     * Registra el hook de 'shutdown' una sola vez por petición.
     */
    private static function registrarHookGuardarLogs(): void
    {
        if (!self::$hookGuardarLogsRegistrado) {
            add_action('shutdown', [self::class, 'guardarLogsEnBuffer'], 100);
            self::$hookGuardarLogsRegistrado = true;
        }
    }

    /**
     * Formatea y escribe un grupo de logs en el archivo de error del servidor.
     *
     * @param string $nombreFuncion El nombre de la función/método que generó los logs.
     * @param array  $datosLog      Los datos del log (mensajes, nivel, etc.).
     */
    private static function crearEntradaLog(string $nombreFuncion, array $datosLog): void
    {
        if (empty($datosLog['mensajes'])) {
            return;
        }

        $bloqueMensajes = "--- GloryLogger Start: {$nombreFuncion} ---\n";

        foreach ($datosLog['mensajes'] as $registro) {
            $nivelTexto = 'INFO';
            switch ($registro['nivel']) {
                case self::nivelAdvertencia:
                    $nivelTexto = 'WARNING';
                    break;
                case self::nivelError:
                    $nivelTexto = 'ERROR';
                    break;
                case self::nivelCritico:
                    $nivelTexto = 'CRITICAL';
                    break;
            }

            $micro = sprintf("%06d", ($registro['marcaTiempo'] - floor($registro['marcaTiempo'])) * 1000000);
            $fecha = new \DateTime(date('Y-m-d H:i:s.' . $micro, (int)$registro['marcaTiempo']));

            $lineaLog = sprintf(
                "[%s] [%s] %s",
                $fecha->format("Y-m-d H:i:s.u"),
                $nivelTexto,
                $registro['mensaje']
            );

            if (!empty($registro['contexto'])) {
                $contexto = preg_replace('/\s+/', ' ', print_r($registro['contexto'], true));
                $lineaLog .= " | Context: " . $contexto;
            }

            $bloqueMensajes .= $lineaLog . "\n";
        }

        $bloqueMensajes .= "--- GloryLogger End: {$nombreFuncion} ---\n";

        error_log($bloqueMensajes);
    }
}
