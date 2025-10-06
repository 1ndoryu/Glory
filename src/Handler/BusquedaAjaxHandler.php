<?php

namespace Glory\Handler;

use Glory\Services\BusquedaService;
use Glory\Components\BusquedaRenderer;
use Glory\Core\GloryLogger;

/**
 * Maneja la solicitud AJAX para la búsqueda.
 * Orquesta la obtención de datos con BusquedaService y su renderizado con BusquedaRenderer.
 */
class BusquedaAjaxHandler
{
    /**
     * Registra los hooks de AJAX para la búsqueda.
     */
    public function __construct()
    {
        add_action('wp_ajax_busquedaAjax', [$this, 'handleRequest']);
        add_action('wp_ajax_nopriv_busquedaAjax', [$this, 'handleRequest']);
    }

    /**
     * Procesa la petición de búsqueda.
     */
    public function handleRequest(): void
    {
        GloryLogger::info('Iniciando busquedaAjax.');

        if (empty($_POST['texto']) || empty($_POST['tipos'])) {
            GloryLogger::error('Parámetros de búsqueda insuficientes.', ['post_data' => $_POST]);
            wp_send_json_error(['message' => 'Parámetros insuficientes para la búsqueda.']);
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

        $servicio = new BusquedaService($textoBusqueda);
        $tiposArray = explode(',', $tipos);

        foreach ($tiposArray as $tipo) {
            $tipo = trim($tipo);
            if ($tipo === 'perfiles') {
                $servicio->agregarTipoBusqueda('usuario', ['limite' => $cantidad + 1]);
            } else {
                $servicio->agregarTipoBusqueda('post', ['post_type' => $tipo, 'limite' => $cantidad + 1]);
            }
        }

        $resultados = $servicio->ejecutar()->balancear($cantidad)->obtenerResultados();
        GloryLogger::info('Resultados obtenidos del servicio.', ['resultados' => $resultados]);

        $html = BusquedaRenderer::renderizarResultados($resultados);
        GloryLogger::info('HTML renderizado.', ['longitud' => strlen($html)]);

        wp_send_json_success(['html' => $html]);
    }
}