<?php

namespace Glory\Manager;

use Glory\Core\GloryLogger;

class AdminPageManager
{
    private static array $topLevelPages = [];
    private static array $subPages = [];

    public static function defineTopLevel(
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        ?string $function = null, // CORREGIDO
        string $icon_url = '',
        ?int $position = null
    ): void {
        if (isset(self::$topLevelPages[$menu_slug])) {
            GloryLogger::warning("AdminPageManager: El menú de nivel superior con el slug '{$menu_slug}' ya ha sido definido.");
            return;
        }
        self::$topLevelPages[$menu_slug] = compact('page_title', 'menu_title', 'capability', 'menu_slug', 'function', 'icon_url', 'position');
    }

    public static function defineSubmenu(
        string $parent_slug,
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        ?string $function = null, // CORREGIDO
        ?int $position = null
    ): void {
        self::$subPages[] = compact('parent_slug', 'page_title', 'menu_title', 'capability', 'menu_slug', 'function', 'position');
    }

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'createAdminPages']);
    }

    public static function createAdminPages(): void
    {
        foreach (self::$topLevelPages as $slug => $page) {
            add_menu_page(
                $page['page_title'],
                $page['menu_title'],
                $page['capability'],
                $page['menu_slug'],
                $page['function'],
                $page['icon_url'],
                $page['position']
            );
        }

        foreach (self::$subPages as $page) {
            add_submenu_page(
                $page['parent_slug'],
                $page['page_title'],
                $page['menu_title'],
                $page['capability'],
                $page['menu_slug'],
                $page['function'],
                $page['position']
            );
        }
    }
}
