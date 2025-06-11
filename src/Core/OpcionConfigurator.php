<?

namespace Glory\Core;

use Glory\Core\GloryLogger;
use Glory\Core\OpcionRegistry;
use Glory\Core\OpcionRepository;

/**
 * Orquesta la configuración e inicialización de las opciones del tema.
 *
 * Su única responsabilidad es registrar las definiciones de las opciones
 * y sincronizarlas con la base de datos al arrancar el framework,
 * asegurando que los valores en la base de datos sean consistentes
 * con lo definido en el código.
 */
class OpcionConfigurator
{
    private static bool $haSincronizado = false;

    /**
     * Define y registra una nueva opción para ser gestionada.
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
    }

    /**
     * Engancha el proceso de sincronización al hook 'init' de WordPress.
     */
    public static function inicializarSincronizacion(): void
    {
        add_action('init', [self::class, 'sincronizarTodasLasOpciones'], 5);
    }

    /**
     * Sincroniza todas las opciones definidas, comparando su estado en la BD
     * con su definición en el código y aplicando la lógica necesaria.
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
        $key = $config['etiqueta'];
        $centinela = OpcionRepository::getCentinela();
        $hashCodigoActual = $config['hashVersionCodigo'];

        if ($config['forzarDefaultAlRegistrar']) {
            if ($estado['valor'] === $centinela || $estado['valor'] !== $config['valorDefault']) {
                $mensaje = "OpcionConfigurator: '{$key}' se actualizará a default de código debido a 'forzarDefaultAlRegistrar'.";
                return ['SOBREESCRIBIR_CON_DEFAULT', $mensaje];
            }
        }

        if ($estado['esPanel']) {
            if ($estado['hashPanel'] === $centinela) {
                $mensaje = "OpcionConfigurator: '{$key}' (valor de panel) inconsistente (sin hash guardado). Se revierte a default.";
                GloryLogger::error($mensaje);
                return ['SOBREESCRIBIR_CON_DEFAULT', null];
            }
            if ($hashCodigoActual !== $estado['hashPanel']) {
                $mensaje = "OpcionConfigurator: '{$key}' (valor de panel) obsoleto (default de código cambió). Se revierte a default.";
                GloryLogger::warning($mensaje);
                return ['SOBREESCRIBIR_CON_DEFAULT', null];
            }
            return ['MANTENER_VALOR_PANEL', null];
        }

        if ($estado['valor'] === $centinela) {
            $mensaje = "OpcionConfigurator: '{$key}' no existe en BD. Se establece a default de código.";
            return ['SOBREESCRIBIR_CON_DEFAULT', $mensaje];
        }

        if ($estado['hashPanel'] !== $centinela) {
            return ['LIMPIAR_METADATOS', null];
        }

        return ['NO_HACER_NADA', null];
    }
}
