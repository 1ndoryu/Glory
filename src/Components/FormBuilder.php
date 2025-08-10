<?php
// Glory/src/Components/FormBuilder.php

namespace Glory\Components;

class FormBuilder
{
    /**
     * Inicia el contenedor del formulario.
     * Ya no guarda estado interno. Los atributos data-* deben pasarse en el array de opciones.
     */
    public static function inicio(array $opciones = []): string
    {
        $id = $opciones['id'] ?? '';
        $action = $opciones['action'] ?? 'javascript:void(0);';
        $method = $opciones['method'] ?? 'post';
        $clases = 'gloryForm ' . ($opciones['extraClass'] ?? '');
        $atributos = $opciones['atributos'] ?? [];

        $dataAttributes = '';
        foreach ($atributos as $clave => $valor) {
            $dataAttributes .= esc_attr($clave) . '="' . esc_attr($valor) . '" ';
        }

        ob_start();
?>
        <div <?php if ($id) : ?>id="<?php echo esc_attr($id) ?>" <?php endif; ?> class="<?php echo esc_attr($clases) ?>" action="<?php echo esc_attr($action) ?>" method="<?php echo esc_attr($method) ?>" <?php echo trim($dataAttributes) ?>>
        <?php
        return ob_get_clean();
    }

    /**
     * Cierra el contenedor del formulario.
     */
    public static function fin(): string
    {
        return "</div>";
    }

    /**
     * Genera un campo de texto. El valor debe ser proporcionado.
     */
    public static function campoTexto(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = $opciones['label'] ?? '';
        $valor = $opciones['valor'] ?? ''; // El valor AHORA debe ser pasado explícitamente.
        $clasesContenedor = 'formCampo ' . ($opciones['classContainer'] ?? '');
        $clasesInput = $opciones['extraClassInput'] ?? '';
        $placeholder = $opciones['placeholder'] ?? '';
        $limite = !empty($opciones['limite']) ? intval($opciones['limite']) : 0;
        $minimo = !empty($opciones['minimo']) ? intval($opciones['minimo']) : 0;
        $obligatorio = $opciones['obligatorio'] ?? false;
        $alertaObligatorio = $opciones['alertaObligatorio'] ?? '';

        ob_start();
        ?>
            <div class="<?php echo esc_attr($clasesContenedor) ?>">
                <?php if ($label) : ?>
                    <label for="<?php echo esc_attr($id) ?>"><?php echo esc_html($label) ?><?php if ($obligatorio) : ?><span class="obligatorio">*</span><?php endif; ?></label>
                <?php endif; ?>
                <input type="text" id="<?php echo esc_attr($id) ?>" name="<?php echo esc_attr($nombre) ?>" value="<?php echo esc_attr($valor) ?>" class="<?php echo esc_attr($clasesInput) ?>" <?php if ($placeholder) : ?>placeholder="<?php echo esc_attr($placeholder) ?>" <?php endif; ?> <?php if ($limite) : ?>data-limit="<?php echo $limite ?>" <?php endif; ?> <?php if ($minimo) : ?>data-minimo="<?php echo $minimo ?>" <?php endif; ?> <?php if ($obligatorio) : ?>required<?php endif; ?> <?php if ($obligatorio && $alertaObligatorio) : ?>data-alerta-obligatorio="<?php echo esc_attr($alertaObligatorio) ?>" <?php endif; ?> />
            </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Genera un campo de área de texto. El valor debe ser proporcionado.
     */
    public static function campoTextarea(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = $opciones['label'] ?? '';
        $valor = $opciones['valor'] ?? ''; // El valor AHORA debe ser pasado explícitamente.
        $clasesContenedor = 'formCampo ' . ($opciones['classContainer'] ?? '');
        $clasesInput = $opciones['extraClassInput'] ?? '';
        $placeholder = $opciones['placeholder'] ?? '';
        $limite = !empty($opciones['limite']) ? intval($opciones['limite']) : 0;
        $minimo = !empty($opciones['minimo']) ? intval($opciones['minimo']) : 0;
        $rows = !empty($opciones['rows']) ? intval($opciones['rows']) : 1;
        $obligatorio = $opciones['obligatorio'] ?? false;
        $alertaObligatorio = $opciones['alertaObligatorio'] ?? '';

        ob_start();
        ?>
            <div class="<?php echo esc_attr($clasesContenedor) ?>">
                <?php if ($label) : ?>
                    <label for="<?php echo esc_attr($id) ?>"><?php echo esc_html($label) ?><?php if ($obligatorio) : ?><span class="obligatorio">*</span><?php endif; ?></label>
                <?php endif; ?>
                <textarea id="<?php echo esc_attr($id) ?>" name="<?php echo esc_attr($nombre) ?>" class="<?php echo esc_attr($clasesInput) ?>" rows="<?php echo $rows ?>" <?php if ($placeholder) : ?>placeholder="<?php echo esc_attr($placeholder) ?>" <?php endif; ?> <?php if ($limite) : ?>data-limit="<?php echo $limite ?>" <?php endif; ?> <?php if ($minimo) : ?>data-minimo="<?php echo $minimo ?>" <?php endif; ?> <?php if ($obligatorio) : ?>required<?php endif; ?> <?php if ($obligatorio && $alertaObligatorio) : ?>data-alerta-obligatorio="<?php echo esc_attr($alertaObligatorio) ?>" <?php endif; ?>><?php echo esc_textarea($valor) ?></textarea>
            </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Genera un campo de archivo. El contenido de la vista previa debe ser proporcionado.
     */
    public static function campoArchivo(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $idPreview = $opciones['idPreview'] ?? '';
        $accept = $opciones['accept'] ?? '';
        $limite = !empty($opciones['limite']) ? intval($opciones['limite']) : 0;
        $clasesContenedor = 'formCampo ' . ($opciones['classContainer'] ?? '');
        $obligatorio = $opciones['obligatorio'] ?? false;
        $alertaObligatorio = $opciones['alertaObligatorio'] ?? '';
        $minimo = !empty($opciones['minimo']) ? intval($opciones['minimo']) : 0;

        // El contenido de la vista previa AHORA debe ser pasado explícitamente.
        // El código que llama a este método es responsable de generar el HTML seguro.
        $previewContent = $opciones['previewContent'] ?? esc_html($opciones['textoPreview'] ?? 'Seleccionar archivo');

        ob_start();
        ?>
            <div class="<?php echo esc_attr($clasesContenedor) ?>" <?php if ($idPreview) : ?>id="<?php echo esc_attr($idPreview) ?>" <?php endif; ?>><?php echo $previewContent ?></div>
            <input type="file" id="<?php echo esc_attr($id) ?>" name="<?php echo esc_attr($nombre) ?>" style="display:none;" <?php if ($accept) : ?>accept="<?php echo esc_attr($accept) ?>" <?php endif; ?> <?php if ($limite) : ?>data-limit="<?php echo $limite ?>" <?php endif; ?> <?php if ($minimo) : ?>data-minimo="<?php echo $minimo ?>" <?php endif; ?> <?php if ($obligatorio) : ?>required<?php endif; ?> <?php if ($obligatorio && $alertaObligatorio) : ?>data-alerta-obligatorio="<?php echo esc_attr($alertaObligatorio) ?>" <?php endif; ?> />
        <?php
        return ob_get_clean();
    }

    /**
     * Genera un campo de tipo checkbox. El estado 'checked' debe ser proporcionado.
     */
    public static function campoCheckbox(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = $opciones['label'] ?? '';
        $valorInput = $opciones['valorInput'] ?? '1';
        $clasesContenedor = 'formCampo ' . ($opciones['classContainer'] ?? '');
        $clasesLabel = $opciones['extraClassLabel'] ?? 'customCheckbox';
        $tooltip = $opciones['tooltip'] ?? '';
        $labelContent = $opciones['labelIcono'] ?? esc_html($label);
        $obligatorio = $opciones['obligatorio'] ?? false;
        $alertaObligatorio = $opciones['alertaObligatorio'] ?? '';

        // El estado 'checked' AHORA debe ser pasado explícitamente.
        $checked = !empty($opciones['checked']) ? 'checked' : '';

        ob_start();
        ?>
            <div class="<?php echo esc_attr($clasesContenedor) ?>">
                <label for="<?php echo esc_attr($id) ?>" class="<?php echo esc_attr($clasesLabel) ?>" <?php if ($tooltip) : ?>data-tooltip="<?php echo esc_attr($tooltip) ?>" <?php endif; ?>>
                    <input type="checkbox" id="<?php echo esc_attr($id) ?>" name="<?php echo esc_attr($nombre) ?>" value="<?php echo esc_attr($valorInput) ?>" <?php if ($obligatorio) : ?>required<?php endif; ?> <?php if ($obligatorio && $alertaObligatorio) : ?>data-alerta-obligatorio="<?php echo esc_attr($alertaObligatorio) ?>" <?php endif; ?> <?php echo $checked ?>>
                    <span class="checkmark"></span>
                    <?php echo $labelContent ?><?php if ($label && $obligatorio) : ?><span class="obligatorio">*</span><?php endif; ?>
                </label>
            </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Genera un campo de tipo select (desplegable).
     */
    public static function campoSelect(array $opciones_select): string
    {
        $nombre = $opciones_select['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = $opciones_select['label'] ?? '';
        $valorSeleccionado = $opciones_select['valor'] ?? '';
        $opciones = $opciones_select['opciones'] ?? [];
        $clasesContenedor = 'formCampo ' . ($opciones_select['classContainer'] ?? '');
        $clasesInput = $opciones_select['extraClassInput'] ?? '';
        $obligatorio = $opciones_select['obligatorio'] ?? false;
        $alertaObligatorio = $opciones_select['alertaObligatorio'] ?? '';

        ob_start();
        ?>
            <div class="<?php echo esc_attr($clasesContenedor); ?>">
                <?php if ($label) : ?>
                    <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?><?php if ($obligatorio) : ?><span class="obligatorio">*</span><?php endif; ?></label>
                <?php endif; ?>
                <select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($nombre); ?>" class="<?php echo esc_attr($clasesInput); ?>" <?php if ($obligatorio) : ?>required<?php endif; ?> <?php if ($obligatorio && $alertaObligatorio) : ?>data-alerta-obligatorio="<?php echo esc_attr($alertaObligatorio); ?>" <?php endif; ?>>
                    <?php foreach ($opciones as $valor => $texto) : ?>
                        <option value="<?php echo esc_attr($valor); ?>" <?php selected($valor, $valorSeleccionado); ?>>
                            <?php echo esc_html($texto); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Genera un grupo de campos de tipo radio.
     */
    public static function campoRadio(array $opciones_radio): string
    {
        $nombre = $opciones_radio['nombre'] ?? '';
        $label = $opciones_radio['label'] ?? '';
        $valorSeleccionado = $opciones_radio['valor'] ?? '';
        $opciones = $opciones_radio['opciones'] ?? [];
        $clasesContenedor = 'formCampo ' . ($opciones_radio['classContainer'] ?? '');
        $obligatorio = $opciones_radio['obligatorio'] ?? false;
        $alertaObligatorio = $opciones_radio['alertaObligatorio'] ?? '';

        ob_start();
        ?>
            <div class="<?php echo esc_attr($clasesContenedor); ?>">
                <?php if ($label) : ?>
                    <label><?php echo esc_html($label); ?><?php if ($obligatorio) : ?><span class="obligatorio">*</span><?php endif; ?></label>
                <?php endif; ?>
                <div class="radio-group">
                    <?php foreach ($opciones as $valor => $texto) : ?>
                        <label for="form-<?php echo esc_attr($nombre . '-' . str_replace(' ', '_', $valor)); ?>">
                            <input type="radio" id="form-<?php echo esc_attr($nombre . '-' . str_replace(' ', '_', $valor)); ?>" name="<?php echo esc_attr($nombre); ?>" value="<?php echo esc_attr($valor); ?>" <?php checked($valor, $valorSeleccionado); ?> <?php if ($obligatorio) : ?>required<?php endif; ?> <?php if ($obligatorio && $alertaObligatorio) : ?>data-alerta-obligatorio="<?php echo esc_attr($alertaObligatorio); ?>" <?php endif; ?>>
                            <?php echo esc_html($texto); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Genera un grupo de campos de tipo checkbox.
     */
    public static function campoCheckboxGrupo(array $opciones_grupo): string
    {
        $nombre = $opciones_grupo['nombre'] ?? ''; // e.g., 'tipo_viaje[]'
        $label = $opciones_grupo['label'] ?? '';
        $valoresSeleccionados = (array) ($opciones_grupo['valor'] ?? []);
        $opciones = $opciones_grupo['opciones'] ?? [];
        $clasesContenedor = 'formCampo ' . ($opciones_grupo['classContainer'] ?? '');

        ob_start();
        ?>
            <div class="<?php echo esc_attr($clasesContenedor); ?>">
                <?php if ($label) : ?>
                    <label><?php echo esc_html($label); ?></label>
                <?php endif; ?>
                <div class="checkbox-group">
                    <?php foreach ($opciones as $valor => $texto) : ?>
                        <label for="form-<?php echo esc_attr(str_replace('[]', '', $nombre) . '-' . str_replace(' ', '_', $valor)); ?>">
                            <input type="checkbox" id="form-<?php echo esc_attr(str_replace('[]', '', $nombre) . '-' . str_replace(' ', '_', $valor)); ?>" name="<?php echo esc_attr($nombre); ?>" value="<?php echo esc_attr($valor); ?>" <?php checked(in_array($valor, $valoresSeleccionados)); ?>>
                            <span class="checkbox-visual-wrapper">
                                <?php echo esc_html($texto); ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Genera un campo de tipo número.
     */
    public static function campoNumero(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = $opciones['label'] ?? '';
        $valor = $opciones['valor'] ?? '';
        $clasesContenedor = 'formCampo ' . ($opciones['classContainer'] ?? '');
        $clasesInput = $opciones['extraClassInput'] ?? '';
        $placeholder = $opciones['placeholder'] ?? '';
        $min = isset($opciones['min']) ? 'min="' . esc_attr($opciones['min']) . '"' : '';
        $max = isset($opciones['max']) ? 'max="' . esc_attr($opciones['max']) . '"' : '';
        $step = isset($opciones['step']) ? 'step="' . esc_attr($opciones['step']) . '"' : '';
        $obligatorio = $opciones['obligatorio'] ?? false;
        $alertaObligatorio = $opciones['alertaObligatorio'] ?? '';

        ob_start();
        ?>
            <div class="<?php echo esc_attr($clasesContenedor); ?>">
                <?php if ($label) : ?>
                    <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?><?php if ($obligatorio) : ?><span class="obligatorio">*</span><?php endif; ?></label>
                <?php endif; ?>
                <input type="number" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($nombre); ?>" value="<?php echo esc_attr($valor); ?>" class="<?php echo esc_attr($clasesInput); ?>" <?php if ($placeholder) : ?>placeholder="<?php echo esc_attr($placeholder); ?>" <?php endif; ?> <?php echo $min; ?> <?php echo $max; ?> <?php echo $step; ?> <?php if ($obligatorio) : ?>required<?php endif; ?> <?php if ($obligatorio && $alertaObligatorio) : ?>data-alerta-obligatorio="<?php echo esc_attr($alertaObligatorio); ?>" <?php endif; ?> />
            </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Genera un campo de tipo fecha.
     */
    public static function campoFecha(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = $opciones['label'] ?? '';
        $valor = $opciones['valor'] ?? '';
        $clasesContenedor = 'formCampo ' . ($opciones['classContainer'] ?? '');
        $clasesInput = $opciones['extraClassInput'] ?? '';
        $obligatorio = $opciones['obligatorio'] ?? false;
        $alertaObligatorio = $opciones['alertaObligatorio'] ?? '';

        ob_start();
        ?>
            <div class="<?php echo esc_attr($clasesContenedor); ?>">
                <?php if ($label) : ?>
                    <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?><?php if ($obligatorio) : ?><span class="obligatorio">*</span><?php endif; ?></label>
                <?php endif; ?>
                <input type="date" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($nombre); ?>" value="<?php echo esc_attr($valor); ?>" class="<?php echo esc_attr($clasesInput); ?>" <?php if ($obligatorio) : ?>required<?php endif; ?> <?php if ($obligatorio && $alertaObligatorio) : ?>data-alerta-obligatorio="<?php echo esc_attr($alertaObligatorio); ?>" <?php endif; ?> />
            </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Genera un campo de tipo rango (slider).
     */
    public static function campoRango(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = $opciones['label'] ?? '';
        $valor = $opciones['valor'] ?? '15'; // default
        $clasesContenedor = 'formCampo ' . ($opciones['classContainer'] ?? '');
        $clasesInput = $opciones['extraClassInput'] ?? '';
        $min = $opciones['min'] ?? '1';
        $max = $opciones['max'] ?? '31'; // Para representar 30+
        $step = $opciones['step'] ?? '1';

        ob_start();
        ?>
            <div class="<?php echo esc_attr($clasesContenedor); ?>">
                <?php if ($label) : ?>
                    <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?> <span id="rango-valor-<?php echo esc_attr($id); ?>"><?php echo ($valor == $max) ? '30+' : esc_html($valor); ?></span> noches</label>
                <?php endif; ?>
                <input type="range" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($nombre); ?>" value="<?php echo esc_attr($valor); ?>" class="<?php echo esc_attr($clasesInput); ?>" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>" step="<?php echo esc_attr($step); ?>" oninput="document.getElementById('rango-valor-<?php echo esc_attr($id); ?>').textContent = (this.value == <?php echo esc_attr($max); ?>) ? '30+' : this.value;">
            </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Genera un botón de envío.
     */
    public static function botonEnviar(array $opciones): string
    {
        $accion = $opciones['accion'] ?? '';
        $texto = $opciones['texto'] ?? 'Enviar';
        $clases = 'dataSubir ' . ($opciones['extraClass'] ?? '');

        return "<button class=\"{$clases}\" data-accion=\"{$accion}\">" . esc_html($texto) . "</button>";
    }

    public static function campoImagen(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $label = $opciones['label'] ?? '';
        $valor = $opciones['valor'] ?? ''; // Debería ser un ID de adjunto
        $descripcion = $opciones['descripcion'] ?? '';
        $previewUrl = '';
        $placeholder = 'Haz clic para subir una imagen';

        if (!empty($valor) && is_numeric($valor)) {
            $previewUrl = wp_get_attachment_image_url((int)$valor, 'thumbnail');
        }

        ob_start();
        ?>
            <div class="glory-image-uploader">
                <?php if ($label) : ?>
                    <label><?php echo esc_html($label) ?></label>
                <?php endif; ?>
                <div class="image-preview" data-placeholder="<?php echo esc_attr($placeholder); ?>">
                    <?php if ($previewUrl): ?>
                        <img src="<?php echo esc_url($previewUrl); ?>" alt="Previsualización">
                    <?php else: ?>
                        <span class="image-preview-placeholder"><?php echo esc_html($placeholder); ?></span>
                    <?php endif; ?>
                </div>
                <input type="hidden" class="glory-image-id" name="<?php echo esc_attr($nombre) ?>" value="<?php echo esc_attr($valor) ?>" />
                <div class="actions">
                    <button type="button" class="button glory-upload-image-button">Seleccionar Imagen</button>
                    <button type="button" class="button glory-remove-image-button" style="<?php echo empty($valor) ? 'display:none;' : ''; ?>">Eliminar</button>
                </div>
                <?php if ($descripcion) : ?>
                    <p class="description"><?php echo esc_html($descripcion) ?></p>
                <?php endif; ?>
            </div>
        <?php
        return ob_get_clean();
    }

    public static function campoColor(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $label = $opciones['label'] ?? '';
        $valor = $opciones['valor'] ?? '#000000';
        $descripcion = $opciones['descripcion'] ?? '';

        ob_start();
        ?>
            <div>
                <?php if ($label) : ?>
                    <label for="form-<?php echo esc_attr($nombre); ?>"><?php echo esc_html($label); ?></label>
                <?php endif; ?>
                <input type="text" class="glory-color-picker" name="<?php echo esc_attr($nombre); ?>" value="<?php echo esc_attr($valor); ?>" data-default-color="<?php echo esc_attr($valor); ?>" />
                <?php if ($descripcion) : ?>
                    <p class="description"><?php echo esc_html($descripcion); ?></p>
                <?php endif; ?>
            </div>
    <?php
        return ob_get_clean();
    }
}
