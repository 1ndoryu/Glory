<?php

namespace Glory\Plugins\AmazonProduct\Service;

/**
 * Handles discount calculations for Amazon products.
 * Single Responsibility: Only calculates discounts.
 */
class DiscountCalculator
{
    /**
     * Calculate discount percentage between original and current price.
     */
    public static function calculate(float $originalPrice, float $currentPrice): int
    {
        if ($originalPrice <= 0 || $originalPrice <= $currentPrice) {
            return 0;
        }
        return (int) round((($originalPrice - $currentPrice) / $originalPrice) * 100);
    }

    /**
     * Check if a product has a valid discount.
     */
    public static function hasDiscount(float $originalPrice, float $currentPrice): bool
    {
        return self::calculate($originalPrice, $currentPrice) > 0;
    }

    /**
     * Calculate savings amount.
     */
    public static function calculateSavings(float $originalPrice, float $currentPrice): float
    {
        if ($originalPrice <= $currentPrice) {
            return 0.0;
        }
        return round($originalPrice - $currentPrice, 2);
    }

    /**
     * Adjust hex color brightness.
     * Used for generating hover states for buttons.
     * 
     * @param string $hex Hex color code
     * @param int $steps Steps to adjust (-255 to 255, negative = darker)
     */
    public static function adjustBrightness(string $hex, int $steps): string
    {
        $steps = max(-255, min(255, $steps));
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) === 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2)
                . str_repeat(substr($hex, 1, 1), 2)
                . str_repeat(substr($hex, 2, 1), 2);
        }

        $colorParts = str_split($hex, 2);
        $return = '#';

        foreach ($colorParts as $color) {
            $color = hexdec($color);
            $color = max(0, min(255, $color + $steps));
            $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT);
        }

        return $return;
    }
}
