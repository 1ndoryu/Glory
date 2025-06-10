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

        $id = !empty($opciones['id']) ? 'id="' . esc_attr($opciones['id']) . '"' : '';
        $action = !empty($opciones['action']) ? 'action="' . esc_attr($opciones['action']) . '"' : 'javascript:void(0);';
        $method = !empty($opciones['method']) ? 'method="' . esc_attr($opciones['method']) . '"' : 'post';
        $clases = 'gloryForm ' . ($opciones['extraClass'] ?? '');
        
        $metaTargetAttr = self::$currentMetaTarget ? 'data-meta-target="' . esc_attr(self::$currentMetaTarget) . '"' : '';
        $objectIdAttr = self::$currentObjectId ? 'data-object-id="' . esc_attr(self::$currentObjectId) . '"' : '';

        return "<div {$id} class=\"{$clases}\" action=\"{$action}\" method=\"{$method}\" {$metaTargetAttr} {$objectIdAttr}>";
    }

    public static function fin(): string
    {
        // Limpiamos el estado estático al finalizar el formulario
        self::$currentMetaTarget = null;
        self::$currentObjectId = null;
        return "</div>";
    }

    private static function obtenerValorMeta(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        
        // El contexto se toma de las propiedades estáticas de la clase
        $metaTarget = self::$currentMetaTarget;
        $objectId = self::$currentObjectId;

        if (empty($nombre) || empty($metaTarget)) {
            return '';
        }

        switch ($metaTarget) {
            case 'user':
                $userId = $objectId ?? get_current_user_id();
                if (!$userId) return '';

                if ($nombre === 'user_login') {
                    $usuario = get_userdata($userId);
                    return $usuario ? $usuario->user_login : '';
                }
                
                return get_user_meta($userId, $nombre, true) ?? '';

            case 'post':
                if (!$objectId) return '';
                return get_post_meta($objectId, $nombre, true) ?? '';
            
            default:
                return '';
        }
    }

    public static function campoTexto(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = !empty($opciones['label']) ? "<label for=\"{$id}\">" . esc_html($opciones['label']) . "</label>" : '';
        
        $valor = $opciones['valor'] ?? self::obtenerValorMeta($opciones);

        $limite = !empty($opciones['limite']) ? 'data-limit="' . intval($opciones['limite']) . '"' : '';
        $placeholder = !empty($opciones['placeholder']) ? 'placeholder="' . esc_attr($opciones['placeholder']) . '"' : '';
        $clasesInput = $opciones['extraClassInput'] ?? '';
        $clasesContenedor = 'formCampo ' . ($opciones['classContainer'] ?? '');

        $html = "
    <div class=\"{$clasesContenedor}\">
      {$label}
      <input type=\"text\" id=\"{$id}\" name=\"{$nombre}\" value=\"" . esc_attr($valor) . "\" {$limite} {$placeholder} class=\"{$clasesInput}\" />
    </div>";

        return $html;
    }

    public static function campoTextarea(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = !empty($opciones['label']) ? "<label for=\"{$id}\">" . esc_html($opciones['label']) . "</label>" : '';

        $valor = $opciones['valor'] ?? self::obtenerValorMeta($opciones);

        $limite = !empty($opciones['limite']) ? 'data-limit="' . intval($opciones['limite']) . '"' : '';
        $rows = !empty($opciones['rows']) ? 'rows="' . intval($opciones['rows']) . '"' : '';
        $placeholder = !empty($opciones['placeholder']) ? 'placeholder="' . esc_attr($opciones['placeholder']) . '"' : '';
        $clasesInput = $opciones['extraClassInput'] ?? '';
        $clasesContenedor = 'formCampo ' . ($opciones['classContainer'] ?? '');

        $html = "
    <div class=\"{$clasesContenedor}\">
      {$label}
      <textarea id=\"{$id}\" name=\"{$nombre}\" {$limite} {$rows} {$placeholder} class=\"{$clasesInput}\">" . esc_textarea($valor) . "</textarea>
    </div>";

        return $html;
    }

    public static function campoArchivo(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $idPreview = !empty($opciones['idPreview']) ? 'id="' . esc_attr($opciones['idPreview']) . '"' : '';
        $textoPreview = $opciones['textoPreview'] ?? 'Seleccionar archivo';
        $previewContent = esc_html($textoPreview);

        $attachmentId = self::obtenerValorMeta($opciones);
        
        if (!empty($attachmentId)) {
            $imagenGuardada = wp_get_attachment_image($attachmentId, 'thumbnail');
            if (!empty($imagenGuardada)) {
                $previewContent = $imagenGuardada;
            }
        }

        $limite = !empty($opciones['limite']) ? 'data-limit="' . intval($opciones['limite']) . '"' : '';
        $accept = !empty($opciones['accept']) ? 'accept="' . esc_attr($opciones['accept']) . '"' : '';
        $clasesContenedor = 'formCampo ' . ($opciones['classContainer'] ?? '');

        $html = "
    <div class=\"{$clasesContenedor}\">
      <div class=\"preview\" {$idPreview}>{$previewContent}</div>
      <input type=\"file\" id=\"{$id}\" name=\"{$nombre}\" {$limite} {$accept} style=\"display:none;\" />
    </div>";

        return $html;
    }

    public static function campoCheckbox(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = $opciones['label'] ?? '';
        $valorInput = $opciones['valorInput'] ?? '1';

        $valorGuardado = self::obtenerValorMeta($opciones);
        $checked = !empty($valorGuardado) ? 'checked' : '';

        $clasesContenedor = 'formCampo ' . ($opciones['classContainer'] ?? '');
        $clasesLabel = $opciones['extraClassLabel'] ?? 'customCheckbox';
        $tooltip = !empty($opciones['tooltip']) ? 'data-tooltip="' . esc_attr($opciones['tooltip']) . '"' : '';

        // El contenido del label puede ser texto o HTML (ej. un icono)
        $labelContent = $opciones['labelIcono'] ?? esc_html($label);

        $html = "
        <div class=\"{$clasesContenedor}\">
            <label for=\"{$id}\" class=\"{$clasesLabel}\" {$tooltip}>
                <input type=\"checkbox\" id=\"{$id}\" name=\"{$nombre}\" value=\"{$valorInput}\" {$checked}>
                <span class=\"checkmark\"></span>
                {$labelContent}
            </label>
        </div>";

        return $html;
    }

    public static function botonEnviar(array $opciones): string
    {
        $accion = $opciones['accion'] ?? '';
        $texto = $opciones['texto'] ?? 'Enviar';
        $clases = 'dataSubir ' . ($opciones['extraClass'] ?? '');

        return "<button class=\"{$clases}\" data-accion=\"{$accion}\">" . esc_html($texto) . "</button>";
    }
}
