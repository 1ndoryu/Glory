<?php

/**
 * Botón de Cambio de Tema (Dark/Light Mode)
 *
 * Componente UI que renderiza un botón accesible para alternar entre
 * el esquema de colores claro y oscuro. Funciona en conjunto con el JS del tema.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

use Glory\Manager\OpcionManager;

/**
 * Clase ThemeToggle.
 *
 * Renderiza el switch de modo oscuro/claro.
 */
class ThemeToggle
{
    /**
     * Renderiza el botón toggle para el tema.
     *
     * @return string HTML del botón.
     */
    public static function render(): string
    {
        // Exclusión forzada para el panel de control de GBN
        if (function_exists('is_page') && is_page('gbn-control-panel')) {
            return '';
        }

        // ID y clases estandarizadas para que JS las encuentre y enlace la funcionalidad.
        $html  = '<button id="themeToggle" class="borde gloryThemeToggle" aria-label="Alternar tema" title="Alternar tema" type="button">';
        $html .= '</button>';
        return $html;
    }
}
