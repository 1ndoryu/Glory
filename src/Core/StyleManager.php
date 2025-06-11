<?php
namespace Glory\Core;

use Glory\Core\GloryLogger; // Asegúrate que GloryLogger está disponible
use Glory\Core\AssetManager; // Importa la clase base

/**
 * Gestiona la definición, registro y puesta en cola de hojas de estilo CSS en WordPress.
 * Hereda de AssetManager para funcionalidades comunes de gestión de assets.
 * @author @wandorius
 * @tarea Jules: Corregida implementación de AssetManager (uso de assetsDefinidos y método enqueueItems). Revisión general.
 * @tarea-completada Jules: Cambiada la visibilidad de enqueueItems a public para corregir error fatal con add_action.
 * @tarea-pendiente Jules: Revisar los métodos `define` y `defineFolder` para asegurar consistencia y optimización después de los cambios en AssetManager.
 */
class StyleManager extends AssetManager // Hereda de AssetManager
{
    // Las propiedades $assetsDefinidos, $modoDesarrolloGlobal, $versionTema
    // y los métodos setGlobalDevMode, setThemeVersion se heredan de AssetManager.

    /**
     * Define una hoja de estilo para ser gestionada.
     * Prepara la configuración específica del estilo y llama a `defineAsset` de la clase base.
     *
     * @param string $handle Identificador único para el estilo (manejador).
     * @param string|null $rutaCss Ruta relativa al archivo CSS (ej: 'css/mi-estilo.css').
     *                             Si es null, se asume 'css/{$handle}.css'.
     * @param array $dependencias Dependencias de otros estilos.
     * @param string|null $version Versión del estilo.
     * @param string $medios Tipo de medio (ej: 'all', 'screen').
     * @param bool|null $modoDesarrolloEspecifico Modo desarrollo para este estilo.
     */
    public static function define(
        string $handle, // Nombre único del estilo
        ?string $rutaCss = null, // Ruta al archivo CSS, relativa al tema
        array $dependencias = [], // Otros estilos de los que depende
        ?string $version = null, // Versión del estilo
        string $medios = 'all', // Medios para los que aplica
        ?bool $modoDesarrolloEspecifico = null // Modo desarrollo específico
    ): void {
        if (is_null($rutaCss)) {
            $rutaCss = 'css/' . $handle . '.css'; // Ruta por defecto
        }

        $configuracion = [
            'ruta' => $rutaCss,
            'dependencias' => $dependencias,
            'version' => $version,
            'medios' => $medios,
            'modoDesarrollo' => $modoDesarrolloEspecifico,
            'identificador' => $handle // Guardamos el handle para referencia
        ];

        static::defineAsset($handle, $configuracion);
    }

    /**
     * Define automáticamente todos los estilos de una carpeta.
     * Llama al método `definirDesdeCarpeta` de la clase base AssetManager.
     *
     * @param string $rutaRelativaCarpeta Ruta de la carpeta relativa a la raíz del tema.
     * @param array $dependenciasDefault Dependencias por defecto.
     * @param string $mediosDefault Medios por defecto.
     * @param bool|null $modoDesarrolloCarpeta Modo desarrollo para la carpeta.
     * @param string $prefijoManejador Prefijo para los handles generados.
     * @param array $archivosExcluidos Archivos a excluir.
     */
    public static function defineFolder(
        string $rutaRelativaCarpeta = 'css', // Carpeta por defecto para CSS
        array $dependenciasDefault = [],
        string $mediosDefault = 'all', // Opción específica de CSS
        ?bool $modoDesarrolloCarpeta = null,
        string $prefijoManejador = '',
        array $archivosExcluidos = []
    ): void {
        static::definirDesdeCarpeta(
            'css',
            $rutaRelativaCarpeta,
            $dependenciasDefault,
            $mediosDefault, // $opcionesDefault para definirDesdeCarpeta
            $modoDesarrolloCarpeta,
            $prefijoManejador,
            $archivosExcluidos
        );
    }

    /**
     * Implementación del método abstracto para construir la configuración de un estilo
     * cuando se define desde una carpeta.
     */
    protected static function construirConfiguracionAssetDesdeCarpeta(
        string $rutaRelativaWeb,
        array $dependenciasDefault,
        mixed $opcionesDefault, // Aquí $opcionesDefault será $mediosDefault
        ?bool $modoDesarrollo,
        string $identificador // Identificador del asset
    ): array {
        return [
            'ruta' => $rutaRelativaWeb,
            'dependencias' => $dependenciasDefault,
            'version' => null, // La versión se calculará en enqueueItems
            'medios' => $opcionesDefault, // $opcionesDefault es $mediosDefault
            'modoDesarrollo' => $modoDesarrollo,
            'identificador' => $identificador,
        ];
    }

    /**
     * Registra el hook para poner en cola los estilos definidos.
     * Se engancha a 'wp_enqueue_scripts'.
     */
    public static function register(): void {
        // Se usa una prioridad de 15 para asegurar que se ejecute después de registros base,
        // pero antes de la puesta en cola principal de scripts (prioridad 20 en ScriptManager).
        add_action('wp_enqueue_scripts', [self::class, 'enqueueItems'], 15);
    }

    /**
     * Pone en cola todos los estilos definidos.
     * Este método es llamado por el hook 'wp_enqueue_scripts'.
     * Procesa cada estilo, determina su versión, y lo pone en cola.
     */
    public static function enqueueItems(): void {
        if (empty(self::$assetsDefinidos)) {
            return; // No hay estilos definidos para procesar.
        }

        foreach (self::$assetsDefinidos as $handle => $definicionEstilo) {
            // Normaliza la ruta relativa y construye la ruta física y la URL del archivo.
            $rutaRelativa = ltrim($definicionEstilo['ruta'], '/\\');
            $rutaArchivo = get_template_directory() . '/' . $rutaRelativa; // Ruta física al archivo.
            $urlArchivo = get_template_directory_uri() . '/' . $rutaRelativa; // URL del archivo.

            if (!file_exists($rutaArchivo)) {
                GloryLogger::error("StyleManager: Archivo de estilo '{$rutaArchivo}' (handle: '{$handle}') no encontrado. Se omite.");
                continue;
            }

            // Determina si se usa el modo desarrollo para este estilo (específico o global).
            $esDesarrollo = $definicionEstilo['modoDesarrollo'] ?? self::$modoDesarrolloGlobal;
            $versionEstilo = $definicionEstilo['version'];

            // Si no hay versión definida, usa el timestamp del archivo (si modo desarrollo) o la versión del tema.
            if (is_null($versionEstilo)) {
                $versionEstilo = ($esDesarrollo && file_exists($rutaArchivo)) ? (string)filemtime($rutaArchivo) : self::$versionTema;
            }

            wp_enqueue_style(
                $handle,
                $urlArchivo,
                $definicionEstilo['dependencias'],
                (string)$versionEstilo, // Asegura que la versión sea un string.
                $definicionEstilo['medios']
            );
        }
    }
}