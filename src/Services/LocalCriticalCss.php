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

        $cmd = escapeshellcmd($node)
            . ' ' . escapeshellarg($script)
            . ' --url ' . escapeshellarg($url)
            . ' --cssDir ' . escapeshellarg($cssDir)
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


