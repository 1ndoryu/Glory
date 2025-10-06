<?php

namespace Glory\Admin;

use Glory\Services\DatabaseExporter;
use Glory\Manager\AssetManager;
use Glory\Core\GloryLogger;

/**
 * Controlador para el panel de exportación de base de datos
 */
class DatabaseExportController
{
    private const MENU_SLUG = 'glory-export';
    private string $hookName;

    /**
     * Registra los hooks necesarios
     */
    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'agregarPaginaExport']);
        add_action('admin_init', [$this, 'handleExportAction']);
        add_action('admin_init', [$this, 'handleDownloadAction']);
        add_action('admin_init', [$this, 'handleDeleteAction']);
    }

    /**
     * Agrega la página al menú de admin
     */
    public function agregarPaginaExport(): void
    {
        $this->hookName = add_menu_page(
            'Glory Export',
            'Glory Export',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'renderizarPagina'],
            'dashicons-database-export',
            61
        );

        add_action('load-' . $this->hookName, [$this, 'enqueueAssets']);
    }

    /**
     * Encola los assets necesarios
     */
    public function enqueueAssets(): void
    {
        // CSS
        AssetManager::define(
            'style',
            'glory-export-css',
            '/Glory/assets/css/glory-export.css',
            [
                'deps' => [],
                'media' => 'all',
                'area' => 'admin',
                'ver' => filemtime(get_template_directory() . '/Glory/assets/css/glory-export.css'),
            ]
        );

        // JavaScript
        AssetManager::define(
            'script',
            'glory-export-js',
            '/Glory/assets/js/admin/glory-export.js',
            [
                'deps' => ['jquery'],
                'in_footer' => true,
                'area' => 'admin',
                'ver' => filemtime(get_template_directory() . '/Glory/assets/js/admin/glory-export.js'),
            ]
        );
    }

    /**
     * Maneja la acción de exportación
     */
    public function handleExportAction(): void
    {
        if (!isset($_POST['glory_export_action']) || $_POST['glory_export_action'] !== 'export') {
            return;
        }

        // Verificar nonce
        if (!isset($_POST['glory_export_nonce']) || !wp_verify_nonce($_POST['glory_export_nonce'], 'glory_export_database')) {
            wp_die('Verificación de seguridad fallida.');
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos suficientes.');
        }

        // Obtener nueva URL
        $newUrl = isset($_POST['new_url']) ? sanitize_url($_POST['new_url']) : '';
        $includeDropTables = isset($_POST['include_drop_tables']) && $_POST['include_drop_tables'] === '1';

        if (empty($newUrl)) {
            add_settings_error('glory_export', 'url_required', 'La URL de destino es requerida.', 'error');
            return;
        }

        // Limpiar exportaciones antiguas antes de crear una nueva
        DatabaseExporter::cleanOldExports();

        // Realizar exportación
        $exporter = new DatabaseExporter();
        $result = $exporter->export($newUrl, $includeDropTables);

        if ($result['success']) {
            $message = sprintf(
                'Exportación completada exitosamente. Procesadas %d tablas, %d filas (%d modificadas). <a href="%s" class="button button-small" download>Descargar</a>',
                $result['stats']['tables_processed'],
                $result['stats']['rows_processed'],
                $result['stats']['rows_modified'],
                esc_url($result['download_url'])
            );
            add_settings_error('glory_export', 'export_success', $message, 'success');
        } else {
            add_settings_error('glory_export', 'export_error', 'Error: ' . $result['message'], 'error');
        }
    }

    /**
     * Maneja la descarga de archivos
     */
    public function handleDownloadAction(): void
    {
        if (!isset($_GET['glory_export_download'])) {
            return;
        }

        // Verificar nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'glory_export_download')) {
            wp_die('Verificación de seguridad fallida.');
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos suficientes.');
        }

        $filename = sanitize_file_name($_GET['glory_export_download']);
        $uploadDir = wp_upload_dir();
        $filepath = $uploadDir['basedir'] . '/glory-exports/' . $filename;

        if (!file_exists($filepath)) {
            wp_die('El archivo no existe.');
        }

        // Forzar descarga
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        readfile($filepath);
        exit;
    }

    /**
     * Maneja la eliminación de archivos
     */
    public function handleDeleteAction(): void
    {
        if (!isset($_POST['glory_export_action']) || $_POST['glory_export_action'] !== 'delete') {
            return;
        }

        // Verificar nonce
        if (!isset($_POST['glory_export_nonce']) || !wp_verify_nonce($_POST['glory_export_nonce'], 'glory_export_delete')) {
            wp_die('Verificación de seguridad fallida.');
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos suficientes.');
        }

        $filename = sanitize_file_name($_POST['filename']);
        $uploadDir = wp_upload_dir();
        $filepath = $uploadDir['basedir'] . '/glory-exports/' . $filename;

        if (file_exists($filepath) && unlink($filepath)) {
            add_settings_error('glory_export', 'delete_success', 'Archivo eliminado exitosamente.', 'success');
        } else {
            add_settings_error('glory_export', 'delete_error', 'Error al eliminar el archivo.', 'error');
        }
    }

    /**
     * Renderiza la página de exportación
     */
    public function renderizarPagina(): void
    {
        $currentUrl = get_site_url();
        $exports = DatabaseExporter::listExports();

        ?>
        <div class="wrap glory-export-wrap">
            <h1>
                <span class="dashicons dashicons-database-export"></span>
                Glory Database Export
            </h1>
            
            <p class="description">
                Exporta la base de datos de WordPress con reemplazo inteligente de URLs. 
                Perfecto para migrar de local a producción o entre diferentes entornos.
            </p>

            <?php settings_errors('glory_export'); ?>

            <div class="glory-export-container">
                <!-- Panel de Exportación -->
                <div class="glory-export-panel">
                    <div class="glory-export-card">
                        <h2><span class="dashicons dashicons-upload"></span> Nueva Exportación</h2>
                        
                        <form method="post" action="" class="glory-export-form">
                            <?php wp_nonce_field('glory_export_database', 'glory_export_nonce'); ?>
                            <input type="hidden" name="glory_export_action" value="export">

                            <div class="glory-export-field-group">
                                <label for="current_url">
                                    <strong>URL Actual:</strong>
                                </label>
                                <input 
                                    type="text" 
                                    id="current_url" 
                                    value="<?php echo esc_url($currentUrl); ?>" 
                                    readonly 
                                    class="regular-text glory-readonly-input"
                                >
                                <p class="description">Esta es la URL actual de tu sitio WordPress.</p>
                            </div>

                            <div class="glory-export-field-group">
                                <label for="new_url">
                                    <strong>URL de Destino:</strong> <span class="required">*</span>
                                </label>
                                <input 
                                    type="url" 
                                    id="new_url" 
                                    name="new_url" 
                                    placeholder="https://ejemplo.com" 
                                    required 
                                    class="regular-text"
                                >
                                <p class="description">
                                    Ingresa la URL donde se instalará esta base de datos. 
                                    Todas las referencias a la URL actual serán reemplazadas automáticamente.
                                </p>
                            </div>

                            <div class="glory-export-field-group">
                                <label>
                                    <input type="checkbox" name="include_drop_tables" value="1">
                                    <strong>Incluir DROP TABLE statements</strong>
                                </label>
                                <p class="description">
                                    Si está marcado, el archivo SQL incluirá comandos para eliminar tablas existentes antes de crearlas.
                                    <strong>Cuidado:</strong> Esto sobrescribirá tablas existentes con el mismo nombre.
                                </p>
                            </div>

                            <div class="glory-export-info-box">
                                <h4><span class="dashicons dashicons-info"></span> El reemplazo inteligente maneja:</h4>
                                <ul>
                                    <li>✓ URLs con y sin <code>https://</code> o <code>http://</code></li>
                                    <li>✓ URLs con y sin trailing slash (<code>/</code>)</li>
                                    <li>✓ Datos serializados de PHP</li>
                                    <li>✓ Datos en formato JSON</li>
                                    <li>✓ URLs en todos los campos de la base de datos</li>
                                </ul>
                            </div>

                            <div class="glory-export-submit">
                                <button type="submit" class="button button-primary button-hero">
                                    <span class="dashicons dashicons-database-export"></span>
                                    Exportar Base de Datos
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Panel de Instrucciones -->
                    <div class="glory-export-card glory-export-instructions">
                        <h3><span class="dashicons dashicons-book"></span> Instrucciones de Importación</h3>
                        <ol>
                            <li>
                                <strong>Crear nueva base de datos:</strong><br>
                                Crea una base de datos vacía en tu servidor de destino (cPanel, phpMyAdmin, etc.)
                            </li>
                            <li>
                                <strong>Importar el archivo SQL:</strong><br>
                                Usa phpMyAdmin, Adminer, o la línea de comandos para importar el archivo descargado
                            </li>
                            <li>
                                <strong>Actualizar wp-config.php:</strong><br>
                                Edita los datos de conexión a la base de datos (DB_NAME, DB_USER, DB_PASSWORD, DB_HOST)
                            </li>
                            <li>
                                <strong>¡Listo!</strong><br>
                                Tu sitio debería funcionar con la nueva URL sin problemas adicionales
                            </li>
                        </ol>

                        <div class="glory-export-warning">
                            <span class="dashicons dashicons-warning"></span>
                            <strong>Importante:</strong> Siempre haz un respaldo de tu base de datos actual antes de importar.
                        </div>
                    </div>
                </div>

                <!-- Lista de Exportaciones -->
                <?php if (!empty($exports)): ?>
                <div class="glory-export-list">
                    <h2><span class="dashicons dashicons-archive"></span> Exportaciones Disponibles</h2>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Archivo</th>
                                <th>Tamaño</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exports as $export): ?>
                            <tr>
                                <td><code><?php echo esc_html($export['filename']); ?></code></td>
                                <td><?php echo esc_html($export['size_formatted']); ?></td>
                                <td><?php echo esc_html($export['date_formatted']); ?></td>
                                <td>
                                    <a 
                                        href="<?php echo esc_url($export['download_url']); ?>" 
                                        class="button button-small"
                                        download
                                    >
                                        <span class="dashicons dashicons-download"></span> Descargar
                                    </a>
                                    
                                    <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este archivo?');">
                                        <?php wp_nonce_field('glory_export_delete', 'glory_export_nonce'); ?>
                                        <input type="hidden" name="glory_export_action" value="delete">
                                        <input type="hidden" name="filename" value="<?php echo esc_attr($export['filename']); ?>">
                                        <button type="submit" class="button button-small button-link-delete">
                                            <span class="dashicons dashicons-trash"></span> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p class="description">
                        <span class="dashicons dashicons-clock"></span>
                        Las exportaciones se eliminan automáticamente después de 7 días.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

