<?php
namespace Glory\Core;

use Glory\Core\GloryLogger; // Asegúrate que GloryLogger está disponible
use Glory\Core\AssetManager; // Importa la clase base

/**
 * Gestiona la definición, registro y puesta en cola de scripts JavaScript en WordPress.
 * Hereda de AssetManager para funcionalidades comunes de gestión de assets.
 * @author @wandorius
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
                GloryLogger::error("ScriptManager: Nombre de objeto ('nombreObjeto') inválido o vacío para los datos de localización del script '{$identificador}'. La localización será omitida.");
                $datosLocalizacion = null;
            } elseif (!isset($datosLocalizacion['datos']) || !is_array($datosLocalizacion['datos'])) {
                GloryLogger::error("ScriptManager: Datos ('datos') inválidos (debe ser un array) para la localización del script '{$identificador}'. La localización será omitida.");
                $datosLocalizacion = null;
            } elseif (empty($datosLocalizacion['datos'])) {
                GloryLogger::warning("ScriptManager: El array de datos ('datos') está VACÍO para la localización del script '{$identificador}'.");
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
        // Se usa parent::defineAsset si se quiere evitar la redefinición en la clase hija,
        // o self::defineAsset si se quiere permitir que la clase hija pueda sobreescribir defineAsset.
        // En este caso, como AssetManager::defineAsset es protected, usamos static::
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
            'enPiePagina' => $opcionesDefault, // $opcionesDefault es $enPiePaginaDefault
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
        add_action('wp_enqueue_scripts', [self::class, 'enqueueScripts'], 20);
    }

    /**
     * Pone en cola todos los scripts definidos.
     * Este método es llamado por el hook 'wp_enqueue_scripts'.
     * Procesa cada script, determina su versión, lo registra y lo pone en cola,
     * además de localizar datos si se especificaron.
     */
    public static function enqueueScripts(): void
    {
        if (empty(self::$coleccionScripts)) {
            return; // No hay scripts definidos para procesar.
        }

        foreach (self::$coleccionScripts as $identificador => $definicionScript) {
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
                GloryLogger::error("El archivo de script '{$rutaArchivo}' para el identificador '{$identificador}' no fue encontrado. Se omite su puesta en cola.");
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

            $registradoCorrectamente = wp_register_script(
                $identificador,
                $urlArchivo,
                $definicionScript['dependencias'],
                $versionScript,
                $definicionScript['enPiePagina']
            );

            if (!$registradoCorrectamente) {
                GloryLogger::error("FALLO al registrar script '{$identificador}' en URL '{$urlArchivo}'. wp_register_script devolvió false. Omitiendo.");
                continue;
            }

            $localizacionExitosa = false;
            if (!empty($definicionScript['datosLocalizacion']) && !empty($definicionScript['datosLocalizacion']['nombreObjeto']) && isset($definicionScript['datosLocalizacion']['datos']) && is_array($definicionScript['datosLocalizacion']['datos'])) {
                $localizacionExitosa = wp_localize_script(
                    $identificador,
                    $definicionScript['datosLocalizacion']['nombreObjeto'],
                    $definicionScript['datosLocalizacion']['datos']
                );
                if (!$localizacionExitosa) {
                    GloryLogger::error("wp_localize_script FALLÓ para el identificador '{$identificador}'. Nombre objeto: '{$definicionScript['datosLocalizacion']['nombreObjeto']}'.");
                }
            }
            wp_enqueue_script($identificador);
        }
    }
}
