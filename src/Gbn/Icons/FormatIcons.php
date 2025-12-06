<?php
namespace Glory\Gbn\Icons;

/**
 * Iconos de formato y tipografía.
 */
class FormatIcons
{
    public static function all(): array
    {
        return [
            // Text Transform
            'text.none' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><line x1="12" y1="4" x2="12" y2="20"/></svg>', // Generic placeholder or strict "no"
            // Better 'none' icon (circle slash)
            'text.standard' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>',
            
            'text.uppercase' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 15v-4.5a2.5 2.5 0 0 1 5 0V15"/><path d="M14 15v-4.5a2.5 2.5 0 0 1 5 0V15"/><path d="M5 19h14"/><path d="M10 7h4"/></svg>', // Abstract AA
            
            'text.lowercase' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M16 8v8"/></svg>', // Simple 'a'
            
            'text.capitalize' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-8a2 2 0 0 -1 2 -2v0a2 2 0 0 -1 2 2v8" /><path d="M4 21v-8a2 2 0 0 1 2 -2h0a2 2 0 0 1 2 2v8"/><path d="M4 13h4"/></svg>', // Abstract Aa
            
            // HTTP Methods
            'http.post' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>', // Arrow Up (Send)
            'http.get' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>', // Arrow Down (Receive)

            // Text Alignment
            'text.align.left' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 9.5H3M21 4.5H3M21 14.5H3M17 19.5H3"/></svg>',
            'text.align.center' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 9.5H5M21 4.5H3M21 14.5H3M19 19.5H5"/></svg>',
            'text.align.right' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 9.5H7M21 4.5H3M21 14.5H3M21 19.5H7"/></svg>',
            'text.align.justify' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4.5h18M3 9.5h18M3 14.5h18M3 19.5h18"/></svg>',
        ];
    }
}
