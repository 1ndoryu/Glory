<?php
namespace Glory\Gbn\Icons;

/**
 * Iconos relacionados con opciones de posicionamiento.
 */
class PositioningIcons
{
    public static function all(): array
    {
        return [
            // === POSITION ===
            'pos.static' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>',
            'pos.relative' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2" stroke-dasharray="4 4"/><rect x="8" y="8" width="8" height="8"/></svg>',
            'pos.absolute' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" stroke-opacity="0.2"/><rect x="12" y="4" width="8" height="8"/></svg>',
            'pos.fixed' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20"/><circle cx="12" cy="12" r="3"/></svg>',
            'pos.sticky' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 8h16"/></svg>',

            // === OVERFLOW ===
            'overflow.visible' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M12 8v8M8 12h8"/></svg>',
            'overflow.hidden' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 4l16 16M20 4L4 20"/></svg>',
            'overflow.scroll' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M12 8v3"/><path d="M12 16v-3"/><path d="M9 13l3 3 3-3"/></svg>',
            'overflow.auto' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><circle cx="12" cy="12" r="3"/></svg>',
        ];
    }
}
