<?php
namespace Glory\Manager;

use Glory\Core\DefaultContentRegistry;
use Glory\Services\DefaultContentSynchronizer;

/**
 * Fachada para el sistema de gestión de contenido por defecto.
 *
 * Proporciona una API pública y estática simple para definir contenido y registrar
 * los procesos de sincronización, delegando toda la lógica compleja a las clases
 * especializadas (Registry, Synchronizer).
 */
class DefaultContentManager
{
    private static ?DefaultContentSynchronizer $sincronizadorInstancia = null;

    /**
     * Define el contenido por defecto para un tipo de post.
     * Delega el almacenamiento de la definición a DefaultContentRegistry.
     *
     * @param string $tipoPost Slug del tipo de post.
     * @param array $postsDefault Array de definiciones de posts.
     * @param string $modoActualizacion 'none', 'force', o 'smart'.
     * @param bool $permitirEliminacion Si se deben eliminar posts obsoletos.
     */
    public static function define(string $tipoPost, array $postsDefault, string $modoActualizacion = 'smart', bool $permitirEliminacion = false): void
    {
        DefaultContentRegistry::define($tipoPost, $postsDefault, $modoActualizacion, $permitirEliminacion);
    }

    /**
     * Registra los hooks necesarios para la sincronización de contenido.
     */
    public static function register(): void
    {
        // Engancha el proceso principal al hook 'init' con prioridad 20.
        add_action('init', [self::class, 'procesarDefinicionesYRegistrarHooks'], 20);
    }

    /**
     * Helper para construir definiciones de posts de ejemplo.
     * - $titles: array de títulos (slugs se derivan como kebab-case con prefijo)
     * - $paragraphs: array de párrafos (se seleccionan 4 aleatorios)
     * - $options: ['aliasImagenes'=>'colors','minBytes'=>103424]
     */
    public static function buildSamplePosts(array $titles, array $paragraphs, array $options = []): array
    {
        $alias = isset($options['aliasImagenes']) ? (string) $options['aliasImagenes'] : 'colors';
        $minBytes = isset($options['minBytes']) ? (int) $options['minBytes'] : 0;

        $pool = \Glory\Utility\AssetsUtility::listImagesForAliasWithMinSize($alias, $minBytes);
        if (empty($pool)) {
            $pool = \Glory\Utility\AssetsUtility::listImagesForAlias($alias);
        }

        $defs = [];
        $idx = 0;
        foreach ($titles as $title) {
            $idx++;
            $slug = 'sample-' . trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $title)), '-');

            // Selección determinista de imágenes internas y destacada basada en el slug
            $internas = [];
            $featured = null;
            if (!empty($pool)) {
                // Ordenar el pool por un peso determinista usando crc32(slug|nombre)
                $weights = [];
                foreach ($pool as $imgName) {
                    $weights[$imgName] = crc32($slug . '|' . $imgName);
                }
                asort($weights);
                $ordered = array_keys($weights);
                $internas = array_slice($ordered, 0, max(1, min(2, count($ordered))));
                $featured = $ordered[abs(crc32($slug . '|featured')) % count($ordered)] ?? null;
            }

            // Selección determinista de 4 párrafos
            $contentParts = [];
            $totalP = count($paragraphs);
            $pickIdxs = [];
            if ($totalP >= 1) {
                $need = min(4, $totalP);
                for ($i = 0; $i < $need; $i++) {
                    $candidate = (abs(crc32($slug . '|p|' . (string) $i)) + $i * 7) % $totalP;
                    // Asegurar unicidad avanzando circularmente
                    $guard = 0;
                    while (in_array($candidate, $pickIdxs, true) && $guard < $totalP) {
                        $candidate = ($candidate + 1) % $totalP;
                        $guard++;
                    }
                    $pickIdxs[] = $candidate;
                }
            }

            $insertadas = 0;
            foreach ($pickIdxs as $k) {
                $contentParts[] = '<p>' . esc_html((string) $paragraphs[$k]) . '</p>';
                if ($insertadas < count($internas)) {
                    $img = $internas[$insertadas];
                    $url = \Glory\Utility\AssetsUtility::imagenUrl($alias . '::' . $img);
                    if (is_string($url) && $url !== '') {
                        $contentParts[] = '<figure class="alignnone"><img src="' . esc_url($url) . '" alt="sample image ' . esc_attr((string) $idx) . '"></figure>';
                        $insertadas++;
                    }
                }
            }

            $defs[] = [
                'slugDefault' => $slug,
                'titulo'      => (string) $title,
                'contenido'   => implode('', $contentParts),
                'estado'      => 'publish',
                'extracto'    => 'Sample reflections on color, form, and contemporary art.',
                'imagenDestacadaAsset' => $featured ? ($alias . '::' . $featured) : '',
            ];
        }
        return $defs;
    }

    /**
     * Instancia el sincronizador, ejecuta la sincronización y registra los hooks
     * para la detección de ediciones manuales.
     * Este método es el callback para el hook 'init'.
     */
    public static function procesarDefinicionesYRegistrarHooks(): void
    {
        // Asegura que solo haya una instancia del sincronizador por petición (patrón Singleton).
        if (self::$sincronizadorInstancia === null) {
            self::$sincronizadorInstancia = new DefaultContentSynchronizer();
        }

        // No ejecutar sincronización automáticamente aquí.
        // La sincronización se ejecuta manualmente desde SyncManager o CLI.

        // Después de la sincronización, registra los hooks 'save_post_{tipo}' para detectar
        // ediciones manuales, pero solo para los tipos de post que gestionamos.
        $definiciones = DefaultContentRegistry::getDefiniciones();
        foreach (array_keys($definiciones) as $tipoPost) {
            add_action('save_post_' . $tipoPost, [self::class, 'detectarEdicionManualHook'], 99, 3);
        }
    }
    
    /**
     * Método intermediario que llama al método de instancia en el sincronizador.
     * Este es el callback real para el hook 'save_post_*'.
     */
    public static function detectarEdicionManualHook(int $idPost, \WP_Post $objetoPost, bool $esActualizacion): void
    {
        if (self::$sincronizadorInstancia === null) {
            // Por si el hook se dispara en un contexto donde 'init' no se ha ejecutado.
            self::$sincronizadorInstancia = new DefaultContentSynchronizer();
        }
        self::$sincronizadorInstancia->detectarEdicionManual($idPost, $objetoPost, $esActualizacion);
    }
}