<?php
namespace Glory\Gbn\Icons;

/**
 * Iconos relacionados con modos de layout.
 */
class LayoutIcons
{
    public static function all(): array
    {
        return [
            // === DISPLAY MODE ===
            'layout.block' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>',
            
            'layout.flex' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 3v18"/></svg>',
            
            'layout.grid' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>',
            
            // === FLEX DIRECTION ===
            'direction.row' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><path d="M16 8l4 4-4 4"/></svg>',
            
            'direction.column' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16"/><path d="M8 16l4 4 4-4"/></svg>',
            
            // === FLEX WRAP ===
            'wrap.nowrap' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/></svg>',
            
            'wrap.wrap' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8h10a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2H4"/><path d="M8 12l-4 4 4 4"/></svg>',
            
            // === JUSTIFY CONTENT ===
            'justify.start' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="6" height="18" rx="1"/></svg>',
            
            'justify.center' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="3" width="6" height="18" rx="1"/></svg>',
            
            'justify.end' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="15" y="3" width="6" height="18" rx="1"/></svg>',
            
            'justify.between' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="4" height="18" rx="1"/><rect x="17" y="3" width="4" height="18" rx="1"/></svg>',
            
            'justify.around' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="3" width="4" height="18" rx="1"/><rect x="15" y="3" width="4" height="18" rx="1"/></svg>',
            
            // === ALIGN ITEMS ===
            'align.stretch' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3v18"/><path d="M20 3v18"/><rect x="8" y="6" width="8" height="12" rx="1"/></svg>',
            
            'align.start' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 3h16"/><rect x="8" y="7" width="8" height="8" rx="1"/></svg>',
            
            'align.center' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>',
            
            'align.end' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21h16"/><rect x="8" y="9" width="8" height="8" rx="1"/></svg>',
        ];
    }
}
