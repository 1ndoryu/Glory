<?php

namespace Glory\Components;

class Modal
{
    public static function render(string $id, string $titulo = '', string $contenidoHtml = '', array $atributos = []): string
    {
        $attrs = '';
        foreach ($atributos as $clave => $valor) {
            $attrs .= esc_attr($clave) . '="' . esc_attr((string)$valor) . '" ';
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr($id); ?>" class="modalOverlay modal" role="dialog" aria-modal="true" style="display:none;" data-close-on-overlay="0" <?php echo trim($attrs); ?>>
            <div class="modalDialog">
                <div class="modalContenido">
                    <?php if ($titulo !== ''): ?>
                        <h2><?php echo esc_html($titulo); ?></h2>
                    <?php endif; ?>
                    <?php echo $contenidoHtml; ?>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

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


