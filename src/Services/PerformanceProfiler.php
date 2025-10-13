<?php

namespace Glory\Services;

use Glory\Core\GloryFeatures;

/**
 * Servicio de profiling de rendimiento para Glory Framework.
 *
 * Mide el tiempo de ejecución de funciones y componentes para identificar cuellos de botella.
 * Se activa solo en modo desarrollo o cuando está explícitamente habilitado.
 */
class PerformanceProfiler
{
    /**
     * Almacena los tiempos de inicio de cada medición.
     * @var array<string, float>
     */
    private static array $startTimes = [];

    /**
     * Almacena los resultados de las mediciones.
     * @var array<string, array{tipo: string, tiempo: float, memoria: int, llamadas: int}>
     */
    private static array $measurements = [];

    /**
     * Contador de llamadas por etiqueta para detectar repeticiones.
     * @var array<string, int>
     */
    private static array $callCounts = [];

    /**
     * Indica si el profiler está activo.
     */
    private static bool $isActive = false;

    /**
     * Indica si ya se inicializó.
     */
    private static bool $initialized = false;

    /**
     * Inicializa el profiler si está activo.
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        // Solo activo en desarrollo o si está explícitamente habilitado
        self::$isActive = GloryFeatures::isActive('performanceProfiler', 'glory_performance_profiler_activo', false);

        if (!self::$isActive) {
            return;
        }

        // Registrar hook para mostrar resumen final
        add_action('shutdown', [self::class, 'mostrarResumenFinal'], 999);
    }

    /**
     * Comienza la medición de tiempo para una función/componente.
     *
     * @param string $etiqueta Identificador único para la medición
     * @param string $tipo Tipo de componente (ej: 'service', 'manager', 'handler')
     */
    public static function start(string $etiqueta, string $tipo = 'general'): void
    {
        if (!self::$isActive) {
            return;
        }

        self::$startTimes[$etiqueta] = microtime(true);
        self::$callCounts[$etiqueta] = (self::$callCounts[$etiqueta] ?? 0) + 1;
    }

    /**
     * Termina la medición y registra el resultado.
     *
     * @param string $etiqueta Identificador de la medición (debe coincidir con start)
     */
    public static function end(string $etiqueta): void
    {
        if (!self::$isActive || !isset(self::$startTimes[$etiqueta])) {
            return;
        }

        $tiempoInicio = self::$startTimes[$etiqueta];
        $tiempoFin = microtime(true);
        $duracion = $tiempoFin - $tiempoInicio;
        $memoria = memory_get_peak_usage(true);
        $tipo = self::determinarTipo($etiqueta);

        // Si ya existe una medición con esta etiqueta, acumulamos
        if (isset(self::$measurements[$etiqueta])) {
            self::$measurements[$etiqueta]['tiempo'] += $duracion;
            self::$measurements[$etiqueta]['llamadas']++;
            self::$measurements[$etiqueta]['memoria'] = max(self::$measurements[$etiqueta]['memoria'], $memoria);
            $duracion = self::$measurements[$etiqueta]['tiempo'];
            $llamadas = self::$measurements[$etiqueta]['llamadas'];
        } else {
            self::$measurements[$etiqueta] = [
                'tipo' => $tipo,
                'tiempo' => $duracion,
                'memoria' => $memoria,
                'llamadas' => 1,
                'timestamp' => time()
            ];
            $llamadas = 1;
        }

        // Log inmediato en debug.log
        self::logMedicionInmediata($etiqueta, $duracion, $memoria, $llamadas, $tipo);

        unset(self::$startTimes[$etiqueta]);
    }

    /**
     * Método conveniente para medir una función completa.
     *
     * @param callable $funcion Función a ejecutar y medir
     * @param string $etiqueta Identificador para la medición
     * @param string $tipo Tipo de componente
     * @return mixed Resultado de la función ejecutada
     */
    public static function medirFuncion(callable $funcion, string $etiqueta, string $tipo = 'general')
    {
        self::start($etiqueta, $tipo);
        try {
            $resultado = $funcion();
            self::end($etiqueta);
            return $resultado;
        } catch (\Throwable $e) {
            self::end($etiqueta);
            throw $e;
        }
    }

    /**
     * Registra una medición inmediata en el log.
     */
    private static function logMedicionInmediata(string $etiqueta, float $duracion, int $memoria, int $llamadas, string $tipo): void
    {
        $memoriaMB = number_format($memoria / 1024 / 1024, 2);
        $mensaje = sprintf(
            '[Glory Profiler] %s | %0.6fs | %s MB | %d llamadas | %s',
            str_pad($etiqueta, 35),
            $duracion,
            $memoriaMB,
            $llamadas,
            $tipo
        );

        error_log($mensaje);
    }

    /**
     * Determina el tipo basado en la etiqueta.
     */
    private static function determinarTipo(string $etiqueta): string
    {
        if (str_contains($etiqueta, 'Manager')) {
            return 'manager';
        }
        if (str_contains($etiqueta, 'Handler')) {
            return 'handler';
        }
        if (str_contains($etiqueta, 'Service')) {
            return 'service';
        }
        if (str_contains($etiqueta, 'Controller')) {
            return 'controller';
        }
        if (str_contains($etiqueta, 'Renderer')) {
            return 'renderer';
        }
        return 'general';
    }

    /**
     * Obtiene todas las mediciones realizadas.
     *
     * @return array
     */
    public static function obtenerMediciones(): array
    {
        return self::$measurements;
    }

    /**
     * Obtiene un resumen ordenado por tiempo de ejecución.
     *
     * @return array Mediciones ordenadas por tiempo descendente
     */
    public static function obtenerResumen(): array
    {
        $mediciones = self::$measurements;

        // Ordenar por tiempo descendente
        uasort($mediciones, static function ($a, $b) {
            return $b['tiempo'] <=> $a['tiempo'];
        });

        return $mediciones;
    }

    /**
     * Muestra el resumen final con estadísticas globales.
     */
    public static function mostrarResumenFinal(): void
    {
        if (!self::$isActive || empty(self::$measurements)) {
            return;
        }

        $resumen = self::obtenerResumen();
        $totalTiempo = array_sum(array_column($resumen, 'tiempo'));
        $totalMemoria = max(array_column($resumen, 'memoria'));
        $top3 = array_slice($resumen, 0, 3);

        $lineas = [];
        $lineas[] = '=== Glory Performance Profiler - RESUMEN FINAL ===';
        $lineas[] = sprintf('Total componentes medidos: %d', count($resumen));
        $lineas[] = sprintf('Tiempo total Glory: %.6f segundos', $totalTiempo);
        $lineas[] = sprintf('Memoria máxima: %s MB', number_format($totalMemoria / 1024 / 1024, 2));
        $lineas[] = '';
        $lineas[] = 'TOP 3 componentes más lentos:';

        foreach ($top3 as $etiqueta => $datos) {
            $porcentaje = $totalTiempo > 0 ? ($datos['tiempo'] / $totalTiempo) * 100 : 0;
            $lineas[] = sprintf(
                '  %-32s | %0.6fs (%5.1f%%)',
                $etiqueta,
                $datos['tiempo'],
                $porcentaje
            );
        }

        $lineas[] = '===================================================';

        error_log(implode("\n", $lineas));
    }

    /**
     * Método obsoleto - ahora solo usamos logs en debug.log
     * Mantenido por compatibilidad.
     */
    public static function injectarDatosDebug(): void
    {
        // No hacer nada - ahora solo logs en debug.log
    }

    /**
     * Reinicia todas las mediciones (útil para testing).
     */
    public static function reiniciar(): void
    {
        self::$startTimes = [];
        self::$measurements = [];
        self::$callCounts = [];
    }

    /**
     * Verifica si el profiler está activo.
     */
    public static function estaActivo(): bool
    {
        return self::$isActive;
    }
}
