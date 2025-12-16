<?php

namespace Glory\Plugins\AmazonProduct\Model;

/**
 * Modelo de Seccion para shortcodes dinamicos.
 * 
 * Representa una seccion de productos con:
 * - Nombre unico (slug)
 * - Valores por defecto del shortcode
 * - Overrides configurados desde el admin
 * - Lista de productos excluidos manualmente
 * - Metadata de origen (archivo, linea)
 */
class Section
{
    private string $slug;
    private array $defaults;
    private array $overrides;
    private array $excludedIds;
    private int $lastModified;
    private string $sourceFile;
    private int $sourceLine;

    /* 
     * Atributos soportados para defaults y overrides 
     */
    public const SUPPORTED_ATTRS = [
        'search',
        'exclude',
        'orderby',
        'order',
        'limit',
        'only_deals',
        'only_prime',
        'min_price',
        'max_price',
        'min_rating',
        'category',
        'hide_filters',
        'pagination',
    ];

    public function __construct(string $slug, array $data = [])
    {
        $this->slug = $this->sanitizeSlug($slug);
        $this->defaults = $data['defaults'] ?? [];
        $this->overrides = $data['overrides'] ?? [];
        $this->excludedIds = array_map('intval', $data['excluded_ids'] ?? []);
        $this->lastModified = (int) ($data['last_modified'] ?? time());
        $this->sourceFile = $data['source_file'] ?? '';
        $this->sourceLine = (int) ($data['source_line'] ?? 0);
    }

    /* 
     * Getters 
     */

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function getOverrides(): array
    {
        return $this->overrides;
    }

    public function getExcludedIds(): array
    {
        return $this->excludedIds;
    }

    public function getLastModified(): int
    {
        return $this->lastModified;
    }

    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }

    public function getSourceLine(): int
    {
        return $this->sourceLine;
    }

    /* 
     * Setters 
     */

    public function setDefaults(array $defaults): void
    {
        $this->defaults = $this->filterAttrs($defaults);
        $this->touch();
    }

    public function setOverrides(array $overrides): void
    {
        $this->overrides = $this->filterAttrs($overrides);
        $this->touch();
    }

    public function setExcludedIds(array $ids): void
    {
        $this->excludedIds = array_map('intval', array_filter($ids));
        $this->touch();
    }

    public function setSourceInfo(string $file, int $line): void
    {
        $this->sourceFile = $file;
        $this->sourceLine = $line;
    }

    /* 
     * Logica de negocio 
     */

    /**
     * Obtiene la configuracion efectiva (defaults + overrides).
     * Los overrides tienen prioridad sobre los defaults.
     */
    public function getEffectiveConfig(): array
    {
        return array_merge($this->defaults, $this->overrides);
    }

    /**
     * Verifica si un atributo ha sido modificado del default.
     */
    public function hasOverride(string $attr): bool
    {
        return isset($this->overrides[$attr]);
    }

    /**
     * Obtiene el valor de un atributo (override o default).
     */
    public function getAttr(string $attr, $default = ''): string
    {
        if (isset($this->overrides[$attr])) {
            return (string) $this->overrides[$attr];
        }
        if (isset($this->defaults[$attr])) {
            return (string) $this->defaults[$attr];
        }
        return (string) $default;
    }

    /**
     * Establece un override para un atributo.
     */
    public function setOverride(string $attr, $value): void
    {
        if (in_array($attr, self::SUPPORTED_ATTRS, true)) {
            $this->overrides[$attr] = $value;
            $this->touch();
        }
    }

    /**
     * Elimina un override, volviendo al valor default.
     */
    public function removeOverride(string $attr): void
    {
        unset($this->overrides[$attr]);
        $this->touch();
    }

    /**
     * Elimina todos los overrides.
     */
    public function clearOverrides(): void
    {
        $this->overrides = [];
        $this->touch();
    }

    /**
     * Agrega un producto a la lista de excluidos.
     */
    public function excludeProduct(int $productId): void
    {
        if (!in_array($productId, $this->excludedIds, true)) {
            $this->excludedIds[] = $productId;
            $this->touch();
        }
    }

    /**
     * Quita un producto de la lista de excluidos.
     */
    public function includeProduct(int $productId): void
    {
        $this->excludedIds = array_filter(
            $this->excludedIds,
            fn($id) => $id !== $productId
        );
        $this->touch();
    }

    /**
     * Verifica si un producto esta excluido.
     */
    public function isProductExcluded(int $productId): bool
    {
        return in_array($productId, $this->excludedIds, true);
    }

    /**
     * Restaura la seccion a su estado original (sin overrides ni exclusiones).
     */
    public function reset(): void
    {
        $this->overrides = [];
        $this->excludedIds = [];
        $this->touch();
    }

    /**
     * Verifica si la seccion tiene modificaciones.
     */
    public function hasModifications(): bool
    {
        return !empty($this->overrides) || !empty($this->excludedIds);
    }

    /**
     * Convierte a array para guardar.
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'defaults' => $this->defaults,
            'overrides' => $this->overrides,
            'excluded_ids' => $this->excludedIds,
            'last_modified' => $this->lastModified,
            'source_file' => $this->sourceFile,
            'source_line' => $this->sourceLine,
        ];
    }

    /* 
     * Metodos privados 
     */

    /**
     * Actualiza el timestamp de modificacion.
     */
    private function touch(): void
    {
        $this->lastModified = time();
    }

    /**
     * Sanitiza el slug de la seccion.
     */
    private function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9_-]/', '', $slug);
        return $slug ?: 'default';
    }

    /**
     * Filtra solo los atributos soportados.
     */
    private function filterAttrs(array $attrs): array
    {
        return array_intersect_key($attrs, array_flip(self::SUPPORTED_ATTRS));
    }
}
