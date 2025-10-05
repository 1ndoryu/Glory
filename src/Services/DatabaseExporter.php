<?php

namespace Glory\Services;

use Glory\Core\GloryLogger;
use Glory\Services\DatabaseUrlReplacer;

/**
 * Servicio de exportación de base de datos con reemplazo de URLs
 */
class DatabaseExporter
{
    private $wpdb;
    private ?DatabaseUrlReplacer $urlReplacer = null;
    private array $stats = [
        'tables_processed' => 0,
        'rows_processed' => 0,
        'rows_modified' => 0,
        'tables_total' => 0,
    ];

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Exporta la base de datos con reemplazo de URLs
     * 
     * @param string $newUrl Nueva URL a la que se migrará
     * @param bool $includeDropTables Si se deben incluir DROP TABLE statements
     * @return array ['success' => bool, 'file' => string|null, 'stats' => array, 'message' => string]
     */
    public function export(string $newUrl, bool $includeDropTables = false): array
    {
        try {
            // Validar URL
            if (!filter_var($newUrl, FILTER_VALIDATE_URL)) {
                return [
                    'success' => false,
                    'message' => 'La URL proporcionada no es válida.',
                    'stats' => $this->stats
                ];
            }

            // Obtener URL actual del sitio
            $oldUrl = get_site_url();

            // Si las URLs son iguales, solo exportar sin reemplazo
            if ($oldUrl === $newUrl) {
                GloryLogger::info("URLs idénticas, exportando sin reemplazo.");
                $this->urlReplacer = null;
            } else {
                // Crear el replacer de URLs
                $this->urlReplacer = new DatabaseUrlReplacer($oldUrl, $newUrl);
                GloryLogger::info("Iniciando exportación con reemplazo de URLs: {$oldUrl} -> {$newUrl}");
            }

            // Crear directorio temporal si no existe
            $uploadDir = wp_upload_dir();
            $exportDir = $uploadDir['basedir'] . '/glory-exports';
            
            if (!file_exists($exportDir)) {
                wp_mkdir_p($exportDir);
            }

            // Proteger directorio con .htaccess
            $this->protectExportDirectory($exportDir);

            // Nombre del archivo
            $filename = 'glory-export-' . date('Y-m-d-His') . '.sql';
            $filepath = $exportDir . '/' . $filename;

            // Abrir archivo para escritura
            $handle = fopen($filepath, 'w');
            
            if (!$handle) {
                throw new \Exception("No se pudo crear el archivo de exportación.");
            }

            // Escribir encabezado del archivo SQL
            $this->writeHeader($handle, $oldUrl, $newUrl);

            // Obtener todas las tablas
            $tables = $this->getTables();
            $this->stats['tables_total'] = count($tables);

            // Exportar cada tabla
            foreach ($tables as $table) {
                $this->exportTable($handle, $table, $includeDropTables);
                $this->stats['tables_processed']++;
            }

            // Cerrar archivo
            fclose($handle);

            GloryLogger::info("Exportación completada: {$filename}", $this->stats);

            return [
                'success' => true,
                'file' => $filename,
                'filepath' => $filepath,
                'stats' => $this->stats,
                'message' => 'Exportación completada exitosamente.',
                'download_url' => $uploadDir['baseurl'] . '/glory-exports/' . $filename
            ];

        } catch (\Exception $e) {
            GloryLogger::error("Error en exportación: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'stats' => $this->stats
            ];
        }
    }

    /**
     * Obtiene todas las tablas de la base de datos
     * 
     * @return array
     */
    private function getTables(): array
    {
        $tables = $this->wpdb->get_col("SHOW TABLES");
        return $tables ? $tables : [];
    }

    /**
     * Exporta una tabla individual
     * 
     * @param resource $handle
     * @param string $table
     * @param bool $includeDropTables
     */
    private function exportTable($handle, string $table, bool $includeDropTables): void
    {
        // Escribir comentario con nombre de tabla
        fwrite($handle, "\n\n-- --------------------------------------------------------\n");
        fwrite($handle, "-- Tabla: {$table}\n");
        fwrite($handle, "-- --------------------------------------------------------\n\n");

        // DROP TABLE si está habilitado
        if ($includeDropTables) {
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
        }

        // Obtener estructura de la tabla
        $createTable = $this->wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);
        if ($createTable) {
            fwrite($handle, $createTable[1] . ";\n\n");
        }

        // Exportar datos
        $this->exportTableData($handle, $table);
    }

    /**
     * Exporta los datos de una tabla
     * 
     * @param resource $handle
     * @param string $table
     */
    private function exportTableData($handle, string $table): void
    {
        // Obtener total de filas
        $rowCount = $this->wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");
        
        if (!$rowCount || $rowCount == 0) {
            fwrite($handle, "-- Tabla vacía\n\n");
            return;
        }

        // Procesar por lotes para no sobrecargar memoria
        $batchSize = 100;
        $offset = 0;

        while ($offset < $rowCount) {
            $rows = $this->wpdb->get_results(
                "SELECT * FROM `{$table}` LIMIT {$batchSize} OFFSET {$offset}",
                ARRAY_A
            );

            if (!$rows) {
                break;
            }

            foreach ($rows as $row) {
                $this->stats['rows_processed']++;
                
                // Procesar fila con reemplazo de URLs si está habilitado
                if ($this->urlReplacer !== null) {
                    $originalRow = $row;
                    $row = $this->urlReplacer->replace($row);
                    
                    // Contar si hubo modificaciones
                    if ($row !== $originalRow) {
                        $this->stats['rows_modified']++;
                    }
                }

                // Escribir INSERT statement
                $this->writeInsertStatement($handle, $table, $row);
            }

            $offset += $batchSize;
        }

        fwrite($handle, "\n");
    }

    /**
     * Escribe un statement INSERT
     * 
     * @param resource $handle
     * @param string $table
     * @param array $row
     */
    private function writeInsertStatement($handle, string $table, array $row): void
    {
        $columns = array_keys($row);
        $values = array_values($row);

        // Escapar valores
        $escapedValues = array_map(function($value) {
            if (is_null($value)) {
                return 'NULL';
            }
            return "'" . $this->wpdb->_real_escape($value) . "'";
        }, $values);

        // Construir statement
        $columnList = '`' . implode('`, `', $columns) . '`';
        $valueList = implode(', ', $escapedValues);

        fwrite($handle, "INSERT INTO `{$table}` ({$columnList}) VALUES ({$valueList});\n");
    }

    /**
     * Escribe el encabezado del archivo SQL
     * 
     * @param resource $handle
     * @param string $oldUrl
     * @param string $newUrl
     */
    private function writeHeader($handle, string $oldUrl, string $newUrl): void
    {
        $date = date('Y-m-d H:i:s');
        $wpVersion = get_bloginfo('version');
        
        $header = <<<SQL
-- --------------------------------------------------------
-- Glory Database Export
-- --------------------------------------------------------
-- 
-- Generado el: {$date}
-- WordPress Version: {$wpVersion}
-- URL Original: {$oldUrl}
-- URL Nueva: {$newUrl}
-- 
-- INSTRUCCIONES:
-- 1. Crear una nueva base de datos vacía
-- 2. Importar este archivo SQL
-- 3. Actualizar wp-config.php con los datos de la nueva base
-- 4. La URL ya está actualizada en el dump
-- 
-- --------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS=0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


SQL;

        fwrite($handle, $header);
    }

    /**
     * Protege el directorio de exportación con .htaccess
     * 
     * @param string $directory
     */
    private function protectExportDirectory(string $directory): void
    {
        $htaccessFile = $directory . '/.htaccess';
        
        if (!file_exists($htaccessFile)) {
            $htaccessContent = <<<HTACCESS
# Proteger archivos de exportación
<Files "*.sql">
    # Solo permitir acceso desde el admin de WordPress
    Order Deny,Allow
    Deny from all
</Files>

# Prevenir listado de directorio
Options -Indexes
HTACCESS;
            
            file_put_contents($htaccessFile, $htaccessContent);
        }
    }

    /**
     * Obtiene las estadísticas de la última exportación
     * 
     * @return array
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Limpia archivos de exportación antiguos (más de 7 días)
     * 
     * @return int Número de archivos eliminados
     */
    public static function cleanOldExports(): int
    {
        $uploadDir = wp_upload_dir();
        $exportDir = $uploadDir['basedir'] . '/glory-exports';
        
        if (!file_exists($exportDir)) {
            return 0;
        }

        $files = glob($exportDir . '/glory-export-*.sql');
        $deletedCount = 0;
        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $sevenDaysAgo) {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }

        if ($deletedCount > 0) {
            GloryLogger::info("Limpieza de exportaciones antiguas: {$deletedCount} archivos eliminados");
        }

        return $deletedCount;
    }

    /**
     * Lista todas las exportaciones disponibles
     * 
     * @return array
     */
    public static function listExports(): array
    {
        $uploadDir = wp_upload_dir();
        $exportDir = $uploadDir['basedir'] . '/glory-exports';
        
        if (!file_exists($exportDir)) {
            return [];
        }

        $files = glob($exportDir . '/glory-export-*.sql');
        $exports = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $exports[] = [
                'filename' => $filename,
                'filepath' => $file,
                'size' => filesize($file),
                'size_formatted' => size_format(filesize($file)),
                'date' => filemtime($file),
                'date_formatted' => date('Y-m-d H:i:s', filemtime($file)),
                'download_url' => $uploadDir['baseurl'] . '/glory-exports/' . $filename
            ];
        }

        // Ordenar por fecha (más reciente primero)
        usort($exports, function($a, $b) {
            return $b['date'] - $a['date'];
        });

        return $exports;
    }
}

