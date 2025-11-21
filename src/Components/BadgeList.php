<?php
/**
 * Componente BadgeList
 *
 * Renderiza una lista de etiquetas (badges) interactivas que pueden funcionar
 * como filtros (toggle) o navegación simple.
 *
 * @package Glory\Components
 */

namespace Glory\Components;

/**
 * Clase BadgeList.
 *
 * Genera un componente visual de lista de etiquetas.
 */
class BadgeList
{
    /**
     * Renderiza una lista de badges.
     *
     * @param array $options Opciones de configuración:
     *                       - 'badges': array de strings con los nombres de los badges.
     *                       - 'mode': 'toggle' (filtro) o 'navigate' (enlaces).
     * @return string HTML del componente.
     */
    public static function render(array $options = []): string
    {
        $badges = $options['badges'] ?? [];
        $mode   = $options['mode'] ?? 'toggle';
        // Generar un ID único y seguro si no se provee uno, para evitar conflictos en la misma página.
        $id = 'glory-badgelist-' . bin2hex(random_bytes(4));

        $html = "<div id='" . esc_attr($id) . "' class='badgeList' data-mode='" . esc_attr($mode) . "'>";
        foreach ($badges as $badge) {
            // Usar la primera palabra como label/slug base y normalizar caracteres (ñ, acentos)
            $parts = preg_split('/\s+/', trim($badge));
            $display = $parts[0] ?? '';

            // Transliterar a ASCII para evitar caracteres especiales en el slug (slug seguro)
            // Se suprime errores de iconv si la configuración del servidor es estricta.
            $trans = @iconv('UTF-8', 'ASCII//TRANSLIT', $display);
            if ($trans === false) {
                $trans = $display; // Fallback si iconv falla
            }
            $slug = preg_replace('/[^a-z0-9_-]/i', '', strtolower($trans));

            $html .= "<div class='badge' data-target='" . esc_attr($slug) . "'>" . esc_html($display) . "</div>";
        }
        $html .= "</div>";

        return $html;
    }
}
