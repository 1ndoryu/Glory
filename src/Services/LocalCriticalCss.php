<?php

namespace Glory\Services;

use Glory\Core\GloryLogger;

class LocalCriticalCss
{
    public static function generate(string $url): ?string
    {
        $themeDir = wp_normalize_path(get_template_directory());
        $script = $themeDir . '/Glory/tools/critical-css/generateCritical.js';
        if (!file_exists($script)) {
            GloryLogger::error('LocalCriticalCss: script no encontrado', ['path' => $script]);
            return null;
        }
        $node = $_ENV['GLORY_CRITICAL_CSS_NODE'] ?? getenv('GLORY_CRITICAL_CSS_NODE') ?: 'node';
        $cssDir = $themeDir . '/App/Assets/css';

        // Añadir parámetro para congelar la navegación/JS dinámico durante la generación
        // evitando cambios de URL que rompen Penthouse (PAGE_UNLOADED_DURING_EXECUTION).
        try {
            $parsed = wp_parse_url($url);
            $query = [];
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $query);
            }
            if (!isset($query['noAjax'])) {
                $query['noAjax'] = '1';
                $scheme   = $parsed['scheme'] ?? 'https';
                $host     = $parsed['host'] ?? '';
                $port     = isset($parsed['port']) ? (':' . $parsed['port']) : '';
                $path     = $parsed['path'] ?? '/';
                $newQuery = http_build_query($query, '', '&');
                $frag     = isset($parsed['fragment']) ? ('#' . $parsed['fragment']) : '';
                $url      = $scheme . '://' . $host . $port . $path . ($newQuery ? ('?' . $newQuery) : '') . $frag;
            }
        } catch (\Throwable $e) {
            // Si algo falla, continuar con la URL original
        }

        $cmd = escapeshellcmd($node)
            . ' ' . escapeshellarg($script)
            . ' --url ' . escapeshellarg($url)
            . ' --cssDir ' . escapeshellarg($cssDir)
            . ' --timeout 60000 --renderWait 800 --skipLoadAfter 20000'
            . ' 2>&1';

        $output = [];
        $rc = 0;
        @exec($cmd, $output, $rc);
        if ($rc !== 0) {
            GloryLogger::error('LocalCriticalCss: fallo ejecutando generador', [
                'rc' => $rc,
                'out' => implode("\n", $output),
            ]);
            return null;
        }
        $css = trim(implode("\n", $output));
        if ($css === '') {
            GloryLogger::error('LocalCriticalCss: CSS vacío generado');
            return null;
        }
        return $css;
    }
}


