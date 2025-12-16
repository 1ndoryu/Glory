<?php

/**
 * Vista: Interfaz de importacion manual de productos Amazon.
 * 
 * Este template renderiza la UI de drag & drop para importar
 * productos desde archivos HTML de Amazon.
 */

if (!defined('ABSPATH')) exit;

use Glory\Plugins\AmazonProduct\i18n\Labels;
?>

<div id="contenedor-importacion-manual" class="importacionManualContenedor">
    <h2><?php echo esc_html(Labels::get('manual_import_title')); ?></h2>
    <p>Arrastra archivos HTML de productos de Amazon o haz clic para seleccionarlos. Puedes importar multiples productos a la vez.</p>

    <!-- Zona de Drag & Drop -->
    <div id="zona-arrastre" class="zonaArrastre">
        <div class="contenidoZonaArrastre">
            <span class="dashicons dashicons-upload" style="font-size: 48px; color: #2271b1; height: auto; width: auto;"></span>
            <p><strong>Arrastra archivos HTML aqui</strong></p>
            <p style="color: #666;">o haz clic para seleccionar</p>
        </div>
        <input
            type="file"
            id="entrada-archivo"
            multiple
            accept=".html,.htm"
            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
    </div>

    <!-- Barra de progreso -->
    <div id="contenedor-progreso" style="display: none;">
        <div class="barraProgreso">
            <div class="barraProgresoRelleno" id="relleno-progreso"></div>
        </div>
        <p id="texto-progreso">Procesando archivos...</p>
    </div>

    <!-- Tabla de productos extraidos -->
    <div id="contenedor-productos" style="display: none;">
        <h3>Productos Detectados</h3>
        <div class="navTablaManual tablenav top">
            <div class="alignleft actions">
                <label>
                    <input type="checkbox" id="descargar-imagenes-global" checked>
                    Descargar imagenes localmente
                </label>
            </div>
            <div class="alineadoDerecha alignright">
                <button type="button" id="importar-seleccionados" class="button button-primary" disabled>
                    Importar Seleccionados (0)
                </button>
                <button type="button" id="importar-todos" class="button button-secondary">
                    Importar Todos
                </button>
                <button type="button" id="limpiar-todo" class="button">
                    Limpiar
                </button>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped" id="tabla-productos-manual">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="seleccionar-todos">
                    </td>
                    <th class="columnaImagen" style="width: 80px;">Imagen</th>
                    <th class="column-title">Producto</th>
                    <th class="column-price" style="width: 100px;">Precio</th>
                    <th class="column-rating" style="width: 80px;">Rating</th>
                    <th class="column-status" style="width: 120px;">Estado</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla-productos">
            </tbody>
        </table>
    </div>

    <!-- Log de importacion -->
    <div id="registro-importacion" style="display: none;">
        <h3>Resultado de Importacion</h3>
        <div id="contenido-registro"></div>
    </div>
</div>

<?php wp_nonce_field('amazon_manual_import_ajax', 'nonce_importacion_manual'); ?>