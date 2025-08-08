<?php

namespace Glory\Manager;

use Glory\Core\GloryFeatures;

class MenuManager
{
    private const UBICACION_MENU_PRINCIPAL = 'main_navigation';


    public static function register(): void
    {
        // No registrar la gestión de menús si la feature 'menu' está desactivada
        if (GloryFeatures::isEnabled('menu') === false) {
            return;
        }

        add_action('after_setup_theme', [self::class, 'registrarUbicacionesMenu']);
        add_action('after_setup_theme', [self::class, 'asegurarMenuPrincipal'], 20);
    }


    public static function registrarUbicacionesMenu(): void
    {
        register_nav_menu(self::UBICACION_MENU_PRINCIPAL, __('Main Navigation', 'glory'));
    }


    public static function asegurarMenuPrincipal(): void
    {
        if (has_nav_menu(self::UBICACION_MENU_PRINCIPAL)) {
            $locations = get_nav_menu_locations();
            $menu_id = $locations[self::UBICACION_MENU_PRINCIPAL];
            $menu_items = wp_get_nav_menu_items($menu_id);
            if (!empty($menu_items)) {
                return;
            }
        }

        $nombreMenu = __('Main Menu', 'glory');
        $menu = wp_get_nav_menu_object($nombreMenu);
        $menuId = null;

        if (!$menu) {
            $menuId = wp_create_nav_menu($nombreMenu);
            if (is_wp_error($menuId)) {
                return;
            }
        } else {
            $menuId = $menu->term_id;
        }

        // Si el menú está vacío, crear items por defecto.
        $menu_items = wp_get_nav_menu_items($menuId);
        if (empty($menu_items)) {
            $items = ['Inicio', 'example', 'example', 'example', 'example'];
            foreach ($items as $index => $title) {
                wp_update_nav_menu_item($menuId, 0, [
                    'menu-item-title'  => $title,
                    'menu-item-url'    => ($title === 'Inicio') ? home_url('/') : '#',
                    'menu-item-status' => 'publish',
                    'menu-item-position' => $index + 1,
                ]);
            }
        }

        $ubicaciones = get_theme_mod('nav_menu_locations');
        $ubicaciones[self::UBICACION_MENU_PRINCIPAL] = $menuId;
        set_theme_mod('nav_menu_locations', $ubicaciones);
    }
}