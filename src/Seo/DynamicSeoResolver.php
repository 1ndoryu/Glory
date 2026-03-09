<?php

namespace Glory\Seo;

/**
 * DynamicSeoResolver
 *
 * Framework-level hook que se registra en el action `wp` de WordPress
 * (despues de resolverRutaDinamica, antes de wp_head) para resolver
 * datos SEO dinamicos en paginas que sirven contenido variable bajo la
 * misma pagina WP padre (ej: /sample/{slug}, /perfil/{username}).
 *
 * La aplicacion registra resolvers via registerResolver() que reciben
 * la ruta actual y pueden poblar RuntimeSeoData con datos especificos.
 *
 * Flujo:
 *   1. App registra resolvers durante boot (ej: en pages.php o seo config)
 *   2. Hook `wp` (prioridad 5) dispara resolver()
 *   3. resolver() extrae la ruta, busca resolver registrado que coincida
 *   4. El resolver del app (callable) recibe el segmento dinamico y puebla RuntimeSeoData
 *   5. wp_head() se ejecuta despues — los renderers consultan RuntimeSeoData primero
 */
class DynamicSeoResolver
{
    /**
     * Resolvers registrados: ['prefijo' => callable]
     * El callable recibe (string $segmento): void y debe llamar RuntimeSeoData::set()
     *
     * @var array<string, callable>
     */
    private static array $resolvers = [];
    private static bool $hookRegistered = false;

    /**
     * Registra un resolver para un prefijo de ruta.
     *
     * @param string $routePrefix Primer segmento de la ruta (ej: 'sample', 'perfil')
     * @param callable $resolver Funcion que recibe el segmento dinamico y puebla RuntimeSeoData
     */
    public static function registerResolver(string $routePrefix, callable $resolver): void
    {
        self::$resolvers[$routePrefix] = $resolver;
    }

    /**
     * Registra el hook de WordPress. Debe llamarse desde SeoFrontendRenderer::register().
     */
    public static function register(): void
    {
        if (self::$hookRegistered) {
            return;
        }
        self::$hookRegistered = true;

        /* Prioridad 5: despues de forzarResolucionDinamica (prioridad 1), antes de wp_head */
        add_action('wp', [self::class, 'resolver'], 5);
    }

    /**
     * Logica principal: se ejecuta en hook `wp`.
     * Parsea la ruta, busca resolver registrado y lo ejecuta.
     */
    public static function resolver(): void
    {
        if (empty(self::$resolvers)) {
            return;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($requestUri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return;
        }

        $segments = explode('/', trim($path, '/'));
        if (count($segments) < 2) {
            return;
        }

        $prefix = $segments[0];
        $dynamicSegment = $segments[1] ?? '';

        /* Ignorar si el segmento dinamico esta vacio o tiene sub-segmentos */
        if ($dynamicSegment === '' || count($segments) > 2) {
            return;
        }

        if (!isset(self::$resolvers[$prefix])) {
            return;
        }

        try {
            call_user_func(self::$resolvers[$prefix], sanitize_text_field($dynamicSegment));
        } catch (\Throwable $e) {
            /* SEO no critico: loggear y continuar sin romper la pagina */
            if (class_exists(\Glory\Core\GloryLogger::class)) {
                \Glory\Core\GloryLogger::error(
                    "DynamicSeoResolver: Error resolviendo SEO para /{$prefix}/{$dynamicSegment}/: "
                    . $e->getMessage()
                );
            }
        }
    }

    /**
     * Limpia resolvers registrados (para testing).
     */
    public static function clearResolvers(): void
    {
        self::$resolvers = [];
    }
}
