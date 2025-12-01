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

    /**
     * Construye definiciones de posts desde arrays simples.
     * 
     * Permite crear contenido por defecto de forma declarativa y legible.
     * 
     * @param array $items Array de items. Cada item puede ser:
     *   - Array asociativo completo (se usa directo)
     *   - Array simple donde posición 0 = titulo, 1 = imagen, resto según config
     * 
     * @param array $config Configuración global:
     *   - 'alias' (string): Alias de imágenes (def: 'images')
     *   - 'parrafos' (array): Párrafos para el contenido
     *   - 'extracto' (string): Extracto por defecto
     *   - 'estado' (string): Estado por defecto (def: 'publish')
     *   - 'meta' (callable): fn($item, $index) => array de metaEntrada
     *   - 'galeria' (callable): fn($item, $index) => array de galeriaAssets
     *   - 'contenido' (callable|int): fn($item) => string, o índice del item
     *   - 'extractoIndice' (int): Índice del item para usar como extracto
     * 
     * @return array Definiciones listas para define()
     * 
     * @example
     * // Forma simple
     * DefaultContentManager::build([
     *     ['Mi Título', 'imagen.jpg'],
     *     ['Otro Título', 'otra.jpg'],
     * ], ['alias' => 'images']);
     * 
     * @example
     * // Con meta personalizado
     * DefaultContentManager::build([
     *     ['Proyecto 1', 'p1.jpg', ['Cat1', 'Cat2'], true],
     *     ['Proyecto 2', 'p2.jpg', ['Cat3'], false],
     * ], [
     *     'alias' => 'images',
     *     'meta' => fn($item) => [
     *         'category' => $item[2] ?? [],
     *         'has_page' => ($item[3] ?? false) ? 'yes' : 'no',
     *     ],
     * ]);
     */
    public static function build(array $items, array $config = []): array
    {
        $alias = $config['alias'] ?? 'images';
        $parrafos = $config['parrafos'] ?? [];
        $extractoDefault = $config['extracto'] ?? '';
        $estadoDefault = $config['estado'] ?? 'publish';
        $metaBuilder = $config['meta'] ?? null;
        $galeriaBuilder = $config['galeria'] ?? null;
        $contenidoConfig = $config['contenido'] ?? null;
        $extractoIndice = $config['extractoIndice'] ?? null;

        $posts = [];
        foreach ($items as $index => $item) {
            // Si es array asociativo con slugDefault, usarlo directo
            if (isset($item['slugDefault'])) {
                $posts[] = $item;
                continue;
            }

            // Array simple: [titulo, imagen, ...extras]
            $titulo = $item[0] ?? '';
            $imagen = $item[1] ?? '';
            $slug = self::generarSlug($titulo);

            // Contenido
            $contenido = '';
            if (is_callable($contenidoConfig)) {
                $contenido = $contenidoConfig($item, $index);
            } elseif (is_int($contenidoConfig) && isset($item[$contenidoConfig])) {
                $contenido = '<p>' . esc_html($item[$contenidoConfig]) . '</p>';
            } elseif (!empty($parrafos)) {
                $contenido = '<p>' . implode('</p><p>', array_map('esc_html', $parrafos)) . '</p>';
            }

            // Extracto
            $extracto = $extractoDefault;
            if ($extractoIndice !== null && isset($item[$extractoIndice])) {
                $extracto = $item[$extractoIndice];
            }

            // Imagen destacada
            $imagenRef = '';
            if ($imagen !== '') {
                $imagenRef = (strpos($imagen, '::') !== false) ? $imagen : "{$alias}::{$imagen}";
            }

            $post = [
                'slugDefault' => $slug,
                'titulo' => $titulo,
                'contenido' => $contenido,
                'estado' => $estadoDefault,
                'extracto' => $extracto,
                'imagenDestacadaAsset' => $imagenRef,
            ];

            // Meta personalizado
            if ($metaBuilder && is_callable($metaBuilder)) {
                $meta = $metaBuilder($item, $index);
                if (!empty($meta)) {
                    $post['metaEntrada'] = $meta;
                }
            }

            // Galería
            if ($galeriaBuilder && is_callable($galeriaBuilder)) {
                $galeria = $galeriaBuilder($item, $index);
                if (!empty($galeria)) {
                    $post['galeriaAssets'] = $galeria;
                }
            }

            $posts[] = $post;
        }

        return $posts;
    }

    /**
     * Genera un slug limpio desde un título.
     */
    private static function generarSlug(string $titulo): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $titulo), '-'));
        return $slug ?: 'post-' . uniqid();
    }
}