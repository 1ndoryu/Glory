<?php

namespace Glory\Manager;

use Glory\Core\GloryFeatures;

/*
 * Fachada principal de gestión de menús.
 * Delega la lógica de definición, sincronización y normalización
 * a MenuDefinition, MenuSync y MenuNormalizer respectivamente.
 */
class MenuManager
{
    private static bool $asegurado = false;

    public static function register(): void
    {
        if (GloryFeatures::isActive('menu', 'glory_componente_menu_activado') === false) {
            return;
        }

        add_action('after_setup_theme', [self::class, 'registrarUbicacionesMenu']);
        add_action('after_setup_theme', [self::class, 'asegurarMenus'], 20);

        /* Marcar menú como desincronizado cuando el usuario lo edita desde el admin */
        add_action('wp_update_nav_menu', [self::class, 'onMenuUpdated'], 10, 1);
        add_action('wp_delete_nav_menu', [self::class, 'onMenuDeleted'], 10, 1);
        add_action('wp_update_nav_menu_item', [self::class, 'onMenuItemUpdated'], 10, 3);

        /* Limpiar duplicados y restablecer menú al activar/reinstalar el tema */
        add_action('after_switch_theme', [self::class, 'onThemeActivation']);
    }

    /**
     * Se ejecuta cuando el tema es activado (instalado o reinstalado).
     * Limpia duplicados y restablece el menú desde el código.
     */
    public static function onThemeActivation(): void
    {
        wp_cache_flush();
        self::$asegurado = false;

        /* Limpiar meta-datos del menú para forzar sincronización desde código */
        $locations = get_nav_menu_locations();
        foreach ($locations as $location => $menuId) {
            if ($menuId) {
                delete_term_meta($menuId, 'glory_code_hash');
                delete_term_meta($menuId, 'glory_seeded_from_code');
            }
        }

        MenuSync::restablecerMenusDesdeCodigo();
    }

    public static function registrarUbicacionesMenu(): void
    {
        register_nav_menu(
            MenuDefinition::UBICACION_MENU_PRINCIPAL,
            __('Main Navigation', 'glory')
        );

        /* En modo desarrollo, registrar dinámicamente ubicaciones adicionales definidas en código */
        $def = MenuDefinition::cargarDefinicionMenusDesdeCodigo();
        if (is_array($def)) {
            foreach (array_keys($def) as $location) {
                if ($location === MenuDefinition::UBICACION_MENU_PRINCIPAL) {
                    continue;
                }
                $label = MenuDefinition::labelParaUbicacion($location);
                register_nav_menu($location, $label);
            }
        }
    }

    public static function asegurarMenus(): void
    {
        if (self::$asegurado === true) {
            return;
        }

        $defMenus = MenuDefinition::cargarDefinicionMenusDesdeCodigo();

        /* Asegurar al menos el menú principal aunque no haya definición */
        $locations = get_nav_menu_locations();
        $menuPrincipalId = null;
        if (isset($locations[MenuDefinition::UBICACION_MENU_PRINCIPAL])) {
            $menuPrincipalId = (int) $locations[MenuDefinition::UBICACION_MENU_PRINCIPAL];
        }
        if (!$menuPrincipalId) {
            $menuPrincipalId = MenuSync::getOrCreateMenuId(__('Main Menu', 'glory'));
        }
        if (!$menuPrincipalId) {
            self::$asegurado = true;
            return;
        }

        /* Si hay definición en modo dev, asegurar TODOS los menús definidos */
        if (MenuDefinition::esModoDesarrollo() && is_array($defMenus) && !empty($defMenus)) {
            foreach ($defMenus as $location => $items) {
                MenuSync::asegurarMenuDesdeDefinicion($location, $items);
            }
            self::$asegurado = true;
            return;
        }

        /* Sin definición: preservar comportamiento legacy para el principal */
        $lockKey = 'glory_menu_lock_' . $menuPrincipalId;
        if (get_transient($lockKey)) {
            return;
        }
        set_transient($lockKey, 1, 15);
        try {
            if (MenuSync::estaDesincronizado($menuPrincipalId)) {
                MenuSync::asignarUbicacion(MenuDefinition::UBICACION_MENU_PRINCIPAL, $menuPrincipalId);
                self::$asegurado = true;
                return;
            }
            $menuItems = wp_get_nav_menu_items($menuPrincipalId);
            if (!is_array($menuItems)) {
                $menuItems = [];
            }
            $hasNonPlaceholders = MenuNormalizer::tieneItemsNoPlaceholders($menuItems);
            if ($hasNonPlaceholders) {
                update_term_meta($menuPrincipalId, 'glory_customized', 1);
                update_term_meta($menuPrincipalId, 'glory_menu_desync', 1);
                MenuSync::asignarUbicacion(MenuDefinition::UBICACION_MENU_PRINCIPAL, $menuPrincipalId);
                return;
            }
            MenuNormalizer::normalizarPlaceholders($menuPrincipalId, $menuItems);
            update_term_meta($menuPrincipalId, 'glory_seeded', 1);
            MenuSync::asignarUbicacion(MenuDefinition::UBICACION_MENU_PRINCIPAL, $menuPrincipalId);
            self::$asegurado = true;
        } finally {
            delete_transient($lockKey);
        }
    }

    /**
     * Delegación a MenuSync para compatibilidad con código externo.
     */
    public static function restablecerMenusDesdeCodigo(): void
    {
        MenuSync::restablecerMenusDesdeCodigo();
    }

    public static function onMenuUpdated(int $menuId): void
    {
        update_term_meta($menuId, 'glory_menu_desync', 1);
        update_term_meta($menuId, 'glory_customized', 1);
    }

    public static function onMenuDeleted(int $menuId): void
    {
        delete_term_meta($menuId, 'glory_menu_desync');
        delete_term_meta($menuId, 'glory_customized');
        delete_term_meta($menuId, 'glory_code_hash');
        delete_term_meta($menuId, 'glory_seeded');
        delete_term_meta($menuId, 'glory_seeded_from_code');
    }

    public static function onMenuItemUpdated(int $menuId, int $menuItemDbId, $args): void
    {
        update_term_meta($menuId, 'glory_menu_desync', 1);
        update_term_meta($menuId, 'glory_customized', 1);
    }
}
