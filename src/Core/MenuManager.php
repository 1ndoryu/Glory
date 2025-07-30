<?php

namespace Glory\Core;

class MenuManager
{
    private const UBICACION_MENU_PRINCIPAL = 'main_navigation';

    /**
     * Registra los hooks necesarios para la gestión de menús.
     */
    public static function register(): void
    {
        add_action('after_setup_theme', [self::class, 'registrarUbicacionesMenu']);
        add_action('after_setup_theme', [self::class, 'asegurarMenuPrincipal'], 20);
    }

    /**
     * Registra las ubicaciones de menú del tema.
     */
    public static function registrarUbicacionesMenu(): void
    {
        register_nav_menu(self::UBICACION_MENU_PRINCIPAL, __('Main Navigation', 'glory'));
    }

    /**
     * Asegura que exista un menú y esté asignado a la ubicación principal si no hay uno.
     */
    public static function asegurarMenuPrincipal(): void
    {
        if (has_nav_menu(self::UBICACION_MENU_PRINCIPAL)) {
            return;
        }

        $nombreMenu = __('Main Menu', 'glory');
        $menu = wp_get_nav_menu_object($nombreMenu);

        if (!$menu) {
            $menuId = wp_create_nav_menu($nombreMenu);
            if (is_wp_error($menuId)) {
                return;
            }
        } else {
            $menuId = $menu->term_id;
        }

        $ubicaciones = get_theme_mod('nav_menu_locations');
        $ubicaciones[self::UBICACION_MENU_PRINCIPAL] = $menuId;
        set_theme_mod('nav_menu_locations', $ubicaciones);
    }
}
