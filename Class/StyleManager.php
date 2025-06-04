<?php

namespace Glory\Class;
use Glory\Class\GloryLogger;

class StyleManager {
    private static $estilos = [];
    private static $modoDesarrolloGlobal = false;
    private static $versionTema = '1.0.0';

    public static function setGlobalDevMode(bool $enabled): void {
        self::$modoDesarrolloGlobal = $enabled;
    }

    public static function setThemeVersion(string $version): void {
        self::$versionTema = $version;
    }

    public static function define(
        string $handle,
        ?string $rutaCss = null,
        array $dependencias = [],
        ?string $version = null,
        string $medios = 'all',
        ?bool $modoDesarrolloEspecifico = null
    ): void {
        if (is_null($rutaCss)) {
            $rutaCss = 'css/' . $handle . '.css';
        }
        if (empty($handle)) {
            GloryLogger::error("El manejador del estilo no puede estar vacío.");
            return;
        }

        self::$estilos[$handle] = [
            'ruta' => $rutaCss,
            'dependencias' => $dependencias,
            'version' => $version,
            'medios' => $medios,
            'modoDesarrollo' => $modoDesarrolloEspecifico,
            'manejador' => $handle
        ];
    }

    public static function defineFolder(
        string $rutaRelativaCarpeta = 'css',
        array $dependenciasDefault = [],
        string $mediosDefault = 'all',
        ?bool $modoDesarrolloCarpeta = null,
        string $prefijoManejador = '',
        array $archivosExcluidos = []
    ): void {
        $rutaCompletaCarpeta = get_template_directory() . '/' . trim($rutaRelativaCarpeta, '/\\');

        if (!is_dir($rutaCompletaCarpeta)) {
            if ($rutaRelativaCarpeta !== 'css') {
                GloryLogger::error("Carpeta no encontrada en {$rutaCompletaCarpeta} al definir estilos por carpeta.");
            }
            return;
        }

        $archivosCss = glob($rutaCompletaCarpeta . '/*.css');
        if ($archivosCss === false) {
            error_log("StyleManager: Fallo al escanear la carpeta {$rutaCompletaCarpeta}");
            return;
        }

        foreach ($archivosCss as $archivoCss) {
            $manejadorRaw = $prefijoManejador . basename($archivoCss, '.css');
            $handle = preg_replace('/[^a-zA-Z0-9_-]/', '', $manejadorRaw);

            if (empty($handle)) {
                GloryLogger::error("El manejador generado está vacío para el archivo {$archivoCss}. Omitiendo.");
                continue;
            }

            $rutaRelativaArchivo = str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                trim($rutaRelativaCarpeta, '/\\') . '/' . basename($archivoCss)
            );

            if (in_array(basename($archivoCss), $archivosExcluidos)) {
                continue;
            }

            if (!isset(self::$estilos[$handle])) {
                self::define(
                    $handle,
                    $rutaRelativaArchivo,
                    $dependenciasDefault,
                    null,
                    $mediosDefault,
                    $modoDesarrolloCarpeta
                );
            }
        }
    }

    public static function register(): void {
        add_action('wp_enqueue_scripts', [self::class, 'enqueueStyles'], 15);
    }

    public static function enqueueStyles(): void {
        if (empty(self::$estilos)) {
            return;
        }

        foreach (self::$estilos as $handle => $definicionEstilo) {
            $rutaRelativa = ltrim($definicionEstilo['ruta'], '/\\');
            $rutaArchivo = get_template_directory() . '/' . $rutaRelativa;
            $urlArchivo = get_template_directory_uri() . '/' . $rutaRelativa;

            if (!file_exists($rutaArchivo)) {
                GloryLogger::error("Archivo de estilo no encontrado en {$rutaArchivo} para el manejador '{$handle}'.");
                continue;
            }

            $esDesarrollo = $definicionEstilo['modoDesarrollo'] ?? self::$modoDesarrolloGlobal;
            $versionEstilo = $definicionEstilo['version'];

            if (is_null($versionEstilo)) {
                $versionEstilo = $esDesarrollo && file_exists($rutaArchivo) ? filemtime($rutaArchivo) : self::$versionTema;
            }

            wp_enqueue_style(
                $handle,
                $urlArchivo,
                $definicionEstilo['dependencias'],
                $versionEstilo,
                $definicionEstilo['medios']
            );
        }
    }
}