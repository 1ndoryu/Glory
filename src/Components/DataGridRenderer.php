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
        // Acciones masivas dentro del contenedor salvo que se indiquen separadas
        if (empty($this->configuracion['acciones_masivas_separadas'])) {
            $this->renderizarAccionesMasivas();
        }
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

    private function renderizarAccionesMasivas(): void
    {
        if (empty($this->configuracion['seleccionMultiple']) || empty($this->configuracion['accionesMasivas'])) {
            return;
        }
        echo '<div class="gloryDataGridAccionesMasivas">';
        echo '  <label for="gloryGridBulkSelect" class="screen-reader-text">' . esc_html__('Acciones masivas', 'glorytemplate') . '</label>';
        echo '  <select id="gloryGridBulkSelect" class="gloryGridBulkSelect">';
        echo '    <option value="">' . esc_html__('Acciones masivas', 'glorytemplate') . '</option>';
        foreach ($this->configuracion['accionesMasivas'] as $accion) {
            $id = esc_attr($accion['id'] ?? '');
            $label = esc_html($accion['etiqueta'] ?? ucfirst($id));
            $ajax = esc_attr($accion['ajax_action'] ?? '');
            $confirm = esc_attr($accion['confirmacion'] ?? '');
            if ($id === '' || $ajax === '') continue;
            echo '    <option value="' . $id . '" data-ajax-action="' . $ajax . '" data-confirm="' . $confirm . '">' . $label . '</option>';
        }
        echo '  </select>';
        echo '  <button type="button" class="button gloryGridBulkApply">' . esc_html__('Aplicar', 'glorytemplate') . '</button>';
        echo '</div>';
    }

    private function normalizarConfiguracion(array $configuracion): array
    {
        return wp_parse_args($configuracion, [
            'columnas' => [],
            'filtros' => [],
            'paginacion' => true,
            // Si se indica true, los filtros no se renderizarán dentro del contenedor principal
            'filtros_separados' => false,
            // Si se indica true, las acciones masivas no se renderizarán dentro del contenedor principal
            'acciones_masivas_separadas' => false,
            // Array de etiquetas/atributos permitidos para kses. Si es null, se usa wp_kses_post.
            'allowed_html' => null,
            // Selección múltiple y acciones masivas (agnóstico)
            'seleccionMultiple' => false,
            // Cada acción: ['id' => 'eliminar', 'etiqueta' => 'Eliminar', 'ajax_action' => '...', 'confirmacion' => '...']
            'accionesMasivas' => [],
        ]);
    }

    /**
     * Renderiza el bloque de acciones masivas a partir de una configuración (para colocarlo fuera del contenedor principal).
     *
     * @param array $configuracion
     * @return void
     */
    public static function renderAccionesMasivasFromConfig(array $configuracion): void
    {
        $config = wp_parse_args($configuracion, [
            'seleccionMultiple' => false,
            'accionesMasivas' => [],
        ]);

        if (empty($config['seleccionMultiple']) || empty($config['accionesMasivas'])) {
            return;
        }

        echo '<div class="gloryDataGridAccionesMasivas">';
        echo '  <label for="gloryGridBulkSelect" class="screen-reader-text">' . esc_html__('Acciones masivas', 'glorytemplate') . '</label>';
        echo '  <select id="gloryGridBulkSelect" class="gloryGridBulkSelect">';
        echo '    <option value="">' . esc_html__('Acciones masivas', 'glorytemplate') . '</option>';
        foreach ($config['accionesMasivas'] as $accion) {
            $id = esc_attr($accion['id'] ?? '');
            $label = esc_html($accion['etiqueta'] ?? ucfirst($id));
            $ajax = esc_attr($accion['ajax_action'] ?? '');
            $confirm = esc_attr($accion['confirmacion'] ?? '');
            if ($id === '' || $ajax === '') continue;
            echo '    <option value="' . $id . '" data-ajax-action="' . $ajax . '" data-confirm="' . $confirm . '">' . $label . '</option>';
        }
        echo '  </select>';
        echo '  <button type="button" class="button gloryGridBulkApply">' . esc_html__('Aplicar', 'glorytemplate') . '</button>';
        echo '</div>';
    }

    private function renderizarFiltros(): void
    {
        if (empty($this->configuracion['filtros']) || !empty($this->configuracion['filtros_separados'])) {
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
        // Añadir clase noAjax para evitar que gloryAjaxNav intercepte el enlace de limpiar
        echo '<a href="' . esc_url(remove_query_arg(array_keys($this->configuracion['filtros']))) . '" class="button noAjax">Limpiar</a>';

        echo '</form>';
        echo '</div>';
    }

    /**
     * Renderiza los filtros a partir de una configuración (método estático útil para colocarlos fuera del contenedor principal).
     *
     * @param array $configuracion
     * @return void
     */
    public static function renderFiltrosFromConfig(array $configuracion): void
    {
        $config = wp_parse_args($configuracion, ['filtros' => []]);
        if (empty($config['filtros'])) {
            return;
        }

        echo '<div class="gloryDataGridFiltros">';
        echo '<form method="get" action="">';

        foreach ($_GET as $clave => $valor) {
            if (strpos($clave, 'filtro_') !== 0 && !in_array($clave, ['submit', 'action'])) {
                echo '<input type="hidden" name="' . esc_attr($clave) . '" value="' . esc_attr($valor) . '">';
            }
        }

        foreach ($config['filtros'] as $clave => $configuracionFiltro) {
            $valorActual = isset($_GET[$clave]) ? sanitize_text_field($_GET[$clave]) : '';
            $etiqueta = esc_html($configuracionFiltro['etiqueta'] ?? ucfirst($clave));
            echo '<input type="search" name="' . esc_attr($clave) . '" value="' . esc_attr($valorActual) . '" placeholder="' . $etiqueta . '">';
        }

        echo '<button type="submit" class="button">Filtrar</button>';
        // Añadir clase noAjax para evitar que gloryAjaxNav intercepte el enlace de limpiar
        echo '<a href="' . esc_url(remove_query_arg(array_keys($config['filtros']))) . '" class="button noAjax">Limpiar</a>';

        echo '</form>';
        echo '</div>';
    }

    private function renderizarEncabezado(): void
    {
        if (empty($this->configuracion['columnas'])) {
            return;
        }

        // Usar $_REQUEST para soportar tanto GET (navegación normal) como POST (respuestas AJAX)
        $ordenamientoActual = isset($_REQUEST['orderby']) ? sanitize_key((string) $_REQUEST['orderby']) : '';
        $ordenActual = isset($_REQUEST['order']) && strtolower((string) $_REQUEST['order']) === 'desc' ? 'desc' : 'asc';

        echo '<thead><tr>';

        if (!empty($this->configuracion['seleccionMultiple'])) {
            echo '<th class="manage-column column-cb check-column">';
            echo '  <label class="screen-reader-text" for="gloryGridSelectAll">' . esc_html__('Seleccionar todo', 'glorytemplate') . '</label>';
            echo '  <input id="gloryGridSelectAll" type="checkbox" class="gloryGridSelectAll" />';
            echo '</th>';
        }

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
            // Añadimos la clase noAjax para evitar que gloryAjaxNav intercepte estos clics
            echo '<a class="noAjax gloryGridSort" href="' . esc_url($urlOrdenamiento) . '">' . $etiqueta . $indicadorOrden . '</a>';
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
                if (!empty($this->configuracion['seleccionMultiple'])) {
                    $id = esc_attr((string) $this->extraerId($post));
                    echo '<th scope="row" class="check-column"><input type="checkbox" class="gloryGridSelect" value="' . $id . '" /></th>';
                }
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
                if (!empty($this->configuracion['seleccionMultiple'])) {
                    $id = esc_attr((string) $this->extraerId($fila));
                    echo '<th scope="row" class="check-column"><input type="checkbox" class="gloryGridSelect" value="' . $id . '" /></th>';
                }
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

        if (!is_null($this->configuracion['allowed_html']) && is_array($this->configuracion['allowed_html'])) {
            echo '<td>' . wp_kses($valor, $this->configuracion['allowed_html']) . '</td>';
        } else {
            echo '<td>' . wp_kses_post($valor) . '</td>';
        }
    }

    private function renderizarPaginacion(): void
    {
        if ($this->configuracion['paginacion'] && $this->datos instanceof WP_Query && $this->datos->max_num_pages > 1) {
            PaginationRenderer::render($this->datos);
        }
    }

    private function extraerId($item)
    {
        if ($item instanceof WP_Post) {
            return $item->ID;
        }
        if (is_array($item)) {
            if (isset($item['ID'])) return $item['ID'];
            if (isset($item['id'])) return $item['id'];
        }
        return '';
    }
}