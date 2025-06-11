<?php

namespace Glory\Core;

/**
 * GloryLogger gestiona el registro de eventos y errores de la aplicación.
 *
 * Utiliza el sistema de logs nativo de PHP (error_log) en lugar de sistemas más pesados para el registro inmediato.
 * Los logs se acumulan en un buffer durante la ejecución y se escriben todos juntos al final del script (hook 'shutdown'),
 * optimizando el rendimiento. Permite configurar un nivel mínimo de log para controlar la verbosidad.
 * @author @wandorius
 */
class GloryLogger
{
    // Niveles de Log
    const NIVEL_INFO        = 10;
    const NIVEL_ADVERTENCIA = 20;
    const NIVEL_ERROR       = 30;
    const NIVEL_CRITICO     = 50;

    private static array $nivelesValidos = [
        self::NIVEL_INFO,
        self::NIVEL_ADVERTENCIA,
        self::NIVEL_ERROR,
        self::NIVEL_CRITICO,
    ];

    private static int $nivelMinimoGuardado       = self::NIVEL_ERROR;
    private static array $bufferLogs                = [];
    private static bool $hookGuardarLogsRegistrado = false;

    /**
     * Inicializa el logger y opcionalmente establece el nivel mínimo de log a registrar.
     *
     * @param int|null $nivelMinimoRegistrar El nivel mínimo para que los logs se guarden.
     */
    public static function init(?int $nivelMinimoRegistrar = null): void
    {
        if (!is_null($nivelMinimoRegistrar)) {
            self::setNivelMinimoGuardado($nivelMinimoRegistrar);
        }
    }

    /**
     * Establece el nivel mínimo de log que se debe guardar.
     *
     * Solo los grupos de logs cuya criticidad máxima sea igual o superior a este nivel serán guardados.
     *
     * @param int $nivelMinimo El nivel de log (ej. self::NIVEL_INFO, self::NIVEL_ERROR).
     */
    public static function setNivelMinimoGuardado(int $nivelMinimo): void
    {
        if (in_array($nivelMinimo, self::$nivelesValidos, true)) {
            self::$nivelMinimoGuardado = $nivelMinimo;
        }
    }

    /**
     * Registra un mensaje de nivel informativo.
     * @param string $mensaje Mensaje del log.
     * @param array  $contexto Datos adicionales.
     */
    public static function info(string $mensaje, array $contexto = []): void
    {
        self::registrar(self::NIVEL_INFO, $mensaje, $contexto);
    }

    /**
     * Registra un mensaje de advertencia.
     * @param string $mensaje Mensaje del log.
     * @param array  $contexto Datos adicionales.
     */
    public static function warning(string $mensaje, array $contexto = []): void
    {
        self::registrar(self::NIVEL_ADVERTENCIA, $mensaje, $contexto);
    }

    /**
     * Registra un mensaje de error.
     * @param string $mensaje Mensaje del log.
     * @param array  $contexto Datos adicionales.
     */
    public static function error(string $mensaje, array $contexto = []): void
    {
        self::registrar(self::NIVEL_ERROR, $mensaje, $contexto);
    }

    /**
     * Registra un mensaje crítico.
     * @param string $mensaje Mensaje del log.
     * @param array  $contexto Datos adicionales.
     */
    public static function critical(string $mensaje, array $contexto = []): void
    {
        self::registrar(self::NIVEL_CRITICO, $mensaje, $contexto);
    }

    /**
     * Guarda los logs acumulados en el buffer al final de la ejecución.
     *
     * Este método se engancha al hook 'shutdown' de WordPress para procesar los logs acumulados al final de la petición.
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
     * @param int    $nivel    Nivel del log (usar constantes NIVEL_*).
     * @param string $mensaje  Mensaje del log.
     * @param array  $contexto Datos adicionales para el log.
     */
    private static function registrar(int $nivel, string $mensaje, array $contexto = []): void
    {
        $nombreLlamador = self::getNombreLlamador();
        $huellaLog      = md5($nivel . '|' . $mensaje . '|' . serialize($contexto));

        if (!isset(self::$bufferLogs[$nombreLlamador])) {
            self::$bufferLogs[$nombreLlamador] = [
                'mensajes'     => [],
                'nivelMaximo'  => self::NIVEL_INFO,
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
     * Esta función analiza la traza de depuración para encontrar el origen de la llamada al log.
     * La lógica actual es adecuada para la mayoría de los casos en WordPress.
     * Una posible mejora futura podría ser limitar la profundidad de búsqueda en `debug_backtrace`
     * para optimizar ligeramente el rendimiento en entornos de alta carga.
     * @return string Nombre del llamador (Clase::metodo o funcion).
     */
    private static function getNombreLlamador(): string
    {
        $traza = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        // Comenzamos en $i = 2 para saltar getNombreLlamador y registrar/info/warning/error/critical.
        for ($i = 2; $i < count($traza); $i++) {
            // Si la entrada de la traza es una llamada dentro de esta misma clase, la saltamos.
            if (isset($traza[$i]['class']) && $traza[$i]['class'] === self::class) {
                continue;
            }
            // Si encontramos una entrada válida, construimos el nombre del llamador.
            if (isset($traza[$i])) {
                $infoLlamador = $traza[$i];
                $funcion      = $infoLlamador['function'] ?? '[funcion_desconocida]';
                if (isset($infoLlamador['class'])) {
                    $funcion = $infoLlamador['class'] . '::' . $funcion;
                }
                return $funcion;
            }
        }
        return '[llamador_desconocido]'; // Retorno por defecto si no se puede determinar el llamador.
    }

    /**
     * Asegura que el método `guardarLogsEnBuffer` se registre en el hook 'shutdown' solo una vez por ciclo de petición.
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

        $bloqueMensajes = "--- GloryLogger Inicio: {$nombreFuncion} ---\n";

        foreach ($datosLog['mensajes'] as $registro) {
            $nivelTexto = 'INFO'; // Por defecto para NIVEL_INFO o niveles desconocidos
            switch ($registro['nivel']) {
                case self::NIVEL_ADVERTENCIA:
                    $nivelTexto = 'ADVERTENCIA';
                    break;
                case self::NIVEL_ERROR:
                    $nivelTexto = 'ERROR';
                    break;
                case self::NIVEL_CRITICO:
                    $nivelTexto = 'CRITICO';
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
                // Se normalizan múltiples espacios/saltos de línea en el contexto para una mejor legibilidad en el log.
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
