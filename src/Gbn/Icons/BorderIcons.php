<?php
namespace Glory\Gbn\Icons;

/**
 * Iconos relacionados con opciones de borde.
 */
class BorderIcons
{
    public static function all(): array
    {
        return [
            // === BORDER STYLE ===
            'border.style.solid' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><line x1="2" y1="12" x2="22" y2="12"/></svg>',
            'border.style.dashed' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M5 12h2"/><path d="M11 12h2"/><path d="M17 12h2"/></svg>',
            'border.style.dotted' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><circle cx="4" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="20" cy="12" r="1"/></svg>',
            'border.style.double' => '<svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><line x1="2" y1="9" x2="22" y2="9"/><line x1="2" y1="15" x2="22" y2="15"/></svg>',
        ];
    }
}
