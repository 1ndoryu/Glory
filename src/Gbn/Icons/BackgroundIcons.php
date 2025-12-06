<?php
namespace Glory\Gbn\Icons;

/**
 * Iconos relacionados con opciones de fondo.
 */
class BackgroundIcons
{
    public static function all(): array
    {
        return [
            // === BACKGROUND SIZE ===
            'bg.size.cover' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M3 3l18 18M21 3L3 21" stroke-opacity="0.2"></path></svg>',
            'bg.size.contain' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="6" y="6" width="12" height="12" rx="1"></rect><rect x="3" y="3" width="18" height="18" rx="2" stroke-opacity="0.3"></rect></svg>',
            'bg.size.auto' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="3"></circle><rect x="3" y="3" width="18" height="18" rx="2" stroke-opacity="0.3"></rect></svg>',
            'bg.size.stretch' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M3 3h18v18H3z"></path><path d="M7 12h10M12 7v10"></path></svg>', // 100% 100%

            // === BACKGROUND POSITION ===
            'bg.pos.top-left' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="6" cy="6" r="2"/></svg>',
            'bg.pos.top-center' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="6" r="2"/></svg>',
            'bg.pos.top-right' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="18" cy="6" r="2"/></svg>',
            'bg.pos.left-center' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="6" cy="12" r="2"/></svg>',
            'bg.pos.center-center' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="2"/></svg>',
            'bg.pos.right-center' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="18" cy="12" r="2"/></svg>',
            'bg.pos.bottom-left' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="6" cy="18" r="2"/></svg>',
            'bg.pos.bottom-center' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="18" r="2"/></svg>',
            'bg.pos.bottom-right' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="18" cy="18" r="2"/></svg>',

            // === BACKGROUND REPEAT ===
            'bg.repeat.no-repeat' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="12" cy="12" r="2" fill="currentColor"></circle></svg>',
            'bg.repeat.repeat' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="2" y="2" width="8" height="8" rx="1"></rect><rect x="14" y="2" width="8" height="8" rx="1"></rect><rect x="2" y="14" width="8" height="8" rx="1"></rect><rect x="14" y="14" width="8" height="8" rx="1"></rect></svg>',
            'bg.repeat.repeat-x' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="2" y="8" width="8" height="8" rx="1"></rect><rect x="14" y="8" width="8" height="8" rx="1"></rect><path d="M12 4v16" stroke-dasharray="2 2" stroke-opacity="0.5"></path></svg>',
            'bg.repeat.repeat-y' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="8" y="2" width="8" height="8" rx="1"></rect><rect x="8" y="14" width="8" height="8" rx="1"></rect><path d="M4 12h16" stroke-dasharray="2 2" stroke-opacity="0.5"></path></svg>',

            // === BACKGROUND ATTACHMENT ===
            'bg.attach.scroll' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M12 8v4"></path><path d="M12 16v.01"></path></svg>',
            'bg.attach.fixed' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 2L12 22"></path><path d="M2 12L22 12"></path><circle cx="12" cy="12" r="3"></circle></svg>',
            'bg.attach.local' => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="3" width="18" height="18" rx="2"></rect><path d="M8 12h8"></path><path d="M12 8v8"></path></svg>',
        ];
    }
}
