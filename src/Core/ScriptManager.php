<?php
namespace Glory\Core;

use Glory\Core\GloryLogger; // Asegúrate que GloryLogger está disponible
use Glory\Core\AssetManager; // Importa la clase base

/**
 * Gestiona la definición, registro y puesta en cola de scripts JavaScript en WordPress.
 * Hereda de AssetManager para funcionalidades comunes de gestión de assets.
 * @author @wandorius
 * @tarea Jules: Corregido error fatal de método abstracto no implementado.
 * @tarea-completada Jules: Cambiada la visibilidad de enqueueItems a public para corregir error fatal con add_action.
 * @tarea-pendiente Jules: Revisar los métodos `define` y `defineFolder` para asegurar consistencia y optimización después de los cambios en AssetManager.
 * @tarea-completada Jules: Corregida advertencia PHP por clave 'enPiePagina' no definida en enqueueItems usando un valor por defecto.
 * @tarea-completada Jules: Asegurada la inclusión de la clave 'enPiePagina' por defecto en construirConfiguracionAssetDesdeCarpeta.
 */
class ScriptManager extends AssetManager // Hereda de AssetManager
{
    // Las propiedades $assetsDefinidos, $modoDesarrolloGlobal, $versionTema
    // y los métodos setGlobalDevMode, setThemeVersion se heredan de AssetManager.

    /**
     * Define un script para ser gestionado.
     * Prepara la configuración específica del script y llama a `defineAsset` de la clase base.
     *
     * @param string $identificador Identificador único para el script (handle).
     * @param string|null $ruta Ruta relativa al archivo del script desde la raíz del tema (ej: 'js/mi-script.js').
     *                            Si es null, se asume 'js/{identificador}.js'.
     * @param array $dependencias Array de identificadores de scripts de los que depende este script.
     * @param string|null $version Versión del script. Si es null, se usará la versión del tema o el timestamp del archivo.
     * @param bool $enPiePagina True para cargar el script en el pie de página, false para cargarlo en el <head>.
     * @param array|null $datosLocalizacion Datos para localizar con `wp_localize_script`.
     * @param bool|null $modoDesarrollo Define si este script específico debe usar timestamp como versión.
     *                                  Si es null, se usa el valor de `$modoDesarrolloGlobal` heredado.
     */
    public static function define( // Mantiene la firma original para compatibilidad externa
        string $identificador, // Nombre único del script
        ?string $ruta = null, // Ruta al archivo JS, relativa al tema
        array $dependencias = [], // Otros scripts de los que depende
        ?string $version = null, // Versión del script
        bool $enPiePagina = true, // Si se carga en el footer
        ?array $datosLocalizacion = null, // Datos para wp_localize_script
        ?bool $modoDesarrollo = null // Modo desarrollo específico para este script
    ): void {
        if (is_null($ruta)) {
            $ruta = 'js/' . $identificador . '.js'; // Ruta por defecto si no se provee
        }

        // Validación de datos de localización
        if (!is_null($datosLocalizacion)) {
            if (empty($datosLocalizacion['nombreObjeto']) || !is_string($datosLocalizacion['nombreObjeto'])) {
                GloryLogger::error("ScriptManager: 'nombreObjeto' inválido o vacío para localización de script '{$identificador}'. Se omite localización.");
                $datosLocalizacion = null;
            } elseif (!isset($datosLocalizacion['datos']) || !is_array($datosLocalizacion['datos'])) {
                GloryLogger::error("ScriptManager: 'datos' inválidos (debe ser array) para localización de script '{$identificador}'. Se omite localización.");
                $datosLocalizacion = null;
            } elseif (empty($datosLocalizacion['datos'])) {
                GloryLogger::warning("ScriptManager: Array 'datos' vacío para localización de script '{$identificador}'.");
            }
        }

        // Construye el array de configuración específico para scripts
        $configuracion = [
            'ruta' => $ruta,
            'dependencias' => $dependencias,
            'version' => $version,
            'enPiePagina' => $enPiePagina,
            'datosLocalizacion' => $datosLocalizacion,
            'modoDesarrollo' => $modoDesarrollo,
            'identificador' => $identificador
        ];

        // Llama al método de la clase base para almacenar el asset.
        static::defineAsset($identificador, $configuracion);
    }

    /**
     * Define automáticamente todos los scripts de una carpeta.
     * Llama al método `definirDesdeCarpeta` de la clase base AssetManager.
     *
     * @param string $rutaRelativaCarpeta Ruta de la carpeta relativa a la raíz del tema.
     * @param array $dependenciasDefault Dependencias por defecto.
     * @param bool $enPiePaginaDefault Si se cargan en el footer por defecto.
     * @param bool|null $modoDesarrolloCarpeta Modo desarrollo para la carpeta.
     * @param string $prefijoIdentificador Prefijo para los handles generados.
     * @param array $archivosExcluidos Archivos a excluir.
     */
    public static function defineFolder( // Mantiene la firma para compatibilidad
        string $rutaRelativaCarpeta = 'js', // Carpeta por defecto para JS
        array $dependenciasDefault = [],
        bool $enPiePaginaDefault = true, // Opción específica de JS
        ?bool $modoDesarrolloCarpeta = null,
        string $prefijoIdentificador = '',
        array $archivosExcluidos = []
    ): void {
        // Llama al método genérico de la clase base, pasando 'js' como extensión
        // y $enPiePaginaDefault como $opcionesDefault.
        static::definirDesdeCarpeta(
            'js',
            $rutaRelativaCarpeta,
            $dependenciasDefault,
            $enPiePaginaDefault, // $opcionesDefault para definirDesdeCarpeta
            $modoDesarrolloCarpeta,
            $prefijoIdentificador,
            $archivosExcluidos
        );
    }

    /**
     * Implementación del método abstracto para construir la configuración de un script
     * cuando se define desde una carpeta.
     */
    protected static function construirConfiguracionAssetDesdeCarpeta(
        string $rutaRelativaWeb,
        array $dependenciasDefault,
        mixed $opcionesDefault, // Aquí $opcionesDefault será $enPiePaginaDefault
        ?bool $modoDesarrollo,
        string $identificador // Identificador del asset
    ): array {
        return [
            'ruta' => $rutaRelativaWeb,
            'dependencias' => $dependenciasDefault,
            'version' => null, // La versión se calculará en enqueueItems
            'enPiePagina' => $opcionesDefault ?? true, // $opcionesDefault es $enPiePaginaDefault, por defecto true
            'datosLocalizacion' => null, // No hay datos de localización por defecto para defineFolder
            'modoDesarrollo' => $modoDesarrollo,
            'identificador' => $identificador,
        ];
    }

    /**
     * Registra el hook para poner en cola los scripts definidos.
     * Se engancha a 'wp_enqueue_scripts'.
     */
    public static function register(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueItems'], 20);
    }

    /**
     * Pone en cola todos los scripts definidos.
     * Este método es llamado por el hook 'wp_enqueue_scripts'.
     * Procesa cada script, determina su versión, lo registra y lo pone en cola,
     * además de localizar datos si se especificaron.
     */
    public static function enqueueItems(): void
    {
        if (empty(self::$assetsDefinidos)) {
            return; // No hay scripts definidos para procesar.
        }

        foreach (self::$assetsDefinidos as $identificador => $definicionScript) {
            // Si el script ya está en cola (ej. por otro plugin/tema o manualmente),
            // solo intenta localizar datos si es necesario y luego continúa.
            if (wp_script_is($identificador, 'enqueued')) {
                if (!empty($definicionScript['datosLocalizacion']) &&
                    !empty($definicionScript['datosLocalizacion']['nombreObjeto']) &&
                    is_array($definicionScript['datosLocalizacion']['datos'])) {
                    wp_localize_script(
                        $identificador,
                        $definicionScript['datosLocalizacion']['nombreObjeto'],
                        $definicionScript['datosLocalizacion']['datos']
                    );
                }
                continue;
            }

            $rutaRelativa = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $definicionScript['ruta']), '/');
            $rutaArchivo = get_template_directory() . '/' . $rutaRelativa; // Ruta física al archivo.
            $urlArchivo = get_template_directory_uri() . '/' . $rutaRelativa; // URL del archivo.

            if (!file_exists($rutaArchivo)) {
                GloryLogger::error("ScriptManager: Archivo '{$rutaArchivo}' (handle: '{$identificador}') no encontrado. Se omite.");
                continue;
            }

            // Determina si se usa el modo desarrollo para este script (específico o global).
            $esDesarrollo = $definicionScript['modoDesarrollo'] ?? self::$modoDesarrolloGlobal;
            $versionScript = $definicionScript['version'];

            // Si no hay versión definida y el modo desarrollo está activo, usa el timestamp del archivo.
            // De lo contrario, usa la versión del tema.
            if (is_null($versionScript)) {
                $tiempoModificacion = @filemtime($rutaArchivo); // Supresor de error por si el archivo no existe (aunque ya se verificó).
                $versionScript = ($esDesarrollo && $tiempoModificacion) ? (string)$tiempoModificacion : self::$versionTema;
            }

            // Asegura que 'enPiePagina' tenga un valor por defecto si no está definido.
            $enPiePagina = $definicionScript['enPiePagina'] ?? true; // Valor por defecto true

            $registradoCorrectamente = wp_register_script(
                $identificador,
                $urlArchivo,
                $definicionScript['dependencias'],
                $versionScript,
                $enPiePagina // Usa la variable con valor por defecto
            );

            if (!$registradoCorrectamente) {
                GloryLogger::error("ScriptManager: wp_register_script devolvió false para '{$identificador}' (URL: '{$urlArchivo}'). Se omite.");
                continue;
            }

            if (!empty($definicionScript['datosLocalizacion']) && !empty($definicionScript['datosLocalizacion']['nombreObjeto']) && isset($definicionScript['datosLocalizacion']['datos']) && is_array($definicionScript['datosLocalizacion']['datos'])) {
                $localizacionExitosa = wp_localize_script(
                    $identificador,
                    $definicionScript['datosLocalizacion']['nombreObjeto'],
                    $definicionScript['datosLocalizacion']['datos']
                );
                if (!$localizacionExitosa) {
                    GloryLogger::error("ScriptManager: wp_localize_script falló para '{$identificador}' (Objeto: '{$definicionScript['datosLocalizacion']['nombreObjeto']}').");
                }
            }
            wp_enqueue_script($identificador);
        }
    }
}
