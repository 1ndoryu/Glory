<?php
/**
 * Walker de Menú Personalizado
 *
 * Extiende la funcionalidad estándar de WordPress para menús, añadiendo clases
 * CSS específicas por nivel para facilitar el estilizado de submenús profundos.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

/**
 * Clase MenuWalker.
 *
 * Walker de menú minimalista para añadir clases por nivel a los UL de submenú.
 * Mantiene el marcado estándar de WordPress para los LI y A.
 */
class MenuWalker extends \Walker_Nav_Menu
{
    /**
     * Abre un nivel de submenú añadiendo clases por nivel.
     *
     * @param string $output Pasado por referencia. Se usa para anexar el contenido.
     * @param int    $depth  Profundidad del elemento de menú.
     * @param mixed  $args   Un array de argumentos.
     */
    public function start_lvl(&$output, $depth = 0, $args = null): void
    {
        $indent = str_repeat("\t", max(0, (int) $depth + 1));
        $nivel  = (int) $depth + 2; // Nivel 1 = raíz, submenú empieza en nivel 2
        $output .= "\n{$indent}<ul class=\"sub-menu menu menu-level-{$nivel}\">\n";
    }
}
