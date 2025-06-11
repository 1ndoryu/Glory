<?

namespace Glory\Manager;

use Glory\Core\GloryLogger;
use Glory\Helper\ScheduleManager;

class OpcionManager
{
    const OPCION_PREFIJO = 'glory_opcion_';
    const META_HASH_CODIGO_SUFIJO = '_code_hash_on_save';
    const META_PANEL_GUARDADO_SUFIJO = '_is_panel_value';

    private static array $opcionesRegistradas = [];
    private static ?\stdClass $centinelaBd = null;

    public static function initEstatico()
    {
        if (self::$centinelaBd === null) {
            self::$centinelaBd = new \stdClass();
        }
    }

    public static function register(string $key, array $configuracion = []): void
    {
        if (self::$centinelaBd === null) {
            self::initEstatico();
        }

        $tipoDefault = $configuracion['tipo'] ?? 'text';
        $defaults = [
            'valorDefault' => '',
            'tipo' => $tipoDefault,
            'etiqueta' => ucfirst(str_replace(['_', '-'], ' ', $key)),
            'seccion' => 'general',
            'subSeccion' => 'general',
            'etiquetaSeccion' => ucfirst(str_replace(['_', '-'], ' ', $configuracion['seccion'] ?? 'general')),
            'descripcion' => '',
            'comportamientoEscape' => ($tipoDefault === 'text'),
            'forzarDefault' => false,
            'forzarDefaultAlRegistrar' => false,
        ];
        $configParseada = wp_parse_args($configuracion, $defaults);
        $defaultCodigoParaHash = $configParseada['valorDefault'];
        $configParseada['hashVersionCodigo'] = md5(is_scalar($defaultCodigoParaHash) ? (string)$defaultCodigoParaHash : serialize($defaultCodigoParaHash));

        self::$opcionesRegistradas[$key] = $configParseada;

        self::sincronizarOpcionRegistrada($key);
    }

    /**
     * Orquesta la sincronización de una opción, delegando la lectura,
     * la decisión y la escritura a métodos especializados.
     */
    private static function sincronizarOpcionRegistrada(string $key): void
    {
        $config = self::$opcionesRegistradas[$key];
        $estadoActual = self::_leerEstadoOpcion($key);

        list($accion, $mensajeLog) = self::_decidirAccionSincronizacion($config, $estadoActual);

        switch ($accion) {
            case 'SOBREESCRIBIR_CON_DEFAULT':
                self::_escribirValorDefault($key, $config['valorDefault']);
                if ($mensajeLog) {
                    GloryLogger::info($mensajeLog);
                }
                break;
            case 'LIMPIAR_METADATOS':
                self::_limpiarMetadatosPanel($key);
                break;
        }
    }

    /**
     * Lee el estado completo de una opción desde la base de datos.
     * Encapsula todas las llamadas a get_option para una clave.
     *
     * @return array Un array con 'valor', 'esPanel', y 'hashPanel'.
     */
    private static function _leerEstadoOpcion(string $key): array
    {
        $nombreOpcion = self::OPCION_PREFIJO . $key;
        return [
            'valor' => get_option($nombreOpcion, self::$centinelaBd),
            'esPanel' => get_option($nombreOpcion . self::META_PANEL_GUARDADO_SUFIJO, false),
            'hashPanel' => get_option($nombreOpcion . self::META_HASH_CODIGO_SUFIJO, self::$centinelaBd),
        ];
    }

    /**
     * Contiene la lógica pura de decisión para la sincronización.
     * No interactúa con la base de datos, solo decide qué hacer.
     *
     * @return array Una tupla con la acción a tomar y un mensaje para el log.
     */
    private static function _decidirAccionSincronizacion(array $config, array $estado): array
    {
        $key = $config['etiqueta']; // Usamos la etiqueta para el log, que es más legible
        $hashCodigoActual = $config['hashVersionCodigo'];

        if ($config['forzarDefaultAlRegistrar'] ?? false) {
            if ($estado['valor'] === self::$centinelaBd || $estado['valor'] !== $config['valorDefault']) {
                $mensaje = "OpcionManager: '{$key}' se actualizará a default de código debido a 'forzarDefaultAlRegistrar'.";
                return ['SOBREESCRIBIR_CON_DEFAULT', $mensaje];
            }
        }

        if ($estado['esPanel']) {
            if ($estado['hashPanel'] === self::$centinelaBd) {
                $mensaje = "OpcionManager: '{$key}' (valor de panel) inconsistente (sin hash guardado). Se revierte a default de código.";
                GloryLogger::error($mensaje);
                return ['SOBREESCRIBIR_CON_DEFAULT', null];
            }
            if ($hashCodigoActual !== $estado['hashPanel']) {
                $mensaje = "OpcionManager: '{$key}' (valor de panel) obsoleto (default de código cambió). Se revierte a default de código.";
                GloryLogger::warning($mensaje);
                return ['SOBREESCRIBIR_CON_DEFAULT', null];
            }
            // Los hashes coinciden, el valor del panel es válido y se mantiene.
            return ['MANTENER_VALOR_PANEL', null];
        }

        // No es valor de panel Y no se fuerza al registrar.
        if ($estado['valor'] === self::$centinelaBd) {
            $mensaje = "OpcionManager: '{$key}' no existe en BD. Se establece a default de código.";
            return ['SOBREESCRIBIR_CON_DEFAULT', $mensaje];
        }

        if (($config['forzarDefault'] ?? false) && $estado['valor'] !== $config['valorDefault']) {
            $mensaje = "OpcionManager: '{$key}' se actualizará a default de código debido a 'forzarDefault' y diferencia con valor en BD.";
            return ['SOBREESCRIBIR_CON_DEFAULT', $mensaje];
        }

        // El valor existe, no se fuerza y no es de panel. Limpiar metadatos por si acaso.
        if ($estado['hashPanel'] !== self::$centinelaBd) {
            return ['LIMPIAR_METADATOS', null];
        }

        return ['NO_HACER_NADA', null];
    }

    /**
     * Escribe el valor por defecto de una opción en la base de datos y limpia sus metadatos de panel.
     */
    private static function _escribirValorDefault(string $key, $valorDefault): void
    {
        $nombreOpcion = self::OPCION_PREFIJO . $key;
        update_option($nombreOpcion, $valorDefault);
        self::_limpiarMetadatosPanel($key);
    }

    /**
     * Limpia los metadatos específicos de panel para una opción.
     */
    private static function _limpiarMetadatosPanel(string $key): void
    {
        $nombreOpcion = self::OPCION_PREFIJO . $key;
        delete_option($nombreOpcion . self::META_PANEL_GUARDADO_SUFIJO);
        delete_option($nombreOpcion . self::META_HASH_CODIGO_SUFIJO);
    }

    public static function getHashDefaultCodigo(string $key): ?string
    {
        if (isset(self::$opcionesRegistradas[$key]['hashVersionCodigo'])) {
            return self::$opcionesRegistradas[$key]['hashVersionCodigo'];
        }
        if (isset(self::$opcionesRegistradas[$key]['valorDefault'])) {
            $valorDefault = self::$opcionesRegistradas[$key]['valorDefault'];
            return md5(is_scalar($valorDefault) ? (string)$valorDefault : serialize($valorDefault));
        }
        GloryLogger::error("Obtener Hash Default Código (getHashDefaultCodigo): CRÍTICO - No se encontró valor por defecto para la clave '{$key}'.");
        return null;
    }

    private static function registrarAlVuelo(string $key, $valorDefault, string $tipo, ?string $etiqueta, ?string $seccion, ?string $subSeccion, ?string $descripcion, bool $comportamientoEscape): void
    {
        if (!isset(self::$opcionesRegistradas[$key])) {
            self::register($key, [
                'valorDefault' => $valorDefault,
                'tipo' => $tipo,
                'etiqueta' => $etiqueta,
                'seccion' => $seccion,
                'subSeccion' => $subSeccion,
                'descripcion' => $descripcion,
                'comportamientoEscape' => $comportamientoEscape,
            ]);
        }
    }

    public static function menu(string $key, array $estructuraDefault = [], ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $subSeccionPanel = null, ?string $descripcionPanel = null): array
    {
        $valor = self::get($key, $estructuraDefault, false, $tituloPanel, $seccionPanel, $subSeccionPanel, $descripcionPanel, 'menu_structure');
        return is_array($valor) ? $valor : $estructuraDefault;
    }

    public static function get(string $key, $defaultParam = '', bool $escaparSalida = true, ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $subSeccionPanel = null, ?string $descripcionPanel = null, string $tipoContenido = 'text')
    {
        if (self::$centinelaBd === null) {
            self::initEstatico();
        }

        self::registrarAlVuelo($key, $defaultParam, $tipoContenido, $tituloPanel, $seccionPanel, $subSeccionPanel, $descripcionPanel, $escaparSalida);

        $nombreOpcion = self::OPCION_PREFIJO . $key;
        $valorFinal = get_option($nombreOpcion, self::$centinelaBd);

        if ($valorFinal === self::$centinelaBd) {
            GloryLogger::error("Error GET para '{$key}': La opción '{$nombreOpcion}' NO SE ENCONTRÓ en la BD. Usando valor por defecto en memoria.");
            $valorFinal = self::$opcionesRegistradas[$key]['valorDefault'] ?? $defaultParam;
        }

        $debeEscapar = self::$opcionesRegistradas[$key]['comportamientoEscape'] ?? $escaparSalida;
        if (is_string($valorFinal) && $debeEscapar) {
            return esc_html($valorFinal);
        }
        return $valorFinal;
    }

    public static function resetSeccionDefaults(string $seccionSlugAResetear): array
    {
        if (self::$centinelaBd === null) {
            self::initEstatico();
        }

        $resultadosReset = ['exito' => [], 'error' => [], 'noEncontradoOVacio' => true, 'camposProcesadosContador' => 0];
        if (empty(self::$opcionesRegistradas)) {
            return $resultadosReset;
        }

        $seccionExisteEnConfig = false;
        foreach (self::$opcionesRegistradas as $key => $configCampo) {
            $seccionCampoSlug = sanitize_title($configCampo['seccion'] ?? 'general');
            if ($seccionCampoSlug !== $seccionSlugAResetear) {
                continue;
            }

            $seccionExisteEnConfig = true;
            if (($configCampo['tipo'] ?? 'text') === 'menu_structure') {
                continue;
            }

            self::_escribirValorDefault($key, $configCampo['valorDefault']);
            $resultadosReset['exito'][] = $key;
            $resultadosReset['camposProcesadosContador']++;
        }

        if ($seccionExisteEnConfig && $resultadosReset['camposProcesadosContador'] > 0) {
            $resultadosReset['noEncontradoOVacio'] = false;
        }

        return $resultadosReset;
    }

    public static function texto(string $key, string $valorDefault = '', ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $descripcionPanel = null): string
    {
        return (string) self::get($key, $valorDefault, true, $tituloPanel, $seccionPanel, null, $descripcionPanel, 'text');
    }

    public static function richText(string $key, string $valorDefault = '', ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $descripcionPanel = null): string
    {
        $valor = self::get($key, $valorDefault, false, $tituloPanel, $seccionPanel, null, $descripcionPanel, 'richText');
        return wp_kses_post((string)$valor);
    }

    public static function imagen(string $key, string $valorDefault = '', ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $descripcionPanel = null): string
    {
        return (string) self::get($key, $valorDefault, false, $tituloPanel, $seccionPanel, null, $descripcionPanel, 'image');
    }

    public static function horario(string $key, array $horarioDefault = [], ?string $tituloPanel = null, ?string $seccionPanel = null, ?string $descripcionPanel = null): array
    {
        self::registrarAlVuelo($key, $horarioDefault, 'schedule', $tituloPanel, $seccionPanel, null, $descripcionPanel, false);
        return ScheduleManager::getScheduleData($key, $horarioDefault, $tituloPanel, $seccionPanel, $descripcionPanel, 'schedule');
    }

    public static function scheduleStatus(string $claveHorario, array $horarioDefault, string $zonaHoraria = 'Europe/Madrid'): array
    {
        return ScheduleManager::getCurrentScheduleStatus($claveHorario, $horarioDefault, $zonaHoraria);
    }

    public static function getDefinicionesRegistradas(): array
    {
        return self::$opcionesRegistradas;
    }
}
