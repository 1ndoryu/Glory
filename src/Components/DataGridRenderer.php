<?php

namespace Glory\Components;

use WP_Query;
use Glory\Components\PaginationRenderer;
use WP_Post;

class DataGridRenderer
{
    private $datos;
    private array $configuracion;

    public function __construct($datos, array $configuracion)
    {
        $this->datos = $datos;
        $this->configuracion = $this->normalizarConfiguracion($configuracion);
    }

    public static function render($datos, array $configuracion): void
    {
        $instancia = new self($datos, $configuracion);
        $instancia->renderizarTabla();
    }

    public function renderizarTabla(): void
    {
        echo '<div class="gloryDataGridContenedor">';
        $this->renderizarFiltros();
        echo '<div class="gloryDataGridTablaScroll">';
        echo '<table class="gloryDataGridTabla wp-list-table widefat fixed striped">';
        $this->renderizarEncabezado();
        $this->renderizarCuerpo();
        echo '</table>';
        echo '</div>';
        $this->renderizarPaginacion();
        echo '</div>';
    }

    private function normalizarConfiguracion(array $configuracion): array
    {
        return wp_parse_args($configuracion, [
            'columnas' => [],
            'filtros' => [],
            'paginacion' => true,
        ]);
    }

    private function renderizarFiltros(): void
    {
        if (empty($this->configuracion['filtros'])) {
            return;
        }

        echo '<div class="gloryDataGridFiltros">';
        echo '<form method="get" action="">';

        foreach ($_GET as $clave => $valor) {
            if (strpos($clave, 'filtro_') !== 0 && !in_array($clave, ['submit', 'action'])) {
                echo '<input type="hidden" name="' . esc_attr($clave) . '" value="' . esc_attr($valor) . '">';
            }
        }

        foreach ($this->configuracion['filtros'] as $clave => $configuracionFiltro) {
            $valorActual = isset($_GET[$clave]) ? sanitize_text_field($_GET[$clave]) : '';
            $etiqueta = esc_html($configuracionFiltro['etiqueta'] ?? ucfirst($clave));
            echo '<input type="search" name="' . esc_attr($clave) . '" value="' . esc_attr($valorActual) . '" placeholder="' . $etiqueta . '">';
        }

        echo '<button type="submit" class="button">Filtrar</button>';
        echo '<a href="' . esc_url(remove_query_arg(array_keys($this->configuracion['filtros']))) . '" class="button">Limpiar</a>';

        echo '</form>';
        echo '</div>';
    }

    private function renderizarEncabezado(): void
    {
        if (empty($this->configuracion['columnas'])) {
            return;
        }

        $ordenamientoActual = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : '';
        $ordenActual = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'desc' : 'asc';

        echo '<thead><tr>';

        foreach ($this->configuracion['columnas'] as $columna) {
            $etiqueta = esc_html($columna['etiqueta'] ?? '');
            $esOrdenable = isset($columna['ordenable']) && $columna['ordenable'] === true;
            $claveOrdenamiento = $columna['clave'] ?? null;

            if (!$esOrdenable || !$claveOrdenamiento) {
                echo "<th>{$etiqueta}</th>";
                continue;
            }

            $esColumnaActual = ($ordenamientoActual === $claveOrdenamiento);
            $proximoOrden = ($esColumnaActual && $ordenActual === 'asc') ? 'desc' : 'asc';
            $urlOrdenamiento = add_query_arg([
                'orderby' => $claveOrdenamiento,
                'order' => $proximoOrden,
            ]);

            $clasesTh = ['columnaOrdenable'];
            $indicadorOrden = '';
            if ($esColumnaActual) {
                $clasesTh[] = 'ordenando';
                $clasesTh[] = 'orden-' . $ordenActual;
                $indicadorOrden = ($ordenActual === 'asc') ? ' <span class="indicadorOrden">&uarr;</span>' : ' <span class="indicadorOrden">&darr;</span>';
            }

            echo '<th class="' . esc_attr(implode(' ', $clasesTh)) . '">';
            echo '<a href="' . esc_url($urlOrdenamiento) . '">' . $etiqueta . $indicadorOrden . '</a>';
            echo '</th>';
        }

        echo '</tr></thead>';
    }

    private function renderizarCuerpo(): void
    {
        echo '<tbody>';

        $tieneDatos = false;

        if ($this->datos instanceof WP_Query && $this->datos->have_posts()) {
            $tieneDatos = true;
            while ($this->datos->have_posts()) {
                $this->datos->the_post();
                global $post;
                echo '<tr>';
                foreach ($this->configuracion['columnas'] as $columna) {
                    $this->renderizarCelda($post, $columna);
                }
                echo '</tr>';
            }
            wp_reset_postdata();
        } elseif (is_array($this->datos) && !empty($this->datos)) {
            $tieneDatos = true;
            foreach ($this->datos as $fila) {
                echo '<tr>';
                foreach ($this->configuracion['columnas'] as $columna) {
                    $this->renderizarCelda($fila, $columna);
                }
                echo '</tr>';
            }
        }

        if (!$tieneDatos) {
            $numeroColumnas = count($this->configuracion['columnas']);
            echo '<tr>';
            echo '<td colspan="' . esc_attr($numeroColumnas > 0 ? $numeroColumnas : 1) . '">No se encontraron resultados.</td>';
            echo '</tr>';
        }

        echo '</tbody>';
    }

    private function renderizarCelda($item, array $columna): void
    {
        $valor = '';
        $clave = $columna['clave'] ?? null;
        $funcionCallback = $columna['callback'] ?? null;

        if (is_callable($funcionCallback)) {
            $valor = call_user_func($funcionCallback, $item);
        } elseif ($clave) {
            if ($item instanceof WP_Post) {
                if (property_exists($item, $clave)) {
                    $valor = $item->$clave;
                } else {
                    $valor = get_post_meta($item->ID, $clave, true);
                }
            } elseif (is_array($item) && isset($item[$clave])) {
                $valor = $item[$clave];
            }
        }

        echo '<td>' . wp_kses_post($valor) . '</td>';
    }

    private function renderizarPaginacion(): void
    {
        if ($this->configuracion['paginacion'] && $this->datos instanceof WP_Query && $this->datos->max_num_pages > 1) {
            PaginationRenderer::render($this->datos);
        }
    }
}