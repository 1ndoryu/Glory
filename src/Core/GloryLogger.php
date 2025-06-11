<?php

namespace Glory\Core;

/**
 * GloryLogger gestiona el registro de eventos y errores de la aplicación.
 *
 * Utiliza el sistema de logs nativo de PHP (`error_log`) para el registro eficiente de eventos.
 * Los logs se acumulan en un búfer durante la ejecución del script y se escriben conjuntamente
 * al final del script (mediante el hook 'shutdown'), optimizando así el rendimiento al minimizar
 * las operaciones de escritura de archivos.
 * Permite configurar un nivel mínimo de log para controlar la verbosidad de los registros guardados.
 *
 * @author @wandorius
 */
class GloryLogger
{
    // Niveles de Log
    /** @var int Nivel de log para información general. */
    const NIVEL_INFO        = 10;
    /** @var int Nivel de log para advertencias o problemas no críticos. */
    const NIVEL_ADVERTENCIA = 20;
    /** @var int Nivel de log para errores que pueden afectar la funcionalidad. */
    const NIVEL_ERROR       = 30;
    /** @var int Nivel de log para errores críticos que requieren atención inmediata. */
    const NIVEL_CRITICO     = 50;

    /** @var array Lista de niveles de log válidos. */
    private static array $nivelesValidos = [
        self::NIVEL_INFO,
        self::NIVEL_ADVERTENCIA,
        self::NIVEL_ERROR,
        self::NIVEL_CRITICO,
    ];

    /** @var int Nivel mínimo de log para que un mensaje sea guardado. Por defecto, NIVEL_ERROR y superiores. */
    private static int $nivelMinimoGuardado       = self::NIVEL_ERROR;
    /** @var array Buffer para acumular los logs durante la ejecución de un script. */
    private static array $bufferLogs                = [];
    /** @var bool Indica si el hook 'shutdown' para guardar los logs ya ha sido registrado. */
    private static bool $hookGuardarLogsRegistrado = false;

    /**
     * Inicializa el logger y, opcionalmente, establece el nivel mínimo de log a registrar.
     * Si no se especifica un nivel, se mantiene el valor por defecto (NIVEL_ERROR).
     *
     * @param int|null $nivelMinimoRegistrar El nivel mínimo para que los logs se guarden (ej. self::NIVEL_INFO).
     *                                       Debe ser una de las constantes de nivel de log (NIVEL_*).
     */
    public static function init(?int $nivelMinimoRegistrar = null): void
    {
        if (!is_null($nivelMinimoRegistrar)) {
            self::setNivelMinimoGuardado($nivelMinimoRegistrar);
        }
    }

    /**
     * Establece el nivel mínimo de log para que los mensajes sean guardados.
     *
     * Solo los grupos de logs cuya criticidad máxima (el nivel del mensaje más severo del grupo)
     * sea igual o superior a este nivel establecido serán efectivamente escritos en el log.
     *
     * @param int $nivelMinimo El nivel de log deseado (ej. self::NIVEL_INFO, self::NIVEL_ERROR).
     *                         Debe ser una de las constantes NIVEL_* de esta clase.
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
     * Guarda los logs acumulados en el buffer en el sistema de logs de PHP (error_log).
     *
     * Este método está diseñado para ser llamado automáticamente al final de la ejecución del script,
     * típicamente a través del hook 'shutdown' de WordPress.
     * Procesa todos los logs agrupados en el buffer, filtrándolos por el `$nivelMinimoGuardado`
     * antes de escribirlos. Después de escribir, limpia el buffer y resetea el flag del hook.
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
     * Registra un mensaje de log en el búfer interno, agrupándolo por el nombre del llamador.
     *
     * Determina el origen de la llamada (clase y método, o función) y agrupa los mensajes
     * provenientes del mismo origen. Para cada grupo, mantiene un nivel máximo de severidad
     * y una lista de mensajes únicos (basados en una huella MD5 del contenido del log para evitar duplicados exactos).
     * También se asegura de que el hook para guardar los logs al final de la ejecución esté registrado.
     *
     * @param int    $nivel    El nivel de severidad del log (ej. self::NIVEL_INFO, self::NIVEL_ERROR).
     *                         Debe ser una de las constantes NIVEL_*.
     * @param string $mensaje  El mensaje principal del log.
     * @param array  $contexto Un array asociativo con datos adicionales relevantes para el log.
     *                         Estos datos se serializarán como parte del mensaje de log.
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
     * Obtiene el nombre de la función o método que invocó una de las funciones públicas del logger (info, error, etc.).
     *
     * Esta función analiza la traza de depuración (`debug_backtrace`) para identificar
     * el contexto de la llamada original al logger (es decir, la función o método que llamó a `info()`, `error()`, etc.).
     * Se omiten los frames internos de `GloryLogger` para encontrar la primera llamada externa.
     *
     * `DEBUG_BACKTRACE_IGNORE_ARGS` se utiliza para optimizar la llamada a `debug_backtrace`
     * al no incluir los argumentos de las funciones en la traza.
     * El límite de `5` frames es una heurística conservadora; la estructura de llamadas típica es:
     * - Frame 0: `getNombreLlamador()` (este método, donde `debug_backtrace()` es llamado)
     * - Frame 1: `registrar()` (método interno que llama a `getNombreLlamador()`)
     * - Frame 2: `info()`, `error()`, etc. (método público del logger que llama a `registrar()`)
     * - Frame 3: El código de la aplicación que llama a `info()`, `error()`, etc. (¡Este es el frame que buscamos!)
     *
     * @return string Nombre del llamador en formato 'Clase::metodo' o 'funcion',
     *                o '[llamador_desconocido]' si no se puede determinar.
     */
    private static function getNombreLlamador(): string
    {
        // Obtiene la traza de llamadas. DEBUG_BACKTRACE_IGNORE_ARGS para eficiencia, límite de 5 frames.
        $traza = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        // El índice esperado del frame del llamador externo es 3, según la estructura descrita en el Javadoc.
        $indiceLlamador = 3;

        if (isset($traza[$indiceLlamador])) {
            $infoFrame = $traza[$indiceLlamador];
            $funcion   = $infoFrame['function'] ?? '[funcion_desconocida]';

            if (isset($infoFrame['class'])) {
                // Si la clase del frame es esta misma clase, algo no está bien o hay más anidamiento.
                // Intentamos el siguiente frame por si acaso, aunque no debería ser necesario.
                if ($infoFrame['class'] === self::class && isset($traza[$indiceLlamador + 1])) {
                    $infoFrameSiguiente = $traza[$indiceLlamador + 1];
                    // Solo usar el siguiente frame si NO pertenece a GloryLogger.
                    if (!isset($infoFrameSiguiente['class']) || $infoFrameSiguiente['class'] !== self::class) {
                        $funcion = $infoFrameSiguiente['function'] ?? '[funcion_desconocida]';
                        if (isset($infoFrameSiguiente['class'])) {
                            return $infoFrameSiguiente['class'] . '::' . $funcion;
                        }
                        return $funcion;
                    }
                } elseif ($infoFrame['class'] !== self::class) {
                    // El frame actual no es de GloryLogger, es el llamador correcto.
                    return $infoFrame['class'] . '::' . $funcion;
                }
                // Si $infoFrame['class'] es self::class y no hay un siguiente frame útil,
                // o si el siguiente frame también es self::class, se procederá al fallback.
            } else {
                // Es una función global (no un método de clase), por lo que es el llamador.
                return $funcion;
            }
        }

        // Fallback: si $traza[3] no da el resultado esperado, iterar desde un punto seguro.
        // Fallback: si $traza[$indiceLlamador] no proporcionó un llamador válido (ej. por anidamiento inesperado),
        // iterar comenzando desde el frame 1 para encontrar el primer llamador externo a GloryLogger.
        // Frame 0 es siempre getNombreLlamador.
        for ($i = 1; $i < count($traza); $i++) {
            // Si el frame actual pertenece a GloryLogger, continuar al siguiente.
            if (isset($traza[$i]['class']) && $traza[$i]['class'] === self::class) {
                continue;
            }
            // Se encontró un frame que no es de GloryLogger, o es una función global.
            if (isset($traza[$i])) {
                $infoLlamador = $traza[$i];
                $funcion      = $infoLlamador['function'] ?? '[funcion_desconocida]';
                if (isset($infoLlamador['class'])) {
                    return $infoLlamador['class'] . '::' . $funcion;
                }
                return $funcion; // Es una función global.
            }
        }

        return '[llamador_desconocido]'; // Si no se encuentra un llamador adecuado.
    }

    /**
     * Asegura que el método `guardarLogsEnBuffer` se registre para ejecutarse al final del script.
     *
     * En un entorno WordPress, esto se hace mediante el hook `shutdown`.
     * Utiliza una bandera estática (`$hookGuardarLogsRegistrado`) para garantizar que la acción
     * se registre solo una vez por ciclo de petición, previniendo ejecuciones múltiples.
     * La prioridad `100` es relativamente tardía, buscando asegurar que la mayoría de las
     * tareas de 'shutdown' ya se hayan ejecutado.
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
     * Formatea y escribe un grupo de logs (asociados a una función/método específico) en el sistema de logs de PHP.
     *
     * Esta función toma los mensajes acumulados para un llamador particular y los formatea en un bloque de texto
     * que luego se envía a `error_log()`. Cada mensaje individual dentro del bloque incluye:
     * - Marca de tiempo precisa (con microsegundos).
     * - Nivel de log (INFO, ADVERTENCIA, ERROR, CRITICO).
     * - El mensaje del log.
     * - El contexto (si existe), serializado con `print_r` para mayor legibilidad y con espacios normalizados.
     *   Para contextos grandes o si la legibilidad directa en el log no es prioritaria, serializar con `json_encode`
     *   podría ser más performante, aunque resultaría en un formato menos legible para objetos complejos.
     *
     * El bloque completo está delimitado por líneas "--- GloryLogger Inicio: [NombreFuncion] ---" y
     * "--- GloryLogger Fin: [NombreFuncion] ---" para facilitar la lectura y el análisis de los logs.
     *
     * @param string $nombreFuncion El nombre de la función o método que originó los logs (determinado por `getNombreLlamador`).
     *                              Se utiliza en las cabeceras del bloque de log.
     * @param array  $datosLog      Un array que contiene los mensajes acumulados para esta función/método.
     *                              Debe tener una clave 'mensajes', que es un array de registros de log individuales.
     *                              Cada registro debe tener 'marcaTiempo', 'nivel', 'mensaje', y opcionalmente 'contexto'.
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

            // Formatear la marca de tiempo para incluir microsegundos.
            $microsegundos = sprintf("%06d", ($registro['marcaTiempo'] - floor($registro['marcaTiempo'])) * 1000000);
            $fechaConMicro = \DateTime::createFromFormat('U.u', sprintf('%.6F', $registro['marcaTiempo']));
            // Si createFromFormat falla (PHP < 7.0.0 para 'u'), usar una aproximación.
            if ($fechaConMicro === false) {
                 $fechaConMicro = new \DateTime(date('Y-m-d H:i:s.' . $microsegundos, (int)$registro['marcaTiempo']));
            }


            $lineaLog = sprintf(
                "[%s] [%s] %s",
                $fechaConMicro->format("Y-m-d H:i:s.u"),
                $nivelTexto,
                $registro['mensaje']
            );

            if (!empty($registro['contexto'])) {
                // Serializar contexto. Normalizar espacios múltiples/saltos de línea para mejorar legibilidad.
                $contextoSerializado = print_r($registro['contexto'], true);
                $contextoNormalizado = preg_replace('/\s+/', ' ', trim($contextoSerializado));
                $lineaLog .= " | Contexto: " . $contextoNormalizado;
            }

            $bloqueMensajes .= $lineaLog . "\n";
        }

        $bloqueMensajes .= "--- GloryLogger Fin: {$nombreFuncion} ---\n";

        error_log($bloqueMensajes);
    }
}
