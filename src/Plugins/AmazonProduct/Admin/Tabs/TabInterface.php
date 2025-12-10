<?php

namespace Glory\Plugins\AmazonProduct\Admin\Tabs;

/**
 * Interface for admin tabs.
 * All tabs must implement this interface.
 */
interface TabInterface
{
    /**
     * Get the tab slug/identifier.
     */
    public function getSlug(): string;

    /**
     * Get the tab display label.
     */
    public function getLabel(): string;

    /**
     * Render the tab content.
     */
    public function render(): void;
}
