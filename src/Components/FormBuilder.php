<?

namespace Glory\Component;

class FormBuilder
{
    private static ?string $currentMetaTarget = null;
    private static ?int $currentObjectId = null;

    public static function inicio(array $opciones = []): string
    {
        self::$currentMetaTarget = $opciones['metaTarget'] ?? null;
        self::$currentObjectId = !empty($opciones['objectId']) ? intval($opciones['objectId']) : null;

        $id = $opciones['id'] ?? '';
        $action = $opciones['action'] ?? 'javascript:void(0);';
        $method = $opciones['method'] ?? 'post';
        $clases = 'gloryForm ' . ($opciones['extraClass'] ?? '');
        $atributosExtra = $opciones['atributos'] ?? [];

        $dataAttributes = '';
        if (self::$currentMetaTarget) {
            $dataAttributes .= 'data-meta-target="' . esc_attr(self::$currentMetaTarget) . '" ';
        }
        if (self::$currentObjectId) {
            $dataAttributes .= 'data-object-id="' . esc_attr(self::$currentObjectId) . '" ';
        }
        foreach ($atributosExtra as $clave => $valor) {
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

    public static function fin(): string
    {
        self::$currentMetaTarget = null;
        self::$currentObjectId = null;
        return "</div>";
    }

    private static function obtenerValorMeta(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $metaTarget = self::$currentMetaTarget;
        $objectId = self::$currentObjectId;

        if (empty($nombre) || empty($metaTarget)) {
            return '';
        }

        switch ($metaTarget) {
            case 'user':
                $userId = $objectId ?? get_current_user_id();
                if (!$userId)
                    return '';

                if ($nombre === 'user_login') {
                    $usuario = get_userdata($userId);
                    return $usuario ? $usuario->user_login : '';
                }

                return get_user_meta($userId, $nombre, true) ?? '';

            case 'post':
                if (!$objectId)
                    return '';
                return get_post_meta($objectId, $nombre, true) ?? '';

            default:
                return '';
        }
    }

    public static function campoTexto(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = $opciones['label'] ?? '';
        $valor = $opciones['valor'] ?? self::obtenerValorMeta($opciones);
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

    public static function campoTextarea(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = $opciones['label'] ?? '';
        $valor = $opciones['valor'] ?? self::obtenerValorMeta($opciones);
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

    public static function campoArchivo(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $idPreview = $opciones['idPreview'] ?? '';
        $textoPreview = $opciones['textoPreview'] ?? 'Seleccionar archivo';
        $previewContent = esc_html($textoPreview);
        $accept = $opciones['accept'] ?? '';
        $limite = !empty($opciones['limite']) ? intval($opciones['limite']) : 0;
        $clasesContenedor = 'formCampo ' . ($opciones['classContainer'] ?? '');
        $obligatorio = $opciones['obligatorio'] ?? false;
        $alertaObligatorio = $opciones['alertaObligatorio'] ?? '';
        $minimo = !empty($opciones['minimo']) ? intval($opciones['minimo']) : 0;
        $attachmentId = self::obtenerValorMeta($opciones);
        if (!empty($attachmentId)) {
            $imagenGuardada = wp_get_attachment_image($attachmentId, 'thumbnail');
            if (!empty($imagenGuardada)) {
                $previewContent = $imagenGuardada;
            }
        }

        ob_start();
        ?>

            <div class="<? echo esc_attr($clasesContenedor) ?>" <? if ($idPreview): ?>id="<? echo esc_attr($idPreview) ?>" <? endif; ?>><? echo $previewContent ?></div>
            <input type="file" id="<? echo esc_attr($id) ?>" name="<? echo esc_attr($nombre) ?>" style="display:none;" <? if ($accept): ?>accept="<? echo esc_attr($accept) ?>" <? endif; ?> <? if ($limite): ?>data-limit="<? echo $limite ?>" <? endif; ?> <? if ($minimo): ?>data-minimo="<? echo $minimo ?>" <? endif; ?> <? if ($obligatorio): ?>required<? endif; ?> <? if ($obligatorio && $alertaObligatorio): ?>data-alerta-obligatorio="<? echo esc_attr($alertaObligatorio) ?>" <? endif; ?> />

        <?
        return ob_get_clean();
    }

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


        $valorGuardado = self::obtenerValorMeta($opciones);
        $checked = !empty($valorGuardado) ? 'checked' : '';

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

    public static function botonEnviar(array $opciones): string
    {
        $accion = $opciones['accion'] ?? '';
        $texto = $opciones['texto'] ?? 'Enviar';
        $clases = 'dataSubir ' . ($opciones['extraClass'] ?? '');

        return "<button class=\"{$clases}\" data-accion=\"{$accion}\">" . esc_html($texto) . "</button>";
    }
}
