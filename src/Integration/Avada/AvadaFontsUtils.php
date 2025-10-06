<?php

namespace Glory\Integration\Avada;

class AvadaFontsUtils
{
    /**
     * Construye CSS @font-face a partir del mapa de fuentes detectadas.
     *
     * @param array<string,array<int,string>> $fonts
     * @return string
     */
    public static function buildFontFaceCss(array $fonts): string
    {
        $fontFace = '';
        $display  = 'swap';
        if ( function_exists('Avada') && is_object(Avada()) && isset(Avada()->settings) && is_object(Avada()->settings) && method_exists(Avada()->settings, 'get') ) {
            $val = (string) Avada()->settings->get('font_face_display');
            $display = ('block' === $val) ? 'block' : 'swap';
        }

        foreach ($fonts as $family => $files) {
            if (empty($files)) { continue; }

            // Agrupar por peso inferido y acumular formatos por peso.
            $byWeight = [];
            foreach ($files as $url) {
                $weight = self::inferWeightFromFilename($url);
                $byWeight[$weight] = $byWeight[$weight] ?? [];
                $byWeight[$weight][] = $url;
            }

            foreach ($byWeight as $weight => $urls) {
                // Construir lista de src combinando múltiples formatos del mismo peso.
                $srcParts = [];
                foreach ($urls as $url) {
                    $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                    $srcParts[] = 'url("' . $url . '") format("' . self::extToFormat($ext) . '")';
                }
                if (empty($srcParts)) { continue; }

                $familyWithWeight = $family . '-' . (is_numeric($weight) ? $weight : 'normal');
                $fontFace .= '@font-face{';
                $fontFace .= 'font-family:' . (false !== strpos($familyWithWeight, ' ') ? '"' . $familyWithWeight . '"' : $familyWithWeight) . ';';
                $fontFace .= 'src:' . implode(',', $srcParts) . ';';
                // Dejamos normal para que Avada seleccione por familia (variant 400) sin conflicto.
                $fontFace .= 'font-weight:normal;font-style:normal;font-display:' . $display . ';';
                $fontFace .= '}';
            }
        }

        return $fontFace;
    }

    /**
     * Retorna lista de familias detectadas.
     *
     * @return array<int,string>
     */
    public static function discoverFontFamilies(): array
    {
        $fonts = self::discoverFonts();
        return array_keys($fonts);
    }

    /**
     * Escanea la(s) carpeta(s) App/assets/fonts para detectar fuentes.
     *
     * Estructura devuelta:
     *  - Clave: nombre de familia
     *  - Valor: array de URLs de archivos de fuente para esa familia
     *
     * @return array<string,array<int,string>>
     */
    public static function discoverFonts(): array
    {
        $result = [];

        $paths = self::getFontsDirectories();
        foreach ($paths as $pathInfo) {
            $basePath = $pathInfo['path'];
            $baseUrl  = $pathInfo['url'];
            if (!is_dir($basePath)) { continue; }

            // Subdirectorios => familias.
            $dirs = glob($basePath . '/*', GLOB_ONLYDIR);
            if (is_array($dirs) && !empty($dirs)) {
                foreach ($dirs as $dir) {
                    $family = basename($dir);
                    $result[$family] = $result[$family] ?? [];
                    $files = glob($dir . '/*.*');
                    if (is_array($files)) {
                        foreach ($files as $file) {
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            if (!in_array($ext, ['woff2','woff','ttf','eot','svg','otf'], true)) { continue; }
                            $url = self::filePathToUrl($file, $basePath, $baseUrl);
                            $result[$family][] = $url;
                        }
                    }
                }
            }

            // Archivos sueltos en la raíz de fonts/.
            $rootFiles = glob($basePath . '/*.*');
            if (is_array($rootFiles)) {
                foreach ($rootFiles as $file) {
                    if (is_dir($file)) { continue; }
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['woff2','woff','ttf','eot','svg','otf'], true)) { continue; }
                    $family = pathinfo($file, PATHINFO_FILENAME);
                    $result[$family] = $result[$family] ?? [];
                    $result[$family][] = self::filePathToUrl($file, $basePath, $baseUrl);
                }
            }
        }

        return $result;
    }

    /** @return array<int,array{path:string,url:string}> */
    private static function getFontsDirectories(): array
    {
        $dirs = [];
        $childPath = trailingslashit(get_stylesheet_directory()) . 'App/assets/fonts';
        $childUrl  = trailingslashit(get_stylesheet_directory_uri()) . 'App/assets/fonts';
        $dirs[] = [ 'path' => wp_normalize_path($childPath), 'url' => $childUrl ];
        $parentPath = trailingslashit(get_template_directory()) . 'App/assets/fonts';
        $parentUrl  = trailingslashit(get_template_directory_uri()) . 'App/assets/fonts';
        $dirs[] = [ 'path' => wp_normalize_path($parentPath), 'url' => $parentUrl ];
        return $dirs;
    }

    private static function filePathToUrl(string $file, string $basePath, string $baseUrl): string
    {
        $relative = ltrim(str_replace(wp_normalize_path($basePath), '', wp_normalize_path($file)), '/\\');
        return rtrim($baseUrl, '/\\') . '/' . str_replace('\\', '/', $relative);
    }

    private static function extToFormat(string $ext): string
    {
        $map = [
            'woff2' => 'woff2',
            'woff'  => 'woff',
            'ttf'   => 'truetype',
            'eot'   => 'embedded-opentype',
            'svg'   => 'svg',
            'otf'   => 'opentype',
        ];
        return $map[strtolower($ext)] ?? 'woff2';
    }

    public static function inferWeightFromFilename(string $url)
    {
        $filename = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? $url, PATHINFO_FILENAME));
        if (preg_match('/(?:_|-)(\d{3})(?:[^0-9]|$)/', $filename, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/extrabold|extra[-_]?bold/', $filename)) { return 800; }
        if (preg_match('/bold/', $filename)) { return 700; }
        if (preg_match('/semibold|semi[-_]?bold/', $filename)) { return 600; }
        if (preg_match('/medium/', $filename)) { return 500; }
        if (preg_match('/regular|book|normal/', $filename)) { return 400; }
        if (preg_match('/light/', $filename)) { return 300; }
        return 'normal';
    }
}


