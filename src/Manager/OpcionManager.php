<?php

namespace Glory\Manager;

use Glory\Core\GloryFeatures;
use Glory\Core\GloryLogger;
use Glory\Core\OpcionRegistry;
use Glory\Core\OpcionRepository;
use Glory\Manager\AssetManager;

/**
 * Gestiona el ciclo de vida completo de las opciones del tema (registro, sincronización y obtención).
 *
 * Actúa como una Fachada (Facade) para el subsistema de opciones, utilizando OpcionRegistry
 * para las definiciones y OpcionRepository para el acceso a datos.
 * Esta clase consolida las responsabilidades de OpcionManager y OpcionConfigurator.
 * Principio de Responsabilidad Única (SRP) no aplica aqui porque tener register y get en una misma clase es mas facil de entender y usar!
 */
class OpcionManager
{
    private static bool $haSincronizado = false;
    /**
     * Cache en memoria para valores de opciones cuando no estamos en modo desarrollo.
     * Clave: nombre de opción (key) -> valor final resuelto.
     */
    private static array $cacheValores = [];

    private static function isDevMode(): bool
    {
        // Preferimos el flag global si está configurado, con fallback a WP_DEBUG
        $assetDev = method_exists(AssetManager::class, 'isGlobalDevMode') ? AssetManager::isGlobalDevMode() : false;
        $wpDebug = (defined('WP_DEBUG') && WP_DEBUG);
        return (bool) ($assetDev || $wpDebug);
    }

    /**
     * Limpia la cache en memoria (se usa, por ejemplo, tras guardar desde el panel).
     */
    public static function clearCache(): void
    {
        self::$cacheValores = [];
    }

    /**
     * Define y registra una nueva opción para ser gestionada.
     * Este es el método principal para declarar una opción desde el código.
     * (Función movida desde OpcionConfigurator)
     */
    public static function register(string $key, array $configuracion = []): void
    {
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
            'forzarDefaultAlRegistrar' => false,
            // Control de visibilidad/comportamiento en producción
            'hideInProd' => false,   // Ocultar en panel cuando no es modo dev
            'lockInProd' => false,   // Forzar valor seguro en producción (pensado para toggles críticos)
        ];
        $configParseada = wp_parse_args($configuracion, $defaults);

        OpcionRegistry::define($key, $configParseada);
    }

    /**
     * Obtiene el valor de una opción. Este es el getter principal.
     * La opción debe estar previamente definida mediante self::register().
     *
     * @param string $key La clave única de la opción.
     * @param mixed|null $valorPorDefecto Opcional. Valor a devolver si la opción no tiene valor.
     * @return mixed El valor de la opción.
     */
    public static function get(string $key, $valorPorDefecto = null)
    {
        $esDev = self::isDevMode();

        // Cache de lectura cuando NO estamos en modo desarrollo
        if (!$esDev && array_key_exists($key, self::$cacheValores)) {
            return self::$cacheValores[$key];
        }

        $config = OpcionRegistry::getDefinicion($key);

        if (!$config) {
            GloryLogger::warning("OpcionManager: Se intentó obtener la opción no definida '{$key}'. Es necesario definirla con OpcionManager::register().");
            return $valorPorDefecto;
        }

        // En producción, si la opción está marcada como bloqueada, devolver un valor seguro inmediatamente
        if (!$esDev && !empty($config['lockInProd'])) {
            $valorFinal = true; // Forzamos 'activado' para no romper funcionalidades críticas
            self::$cacheValores[$key] = $valorFinal;
            return $valorFinal;
        }

        // Para opciones vinculadas a una feature (featureKey), ajustamos prioridad según modo dev:
        // - Modo DEV: si el código (GloryFeatures::) fija true/false, se devuelve inmediatamente.
        // - Modo PROD: el panel tiene prioridad; el valor del código solo define el DEFAULT.
        $defaultDesdeCodigo = null;
        if (!empty($config['featureKey'])) {
            $estadoDesdeCodigo = GloryFeatures::isEnabled($config['featureKey']);
            if ($esDev) {
                if ($estadoDesdeCodigo !== null) {
                    return (bool) $estadoDesdeCodigo;
                }
            } else {
                if ($estadoDesdeCodigo !== null) {
                    $defaultDesdeCodigo = (bool) $estadoDesdeCodigo;
                }
            }
        }

        // Prioridad 2: Obtener el valor desde la base de datos.
        $valorObtenido = OpcionRepository::get($key);

        // Prioridad 3: Usar el valor por defecto si no hay nada en la BD.
        if ($valorObtenido === OpcionRepository::getCentinela()) {
            // Si hay default derivado del código (solo PROD), úsalo; luego valorPorDefecto; luego valorDefault de la definición
            $valorFinal = ($defaultDesdeCodigo !== null)
                ? $defaultDesdeCodigo
                : ($valorPorDefecto ?? $config['valorDefault']);
        } else {
            $valorFinal = $valorObtenido;
        }

        // Aplicar escape si es necesario.
        $debeEscapar = $config['comportamientoEscape'] ?? false;
        if (is_string($valorFinal) && $debeEscapar) {
            $valorEscapado = esc_html($valorFinal);
            if (!$esDev) {
                self::$cacheValores[$key] = $valorEscapado;
            }
            return $valorEscapado;
        }

        if (!$esDev) {
            self::$cacheValores[$key] = $valorFinal;
        }
        return $valorFinal;
    }

    /**
     * Engancha el proceso de sincronización al hook 'init' de WordPress.
     * (Función movida desde OpcionConfigurator)
     */
    public static function init(): void
    {
        #add_action('init', [self::class, 'sincronizarTodasLasOpciones'], 5);
    }

    /**
     * Sincroniza todas las opciones definidas, comparando su estado en la BD
     * con su definición en el código y aplicando la lógica necesaria.
     * (Función movida desde OpcionConfigurator)
     */
    public static function sincronizarTodasLasOpciones(): void
    {
        if (self::$haSincronizado) {
            return;
        }

        $definiciones = OpcionRegistry::getDefiniciones();
        if (empty($definiciones)) {
            return;
        }

        foreach (array_keys($definiciones) as $key) {
            self::sincronizarOpcionIndividual($key);
        }

        self::$haSincronizado = true;
    }

    private static function sincronizarOpcionIndividual(string $key): void
    {
        $config = OpcionRegistry::getDefinicion($key);
        if (!$config) {
            return;
        }

        $estadoActual = [
            'valor' => OpcionRepository::get($key),
        ] + OpcionRepository::getPanelMeta($key);

        list($accion, $mensajeLog) = self::decidirAccionSincronizacion($config, $estadoActual);

        switch ($accion) {
            case 'SOBREESCRIBIR_CON_DEFAULT':
                OpcionRepository::save($key, $config['valorDefault']);
                OpcionRepository::deletePanelMeta($key);
                if ($mensajeLog) {
                    GloryLogger::info($mensajeLog);
                }
                break;
            case 'LIMPIAR_METADATOS':
                OpcionRepository::deletePanelMeta($key);
                break;
        }
    }

    private static function decidirAccionSincronizacion(array $config, array $estado): array
    {
        $key = $config['etiqueta']; // Se usa la etiqueta para logs más descriptivos
        $centinela = OpcionRepository::getCentinela();
        $hashCodigoActual = $config['hashVersionCodigo'];

        if ($config['forzarDefaultAlRegistrar']) {
            if ($estado['valor'] === $centinela || $estado['valor'] !== $config['valorDefault']) {
                return ['SOBREESCRIBIR_CON_DEFAULT', "OpcionManager: '{$key}' se actualizará a default debido a 'forzarDefaultAlRegistrar'."];
            }
        }

        if ($estado['esPanel']) {
            if ($estado['hashPanel'] === $centinela) {
                GloryLogger::error("OpcionManager: '{$key}' (valor de panel) inconsistente (sin hash). Se revierte a default.");
                return ['SOBREESCRIBIR_CON_DEFAULT', null];
            }
            if ($hashCodigoActual !== $estado['hashPanel']) {
                GloryLogger::warning("OpcionManager: '{$key}' (valor de panel) obsoleto (código cambió). Se revierte a default.");
                return ['SOBREESCRIBIR_CON_DEFAULT', null];
            }
            return ['MANTENER_VALOR_PANEL', null];
        }

        if ($estado['valor'] === $centinela) {
            return ['SOBREESCRIBIR_CON_DEFAULT', "OpcionManager: '{$key}' no existe en BD. Se establece a default."];
        }

        if ($estado['hashPanel'] !== $centinela) {
            return ['LIMPIAR_METADATOS', "OpcionManager: '{$key}' tenía metadatos de panel obsoletos. Limpiando."];
        }

        return ['NO_HACER_NADA', null];
    }


    // --- Métodos de ayuda para tipos específicos ---

    public static function texto(string $key, string $default = ''): string
    {
        return (string) self::get($key, $default);
    }

    public static function richText(string $key, string $default = ''): string
    {
        $valor = self::get($key, $default);
        return wp_kses_post((string)$valor);
    }

    public static function imagen(string $key, string $default = ''): string
    {
        return (string) self::get($key, $default);
    }

    public static function menu(string $key, array $default = []): array
    {
        $valor = self::get($key, $default);
        return is_array($valor) ? $valor : $default;
    }
}