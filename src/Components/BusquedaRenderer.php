<?php
namespace Glory\Components;

use Glory\Core\GloryLogger;

/**
 * Gestiona la renderización de los resultados de búsqueda.
 *
 * Transforma un array de datos de resultados en una representación HTML.
 * Es agnóstico a la fuente de los datos, simplemente los formatea.
 * @author @wandorius
 * // @tarea Jules: MEJORA FUTURA - Considerar la implementación de un sistema de plantillas (ej. get_template_part o un sistema de micro-plantillas basado en closures/filtros) para permitir una personalización más sencilla y desacoplada del HTML de los resultados de búsqueda. Esta mejora aumentaría significativamente la flexibilidad del componente.
 * @tarea Jules: Revisión de seguridad (escapado de HTML) y actualización de comentarios/tareas pendientes.
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
        // Asegurar el escapado correcto de todas las variables para prevenir XSS.
        $url = !empty($item['url']) ? esc_url($item['url']) : '#';
        $titulo = !empty($item['titulo']) ? esc_html($item['titulo']) : 'Sin título';
        $tipo = !empty($item['tipo']) ? esc_html($item['tipo']) : 'Desconocido'; // Escapar también el tipo por si se muestra directamente.
        $claseTipo = sanitize_title(!empty($item['tipo']) ? $item['tipo'] : 'desconocido'); // Usar el tipo original para la clase, luego sanitizar.

        $imagenHtml = !empty($item['imagen'])
            ? sprintf(
                '<img class="resultadoImagen" src="%s" alt="%s">',
                esc_url($item['imagen']), // URL de imagen ya escapada.
                esc_attr($titulo) // Título ya escapado, usado como alt.
            )
            : '<div class="resultadoImagen placeholder"></div>'; // Placeholder si no hay imagen.

        // El HTML se genera usando sprintf para mayor claridad.
        // Todas las variables dinámicas ($url, $claseTipo, $titulo, $tipo) ya están escapadas.
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
            $url, // Ya escapada
            esc_attr($claseTipo), // Sanitizada y luego escapada como atributo de clase
            $imagenHtml, // Contiene HTML seguro (img con URL y alt escapados, o div placeholder)
            $titulo, // Ya escapada
            $tipo    // Ya escapada
        );
    }
}
