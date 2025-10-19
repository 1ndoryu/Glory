<?php

namespace Glory\Manager;

use Glory\Core\GloryFeatures;

class MenuManager
{
    private const UBICACION_MENU_PRINCIPAL = 'main_navigation';
    private const RUTA_MENU_CODIGO = '/App/Content/menu.php';

    private static bool $asegurado = false;


    public static function register(): void
    {
        // No registrar la gestión de menús si la feature 'menu' está desactivada.
        // Usar isActive para combinar override por código + opción en BD.
        if (GloryFeatures::isActive('menu', 'glory_componente_menu_activado') === false) {
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
        if (self::$asegurado === true) {
            return;
        }
        // 1) Determinar/crear el menú objetivo
        $menuName = __('Main Menu', 'glory');
        $menuId = null;

        if (has_nav_menu(self::UBICACION_MENU_PRINCIPAL)) {
            $locations = get_nav_menu_locations();
            if (isset($locations[self::UBICACION_MENU_PRINCIPAL])) {
                $menuId = (int) $locations[self::UBICACION_MENU_PRINCIPAL];
            }
        }

        if (!$menuId) {
            $menuId = self::getOrCreateMenuId($menuName);
            if (!$menuId) {
                self::$asegurado = true;
                return;
            }
        }

        // Prioridad: en modo desarrollo usar la definición de código
        if (self::esModoDesarrollo()) {
            $definicion = self::cargarDefinicionMenuDesdeCodigo();
            if (is_array($definicion) && !empty($definicion)) {
                self::reconstruirMenuDesdeCodigo($menuId, $definicion);
                update_term_meta($menuId, 'glory_seeded_from_code', 1);
                self::asignarUbicacion($menuId);
                self::$asegurado = true;
                return;
            }
        }

        // 2) Comprobar si el menú contiene elementos no-placeholder (personalizado)
        $menuItems = wp_get_nav_menu_items($menuId);
        if (!is_array($menuItems)) {
            $menuItems = [];
        }

        $hasNonPlaceholders = self::tieneItemsNoPlaceholders($menuItems);
        if ($hasNonPlaceholders) {
            // Marcar como personalizado si detectamos cambios respecto a placeholders
            update_term_meta($menuId, 'glory_customized', 1);
            self::asignarUbicacion($menuId);
            return;
        }

        // 3) Normalizar placeholders (evitar duplicados, crear faltantes, ordenar)
        self::normalizarPlaceholders($menuId, $menuItems);

        // 4) Marcar como sembrado y asignar a la ubicación
        update_term_meta($menuId, 'glory_seeded', 1);
        self::asignarUbicacion($menuId);
        self::$asegurado = true;
    }

    /**
     * Devuelve la definición de los ítems por defecto.
     * Mantener el mismo contenido para que la normalización sea idempotente.
     * @return array<int, array{title:string,url:string}>
     */
    private static function obtenerSeedPorDefecto(): array
    {
        return [
            [ 'title' => 'Inicio',  'url' => home_url('/') ],
            [ 'title' => 'example', 'url' => '#' ],
            [ 'title' => 'example', 'url' => '#' ],
            [ 'title' => 'example', 'url' => '#' ],
            [ 'title' => 'example', 'url' => '#' ],
        ];
    }

    /**
     * Crear u obtener el ID del menú dado su nombre.
     */
    private static function getOrCreateMenuId(string $menuName): ?int
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
     * Verifica si el array de ítems contiene algún elemento que NO sea un placeholder del seed.
     */
    private static function tieneItemsNoPlaceholders(array $menuItems): bool
    {
        if (empty($menuItems)) {
            return false;
        }

        $seed = self::obtenerSeedPorDefecto();
        $permitidos = self::construirMulticonjuntoPermitidos($seed);

        foreach ($menuItems as $item) {
            $titleRaw = (string) ($item->title ?? $item->post_title ?? '');
            $urlRaw = (string) ($item->url ?? '');
            if (trim($titleRaw) === '' || trim($urlRaw) === '') {
                // Ítems vacíos no indican personalización; se normalizarán luego
                continue;
            }
            $key = self::claveItem($titleRaw, $urlRaw);
            if (!array_key_exists($key, $permitidos)) {
                return true; // hay un ítem que no es placeholder
            }
        }

        return false;
    }

    /**
     * Normaliza el menú para que contenga exactamente los placeholders (sin duplicados extra) y ordenados.
     */
    private static function normalizarPlaceholders(int $menuId, array $menuItems): void
    {
        $seed = self::obtenerSeedPorDefecto();
        $permitidos = self::construirMulticonjuntoPermitidos($seed); // key => cantidad requerida
        $keyToDef = [];
        foreach ($seed as $def) {
            $keyToDef[self::claveItem($def['title'], $def['url'])] = $def; // conservar mayúsculas originales
        }

        // Conteo actual
        $conteoActual = [];
        foreach ($menuItems as $item) {
            $titleRaw = (string) ($item->title ?? $item->post_title ?? '');
            $urlRaw = (string) ($item->url ?? '');
            $key = self::claveItem($titleRaw, $urlRaw);
            if (!isset($conteoActual[$key])) {
                $conteoActual[$key] = 0;
            }
            $conteoActual[$key]++;
        }

        // 1) Eliminar ítems vacíos y excedentes de placeholders permitidos
        foreach ($menuItems as $item) {
            $titleRaw = (string) ($item->title ?? $item->post_title ?? '');
            $urlRaw = (string) ($item->url ?? '');
            if (trim($titleRaw) === '' || trim($urlRaw) === '') {
                wp_delete_post((int) $item->ID, true);
                continue;
            }
            $key = self::claveItem($titleRaw, $urlRaw);
            if (!array_key_exists($key, $permitidos)) {
                // Ítem no permitido (si llegamos aquí, asumimos que no hay personalización real; lo dejamos)
                continue;
            }
            $requeridos = (int) $permitidos[$key];
            if ($conteoActual[$key] > $requeridos) {
                // eliminar excedentes hasta igualar el requerido
                wp_delete_post((int) $item->ID, true);
                $conteoActual[$key]--;
            }
        }

        // 2) Agregar faltantes hasta alcanzar la cantidad requerida de cada placeholder
        foreach ($permitidos as $key => $requeridos) {
            $faltantes = max(0, (int) $requeridos - (int) ($conteoActual[$key] ?? 0));
            if ($faltantes <= 0) {
                continue;
            }

            // Obtener título (con mayúsculas originales) y url desde el seed
            $def = $keyToDef[$key] ?? null;
            if (!$def) {
                continue;
            }
            $title = (string) $def['title'];
            $url = (string) $def['url'];
            for ($i = 0; $i < $faltantes; $i++) {
                wp_update_nav_menu_item($menuId, 0, [
                    'menu-item-type'      => 'custom',
                    'menu-item-object'    => 'custom',
                    'menu-item-title'     => $title,
                    'menu-item-attr-title'=> $title,
                    'menu-item-url'       => $url,
                    'menu-item-status'    => 'publish',
                ]);
            }
        }

        // 2.b) Reparar ítems permitidos existentes con título vacío
        $actual = wp_get_nav_menu_items($menuId);
        if (!is_array($actual)) {
            $actual = [];
        }
        foreach ($actual as $item) {
            $titleRaw = (string) ($item->title ?? $item->post_title ?? '');
            $urlRaw = (string) ($item->url ?? '');
            $key = self::claveItem($titleRaw, $urlRaw);
            if (isset($keyToDef[$key]) && trim($titleRaw) === '') {
                $def = $keyToDef[$key];
                wp_update_nav_menu_item($menuId, (int) $item->ID, [
                    'menu-item-type'       => 'custom',
                    'menu-item-object'     => 'custom',
                    'menu-item-title'      => (string) $def['title'],
                    'menu-item-attr-title' => (string) $def['title'],
                    'menu-item-url'        => (string) $def['url'],
                    'menu-item-status'     => 'publish',
                ]);
            }
        }

        // 3) Ordenar exactamente como el seed
        $pos = 1;
        foreach ($seed as $def) {
            $title = $def['title'];
            $url = $def['url'];
            $keyObjetivo = self::claveItem($title, $url);

            // Obtener ítems actuales otra vez para reflejar altas/bajas
            $actual = wp_get_nav_menu_items($menuId);
            if (!is_array($actual)) {
                $actual = [];
            }

            foreach ($actual as $item) {
                $titleRaw = (string) ($item->title ?? $item->post_title ?? '');
                $urlRaw = (string) ($item->url ?? '');
                $key = self::claveItem($titleRaw, $urlRaw);
                if ($key === $keyObjetivo) {
                    // Actualizar con todos los campos para no perder título/URL al reordenar
                    wp_update_nav_menu_item($menuId, (int) $item->ID, [
                        'menu-item-type'       => 'custom',
                        'menu-item-object'     => 'custom',
                        'menu-item-title'      => $title,
                        'menu-item-attr-title' => $title,
                        'menu-item-url'        => $url,
                        'menu-item-status'     => 'publish',
                        'menu-item-position'   => $pos,
                    ]);
                    $pos++;
                    // Quitar uno de este tipo para no reordenar el mismo ítem múltiples veces
                    // Disminuir temporalmente el conteo para evitar volver a elegirlo
                    if (!isset($conteoActual[$key])) {
                        $conteoActual[$key] = 0;
                    }
                    $conteoActual[$key]--;
                    if ($conteoActual[$key] < 0) {
                        $conteoActual[$key] = 0;
                    }
                    // Romper para continuar con el siguiente elemento del seed
                    break;
                }
            }
        }
    }

    private static function construirMulticonjuntoPermitidos(array $seed): array
    {
        $map = [];
        foreach ($seed as $def) {
            $key = self::claveItem((string) $def['title'], (string) $def['url']);
            if (!isset($map[$key])) {
                $map[$key] = 0;
            }
            $map[$key]++;
        }
        return $map;
    }

    private static function claveItem(string $title, string $url): string
    {
        $t = strtolower(trim($title));
        $u = self::normalizarUrl($url);
        return $t . '|' . $u;
    }

    private static function descomponerClave(string $key): array
    {
        $parts = explode('|', $key, 2);
        $title = $parts[0] ?? '';
        $url = $parts[1] ?? '';
        return [$title, $url];
    }

    private static function normalizarUrl(string $url): string
    {
        // Normalizar URLs para comparación (quitar slash final, minúsculas para esquemas, etc.)
        if ($url === '#') {
            return '#';
        }
        $normalized = untrailingslashit($url);
        return $normalized !== '' ? $normalized : '/';
    }

    private static function asignarUbicacion(int $menuId): void
    {
        $ubicaciones = get_theme_mod('nav_menu_locations');
        if (!is_array($ubicaciones)) {
            $ubicaciones = [];
        }
        $ubicaciones[self::UBICACION_MENU_PRINCIPAL] = $menuId;
        set_theme_mod('nav_menu_locations', $ubicaciones);
    }

    // ==========================
    // Soporte de menú definido por código (modo dev)
    // ==========================

    private static function esModoDesarrollo(): bool
    {
        // Usar constante LOCAL definida en App/Config/environment.php
        return defined('LOCAL') && LOCAL === true;
    }

    /**
     * Carga la definición del menú desde App/Content/menu.php.
     * Debe devolver un array de items con estructura:
     * [ [ 'title' => string, 'url' => string, 'children' => [] ], ... ]
     */
    private static function cargarDefinicionMenuDesdeCodigo(): ?array
    {
        $ruta = get_template_directory() . self::RUTA_MENU_CODIGO;
        if (!file_exists($ruta)) {
            return null;
        }

        $menu = require $ruta;
        if (!is_array($menu)) {
            return null;
        }

        // Normalizar estructura: asegurar claves y tipos
        $normalizado = [];
        foreach ($menu as $item) {
            $titulo = (string) ($item['title'] ?? '');
            $url = (string) ($item['url'] ?? '#');
            $children = $item['children'] ?? [];
            if ($titulo === '') {
                continue;
            }
            if (!is_array($children)) {
                $children = [];
            }
            $normalizado[] = [
                'title' => $titulo,
                'url' => $url,
                'children' => self::normalizarChildren($children),
            ];
        }
        return $normalizado;
    }

    private static function normalizarChildren(array $children): array
    {
        $out = [];
        foreach ($children as $child) {
            $titulo = (string) ($child['title'] ?? '');
            $url = (string) ($child['url'] ?? '#');
            $grandChildren = $child['children'] ?? [];
            if ($titulo === '') {
                continue;
            }
            if (!is_array($grandChildren)) {
                $grandChildren = [];
            }
            $out[] = [
                'title' => $titulo,
                'url' => $url,
                'children' => self::normalizarChildren($grandChildren),
            ];
        }
        return $out;
    }

    /**
     * Reconstruye el menú completo (elimina ítems actuales y crea los del código, con jerarquía).
     */
    private static function reconstruirMenuDesdeCodigo(int $menuId, array $definicion): void
    {
        // Eliminar todos los ítems actuales del menú
        $existentes = wp_get_nav_menu_items($menuId);
        if (is_array($existentes)) {
            foreach ($existentes as $item) {
                wp_delete_post((int) $item->ID, true);
            }
        }

        // Crear recursivamente los ítems
        foreach ($definicion as $item) {
            $parentId = self::crearItemMenu($menuId, 0, $item['title'], $item['url']);
            if ($parentId && !empty($item['children'])) {
                self::crearHijosRecursivo($menuId, $parentId, $item['children']);
            }
        }
    }

    private static function crearHijosRecursivo(int $menuId, int $parentItemId, array $children): void
    {
        foreach ($children as $child) {
            $childId = self::crearItemMenu($menuId, $parentItemId, (string) $child['title'], (string) $child['url']);
            if ($childId && !empty($child['children'])) {
                self::crearHijosRecursivo($menuId, $childId, $child['children']);
            }
        }
    }

    /**
     * Crea un ítem en el menú y devuelve el ID del post creado (item de menú).
     */
    private static function crearItemMenu(int $menuId, int $parentItemId, string $title, string $url): ?int
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
        $itemId = wp_update_nav_menu_item($menuId, 0, $args);
        if (is_wp_error($itemId)) {
            return null;
        }
        return (int) $itemId;
    }

    private static function resolverUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || $url === '#') {
            return '#';
        }
        // Si empieza con '/', construir absoluta con home_url
        if (strpos($url, '/') === 0) {
            return home_url($url);
        }
        return $url;
    }
}