<?php

namespace Glory\Manager;

use Glory\Core\GloryFeatures;
use Glory\Core\GloryLogger;
use Glory\Core\OpcionRegistry;
use Glory\Core\OpcionRepository;

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
        $config = OpcionRegistry::getDefinicion($key);

        if (!$config) {
            GloryLogger::warning("OpcionManager: Se intentó obtener la opción no definida '{$key}'. Es necesario definirla con OpcionManager::register().");
            return $valorPorDefecto;
        }

        // Prioridad 1: Comprobar si hay una anulación por código a través de GloryFeatures.
        if (!empty($config['featureKey'])) {
            $estadoDesdeCodigo = GloryFeatures::isEnabled($config['featureKey']);
            if ($estadoDesdeCodigo !== null) {
                return $estadoDesdeCodigo; // La anulación por código tiene la máxima prioridad.
            }
        }

        // Prioridad 2: Obtener el valor desde la base de datos.
        $valorObtenido = OpcionRepository::get($key);

        // Prioridad 3: Usar el valor por defecto si no hay nada en la BD.
        if ($valorObtenido === OpcionRepository::getCentinela()) {
            $valorFinal = $valorPorDefecto ?? $config['valorDefault'];
        } else {
            $valorFinal = $valorObtenido;
        }

        // Aplicar escape si es necesario.
        $debeEscapar = $config['comportamientoEscape'] ?? false;
        if (is_string($valorFinal) && $debeEscapar) {
            return esc_html($valorFinal);
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