<?
namespace Glory\Component;

class FormBuilder
{
    public static function inicio(array $opciones = []): string
    {
        $id = !empty($opciones['id']) ? 'id="' . esc_attr($opciones['id']) . '"' : '';
        $action = !empty($opciones['action']) ? 'action="' . esc_attr($opciones['action']) . '"' : 'javascript:void(0);';
        $method = !empty($opciones['method']) ? 'method="' . esc_attr($opciones['method']) . '"' : 'post';
        $clases = 'gloryForm ' . ($opciones['extraClasses'] ?? '');

        return "<div {$id} class=\"{$clases}\" action=\"{$action}\" method=\"{$method}\">";
    }

    public static function fin(): string
    {
        return "</div>";
    }

    public static function campoTexto(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = !empty($opciones['label']) ? "<label for=\"{$id}\">" . esc_html($opciones['label']) . "</label>" : '';
        $valor = $opciones['valor'] ?? '';
        $limite = !empty($opciones['limite']) ? 'data-limit="' . intval($opciones['limite']) . '"' : '';
        $placeholder = !empty($opciones['placeholder']) ? 'placeholder="' . esc_attr($opciones['placeholder']) . '"' : '';
        $clasesInput = $opciones['extraClassesInput'] ?? '';
        $clasesContenedor = 'formCampo ' . ($opciones['extraClassesContenedor'] ?? '');

        $html = "
        <div class=\"{$clasesContenedor}\">
            {$label}
            <input type=\"text\" id=\"{$id}\" name=\"{$nombre}\" value=\"{$valor}\" {$limite} {$placeholder} class=\"{$clasesInput}\" />
        </div>";

        return $html;
    }

    public static function campoTextarea(array $opciones): string
    {
        $nombre = $opciones['nombre'] ?? '';
        $id = 'form-' . $nombre;
        $label = !empty($opciones['label']) ? "<label for=\"{$id}\">" . esc_html($opciones['label']) . "</label>" : '';
        $valor = $opciones['valor'] ?? '';
        $limite = !empty($opciones['limite']) ? 'data-limit="' . intval($opciones['limite']) . '"' : '';
        $rows = !empty($opciones['rows']) ? 'rows="' . intval($opciones['rows']) . '"' : '';
        $placeholder = !empty($opciones['placeholder']) ? 'placeholder="' . esc_attr($opciones['placeholder']) . '"' : '';
        $clasesInput = $opciones['extraClassesInput'] ?? '';
        $clasesContenedor = 'formCampo ' . ($opciones['extraClassesContenedor'] ?? '');
        
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
        $limite = !empty($opciones['limite']) ? 'data-limit="' . intval($opciones['limite']) . '"' : '';
        $accept = !empty($opciones['accept']) ? 'accept="' . esc_attr($opciones['accept']) . '"' : '';
        $clasesContenedor = 'formCampo ' . ($opciones['extraClassesContenedor'] ?? '');

        $html = "
        <div class=\"{$clasesContenedor}\">
            <div class=\"preview\" {$idPreview}>" . esc_html($textoPreview) . "</div>
            <input type=\"file\" id=\"{$id}\" name=\"{$nombre}\" {$limite} {$accept} style=\"display:none;\" />
        </div>";

        return $html;
    }

    public static function botonEnviar(array $opciones): string
    {
        $accion = $opciones['accion'] ?? '';
        $texto = $opciones['texto'] ?? 'Enviar';
        $clases = 'dataSubir ' . ($opciones['extraClasses'] ?? '');
        
        return "<button class=\"{$clases}\" data-accion=\"{$accion}\">" . esc_html($texto) . "</button>";
    }
}