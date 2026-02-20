<?php

namespace Glory\Services;

/**
 * Carga de scripts React (dev/prod).
 * Extraído de ReactIslands para cumplir SRP — ReactIslands gestiona islas,
 * ReactAssetLoader gestiona el enqueue de assets.
 */
class ReactAssetLoader
{
    /**
     * Encola scripts en modo desarrollo (Vite HMR).
     */
    public static function enqueueDevScripts(string $assetsUrl): void
    {
        /* React Refresh Preamble — necesario para @vitejs/plugin-react */
        echo '<script type="module">';
        echo 'import RefreshRuntime from "' . esc_url($assetsUrl) . '/@react-refresh";';
        echo 'RefreshRuntime.injectIntoGlobalHook(window);';
        echo 'window.$RefreshReg$ = () => {};';
        echo 'window.$RefreshSig$ = () => (type) => type;';
        echo 'window.__vite_plugin_react_preamble_installed__ = true;';
        echo '</script>' . PHP_EOL;

        /* Vite client para HMR */
        printf(
            '<script type="module" src="%s/@vite/client"></script>' . PHP_EOL,
            esc_url($assetsUrl)
        );

        /* Entry point principal */
        printf(
            '<script type="module" src="%s/src/main.tsx"></script>' . PHP_EOL,
            esc_url($assetsUrl)
        );
    }

    /**
     * Encola scripts en modo producción.
     */
    public static function enqueueProdScripts(string $assetsUrl, string $reactPath): void
    {
        $manifestPath = $reactPath . '/dist/.vite/manifest.json';

        if (!file_exists($manifestPath)) {
            $manifestPath = $reactPath . '/dist/manifest.json';
        }

        if (!file_exists($manifestPath)) {
            if (current_user_can('manage_options')) {
                echo '<!-- Glory React: manifest.json no encontrado. Ejecuta "npm run build" en Glory/assets/react/ -->';
            }
            return;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!is_array($manifest)) {
            return;
        }

        $mainEntry = $manifest['src/main.tsx'] ?? null;

        if (!$mainEntry) {
            return;
        }

        /* CSS — bloqueante para SSG (el HTML ya está renderizado) */
        if (!empty($mainEntry['css'])) {
            foreach ($mainEntry['css'] as $cssFile) {
                $cssUrl = esc_url($assetsUrl) . '/' . esc_attr($cssFile);
                printf('<link rel="stylesheet" href="%s">' . PHP_EOL, $cssUrl);
            }
        }

        /* Script principal */
        printf(
            '<script type="module" src="%s/%s"></script>' . PHP_EOL,
            esc_url($assetsUrl),
            esc_attr($mainEntry['file'])
        );
    }
}
