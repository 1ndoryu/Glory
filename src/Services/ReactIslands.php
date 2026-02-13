<?php

namespace Glory\Services;

/**
 * ReactIslands - Sistema opcional de integracion React en WordPress
 * 
 * IMPORTANTE: Este sistema es completamente OPCIONAL.
 * Los scripts de React SOLO se cargan si hay islas registradas en la pagina.
 * Si no se usa, no afecta al rendimiento del sitio.
 * 
 * COMPATIBILIDAD PHP PURO:
 * - Si no se llama a render() o register(), NINGUN asset de React se carga.
 * - Glory funciona 100% como tema WordPress tradicional.
 * - No se requiere Node.js ni npm para proyectos que no usen React.
 * 
 * MODOS DE USO:
 * 1. SSG (100% React): Paginas completas en React con pre-render estatico.
 * 2. Islands (Hibrido): PHP para contenido, React para widgets interactivos.
 * 3. PHP Puro: Sin React, tema WordPress clasico.
 * 
 * Uso basico:
 * 1. Registrar una isla: ReactIslands::register('NombreIsla', ['prop1' => 'valor'])
 * 2. Renderizar el contenedor: ReactIslands::render('NombreIsla')
 * 
 * Los scripts se encolan automaticamente solo si hay islas registradas.
 * 
 * @see SSR_ARCHITECTURE.md para detalles de la arquitectura SSG
 * @see react-glory.md para guia de uso
 */
class ReactIslands
{
    // Islas registradas en la pagina actual
    private static array $registeredIslands = [];

    // Estado de inicializacion
    private static bool $initialized = false;

    // Modo desarrollo (detecta si Vite dev server esta corriendo)
    private static ?bool $devMode = null;

    // Puerto del servidor de desarrollo Vite
    private const DEV_SERVER_PORT = 5173;

    /**
     * Inicializa el sistema de islas React
     * Se llama automaticamente al registrar la primera isla
     */
    private static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Encolar scripts en el footer (solo si hay islas)
        add_action('wp_footer', [self::class, 'enqueueScripts'], 100);

        self::$initialized = true;
    }

    /**
     * Detecta si estamos en modo desarrollo
     * Verifica si el servidor Vite esta corriendo
     */
    private static function isDevMode(): bool
    {
        if (self::$devMode !== null) {
            return self::$devMode;
        }

        // Solo verificar en entornos locales
        if (!self::isLocalEnvironment()) {
            self::$devMode = false;
            return false;
        }

        /* Cachear resultado con transient para evitar HTTP check en cada request (30s) */
        $cached = get_transient('glory_vite_dev_mode');
        if ($cached !== false) {
            self::$devMode = ($cached === '1');
            return self::$devMode;
        }

        // Verificar si el dev server esta respondiendo
        $devServerUrl = 'http://localhost:' . self::DEV_SERVER_PORT;

        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        $response = @file_get_contents($devServerUrl, false, stream_context_create([
            'http' => [
                'timeout' => 0.5,
                'ignore_errors' => true,
            ],
        ]));

        self::$devMode = $response !== false;
        set_transient('glory_vite_dev_mode', self::$devMode ? '1' : '0', 30);

        return self::$devMode;
    }

    /**
     * Determina si estamos en un entorno local
     */
    private static function isLocalEnvironment(): bool
    {
        $localHosts = ['localhost', '127.0.0.1', '::1'];
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';

        // Detectar Local by Flywheel (.local domains)
        if (str_contains($serverName, '.local')) {
            return true;
        }

        return in_array($serverName, $localHosts) || in_array($serverAddr, $localHosts);
    }

    /**
     * Obtiene la URL base para los assets de React
     */
    private static function getAssetsUrl(): string
    {
        if (self::isDevMode()) {
            return 'http://localhost:' . self::DEV_SERVER_PORT;
        }

        return get_template_directory_uri() . '/Glory/assets/react/dist';
    }

    /**
     * Obtiene la ruta al directorio de React
     */
    private static function getReactPath(): string
    {
        return get_template_directory() . '/Glory/assets/react';
    }

    /**
     * Registra una isla React para ser renderizada
     * 
     * @param string $islandName Nombre del componente React (debe coincidir con islandComponents en main.tsx)
     * @param array $props Props a pasar al componente
     * @param string|null $containerId ID unico para el contenedor (auto-generado si no se proporciona)
     * @return string ID del contenedor
     */
    public static function register(string $islandName, array $props = [], ?string $containerId = null): string
    {
        self::initialize();

        $containerId = $containerId ?? 'react-island-' . sanitize_title($islandName) . '-' . uniqid();

        self::$registeredIslands[$containerId] = [
            'name' => $islandName,
            'props' => $props,
        ];

        return $containerId;
    }

    /**
     * Intenta obtener el contenido HTML pre-renderizado (SSG)
     * Busca archivos generados en Glory/assets/react/dist/ssg/
     * 
     * @param string $islandName Nombre de la isla
     * @return string HTML pre-renderizado o string vacio si no existe
     */
    private static function getSSRContent(string $islandName): string
    {
        // En modo dev, no usamos SSG para evitar contenido stale
        if (self::isDevMode()) {
            return '';
        }

        // Buscar archivo HTML generado para esta isla
        $ssgPath = self::getReactPath() . '/dist/ssg/' . $islandName . '.html';

        if (file_exists($ssgPath)) {
            return file_get_contents($ssgPath);
        }

        return '';
    }

    /**
     * Renderiza el contenedor para una isla React
     * 
     * Flujo SSG:
     * 1. Si existe HTML pre-renderizado en dist/ssg/, lo usa como contenido inicial
     * 2. Marca el contenedor con data-hydrate="true" para que React hidrate (no reemplace)
     * 3. Los props frescos vienen de PHP via data-props
     * 
     * @param string $islandName Nombre del componente
     * @param array $props Props a pasar al componente
     * @param string $fallbackContent HTML a mostrar antes de que React hidrate (SEO/noscript)
     * @param array $containerAttrs Atributos adicionales para el contenedor
     * @return string HTML del contenedor
     */
    public static function render(
        string $islandName,
        array $props = [],
        string $fallbackContent = '',
        array $containerAttrs = []
    ): string {
        $containerId = self::register($islandName, $props);

        $propsJson = !empty($props) ? htmlspecialchars(json_encode($props), ENT_QUOTES, 'UTF-8') : '';

        // Construir atributos del contenedor
        $attrs = array_merge([
            'id' => $containerId,
            'data-island' => $islandName,
        ], $containerAttrs);

        if ($propsJson) {
            $attrs['data-props'] = $propsJson;
        }

        // Intentar cargar contenido SSG si no hay fallback manual
        if (empty($fallbackContent)) {
            $fallbackContent = self::getSSRContent($islandName);

            // Si encontramos contenido SSG, marcar para hidratacion
            if (!empty($fallbackContent)) {
                $attrs['data-hydrate'] = 'true';
            }
        }

        $attrsString = '';
        foreach ($attrs as $key => $value) {
            $attrsString .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }

        // Si no hay contenido fallback, agregar un placeholder invisible
        // Esto evita que el tema filtre contenedores "vacios"
        if (empty($fallbackContent)) {
            $fallbackContent = '<!-- react-island-loading -->';
        }

        return sprintf(
            '<div%s>%s</div>',
            $attrsString,
            $fallbackContent
        );
    }

    /**
     * Encola los scripts de React
     * Solo se ejecuta si hay islas registradas
     */
    public static function enqueueScripts(): void
    {
        // No cargar si no hay islas registradas
        if (empty(self::$registeredIslands)) {
            return;
        }

        // Inyectar contexto global via filtro (Agnóstico)
        $context = apply_filters('glory_react_context', []);

        if (!empty($context)) {
            echo '<script id="glory-context">';
            echo 'window.GLORY_CONTEXT = ' . wp_json_encode($context, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';';
            echo '</script>' . PHP_EOL;
        }

        $assetsUrl = self::getAssetsUrl();

        if (self::isDevMode()) {
            // Modo desarrollo: cargar desde Vite dev server
            self::enqueueDevScripts($assetsUrl);
        } else {
            // Modo produccion: cargar desde bundles compilados
            self::enqueueProdScripts($assetsUrl);
        }
    }

    /**
     * Encola scripts en modo desarrollo (Vite HMR)
     */
    private static function enqueueDevScripts(string $assetsUrl): void
    {
        // React Refresh Preamble - necesario para @vitejs/plugin-react
        // Debe cargarse ANTES de cualquier otro script
        echo '<script type="module">';
        echo 'import RefreshRuntime from "' . esc_url($assetsUrl) . '/@react-refresh";';
        echo 'RefreshRuntime.injectIntoGlobalHook(window);';
        echo 'window.$RefreshReg$ = () => {};';
        echo 'window.$RefreshSig$ = () => (type) => type;';
        echo 'window.__vite_plugin_react_preamble_installed__ = true;';
        echo '</script>' . PHP_EOL;

        // Vite client para HMR
        printf(
            '<script type="module" src="%s/@vite/client"></script>' . PHP_EOL,
            esc_url($assetsUrl)
        );

        // Entry point principal
        printf(
            '<script type="module" src="%s/src/main.tsx"></script>' . PHP_EOL,
            esc_url($assetsUrl)
        );
    }

    /**
     * Encola scripts en modo produccion
     */
    private static function enqueueProdScripts(string $assetsUrl): void
    {
        $manifestPath = self::getReactPath() . '/dist/.vite/manifest.json';

        if (!file_exists($manifestPath)) {
            // Fallback: intentar ruta alternativa del manifest
            $manifestPath = self::getReactPath() . '/dist/manifest.json';
        }

        if (!file_exists($manifestPath)) {
            if (current_user_can('manage_options')) {
                echo '<!-- Glory React: manifest.json no encontrado. Ejecuta "npm run build" en Glory/assets/react/ -->';
            }
            return;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!$manifest) {
            return;
        }

        // Buscar el entry point principal
        $mainEntry = $manifest['src/main.tsx'] ?? null;

        if (!$mainEntry) {
            return;
        }

        // Cargar CSS - BLOQUEANTE para SSG (el HTML ya esta renderizado)
        // Esto asegura que los estilos esten disponibles antes de mostrar el contenido
        if (!empty($mainEntry['css'])) {
            foreach ($mainEntry['css'] as $cssFile) {
                $cssUrl = esc_url($assetsUrl) . '/' . esc_attr($cssFile);
                printf(
                    '<link rel="stylesheet" href="%s">' . PHP_EOL,
                    $cssUrl
                );
            }
        }

        // Cargar el script principal
        printf(
            '<script type="module" src="%s/%s"></script>' . PHP_EOL,
            esc_url($assetsUrl),
            esc_attr($mainEntry['file'])
        );
    }

    /**
     * Verifica si hay islas registradas
     */
    public static function hasIslands(): bool
    {
        return !empty(self::$registeredIslands);
    }

    /**
     * Obtiene las islas registradas (para debug)
     */
    public static function getRegisteredIslands(): array
    {
        return self::$registeredIslands;
    }

    /**
     * Limpia las islas registradas (util para tests)
     */
    public static function reset(): void
    {
        self::$registeredIslands = [];
        self::$initialized = false;
        self::$devMode = null;
    }

}
