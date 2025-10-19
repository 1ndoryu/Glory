<?php

namespace Glory\Components;

/**
 * Walker de menú minimalista para añadir clases por nivel a los UL de submenú.
 * Mantiene el marcado estándar de WordPress para los LI y A.
 */
class MenuWalker extends \Walker_Nav_Menu
{
    /**
     * Abre un nivel de submenú añadiendo clases por nivel.
     */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        $indent = str_repeat("\t", max(0, (int) $depth + 1));
        $nivel = (int) $depth + 2; // nivel 1 = raíz, submenú empieza en nivel 2
        $output .= "\n{$indent}<ul class=\"sub-menu menu menu-level-{$nivel}\">\n";
    }
}


