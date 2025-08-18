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
        $html = '<button id="theme-toggle" class="borde glory-theme-toggle" aria-label="Alternar tema" title="Alternar tema" type="button" style="position:fixed;top:1rem;right:1rem;z-index:2000;display:flex;align-items:center;justify-content:center;border-radius:6px;">';
        $html .= '</button>';
        return $html;
    }
}


