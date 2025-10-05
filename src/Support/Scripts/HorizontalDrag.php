<?php

namespace Glory\Support\Scripts;

class HorizontalDrag
{
    public static function enqueue(): void
    {
        if ( function_exists('wp_enqueue_script') ) {
            wp_enqueue_script('glory-horizontal-drag');
        }
    }

    public static function buildInitScript( string $selector ): string
    {
        $sel = function_exists('wp_json_encode') ? wp_json_encode($selector) : json_encode($selector);
        return '<script>(function(){var sel=' . $sel . ';if(window.GloryHorizontalDrag&&typeof window.GloryHorizontalDrag.init==="function"){window.GloryHorizontalDrag.init(sel);}else{window.GloryHorizontalDragQueue=window.GloryHorizontalDragQueue||[];window.GloryHorizontalDragQueue.push({selector:sel});}})();</script>';
    }
}


