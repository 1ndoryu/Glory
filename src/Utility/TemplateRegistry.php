<?php

namespace Glory\Utility;

/**
 * Registro global y agnóstico para plantillas de renderizado de contenido.
 * El tema puede registrar funciones (callables) con un identificador y etiqueta.
 * Cada plantilla puede declarar a qué post types aplica.
 */
class TemplateRegistry
{
    /** @var array<string, array{label:string, callable:callable, applies_to:array}> */
    protected static $templates = [];

    /**
     * Registrar una plantilla
     *
     * @param string   $id
     * @param string   $label
     * @param callable $callable Firma recomendada: callable(\WP_Post $post, string $itemClass): void
     * @param string[] $appliesTo Lista de post types a los que aplica (vacío = aplica a todos)
     */
    public static function register(string $id, string $label, callable $callable, array $appliesTo = []): void
    {
        self::$templates[$id] = [
            'label'      => $label,
            'callable'   => $callable,
            'applies_to' => $appliesTo,
        ];
    }

    /**
     * Devuelve opciones id => label, opcionalmente filtradas por post type
     *
     * @param string|null $postType
     * @return array<string, string>
     */
    public static function options(string $postType = null): array
    {
        $opts = [];
        foreach (self::$templates as $id => $def) {
            if ($postType !== null && !empty($def['applies_to'])) {
                if (!in_array($postType, $def['applies_to'], true)) {
                    continue;
                }
            }
            $opts[$id] = $def['label'];
        }
        return $opts;
    }

    public static function get(string $id): ?callable
    {
        return isset(self::$templates[$id]) ? self::$templates[$id]['callable'] : null;
    }

    /**
     * @return string[] Lista de post types a los que aplica una plantilla
     */
    public static function appliesTo(string $id): array
    {
        return isset(self::$templates[$id]) ? (array) self::$templates[$id]['applies_to'] : [];
    }
}


