<?
// Glory/src/Components/BusquedaRenderer.php

namespace Glory\Components;

/**
 * Gestiona la renderización de los resultados de búsqueda.
 *
 * Transforma un array de datos de resultados en una representación HTML.
 * Es agnóstico a la fuente de los datos, simplemente los formatea.
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
        $htmlFinal = '';
        $totalResultados = array_reduce($datos, function ($carry, $items) {
            return $carry + (is_array($items) ? count($items) : 0);
        }, 0);

        if ($totalResultados === 0) {
            return '<div class="resultadoItemNoEncontrado">No se encontraron resultados.</div>';
        }

        foreach ($datos as $grupoItems) {
            if (is_array($grupoItems)) {
                foreach ($grupoItems as $item) {
                    $htmlFinal .= self::renderizarItem($item);
                }
            }
        }

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
        $tipo = !empty($item['tipo']) ? esc_html($item['tipo']) : 'Desconocido';

        $imagenHtml = !empty($item['imagen'])
            ? sprintf(
                '<img class="resultado-imagen" src="%s" alt="%s">',
                esc_url($item['imagen']),
                esc_attr($titulo)
            )
            : '<div class="resultado-imagen placeholder"></div>';

        return sprintf(
            '<a href="%s" class="resultadoEnlace">
                <div class="resultadoItem">
                    %s
                    <div class="resultadoInfo">
                        <h3>%s</h3>
                        <p>%s</p>
                    </div>
                </div>
            </a>',
            $url,
            $imagenHtml,
            $titulo,
            $tipo
        );
    }
}
