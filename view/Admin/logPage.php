<?php
# Glory/Admin/logsPage.php
# Estilos movidos a Glory\assets\css\logsPage.css

/**
 * Página de administración para visualizar los logs del Glory Framework.
 * Esta página permite filtrar, buscar y ver los logs generados por el framework.
 *
 * @package Glory\Admin
 */

/**
 * Genera y devuelve el contenido HTML para la página de visualización de logs.
 *
 * Esta función configura los filtros disponibles (clases, métodos/funciones)
 * y estructura el HTML para que los scripts del lado del cliente puedan cargar y mostrar los logs.
 *
 * @return string El contenido HTML completo de la página de logs.
 */
function logPage(): string
{
    // YA NO SE ASEGURA JQUERY AQUÍ

    ob_start();

    $unique_classes = [];
    $unique_methods = [];

    if (class_exists('Glory\GloryLogger')) { // Chequea si la clase existe
        // Comentario por Jules: La función get_unique_glory_log_classes_and_methods()
        // es crucial para poblar los filtros. Es necesario confirmar su existencia y
        // correcto funcionamiento en una revisión posterior si esta página no carga los filtros como se espera.
        if (function_exists('get_unique_glory_log_classes_and_methods')) {
            list($unique_classes, $unique_methods) = get_unique_glory_log_classes_and_methods();
        }
    } else {
        echo "<p style='color:red;'>Error: La clase GloryLogger no se encontró. No se pueden popular los filtros.</p>";
    }

?>

    <div class="glory-logger-viewer">

        <div class="glory-filters">
            <div class="filter-group">
                <label for="filter-classes">Clase(s):</label>
                <select id="filter-classes" multiple="multiple" style="width:100%; min-height: 80px;">
                    <?php foreach ($unique_classes as $class_name) : ?>
                        <option value="<?php echo esc_attr($class_name); ?>"><?php echo esc_html($class_name); ?></option>
                    <?php endforeach; ?>
                </select>

            </div>
            <div class="filter-group">
                <label for="filter-methods">Función/Método(s):</label>
                <select id="filter-methods" multiple="multiple" style="width:100%; min-height: 80px;">
                    <?php foreach ($unique_methods as $method_name) : ?>
                        <option value="<?php echo esc_attr($method_name); ?>"><?php echo esc_html($method_name); ?></option>
                    <?php endforeach; ?>
                </select>

            </div>
            <div class="filter-group">
                <label for="filter-level">Nivel:</label>
                <select id="filter-level">
                    <option value="all">Todos</option>
                    <option value="info">INFO</option>
                    <option value="error">ERROR</option>
                </select>
            </div>
            <div class="filter-group" style="grid-column: span 1; display: flex; gap: 10px; align-items: flex-end;">
                <!-- Espacio vacío para alinear botones o añadir otro filtro -->
            </div>

        </div>
        <div class="botones-filter">
            <button id="apply-filters">Aplicar Filtros</button>
            <button id="glory-clear-filters">Limpiar Filtros</button>
        </div>

        <div id="glory-logs-container">
            <div class="glory-loader"></div>
            <!-- Los logs se insertarán aquí -->
        </div>
        <button id="glory-load-more" style="display:none;">Cargar Más</button>
    </div>

    <?php // EL SCRIPT INLINE FUE ELIMINADO DE ESTA UBICACIÓN
    ?>
    <!-- Estilos CSS migrados a Assets/css/logsPage.css - Jules -->
<?php
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}
?>