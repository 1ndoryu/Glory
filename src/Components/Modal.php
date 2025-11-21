<?php
/**
 * Componente Modal
 *
 * Proporciona métodos estáticos para renderizar estructuras HTML de ventanas modales,
 * incluyendo modales simples y modales que contienen formularios generados dinámicamente.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

/**
 * Clase Modal.
 *
 * Genera el markup HTML para modales accesibles y personalizables.
 */
class Modal
{
    /**
     * Renderiza el HTML base de un modal.
     *
     * @param string $id            ID único para el elemento modal.
     * @param string $titulo        Título visible del modal.
     * @param string $contenidoHtml Contenido HTML interno del modal.
     * @param array  $atributos     Atributos HTML adicionales (data-*, etc.).
     * @return string HTML del modal.
     */
    public static function render(string $id, string $titulo = '', string $contenidoHtml = '', array $atributos = []): string
    {
        $attrs = '';
        foreach ($atributos as $clave => $valor) {
            $attrs .= esc_attr($clave) . '="' . esc_attr((string) $valor) . '" ';
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr($id); ?>" class="modalOverlay modal" role="dialog" aria-modal="true" style="display:none;" data-close-on-overlay="0" <?php echo trim($attrs); ?>>
            <div class="modalDialog">
                <div class="modalContenido">
                    <?php if ($titulo !== '') : ?>
                        <h2><?php echo esc_html($titulo); ?></h2>
                    <?php endif; ?>
                    <?php echo $contenidoHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Renderiza un modal que contiene un formulario generado.
     *
     * Utiliza `FormBuilder` para envolver el contenido generado por el renderer
     * en etiquetas de formulario adecuadas.
     *
     * @param string   $id                 ID único del modal.
     * @param string   $titulo             Título del modal.
     * @param callable $formRenderer       Función que genera el contenido del formulario.
     * @param array    $opcionesFormulario Opciones para `FormBuilder::inicio()`.
     * @param array    $atributos          Atributos extra para el contenedor del modal.
     * @return string HTML completo del modal con formulario.
     */
    public static function renderConFormulario(string $id, string $titulo, callable $formRenderer, array $opcionesFormulario = [], array $atributos = []): string
    {
        $contenido = '';
        if (is_callable($formRenderer)) {
            $contenido .= FormBuilder::inicio($opcionesFormulario);
            $contenido .= (string) call_user_func($formRenderer);
            $contenido .= FormBuilder::fin();
        }
        return self::render($id, $titulo, $contenido, $atributos);
    }
}
