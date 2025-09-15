<?php

namespace Glory\Components;

use Glory\Manager\OpcionManager;

class ThemeToggle
{
    /**
     * Renderiza el botón toggle para el tema.
     */
    public static function render(): string
    {
        // ID y clases estandarizadas para que JS las encuentre.
        $html = '<button id="themeToggle" class="borde gloryThemeToggle" aria-label="Alternar tema" title="Alternar tema" type="button">';
        $html .= '</button>';
        return $html;
    }
}


