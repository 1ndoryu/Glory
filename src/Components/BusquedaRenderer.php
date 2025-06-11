<?php
namespace Glory\Components;

use Glory\Core\GloryLogger;

/**
 * Gestiona la renderización de los resultados de búsqueda.
 *
 * Transforma un array de datos de resultados en una representación HTML.
 * Es agnóstico a la fuente de los datos, simplemente los formatea.
 * @author @wandorius
 * // @tarea Jules: Considerar la implementación de un sistema de plantillas simple (ej. get_template_part)
 * // o filtros de WordPress para permitir una personalización más sencilla del HTML de los resultados de búsqueda.
 */
class BusquedaRenderer
{
    /**
     * Renderiza el HTML para un conjunto completo de resultados de búsqueda.
     *
     * @param array $datos Los datos de resultados, generalmente agrupados por tipo.
     * @return string El bloque de HTML final para mostrar.
     */
    public static function renderizarResultados(array $datos): string
    {
        GloryLogger::info('Iniciando renderizado de resultados.', ['datos_recibidos' => $datos]);
        $htmlFinal = '';
        $totalResultados = array_reduce($datos, function ($carry, $items) {
            return $carry + (is_array($items) ? count($items) : 0);
        }, 0);

        if ($totalResultados === 0) {
            GloryLogger::info('No se encontraron resultados para renderizar.');
            return '<div class="resultadoItemNoEncontrado">No se encontraron resultados.</div>';
        }

        GloryLogger::info("Renderizando un total de {$totalResultados} resultados.");

        foreach ($datos as $grupo => $grupoItems) {
            if (is_array($grupoItems)) {
                GloryLogger::info("Renderizando grupo '{$grupo}'.", ['numero_items' => count($grupoItems)]);
                foreach ($grupoItems as $item) {
                    $htmlFinal .= self::renderizarItem($item);
                }
            }
        }

        GloryLogger::info('Renderizado de HTML completado.');
        return $htmlFinal;
    }

    /**
     * Renderiza el HTML para un único item de resultado.
     *
     * @param array $item Los datos del item a renderizar.
     * @return string El HTML para un único resultado.
     */
    private static function renderizarItem(array $item): string
    {
        $url = !empty($item['url']) ? esc_url($item['url']) : '#';
        $titulo = !empty($item['titulo']) ? esc_html($item['titulo']) : 'Sin título';
        $tipo = !empty($item['tipo']) ? $item['tipo'] : 'Desconocido';

        $imagenHtml = !empty($item['imagen'])
            ? sprintf(
                '<img class="resultadoImagen" src="%s" alt="%s">',
                esc_url($item['imagen']),
                esc_attr($titulo)
            )
            : '<div class="resultadoImagen placeholder"></div>';

        $claseTipo = sanitize_title($tipo);

        return sprintf(
            '<a href="%s" class="resultadoEnlace">
				<div class="resultadoItem %s">
					%s
					<div class="resultadoInfo">
						<h3>%s</h3>
						<p>%s</p>
					</div>
				</div>
			</a>',
            $url,
            esc_attr($claseTipo),
            $imagenHtml,
            $titulo,
            esc_html($tipo)
        );
    }
}
