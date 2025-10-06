<?php

namespace Glory\Core;

/**
 * Gestiona el registro de eventos y errores de la aplicación.
 * Utiliza `error_log` y un buffer para optimizar el rendimiento.
 * @author @wandorius
 */
class GloryLogger
{
    // Niveles de Log
    /** @var int Nivel de información general. */
    const NIVEL_INFO        = 10;
    /** @var int Nivel de advertencia para problemas no críticos. */
    const NIVEL_ADVERTENCIA = 20;
    /** @var int Nivel de error que puede afectar la funcionalidad. */
    const NIVEL_ERROR       = 30;
    /** @var int Nivel crítico para errores que requieren atención inmediata. */
    const NIVEL_CRITICO     = 50;

    /** @var array Niveles de log válidos. */
    private static array $nivelesValidos = [
        self::NIVEL_INFO,
        self::NIVEL_ADVERTENCIA,
        self::NIVEL_ERROR,
        self::NIVEL_CRITICO,
    ];

    /** @var int Nivel mínimo para guardar un mensaje. Por defecto: errores y superiores. */
    private static int $nivelMinimoGuardado       = self::NIVEL_INFO;
    /** @var array Buffer para logs acumulados. */
    private static array $bufferLogs                = [];
    /** @var bool Indica si el hook de guardado ya fue registrado. */
    private static bool $hookGuardarLogsRegistrado = false;

    /**
     * Inicializa el logger y opcionalmente establece el nivel mínimo a registrar.
     *
     * @param int|null $nivelMinimoRegistrar Nivel mínimo para guardar logs (ej. self::NIVEL_INFO).
     */
    public static function init(?int $nivelMinimoRegistrar = null): void
    {
        if (!is_null($nivelMinimoRegistrar)) {
            self::setNivelMinimoGuardado($nivelMinimoRegistrar);
        }
    }

    /**
     * Establece el nivel mínimo de log a guardar.
     *
     * @param int $nivelMinimo El nivel de log (ej. self::NIVEL_INFO).
     */
    public static function setNivelMinimoGuardado(int $nivelMinimo): void
    {
        if (in_array($nivelMinimo, self::$nivelesValidos, true)) {
            self::$nivelMinimoGuardado = $nivelMinimo;
        }
    }

    /**
     * Registra un mensaje informativo.
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
     * Guarda los logs acumulados en el buffer en el sistema de logs de PHP (`error_log`).
     * Se llama automáticamente al final de la ejecución del script (hook 'shutdown').
     */
    public static function guardarLogsEnBuffer(): void
    {
        if (empty(self::$bufferLogs)) {
            return; // No hay logs en el buffer para procesar.
        }

        // Itera sobre cada grupo de logs (agrupados por función/método llamador).
        foreach (self::$bufferLogs as $nombreFuncion => $datosLog) {
            // Solo procesa y guarda el grupo si su nivel máximo de severidad
            // es igual o superior al nivel mínimo configurado.
            if ($datosLog['nivelMaximo'] >= self::$nivelMinimoGuardado) {
                self::crearEntradaLog($nombreFuncion, $datosLog);
            }
        }

        // Limpiar el buffer y resetear el estado del hook para la siguiente petición.
        self::$bufferLogs                = [];
        self::$hookGuardarLogsRegistrado = false;
    }

    /**
     * Registra un mensaje de log en el buffer interno, agrupándolo por el nombre del llamador.
     * Asegura que el hook para guardar logs al final de la ejecución esté registrado.
     *
     * @param int    $nivel    Nivel de severidad del log (ej. self::NIVEL_INFO).
     * @param string $mensaje  Mensaje principal del log.
     * @param array  $contexto Datos adicionales relevantes, se serializarán y añadirán al mensaje.
     */
    private static function registrar(int $nivel, string $mensaje, array $contexto = []): void
    {
        // Obtiene el nombre de la función o método que llamó al logger.
        $nombreLlamador = self::getNombreLlamador();

        // Genera una huella única para este mensaje específico (nivel, mensaje, contexto)
        // para prevenir la duplicación exacta de logs dentro del mismo grupo y ejecución.
        $huellaLog      = md5($nivel . '|' . $mensaje . '|' . serialize($contexto));

        // Si es la primera vez que se registra un log desde este llamador, inicializa su entrada en el buffer.
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
     * Analiza la traza de depuración para identificar el llamador original, saltando frames internos.
     *
     * @return string Nombre del llamador en formato 'Clase::metodo' o 'funcion', o '[llamador_desconocido]'.
     */
    private static function getNombreLlamador(): string
    {
        // Obtiene la traza de llamadas. Limita a 5 frames y omite argumentos para eficiencia.
        $traza = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        // Itera sobre la traza.
        // El índice inicial ($i = 2) se elige para omitir:
        // Frame 0: La llamada a debug_backtrace() dentro de getNombreLlamador().
        // Frame 1: La llamada a getNombreLlamador() desde registrar().
        // Frame 2: La llamada a registrar() desde un método público como info(), warning(), error(), critical().
        // Por lo tanto, el frame relevante que contiene el llamador original suele estar en $traza[2] o $traza[3]
        // si la llamada fue info(), o $traza[1] si fue directo a registrar() (lo cual es privado).
        // La lógica del bucle y el continue ajustan esto para encontrar el primer frame *fuera* de GloryLogger.
        for ($i = 2; $i < count($traza); $i++) { // Ajuste de $i según la estructura de llamadas interna.
                                                 // Típicamente, $traza[0] es getNombreLlamador, $traza[1] es registrar, $traza[2] es info/error/etc.
                                                 // El llamador real es $traza[3] en el caso común.
                                                 // Sin embargo, si se llamara a registrar() directamente desde otra clase (no debería pasar),
                                                 // o si la estructura de llamadas cambia, este bucle intenta ser más robusto.

            // Si la entrada actual de la traza corresponde a una llamada *dentro* de esta misma clase (GloryLogger),
            // se ignora y se continúa con el siguiente frame. Esto es para saltar los métodos internos del logger.
            if (isset($traza[$i]['class']) && $traza[$i]['class'] === self::class) {
                continue; // Saltar frames internos de GloryLogger (ej. info, warning, error, critical, registrar).
            }

            // Una vez que se encuentra un frame que no es de GloryLogger, se asume que es el llamador.
            // Se construye el nombre en formato 'Clase::metodo' o 'funcion'.
            if (isset($traza[$i])) { // Debería estar seteado si $i < count($traza)
                $infoLlamador = $traza[$i];
                $funcion      = $infoLlamador['function'] ?? '[funcion_desconocida]'; // Nombre de la función o método.
                if (isset($infoLlamador['class'])) {
                    // Si existe 'class', es un método de una clase.
                    $funcion = $infoLlamador['class'] . '::' . $funcion;
                }
                return $funcion; // Retorna el nombre del llamador encontrado.
            }
        }
        // Si el bucle termina sin encontrar un llamador adecuado (poco probable en uso normal),
        // se retorna un valor por defecto.
        return '[llamador_desconocido]';
    }

    /**
     * Asegura que `guardarLogsEnBuffer` se registre en el hook 'shutdown' de WordPress.
     * Previene registros múltiples por petición.
     */
    private static function registrarHookGuardarLogs(): void
    {
        if (!self::$hookGuardarLogsRegistrado) {
            // Registra la función guardarLogsEnBuffer para que se ejecute durante el hook 'shutdown'.
            add_action('shutdown', [self::class, 'guardarLogsEnBuffer'], 100);
            // Marca el hook como registrado para esta petición.
            self::$hookGuardarLogsRegistrado = true;
        }
    }

    /**
     * Formatea y escribe un grupo de logs en el sistema de logs de PHP (`error_log`).
     * Los mensajes se agrupan por la función/método que los originó y se formatean para legibilidad.
     *
     * @param string $nombreFuncion Nombre de la función/método que originó los logs.
     * @param array  $datosLog      Array con los mensajes a registrar.
     */
    private static function crearEntradaLog(string $nombreFuncion, array $datosLog): void
    {
        // Si no hay mensajes para esta función/método, no hace nada.
        if (empty($datosLog['mensajes'])) {
            return;
        }

        // Encabezado para el bloque de logs de esta función/método.
        $bloqueMensajes = "--- GloryLogger Inicio: {$nombreFuncion} ---\n";

        // Itera sobre cada mensaje individual registrado para esta función/método.
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
