<?php

namespace Glory\Manager;

/*
 * Normalización de placeholders en menús de WordPress.
 * Se encarga de mantener los ítems placeholder sincronizados
 * con la definición seed por defecto.
 */
class MenuNormalizer
{
    /**
     * Verifica si el array de ítems contiene algún elemento que NO sea un placeholder del seed.
     */
    public static function tieneItemsNoPlaceholders(array $menuItems): bool
    {
        if (empty($menuItems)) {
            return false;
        }

        $seed = MenuDefinition::obtenerSeedPorDefecto();
        $permitidos = self::construirMulticonjuntoPermitidos($seed);

        foreach ($menuItems as $item) {
            $titleRaw = (string) ($item->title ?? $item->post_title ?? '');
            $urlRaw = (string) ($item->url ?? '');
            if (trim($titleRaw) === '' || trim($urlRaw) === '') {
                continue;
            }
            $key = self::claveItem($titleRaw, $urlRaw);
            if (!array_key_exists($key, $permitidos)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normaliza el menú para que contenga exactamente los placeholders (sin duplicados extra) y ordenados.
     */
    public static function normalizarPlaceholders(int $menuId, array $menuItems): void
    {
        $seed = MenuDefinition::obtenerSeedPorDefecto();
        $permitidos = self::construirMulticonjuntoPermitidos($seed);
        $keyToDef = [];
        foreach ($seed as $def) {
            $keyToDef[self::claveItem($def['title'], $def['url'])] = $def;
        }

        /* Conteo actual */
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

        /* 1) Eliminar ítems vacíos y excedentes de placeholders permitidos */
        foreach ($menuItems as $item) {
            $titleRaw = (string) ($item->title ?? $item->post_title ?? '');
            $urlRaw = (string) ($item->url ?? '');
            if (trim($titleRaw) === '' || trim($urlRaw) === '') {
                wp_delete_post((int) $item->ID, true);
                continue;
            }
            $key = self::claveItem($titleRaw, $urlRaw);
            if (!array_key_exists($key, $permitidos)) {
                continue;
            }
            $requeridos = (int) $permitidos[$key];
            if ($conteoActual[$key] > $requeridos) {
                wp_delete_post((int) $item->ID, true);
                $conteoActual[$key]--;
            }
        }

        /* 2) Agregar faltantes hasta alcanzar la cantidad requerida */
        foreach ($permitidos as $key => $requeridos) {
            $faltantes = max(0, (int) $requeridos - (int) ($conteoActual[$key] ?? 0));
            if ($faltantes <= 0) {
                continue;
            }

            $def = $keyToDef[$key] ?? null;
            if (!$def) {
                continue;
            }
            $title = (string) $def['title'];
            $url = (string) $def['url'];
            for ($i = 0; $i < $faltantes; $i++) {
                wp_update_nav_menu_item($menuId, 0, [
                    'menu-item-type'       => 'custom',
                    'menu-item-object'     => 'custom',
                    'menu-item-title'      => $title,
                    'menu-item-attr-title' => $title,
                    'menu-item-url'        => $url,
                    'menu-item-status'     => 'publish',
                ]);
            }
        }

        /* 2.b) Reparar ítems permitidos existentes con título vacío */
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

        /* 3) Ordenar exactamente como el seed */
        /* Obtener items una sola vez fuera del bucle para evitar N queries */
        $actual = wp_get_nav_menu_items($menuId);
        if (!is_array($actual)) {
            $actual = [];
        }
        $pos = 1;
        foreach ($seed as $def) {
            $title = $def['title'];
            $url = $def['url'];
            $keyObjetivo = self::claveItem($title, $url);

            foreach ($actual as $item) {
                $titleRaw = (string) ($item->title ?? $item->post_title ?? '');
                $urlRaw = (string) ($item->url ?? '');
                $key = self::claveItem($titleRaw, $urlRaw);
                if ($key === $keyObjetivo) {
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
                    if (!isset($conteoActual[$key])) {
                        $conteoActual[$key] = 0;
                    }
                    $conteoActual[$key]--;
                    if ($conteoActual[$key] < 0) {
                        $conteoActual[$key] = 0;
                    }
                    break;
                }
            }
        }
    }

    /**
     * Construye un multiconjunto (clave => cantidad) a partir del seed.
     */
    public static function construirMulticonjuntoPermitidos(array $seed): array
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

    /**
     * Genera una clave normalizada título|url para comparación.
     */
    public static function claveItem(string $title, string $url): string
    {
        $t = strtolower(trim($title));
        $u = self::normalizarUrl($url);
        return $t . '|' . $u;
    }

    /**
     * Descompone una clave título|url en sus partes.
     */
    public static function descomponerClave(string $key): array
    {
        $parts = explode('|', $key, 2);
        $title = $parts[0] ?? '';
        $url = $parts[1] ?? '';
        return [$title, $url];
    }

    /**
     * Normaliza una URL para comparación (quitar slash final, etc.).
     */
    public static function normalizarUrl(string $url): string
    {
        if ($url === '#') {
            return '#';
        }
        $normalized = untrailingslashit($url);
        return $normalized !== '' ? $normalized : '/';
    }
}
