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
     * - ajax_action: si se define, el formulario se marca para envío AJAX (data-glory-filters="ajax") y usa esta acción
     * - target_selector: selector CSS opcional para ubicar el contenedor a reemplazar con el HTML de respuesta
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

        $ajaxAction = isset($opciones['ajax_action']) ? sanitize_key((string) $opciones['ajax_action']) : '';
        $targetSelector = isset($opciones['target_selector']) ? (string) $opciones['target_selector'] : '';
        $scope = isset($opciones['scope']) ? sanitize_key((string) $opciones['scope']) : '';

        $method = $ajaxAction ? 'POST' : 'GET';
        $extraAttrs = '';
        if ($ajaxAction) {
            $extraAttrs .= ' data-glory-filters="ajax" data-ajax-action="' . esc_attr($ajaxAction) . '"';
            if ($targetSelector !== '') {
                $extraAttrs .= ' data-target="' . esc_attr($targetSelector) . '"';
            }
        }
        if ($scope !== '') {
            $extraAttrs .= ' data-glory-scope="' . esc_attr($scope) . '"';
        }

        echo '<div class="' . esc_attr($containerClass) . '">';
        echo '  <div class="inside">';
        echo '    <form method="' . esc_attr($method) . '" action="" class="' . esc_attr($formClass) . '"' . $extraAttrs . '>';

        if ($paginaActualKey !== '') {
            echo '      <input type="hidden" name="page" value="' . esc_attr($paginaActualKey) . '">';
        }
        foreach ($preservarKeys as $k) {
            if (isset($_GET[$k]) || isset($_POST[$k])) {
                $valFuente = isset($_POST[$k]) ? $_POST[$k] : $_GET[$k];
                $val = is_array($valFuente) ? '' : (string) $valFuente;
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
            $valorActual = isset($_POST[$name]) ? (string) $_POST[$name] : (isset($_GET[$name]) ? (string) $_GET[$name] : '');

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
            } elseif ($tipo === 'date_range') {
                // date_range requiere: from_name y to_name; usa un input visible y 2 hidden reales
                $fromName = $campo['from_name'] ?? '';
                $toName   = $campo['to_name'] ?? '';
                $desdeVal = isset($_REQUEST[$fromName]) ? (string) $_REQUEST[$fromName] : '';
                $hastaVal = isset($_REQUEST[$toName]) ? (string) $_REQUEST[$toName] : '';
                $display  = ($desdeVal || $hastaVal) ? trim($desdeVal . ' — ' . $hastaVal) : '';
                $idVis    = 'dr_' . md5($fromName . '|' . $toName);
                echo '          <input type="hidden" name="' . esc_attr($fromName) . '" value="' . esc_attr($desdeVal) . '">';
                echo '          <input type="hidden" name="' . esc_attr($toName) . '" value="' . esc_attr($hastaVal) . '">';
                echo '          <input type="text" readonly class="gloryDateRangeInput" id="' . esc_attr($idVis) . '"'
                    . ' data-from-name="' . esc_attr($fromName) . '" data-to-name="' . esc_attr($toName) . '"'
                    . ' placeholder="' . esc_attr($placeholder ?: 'Selecciona rango') . '" value="' . esc_attr($display) . '">';
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


