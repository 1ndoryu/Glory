<?php

namespace Glory\Components;

class BadgeList
{
    /**
     * Renderiza una lista de badges.
     * $options: ['badges' => ['formulario','modal'], 'mode' => 'toggle'|'navigate']
     */
    public static function render(array $options = [])
    {
        $badges = $options['badges'] ?? [];
        $mode = $options['mode'] ?? 'toggle';
        $id = 'glory-badgelist-' . bin2hex(random_bytes(4));

        $html = "<div id='$id' class='badgeList' data-mode='$mode'>";
        foreach ($badges as $badge) {
            // usar la primera palabra como label/slug y normalizar caracteres (ñ, acentos)
            $parts = preg_split('/\s+/', trim($badge));
            $display = $parts[0] ?? '';
            // transliterar a ASCII para evitar caracteres especiales en el slug
            $trans = iconv('UTF-8', 'ASCII//TRANSLIT', $display);
            $slug = preg_replace('/[^a-z0-9_-]/i', '', strtolower($trans));
            $html .= "<div class='badge' data-target='" . htmlspecialchars($slug) . "'>" . htmlspecialchars($display) . "</div>";
        }
        $html .= "</div>";

        return $html;
    }
}


