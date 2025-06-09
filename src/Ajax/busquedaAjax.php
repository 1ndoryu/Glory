<?
// Glory/src/Ajax/busquedaAjax.php

use Glory\Services\BusquedaService;
use Glory\Components\BusquedaRenderer;
use Glory\Core\GloryLogger;

/**
 * Maneja la solicitud AJAX para la búsqueda.
 * Orquesta la obtención de datos con BusquedaService y su renderizado con BusquedaRenderer.
 */
function busquedaAjax()
{
    GloryLogger::info('Iniciando busquedaAjax.');

    // Validación inicial de parámetros.
    if (empty($_POST['texto']) || empty($_POST['tipos'])) {
        GloryLogger::error('Parámetros de búsqueda insuficientes.', ['post_data' => $_POST]);
        wp_send_json_info(['message' => 'Parámetros insuficientes para la búsqueda.']);
        return;
    }

    $textoBusqueda = sanitize_text_field($_POST['texto']);
    $tipos = sanitize_text_field($_POST['tipos']);
    $cantidad = !empty($_POST['cantidad']) ? absint($_POST['cantidad']) : 2;

    GloryLogger::info('Parámetros de búsqueda recibidos.', [
        'texto' => $textoBusqueda,
        'tipos' => $tipos,
        'cantidad' => $cantidad
    ]);

    // 1. Lógica de Negocio
    $servicio = new BusquedaService($textoBusqueda);
    $tiposArray = explode(',', $tipos);

    foreach ($tiposArray as $tipo) {
        $tipo = trim($tipo);
        if ($tipo === 'perfiles') {
            $servicio->agregarTipoBusqueda('usuario', ['limite' => $cantidad + 1]);
        } else {
            // Asume que cualquier otro tipo es un post_type.
            $servicio->agregarTipoBusqueda('post', ['post_type' => $tipo, 'limite' => $cantidad + 1]);
        }
    }

    $servicio->ejecutar()->balancear($cantidad);
    $resultados = $servicio->obtenerResultados();

    GloryLogger::info('Resultados obtenidos del servicio.', ['resultados' => $resultados]);

    // 2. Lógica de Presentación
    $html = BusquedaRenderer::renderizarResultados($resultados);

    GloryLogger::info('HTML renderizado.', ['longitud' => strlen($html)]);

    // 3. Respuesta
    wp_send_json_success(['html' => $html]);
}


add_action('wp_ajax_busquedaAjax', 'busquedaAjax');
add_action('wp_ajax_nopriv_busquedaAjax', 'busquedaAjax');
