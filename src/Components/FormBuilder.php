<?

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
        <div <? if ($id): ?>id="<? echo esc_attr($id) ?>" <? endif; ?>
             class="<? echo esc_attr($clases) ?>" 
             action="<? echo esc_attr($action) ?>" 
             method="<? echo esc_attr($method) ?>" 
             <? echo trim($dataAttributes) ?>>
        <?
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
            <div class="<? echo esc_attr($clasesContenedor) ?>">
                <? if ($label): ?>
                    <label for="<? echo esc_attr($id) ?>"><? echo esc_html($label) ?><? if ($obligatorio): ?><span class="obligatorio">*</span><? endif; ?></label>
                <? endif; ?>
                <input type="text" id="<? echo esc_attr($id) ?>" name="<? echo esc_attr($nombre) ?>" value="<? echo esc_attr($valor) ?>" class="<? echo esc_attr($clasesInput) ?>" <? if ($placeholder): ?>placeholder="<? echo esc_attr($placeholder) ?>" <? endif; ?> <? if ($limite): ?>data-limit="<? echo $limite ?>" <? endif; ?> <? if ($minimo): ?>data-minimo="<? echo $minimo ?>" <? endif; ?> <? if ($obligatorio): ?>required<? endif; ?> <? if ($obligatorio && $alertaObligatorio): ?>data-alerta-obligatorio="<? echo esc_attr($alertaObligatorio) ?>" <? endif; ?> />
            </div>
        <?
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
            <div class="<? echo esc_attr($clasesContenedor) ?>">
                <? if ($label): ?>
                    <label for="<? echo esc_attr($id) ?>"><? echo esc_html($label) ?><? if ($obligatorio): ?><span class="obligatorio">*</span><? endif; ?></label>
                <? endif; ?>
                <textarea id="<? echo esc_attr($id) ?>" name="<? echo esc_attr($nombre) ?>" class="<? echo esc_attr($clasesInput) ?>" rows="<? echo $rows ?>" <? if ($placeholder): ?>placeholder="<? echo esc_attr($placeholder) ?>" <? endif; ?> <? if ($limite): ?>data-limit="<? echo $limite ?>" <? endif; ?> <? if ($minimo): ?>data-minimo="<? echo $minimo ?>" <? endif; ?> <? if ($obligatorio): ?>required<? endif; ?> <? if ($obligatorio && $alertaObligatorio): ?>data-alerta-obligatorio="<? echo esc_attr($alertaObligatorio) ?>" <? endif; ?>><? echo esc_textarea($valor) ?></textarea>
            </div>
        <?
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
            <div class="<? echo esc_attr($clasesContenedor) ?>" <? if ($idPreview): ?>id="<? echo esc_attr($idPreview) ?>" <? endif; ?>><? echo $previewContent ?></div>
            <input type="file" id="<? echo esc_attr($id) ?>" name="<? echo esc_attr($nombre) ?>" style="display:none;" <? if ($accept): ?>accept="<? echo esc_attr($accept) ?>" <? endif; ?> <? if ($limite): ?>data-limit="<? echo $limite ?>" <? endif; ?> <? if ($minimo): ?>data-minimo="<? echo $minimo ?>" <? endif; ?> <? if ($obligatorio): ?>required<? endif; ?> <? if ($obligatorio && $alertaObligatorio): ?>data-alerta-obligatorio="<? echo esc_attr($alertaObligatorio) ?>" <? endif; ?> />
        <?
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
            <div class="<? echo esc_attr($clasesContenedor) ?>">
                <label for="<? echo esc_attr($id) ?>" class="<? echo esc_attr($clasesLabel) ?>" <? if ($tooltip): ?>data-tooltip="<? echo esc_attr($tooltip) ?>" <? endif; ?>>
                    <input type="checkbox" id="<? echo esc_attr($id) ?>" name="<? echo esc_attr($nombre) ?>" value="<? echo esc_attr($valorInput) ?>" <? if ($obligatorio): ?>required<? endif; ?> <? if ($obligatorio && $alertaObligatorio): ?>data-alerta-obligatorio="<? echo esc_attr($alertaObligatorio) ?>" <? endif; ?> <? echo $checked ?>>
                    <span class="checkmark"></span>
                    <? echo $labelContent ?><? if ($label && $obligatorio): ?><span class="obligatorio">*</span><? endif; ?>
                </label>
            </div>
    <?
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
}