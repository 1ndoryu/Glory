<?php

namespace Glory\Manager;

/*
 * Definiciones de menú basadas en código.
 * Contiene la lógica para cargar, normalizar y preparar las definiciones
 * de menús desde archivos PHP del tema.
 */
class MenuDefinition
{
    public const UBICACION_MENU_PRINCIPAL = 'main_navigation';
    private const RUTA_MENU_CODIGO = '/App/Content/menu.php';

    /**
     * Devuelve la definición de los ítems por defecto (seed).
     * Mantener el mismo contenido para que la normalización sea idempotente.
     * @return array<int, array{title:string,url:string}>
     */
    public static function obtenerSeedPorDefecto(): array
    {
        return [
            ['title' => 'Inicio',  'url' => home_url('/')],
            ['title' => 'example', 'url' => '#'],
            ['title' => 'example', 'url' => '#'],
            ['title' => 'example', 'url' => '#'],
            ['title' => 'example', 'url' => '#'],
        ];
    }

    /**
     * Genera una etiqueta legible para una ubicación de menú.
     */
    public static function labelParaUbicacion(string $location): string
    {
        if ($location === self::UBICACION_MENU_PRINCIPAL) {
            return __('Main Navigation', 'glory');
        }
        $label = str_replace(['_', '-'], ' ', $location);
        $label = ucwords($label);
        return $label;
    }

    /**
     * Determina si el entorno actual es de desarrollo.
     * Usa la constante LOCAL definida en App/Config/environment.php.
     */
    public static function esModoDesarrollo(): bool
    {
        return defined('LOCAL') && LOCAL === true;
    }

    /**
     * Carga la definición de menús desde App/Content/menu.php.
     * Soporta array numérico (menú único) o asociativo (múltiples menús por ubicación).
     * @return array<string, array>|null
     */
    public static function cargarDefinicionMenusDesdeCodigo(): ?array
    {
        $ruta = get_template_directory() . self::RUTA_MENU_CODIGO;
        if (!file_exists($ruta)) {
            return null;
        }
        $menu = require $ruta;
        if (!is_array($menu)) {
            return null;
        }

        /* Detectar si es array numérico (definición única) o asociativo (múltiples menús) */
        $isNumeric = array_keys($menu) === range(0, count($menu) - 1);
        if ($isNumeric) {
            $items = self::normalizarListaItems($menu);
            $map = [self::UBICACION_MENU_PRINCIPAL => $items];
        } else {
            $map = [];
            foreach ($menu as $location => $items) {
                if (!is_array($items)) {
                    continue;
                }
                $map[(string) $location] = self::normalizarListaItems($items);
            }
        }

        /* Derivar menús secundarios del principal si no se definieron explícitamente */
        if (isset($map[self::UBICACION_MENU_PRINCIPAL])) {
            $principal = $map[self::UBICACION_MENU_PRINCIPAL];
            if (!isset($map['brands_navigation'])) {
                $map['brands_navigation'] = self::extraerChildrenPorTitulo($principal, 'Marcas');
            }
            if (!isset($map['products_navigation'])) {
                $map['products_navigation'] = self::extraerChildrenPorTitulo($principal, 'Productos');
            }
        }

        return $map;
    }

    /**
     * Normaliza una lista plana de ítems de menú.
     */
    public static function normalizarListaItems(array $items): array
    {
        $normalizado = [];
        foreach ($items as $item) {
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

    /**
     * Normaliza recursivamente los hijos de un ítem de menú.
     */
    public static function normalizarChildren(array $children): array
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
     * Extrae los hijos del ítem cuyo título coincide con $tituloSeccion.
     */
    public static function extraerChildrenPorTitulo(array $lista, string $tituloSeccion): array
    {
        $needle = strtolower(trim($tituloSeccion));
        foreach ($lista as $item) {
            $t = strtolower(trim((string) ($item['title'] ?? '')));
            if ($t === $needle) {
                $children = $item['children'] ?? [];
                $salida = [];
                if (is_array($children)) {
                    foreach ($children as $child) {
                        $ct = (string) ($child['title'] ?? '');
                        $cu = (string) ($child['url'] ?? '#');
                        if ($ct !== '') {
                            $salida[] = ['title' => $ct, 'url' => $cu, 'children' => []];
                        }
                    }
                }
                return $salida;
            }
        }
        return [];
    }
}
