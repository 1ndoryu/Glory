<?
namespace Glory\Clase;

use Glory\Clase\GloryLogger;

class ScriptManager {
    private static $coleccionScripts = [];
    private static $modoDesarrolloGlobal = false;
    private static $versionTema = '1.0.0';

    public static function setGlobalDevMode(bool $activado): void {
        self::$modoDesarrolloGlobal = $activado;
    }

    public static function setThemeVersion(string $version): void {
        self::$versionTema = $version;
    }

    public static function define(
        string $identificador,
        ?string $ruta = null,
        array $dependencias = [],
        ?string $version = null,
        bool $enPiePagina = true,
        ?array $datosLocalizacion = null,
        ?bool $modoDesarrollo = null
    ): void {
        if (empty($identificador)) {
            GloryLogger::error("El identificador del script no puede estar vacío. Definición omitida.");
            return;
        }

        if (isset(self::$coleccionScripts[$identificador])) {
            return;
        }

        if (is_null($ruta)) {
            $ruta = 'js/' . $identificador . '.js';
        }

        if (!is_null($datosLocalizacion)) {
            if (!isset($datosLocalizacion['nombreObjeto']) || !is_string($datosLocalizacion['nombreObjeto']) || empty($datosLocalizacion['nombreObjeto'])) {
                GloryLogger::error("Nombre de objeto ('nombreObjeto') inválido o vacío para los datos de localización del identificador '{$identificador}'. Localización omitida.");
                $datosLocalizacion = null;
            } elseif (!isset($datosLocalizacion['datos']) || !is_array($datosLocalizacion['datos'])) {
                GloryLogger::error("Datos ('datos') inválidos (debe ser un array) para la localización del identificador '{$identificador}'. Localización omitida.");
                $datosLocalizacion = null;
            } elseif (empty($datosLocalizacion['datos'])) {
                 GloryLogger::error("ADVERTENCIA: El array de datos ('datos') está VACÍO para la localización del identificador '{$identificador}'.");
            }
        }

        self::$coleccionScripts[$identificador] = [
            'ruta' => $ruta,
            'dependencias' => $dependencias,
            'version' => $version,
            'enPiePagina' => $enPiePagina,
            'datosLocalizacion' => $datosLocalizacion,
            'modoDesarrollo' => $modoDesarrollo,
            'identificador' => $identificador
        ];
    }

    public static function defineFolder(
        string $rutaRelativaCarpeta = 'js',
        array $dependenciasDefault = [],
        bool $enPiePaginaDefault = true,
        ?bool $modoDesarrolloCarpeta = null,
        string $prefijoIdentificador = '',
        array $archivosExcluidos = []
    ): void {
        $directorioTema = get_template_directory();
        $rutaRelativaCarpetaNormalizada = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $rutaRelativaCarpeta);
        $rutaCompletaCarpeta = rtrim($directorioTema, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($rutaRelativaCarpetaNormalizada, DIRECTORY_SEPARATOR);

        if (!is_dir($rutaCompletaCarpeta)) {
            if (trim($rutaRelativaCarpetaNormalizada, DIRECTORY_SEPARATOR) !== 'js') {
                GloryLogger::error("Carpeta no encontrada en {$rutaCompletaCarpeta} al definir desde carpeta '{$rutaRelativaCarpeta}'.");
            }
            return;
        }

        $archivosJs = glob($rutaCompletaCarpeta . DIRECTORY_SEPARATOR . '*.js');
        if ($archivosJs === false || empty($archivosJs)) {
            return;
        }

        foreach ($archivosJs as $archivo) {
            $nombreArchivoConExtension = basename($archivo);
            if (in_array($nombreArchivoConExtension, $archivosExcluidos)) {
                continue;
            }
            $nombreArchivoBase = basename($archivo, '.js');
            $identificadorCrudo = $prefijoIdentificador . $nombreArchivoBase;
            $identificador = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $identificadorCrudo));
            $identificador = trim($identificador, '-');

            if (empty($identificador)) {
                GloryLogger::error("El identificador generado está vacío para el archivo '{$nombreArchivoBase}' en la carpeta '{$rutaRelativaCarpeta}'. Omitiendo.");
                continue;
            }

            $rutaRelativaWeb = trim(str_replace(DIRECTORY_SEPARATOR, '/', $rutaRelativaCarpetaNormalizada), '/') . '/' . basename($archivo);

            if (!isset(self::$coleccionScripts[$identificador])) {
                self::define(
                    $identificador,
                    $rutaRelativaWeb,
                    $dependenciasDefault,
                    null,
                    $enPiePaginaDefault,
                    null,
                    $modoDesarrolloCarpeta
                );
            }
        }
    }

    public static function register(): void {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueScripts'], 20);
    }

    public static function enqueueScripts(): void {
        if (empty(self::$coleccionScripts)) {
            return;
        }

        foreach (self::$coleccionScripts as $identificador => $definicionScript) {
            if (wp_script_is($identificador, 'enqueued')) {
                if (!empty($definicionScript['datosLocalizacion']) && !empty($definicionScript['datosLocalizacion']['nombreObjeto']) && is_array($definicionScript['datosLocalizacion']['datos'])) {
                    wp_localize_script(
                        $identificador,
                        $definicionScript['datosLocalizacion']['nombreObjeto'],
                        $definicionScript['datosLocalizacion']['datos']
                    );
                }
                continue;
            }

            $rutaRelativa = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $definicionScript['ruta']), '/');
            $rutaArchivo = get_template_directory() . '/' . $rutaRelativa;
            $urlArchivo = get_template_directory_uri() . '/' . $rutaRelativa;

            if (!file_exists($rutaArchivo)) {
                GloryLogger::error("Archivo de script NO ENCONTRADO en '{$rutaArchivo}' para el identificador '{$identificador}'. Omitiendo puesta en cola.");
                continue;
            }

            $esDesarrollo = $definicionScript['modoDesarrollo'] ?? self::$modoDesarrolloGlobal;
            $versionScript = $definicionScript['version'];
            if (is_null($versionScript)) {
                $tiempoModificacion = @filemtime($rutaArchivo);
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