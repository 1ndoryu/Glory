<?php

namespace Glory\Plugins\AmazonProduct\Service;

/**
 * API Usage Tracker - Controla el uso de llamadas a la API de Amazon.
 * 
 * Funcionalidades:
 * - Contador persistente de llamadas por ciclo mensual
 * - Configuracion del dia de inicio del ciclo (1-28)
 * - Limite mensual configurable
 * - Historial de llamadas recientes
 * - Advertencia al acercarse al limite
 * 
 * API-01: Sistema de contador de llamadas API mensuales
 */
class ApiUsageTracker
{
    // Opciones de WordPress para guardar configuracion
    private const OPTION_CYCLE_START_DAY = 'amazon_api_cycle_start_day';
    private const OPTION_MONTHLY_LIMIT = 'amazon_api_monthly_limit';
    private const OPTION_CALL_LOG = 'amazon_api_call_log';
    private const OPTION_CURRENT_CYCLE_COUNT = 'amazon_api_cycle_count';
    private const OPTION_LAST_CYCLE_DATE = 'amazon_api_last_cycle_date';

    // Valores por defecto
    private const DEFAULT_CYCLE_START_DAY = 1;
    private const DEFAULT_MONTHLY_LIMIT = 10000;
    private const MAX_LOG_ENTRIES = 100;

    /**
     * Registra una llamada a la API.
     * 
     * @param string $endpoint Endpoint llamado (search.php, deal.php, asin.php)
     * @param array $params Parametros de la llamada
     * @param bool $success Si la llamada fue exitosa
     * @param bool $fromCache Si se sirvio desde cache (no cuenta como llamada real)
     */
    public static function recordCall(
        string $endpoint,
        array $params,
        bool $success,
        bool $fromCache = false
    ): void {
        // Las llamadas desde cache no cuentan
        if ($fromCache) {
            return;
        }

        // Verificar si hay que reiniciar el ciclo
        self::checkCycleReset();

        // Incrementar contador del ciclo actual
        $currentCount = (int) get_option(self::OPTION_CURRENT_CYCLE_COUNT, 0);
        update_option(self::OPTION_CURRENT_CYCLE_COUNT, $currentCount + 1);

        // Agregar al historial
        self::addToLog($endpoint, $params, $success);
    }

    /**
     * Agrega una entrada al historial de llamadas.
     */
    private static function addToLog(string $endpoint, array $params, bool $success): void
    {
        $log = get_option(self::OPTION_CALL_LOG, []);

        // Agregar nueva entrada al inicio
        array_unshift($log, [
            'timestamp' => current_time('timestamp'),
            'date' => current_time('Y-m-d H:i:s'),
            'endpoint' => $endpoint,
            'params' => $params,
            'success' => $success
        ]);

        // Limitar el tamanho del log
        if (count($log) > self::MAX_LOG_ENTRIES) {
            $log = array_slice($log, 0, self::MAX_LOG_ENTRIES);
        }

        update_option(self::OPTION_CALL_LOG, $log);
    }

    /**
     * Verifica si hay que reiniciar el ciclo mensual.
     */
    private static function checkCycleReset(): void
    {
        $lastCycleDate = get_option(self::OPTION_LAST_CYCLE_DATE, '');
        $currentCycleStart = self::getCurrentCycleStartDate();

        // Si no hay fecha guardada o la fecha almacenada es anterior al inicio del ciclo actual
        if (empty($lastCycleDate) || strtotime($lastCycleDate) < strtotime($currentCycleStart)) {
            update_option(self::OPTION_CURRENT_CYCLE_COUNT, 0);
            update_option(self::OPTION_LAST_CYCLE_DATE, $currentCycleStart);
        }
    }

    /**
     * Calcula la fecha de inicio del ciclo actual.
     * 
     * @return string Fecha en formato Y-m-d
     */
    public static function getCurrentCycleStartDate(): string
    {
        $cycleDay = (int) get_option(self::OPTION_CYCLE_START_DAY, self::DEFAULT_CYCLE_START_DAY);
        $cycleDay = max(1, min(28, $cycleDay)); // Limitar entre 1 y 28

        $today = current_time('j'); // Dia del mes actual
        $currentMonth = current_time('n');
        $currentYear = current_time('Y');

        // Si el dia actual es menor al dia de inicio, el ciclo empezo el mes pasado
        if ($today < $cycleDay) {
            $currentMonth--;
            if ($currentMonth < 1) {
                $currentMonth = 12;
                $currentYear--;
            }
        }

        return sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $cycleDay);
    }

    /**
     * Obtiene la fecha de fin del ciclo actual.
     * 
     * @return string Fecha en formato Y-m-d
     */
    public static function getCurrentCycleEndDate(): string
    {
        $cycleStart = self::getCurrentCycleStartDate();
        $startTimestamp = strtotime($cycleStart);

        // Agregar un mes y restar un dia
        $endTimestamp = strtotime('+1 month -1 day', $startTimestamp);

        return date('Y-m-d', $endTimestamp);
    }

    /**
     * Obtiene las estadisticas del ciclo actual.
     * 
     * @return array [count, limit, percentage, remaining, cycleStart, cycleEnd, isWarning, isCritical]
     */
    public static function getStats(): array
    {
        self::checkCycleReset();

        $count = (int) get_option(self::OPTION_CURRENT_CYCLE_COUNT, 0);
        $limit = (int) get_option(self::OPTION_MONTHLY_LIMIT, self::DEFAULT_MONTHLY_LIMIT);
        $percentage = $limit > 0 ? round(($count / $limit) * 100, 1) : 0;
        $remaining = max(0, $limit - $count);

        return [
            'count' => $count,
            'limit' => $limit,
            'percentage' => min(100, $percentage),
            'remaining' => $remaining,
            'cycleStart' => self::getCurrentCycleStartDate(),
            'cycleEnd' => self::getCurrentCycleEndDate(),
            'isWarning' => $percentage >= 80 && $percentage < 100,
            'isCritical' => $percentage >= 100
        ];
    }

    /**
     * Obtiene el historial de llamadas recientes.
     * 
     * @param int $limit Numero maximo de entradas a devolver
     * @return array Lista de llamadas
     */
    public static function getRecentCalls(int $limit = 20): array
    {
        $log = get_option(self::OPTION_CALL_LOG, []);
        return array_slice($log, 0, $limit);
    }

    /**
     * Obtiene la configuracion actual.
     * 
     * @return array [cycleStartDay, monthlyLimit]
     */
    public static function getConfig(): array
    {
        return [
            'cycleStartDay' => (int) get_option(self::OPTION_CYCLE_START_DAY, self::DEFAULT_CYCLE_START_DAY),
            'monthlyLimit' => (int) get_option(self::OPTION_MONTHLY_LIMIT, self::DEFAULT_MONTHLY_LIMIT)
        ];
    }

    /**
     * Guarda la configuracion.
     * 
     * @param int $cycleStartDay Dia del mes para iniciar el ciclo (1-28)
     * @param int $monthlyLimit Limite mensual de llamadas
     */
    public static function saveConfig(int $cycleStartDay, int $monthlyLimit): void
    {
        $cycleStartDay = max(1, min(28, $cycleStartDay));
        $monthlyLimit = max(0, $monthlyLimit);

        update_option(self::OPTION_CYCLE_START_DAY, $cycleStartDay);
        update_option(self::OPTION_MONTHLY_LIMIT, $monthlyLimit);
    }

    /**
     * Verifica si se puede hacer una llamada a la API.
     * Retorna false si se ha alcanzado el limite.
     * 
     * @return bool True si se puede hacer la llamada
     */
    public static function canMakeCall(): bool
    {
        $stats = self::getStats();
        return !$stats['isCritical'];
    }

    /**
     * Limpia el historial de llamadas.
     */
    public static function clearLog(): void
    {
        update_option(self::OPTION_CALL_LOG, []);
    }

    /**
     * Reinicia el contador del ciclo actual (uso administrativo).
     */
    public static function resetCycleCount(): void
    {
        update_option(self::OPTION_CURRENT_CYCLE_COUNT, 0);
        update_option(self::OPTION_LAST_CYCLE_DATE, self::getCurrentCycleStartDate());
    }
}
