<?

namespace Glory\Manager;

use Glory\Core\GloryLogger;
use Glory\Core\OpcionRegistry;
use Glory\Core\OpcionRepository;

/**
 * Orquesta la lógica de negocio para las opciones del tema.
 *
 * Actúa como un Service/Facade que utiliza OpcionRegistry para las definiciones
 * y OpcionRepository para el acceso a datos. Contiene la lógica de
 * sincronización y es el punto de entrada principal para acceder a los valores
 * de las opciones desde el resto de la aplicación.
 */
class OpcionManager
{
    /**
     * Define una opción, la registra y la sincroniza con la base de datos.
     * Este es el método principal para declarar una opción desde el código.
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

        self::sincronizarOpcion($key);
    }

    /**
     * Sincroniza una opción individual, comparando su estado en la base de datos
     * con su definición en el código y aplicando la lógica necesaria.
     */
    private static function sincronizarOpcion(string $key): void
    {
        $config = OpcionRegistry::getDefinicion($key);
        if (!$config) {
            // Esto no debería ocurrir si register() se llama justo antes.
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

    /**
     * Contiene la lógica pura de decisión para la sincronización.
     * No interactúa con la base de datos, solo decide qué hacer.
     *
     * @return array Una tupla con la acción a tomar y un mensaje para el log.
     */
    private static function decidirAccionSincronizacion(array $config, array $estado): array
    {
        $key = $config['etiqueta'];
        $centinela = OpcionRepository::getCentinela();
        $hashCodigoActual = $config['hashVersionCodigo'];

        if ($config['forzarDefaultAlRegistrar']) {
            if ($estado['valor'] === $centinela || $estado['valor'] !== $config['valorDefault']) {
                $mensaje = "OpcionManager: '{$key}' se actualizará a default de código debido a 'forzarDefaultAlRegistrar'.";
                return ['SOBREESCRIBIR_CON_DEFAULT', $mensaje];
            }
        }

        if ($estado['esPanel']) {
            if ($estado['hashPanel'] === $centinela) {
                $mensaje = "OpcionManager: '{$key}' (valor de panel) inconsistente (sin hash guardado). Se revierte a default.";
                GloryLogger::error($mensaje);
                return ['SOBREESCRIBIR_CON_DEFAULT', null];
            }
            if ($hashCodigoActual !== $estado['hashPanel']) {
                $mensaje = "OpcionManager: '{$key}' (valor de panel) obsoleto (default de código cambió). Se revierte a default.";
                GloryLogger::warning($mensaje);
                return ['SOBREESCRIBIR_CON_DEFAULT', null];
            }
            return ['MANTENER_VALOR_PANEL', null];
        }

        if ($estado['valor'] === $centinela) {
            $mensaje = "OpcionManager: '{$key}' no existe en BD. Se establece a default de código.";
            return ['SOBREESCRIBIR_CON_DEFAULT', $mensaje];
        }

        if ($estado['hashPanel'] !== $centinela) {
            return ['LIMPIAR_METADATOS', null];
        }

        return ['NO_HACER_NADA', null];
    }
    
    /**
     * Define una opción si aún no existe en el registro. Usado para llamadas "al vuelo".
     */
    private static function definirSiNoExiste(string $key, $defaultParam, bool $escapar, ?string $titulo, ?string $seccion, ?string $subSeccion, ?string $desc, string $tipo): void
    {
        if (OpcionRegistry::getDefinicion($key) === null) {
            self::register($key, [
                'valorDefault' => $defaultParam,
                'tipo' => $tipo,
                'etiqueta' => $titulo,
                'seccion' => $seccion,
                'subSeccion' => $subSeccion,
                'descripcion' => $desc,
                'comportamientoEscape' => $escapar,
            ]);
        }
    }

    /**
     * Obtiene el valor de una opción. Este es el getter principal.
     */
    public static function get(string $key, $defaultParam = '', bool $escapar = true, ?string $titulo = null, ?string $seccion = null, ?string $sub = null, ?string $desc = null, string $tipo = 'text')
    {
        self::definirSiNoExiste($key, $defaultParam, $escapar, $titulo, $seccion, $sub, $desc, $tipo);
        
        $valorObtenido = OpcionRepository::get($key);

        if ($valorObtenido === OpcionRepository::getCentinela()) {
            $config = OpcionRegistry::getDefinicion($key);
            $valorFinal = $config['valorDefault'] ?? $defaultParam;
        } else {
            $valorFinal = $valorObtenido;
        }

        $config = OpcionRegistry::getDefinicion($key);
        $debeEscapar = $config['comportamientoEscape'] ?? $escapar;

        if (is_string($valorFinal) && $debeEscapar) {
            return esc_html($valorFinal);
        }
        return $valorFinal;
    }

    public static function texto(string $key, string $default = '', ?string $titulo = null, ?string $seccion = null, ?string $desc = null): string
    {
        return (string) self::get($key, $default, true, $titulo, $seccion, null, $desc, 'text');
    }

    public static function richText(string $key, string $default = '', ?string $titulo = null, ?string $seccion = null, ?string $desc = null): string
    {
        $valor = self::get($key, $default, false, $titulo, $seccion, null, $desc, 'richText');
        return wp_kses_post((string)$valor);
    }

    public static function imagen(string $key, string $default = '', ?string $titulo = null, ?string $seccion = null, ?string $desc = null): string
    {
        return (string) self::get($key, $default, false, $titulo, $seccion, null, $desc, 'image');
    }

    public static function menu(string $key, array $default = [], ?string $titulo = null, ?string $seccion = null, ?string $sub = null, ?string $desc = null): array
    {
        $valor = self::get($key, $default, false, $titulo, $seccion, $sub, $desc, 'menu_structure');
        return is_array($valor) ? $valor : $default;
    }

    public static function resetSeccionDefaults(string $seccionSlugAResetear): array
    {
        $resultadosReset = ['exito' => [], 'error' => [], 'noEncontradoOVacio' => true, 'camposProcesadosContador' => 0];
        $definiciones = OpcionRegistry::getDefiniciones();

        if (empty($definiciones)) {
            return $resultadosReset;
        }

        $seccionExisteEnConfig = false;
        foreach ($definiciones as $key => $config) {
            if (sanitize_title($config['seccion'] ?? 'general') !== $seccionSlugAResetear) {
                continue;
            }

            $seccionExisteEnConfig = true;
            OpcionRepository::save($key, $config['valorDefault']);
            OpcionRepository::deletePanelMeta($key);
            $resultadosReset['exito'][] = $key;
            $resultadosReset['camposProcesadosContador']++;
        }

        if ($seccionExisteEnConfig && $resultadosReset['camposProcesadosContador'] > 0) {
            $resultadosReset['noEncontradoOVacio'] = false;
        }

        return $resultadosReset;
    }
    
    /**
     * Devuelve las definiciones registradas. Actúa como un proxy a OpcionRegistry.
     */
    public static function getDefinicionesRegistradas(): array
    {
        return OpcionRegistry::getDefiniciones();
    }
}