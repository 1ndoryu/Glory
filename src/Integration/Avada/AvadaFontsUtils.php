<?php

namespace Glory\Integration\Avada;

class AvadaFontsUtils
{
    /**
     * Construye CSS @font-face a partir del mapa de fuentes detectadas.
     *
     * @param array<string,array<string,string>> $fonts
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

        foreach ($fonts as $family => $sources) {
            $hasFile = false;
            foreach (['woff2','woff','ttf','eot','svg','otf'] as $ext) {
                if (!empty($sources[$ext])) { $hasFile = true; break; }
            }
            if (!$hasFile) { continue; }

            $first = true;
            $fontFace .= '@font-face{';
            $fontFace .= 'font-family:' . (false !== strpos($family, ' ') ? '"' . $family . '"' : $family) . ';';
            $fontFace .= 'src:';
            if (!empty($sources['woff2'])) { $fontFace .= 'url("' . $sources['woff2'] . '") format("woff2")'; $first = false; }
            if (!empty($sources['woff']))  { $fontFace .= ($first ? '' : ',') . 'url("' . $sources['woff'] . '") format("woff")'; $first = false; }
            if (!empty($sources['ttf']))   { $fontFace .= ($first ? '' : ',') . 'url("' . $sources['ttf'] . '") format("truetype")'; $first = false; }
            if (!empty($sources['eot']))   { $fontFace .= ($first ? '' : ',') . 'url("' . $sources['eot'] . '?#iefix") format("embedded-opentype")'; $first = false; }
            if (!empty($sources['svg']))   { $fontFace .= ($first ? '' : ',') . 'url("' . $sources['svg'] . '") format("svg")'; $first = false; }
            if (!empty($sources['otf']))   { $fontFace .= ($first ? '' : ',') . 'url("' . $sources['otf'] . '") format("opentype")'; $first = false; }
            $fontFace .= ';font-weight:normal;font-style:normal;font-display:' . $display . ';}';
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
     * @return array<string,array<string,string>>
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
                            if (!isset($result[$family][$ext])) {
                                $result[$family][$ext] = self::filePathToUrl($file, $basePath, $baseUrl);
                            }
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
                    if (!isset($result[$family][$ext])) {
                        $result[$family][$ext] = self::filePathToUrl($file, $basePath, $baseUrl);
                    }
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
}


