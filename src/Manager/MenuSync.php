<?php

namespace Glory\Manager;

/*
 * Sincronización de menús con la base de datos de WordPress.
 * Gestiona la creación, reconstrucción y verificación de menús
 * a partir de definiciones en código.
 */
class MenuSync
{
    /**
     * Asegura que un menú definido por código exista y esté actualizado en la BD.
     */
    public static function asegurarMenuDesdeDefinicion(string $location, array $definicion): void
    {
        $menuName = MenuDefinition::labelParaUbicacion($location);
        $menuId = null;

        $locations = get_nav_menu_locations();
        if (isset($locations[$location])) {
            $menuId = (int) $locations[$location];
        }
        if (!$menuId) {
            $menuId = self::getOrCreateMenuId($menuName);
            if (!$menuId) {
                return;
            }
        }

        $lockKey = 'glory_menu_lock_' . $menuId;
        if (get_transient($lockKey)) {
            return;
        }
        set_transient($lockKey, 1, 15);
        try {
            /* En modo dev, ignorar desincronización para aplicar cambios de código */
            if (!MenuDefinition::esModoDesarrollo() && self::estaDesincronizado($menuId)) {
                self::asignarUbicacion($location, $menuId);
                return;
            }

            /* Verificar si los ítems actuales ya coinciden (evita duplicados) */
            $itemsActuales = wp_get_nav_menu_items($menuId);
            if (self::itemsCoinciden($itemsActuales, $definicion)) {
                self::asignarUbicacion($location, $menuId);
                return;
            }

            $hashActual = self::hashDefinicion($definicion);
            $hashPrevio = (string) get_term_meta($menuId, 'glory_code_hash', true);
            if ($hashActual !== '' && $hashActual !== $hashPrevio) {
                self::reconstruirMenuDesdeCodigo($menuId, $definicion);
                update_term_meta($menuId, 'glory_code_hash', $hashActual);
                update_term_meta($menuId, 'glory_seeded_from_code', 1);
            }
            self::asignarUbicacion($location, $menuId);
        } finally {
            delete_transient($lockKey);
        }
    }

    /**
     * Restablece todos los menús definidos por código, ignorando estados de desincronización.
     * Se usa para el botón "Restablecer a Default" y tareas de mantenimiento.
     */
    public static function restablecerMenusDesdeCodigo(): void
    {
        $map = MenuDefinition::cargarDefinicionMenusDesdeCodigo();
        if (!is_array($map) || empty($map)) {
            $map = [MenuDefinition::UBICACION_MENU_PRINCIPAL => MenuDefinition::obtenerSeedPorDefecto()];
        }

        foreach ($map as $location => $items) {
            if (!is_array($items)) {
                $items = [];
            }

            $menuName = MenuDefinition::labelParaUbicacion((string) $location);
            $menuId = self::getOrCreateMenuId($menuName);
            if (!$menuId) {
                continue;
            }

            self::reconstruirMenuDesdeCodigo($menuId, $items);

            $hashActual = self::hashDefinicion($items);
            if ($hashActual !== '') {
                update_term_meta($menuId, 'glory_code_hash', $hashActual);
            } else {
                delete_term_meta($menuId, 'glory_code_hash');
            }

            /* Limpiar flags de desincronización y marcar como seed desde código */
            delete_term_meta($menuId, 'glory_menu_desync');
            delete_term_meta($menuId, 'glory_customized');
            update_term_meta($menuId, 'glory_seeded_from_code', 1);
            update_term_meta($menuId, 'glory_seeded', 1);

            self::asignarUbicacion((string) $location, $menuId);
        }
    }

    /**
     * Reconstruye el menú completo (elimina ítems actuales y crea los del código, con jerarquía).
     */
    public static function reconstruirMenuDesdeCodigo(int $menuId, array $definicion): void
    {
        wp_cache_flush();

        /* Eliminar todos los ítems actuales (2 pasadas para asegurar limpieza) */
        for ($pasada = 0; $pasada < 2; $pasada++) {
            $existentes = wp_get_nav_menu_items($menuId);
            if (is_array($existentes) && count($existentes) > 0) {
                foreach ($existentes as $item) {
                    wp_delete_post((int) $item->ID, true);
                }
            } else {
                break;
            }
        }

        /* Crear recursivamente los ítems */
        $posicion = 1;
        foreach ($definicion as $item) {
            $parentId = self::crearItemMenu($menuId, 0, $item['title'], $item['url'], $posicion);
            $posicion++;
            if ($parentId && !empty($item['children'])) {
                self::crearHijosRecursivo($menuId, $parentId, $item['children']);
            }
        }
    }

    /**
     * Crea recursivamente los ítems hijos en el menú.
     */
    public static function crearHijosRecursivo(int $menuId, int $parentItemId, array $children): void
    {
        foreach ($children as $child) {
            $childId = self::crearItemMenu($menuId, $parentItemId, (string) $child['title'], (string) $child['url']);
            if ($childId && !empty($child['children'])) {
                self::crearHijosRecursivo($menuId, $childId, $child['children']);
            }
        }
    }

    /**
     * Crea un ítem en el menú y devuelve el ID del post creado.
     */
    public static function crearItemMenu(int $menuId, int $parentItemId, string $title, string $url, int $position = 0): ?int
    {
        $args = [
            'menu-item-type'        => 'custom',
            'menu-item-object'      => 'custom',
            'menu-item-title'       => $title,
            'menu-item-attr-title'  => $title,
            'menu-item-url'         => self::resolverUrl($url),
            'menu-item-status'      => 'publish',
        ];
        if ($parentItemId > 0) {
            $args['menu-item-parent-id'] = $parentItemId;
        }
        if ($position > 0) {
            $args['menu-item-position'] = $position;
        }
        $itemId = wp_update_nav_menu_item($menuId, 0, $args);
        if (is_wp_error($itemId)) {
            return null;
        }
        return (int) $itemId;
    }

    /**
     * Resuelve una URL relativa o especial a su forma absoluta.
     */
    public static function resolverUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || $url === '#') {
            return '#';
        }
        if (strpos($url, '/') === 0) {
            return home_url($url);
        }
        return $url;
    }

    /**
     * Verifica si los ítems actuales del menú coinciden con la definición del código.
     * Evita reconstruir el menú si ya tiene los ítems correctos.
     */
    public static function itemsCoinciden(?array $itemsActuales, array $definicion): bool
    {
        if (!is_array($itemsActuales)) {
            $itemsActuales = [];
        }

        /* Filtrar solo ítems de nivel superior (sin padre) */
        $itemsRaiz = array_filter($itemsActuales, function ($item) {
            return empty($item->menu_item_parent) || (int) $item->menu_item_parent === 0;
        });

        if (count($itemsRaiz) !== count($definicion)) {
            return false;
        }

        /* Mapa de ítems actuales por título normalizado */
        $mapaActual = [];
        foreach ($itemsRaiz as $item) {
            $titulo = strtolower(trim((string) ($item->title ?? $item->post_title ?? '')));
            if (!isset($mapaActual[$titulo])) {
                $mapaActual[$titulo] = 0;
            }
            $mapaActual[$titulo]++;
        }

        /* Mapa de ítems esperados por título normalizado */
        $mapaEsperado = [];
        foreach ($definicion as $def) {
            $titulo = strtolower(trim((string) ($def['title'] ?? '')));
            if ($titulo === '') {
                continue;
            }
            if (!isset($mapaEsperado[$titulo])) {
                $mapaEsperado[$titulo] = 0;
            }
            $mapaEsperado[$titulo]++;
        }

        if (count($mapaActual) !== count($mapaEsperado)) {
            return false;
        }

        foreach ($mapaEsperado as $titulo => $cantidad) {
            if (!isset($mapaActual[$titulo]) || $mapaActual[$titulo] !== $cantidad) {
                return false;
            }
        }

        return true;
    }

    /**
     * Crear u obtener el ID del menú dado su nombre.
     */
    public static function getOrCreateMenuId(string $menuName): ?int
    {
        $menu = wp_get_nav_menu_object($menuName);
        if ($menu && isset($menu->term_id)) {
            return (int) $menu->term_id;
        }

        $menuId = wp_create_nav_menu($menuName);
        if (is_wp_error($menuId)) {
            return null;
        }
        return (int) $menuId;
    }

    /**
     * Asigna un menú a una ubicación del tema.
     */
    public static function asignarUbicacion(string $location, int $menuId): void
    {
        $ubicaciones = get_theme_mod('nav_menu_locations');
        if (!is_array($ubicaciones)) {
            $ubicaciones = [];
        }
        $ubicaciones[$location] = $menuId;
        set_theme_mod('nav_menu_locations', $ubicaciones);
    }

    /**
     * Verifica si un menú está desincronizado respecto al código.
     */
    public static function estaDesincronizado(int $menuId): bool
    {
        $desync = (int) get_term_meta($menuId, 'glory_menu_desync', true);
        if ($desync === 1) {
            return true;
        }
        $custom = (int) get_term_meta($menuId, 'glory_customized', true);
        return $custom === 1;
    }

    /**
     * Genera un hash SHA1 de la definición para detectar cambios.
     */
    public static function hashDefinicion(array $definicion): string
    {
        if (empty($definicion)) {
            return '';
        }
        $json = wp_json_encode($definicion);
        if (!is_string($json) || $json === '') {
            return '';
        }
        return sha1($json);
    }
}
