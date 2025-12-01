<?php

namespace Glory\Manager;

class TemplateManager
{
    /**
     * @var array<string, string> Mapa de nombres de plantilla a rutas de archivo.
     */
    private static $templates = [];

    /**
     * @var bool Indica si ya se escanearon las plantillas.
     */
    private static $scanned = false;

    /**
     * Obtiene el callback (nombre de función) para una plantilla dada.
     * Carga el archivo si es necesario.
     *
     * @param string $templateName Nombre de la plantilla (ej: 'plantillaLibro').
     * @return callable|null El nombre de la función si es callable, o null si no se encuentra.
     */
    public static function getTemplateCallback(string $templateName): ?string
    {
        if (!self::$scanned) {
            self::scanTemplates();
        }

        if (isset(self::$templates[$templateName])) {
            $filePath = self::$templates[$templateName];
            if (file_exists($filePath)) {
                include_once $filePath;
                error_log("TemplateManager: Archivo incluido: $filePath");
            } else {
                error_log("TemplateManager: Archivo no existe: $filePath");
            }
        } else {
            error_log("TemplateManager: Plantilla '$templateName' no encontrada en mapa. Plantillas disponibles: " . implode(', ', array_keys(self::$templates)));
        }

        if (function_exists($templateName)) {
            return $templateName;
        }

        return null;
    }

    /**
     * Escanea el directorio de plantillas en busca de archivos PHP.
     */
    private static function scanTemplates(): void
    {
        $baseDir = get_template_directory() . '/App/Templates';
        
        if (!is_dir($baseDir)) {
            return;
        }

        self::scanDirectory($baseDir);
        self::$scanned = true;
    }

    /**
     * Escanea recursivamente un directorio.
     *
     * @param string $dir Directorio a escanear.
     */
    private static function scanDirectory(string $dir): void
    {
        $files = scandir($dir);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                self::scanDirectory($path);
            } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $templateName = pathinfo($path, PATHINFO_FILENAME);
                self::$templates[$templateName] = $path;
            }
        }
    }
}
