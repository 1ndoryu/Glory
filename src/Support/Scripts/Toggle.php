<?php

namespace Glory\Support\Scripts;

class Toggle
{
    public static function enqueue(): void
    {
        if ( function_exists('wp_enqueue_script') ) {
            wp_enqueue_script('glory-glory-toggle');
        }
    }

    public static function buildInitScript( string $selector, array $options ): string
    {
        $sel = function_exists('wp_json_encode') ? wp_json_encode($selector) : json_encode($selector);
        $opt = function_exists('wp_json_encode') ? wp_json_encode($options) : json_encode($options);
        return '<script>(function(){var sel=' . $sel . ';var opt=' . $opt . ';if(window.GloryToggle&&typeof window.GloryToggle.init==="function"){window.GloryToggle.init(sel,opt);}else{window.GloryToggleQueue=window.GloryToggleQueue||[];window.GloryToggleQueue.push({selector:sel,options:opt});}})();</script>';
    }
}
