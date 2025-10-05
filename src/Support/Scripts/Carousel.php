<?php

namespace Glory\Support\Scripts;

class Carousel
{
    public static function enqueue(): void
    {
        if ( function_exists('wp_enqueue_script') ) {
            wp_enqueue_script('glory-glory-carousel');
        }
    }

    public static function buildInitOrQueue( string $selector, float $speed ): string
    {
        $sel = function_exists('wp_json_encode') ? wp_json_encode($selector) : json_encode($selector);
        $spd = json_encode($speed);
        return '<script>(window.GloryCarousel?window.GloryCarousel:window.GloryCarouselQueue=(window.GloryCarouselQueue||[])).init?window.GloryCarousel.init(' . $sel . ',{"speed":' . $spd . '}):window.GloryCarouselQueue.push({selector:' . $sel . ',options:{"speed":' . $spd . '}});</script>';
    }

    public static function buildStop( string $selector ): string
    {
        $sel = function_exists('wp_json_encode') ? wp_json_encode($selector) : json_encode($selector);
        return '<script>window.GloryCarousel&&window.GloryCarousel.stop(' . $sel . ');</script>';
    }
}
