<?php
/**
 * Componente Botón
 *
 * Renderiza un elemento de botón HTML de forma estandarizada, permitiendo
 * configurar texto, clases y atributos adicionales.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

/**
 * Clase Button.
 *
 * Generador simple de botones.
 */
class Button
{
    /**
     * Renderiza una cadena HTML de botón.
     *
     * @param array $options Opciones de configuración:
     *                       - 'texto': Etiqueta del botón (default 'Botón').
     *                       - 'class': Clases CSS.
     *                       - 'attrs': Array asociativo de atributos HTML.
     * @return string HTML del botón.
     */
    public static function render(array $options = []): string
    {
        $texto = $options['texto'] ?? 'Botón';
        $class = $options['class'] ?? '';
        $attrs = '';
        if (!empty($options['attrs']) && is_array($options['attrs'])) {
            foreach ($options['attrs'] as $k => $v) {
                $attrs .= sprintf(' %s="%s"', htmlspecialchars($k, ENT_QUOTES), htmlspecialchars($v, ENT_QUOTES));
            }
        }

        return sprintf(
            '<button class="%s" type="button"%s>%s</button>',
            htmlspecialchars($class, ENT_QUOTES),
            $attrs,
            htmlspecialchars($texto, ENT_QUOTES)
        );
    }
}
