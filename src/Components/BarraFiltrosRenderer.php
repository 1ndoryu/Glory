<?php

namespace Glory\Components;

class BarraFiltrosRenderer
{
    /**
     * Renderiza una barra de filtros genérica a partir de una configuración de campos.
     * Campos soportados (por ahora): search, text, date, select.
     *
     * Ejemplo de $campos:
     * [
     *   ['tipo' => 'search', 'name' => 's', 'label' => 'Cliente', 'placeholder' => 'Buscar…'],
     *   ['tipo' => 'date', 'name' => 'fecha_desde', 'label' => 'Desde'],
     *   ['tipo' => 'select', 'name' => 'filtro_servicio', 'label' => 'Servicio', 'opciones' => ['' => 'Todos', 1 => 'Corte']],
     * ]
     *
     * Opciones soportadas ($opciones):
     * - container_class: clases del contenedor (por defecto: 'glory-analytics-filters postbox')
     * - form_class: clases extra del formulario
     * - layout_row_class: clases del contenedor de fila (por defecto: 'form-row')
     * - actions_class: clases del contenedor de acciones (por defecto: 'form-actions')
     * - submit_text: texto del botón submit (por defecto: 'Aplicar Filtros')
     * - clear_text: texto del botón limpiar (por defecto: 'Limpiar')
     * - limpiar_url: URL para limpiar filtros (por defecto: admin_url('admin.php?page=' . $_REQUEST['page']))
     * - preservar_keys: array de claves GET a preservar como inputs ocultos (además de 'page')
     */
    public static function render(array $campos, array $opciones = []): void
    {
        $containerClass  = $opciones['container_class'] ?? 'glory-analytics-filters postbox';
        $formClass       = $opciones['form_class'] ?? '';
        $layoutRowClass  = $opciones['layout_row_class'] ?? 'form-row';
        $actionsClass    = $opciones['actions_class'] ?? 'form-actions';
        $submitText      = $opciones['submit_text'] ?? esc_html__('Aplicar Filtros', 'glorytemplate');
        $clearText       = $opciones['clear_text'] ?? esc_html__('Limpiar', 'glorytemplate');
        $preservarKeys   = is_array($opciones['preservar_keys'] ?? null) ? $opciones['preservar_keys'] : [];
        $paginaActualKey = isset($_REQUEST['page']) ? sanitize_text_field((string) $_REQUEST['page']) : '';
        $limpiarUrl      = $opciones['limpiar_url'] ?? ($paginaActualKey !== '' ? admin_url('admin.php?page=' . $paginaActualKey) : '');

        echo '<div class="' . esc_attr($containerClass) . '">';
        echo '  <h2 class="hndle"><span>' . esc_html__('Filtros', 'glorytemplate') . '</span></h2>';
        echo '  <div class="inside">';
        echo '    <form method="GET" action="" class="' . esc_attr($formClass) . '">';

        if ($paginaActualKey !== '') {
            echo '      <input type="hidden" name="page" value="' . esc_attr($paginaActualKey) . '">';
        }
        foreach ($preservarKeys as $k) {
            if (isset($_GET[$k])) {
                $val = is_array($_GET[$k]) ? '' : (string) $_GET[$k];
                echo '      <input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr(sanitize_text_field($val)) . '">';
            }
        }

        echo '      <div class="' . esc_attr($layoutRowClass) . '">';
        foreach ($campos as $campo) {
            $tipo        = $campo['tipo'] ?? 'text';
            $name        = $campo['name'] ?? '';
            if ($name === '') { continue; }
            $label       = $campo['label'] ?? '';
            $placeholder = $campo['placeholder'] ?? '';
            $valorActual = isset($_GET[$name]) ? (string) $_GET[$name] : '';

            echo '        <p class="form-field">';
            if ($label !== '') {
                echo '          <label for="' . esc_attr($name) . '">' . esc_html($label) . '</label>';
            }

            if ($tipo === 'select') {
                $opcionesSelect = $campo['opciones'] ?? [];
                echo '          <select id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">';
                foreach ($opcionesSelect as $valor => $texto) {
                    $selected = ((string) $valor === (string) $valorActual) ? ' selected' : '';
                    echo '            <option value="' . esc_attr((string) $valor) . '"' . $selected . '>' . esc_html((string) $texto) . '</option>';
                }
                echo '          </select>';
            } else {
                $typeAttr = in_array($tipo, ['search', 'text', 'date'], true) ? $tipo : 'text';
                echo '          <input type="' . esc_attr($typeAttr) . '" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($valorActual) . '" placeholder="' . esc_attr($placeholder) . '">';
            }
            echo '        </p>';
        }
        echo '      </div>';

        echo '      <div class="' . esc_attr($actionsClass) . '">';
        echo '        <button type="submit" class="button button-primary">' . esc_html($submitText) . '</button>';
        if ($limpiarUrl !== '') {
            echo '        <a href="' . esc_url($limpiarUrl) . '" class="button">' . esc_html($clearText) . '</a>';
        }
        echo '      </div>';

        echo '    </form>';
        echo '  </div>';
        echo '</div>';
    }
}


