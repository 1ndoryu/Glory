<?php

namespace Glory\Plugins\AmazonProduct\Service;

use Glory\Plugins\AmazonProduct\Model\Section;

/**
 * Servicio para gestionar secciones de productos.
 * 
 * Responsabilidades:
 * - CRUD de secciones en wp_options
 * - Obtener configuracion efectiva para shortcodes
 * - Restaurar secciones a valores default
 */
class SectionManager
{
    private const OPTION_KEY = 'glory_amazon_sections';

    private static ?array $sectionsCache = null;

    /* 
     * Operaciones de lectura 
     */

    /**
     * Obtiene todas las secciones registradas.
     * 
     * @return Section[]
     */
    public function getAll(): array
    {
        $data = $this->loadFromDb();
        $sections = [];

        foreach ($data as $slug => $sectionData) {
            $sections[$slug] = new Section($slug, $sectionData);
        }

        return $sections;
    }

    /**
     * Obtiene una seccion por su slug.
     */
    public function get(string $slug): ?Section
    {
        $data = $this->loadFromDb();
        $slug = $this->sanitizeSlug($slug);

        if (!isset($data[$slug])) {
            return null;
        }

        return new Section($slug, $data[$slug]);
    }

    /**
     * Verifica si existe una seccion.
     */
    public function exists(string $slug): bool
    {
        $data = $this->loadFromDb();
        return isset($data[$this->sanitizeSlug($slug)]);
    }

    /**
     * Obtiene la configuracion efectiva para un shortcode.
     * 
     * Flujo:
     * 1. Si la seccion existe en BD, merge defaults + overrides
     * 2. Si no existe, devuelve los atributos originales del shortcode
     * 
     * @param string $sectionSlug Slug de la seccion
     * @param array $shortcodeAtts Atributos originales del shortcode
     * @return array Configuracion efectiva + excluded_ids
     */
    public function getEffectiveConfig(string $sectionSlug, array $shortcodeAtts): array
    {
        $section = $this->get($sectionSlug);

        if ($section === null) {
            return [
                'config' => $shortcodeAtts,
                'excluded_ids' => [],
            ];
        }

        $effectiveConfig = array_merge($shortcodeAtts, $section->getOverrides());

        return [
            'config' => $effectiveConfig,
            'excluded_ids' => $section->getExcludedIds(),
        ];
    }

    /* 
     * Operaciones de escritura 
     */

    /**
     * Registra una nueva seccion o actualiza los defaults si ya existe.
     * 
     * Se llama automaticamente cuando se renderiza un shortcode con section="xxx".
     * 
     * @param string $slug Slug de la seccion
     * @param array $defaults Atributos por defecto del shortcode
     * @param string $sourceFile Archivo donde esta el shortcode
     * @param int $sourceLine Linea del archivo
     */
    public function register(string $slug, array $defaults, string $sourceFile = '', int $sourceLine = 0): Section
    {
        $slug = $this->sanitizeSlug($slug);
        $data = $this->loadFromDb();

        if (isset($data[$slug])) {
            $section = new Section($slug, $data[$slug]);
            $section->setDefaults($defaults);
            $section->setSourceInfo($sourceFile, $sourceLine);
        } else {
            $section = new Section($slug, [
                'defaults' => $defaults,
                'overrides' => [],
                'excluded_ids' => [],
                'source_file' => $sourceFile,
                'source_line' => $sourceLine,
            ]);
        }

        $this->save($section);
        return $section;
    }

    /**
     * Guarda una seccion.
     */
    public function save(Section $section): bool
    {
        $data = $this->loadFromDb();
        $data[$section->getSlug()] = $section->toArray();

        return $this->saveToDb($data);
    }

    /**
     * Actualiza los overrides de una seccion.
     */
    public function updateOverrides(string $slug, array $overrides): bool
    {
        $section = $this->get($slug);

        if ($section === null) {
            return false;
        }

        $section->setOverrides($overrides);
        return $this->save($section);
    }

    /**
     * Actualiza la lista de productos excluidos.
     */
    public function updateExcludedIds(string $slug, array $excludedIds): bool
    {
        $section = $this->get($slug);

        if ($section === null) {
            return false;
        }

        $section->setExcludedIds($excludedIds);
        return $this->save($section);
    }

    /**
     * Excluye un producto de una seccion.
     */
    public function excludeProduct(string $slug, int $productId): bool
    {
        $section = $this->get($slug);

        if ($section === null) {
            return false;
        }

        $section->excludeProduct($productId);
        return $this->save($section);
    }

    /**
     * Incluye un producto en una seccion (quita de excluidos).
     */
    public function includeProduct(string $slug, int $productId): bool
    {
        $section = $this->get($slug);

        if ($section === null) {
            return false;
        }

        $section->includeProduct($productId);
        return $this->save($section);
    }

    /**
     * Restaura una seccion a sus valores default.
     */
    public function reset(string $slug): bool
    {
        $section = $this->get($slug);

        if ($section === null) {
            return false;
        }

        $section->reset();
        return $this->save($section);
    }

    /**
     * Restaura todas las secciones a sus valores default.
     */
    public function resetAll(): bool
    {
        $sections = $this->getAll();

        foreach ($sections as $section) {
            $section->reset();
            $this->save($section);
        }

        return true;
    }

    /**
     * Elimina una seccion completamente.
     */
    public function delete(string $slug): bool
    {
        $data = $this->loadFromDb();
        $slug = $this->sanitizeSlug($slug);

        if (!isset($data[$slug])) {
            return false;
        }

        unset($data[$slug]);
        return $this->saveToDb($data);
    }

    /* 
     * Estadisticas 
     */

    /**
     * Obtiene el conteo de productos de una seccion.
     * Usa el QueryBuilder real para aplicar todos los filtros.
     */
    public function getProductCount(string $slug): int
    {
        $section = $this->get($slug);

        if ($section === null) {
            return 0;
        }

        $config = $section->getEffectiveConfig();
        $excludedIds = $section->getExcludedIds();

        $params = array_merge($config, [
            'limit' => -1,
            'paged' => 1,
            '_excluded_ids' => $excludedIds,
        ]);

        $queryBuilder = new \Glory\Plugins\AmazonProduct\Renderer\QueryBuilder();
        $query = $queryBuilder->build($params);

        $posts = [];
        while ($query->have_posts()) {
            $query->the_post();
            $posts[] = get_post();
        }
        wp_reset_postdata();

        $excludeWords = $queryBuilder->getExcludeWords($params);
        if (!empty($excludeWords)) {
            $posts = \Glory\Plugins\AmazonProduct\Renderer\QueryBuilder::filterExcludedPosts($posts, $excludeWords);
        }

        return count($posts);
    }

    /**
     * Obtiene estadisticas resumidas de todas las secciones.
     */
    public function getStats(): array
    {
        $sections = $this->getAll();

        return [
            'total' => count($sections),
            'modified' => count(array_filter($sections, fn($s) => $s->hasModifications())),
        ];
    }

    /**
     * Escanea archivos del tema para detectar secciones automaticamente.
     * Busca shortcodes [amazon_products section="xxx" ...] en archivos PHP.
     */
    public function scanSectionsFromFiles(): array
    {
        $themePath = get_template_directory();
        $foundSections = [];

        $directories = [
            $themePath . '/App/Templates',
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = file_get_contents($file->getPathname());

                $pattern = '/\[amazon_products\s+[^\]]*section=["\']([^"\']+)["\'][^\]]*\]/';

                if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $sectionSlug = $this->sanitizeSlug($match[1]);
                        $fullShortcode = $match[0];

                        $defaults = $this->parseShortcodeAttributes($fullShortcode);
                        unset($defaults['section']);

                        $relativePath = str_replace($themePath, '', $file->getPathname());

                        $foundSections[$sectionSlug] = [
                            'slug' => $sectionSlug,
                            'defaults' => $defaults,
                            'source_file' => ltrim($relativePath, '/\\'),
                            'shortcode' => $fullShortcode,
                        ];
                    }
                }
            }
        }

        return $foundSections;
    }

    /**
     * Escanea y registra todas las secciones encontradas en archivos.
     */
    public function syncSectionsFromFiles(): array
    {
        $found = $this->scanSectionsFromFiles();
        $registered = [];

        foreach ($found as $slug => $data) {
            $section = $this->register(
                $slug,
                $data['defaults'],
                $data['source_file'],
                0
            );
            $registered[] = $slug;
        }

        return $registered;
    }

    /**
     * Parsea atributos de un shortcode string.
     */
    private function parseShortcodeAttributes(string $shortcode): array
    {
        $attrs = [];

        $pattern = '/(\w+)=["\']([^"\']*)["\']|(\w+)=(\S+)/';

        if (preg_match_all($pattern, $shortcode, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (!empty($match[1])) {
                    $attrs[$match[1]] = $match[2];
                } elseif (!empty($match[3])) {
                    $attrs[$match[3]] = $match[4];
                }
            }
        }

        return $attrs;
    }

    /* 
     * Metodos privados 
     */

    /**
     * Carga los datos desde wp_options.
     */
    private function loadFromDb(): array
    {
        if (self::$sectionsCache !== null) {
            return self::$sectionsCache;
        }

        $data = get_option(self::OPTION_KEY, []);
        self::$sectionsCache = is_array($data) ? $data : [];

        return self::$sectionsCache;
    }

    /**
     * Guarda los datos en wp_options.
     */
    private function saveToDb(array $data): bool
    {
        self::$sectionsCache = $data;
        return update_option(self::OPTION_KEY, $data, false);
    }

    /**
     * Sanitiza el slug.
     */
    private function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9_-]/', '', $slug);
        return $slug ?: 'default';
    }

    /**
     * Limpia la cache (util para testing).
     */
    public function clearCache(): void
    {
        self::$sectionsCache = null;
    }
}
